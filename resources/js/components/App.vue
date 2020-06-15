<style>
    .bg-my-header {
        background: #242835;
        color: #5e80e4;
    }
</style>

<template>
    <div>
        <div class="header bg-my-header" v-if="loading">

            <div class="container">
                
                <b-navbar toggleable="lg" type="dark">

                    <b-navbar-brand href="/" class="header-main-link">
                        <img src="/favicon.ico" class="d-inline-block rounded align-top for-hover" width="30" alt="Kolgaev.ru">
                        <span class="pl-1 d-none">Kolgaev.ru</span>
                    </b-navbar-brand>

                    <b-navbar-nav class="ml-auto" right v-if="!login">
                        <user-registration :login.sync="login" />
                        <user-login :login.sync="login" />
                    </b-navbar-nav>

                    <b-navbar-nav class="ml-auto" right v-if="login">
                        <b-nav-item href="#" @click="logout">Выход</b-nav-item>
                    </b-navbar-nav>

                </b-navbar>

            </div>

        </div>

        <div class="container my-4 p-1" v-if="loading">
            <router-view :login="login" :user="user" />
        </div>

        <div class="global-loading" v-if="!loading">
            <b-spinner variant="dark" type="grow"></b-spinner>
        </div>

    </div>
</template>

<script>
    export default {

        data() {
            return {
                display404: false,
                loading: false, // Идентификатор глобальной загрузки певоначальных данных
                token: false, // Токен пользователя
                user: {}, // Данные пользователя
                login: false, // Идентификатор авторизации
            }
        },

        created() {
            this.$eventBus.$on('error-catch', this.errorCatch);
        },

        beforeDestroy(){
            this.$eventBus.$off('error-catch');
        },

        beforeMount() {

            let token = localStorage.getItem('token');
            
            if (token) {
                
                this.token = token ?? false;
                window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;

                axios.post('/api/auth/user').then(({data}) => {
                    this.checked(data);
                }).catch(error => {
                    this.checked();
                });

            }
            else {
                this.checked();
            }

        },

        mounted() {

        },

        methods: {

            /**
             * Завершение проверки данных пользователя
             */
            checked(data = false) {

                if (data) {

                    this.login = true;
                    
                    this.user = data;
                    localStorage.setItem('user', JSON.stringify(data));

                }
                else {
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');
                }

                this.loading = true;

            },

            logout() {

                axios.post('/api/auth/logout').then(({data}) => {

                    console.log(data);
                    localStorage.removeItem('token');
                    localStorage.removeItem('user');

                    this.login = false;
                    window.location = '/';

                }).catch(error => {
                    console.log(error);
                });

            },

            errorCatch(err) {
                console.log(err);
            },

        },

    }
</script>