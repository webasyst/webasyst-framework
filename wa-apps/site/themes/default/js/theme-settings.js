/**
 * Визуальный редактор темы
 * Позволяет настраивать параметры темы в реальном времени
 */

const THEME_GOOGLE_FONTS = {
    'Rubik': 'Rubik:ital,wght@0,300..900;1,300..900&display=swap',
    'Mulish': 'Mulish:ital,wght@0,200..1000;1,200..1000&display=swap',
    'Source Sans 3': 'Source+Sans+3:ital,wght@0,200..900;1,200..900&display=swap',
    'Spectral': 'Spectral:ital,wght@0,200;0,300;0,400;0,500;0,600;0,700;0,800;1,200;1,300;1,400;1,500;1,600;1,700;1,800&display=swap',
    'Source Serif 4': 'Source+Serif+4:ital,opsz,wght@0,8..60,200..900;1,8..60,200..900&display=swap',
    'Nunito': 'Nunito:ital,wght@0,200..1000;1,200..1000&display=swap'
};

const SYSTEM_FONT_STACK = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif';
const CHEVRON_SVG_LIGHT = "url(\"data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M18 9L12 15L6 9' stroke='%23000000' stroke-opacity='0.5' stroke-width='1.875' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E\")";
const CHEVRON_SVG_DARK = "url(\"data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M18 9L12 15L6 9' stroke='%23ffffff' stroke-opacity='0.5' stroke-width='1.875' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E\")";
const PREFERS_DARK_QUERY = '(prefers-color-scheme: dark)';

function getFontFallback(fontFamily) {
    if (fontFamily === 'Source Serif 4' || fontFamily === 'Spectral') {
        return 'serif';
    }
    return 'sans-serif';
}

function applyThemeFontFamily(root, fontFamily) {
    if (fontFamily === 'System') {
        root.style.setProperty('--font-family', SYSTEM_FONT_STACK);
        return;
    }
    root.style.setProperty('--font-family', '"' + fontFamily + '", ' + getFontFallback(fontFamily));
}

function applyThemeGoogleFont(fontFamily) {
    const realFontName = THEME_GOOGLE_FONTS[fontFamily];
    const head = document.head || document.getElementsByTagName('head')[0];
    const linkId = 'theme-google-font';
    const preconnectId = 'theme-google-font-preconnect';
    const preconnectStaticId = 'theme-google-font-preconnect-static';

    if (realFontName) {
        if (!document.getElementById(preconnectId)) {
            const preconnect = document.createElement('link');
            preconnect.id = preconnectId;
            preconnect.rel = 'preconnect';
            preconnect.href = 'https://fonts.googleapis.com';
            head.appendChild(preconnect);
        }

        if (!document.getElementById(preconnectStaticId)) {
            const preconnectStatic = document.createElement('link');
            preconnectStatic.id = preconnectStaticId;
            preconnectStatic.rel = 'preconnect';
            preconnectStatic.href = 'https://fonts.gstatic.com';
            preconnectStatic.crossOrigin = '';
            head.appendChild(preconnectStatic);
        }

        let link = document.getElementById(linkId);
        if (!link) {
            link = document.createElement('link');
            link.id = linkId;
            link.rel = 'stylesheet';
            head.appendChild(link);
        }
        link.href = 'https://fonts.googleapis.com/css2?family=' + realFontName;
    } else {
        const link = document.getElementById(linkId);
        if (link) {
            link.remove();
        }
    }
}

function applyThemeEditorFontVars(root, fontSize) {
    const baseSize = parseInt(fontSize, 10);
    if (!Number.isFinite(baseSize) || baseSize <= 0) {
        return;
    }

    const smSize = Math.max(10, baseSize - 2);
    const lgSize = Math.max(12, baseSize + 2);

    root.style.setProperty('--theme-editor-font-size', baseSize + 'px');
    root.style.setProperty('--theme-editor-font-size-sm', smSize + 'px');
    root.style.setProperty('--theme-editor-font-size-lg', lgSize + 'px');
}

