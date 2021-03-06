<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :deleteAll="true" :newPassword="false" v-on:deleteAll="clearTrash"/>
            <div class="item-list">
                <header-line :by="sort.by" :order="sort.order" v-on:updateSorting="updateSorting($event)" v-if="showHeaderAndFooter"/>
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id">
                    <translate tag="li" icon="undo" slot="menu-top" @click="restoreFolderAction(folder)" say="Restore"/>
                </folder-line>
                <tag-line :tag="tag" v-for="tag in tags" :key="tag.id">
                    <translate tag="li" icon="undo" slot="menu-top" @click="restoreTagAction(tag)" say="Restore"/>
                </tag-line>
                <password-line :password="password" v-for="password in passwords" v-if="password.trashed" :key="password.id">
                    <translate tag="li" icon="undo" slot="menu-top" @click="restorePasswordAction(password)" say="Restore"/>
                </password-line>
                <footer-line :passwords="passwords" :folders="folders" :tags="tags" v-if="showHeaderAndFooter"/>
                <empty v-if="isEmpty" text="Deleted items will appear here"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="showPasswordDetails" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import TagLine from '@vue/Line/Tag';
    import Translate from '@vc/Translate';
    import Breadcrumb from '@vc/Breadcrumbs';
    import FolderLine from '@vue/Line/Folder';
    import HeaderLine from "@vue/Line/Header";
    import FooterLine from "@vue/Line/Footer";
    import Empty from "@vue/Components/Empty";
    import Messages from "@js/Classes/Messages";
    import PasswordLine from '@vue/Line/Password';
    import TagManager from '@js/Manager/TagManager';
    import BaseSection from '@vue/Section/BaseSection';
    import PasswordDetails from '@vue/Details/Password';
    import FolderManager from '@js/Manager/FolderManager';
    import PasswordManager from '@js/Manager/PasswordManager';

    export default {
        extends: BaseSection,

        data() {
            return {
                folders  : [],
                tags     : []
            };
        },

        components: {
            Empty,
            TagLine,
            Translate,
            Breadcrumb,
            FolderLine,
            HeaderLine,
            FooterLine,
            PasswordLine,
            PasswordDetails
        },

        methods: {
            refreshView       : function() {
                API.findPasswords({trashed: true}).then(this.updatePasswordList);
                API.findFolders({trashed: true}).then(this.updateFolderList);
                API.findTags({trashed: true}).then(this.updateTagList);
            },
            restorePasswordAction(password) {
                PasswordManager.restorePassword(password);
                API.findPasswords({trashed: true}).then(this.updatePasswordList);
            },
            restoreFolderAction(folder) {
                FolderManager.restoreFolder(folder);
                API.findFolders({trashed: true}).then(this.updateFolderList);
            },
            restoreTagAction(tag) {
                TagManager.restoreTag(tag);
                API.findTags({trashed: true}).then(this.updateTagList);
            },
            clearTrash() {
                Messages.confirm('Delete all items in trash?', 'Empty Trash')
                        .then(() => {
                            for(let i = 0; i < this.passwords.length; i++) {
                                PasswordManager.deletePassword(this.passwords[i], false);
                            }
                            for(let i = 0; i < this.folders.length; i++) {
                                FolderManager.deleteFolder(this.folders[i], false);
                            }
                            for(let i = 0; i < this.tags.length; i++) {
                                TagManager.deleteTag(this.tags[i], false);
                            }

                            Messages.notification('Trash emptied');
                        });
            }
        }
    };
</script>