/**
 * Визуальный редактор темы
 * Позволяет настраивать параметры темы в реальном времени
 */

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
            }
            if (settings.accentColor) {
                root.style.setProperty('--accent-color', settings.accentColor);
            }
            if (settings.bgColorLight) {
                root.style.setProperty('--bg-color-light', settings.bgColorLight);
            }
            if (settings.bgColorDark) {
                root.style.setProperty('--bg-color-dark', settings.bgColorDark);
            }
            if (settings.fontFamily) {
                root.style.setProperty('--font-family', '"' + settings.fontFamily + '", sans-serif');
            }
            if (settings.fontSize) {
                root.style.setProperty('--font-size', settings.fontSize + 'px');
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
            this.applySettingsToPage();
            this.applySettingsToUI();
            this.setupEventListeners();
            this.initializeTabs();
            this.centerModal();
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

        // Сохранение настроек
        saveSettings() {
            try {
                sessionStorage.setItem('themeEditorSettings', JSON.stringify(this.currentSettings));
                this.closeModal();
                this.showNotification(this.locale.save_success || 'Настройки сохранены', 'success');
            } catch (e) {
                console.error(`${this.locale.save_settings_error}:`, e);
                this.showNotification(this.locale.save_settings_error, 'error');
            }
        }

        // Сброс настроек к значениям по умолчанию
        resetSettings() {
            if (confirm(this.locale.reset_confirm || 'Сбросить все настройки к значениям по умолчанию?')) {
                this.currentSettings = { ...this.defaultSettings };
                this.applySettingsToUI();
                this.applySettingsToPage();
                sessionStorage.removeItem('themeEditorSettings');
                this.showNotification(this.locale.reset_success || 'Настройки сброшены', 'success');
            }
        }

        // Применение настроек к UI редактора
        applySettingsToUI() {
            // Скругление углов
            const borderRadiusSlider = document.getElementById('borderRadiusSlider');
            const borderRadiusInput = document.getElementById('borderRadiusInput');
            if (borderRadiusSlider && borderRadiusInput) {
                borderRadiusSlider.value = this.currentSettings.borderRadius;
                borderRadiusInput.value = this.currentSettings.borderRadius;
            }

            // Цветовая схема
            const colorSchemeRadios = document.querySelectorAll('input[name="colorScheme"]');
            colorSchemeRadios.forEach(radio => {
                radio.checked = radio.value === this.currentSettings.colorScheme;
            });

            // Акцентный цвет
            this.setActiveColor('accentColorGrid', this.currentSettings.accentColor);

            // Фон светлой темы
            this.setActiveColor('bgColorLightGrid', this.currentSettings.bgColorLight);

            // Фон тёмной темы
            this.setActiveColor('bgColorDarkGrid', this.currentSettings.bgColorDark);

            // Семейство шрифтов
            const fontFamilySelect = document.getElementById('fontFamilySelect');
            if (fontFamilySelect) {
                fontFamilySelect.value = this.currentSettings.fontFamily;
            }

            // Размер шрифта
            const fontSizeSlider = document.getElementById('fontSizeSlider');
            const fontSizeInput = document.getElementById('fontSizeInput');
            if (fontSizeSlider && fontSizeInput) {
                fontSizeSlider.value = this.currentSettings.fontSize;
                fontSizeInput.value = this.currentSettings.fontSize;
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

            // Цвета
            root.style.setProperty('--accent-color', this.currentSettings.accentColor);
            root.style.setProperty('--bg-color-light', this.currentSettings.bgColorLight);
            root.style.setProperty('--bg-color-dark', this.currentSettings.bgColorDark);

            // Шрифт
            if (this.currentSettings.fontFamily) {
                root.style.setProperty('--font-family', '"' + this.currentSettings.fontFamily + '", sans-serif');
            }

            // Размер шрифта
            root.style.setProperty('--font-size', this.currentSettings.fontSize + 'px');
        }

        // Установка активного цвета в сетке
        setActiveColor(gridId, color) {
            const grid = document.getElementById(gridId);
            if (!grid) return;

            const options = grid.querySelectorAll('.theme-editor-color-option');
            options.forEach(option => {
                if (option.dataset.color.toLowerCase() === color.toLowerCase()) {
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
            const header = document.getElementById('themeEditorHeader');
            if (header) {
                header.addEventListener('mousedown', (e) => this.dragStart(e));
                document.addEventListener('mousemove', (e) => this.drag(e));
                document.addEventListener('mouseup', () => this.dragEnd());
            }

            // Скругление углов
            this.setupSliderSync('borderRadiusSlider', 'borderRadiusInput', (value) => {
                this.currentSettings.borderRadius = value;
                this.applySettingsToPage();
            });

            // Цветовая схема
            const colorSchemeRadios = document.querySelectorAll('input[name="colorScheme"]');
            colorSchemeRadios.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    this.currentSettings.colorScheme = e.target.value;
                    this.applySettingsToPage();
                });
            });

            // Акцентный цвет
            this.setupColorGrid('accentColorGrid', (color) => {
                this.currentSettings.accentColor = color;
                this.applySettingsToPage();
            });

            // Фон светлой темы
            this.setupColorGrid('bgColorLightGrid', (color) => {
                this.currentSettings.bgColorLight = color;
                this.applySettingsToPage();
            });

            // Фон тёмной темы
            this.setupColorGrid('bgColorDarkGrid', (color) => {
                this.currentSettings.bgColorDark = color;
                this.applySettingsToPage();
            });

            // Семейство шрифтов
            const fontFamilySelect = document.getElementById('fontFamilySelect');
            if (fontFamilySelect) {
                fontFamilySelect.addEventListener('change', (e) => {
                    this.currentSettings.fontFamily = e.target.value;
                    this.applySettingsToPage();
                });
            }

            // Размер шрифта
            this.setupSliderSync('fontSizeSlider', 'fontSizeInput', (value) => {
                this.currentSettings.fontSize = value;
                this.applySettingsToPage();
            });

            // Сворачивание по Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.modal?.classList.contains('active') && !this.isMinimized) {
                    this.minimizeModal();
                }
            });
        }

        // Синхронизация слайдера и инпута
        setupSliderSync(sliderId, inputId, callback) {
            const slider = document.getElementById(sliderId);
            const input = document.getElementById(inputId);

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
            const grid = document.getElementById(gridId);
            if (!grid) return;

            const options = grid.querySelectorAll('.theme-editor-color-option');
            options.forEach(option => {
                option.addEventListener('click', () => {
                    // Убираем active со всех опций в этой сетке
                    options.forEach(opt => opt.classList.remove('active'));
                    // Добавляем active к выбранной
                    option.classList.add('active');
                    // Вызываем callback
                    callback(option.dataset.color);
                });
            });
        }

        // Инициализация вкладок
        initializeTabs() {
            const tabs = document.querySelectorAll('.theme-editor-tab');
            const panels = document.querySelectorAll('.theme-editor-panel');

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
                this.centerModal();
            }
        }

        // Закрытие модального окна
        closeModal() {
            if (this.modal) {
                this.modal.classList.remove('active', 'minimized');
                this.isMinimized = false;
                this.openBtn.classList.remove('minimized');
            }
        }

        // Сворачивание модального окна
        minimizeModal() {
            if (this.modal && this.openBtn && this.modalContent) {
                // Сохраняем текущую позицию
                const currentTransform = this.modalContent.style.transform;
                this.savedTransform = currentTransform; // || 'translate(-50%, -50%)';

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

                // Очищаем inline transform, чтобы сработал стиль класса .minimized
                this.modalContent.style.transform = '';

                this.modal.classList.add('minimized');
                this.isMinimized = true;
                this.openBtn.classList.add('minimized');
                this.openBtn.setAttribute('title', this.locale.maximize_title || 'Развернуть окно настроек');
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
            }
        }

        // Центрирование модального окна
        centerModal() {
            if (!this.modalContent) return;

            this.xOffset = 0;
            this.yOffset = 0;
            this.modalContent.style.transform = 'translate(-50%, -50%)';
            this.savedTransform = 'translate(-50%, -50%)';
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
        // Применение трансформации
        setTranslate(xPos, yPos) {
            if (this.modalContent) {
                // Подсказываем браузеру готовиться к трансформации для лучшей отзывчивости
                if (this.modalContent.style.willChange !== 'transform') {
                    this.modalContent.style.willChange = 'transform';
                }

                // Округляем значения, чтобы избежать субпиксельного рендеринга и размытия текста
                const x = Math.round(xPos);
                const y = Math.round(yPos);

                const transform = `translate(calc(-50% + ${x}px), calc(-50% + ${y}px))`;
                this.modalContent.style.transform = transform;
                this.savedTransform = transform;
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
