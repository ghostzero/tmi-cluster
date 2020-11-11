window.Vue = require('vue');

window.Vue.prototype.$http = require('axios');

Vue.component('tmi-dashboard', require('./components/TmiDashboard').default);

const app = new Vue({
    el: '#app',
});
