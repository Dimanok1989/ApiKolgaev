<template>
    <div>
        <input type="file" class="d-none" id="input-upload-files" name="files" multiple="true" @change="startUploadFiles" />

        <b-modal
            id="bv-modal-upload-files"
            v-model="openUpload"
            :title="'Загрузка ' + Math.round(progress) + '%'"
            no-fade
            hide-footer
            no-close-on-backdrop
            no-close-on-esc
            :hide-header-close="!doneUploadFlag"
            @close="closeModal"
        >

            <div class="d-flex justify-content-start align-items-center">
                <span>Файлов для загрузки:</span>
                <strong class="ml-1">{{ filesUploadList.length }}/{{ filesUploaded.length }}</strong>
                <b-icon-check-circle-fill variant="success" class="ml-2" v-if="doneUploadFlag"/>
            </div>
            <b-progress :value="progress" class="mb-2" striped :animated="!doneUploadFlag"></b-progress>

            <hr>

            <div v-for="(file, index) in filesUploadList" :key="file.lastModified">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-nowrap text-truncate">
                        <strong>{{ (index + 1) }}</strong>
                        <span>{{ file.name }}</span>
                    </div>
                    <div>
                        <b-icon-capslock-fill animation="fade" v-if="file.status == 1"/>
                        <b-icon-circle-fill variant="success" animation="throb" v-else-if="file.status == 2"/>
                        <b-icon-check-circle-fill variant="success" v-else-if="file.status == 3"/>
                        <b-icon-x-circle-fill variant="danger" v-else-if="file.status == 4" v-b-tooltip.hover :title="file.error"/>
                    </div>
                </div>
                <div style="height: 3px;">
                    <b-progress :value="file.progress" v-if="index == fileCurrent" class="h-100"></b-progress>
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
        }
    },

    methods: {

        async startUploadFiles() {

            this.filesUploaded = []; // Список загуженных файлов
            this.fileCurrent = -1; // Идентификатор файла текущей загрузки
            this.fileProgress = 0; // Процент загрузки файла
            this.progress = 0; // Общий процент загрузки файлов
            this.doneUploadFlag = false; // Завершение загрузки

            let files = Array.from(event.target.files);
            this.filesUploadList = files.slice();

            this.$emit('update:openUpload', true);

            this.filesUploadList.forEach((file,index) => {
                this.filesUploadList[index].progress = 0;
                this.filesUploadList[index].status = 0;
                this.filesUploadList[index].error = "Неизвестная ошибка";
            });

            for (let file in files)
                await this.uploadFile(files[file], file);

        },

        async uploadFile(file, index) {

            let form = new FormData();
            form.append('files', file);
            form.append('user', this.user.id);
            form.append('cd', this.cd);

            this.fileCurrent = index; // Идентификатор файла загрузки
            this.fileProgress = 0; // Обнуление процента загрузки файла
            this.filesUploadList[index].status = 1; // Статус загрузки файла
            this.doneUploadFlag = false; // Идентификатор завершения загрузки всех файлов

            await axios.post('/api/disk/uploadFile', form, {

                // Прогресс загрузки файла
                onUploadProgress: (itemUpload) => {

                    this.fileProgress = (itemUpload.loaded / itemUpload.total) * 100;
                    this.filesUploadList[index].progress = this.fileProgress;

                    this.progress = ((this.filesUploaded.length * 100) + this.fileProgress) / this.filesUploadList.length;

                    if (this.filesUploadList[index].progress >= 100)
                        this.filesUploadList[index].status = 2;

                }

            }).then(({data}) => {

                this.filesUploadList[index].status = 3;
                this.$eventBus.$emit('sort-files', data.file);

            }).catch(error => {

                this.filesUploadList[index].status = 4;
                this.filesUploadList[index].error = error.response.data.message ? error.response.data.message : this.filesUploadList[index].error;

            });

            this.fileCurrent = -1; // Обнуление идентификтора текущей загрузки файла
            this.filesUploaded.push(file);

            // Завершение загрузки всех файлов
            if (this.filesUploaded.length == this.filesUploadList.length) {
                document.getElementById('input-upload-files').value = '';
                this.doneUploadFlag = true;
            }

        },

        closeModal(bvModalEvt) {

            bvModalEvt.preventDefault();
            this.$emit('update:openUpload', false);

        },

    },
    
}
</script>