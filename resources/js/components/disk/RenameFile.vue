<template>
    <div>
        <b-modal id="bv-modal-rename-file"
            v-model="open"
            title="Переименовать"
            no-fade
            hide-footer
            no-close-on-backdrop
            no-close-on-esc
            :hide-header-close="loading"
            @close="closeModal"
            @show="resetModal"
        >
            <b-overlay :show="loading" rounded="sm" spinner-type="grow" variant="transparent" class="py-1">
        
                 <b-form ref="form" @submit.stop.prevent="rename"> 

                    <div role="group">
                        <label for="namefile" :class="error ? 'text-danger font-weight-bold' : 'text-dark'">{{ this.text }}</label>
                        <b-form-input
                            id="namefile"
                            v-model="name"
                            placeholder="Введите имя файла..."
                            :state="error"
                        ></b-form-input>
                    </div>

                    <div class="text-center mt-3">
                        <b-button variant="outline-primary" type="submit" :disabled="loading">Переименовать</b-button>
                    </div>

                </b-form>

            </b-overlay> 

        </b-modal>
    </div>
</template>

<script>
export default {

    props: [
        'file',
        'open',
        'files'
    ],

    data() {
        return {
            loading: false,
            name: "",
            text: "Введите новое имя",
            error: null,
        }
    },

    mounted() {

        // console.log(this.$eventBus)

    },

    methods: {

        rename() {

            this.loading = true;

            let data = {
                id: this.file.id,
                name: this.name,
            };

            axios.post('/api/disk/rename', data).then(({data}) => {

                this.error = null;
                this.text = "Введите новое имя";

                if (data.done == "error") {
                    this.error = false;
                    this.text = data.message;
                    return false;
                }

                this.files[this.file.index].name = this.name;
                this.$emit('update:files', this.files);
                this.$emit('update:open', false);

            }).catch(error => {
                this.$eventBus.$emit('error-catch', error.response);
            }).then(() => {
                this.loading = false;
            });

        },

        resetModal() {
            this.name = this.file.name;
            this.text = "Введите новое имя";
            this.error = null;
            this.loading = false;
        },

        closeModal(bvModalEvt) {

            bvModalEvt.preventDefault();
            this.$emit('update:open', false);

        },

    },
    
}
</script>