<template>
    <b-modal
            id="add-car"
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
</template>