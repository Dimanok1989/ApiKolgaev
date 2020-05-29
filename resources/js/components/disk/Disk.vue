<style scoped>
    #users-list button {
        outline: none;
    }
    #users-list button.active {
        background-color: #c6d3ff;
        border-color: #c6d3ff;
        color: #000;
        font-weight: 700;
    }
    .files-table {
        font-size: 90%;
    }
</style>

<template>

    <div>

        <b-card>

            <b-card-title>
                <span>Наши файлы</span>
                <b-icon-cloud-upload @click="openFileInput" class="for-hover cursor-pointer ml-2" v-if="user.id == selectedUser" />
            </b-card-title>

            <b-card-text class="mt-3" v-if="doneUploadFlag">
                <div class="mb-1">
                    <span>Файлов для загрузки:</span>
                    <strong>{{ filesUploadList.length }}/{{ filesUploaded.length }}</strong>
                    <span class="ml-2"><i>{{ fileCurrent }}</i> <b v-if="fileProgress">{{ fileProgress }}%</b></span>
                </div>
                <b-progress height="20px" :value="progress" show-progress class="mb-2"></b-progress>
                <hr>
            </b-card-text>

            <input type="file" class="d-none" id="input-upload-files" name="files" multiple="true" @change="startUploadFiles" />

            <b-card-text class="mt-3">
                <b-row>
                    <b-col sm="4">
                        <b-list-group id="users-list" flush>
                            <b-list-group-item
                                class="d-flex align-items-center py-1 px-3"
                                v-for="user in users"
                                :key="user.id"
                                @click="getUserFiles(user.id)"
                                :active="user.id == selectedUser"
                                button
                                :disabled="loadingUser"
                            >

                                <b-avatar class="mr-3" :text="String(user.name)[0]+(user.surname ? String(user.surname)[0] : '')"></b-avatar>

                                <span class="mr-auto">{{ user.name }}{{ user.surname ? " "+user.surname : "" }}</span>

                                <b-spinner type="grow" label="Spinning" small v-if="user.id == selectedUser && loadingUser ? true : false"></b-spinner>

                            </b-list-group-item>
                        </b-list-group>
                    </b-col>
                    <b-col sm="8">
                        <b-overlay :show="isBusy" rounded="sm" variant="white" opacity="0.7">
                            <div>
                                <b-link @click="getUserFiles(selectedUser)">Файлы</b-link>
                                <span v-for="path in paths" :key="path">
                                    <b-icon-chevron-right/>
                                    <b-link @click="openOneFolder(path)">{{ path }}</b-link>
                                </span>
                            </div>

                            <b-table class="files-table mt-3" 
                                small
                                :items="files"
                                hover
                                striped
                                :fields="fields"
                                responsive
                                stacked="sm"
                                ref="filetable"
                                selectable
                                @row-selected="onRowSelected"
                                select-mode="multi"
                                selected-variant="success"
                            >

                                <template v-slot:cell(name)="data">
                                    <b-link @click="openFolder(data.item.path)" v-if="data.item.ext == 'Папка'">{{ data.value }}</b-link>
                                    <b-link :href="data.item.link" v-else :download="data.value">{{ data.value }}</b-link>
                                </template>

                                <template v-slot:head(selected)>
                                    <div v-if="user.id == selectedUser && !isBusy" class="text-center cursor-pointer">
                                        <span class="for-hover">
                                            <b-icon-square
                                                @click="selectedRows"
                                                v-if="selected.length == 0" 
                                            />
                                            <b-icon-dash-square
                                                v-if="selected.length > 0 && selected.length < files.length" 
                                                @click="selectedRows"
                                            />
                                            <b-icon-check-square
                                                @click="selectedRows"
                                                v-if="selected.length == files.length"
                                            />
                                        </span>
                                    </div>
                                </template>

                                <template v-slot:cell(selected)="{ rowSelected }">
                                    <div class="text-center">
                                        <b-icon-check-square
                                            v-if="user.id == selectedUser && rowSelected"
                                        />
                                        <b-icon-square 
                                            v-if="user.id == selectedUser && !rowSelected"
                                        />
                                    </div>
                                </template>
                            </b-table> 
                            <div v-if="files.length > 0">Выбрано: <strong>{{ selected.length }}</strong></div>
                            <div v-else>
                                <div class="text-center text-muted my-5">Файлов нет</div>
                            </div>
                        </b-overlay>
                    </b-col>
                </b-row>
            </b-card-text>
        </b-card>

    </div>

</template>

