<template>
    <div>
        <b-dropdown-item href="#" @click="openModal">Вход</b-dropdown-item>
        <!-- <b-nav-item href="#" @click="show = !show">Вход</b-nav-item> -->

        <b-modal id="bv-modal-user-login"
            v-model="openLogin"
            title="Авторизация"
            no-fade
            hide-footer
            no-close-on-backdrop
            no-close-on-esc
            :hide-header-close="loading"
            @show="openAuth"
            @close="closeModal"
        >
            <b-overlay :show="loading" rounded="sm" spinner-type="grow" variant="transparent" class="py-1">
        
                <b-form ref="form" @submit.stop.prevent="auth"> 
                    <div role="group">
                        <label for="auth-email" :class="error ? 'text-danger font-weight-bold' : 'text-dark'">{{ this.text }}</label>
                        <b-form-input
                            id="auth-email"
                            v-model="data.email"
                            placeholder="Введите адрес электроннйо почты..."
                            :state="check.email"
                        ></b-form-input>
                    </div>
                    <b-form-input class="mt-3"
                        id="auth-password"
                        v-model="data.password"
                        type="password"
                        placeholder="Пароль..."
                        :state="check.password"
                    ></b-form-input>
                    <div class="text-center mt-3">
                        <b-button variant="outline-primary" type="submit" :disabled="loading">Войти</b-button>
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
            'userMain', // Данные пользователя
            'openLogin', // Идентификтаор открытия окна
        ],

        data() {
            return {
                loading: false,
                user: {},
                data: {
                    email: '',
                    password: '',
                },
                check: {
                    email: null,
                    password: null,
                },
                error: false,
                text: "",
            }
        },

        mounted() {
  
        },

        methods: {

            openModal() {
                this.$emit('update:openLogin', true);
            },

            closeModal(bvModalEvt) {
                bvModalEvt.preventDefault();
                this.$emit('update:openLogin', false);
            },

            openAuth() {

                this.loading = false;
                this.data.email = '';
                this.data.password = '';

                this.check.email = null;
                this.check.password = null;

                this.error = false;
                this.text = "Адрес почты, логин или телефон";

            },

            auth() {

                this.loading = true;

                axios.post('/api/auth/login', this.data).then(({data}) => {

                    this.loading = false;

                    if (data.done == "error") {
                        this.error = true;
                        this.text = data.message;
                        return;
                    }

                    localStorage.setItem('token', data.token);
                    localStorage.setItem('user', JSON.stringify(data.user));

                    window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + data.token;

                    this.show = false;
                    this.$emit('update:login', true);
                    this.$emit('update:userMain', data.user);

                }).catch(error => console.log(error.response));

            },

        },

    }
</script>