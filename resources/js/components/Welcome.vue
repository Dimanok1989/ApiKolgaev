<style>
    .welcome.full-height {
        height: 100vh;
    }

    .welcome.flex-center {
        align-items: center;
        display: flex;
        justify-content: center;
    }

    .welcome.position-ref {
        position: relative;
    }

    .welcome .content {
        text-align: center;
    }

    .welcome .title {
        font-size: 84px;
    }

    .welcome .m-b-md {
        margin-bottom: 30px;
    }
</style>

<template>
    <div class="flex-center position-ref full-height welcome">
        <div class="content">
            <div class="title m-b-md">
                kolgaev.ru
            </div>
        </div>
    </div>
</template>

<script>
    export default {

        props: {
            login: {
                default: false
            },
        },

        data() {
            return {
                
            }
        },

        beforeMount() {


            let head = document.querySelector('head'),
                link = document.createElement("link");

            link.setAttribute('href', 'https://fonts.googleapis.com/css?family=Nunito:200,600');
            link.setAttribute('rel', 'stylesheet');

            // head.appendChild(link);

        },

        mounted() {

            console.log(this.login);


        },

        methods: {

        },

    }
</script>