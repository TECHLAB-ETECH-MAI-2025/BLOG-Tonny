const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/js/app.js')
    .enableStimulusBridge('./assets/controllers.json')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })
    .enablePostCssLoader() // Assurez-vous que cela est activé pour le CSS
    .enableSassLoader() // Activez si vous utilisez Sass/SCSS
    // .enableTypeScriptLoader() // Décommentez si vous utilisez TypeScript
    // .enableReactPreset() // Décommentez si vous utilisez React
    .autoProvidejQuery() // Décommentez si vous avez des problèmes avec un plugin jQuery
;

module.exports = Encore.getWebpackConfig();
