<style>
    .fuels {
        width: 98%;
        max-width: 500px;
    }
    .cars-row {
        width: 95%;
        max-width: 1500px;
    }
    .button-add {
        position: fixed;
        bottom: 10px;
        right: 10px;
    }
</style>

<template>
    <div class="text-center">

        <h4 class="mt-4 mb-0">Все заправки</h4>
        <div class="mb-3 text-muted">{{ car.brand ? car.brand + ' ' + car.model : '' }}</div>

        <div v-for="row in fuels" :key="row.id" class="fuels card mx-auto my-2">
            <div class="card-body text-left py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <span>{{ row.date }}</span>
                    <small class="text-muted"><b>{{ row.mileage }}</b></small>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span>Заправка {{ row.type }}</span>
                        <i>"{{ row.gas_station }}"</i>
                    </div>
                    <div><small>{{ row.liters }} л × {{ row.price }} руб</small></div>
                </div>
                <div class="d-flex justify-content-end align-items-center">
                    <small>{{ (row.liters*row.price).toFixed(2) }} руб</small>
                </div>
            </div>
        </div>

        <div class="text-center my-3" v-if="loading || progress">
            <div class="spinner-grow spinner-grow-sm" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>

        <b-button pill variant="success" class="button-add" v-if="mycar" @click="$bvModal.show('add-fuel')">
            <fa-icon :icon="['fas','plus']" />
        </b-button>

        <b-modal
            id="add-fuel"
            ref="modal"
            title="Новая заправка"
            @show="resetModal"
            @hidden="resetModal"
        >
            <form ref="form" @submit.stop.prevent="addFuel">
        
            </form>
        </b-modal>

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

                loading: true,
                progress: false,

                car: [],
                
                offset: 0,
                endrows: false,
                fuels: [],

                mycar: false,

            }
        },

        mounted() {

            // Получение данных на главную страницу
            this.getFuelsCar();

            window.onscroll = () => {
                if (document.documentElement.scrollTop + window.innerHeight === document.documentElement.offsetHeight && !this.progress && !this.endrows) {
                    this.getFuelsCar();
                }
            }

        },

        methods: {

            getFuelsCar() {

                this.progress = true;

                let form = {
                    car: this.$route.params.id,
                    offset: this.offset,
                }

                this.mycar = false;

                axios.post('/api/fuel/getFuelsCar', form).then(({data}) => {

                    this.car = data.car;

                    data.fuels.forEach(row => {
                        this.fuels.push(row);
                    });

                    this.offset += data.fuels.length;

                    if (data.fuels.length < data.limit)
                        this.endrows = true;

                    this.mycar = data.user;

                }).catch(error => {

                    
                }).then(() => {
                    this.loading = false;
                    this.progress = false;
                });
            
            },

            addFuel() {

            },

            resetModal() {

            },

        },

    }

</script>