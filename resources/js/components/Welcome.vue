<style>
.main-header {
    width: 100%;
    height: 300px;
    background-color: #004587;
}
.main-header-bg-grd {
    background: url(/css/header/gradient.png) repeat-x;
    background-size: auto 300px;
}

.welcome-title {
    font-size: 58px;
}
</style>

<template>
    <div>

        <div class="main-header position-relative" id="main-header">
            <div class="position-absolute w-100 h-100 main-header-bg-grd"></div>
            <div class="position-absolute w-100 h-100 text-center text-light d-flex justify-content-center align-items-center">
                <div>
                    <div class="welcome-title">kolgaev.ru</div>
                    <div v-if="!login">
                        <h4>Добро пожаловать!</h4>
                        <div class="mt-3">
                            <span class="font-weight-bold for-hover cursor-pointer" @click="openAuth">Войдите</span>
                            <span>или</span>
                            <span class="font-weight-bold for-hover cursor-pointer" @click="openReg">Зарегистрируйтесь</span>
                        </div>
                    </div>
                    <div v-else>
                        <h4>Добро пожаловать, {{ user.name }}!</h4>
                        <h5>Это наш сайт!</h5>
                    </div>
                </div>
            </div>
        </div>

    </div>
    
</template>

<script>
    export default {

        props: [
            'login',
            'user',
            'menu', // Пункты меню
        ],

        data() {
            return {
                
            }
        },

        beforeMount() {

            // let head = document.querySelector('head'),
            //     link = document.createElement("link");
            // link.setAttribute('href', 'https://fonts.googleapis.com/css?family=Nunito:200,600');
            // link.setAttribute('rel', 'stylesheet');
            // head.appendChild(link);

        },

        mounted() {

            let src = '/css/header/header.jpg',
                img = document.createElement("img");
            img.onload = function() {
                let mainHeader = document.getElementById('main-header');
                mainHeader.style.background = "url(" + src + ")";
                mainHeader.style.backgroundPosition = "50% 20%";
            }
            img.src = src;

        },

        methods: {

            openAuth() {
                this.$eventBus.$emit('open-auth');
            },
            openReg() {
                this.$eventBus.$emit('open-reg');
            },

        },

    }
</script>