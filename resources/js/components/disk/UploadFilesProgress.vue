<style>
.progress-no-bg {
    background: none!important;
    z-index: 0;
}
</style>

<template>
    <div>
        <input type="file" class="d-none" id="input-upload-files" name="files" multiple="true" @change="startUploadFiles" />

        <b-modal
            id="bv-modal-upload-files"
            v-model="openUpload"
            no-fade
            scrollable 
            hide-footer
            no-close-on-backdrop
            no-close-on-esc
            @close="closeModal"
            header-bg-variant="secondary"
            header-text-variant="light"
            header-class="position-relative"
        >

            <template v-slot:modal-header="{ close }">
                <div class="position-absolute absolute">
                    <b-progress :value="progress" class="h-100 progress-no-bg" :striped="!doneUploadFlag" :animated="!doneUploadFlag" :variant="doneUploadFlag ? 'success' : 'primary'"></b-progress>
                </div>
                <h5 class="modal-title" style="z-index: 1;">Загрузка {{ Math.round(progress) }}%</h5>
                <button type="button" class="close text-light" @click="close()" v-if="doneUploadFlag" style="z-index: 1;">×</button>
            </template>

            <div class="d-flex justify-content-start align-items-center" v-if="false">
                <span>Файлов для загрузки:</span>
                <strong class="ml-1">{{ filesUploadList.length }}/{{ filesUploaded.length }}</strong>
                <b-icon-check-circle-fill variant="success" class="ml-2" v-if="doneUploadFlag"/>
            </div>
            <b-progress :value="progress" class="mb-2" striped :animated="!doneUploadFlag" :variant="doneUploadFlag ? 'success' : 'primary'" v-if="false"></b-progress>

            <hr v-if="false">

            <div v-for="(file, index) in filesUploadList" :key="index">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-nowrap text-truncate">
                        <strong class="mr-1">{{ (index + 1) }}</strong>
                        <span>{{ file.name }}</span>
                    </div>
                    <div>
                        <b-icon-cloud-upload animation="fade" v-if="file.status == 1"/>
                        <b-icon-circle-fill variant="success" animation="throb" v-else-if="file.status == 2"/>
                        <b-icon-check-circle-fill variant="success" v-else-if="file.status == 3"/>
                        <b-icon-x-circle-fill variant="danger" v-else-if="file.status == 4" v-b-tooltip.hover :title="file.error"/>
                    </div>
                </div>
                <div style="height: 3px;">
                    <b-progress :value="fileProgress" v-if="index == fileCurrent" class="h-100"></b-progress>
                </div>
            </div>

        </b-modal>
    </div>
</template>