function getAccentColorCssValue(accentColor, colorScheme) {
    if (accentColor !== '#000000' && accentColor !== '#666666') {
        return accentColor;
    }

    const darkAccentMap = {
        '#000000': '#888888',
        '#666666': '#555555'
    };

    if (colorScheme === 'dark') {
        return darkAccentMap[accentColor];
    }

    if (colorScheme === 'light') {
        return accentColor;
    }

    if (window.matchMedia && window.matchMedia(PREFERS_DARK_QUERY).matches) {
        return darkAccentMap[accentColor];
    }

    return accentColor;
}

function resolveChevronSvg(colorScheme) {
    if (colorScheme === 'dark') {
        return CHEVRON_SVG_DARK;
    }

    if (colorScheme === 'auto' && window.matchMedia && window.matchMedia(PREFERS_DARK_QUERY).matches) {
        return CHEVRON_SVG_DARK;
    }

    return CHEVRON_SVG_LIGHT;
}

function applyThemeChevronSvg(root, colorScheme) {
    root.style.setProperty('--chevron-svg', resolveChevronSvg(colorScheme));
}

// Применяем сохраненные настройки сразу при загрузке скрипта
(function() {
    'use strict';

    try {
        const saved = sessionStorage.getItem('themeEditorSettings');
        if (saved) {
            const settings = JSON.parse(saved);
            const root = document.documentElement;

            // Применяем настройки к CSS переменным
            if (settings.borderRadius) {
                root.style.setProperty('--element-border-radius', settings.borderRadius + 'px');
            }
            if (settings.colorScheme === 'light') {
                root.style.setProperty('color-scheme', 'light');
            } else if (settings.colorScheme === 'dark') {
                root.style.setProperty('color-scheme', 'dark');
            } else {
                root.style.removeProperty('color-scheme');
            }
            applyThemeChevronSvg(root, settings.colorScheme || 'auto');
            if (settings.accentColor) {
                root.style.setProperty('--accent-color', getAccentColorCssValue(settings.accentColor, settings.colorScheme || 'auto'));
            }
            if (settings.bgColorLight) {
                root.style.setProperty('--bg-color-light', settings.bgColorLight);
            }
            if (settings.bgColorDark) {
                root.style.setProperty('--bg-color-dark', settings.bgColorDark);
            }
            if (settings.fontFamily) {
                applyThemeFontFamily(root, settings.fontFamily);
                applyThemeGoogleFont(settings.fontFamily);
            }
            if (settings.fontSize) {
                root.style.setProperty('--font-size', settings.fontSize + 'px');
                applyThemeEditorFontVars(root, settings.fontSize);
            }
        }
    } catch (e) {
        console.error('Error applying saved settings:', e);
    }
})();

