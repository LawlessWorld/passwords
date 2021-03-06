<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\AvatarService;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FaviconService;
use OCA\Passwords\Services\WebsitePreviewService;
use OCA\Passwords\Services\WordsService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\IRequest;

/**
 * Class ServiceApiController
 *
 * @package OCA\Passwords\Controller
 */
class ServiceApiController extends AbstractApiController {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var WordsService
     */
    protected $wordsService;

    /**
     * @var AvatarService
     */
    protected $avatarService;

    /**
     * @var FaviconService
     */
    protected $faviconService;

    /**
     * @var WebsitePreviewService
     */
    protected $previewService;

    /**
     * ServiceApiController constructor.
     *
     * @param IRequest              $request
     * @param WordsService          $wordsService
     * @param AvatarService         $avatarService
     * @param ConfigurationService  $config
     * @param FaviconService        $faviconService
     * @param WebsitePreviewService $previewService
     */
    public function __construct(
        IRequest $request,
        WordsService $wordsService,
        AvatarService $avatarService,
        ConfigurationService $config,
        FaviconService $faviconService,
        WebsitePreviewService $previewService
    ) {
        parent::__construct($request);
        $this->faviconService = $faviconService;
        $this->wordsService   = $wordsService;
        $this->previewService = $previewService;
        $this->avatarService  = $avatarService;
        $this->config         = $config;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param int  $strength
     * @param bool $numbers
     * @param bool $special
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function generatePassword(?int $strength = null, ?bool $numbers = null, ?bool $special = null): JSONResponse {
        if($strength === null) $strength = $this->config->getUserValue('password/generator/strength', 1);
        if($numbers === null) $numbers = $this->config->getUserValue('password/generator/numbers', false);
        if($special === null) $special = $this->config->getUserValue('password/generator/special', false);

        list($password, $words) = $this->wordsService->getPassword($strength, $numbers, $special);
        if(empty($password)) throw new ApiException('Unable to generate password');

        return $this->createJsonResponse(
            [
                'password' => $password,
                'words'    => $words,
                'strength' => $strength,
                'numbers'  => $numbers,
                'special'  => $special
            ]
        );
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $user
     * @param int    $size
     *
     * @return FileDisplayResponse|JSONResponse
     * @throws \Throwable
     */
    public function getAvatar(string $user, int $size = 32) {
        $file = $this->avatarService->getAvatar($user, $size);

        return $this->createFileDisplayResponse($file);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $domain
     * @param int    $size
     *
     * @return FileDisplayResponse|JSONResponse
     * @throws \Throwable
     */
    public function getFavicon(string $domain, int $size = 32) {
        $file = $this->faviconService->getFavicon($domain, $size);

        return $this->createFileDisplayResponse($file);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $domain
     * @param string $view
     * @param string $width
     * @param string $height
     *
     * @return FileDisplayResponse|JSONResponse
     * @throws ApiException
     * @throws \OCP\AppFramework\QueryException
     */
    public function getPreview(string $domain, string $view = 'desktop', string $width = '640', string $height = '360...') {
        list($minWidth, $maxWidth) = $this->validatePreviewSize($width);
        list($minHeight, $maxHeight) = $this->validatePreviewSize($height);

        $file = $this->previewService->getPreview($domain, $view, $minWidth, $minHeight, $maxWidth, $maxHeight);

        return $this->createFileDisplayResponse($file);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function coffee(): JSONResponse {
        throw new ApiException('I’m a password manager', 418);
    }

    /**
     * @param ISimpleFile $file
     * @param int         $statusCode
     *
     * @return FileDisplayResponse
     */
    protected function createFileDisplayResponse(ISimpleFile $file, int $statusCode = Http::STATUS_OK): FileDisplayResponse {
        return new FileDisplayResponse(
            $file,
            $statusCode,
            ['Content-Type' => $file->getMimeType()]
        );
    }

    /**
     * @param $size
     *
     * @return array
     * @throws ApiException
     */
    protected function validatePreviewSize($size) {
        if(is_numeric($size)) {
            return [intval($size), intval($size)];
        } else if(preg_match("/([0-9]+)?\.\.\.([0-9]+)?/", $size, $matches)) {
            if(!isset($matches[1])) $matches[1] = 0;
            if(!isset($matches[2])) $matches[2] = 0;

            return [intval($matches[1]), intval($matches[2])];
        }

        throw new ApiException('Invalid dimensions given', 400);
    }
}