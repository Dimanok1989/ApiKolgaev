<style>
    .main-users { max-width: 500px; }
    .role-item img {
        position: absolute;
        right: 10px;
    }
</style>

<template>
    <div class="text-center main-users mx-auto">

        <h5 class="mt-4">Настройка прав группы</h5>

        <ul class="list-group text-left mt-3" v-if="!permissions.length">
            <button type="button" class="list-group-item list-group-item-action position-relative role-item" v-for="item in roles" :key="item.id" @click="getPermissions(item)">
                <span>
                    <span>{{ item.name }}</span>
                    <span class="badge badge-primary">{{ item.count }}</span>
                </span>
                <img src="/css/ajax-loader-2.gif" class="my-2" v-if="item.id == permissionLoad" />
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

        }
    },

    mounted() {
        this.getRoles();
    },

    methods: {

        backGetRole() {
            this.roles = [];
            this.permissions = [];
            this.role = {};
            this.getRoles();
        },

        getRoles() {
            axios.post(`/api/admin/users/getRoles`).then(({data}) => {
                this.roles = data.roles;
            }).catch(error => {

            });
        },

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

        setPermissionRole(id) {

            let checkbox = document.getElementById(`permission-${id}`);

            let dataform = {
                id: checkbox.dataset.permission,
                checked: checkbox.checked,
                role: this.role.id,
            }

            this.permissionSet = dataform.id;

            axios.post(`/api/admin/users/setPermissionRole`, dataform)
            .then(({data}) => {

                this.permissionSet = false;

            })
            .catch(error => {

                this.permissionSet = false;
                checkbox.checked = !dataform.checked;

                let label = document.querySelector(`[for="permission-${id}"]`);
                label.classList.add("text-danger");

            });
            
        },

    },
    
}
</script>