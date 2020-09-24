<style>
    .fuels {
        width: 98%;
        max-width: 500px;
    }
    .cars-row {
        width: 95%;
        max-width: 1500px;
    }
</style>

<template>
    <div class="text-center">

        <h4 class="my-4">Расход топлива</h4>

        <div class="row row-cols-1 row-cols-md-4 cars-row mx-auto mb-4 justify-content-center">

            <div class="col mb-4" v-for="row in cars" :key="row.id">
                <div class="card h-100 cursor-default">
                    <!-- <img src="..." class="card-img-top" alt="..."> -->
                    <div class="card-body">
                        <h5 class="card-title">
                            <router-link :to="{ name: 'fuelRows', params: { id: row.id }}">{{ row.brand }} {{ row.model }}</router-link>
                        </h5>                        
                        <p class="card-text mb-0" v-if="row.modification">{{ row.modification }}</p>
                        <p class="card-text mb-0" v-if="row.year">
                            <small class="text-muted">{{ row.year }}</small>
                        </p>
                    </div>
                </div>
            </div>

        </div>

        <h5 class="my-3" v-if="fuels.length">Последние заправки</h5>

        <div v-for="row in fuels" :key="row.id" class="fuels card mx-auto mt-2">
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
                    
                });
            
            },

        },

    }

</script>