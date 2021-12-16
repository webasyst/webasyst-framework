module.exports = {
    publicPath: process.env.NODE_ENV === 'production' ? './' : '/',
    filenameHashing: false,
    devServer: {
        proxy: {
            '^/(webasyst/|wa-content/|wa-apps/|wa-data/|api.php)': {
                target: process.env.VUE_APP_WA_DEV,
                changeOrigin: true
            }
        }
    },
    pluginOptions: {
        i18n: {
            locale: "en",
            fallbackLocale: "en",
            localeDir: "locales",
            enableInSFC: false
        }
    }
}