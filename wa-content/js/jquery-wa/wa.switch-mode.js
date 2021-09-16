/**
 * Switch Design Theme Mode By Media Query
 */
new class ThemeMode {

    constructor() {
        this.mediaQueryList = window.matchMedia("(prefers-color-scheme: dark)");
        this.ls_var_name = 'wa_theme_user_mode';

        this.init();
    }

    init() {
        this.setTheme();
        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('DOMContentLoaded', () => {
            this.buttons = document.querySelectorAll('[data-wa-theme-mode]');

            for (let button of this.buttons) {
                button.addEventListener('click', (event) => {
                    this.switchButtonClick(event);
                });
            }
        });

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            this.setTheme();
        });
    }

    switchButtonClick(event) {
        event.preventDefault();
        const theme = event.target.closest('span').dataset.waThemeMode;
        this.setThemeManually(theme);
    }

    getSystemTheme() {
        return this.mediaQueryList.matches ? 'dark' : 'light';
    }

    setTheme() {
        let theme;

        const currentTheme = localStorage.getItem(this.ls_var_name);

        if (currentTheme && currentTheme !== 'auto') {
            theme = currentTheme;
        }

        if (!currentTheme || currentTheme === 'auto') {
            theme = this.getSystemTheme();
        }

        document.documentElement.setAttribute('data-theme', theme);
    }

    setThemeManually(theme) {
        localStorage.setItem(this.ls_var_name, theme);

        if (theme === 'auto') {
            const userTheme = this.getSystemTheme();
            document.documentElement.setAttribute('data-theme', userTheme);
            this.changeThemeEvent();
            return;
        }

        document.documentElement.setAttribute('data-theme', theme);
        this.changeThemeEvent();
    }

    changeThemeEvent() {
        const eventChange = new Event('wa-theme-change');
        document.documentElement.dispatchEvent(eventChange);
    }
}