<script>
export default {

    props: [
        'openUpload', // Идентификатор открытия окна загрузки
        'user', // ДАнные пользователя
        'cd' // Открытый каталог
    ],

    data() {
        return {
            loading: false,
            filesUploadList: [], // Список файлов для загрузки
            filesUploaded: [], // Список загуженных файлов
            fileCurrent: -1, // Идентификатор файла текущей загрузки
            fileProgress: 0, // Процент загрузки файла
            progress: 0, // Общий процент загрузки файлов
            doneUploadFlag: false, // Завершение загрузки
            chunk: 5242880, // Размер загружаемой части файла
            offset: 0, // Текущая позиция чтения файла
            path: false, // Путь до папки с файлом
        }
    },

    methods: {

        /**
         * Метод запускается сразу, после изменения формы выборва файлов
         */
        async startUploadFiles() {

            this.filesUploaded = []; // Список загуженных файлов
            this.fileCurrent = -1; // Идентификатор файла текущей загрузки
            this.fileProgress = 0; // Процент загрузки файла
            this.progress = 0; // Общий процент выполнения
            this.doneUploadFlag = false; // Обнуление завершенной ранее загрузки

            let files = Array.from(event.target.files);

            // Открытие диалогового окна с процессом загрузки
            this.$emit('update:openUpload', true);

            // Добавление переменных для файлов
            for (let index in files) {

                let file = files[index];

                this.filesUploadList[index] = {
                    name: file.name,
                    lastModified: file.lastModified,
                    lastModifiedDate: file.lastModifiedDate,
                    size: file.size,
                    progress: 0,
                    status: 0,
                    error: "Неизвестная ошибка",
                }
            }

            // Поочередная загрузка каждого файла
            for (let index in files)
                await this.uploadFile(files[index], index);

            // Завершение загрузки всех файлов
            document.getElementById('input-upload-files').value = '';
            this.doneUploadFlag = true;

        },

        async uploadFile(file, index) {

            this.offset = 0;
            this.fileProgress = 0;
            this.filesUploadList[index].status = 2;

            let formdata = {
                name: file.name, // Имя файла
                size: file.size, // Размер файла
                type: file.type, // Тип файла
                user: this.user.id, // Идентификатор пользователя
                cd: this.cd, // Директория загрузки
                index, // Идентификатор файла в списке файлов
                hash: false, // Идентификатор созданного файла
            }

            let response = {};

            while (this.offset < formdata.size) {

                if (this.offset + this.chunk >= formdata.size)
                    formdata.endchunk = true;

                formdata.chunk = await this.getChunkFile(file, index);

                if (this.path)
                    formdata.path = this.path;

                if (formdata.chunk === false)
                    return;

                response = await this.uploadChunk(formdata);
                formdata.hash = response.hash;

                this.offset += this.chunk;

            }

            this.fileCurrent = -1; // Обнуление идентификтора текущей загрузки файла
            this.path = false; // Сброс пути до файла на сервере

        },

        async getChunkFile(file, index) {

            return new Promise((resolve, reject) => {

                var reader = new FileReader();

                // Вывод ошибки чтения файла
                reader.onerror = event => {
                    
                    this.filesUploadList[index].status = 4;
                    this.filesUploadList[index].progress = 100;
                    this.filesUploadList[index].error = `Ошибка чтения файла в Вашем браузере (${event.target.error.name})`;
                    
                    reader.abort();
                    console.error("Failed to read file!\n" + reader.error);

                    resolve(false);

                }

                reader.onloadend = (evt) => {

                    let base64 = String(reader.result),
                        len = base64.indexOf(',');

                    base64 = len > 0 ? base64.substring(len + 1) : base64;

                    resolve(base64);

                };

                var blob = file.slice(this.offset, this.offset + this.chunk);
                reader.readAsDataURL(blob);

            });

        },

        async uploadChunk(formdata) {

            let hash = false,
                index = Number(formdata.index);

            this.fileCurrent = index;
            this.filesUploadList[index].status = 1;

            await axios.post('/api/disk/uploadFile', formdata, {

                // Прогресс загрузки файла
                onUploadProgress: (itemUpload) => {

                    this.fileProgress += ((itemUpload.loaded / itemUpload.total) * 100) / (formdata.size / this.chunk);
                    this.fileProgress = this.fileProgress > 100 ? 100 : this.fileProgress;
                    this.filesUploadList[index].progress = this.fileProgress;

                    this.progress = ((this.filesUploaded.length * 100) + this.fileProgress) / this.filesUploadList.length;

                    this.progress = this.progress > 100 ? 100 : this.progress;

                }

            }).then(({data}) => {

                // Имя файла для склейки очередной части
                hash = data;

                // Путь до файла на сервере, требуется для правильной склейки файла в момент
                // смены даты, в противном случае файл разделится по каталогам дат
                this.path = this.path ? this.path : data.path;

                if (data.file) {
                    
                    this.fileProgress = 100;

                    this.filesUploadList[index].status = 3;
                    this.$eventBus.$emit('sort-files', data.file);

                    this.filesUploaded.push(data.file);

                }

            }).catch(error => {

                if (error.response) {
                    this.filesUploadList[index].status = 4;
                    this.filesUploadList[index].error = error.response.data.message ? error.response.data.message : this.filesUploadList[index].error;
                }

            });

            return hash;

        },

        closeModal(bvModalEvt) {

            bvModalEvt.preventDefault();
            this.$emit('update:openUpload', false);

        },

    },
    
}
</script>