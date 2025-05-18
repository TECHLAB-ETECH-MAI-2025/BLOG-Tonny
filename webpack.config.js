// webpack.config.js
const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    // Ajout de l'entrée pour la page d'article
    .addEntry('article-page', './assets/js/article-page.js')
    // Pour la recherche d'articles
    .addEntry('search', './assets/js/search.js')
    .addEntry('likes', './assets/js/likes.js')
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
    .enableSassLoader() // Activé pour Sass/SCSS
    // .enableTypeScriptLoader() // Décommentez si vous utilisez TypeScript
    // .enableReactPreset() // Décommentez si vous utilisez React
    .autoProvidejQuery(); // jQuery est disponible automatiquement

module.exports = Encore.getWebpackConfig();