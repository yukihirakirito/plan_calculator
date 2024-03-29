require('./bootstrap');

window.Vue = require('vue').default;

Vue.component('example-component', require('./components/ExampleComponent.vue').default);
Vue.component('plan-calculator', require('./components/PlanCalculator.vue').default);

const app = new Vue({
    el: '#app',
});
