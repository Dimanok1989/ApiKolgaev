// import Main from './components/Main.vue';
// import Welcome from './components/Welcome.vue';
// import NotFound from './components/system/NotFound.vue';

// import Mail from './views/Mail.vue';
// import Fuel from './views/Fuel.vue';



export const routers = [
    { path: '/', name: 'welcome', component: Welcome },
    { path: '/main', name: 'main', component: Welcome },
    // { path: '/mail', component: Mail },
    // { path: '/fuel', name: 'fuel', component: Fuel },
    { path: '*', component: NotFound }
];