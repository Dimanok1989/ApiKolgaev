<style>
    .main-users { max-width: 500px; }
    .role-item img {
        position: absolute;
        right: 15px;
        top: 50%;
        margin-top: -5px;
    }
</style>

<template>
    <div class="text-center main-users mx-auto">

        <div v-if="!user.id">

            <h5 class="mt-4">Настройка прав группы</h5>

            <ul class="list-group text-left mt-3" v-if="!permissions.length">
                <button type="button" class="list-group-item list-group-item-action position-relative role-item" v-for="item in roles" :key="item.id" @click="getPermissions(item)" :disabled="item.id == permissionLoad">
                    <span>
                        <span>{{ item.name }}</span>
                        <span class="badge badge-primary">{{ item.count }}</span>
                    </span>
                    <img src="/css/ajax-loader-2.gif" v-if="item.id == permissionLoad" />
                </button>
            </ul>

            <div class="d-flex justify-content-center align-items-center mt-2 mb-3" v-if="role.name">
                <fa-icon :icon="['fas','arrow-circle-left']" class="for-hover cursor-pointer mr-2" @click="backGetRole" />
                <strong>{{ role.name }}</strong>
            </div>

            <div v-if="permissions.length">
                <div v-for="item in permissions" :key="item.id" class="text-left mb-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" :id="`permission-${item.id}`" @change="setPermissionRole(item.id)" :data-permission="item.id" :checked="item.access" :disabled="item.id == permissionSet">
                        <label class="custom-control-label font-weight-bold" :for="`permission-${item.id}`">
                            <span>{{ item.slug }}</span>
                            <img src="/css/ajax-loader-2.gif" class="ml-2" v-if="item.id == permissionSet" />
                        </label>
                    </div>
                    <div class="mt-0 px-2"><small>{{ item.name }}</small></div>
                </div>
            </div>

            <img src="/css/ajax-loader-1.gif" class="my-2" v-if="!roles.length" />

            <h5 class="mt-4" v-if="!permissions.length">
                <span>Пользователи</span>
                <span class="badge badge-primary">{{ countUsers }}</span>
            </h5>

            <ul class="list-group text-left mt-3" v-if="!permissions.length">
                <button type="button" class="list-group-item list-group-item-action position-relative role-item" v-for="item in users" :key="item.id" @click="getUserData(item)" :disabled="item.id == userLoad" :id="`user-${item.id}`">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="font-weight-bold">{{ item.name }}</div>
                        <small v-if="item.last">Был {{ item.last }}</small>
                    </div>
                    <div>
                        <span>{{ item.email }}</span>
                        <span class="badge badge-primary">{{ item.role }}</span>
                    </div>
                    <div>
                        <span>{{ item.login }}</span>
                        <span>{{ item.phone }}</span>
                    </div>
                    <img src="/css/ajax-loader-2.gif" v-if="item.id == userLoad" />
                </button>
            </ul>

            <img src="/css/ajax-loader-1.gif" class="my-2" v-if="!users.length" />

        </div>

        <div v-else>

            <h5 class="d-flex justify-content-center align-items-center mt-4 mb-3">
                <fa-icon :icon="['fas','arrow-circle-left']" class="for-hover cursor-pointer mr-2" @click="backGetRole" />
                <span>{{ user.name ? user.name : `Пользователь #${user.id}` }}</span>
            </h5>

            <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                <span>Email</span>
                <strong>{{ user.email }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                <span>ФИО</span>
                <strong>{{ user.name || `---` }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                <span>Логин</span>
                <strong>{{ user.login || `---` }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                <span>Телефон</span>
                <strong>{{ user.phone || `---` }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                <span>Регистрация</span>
                <strong>{{ user.date }}</strong>
            </div>
            <div class="d-flex justify-content-between align-items-center px-1 mb-2">
                <span>Был на сайте</span>
                <strong>{{ user.last || `---` }}</strong>
            </div>

            <div class="position-relative role-item mb-3">
                <select class="custom-select" id="user-select-role" @change="setRole" v-model="selectRole" :disabled="this.userLoad">
                    <option value="0">Выберите группу...</option>
                    <option v-for="row in userRole" :key="row.id" :value="row.id">{{ row.name }}</option>
                </select>
                <img src="/css/ajax-loader-2.gif" v-if="this.userLoad" />
            </div>

            <hr>

            <div v-for="row in permissions" :key="row.id" class="text-left mb-3">

                <div class="custom-control custom-switch">

                    <input type="checkbox" class="custom-control-input" :id="`user-permission-${row.id}`" @change="setPermissionUser(row.id)" :data-permission="row.id" :checked="row.on" :disabled="row.id == permissionSet">
                    <label class="custom-control-label font-weight-bold" :for="`user-permission-${row.id}`">
                        <span>{{ row.slug }}</span>
                        <fa-icon :icon="['fas','circle']" :class="`text-${row.role_on ? 'success' : 'danger'} ml-1`" />
                        <img src="/css/ajax-loader-2.gif" class="ml-2" v-if="row.id == permissionSet" />
                    </label>
                </div>

                <div class="mt-0 px-1"><small>{{ row.name }}</small></div>

            </div>

        </div>

    </div>    
</template>

<script>
export default {

    data() {
        return {
            
            roles: [],
            permissions: [],

            role: {},
            permissionLoad: false,
            permissionSet: false,

            users: [],
            countUsers: 0,
            userLoad: false,

            user: {},
            userRole: [],
            selectRole: 0,

        }
    },

    mounted() {
        this.getData();
    },

    methods: {

        /**
         * Загрузка всех данных
         */
        getData() {
            this.getRoles();
            this.getLastUsers();
        },

        /**
         * Обработка кнопки назад
         */
        backGetRole() {
            this.roles = [];
            this.permissions = [];
            this.permissionLoad = false;
            this.role = {};
            this.users = [];
            this.userLoad = false;
            this.user = {};
            this.getData();
        },

        /**
         * Метод получения списка групп пользователей
         */
        getRoles() {
            axios.post(`/api/admin/users/getRoles`).then(({data}) => {
                this.roles = data.roles;
            }).catch(error => {

            });
        },

        /**
         * Метод получения списка прав группы
         */
        getPermissions(role = {}) {

            this.permissionLoad = role.id;

            axios.post(`/api/admin/users/getPermissions`, {
                id: role.id,
            }).then(({data}) => {

                this.permissions = data.permissions;

                this.permissionLoad = false;
                this.role = role;

            }).catch(error => {

            });

        },

        /**
         * Настройка права группе
         */
        setPermissionRole(id) {

            // Элементы
            let checkbox = document.getElementById(`permission-${id}`);
            let label = document.querySelector(`[for="permission-${id}"]`);

            // Данные на отправку
            let dataform = {
                id: checkbox.dataset.permission, // Идентификатор права
                checked: checkbox.checked, // Разрешено/Запрещено
                role: this.role.id, // Идентификатор группы
            }

            this.permissionSet = dataform.id; // Идентификатор права

            // Запрос на установку права
            axios.post(`/api/admin/users/setPermissionRole`, dataform).then(({data}) => {

                this.permissionSet = false; // Обнуление текущего права
                label.classList.remove("text-danger");

            }).catch(error => {

                this.permissionSet = false; // Обнуление текущего права
                checkbox.checked = !dataform.checked; // Чекбокс в исходное состояние

                // Окраска подписи чекбокса в красный ошибочный цвет
                label.classList.add("text-danger");

            });
            
        },

        /**
         * Получение списка последних зарегистрированных пользователей
         */
        getLastUsers() {

            axios.post(`/api/admin/users/getLastUsers`).then(({data}) => {
                
                this.users = data.users; // Список для вывода
                this.countUsers = data.count; // Общее количество пользователей

            }).catch(error => {

            });

        },

        /**
         * Данные строки пользователя
         */
        getUserData(user) {

            this.userLoad = user.id; // Идентификатор для анимации загрузки данных пользователя
            let btn = document.getElementById(`user-${user.id}`);

            axios.post(`/api/admin/users/getUserData`, {
                id: user.id
            }).then(({data}) => {

                btn.classList.remove("list-group-item-danger");

                this.user = data.user;
                this.userRole = data.roles;
                this.selectRole = data.user.role_id;
                this.permissions = data.permissions;

            }).catch(error => {

                btn.classList.add("list-group-item-danger");
                
            }).then(() => {
                this.userLoad = false; // Обнуление загрузки данных пользователя
            });

        },

        /**
         * Смена группы пользователю
         */
        setRole() {

            this.userLoad = true; // Индикация загрузки
            let select = document.getElementById('user-select-role');

            axios.post(`/api/admin/users/setRole`, {
                id: this.user.id, // Идентификатор пользователя
                role: this.selectRole // Идентификатор группы
            }).then(({data}) => {
                select.classList.remove('is-invalid');
            }).catch(error => {
                this.selectRole = this.user.role_id;
                select.classList.add('is-invalid');
            }).then(() => {
                this.userLoad = false; // Обнуление загрузки данных пользователя
            });

        },

        /**
         * Настройка индивидуального права
         */
        setPermissionUser(id) {

            // Элементы
            let checkbox = document.getElementById(`user-permission-${id}`);
            let label = document.querySelector(`[for="user-permission-${id}"]`);

            // Данные на отправку
            let dataform = {
                id: checkbox.dataset.permission, // Идентификатор права
                checked: checkbox.checked, // Разрешено/Запрещено
                user: this.user.id, // Идентификатор группы
            }

            this.permissionSet = dataform.id; // Идентификатор права

            // Запрос на установку права
            axios.post(`/api/admin/users/setPermissionUser`, dataform).then(({data}) => {

                label.classList.remove("text-danger");

            }).catch(error => {

                checkbox.checked = !dataform.checked; // Чекбокс в исходное состояние

                // Окраска подписи чекбокса в красный ошибочный цвет
                label.classList.add("text-danger");

            }).then(() => {

                this.permissionSet = false; // Обнуление текущего права

            });
            
        },

    },
    
}
</script>