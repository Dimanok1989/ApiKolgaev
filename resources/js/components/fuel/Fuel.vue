<template>
    <div class="text-center">

        <h4 class="my-4">Расход топлива</h4>

        <div class="text-center" v-if="loading">
            <b-spinner type="grow" label="Spinning"></b-spinner>
        </div>

        <div class="mx-auto mb-4 text-left" v-if="cars.length" style="max-width: 500px;">

            <div class="card h-100 cursor-default" v-for="row in cars" :key="row.id">
                <!-- <img src="..." class="card-img-top" alt="..."> -->
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <router-link :to="{ name: 'fuelRows', params: { id: row.id }}">
                                <span>{{ row.brand }}</span>
                                <span v-if="row.model">{{ row.model }}</span>
                                <span v-if="row.modification">{{ row.modification }}</span>
                            </router-link>
                        </h5>
                        <small class="text-muted" v-if="row.year">{{ row.year }}</small>
                    </div>
                    <!-- <b-button-group size="sm" class="mt-2">
                        <b-button variant="success">
                            <span>Добавить заправку</span>
                        </b-button>
                        <b-button variant="info" disabled>
                            <span>Изменить</span>
                        </b-button>
                    </b-button-group> -->
                </div>
            </div>

            <!-- <b-button variant="success" @click="$bvModal.show('add-car');" size="sm" class="mt-2">
                <fa-icon :icon="['fas','plus']" />
                <span>Добавить машину</span>
            </b-button> -->

            <!-- <AddCar /> -->

        </div>

        <div v-if="fuels.length">

            <h5 class="mb-3" v-if="fuels.length">Последние заправки</h5>

            <div v-for="row in fuels" :key="row.id" class="fuels card mx-auto my-2">
                <div class="card-body text-left py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ row.brand }} {{ row.model }}</span>
                        <small class="text-muted">{{ row.date }} - <b>{{ row.mileage }}</b></small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span>Заправка {{ row.type }}</span>
                            <i>"{{ row.gas_station }}"</i>
                        </div>
                        <div>{{ row.liters }} л</div>
                    </div>
                </div>
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
                loading: true,
                open: false,
                cars: [],
                fuels: [],
            }
        },

        mounted() {

            // Получение данных на главную страницу
            this.getMainData();

        },

        methods: {

            getMainData() {

                axios.post('/api/fuel/getMainData').then(({data}) => {

                    this.cars = data.cars;
                    this.fuels = data.fuels;

                }).catch(error => {
                    
                }).then(() => {
                    this.loading = false;
                });
            
            },

        },

    }

</script>