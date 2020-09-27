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

        <b-button pill variant="success" class="button-add" v-if="mycar" @click="$bvModal.show('add-fuel'); countPrice();">
            <fa-icon :icon="['fas','plus']" />
        </b-button>

        <b-modal
            id="add-fuel"
            ref="modal"
            title="Новая заправка"
            @hidden="resetModal"
            @ok="addFuel"
            no-close-on-backdrop
            ok-title="Добавить"
            cancel-title="Отмена"
            :hide-close="true"
            :ok-disabled="addLoading"
            :cancel-disabled="addLoading"
            no-fade
        >
            <form ref="form" @submit.stop.prevent="addFuel">

                <b-overlay variant="white" opacity="0.7" :show="this.addLoading">

                    <b-row class="my-2 align-items-center">
                        <b-col sm="3">
                            <label for="add-date" class="mb-0">Дата</label>
                        </b-col>
                        <b-col sm="9">
                            <b-form-datepicker id="add-date" size="sm" v-model="add.date" placeholder="Дата заправки"></b-form-datepicker>
                        </b-col>
                    </b-row>

                    <b-row class="my-2 align-items-center">
                        <b-col sm="3">
                            <label for="add-mileage" class="mb-0">Пробег</label>
                        </b-col>
                        <b-col sm="9">
                            <b-form-input id="add-mileage" type="number" size="sm" v-model="add.mileage" placeholder="Укажите пробег..."></b-form-input>
                        </b-col>
                    </b-row>

                    <b-row class="my-2 align-items-center">
                        <b-col sm="3">
                            <label for="add-liters" class="mb-0">Литры</label>
                        </b-col>
                        <b-col sm="9">
                            <b-form-input id="add-liters" type="number" step="0.001" min="0" size="sm" v-model="add.liters" placeholder="Количество литров..." @change="countPrice"></b-form-input>
                        </b-col>
                    </b-row>

                    <b-row class="my-2 align-items-center">
                        <b-col sm="3">
                            <label for="add-price" class="mb-0">Цена</label>
                        </b-col>
                        <b-col sm="9">
                            <b-input-group size="sm">
                                <b-form-input id="add-price" type="number" step="0.01" min="0" size="sm" v-model="add.price" placeholder="Цена за литр..." @change="countPrice"></b-form-input>
                                <b-form-input type="text" placeholder="Стоимость" :value="`${add.summ} руб`" disabled></b-form-input>
                            </b-input-group>
                        </b-col>
                    </b-row>

                    <b-row class="my-2 align-items-center">
                        <b-col sm="3">
                            <label for="add-price" class="mb-0">Вид топлива</label>
                        </b-col>
                        <b-col sm="9">
                            <b-form-select v-model="add.type" :options="options" size="sm"></b-form-select>
                        </b-col>
                    </b-row>

                    <b-form-checkbox value="1" unchecked-value="0" v-model="add.full" class="my-2">Полный бак</b-form-checkbox>
                    <b-form-checkbox value="1" unchecked-value="0" v-model="add.lost" class="my-2">Не записал педыдущую заправку</b-form-checkbox>

                    <b-row class="my-2 align-items-center">
                        <b-col sm="3">
                            <label for="add-stantion" class="mb-0">АЗС</label>
                        </b-col>
                        <b-col sm="9">
                            <b-form-input id="add-stantion" type="text" size="sm" v-model="add.stantion" placeholder="Наименование АЗС"></b-form-input>
                        </b-col>
                    </b-row>

                    <div v-if="stantions.length">
                        <small class="text-primary for-hover cursor-pointer mr-3" v-for="row in stantions" :key="row" @click="autoGasStantion(row)">{{ row }}</small>
                    </div>

                </b-overlay>

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
                toDay: "",

                addLoading: false,

                add: {
                    date: "",
                    mileage: "",
                    liters: "",
                    price: "",
                    stantion: "",
                    full: "",
                    lost: "",
                    type: "",
                    summ: 0,
                },

                stantions: [],

                options: [
                    { value: "", text: "Выберите топливо..." },
                    { value: "АИ 80", text: "АИ 80" },
                    { value: "АИ 92", text: "АИ 92" },
                    { value: "АИ 95", text: "АИ 95" },
                    { value: "АИ 95 Premium", text: "АИ 95 Premium" },
                    { value: "АИ 98", text: "АИ 98" },
                    { value: "АИ 100", text: "АИ 100" },
                    { value: "ДТ", text: "ДТ" },
                ],

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
                    this.toDay = data.date;

                    this.stantions = data.statistic.stantions;
                    this.add.type = data.statistic.type;

                }).catch(error => {

                    
                }).then(() => {
                    this.loading = false;
                    this.progress = false;
                });
            
            },

            addFuel(bvModalEvt) {

                bvModalEvt.preventDefault();

                let dataform = this.add;
                dataform.car = this.car.id;

                this.addLoading = true;

                axios.post('/api/fuel/addFuel', dataform).then(({data}) => {

                    this.fuels.unshift(data.refuel);
                    this.$bvModal.hide('add-fuel');

                }).catch(error => {


                    
                }).then(() => {

                    this.addLoading = false;

                });

            },

            resetModal() {

                this.addLoading = false;

                this.add.date = this.toDay;
                this.add.mileage = "";
                this.add.liters = "";
                this.add.price = "";
                this.add.stantion = "";
                this.add.full = "";
                this.add.lost = "";
                this.add.type = "";

            },

            countPrice() {
                this.add.summ = Number(this.add.liters * this.add.price).toFixed(2);
            },

            autoGasStantion(gas) {
                this.add.stantion = gas;
            },

        },

    }

</script>