<script>
    export default {

        props: {
            login: {
                default: false
            },
            // Данные текущего пользователя
            user: {
                default: {}
            },
        },

        data() {
            return {

                loading: false, // Глобальная загрузка страницы

                users: [], // Список пользователей
                selectedUser: false, // Выбранный пользователь
                loadingUser: false, // Статус загрузки файлов пользователя
                files: [], // Список файлов

                isBusy: true, // Загрузка таблицы
                // Колонки таблицы с файлами
                fields: [
                    { key: 'selected', label: '' },
                    { key: 'name', label: 'Имя' },
                    { key: 'ext', label: 'Тип' },
                    { key: 'size', label: 'Размер' },
                    { key: 'time', label: 'Дата' },
                ],
                selected: [], // Выбранные строки таблицы

                paths: [], // Массив пути до подкаталога
                cd: "", // Путь до подкаталога

                filesUploadList: [], // Список файлов для загрузки
                filesUploaded: [], // Список загуженных файлов
                fileCurrent: "", // Наименование файла текущего в загрузке
                fileProgress: 0, // Процент загрузки файла
                progress: 0, // Общий процент загрузки файлов
                doneUploadFlag: false, // Завершение загрузки
                
            }
        },

        beforeMount() {

            if (!this.login)
                this.$router.push('/404');

        },

        async mounted() {

            await this.getUsersList();

            let user = this.user.id ?? 0;
            await this.getUserFiles(user);

        },

        methods: {

            /**
             * Получение списка пользователей, доступным данный модуль
             */
            async getUsersList() {

                await axios.get('/api/disk/getUsersList').then(({data}) => {

                    this.users = data.users;

                }).catch(error => {

                    console.log(error.response.data, error.response);

                });

            },

            /**
             * Получение списка файлов пользователя
             */
            async getUserFiles(id = 0, path = false) {

                if (!id)
                    return false;

                this.selectedUser = id;
                this.loadingUser = true;
                this.isBusy = true;

                await axios.post('/api/disk/getUserFiles', {id, path}).then(({data}) => {

                    this.files = [];
                    this.paths = data.paths;
                    this.cd = data.cd;

                    data.dirs.forEach(dir => {                  
                        this.files.push(dir);
                    });

                    data.files.forEach(file => {
                        this.files.push(file);
                    });

                }).catch(error => {

                    console.log(error.response.data, error.response);

                }).then(() => {

                    this.loadingUser = false;
                    this.isBusy = false;

                });

            },

            selectedRows() {
                if (this.selected.length == 0)
                    this.selectAllRows();
                else
                    this.clearSelected();
            },
            onRowSelected(items) {
                this.selected = items;
            },
            selectAllRows() {
                this.$refs.filetable.selectAllRows();
            },
            clearSelected() {
                this.$refs.filetable.clearSelected();
            },

            openFolder(path = false) {
                this.getUserFiles(this.selectedUser, path);
            },
            openOneFolder(pathpart = false) {

                let path = "";
                this.paths.forEach(part => {

                    path += "/" + part;

                    if (part == pathpart)
                        return this.openFolder(path);

                });

                return this.openFolder(path);

            },

            openFileInput() {
                document.getElementById('input-upload-files').click();
            },

            async startUploadFiles() {

                this.doneUpload();

                let files = Array.from(event.target.files);
                this.filesUploadList = files.slice();
                
                console.log(files, this.filesUploadList);

                for (let file in files)
                    await this.uploadFile(files[file]);

            },

            async uploadFile(file) {

                let form = new FormData();
                form.append('files', file);
                form.append('user', this.user.id);
                form.append('cd', this.cd);

                await axios.post('/api/disk/uploadFile', form, {

                    onUploadProgress: (itemUpload) => {

                        this.fileProgress = Math.round((itemUpload.loaded / itemUpload.total) * 100);

                        this.progress = Math.round(((this.filesUploaded.length * 100) + this.fileProgress) / this.filesUploadList.length, 1);

                        this.fileCurrent = file.name;

                    }

                }).then(response => {

                    this.fileProgress = 0;
                    this.fileCurrent = '';
                    this.filesUploaded.push(file);

                    if (this.filesUploaded.length == this.filesUploadList.length) {
                        document.getElementById('input-upload-files').value = '';
                        this.openOneFolder();
                        this.doneUploadFlag = false;
                    }

                }).catch(error => {
                    console.log(error.response);
                });

            },

            doneUpload() {
                this.doneUploadFlag = true;
                this.filesUploadList = [];
                this.filesUploaded = [];
                this.progress = 0;
            },

        },

    }
</script>