<template>
    <div id="app-content" :class="getContentClass">
        <div class="app-content-left">
            <breadcrumb :newFolder="true" :folder="currentFolder" :items="breadcrumb" :showAddNew="showAddNew"/>
            <div class="item-list">
                <header-line :by="sort.by" :order="sort.order" v-on:updateSorting="updateSorting($event)" v-if="showHeaderAndFooter"/>
                <folder-line :folder="folder" v-for="folder in folders" :key="folder.id" draggable="true"/>
                <password-line :password="password" v-for="password in passwords" :key="password.id" draggable="true"/>
                <footer-line :passwords="passwords" :folders="folders" v-if="showHeaderAndFooter"/>
                <empty v-if="isEmpty"/>
            </div>
        </div>
        <div class="app-content-right">
            <password-details v-if="showPasswordDetails" :password="detail.element"/>
        </div>
    </div>
</template>

<script>
    import API from '@js/Helper/api';
    import Breadcrumb from '@vc/Breadcrumbs';
    import Utility from "@js/Classes/Utility";
    import FolderLine from '@vue/Line/Folder';
    import Empty from "@vue/Components/Empty";
    import HeaderLine from "@vue/Line/Header";
    import FooterLine from "@vue/Line/Footer";
    import PasswordLine from '@vue/Line/Password';
    import BaseSection from '@vue/Section/BaseSection';
    import PasswordDetails from '@vue/Details/Password';

    export default {
        extends: BaseSection,

        components: {
            Empty,
            Breadcrumb,
            FolderLine,
            HeaderLine,
            FooterLine,
            PasswordLine,
            PasswordDetails
        },

        data() {
            return {
                defaultFolder: '00000000-0000-0000-0000-000000000000',
                defaultTitle : Utility.translate('Folders'),
                defaultPath  : '/folders',
                currentFolder: '00000000-0000-0000-0000-000000000000',
                draggable    : 'true',
                folders      : [],
                showAddNew   : true,
                breadcrumb   : []
            };
        },

        methods: {
            refreshView          : function() {
                this.loading = true;
                this.folders = [];
                this.passwords = [];
                this.detail.type = 'none';
                if(this.$route.params.folder !== undefined) {
                    API.showFolder(this.$route.params.folder, 'model+folders+passwords+parent').then(this.updateContentList);
                } else {
                    API.showFolder(this.defaultFolder, 'model+folders+passwords').then(this.updateContentList);
                }
            },
            refreshViewIfRequired: function(data) {
                let object = data.object;

                if(object.type === 'password' && (object.folder === this.currentFolder || object.folder.id === this.currentFolder)) {
                    if(object.trashed) {
                        this.passwords = Utility.removeApiObjectFromArray(this.passwords, object);
                    } else {
                        let passwords = Utility.replaceOrAppendApiObject(this.passwords, object);
                        this.passwords = Utility.sortApiObjectArray(passwords, 'label', true);
                    }
                } else if(object.type === 'folder' && object.id === this.currentFolder) {
                    if(object.trashed) {
                        this.loading = true;
                        this.detail.type = 'none';
                        API.showFolder(this.defaultFolder, 'model+folders+passwords').then(this.updateContentList);
                    } else if(object.passwords && object.folders && typeof object.parent !== 'string') {
                        this.updateContentList(object);
                    } else {
                        this.loading = true;
                        this.detail.type = 'none';
                        API.showFolder(this.currentFolder, 'model+folders+passwords+parent').then(this.updateContentList);
                    }
                } else if(object.type === 'folder' && object.parent === this.currentFolder) {
                    if(object.trashed) {
                        this.folders = Utility.removeApiObjectFromArray(this.folders, object);
                    } else {
                        let folders = Utility.replaceOrAppendApiObject(this.folders, object);
                        this.folders = Utility.sortApiObjectArray(folders, 'label', true);
                    }
                } else if(object.type === 'folder' && Utility.searchApiObjectInArray(this.folders, object) !== -1) {
                    this.folders = Utility.removeApiObjectFromArray(this.folders, object);
                } else if(object.type === 'password' && Utility.searchApiObjectInArray(this.passwords, object) !== -1) {
                    this.passwords = Utility.removeApiObjectFromArray(this.passwords, object);
                }
            },
            updateContentList    : function(folder) {
                this.loading = false;
                if(folder.trashed) {
                    this.defaultTitle = Utility.translate('Trash');
                    this.defaultPath = '/trash';
                    this.showAddNew = false;
                    this.draggable = false;
                } else if(this.defaultPath === '/trash' && this.$route.params.folder === undefined) {
                    this.defaultTitle = Utility.translate('Folders');
                    this.defaultPath = '/folders';
                    this.showAddNew = true;
                    this.draggable = true;
                }

                this.folders = Utility.sortApiObjectArray(folder.folders, this.sort.by, this.sort.order);
                this.passwords = Utility.sortApiObjectArray(folder.passwords, this.sort.by, this.sort.order);
                this.currentFolder = folder.id;
                this.updateBreadcrumb(folder);
            },
            updateBreadcrumb     : function(folder) {
                this.breadcrumb = [
                    {path: this.defaultPath, label: this.defaultTitle, dropType: 'folder', folderId: this.defaultFolder}
                ];

                if(typeof folder.parent !== 'string' && folder.parent.id !== this.defaultFolder) {
                    this.breadcrumb[0].label = '…';
                    let parent = folder.parent;
                    this.breadcrumb.push(
                        {
                            path    : '/folders/' + parent.id,
                            label   : parent.label,
                            dropType: 'folder',
                            folderId: parent.id
                        }
                    );
                }

                if(folder.id !== this.defaultFolder) {
                    this.breadcrumb.push(
                        {
                            path    : this.$route.path,
                            label   : folder.label,
                            dropType: 'folder',
                            folderId: folder.id
                        }
                    );
                }
            }
        },

        watch: {
            $route: function() {
                this.refreshView();
            }
        }
    };
</script>