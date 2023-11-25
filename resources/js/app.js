require('./bootstrap');

window.Vue = require('vue').default;
window.moment = require('moment');
import "vue-loading-overlay/dist/vue-loading.css";
import { BootstrapVue, IconsPlugin } from 'bootstrap-vue';
import 'bootstrap/dist/css/bootstrap.css';
import 'bootstrap-vue/dist/bootstrap-vue.css';
Vue.use(BootstrapVue);
Vue.use(IconsPlugin);

Vue.component('plan-calculator', require('./components/PlanCalculator.vue').default);

const app = new Vue({
    el: '#app',
});
