<style scoped>
    #users-list button {
        outline: none;
        border: none;
        border-radius: 5px;
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
    .files-tables td {
        vertical-align: middle;
    }
</style>

<template>

    <div>

        <b-card>

            <b-card-title>
                <span>Наше хранилище</span>
                <b-icon-cloud-upload @click="openFileInput" class="for-hover cursor-pointer ml-2" v-if="user.id == selectedUser" />
                <b-icon-folder-plus @click="mkdir" class="for-hover cursor-pointer ml-2" v-if="user.id == selectedUser" />
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
                <div class="d-flex justify-content-start align-items-start">
                    <div>
                        <b-list-group id="users-list" flush>
                            
                            <b-list-group-item
                                class="d-flex align-items-center p-2"
                                v-for="user in users"
                                :key="user.id"
                                @click="getUserFiles(user.id)"
                                :active="user.id == selectedUser"
                                button
                                :disabled="loadingUser"
                            >

                                <b-overlay :show="isBusy && user.id == selectedUser" rounded="sm" variant="white" opacity="0.7" spinner-type="grow" spinner-small spinner-variant="dark">

                                    <b-avatar :text="String(user.name)[0]+(user.surname ? String(user.surname)[0] : '')"></b-avatar>

                                    <!-- <span class="mr-auto">{{ user.name }}{{ user.surname ? " "+user.surname : "" }}</span> -->

                                    <!-- <b-spinner type="grow" label="Spinning" small v-if="user.id == selectedUser && loadingUser ? true : false"></b-spinner> -->

                                </b-overlay>

                            </b-list-group-item>
                            
                        </b-list-group>
                    </div>
                    <div class="p-2"></div>
                    <div class="flex-grow-1">
                        <b-overlay :show="isBusy || mkdirWait" rounded="sm" variant="white" opacity="0.7">

                            <template v-slot:overlay>

                                <div v-if="download" class="text-center p-4 bg-primary text-light rounded">
                                    <b-icon icon="cloud-download" font-scale="4" animation="fade"></b-icon>
                                    <div class="mb-3">Загрузка...</div>
                                    <b-progress
                                        min="1"
                                        max="20"
                                        :value="downloadProgress"
                                        variant="success"
                                        height="3px"
                                        class="mx-n4 rounded-0"
                                    ></b-progress>
                                </div>
                                <div v-else></div>
                            </template>

                            <div>
                                <b-icon-folder-fill class="mr-1 text-muted"/>
                                <b-link @click="getUserFiles(selectedUser)">Файлы</b-link>
                                <span v-for="path in paths" :key="path.id">
                                    <b-icon-chevron-right/>
                                    <b-link @click="openFolder(path.id)">{{ path.name }}</b-link>
                                </span>
                            </div>

                            <b-table
                                table-class="files-table mt-2" 
                                small
                                :items="files"
                                hover
                                striped2
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
                                    <b-icon :icon="getIconName(data.item.ext)"></b-icon>
                                    <b-link @click="openFolder(data.item.id)" v-if="data.item.ext == 'Папка'">{{ data.value }}</b-link>
                                    <span v-else>{{ data.value }}</span>
                                    <!-- <b-link :href="data.item.link" v-else :download="data.value">{{ data.value }}</b-link> -->
                                </template>

                                <template v-slot:head(selected)>
                                    <div v-if="user.id == selectedUser && !isBusy" class="text-center cursor-pointer for-hover">
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
                                            v-if="selected.length == files.length && files.length != 0"
                                        />
                                    </div>
                                </template>

                                <template v-slot:cell(selected)="{ rowSelected }">
                                    <div class="text-center cursor-pointer for-hover">
                                        <b-icon-check-square
                                            v-if="user.id == selectedUser && rowSelected"
                                        />
                                        <b-icon-square 
                                            v-if="user.id == selectedUser && !rowSelected"
                                        />
                                    </div>
                                </template>

                                <template v-slot:cell(panel)="data">
                                    <div class="text-center">
                                        <b-dropdown
                                            size="sm"
                                            variant="link"
                                            toggle-class="text-decoration-none py-0"
                                            menu-class="shadow"
                                            no-caret
                                            right
                                        >
                                            <template v-slot:button-content>
                                                <b-icon-three-dots-vertical/>
                                            </template>
                                                
                                            <b-dropdown-header>{{ data.item.name }}</b-dropdown-header>
                                            <b-dropdown-item @click="downloadFile(data)" v-if="data.item.is_dir == 0">Скачать</b-dropdown-item>
                                            <b-dropdown-item v-if="user.id == selectedUser" @click="openRename(data)">Переименовать</b-dropdown-item>
                                        </b-dropdown>
                                    </div>
                                </template>

                            </b-table> 
                            <div v-if="files.length > 0">Выбрано: <strong>{{ selected.length }}</strong></div>
                            <div v-else>
                                <div class="text-center text-muted my-5">Файлов нет</div>
                            </div>
                        </b-overlay>
                    </div>
                </div>
            </b-card-text>
        </b-card>

        <rename-file :file="selectedFile" :open.sync="renameModal" :files.sync="files" />

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
                    { key: 'selected', label: '', tdClass: "align-middle" },
                    { key: 'name', label: 'Имя', tdClass: "align-middle" },
                    { key: 'ext', label: 'Тип', tdClass: "align-middle" },
                    { key: 'size', label: 'Размер', tdClass: "align-middle" },
                    { key: 'time', label: 'Дата', tdClass: "align-middle" },
                    { key: 'panel', label: '', tdClass: "align-middle" },
                ],
                selected: [], // Выбранные строки таблицы

                paths: [], // Массив пути до подкаталога
                cd: 0, // Выбранный каталог

                mkdirWait: false, // Идентификатор ожидания создания папки

                filesUploadList: [], // Список файлов для загрузки
                filesUploaded: [], // Список загуженных файлов
                fileCurrent: "", // Наименование файла текущего в загрузке
                fileProgress: 0, // Процент загрузки файла
                progress: 0, // Общий процент загрузки файлов
                doneUploadFlag: false, // Завершение загрузки

                renameModal: false, // Открытие диалогового окна переименовки файла
                selectedFile: {}, // Выбранный файл

                download: false, // Индикация загрузки файла
                downloadProgress: 0, // Процент скачивания файла
                
            }
        },

        beforeMount() {

            if (!this.login)
                this.$router.push('/404');

        },

        async mounted() {

            // window.onpopstate = event => {
            //     console.log(event);
            //     router.go(-1);
            // }

            await this.getUsersList();

            let user = this.user.id ?? 0,
                folder = this.$route.query.path ?? 0;

            // Проверка идентификатор пользователя в ссылке
            user = this.$route.query.user ?? user;

            await this.getUserFiles(user, folder);

        },

        methods: {

            /**
             * Получение списка пользователей, доступным данный модуль
             */
            async getUsersList() {

                await axios.get('/api/disk/getUsersList').then(({data}) => {
                    this.users = data.users;
                }).catch(error => {
                    this.$eventBus.$emit('error-catch', error.response);
                });

            },

            /**
             * Получение списка файлов пользователя
             */
            async getUserFiles(id = 0, folder = 0) {

                if (!id)
                    return false;

                let query = {};
                query.user = id;

                if (folder)
                    query.path = folder;

                if (query.user != this.$route.query.user || query.path != this.$route.query.path)
                    this.$router.push({ query: query });

                this.selectedUser = id;
                this.loadingUser = true;
                this.isBusy = true;
                this.cd = folder;

                await axios.post('/api/disk/getUserFiles', {id, folder}).then(({data}) => {

                    this.files = [];
                    this.paths = data.paths;

                    data.dirs.forEach(dir => {                  
                        this.files.push(dir);
                    });

                    data.files.forEach(file => {
                        this.files.push(file);
                    });

                }).catch(error => {
                    this.$eventBus.$emit('error-catch', error.response);
                }).then(() => {
                    this.loadingUser = false;
                    this.isBusy = false;
                });

            },

            /**
             * Метод открытия подкаталога
             */
            openFolder(folder = 0) {
                this.getUserFiles(this.selectedUser, folder);
            },

            /**
             * Метод вывода иконки файла
             */
            getIconName(ext = "") {

                let icon = "file-earmark";

                if (ext == 'Папка')
                    return "folder";

                ext = String(ext).toUpperCase();

                let exts = {
                    image: ['JPG','JPEG'],
                    film: ['MOV','AVI','MP4','WEBM']
                };

                for (var key in exts) {

                    exts[key].forEach(row => {
                        if (row == ext)
                            icon = key;                        
                    });
                    
                }

                return icon;

            },

            /**
             * Метод обработки кнопки выбора всех строк
             */
            selectedRows() {
                if (this.selected.length == 0)
                    this.selectAllRows();
                else
                    this.clearSelected();
            },
            /** Выбор одной строки с файллом */
            onRowSelected(items) {
                this.selected = items;
            },
            /** Выбор всех строк с файлами */
            selectAllRows() {
                this.$refs.filetable.selectAllRows();
            },
            /** Снятие выбора всех строк с файлами */
            clearSelected() {
                this.$refs.filetable.clearSelected();
            },

            /**
             * Метод открытия формы выбора файлов
             */
            openFileInput() {
                document.getElementById('input-upload-files').click();
            },

            /**
             * Обнуление сектора прогресса загрузки файлов
             */
            doneUpload() {
                this.doneUploadFlag = true;
                this.filesUploadList = [];
                this.filesUploaded = [];
                this.progress = 0;
            },

            async startUploadFiles() {

                this.doneUpload();

                let files = Array.from(event.target.files);
                this.filesUploadList = files.slice();

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

                }).then(({data}) => {

                    this.fileProgress = 0;
                    this.fileCurrent = '';
                    this.filesUploaded.push(file);

                    if (this.filesUploaded.length == this.filesUploadList.length) {
                        document.getElementById('input-upload-files').value = '';
                        this.openFolder(data.file.in_dir);
                        this.doneUploadFlag = false;
                    }

                }).catch(error => {
                    this.$eventBus.$emit('error-catch', error.response);
                });

            },

            async mkdir() {

                this.mkdirWait = true;

                let form = {
                    cd: this.cd,
                    user: this.user.id,
                };

                await axios.post('/api/disk/mkdir', form).then(({data}) => {

                    // Добавление новой папки в список файлов
                    this.files.push(data.file);

                    // Сортировка списка файлов по имени
                    this.files.sort(function(a, b) {

                        let nameA = a.name.toLowerCase(),
                            nameB = b.name.toLowerCase();
                        
                        if (nameA < nameB)
                            return -1
                        if (nameA > nameB)
                            return 1
                        return 0

                    });

                    this.files.sort(function(a, b) {

                        if (a.is_dir < b.is_dir)
                            return 1
                        if (a.is_dir > b.is_dir)
                            return -1
                        return 0

                    });

                }).catch(error => {
                    this.$eventBus.$emit('error-catch', error.response);
                }).then(() => {
                    this.mkdirWait = false;
                });

            },

            openRename(data) {

                this.selectedFile = data.item;
                this.selectedFile.index = data.index;
                this.selectedFile.cd = this.cd;

                this.renameModal = true;

            },

            downloadFile(data) {

                this.mkdirWait = true;
                this.download = true;
                this.downloadProgress = 0;

                axios.post('/api/disk/download', {
                    id: data.item.id
                }, {
                    responseType: 'blob',
                    onDownloadProgress: progressEvent => {
                        this.downloadProgress = (progressEvent.loaded / progressEvent.total) * 100;
                        console.log(progressEvent.loaded, progressEvent.total, this.downloadProgress);
                    },
                }).then(response => {

                    const url = window.URL.createObjectURL(new Blob([response.data]));
                    const link = document.createElement('a');

                    link.href = url;
                    link.setAttribute('download', data.item.name + '.' + data.item.ext);
                    document.body.appendChild(link);
                    link.click();

                }).catch(error => {
                    this.$eventBus.$emit('error-catch', error.response);
                }).then(() => {
                    this.mkdirWait = this.download = false;
                });

            },

        },

    }
</script>