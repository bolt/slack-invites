var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .enableSourceMaps(!Encore.isProduction())

    .addEntry('js/app', './assets/js/app.js')
    .addStyleEntry('css/app', './assets/css/app.scss')

    .enableSassLoader()
    .enableVueLoader()

    // This is needed because Bootstrap expects jQuery to be available as a global variable
    .autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();
