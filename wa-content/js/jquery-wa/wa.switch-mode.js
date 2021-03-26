/**
 * Switch Design Theme Mode By Media Query
 */
new class ThemeMode {

    constructor() {
        this.mediaQueryList = window.matchMedia("(prefers-color-scheme: dark)");
        this.ls_var_name = 'wa_theme_user_mode';
        this.wa_theme_user_mode = localStorage.getItem(this.ls_var_name);
        this.$dark_mode_style = document.querySelector('#wa-dark-mode');

        this.init();
    }

    init() {
        const that = this;
        const event = document.createEvent("Event");

        if (that.wa_theme_user_mode === 'dark') {
            // if user theme mode settings exist - enable that
            that.setTheme(that.wa_theme_user_mode, that.mediaQueryList)
            event.initEvent("wa_theme_mode_dark", false, true);
            document.dispatchEvent(event);
        }

        that.handleOrientationChange(that.mediaQueryList);

        that.mediaQueryList.addListener(that.handleOrientationChange.bind(that));

        onstorage = event => {
            if (event.key !== that.ls_var_name || event.newValue === null) return;
            that.setTheme(event.newValue, that.mediaQueryList);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const $dark_mode = document.querySelector('[data-wa-theme-mode="dark"]'),
                $light_mode = document.querySelector('[data-wa-theme-mode="light"]'),
                $auto_mode = document.querySelector('[data-wa-theme-mode="auto"]');

            if ($dark_mode) {
                $dark_mode.addEventListener('click', function (e) {
                    e.preventDefault();
                    that.setTheme('dark', that.mediaQueryList)
                    event.initEvent("wa_theme_mode_dark", false, true);
                    document.dispatchEvent(event);
                });
            }

            if ($light_mode) {
                $light_mode.addEventListener('click', function (e) {
                    e.preventDefault();
                    that.setTheme('light', that.mediaQueryList)
                    event.initEvent("wa_theme_mode_light", false, true);
                    document.dispatchEvent(event);
                });
            }

            if ($auto_mode) {
                $auto_mode.addEventListener('click', function (e) {
                    e.preventDefault();
                    that.setTheme('auto', that.mediaQueryList)
                    event.initEvent("wa_theme_mode_auto", false, true);
                    document.dispatchEvent(event);
                });
            }
        });
    }

    setTheme(theme_mode, mediaQueryList) {
        let event = new Event("change");
        localStorage.setItem(this.ls_var_name, theme_mode);
        this.wa_theme_user_mode = theme_mode;
        mediaQueryList.dispatchEvent(event);
    }

    handleOrientationChange(mql) {
        const that = this;
        let event_matches;

        if (mql.target !== undefined) {
            event_matches = mql.target.matches;
        }

        setMediaColorScheme();

        if (mql.matches || event_matches) {
            if (this.wa_theme_user_mode === 'light') {
                setMediaColorScheme('light');
            }
        } else {
            if (this.wa_theme_user_mode === 'dark') {
                setMediaColorScheme('light');
            }
        }

        function setMediaColorScheme(scheme = 'dark') {
            that.$dark_mode_style.setAttribute('media', `(prefers-color-scheme: ${scheme})`);
        }
    }
}