// Основной класс редактора
(function() {
    'use strict';

    class ThemeEditor {
        constructor(options = {}) {
            this.modal = document.getElementById('themeEditorModal');
            this.modalContent = document.getElementById('themeEditorContent');
            this.openBtn = document.getElementById('openThemeEditor');
            this.closeBtn = document.getElementById('closeThemeEditor');
            this.minimizeBtn = document.getElementById('minimizeThemeEditor');
            this.saveBtn = document.getElementById('saveSettings');
            this.resetBtn = document.getElementById('resetSettings');
            this.badge = document.getElementById('editorBadge');
            this.locale = options.locale || {};
            this.saveUrl = options.saveUrl || '';
            this.csrfToken = options.csrfToken || '';
            this.settingsArrayName = options.settingsArrayName || 'settings';
            this.windowStateKey = 'themeEditorWindowState';
            this.ui = {};
            this.colorGridsById = {};
            this.prefersDarkMediaQuery = window.matchMedia ? window.matchMedia(PREFERS_DARK_QUERY) : null;
            this.handleSystemColorSchemeChange = null;

            // Настройки по умолчанию
            this.defaultSettings = {
                borderRadius: '12',
                colorScheme: 'auto',
                accentColor: '#A538DC',
                bgColorLight: '#ffffff',
                bgColorDark: '#000000',
                fontFamily: 'Rubik',
                fontSize: '16',
                ...options
            };

            // Текущие настройки
            this.currentSettings = { ...this.defaultSettings };

            // Состояние окна
            this.isMinimized = false;
            this.savedTransform = null;
            this.windowState = null;

            // Параметры для перемещения окна
            this.isDragging = false;
            this.currentX = 0;
            this.currentY = 0;
            this.initialX = 0;
            this.initialY = 0;
            this.xOffset = 0;
            this.yOffset = 0;
            this.rafId = null;

            this.init();
        }

        init() {
            this.loadSettings();
            this.loadWindowState();
            this.cacheDom();
            this.applySettingsToPage();
            this.applySettingsToUI();
            this.setupEventListeners();
            this.initializeTabs();
            this.restoreWindowState();
        }

        cacheDom() {
            this.ui.borderRadiusSlider = document.getElementById('borderRadiusSlider');
            this.ui.borderRadiusInput = document.getElementById('borderRadiusInput');
            this.ui.fontSizeSlider = document.getElementById('fontSizeSlider');
            this.ui.fontSizeInput = document.getElementById('fontSizeInput');
            this.ui.fontFamilySelect = document.getElementById('fontFamilySelect');
            this.ui.colorSchemeRadios = Array.from(document.querySelectorAll('input[name="colorScheme"]'));
            this.ui.header = document.getElementById('themeEditorHeader');
            this.ui.tabs = Array.from(document.querySelectorAll('.theme-editor-tab'));
            this.ui.panels = Array.from(document.querySelectorAll('.theme-editor-panel'));
            this.ui.colorBlocks = {
                light: document.querySelector('[data-theme-color-block="light"]'),
                dark: document.querySelector('[data-theme-color-block="dark"]')
            };

            this.colorGridsById = {
                accentColorGrid: this.buildColorGrid('accentColorGrid'),
                bgColorLightGrid: this.buildColorGrid('bgColorLightGrid'),
                bgColorDarkGrid: this.buildColorGrid('bgColorDarkGrid')
            };
        }

        buildColorGrid(gridId) {
            const grid = document.getElementById(gridId);
            return {
                grid,
                options: grid ? Array.from(grid.querySelectorAll('.theme-editor-color-option')) : []
            };
        }

        // Загрузка сохраненных настроек
        loadSettings() {
            try {
                const saved = sessionStorage.getItem('themeEditorSettings');
                if (saved) {
                    this.currentSettings = { ...this.defaultSettings, ...JSON.parse(saved) };
                }
            } catch (e) {
                console.error('Ошибка загрузки настроек:', e);
            }
        }

        // Загрузка состояния окна
        loadWindowState() {
            try {
                const saved = sessionStorage.getItem(this.windowStateKey);
                if (saved) {
                    this.windowState = JSON.parse(saved);
                }
            } catch (e) {
                console.error('Ошибка загрузки состояния окна:', e);
            }
        }

        // Сохранение настроек
        saveSettings() {
            if (!this.saveUrl) {
                this.showNotification(this.locale.save_settings_error || 'Ошибка сохранения настроек', 'error');
                return;
            }

            const payload = this.buildServerSettings();
            const formData = new FormData();
            Object.keys(payload).forEach((key) => {
                formData.append(`${this.settingsArrayName}[${key}]`, payload[key]);
            });
            const csrfToken = this.getCsrfToken();
            if (csrfToken) {
                formData.append('_csrf', csrfToken);
            }

            if (this.saveBtn) {
                this.saveBtn.disabled = true;
            }

            fetch(this.saveUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then((response) => response.json())
                .then((data) => {
                    if (data && data.status === 'ok') {
                        this.closeModal();
                        this.showNotification(this.locale.save_success || 'Настройки сохранены', 'success');
                    } else {
                        throw new Error(data?.errors || 'save_failed');
                    }
                })
                .catch((e) => {
                    console.error(`${this.locale.save_settings_error}:`, e);
                    this.showNotification(this.locale.save_settings_error || 'Ошибка сохранения настроек', 'error');
                })
                .finally(() => {
                    if (this.saveBtn) {
                        this.saveBtn.disabled = false;
                    }
                });
        }

        // Сброс настроек к значениям по умолчанию
        resetSettings() {
            if (confirm(this.locale.reset_confirm || 'Сбросить все настройки к значениям по умолчанию?')) {
                this.currentSettings = { ...this.defaultSettings };
                this.applySettingsToUI();
                this.applySettingsToPage();
                this.persistSettingsToSession();
                this.showNotification(this.locale.reset_success || 'Настройки сброшены', 'success');
            }
        }

        // Применение настроек к UI редактора
        applySettingsToUI() {
            // Скругление углов
            if (this.ui.borderRadiusSlider && this.ui.borderRadiusInput) {
                this.ui.borderRadiusSlider.value = this.currentSettings.borderRadius;
                this.ui.borderRadiusInput.value = this.currentSettings.borderRadius;
            }

            // Цветовая схема
            this.ui.colorSchemeRadios.forEach(radio => {
                radio.checked = radio.value === this.currentSettings.colorScheme;
            });
            this.updateColorBlocksVisibility(this.currentSettings.colorScheme);

            // Акцентный цвет
            this.setActiveColor('accentColorGrid', this.currentSettings.accentColor);

            // Фон светлой темы
            this.setActiveColor('bgColorLightGrid', this.currentSettings.bgColorLight);

            // Фон тёмной темы
            this.setActiveColor('bgColorDarkGrid', this.currentSettings.bgColorDark);

            // Семейство шрифтов
            if (this.ui.fontFamilySelect) {
                this.ui.fontFamilySelect.value = this.currentSettings.fontFamily;
            }

            // Размер шрифта
            if (this.ui.fontSizeSlider && this.ui.fontSizeInput) {
                this.ui.fontSizeSlider.value = this.currentSettings.fontSize;
                this.ui.fontSizeInput.value = this.currentSettings.fontSize;
            }
        }

        // Применение настроек к странице
        applySettingsToPage() {
            const root = document.documentElement;

            // Скругление углов
            root.style.setProperty('--element-border-radius', this.currentSettings.borderRadius + 'px');

            // Цветовая схема
            if (this.currentSettings.colorScheme === 'light') {
                root.style.setProperty('color-scheme', 'light');
            } else if (this.currentSettings.colorScheme === 'dark') {
                root.style.setProperty('color-scheme', 'dark');
            } else {
                root.style.removeProperty('color-scheme');
            }
            applyThemeChevronSvg(root, this.currentSettings.colorScheme);

            // Цвета
            root.style.setProperty('--accent-color', getAccentColorCssValue(this.currentSettings.accentColor, this.currentSettings.colorScheme));
            root.style.setProperty('--bg-color-light', this.currentSettings.bgColorLight);
            root.style.setProperty('--bg-color-dark', this.currentSettings.bgColorDark);

            // Шрифт
            if (this.currentSettings.fontFamily) {
                applyThemeFontFamily(root, this.currentSettings.fontFamily);
                applyThemeGoogleFont(this.currentSettings.fontFamily);
            }

            // Размер шрифта
            root.style.setProperty('--font-size', this.currentSettings.fontSize + 'px');
            applyThemeEditorFontVars(root, this.currentSettings.fontSize);
        }

        // Установка активного цвета в сетке
        setActiveColor(gridId, color) {
            const data = this.colorGridsById[gridId];
            if (!data || !data.grid) return;

            const compareColor = (color || '').toLowerCase();
            data.options.forEach(option => {
                if ((option.dataset.color || '').toLowerCase() === compareColor) {
                    option.classList.add('active');
                } else {
                    option.classList.remove('active');
                }
            });
        }

        // Настройка обработчиков событий
        setupEventListeners() {
            // Открытие/разворачивание модального окна
            if (this.openBtn) {
                this.openBtn.addEventListener('click', () => {
                    if (this.isMinimized) {
                        this.restoreModal();
                    } else {
                        this.openModal();
                    }
                });
            }

            if (this.closeBtn) {
                this.closeBtn.addEventListener('click', () => this.closeModal());
            }

            if (this.minimizeBtn) {
                this.minimizeBtn.addEventListener('click', () => this.minimizeModal());
            }

            // Сохранение и сброс
            if (this.saveBtn) {
                this.saveBtn.addEventListener('click', () => this.saveSettings());
            }

            if (this.resetBtn) {
                this.resetBtn.addEventListener('click', () => this.resetSettings());
            }

            // Перемещение окна
            if (this.ui.header) {
                this.ui.header.addEventListener('mousedown', (e) => this.dragStart(e));
                document.addEventListener('mousemove', (e) => this.drag(e));
                document.addEventListener('mouseup', () => this.dragEnd());
            }

            // Скругление углов
            this.setupSliderSync('borderRadiusSlider', 'borderRadiusInput', (value) => {
                this.updateSetting('borderRadius', value);
            });

            // Цветовая схема
            this.ui.colorSchemeRadios.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    this.updateSetting('colorScheme', e.target.value);
                    this.updateColorBlocksVisibility(e.target.value);
                });
            });

            // Акцентный цвет
            this.setupColorGrid('accentColorGrid', (color) => {
                this.updateSetting('accentColor', color);
            });

            // Фон светлой темы
            this.setupColorGrid('bgColorLightGrid', (color) => {
                this.updateSetting('bgColorLight', color);
            });

            // Фон тёмной темы
            this.setupColorGrid('bgColorDarkGrid', (color) => {
                this.updateSetting('bgColorDark', color);
            });

            // Семейство шрифтов
            if (this.ui.fontFamilySelect) {
                this.ui.fontFamilySelect.addEventListener('change', (e) => {
                    this.updateSetting('fontFamily', e.target.value);
                });
            }

            // Размер шрифта
            this.setupSliderSync('fontSizeSlider', 'fontSizeInput', (value) => {
                this.updateSetting('fontSize', value);
            });

            // Сворачивание по Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.modal?.classList.contains('active') && !this.isMinimized) {
                    this.minimizeModal();
                }
            });

            if (this.prefersDarkMediaQuery) {
                this.handleSystemColorSchemeChange = () => {
                    if (this.currentSettings.colorScheme === 'auto') {
                        const root = document.documentElement;
                        applyThemeChevronSvg(root, 'auto');
                        root.style.setProperty('--accent-color', getAccentColorCssValue(this.currentSettings.accentColor, 'auto'));
                    }
                };

                if (typeof this.prefersDarkMediaQuery.addEventListener === 'function') {
                    this.prefersDarkMediaQuery.addEventListener('change', this.handleSystemColorSchemeChange);
                } else if (typeof this.prefersDarkMediaQuery.addListener === 'function') {
                    this.prefersDarkMediaQuery.addListener(this.handleSystemColorSchemeChange);
                }
            }
        }

        // Синхронизация слайдера и инпута
        setupSliderSync(sliderId, inputId, callback) {
            const slider = sliderId === 'borderRadiusSlider' ? this.ui.borderRadiusSlider : this.ui.fontSizeSlider;
            const input = inputId === 'borderRadiusInput' ? this.ui.borderRadiusInput : this.ui.fontSizeInput;

            if (!slider || !input) return;

            slider.addEventListener('input', (e) => {
                input.value = e.target.value;
                callback(e.target.value);
            });

            input.addEventListener('input', (e) => {
                let value = parseInt(e.target.value);
                const min = parseInt(slider.min);
                const max = parseInt(slider.max);

                if (value < min) value = min;
                if (value > max) value = max;

                slider.value = value;
                input.value = value;
                callback(value.toString());
            });
        }

        // Настройка цветовой сетки
        setupColorGrid(gridId, callback) {
            const data = this.colorGridsById[gridId];
            if (!data || !data.grid) return;

            data.options.forEach(option => {
                option.addEventListener('click', () => {
                    // Убираем active со всех опций в этой сетке
                    data.options.forEach(opt => opt.classList.remove('active'));
                    // Добавляем active к выбранной
                    option.classList.add('active');
                    // Вызываем callback
                    callback(option.dataset.color);
                });
            });
        }

        updateSetting(key, value) {
            this.currentSettings[key] = value;
            this.applySettingsToPage();
            this.persistSettingsToSession();
        }

        persistSettingsToSession() {
            try {
                sessionStorage.setItem('themeEditorSettings', JSON.stringify(this.currentSettings));
            } catch (e) {
                console.error('Ошибка сохранения настроек в sessionStorage:', e);
            }
        }

        persistWindowState() {
            try {
                const payload = {
                    isOpen: !!(this.modal && this.modal.classList.contains('active')),
                    isMinimized: !!this.isMinimized,
                    xOffset: Number.isFinite(this.xOffset) ? this.xOffset : 0,
                    yOffset: Number.isFinite(this.yOffset) ? this.yOffset : 0,
                    savedTransform: this.savedTransform || ''
                };
                sessionStorage.setItem(this.windowStateKey, JSON.stringify(payload));
            } catch (e) {
                console.error('Ошибка сохранения состояния окна:', e);
            }
        }

        getCsrfToken() {
            if (this.csrfToken) {
                return this.csrfToken;
            }

            const cookieValue = document.cookie
                .split('; ')
                .find((row) => row.startsWith('_csrf='));
            return cookieValue ? decodeURIComponent(cookieValue.split('=')[1]) : '';
        }

        buildServerSettings() {
            const fontFamily = this.currentSettings.fontFamily === 'Rubik' ? '' : this.currentSettings.fontFamily;
            return {
                border_radius: this.currentSettings.borderRadius ? `${this.currentSettings.borderRadius}px` : '',
                color_scheme: this.currentSettings.colorScheme || 'auto',
                color_accent: this.currentSettings.accentColor || '',
                color_background_light: this.currentSettings.bgColorLight || '',
                color_background_dark: this.currentSettings.bgColorDark || '',
                font_family: fontFamily || '',
                font_size: this.currentSettings.fontSize ? `${this.currentSettings.fontSize}px` : ''
            };
        }

        // Инициализация вкладок
        initializeTabs() {
            const tabs = this.ui.tabs;
            const panels = this.ui.panels;

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const targetPanel = tab.dataset.tab;

                    // Переключаем активную вкладку
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    // Переключаем активную панель
                    panels.forEach(panel => {
                        if (panel.dataset.panel === targetPanel) {
                            panel.classList.add('active');
                        } else {
                            panel.classList.remove('active');
                        }
                    });
                });
            });
        }

        // Открытие модального окна
        openModal() {
            if (this.modal) {
                this.modal.classList.add('active');
                this.modal.classList.remove('minimized');
                this.isMinimized = false;
                this.openBtn.classList.remove('minimized');
                if (this.savedTransform) {
                    this.modalContent.style.transform = this.savedTransform;
                } else {
                    this.centerModal();
                }
                this.snapToPixel();
                this.persistWindowState();
            }
        }

        // Закрытие модального окна
        closeModal() {
            if (this.modal) {
                this.modal.classList.remove('active', 'minimized');
                this.isMinimized = false;
                this.openBtn.classList.remove('minimized');
                this.persistWindowState();
            }
        }

        // Сворачивание модального окна
        minimizeModal() {
            if (this.modal && this.openBtn && this.modalContent) {
                // Сохраняем текущую позицию
                const currentTransform = this.modalContent.style.transform;
                this.savedTransform = currentTransform; // || 'translate(-50%, -50%)';

                this.setMinimizeTarget();

                // Очищаем inline transform, чтобы сработал стиль класса .minimized
                this.modalContent.style.transform = '';

                this.modal.classList.add('minimized');
                this.isMinimized = true;
                this.openBtn.classList.add('minimized');
                this.openBtn.setAttribute('title', this.locale.maximize_title || 'Развернуть окно настроек');
                this.persistWindowState();
            }
        }

        // Восстановление модального окна
        restoreModal() {
            if (this.modal && this.openBtn && this.modalContent) {
                // Восстанавливаем сохраненную позицию
                if (this.savedTransform) {
                    this.modalContent.style.transform = this.savedTransform;
                } else {
                     // Fallback если трансформация не была сохранена
                     this.modalContent.style.transform = 'translate(-50%, -50%)';
                }

                this.modal.classList.remove('minimized');
                this.isMinimized = false;
                this.openBtn.classList.remove('minimized');
                this.openBtn.setAttribute('title', this.locale.restore_title || 'Настроить тему');
                this.persistWindowState();
            }
        }

        // Центрирование модального окна
        centerModal() {
            if (!this.modalContent) return;

            this.xOffset = 0;
            this.yOffset = 0;
            this.modalContent.style.transform = 'translate(-50%, -50%)';
            this.savedTransform = 'translate(-50%, -50%)';
            this.persistWindowState();
        }

        setMinimizeTarget() {
            if (!this.openBtn || !this.modalContent) return;

            // Вычисляем позицию кнопки для анимации "в кнопку"
            const btnRect = this.openBtn.getBoundingClientRect();
            const btnCenter = {
                x: btnRect.left + btnRect.width / 2,
                y: btnRect.top + btnRect.height / 2
            };
            const windowCenter = {
                x: window.innerWidth / 2,
                y: window.innerHeight / 2
            };

            // Смещение от центра экрана до центра кнопки
            const targetX = btnCenter.x - windowCenter.x;
            const targetY = btnCenter.y - windowCenter.y;

            this.modalContent.style.setProperty('--minimize-x', targetX + 'px');
            this.modalContent.style.setProperty('--minimize-y', targetY + 'px');
        }

        restoreWindowState() {
            if (!this.modal || !this.modalContent || !this.openBtn) return;

            if (!this.windowState) {
                this.centerModal();
                return;
            }

            if (this.windowState.savedTransform) {
                this.savedTransform = this.windowState.savedTransform;
            }

            const hasOffsets = Number.isFinite(this.windowState.xOffset) && Number.isFinite(this.windowState.yOffset);
            if (hasOffsets) {
                this.xOffset = this.windowState.xOffset;
                this.yOffset = this.windowState.yOffset;
                this.currentX = this.xOffset;
                this.currentY = this.yOffset;
                this.setTranslate(this.xOffset, this.yOffset);
            } else if (this.savedTransform) {
                this.modalContent.style.transform = this.savedTransform;
            } else {
                this.centerModal();
            }

            if (this.windowState.isOpen) {
                this.modal.classList.add('active');
                if (this.windowState.isMinimized) {
                    this.setMinimizeTarget();
                    this.modalContent.style.transform = '';
                    this.modal.classList.add('minimized');
                    this.isMinimized = true;
                    this.openBtn.classList.add('minimized');
                    this.openBtn.setAttribute('title', this.locale.maximize_title || 'Развернуть окно настроек');
                } else {
                    this.modal.classList.remove('minimized');
                    this.isMinimized = false;
                    this.openBtn.classList.remove('minimized');
                    this.openBtn.setAttribute('title', this.locale.restore_title || 'Настроить тему');
                    this.snapToPixel();
                }
            } else {
                this.modal.classList.remove('active', 'minimized');
                this.isMinimized = false;
                this.openBtn.classList.remove('minimized');
            }
        }

        // Начало перемещения
        dragStart(e) {
            // Игнорируем клики по интерактивным элементам
            if (e.target.closest('button, input, select')) return;

            this.initialX = e.clientX - this.xOffset;
            this.initialY = e.clientY - this.yOffset;
            this.isDragging = true;
            if (this.modalContent) {
                this.modalContent.classList.add('dragging');
                this.modalContent.style.willChange = 'transform';
            }
        }

        // Перемещение
        drag(e) {
            if (!this.isDragging) return;

            e.preventDefault();

            this.currentX = e.clientX - this.initialX;
            this.currentY = e.clientY - this.initialY;

            this.xOffset = this.currentX;
            this.yOffset = this.currentY;

            this.scheduleTranslate();
        }

        // Окончание перемещения
        dragEnd() {
            this.isDragging = false;
            if (this.modalContent) {
                this.modalContent.classList.remove('dragging');
                this.modalContent.style.willChange = 'auto';
            }
            if (this.rafId) {
                cancelAnimationFrame(this.rafId);
                this.rafId = null;
            }
            this.snapToPixel();
            this.persistWindowState();
        }

        // Планирование перерисовки через requestAnimationFrame
        scheduleTranslate() {
            if (this.rafId) return;

            this.rafId = requestAnimationFrame(() => {
                this.setTranslate(this.currentX, this.currentY);
                this.rafId = null;
            });
        }

        // Применение трансформации
        setTranslate(xPos, yPos) {
            if (this.modalContent) {
                // Округляем значения, чтобы избежать субпиксельного рендеринга и размытия текста
                const x = Math.round(xPos);
                const y = Math.round(yPos);

                const transform = (x === 0 && y === 0)
                    ? 'translate(-50%, -50%)'
                    : `translate(calc(-50% + ${x}px), calc(-50% + ${y}px))`;
                this.modalContent.style.transform = transform;
                this.savedTransform = transform;
            }
        }

        updateColorBlocksVisibility(mode) {
            if (!this.ui.colorBlocks.light || !this.ui.colorBlocks.dark) return;

            const isAuto = mode === 'auto';
            const isLight = mode === 'light';
            const isDark = mode === 'dark';

            this.ui.colorBlocks.light.classList.toggle('is-hidden', !(isAuto || isLight));
            this.ui.colorBlocks.dark.classList.toggle('is-hidden', !(isAuto || isDark));
        }

        snapToPixel() {
            if (!this.modalContent) return;

            const rect = this.modalContent.getBoundingClientRect();
            const dx = rect.left - Math.round(rect.left);
            const dy = rect.top - Math.round(rect.top);

            if (Math.abs(dx) > 0.01 || Math.abs(dy) > 0.01) {
                this.xOffset -= dx;
                this.yOffset -= dy;
                this.currentX = this.xOffset;
                this.currentY = this.yOffset;
                this.setTranslate(this.xOffset, this.yOffset);
            }
        }

        // Показ уведомления
        showNotification(message, type = 'info') {
            // Создаем уведомление
            const notification = document.createElement('div');
            notification.className = `theme-editor-notification theme-editor-notification-${type}`;
            notification.textContent = message;

            // Стили для уведомления
            Object.assign(notification.style, {
                position: 'fixed',
                top: '24px',
                right: '24px',
                padding: '16px 24px',
                borderRadius: '12px',
                background: type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8',
                color: '#ffffff',
                fontWeight: '500',
                fontSize: '14px',
                boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
                zIndex: '1000001',
                animation: 'slideInFromRight 0.3s ease',
                fontFamily: 'inherit'
            });

            document.body.appendChild(notification);

            // Удаляем через 3 секунды
            setTimeout(() => {
                notification.style.animation = 'slideOutToRight 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    }

    // Добавляем анимации для уведомлений
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInFromRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutToRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
    `;
    document.head.appendChild(style);

    window.ThemeEditor = ThemeEditor;

})();
