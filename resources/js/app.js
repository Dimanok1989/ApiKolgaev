require('./bootstrap');

import Vue from 'vue';
import VueRouter from 'vue-router';
Vue.use(VueRouter);

import { BootstrapVue, BootstrapVueIcons } from 'bootstrap-vue';
Vue.use(BootstrapVue);
Vue.use(BootstrapVueIcons);

import { library } from '@fortawesome/fontawesome-svg-core';
import { fas } from '@fortawesome/free-solid-svg-icons';
import { far } from '@fortawesome/free-regular-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

library.add(fas, far);
Vue.component('fa-icon', FontAwesomeIcon);

const files = require.context('./components/', true, /\.vue$/i);
const components = {};

files.keys().forEach(component => {
    let name = component.split('/').pop().split('.')[0];
    components[name] = Vue.component(name, files(component).default)
});

const routers = [
    { path: '/', name: 'welcome', component: components.Welcome },
    { path: '/main', name: 'main', component: components.Main },
    { path: '/disk', name: 'disk', component: components.Disk },
    { path: '/fuel', name: 'fuel', component: components.Fuel },
    { path: '*', component: components.NotFound }
];

const router = new VueRouter({
	mode: 'history',
	routes: routers,    
});

// Шина событий
Vue.prototype.$eventBus = new Vue();

const app = new Vue({
    el: '#app',
    router,
});
// console.log(app);