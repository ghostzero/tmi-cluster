const numeral = require('numeral');

window.Vue = require('vue');

window.Vue.prototype.$http = require('axios');

window.chartColors = {
    color_1: '#6d00ff',
    color_2: '#994fff',
    color_3: '#bb7fff',
    color_4: '#d6adff',
};

window.chartBackgroundColors = {
    color_1: 'rgba(109, 0, 255, .5)',
    color_2: 'rgba(153, 79, 255, .5)',
    color_3: 'rgba(187, 127, 255, .5)',
    color_4: 'rgba(214, 173, 255, .5)',
};

Vue.filter('formatNumber', function (value) {
    return numeral(value).format("0,0");
});


Vue.component('tmi-dashboard', require('./components/TmiDashboard').default);

const app = new Vue({
    el: '#app',
});
