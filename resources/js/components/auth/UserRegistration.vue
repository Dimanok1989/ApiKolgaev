<template>
    <div>
        <b-dropdown-item @click="openModal">Регистрация</b-dropdown-item>
        <!-- <b-nav-item href="#" @click="show = !show">Регистрация</b-nav-item> -->

        <b-modal id="bv-modal-user-login"
            v-model="openReg"
            title="Регистрация"
            no-fade
            hide-footer
            no-close-on-backdrop
            no-close-on-esc
            :hide-header-close="loading"
            @show="open"
            @close="closeModal"
        >
            <b-overlay :show="loading" rounded="sm" spinner-type="grow" variant="transparent" class="py-1">
        
                <b-form ref="form" @submit.stop.prevent="registration">

                    <b-alert variant="danger" :show="error ? true : false">{{ error }}</b-alert>

                    <div role="group" class="mb-3 px-1">
                        <label for="input-email" class="mb-0">Адрес электронной почты</label>
                        <b-form-input
                            id="input-email"
                            v-model="data.email"
                            :state="check.email"
                            aria-describedby="input-email-feedback"
                            placeholder="Введите адрес*"
                        ></b-form-input>
                        <b-form-invalid-feedback id="input-email-feedback">{{ errs.email }}</b-form-invalid-feedback>
                    </div>

                    <div role="group" class="mb-2 px-1">
                        <label for="input-password" class="mb-0">Пароль</label>
                        <b-form-input
                            id="input-password"
                            v-model="data.password"
                            :state="check.password"
                            aria-describedby="input-password-feedback"
                            placeholder="Введите пароль*"
                            type="password"
                        ></b-form-input>
                        <b-form-invalid-feedback id="input-password-feedback">{{ errs.password }}</b-form-invalid-feedback>
                    </div>

                    <div role="group" class="mb-3 px-1">
                        <b-form-input
                            id="input-password_confirmation"
                            v-model="data.password_confirmation"
                            :state="check.password_confirmation"
                            aria-describedby="input-password_confirmation-feedback"
                            placeholder="Подтвердите пароль*"
                            type="password"
                        ></b-form-input>
                        <b-form-invalid-feedback id="input-password_confirmation-feedback">{{ errs.password_confirmation }}</b-form-invalid-feedback>
                    </div>

                    <label for="input-name" class="mb-0 px-1">Представьтесь</label>

                    <b-row class="px-3">
                        <b-col class="px-0" sm>
                            <div role="group" class="mb-2 px-1">
                                <b-form-input
                                    id="input-name"
                                    v-model="data.name"
                                    :state="check.name"
                                    aria-describedby="input-name-feedback"
                                    placeholder="Имя*"
                                ></b-form-input>
                                <b-form-invalid-feedback id="input-name-feedback">{{ errs.name }}</b-form-invalid-feedback>
                            </div>
                        </b-col>
                        <b-col class="px-0" sm>
                            <div role="group" class="mb-2 px-1">
                                <b-form-input
                                    id="input-surname"
                                    v-model="data.surname"
                                    placeholder="Фамилия"
                                ></b-form-input>
                            </div>
                        </b-col>
                    </b-row>

                    <div class="text-center mt-2">
                        <b-button variant="outline-primary" type="submit" :disabled="loading">Зарегистрироваться</b-button>
                    </div>

                </b-form>

            </b-overlay> 

        </b-modal>
    </div>
</template>

<script>
    export default {

        props: [
            'login', // Идентификатор авторизации пользователя
            'openReg', // Идентификтаор открытия окна
        ],

        data() {
            return {
                loading: false,
                user: {},
                data: {
                    email: "",
                    password: "",
                    password_confirmation: "",
                    surname: "",
                    name: "",
                },
                check: {
                    email: null,
                    password: null,
                    password_confirmation: null,
                    surname: null,
                    name: null,
                },
                errs: {
                    email: "",
                    password: "",
                    password_confirmation: "",
                    surname: "",
                    name: "",
                },
                error: false,
            }
        },

        mounted() {
  
        },

        computed: {
            nameState() {
                return this.name.length > 2 ? true : false
            }
        },

        methods: {

            openModal() {
                this.$emit('update:openReg', true);
            },

            closeModal(bvModalEvt) {
                bvModalEvt.preventDefault();
                this.$emit('update:openReg', false);
            },

            open() {

                this.loading = false;
                this.error = false;

                for (let i in this.data)
                    this.data[i] = "";

                this.resetErrors();

            },

            errors(errors = {}) {

                for (let i in errors) {
                    this.errs[i] = errors[i].join(" ");
                    this.check[i] = false;
                }

            },

            resetErrors() {

                for (let i in this.errs)
                    this.errs[i] = "";

                for (let i in this.check)
                    this.check[i] = null;

            },

            registration() {

                this.loading = true;

                axios.post('/api/auth/registration', this.data).then(({data}) => {

                    this.loading = false;
                    this.resetErrors();
                    this.error = false;

                    if (data.done == "error")
                        return this.error = data.message;

                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));
                    window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + data.token;

                    this.show = false;
                    this.$emit('update:login', true);

                }).catch(error => {

                    this.loading = false;
                    this.resetErrors();

                    if (error.response)
                        this.errors(error.response.data.errors);

                });

            },

        },

    }
</script>