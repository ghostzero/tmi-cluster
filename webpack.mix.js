let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

mix.js('resources/js/tmi-cluster.js', 'public')
    .sass('resources/scss/tmi-cluster.scss', 'public')
    .setPublicPath('public')
    .version();
