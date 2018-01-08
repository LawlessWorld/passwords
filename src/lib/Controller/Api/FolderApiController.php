<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 09.10.17
 * Time: 13:44
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\ApiObjects\FolderObjectHelper;
use OCA\Passwords\Services\EncryptionService;
use OCA\Passwords\Services\Object\FolderRevisionService;
use OCA\Passwords\Services\Object\FolderService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class FolderApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class FolderApiController extends AbstractObjectApiController {

    /**
     * @var FolderService
     */
    protected $modelService;

    /**
     * @var FolderObjectHelper
     */
    protected $objectHelper;

    /**
     * @var FolderRevisionService
     */
    protected $revisionService;

    /**
     * @var array
     */
    protected $allowedFilterFields = ['created', 'updated', 'cseType', 'sseType', 'trashed', 'favourite'];

    /**
     * PasswordApiController constructor.
     *
     * @param string                $appName
     * @param IRequest              $request
     * @param FolderService         $modelService
     * @param FolderRevisionService $revisionService
     * @param FolderObjectHelper    $objectHelper
     */
    public function __construct(
        $appName,
        IRequest $request,
        FolderService $modelService,
        FolderRevisionService $revisionService,
        FolderObjectHelper $objectHelper
    ) {
        parent::__construct($appName, $request, $modelService, $revisionService, $objectHelper);
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $label
     * @param string $parent
     * @param string $cseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @TODO check $parent access
     *
     * @return JSONResponse
     */
    public function create(
        string $label,
        string $parent,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {
        try {
            $this->checkAccessPermissions();
            $folder   = $this->modelService->create();
            $revision = $this->revisionService->create(
                $folder->getUuid(), $label, $parent, $cseType, $hidden, false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($folder, $revision);

            return $this->createJsonResponse(
                ['id' => $folder->getUuid(), 'revision' => $revision->getUuid()],
                Http::STATUS_CREATED
            );
        } catch(\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $id
     * @param string $label
     * @param string $parent
     * @param string $cseType
     * @param bool   $hidden
     * @param bool   $favourite
     *
     * @TODO check $parent access
     *
     * @return JSONResponse
     */
    public function update(
        string $id,
        string $label,
        string $parent,
        string $cseType = EncryptionService::DEFAULT_CSE_ENCRYPTION,
        bool $hidden = false,
        bool $favourite = false
    ): JSONResponse {
        try {
            $this->checkAccessPermissions();
            if($id === $this->modelService::BASE_FOLDER_UUID) throw new ApiException('Can not edit base folder', 422);

            $folder = $this->modelService->findByUuid($id);

            $revision = $this->revisionService->create(
                $folder->getUuid(), $label, $parent, $cseType, $hidden, false, $favourite
            );

            $this->revisionService->save($revision);
            $this->modelService->setRevision($folder, $revision);

            return $this->createJsonResponse(['id' => $folder->getUuid(), 'revision' => $revision->getUuid()]);
        } catch(\Throwable $e) {
            return $this->createErrorResponse($e);
        }
    }

    /**
     * @param string $id
     *
     * @return JSONResponse
     */
    public function delete(string $id): JSONResponse {
        if($id === $this->modelService::BASE_FOLDER_UUID) {
            return $this->createErrorResponse(
                new ApiException('Can not edit base folder', 422)
            );
        }

        return parent::delete($id);
    }

    /**
     * @param string $id
     * @param null   $revision
     *
     * @return JSONResponse
     */
    public function restore(string $id, $revision = null): JSONResponse {
        if($id === $this->modelService::BASE_FOLDER_UUID || $revision == $this->revisionService::BASE_REVISION_UUID) {
            return $this->createErrorResponse(
                new ApiException('Can not edit base folder', 422)
            );
        }

        return parent::restore($id, $revision);
    }
}