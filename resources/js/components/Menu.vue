<style>

    .main-menu {
        position: fixed;
        top: 0;
        left: -300px;
        bottom: 0;
        width: 250px;
        transition: .3s;
        background: #f8f9fa;
        z-index: 100;
        color: #000000;
    }

    .menu-title {
        margin-left: 1rem;
        font-weight: 700;
        font-size: 110%;
        cursor: default;
    }

    .main-menu-point {
        display: block;
        width: 100%;
        padding: 0.5rem;
        cursor: pointer;
        font-weight: 700;
    }
    .main-menu-point:hover {
        background: #eeeeee;
    }
    .main-menu-point > div {
        display: inline-block;
        text-align: left;
        width: 1.5em;
    }

</style>

<template>
    <div>

        <b-navbar-nav class="mr-3">
            <fa-icon :icon="['fas', 'bars']" class="fa-lg text-light for-hover cursor-pointer" @click="openMenu" />
        </b-navbar-nav>

        <div class="main-menu border-right shadow pb-2" :style="(this.open ? `left: 0px;` : `left: -300px;`)">

            <div class="w-100 border-bottom py-3 px-2">
                <fa-icon :icon="['fas', 'arrow-left']" class="fa-lg for-hover cursor-pointer" @click="openMenu()" />
                <span class="menu-title">Kolgaev.ru</span>
            </div>

            <div class="main-menu-point border-bottom" @click="openLink()">
                <div>
                    <fa-icon :icon="['fas', 'home']" />
                </div>
                <span>Главная</span>
            </div>

            <a v-for="row in menu" :key="row.name" class="main-menu-point border-bottom" @click="openLink(row.name)">
                <div v-if="row.icon">
                    <fa-icon :icon="['fas', row.icon]" />
                </div>
                <span>{{ row.title }}</span>
            </a>

            <div class="main-menu-point border-bottom" @click="logout">
                <div>
                    <fa-icon :icon="['fas', 'sign-out-alt']" />
                </div>
                <span>Выход</span>
            </div>
            

        </div>

    </div>
</template>

<script>

    export default {

        props: {
            menu: {
                default: [],
            },
        },

        data() {
            return {
                open: false,
            }
        },

        mounted() {

        },

        methods: {

            openMenu() {
                this.open = !this.open;
            },

            logout() {
                this.$eventBus.$emit('logout');
            },

            openLink(link = "") {
                this.$router.push(`/${link}`);
                this.open = false;
            },

        },

    }

</script>