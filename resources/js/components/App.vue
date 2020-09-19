<style>
    .bg-my-header {
        background: #1d2332;
        color: #5e80e4;
    }
</style>

<template>
    <div>

        <div class="header bg-my-header" v-if="loading">

            <div class="container">
                
                <b-navbar toggleable="lg" type="dark">

                    <Menu v-if="login" :menu="menu" />

                    <b-navbar-brand href="/" class="header-main-link">
                        <img src="/favicon.ico" class="d-inline-block rounded align-top for-hover" width="30" alt="Kolgaev.ru">
                        <span class="pl-1 d-none">Kolgaev.ru</span>
                    </b-navbar-brand>

                    <b-navbar-nav class="ml-auto" right>

                        <b-nav-item-dropdown toggle-class="text-light for-hover p-0 m-0" no-caret right toggle-tag="span" menu-class="shadow">
                            <template v-slot:button-content>
                                <b-avatar :src="user.avatar" v-if="login && user.avatar"></b-avatar>
                                <b-avatar :text="String(user.name)[0]+(user.surname ? String(user.surname)[0] : '')" v-else-if="login"></b-avatar>
                                <fa-icon :icon="['fas','user']" v-else />
                            </template>
                            <user-registration :openReg.sync="openReg" :login.sync="login" :userMain.sync="user" v-if="!login" />
                            <user-login :openLogin.sync="openLogin" :login.sync="login" :userMain.sync="user" v-if="!login" />

                            <template v-for="row in menu">
                                <b-dropdown-item :key="row.name" :href="row.name" v-if="row.name == 'disk'">{{ row.title }}</b-dropdown-item>
                            </template>

                            <b-dropdown-item href="#" @click="logout" v-if="login">Выход</b-dropdown-item>
                        </b-nav-item-dropdown>

                    </b-navbar-nav>

                </b-navbar>

            </div>

        </div>

        <router-view :login="login" :user="user" :menu="menu" v-if="loading" />

        <div class="global-loading" v-if="!loading">
            <!-- <b-spinner variant="dark" type="grow"></b-spinner> -->
            <img src="/css/main-loading.gif" width="48" height="48" />
        </div>

    </div>
</template>

<script>
    export default {

        data() {
            return {

                display404: false,

                loading: false, // Идентификатор глобальной загрузки певоначальных данных

                user: {}, // Данные пользователя
                login: false, // Идентификатор авторизации
                token: false, // Токен пользователя
                menu: [], // Пункты меню

                openLogin: false, // Открытие окна авторизации
                openReg: false, // Открытие окна регистрации                

            }

        },

        created() {
            this.$eventBus.$on('error-catch', this.errorCatch);
            this.$eventBus.$on('open-auth', this.openModalAuth);
            this.$eventBus.$on('open-reg', this.openModalReg);
            this.$eventBus.$on('get-user-menu', this.getUserMenu);
            this.$eventBus.$on('logout', this.logout);
        },

        beforeDestroy(){
            this.$eventBus.$off('error-catch');
            this.$eventBus.$off('open-auth');
            this.$eventBus.$off('open-reg');
        },

        async beforeMount() {

            let token = localStorage.getItem('token');
            
            if (token) {
                
                this.token = token ?? false;
                window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;

                await axios.post('/api/auth/user').then(({data}) => {
                    this.checked(data);
                }).catch(error => {
                    this.checked();
                });

                await this.getUserMenu();

            }
            else {
                this.checked();
            }

        },

        mounted() {

            let el = document.getElementById('main-loading');
            el.parentNode.removeChild(el);

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

            /** Получение пунтов меню пользователя */
            async getUserMenu() {
                await axios.post('/api/auth/getUserMenu').then(({data}) => {
                    this.menu = data.menu;
                });
            },

            logout() {

                axios.post('/api/auth/logout').then(({data}) => {

                    localStorage.removeItem('token');
                    localStorage.removeItem('user');

                    this.login = false;
                    window.location = '/';

                }).catch(error => {
                    console.log(error);
                });

            },

            /**
             * Вывод глобальной ошибки
             */
            errorCatch(err) {
                console.log(err);
            },

            openModalAuth() {
                this.openLogin = true;
            },
            openModalReg() {
                this.openReg = true;
            },

        },

    }
</script>