<?php

/**
 * Class waPluginTemplates
 */
class waPluginTemplates {

    /**
     * @var array массив подготовленных шаблонов
     */
    protected $templates = array();

    /**
     * @var array массив зарегистрированных шаблонов
     */
    protected $registered_templates = array();

    /**
     * @var waTheme| null - тема отеносительно которой будет производиться поиск шаблонов в теме см. $this->setTheme
     */
    protected  $theme = null;

    /**
     * @var string  - Идентификатор приложения (shop,site,blog....)
     */
    protected $app_id = '';

    /**
     * @var string - Строковый идентификатор плагина
     */
    protected $plugin_id = '';

    /**
     * @var waPlugin - Объект плагина (shopMypluginPlugin)
     */
    protected $plugin = null;

    /**
     * @var string - путь к корневой папке плагина от корня сервера
     */
    protected $plugin_path = '';

    /**
     * @var string - тип откуда следует брать шаблоны
     * ENUM
     * plugin - брать шаблоны по ключу из плагина
     * theme -  брать шаблоны по ключу из темы дизайна
     * custom - брать принудительно из переданных шаблонов в массиве templates
     */
    protected $templates_type = 'theme';


    /**
     * waPluginTemplates constructor.
     * @param waPlugin $plugin
     * @param null $templates
     * @param null $templates_type
     * @throws  waException
     */
    public function __construct(waPlugin $plugin, $templates = null, $templates_type = null)
    {
        try {
            $this->init($plugin, $templates, $templates_type);
        } catch (waException $e) {
            throw new waException($e->getMessage());
        }
    }

    /**
     * @param waPlugin $plugin
     * @param null $templates
     * @param null $templates_type
     * @throws waException
     */
    protected function init(waPlugin $plugin, $templates = null, $templates_type = null){
        /* Ставим плагин и его данные */
        $this->setPlugin($plugin);
        /* Устанавливаем шаблоны плагина */
        if($templates === null && method_exists($plugin, 'getRegisterTemplates')) {
            $templates = $plugin->getRegisterTemplates();
        } elseif(!is_array($templates)) {
            $templates = array();
        }
        $this->registerTemplates($templates);
        /* Выбираем тип щаблонов */
        if($templates_type === null && method_exists($plugin, 'getTemplatesType')) {
            $templates_type = $plugin->getTemplatesType();
        } elseif ($templates_type === null || !$this->isValidType($templates_type)) {
            $templates_type = 'theme';
        }
        $this->setTemplatesType($templates_type);
    }

    /**
     * @param $plugin waPlugin
     * @throws waException
     */
    protected function setPlugin(waPlugin $plugin) {
        /* if(is_array($plugin) && count($plugin) == 2) {
             $this->plugin = wa($plugin[0])->getPlugin($plugin[1]);
             $this->getApp() = $plugin[0];
             $this->plugin_id = $plugin[1];
         }  elseif(is_string($plugin)) {
             $this->getApp() = waSystem::getInstance()->getApp();
             $this->plugin_id = $plugin;
             $this->plugin = wa($this->getApp())->getPlugin($plugin);
         }
         else*/
        if(is_object($plugin) && ($plugin instanceof waPlugin)) {
            $this->app_id = waSystem::getInstance()->getApp();
            /* Костыль , нужен метод получения идентификатора  */
            $class_name = get_class($plugin);
            $plugin_id = str_replace($this->app_id, '', $class_name);
            $plugin_id = str_replace('Plugin', '', $plugin_id);
            $this->plugin_id = strtolower($plugin_id);
            $this->plugin = $plugin;
        } else {
            throw new waException('Переданы некорректные данные плагина!');
        }
        if(!$this->plugin) {
            throw new waException('Плагин не найден!');
        }
        $this->plugin_path = wa()->getAppPath('plugins/'.$this->plugin_id, $this->getApp());
    }

    /**
     * Проверяет и записывает шаблоны плагина
     * @param array $templates
     * @throws waException
     */
    protected function registerTemplates($templates = array()) {
        if(!is_array($templates)) {
            throw new waException('Регистрируемые шаблоны должны быть переданы в массиве!');
        }
        if(!empty($templates)) {
            $valid_types = 0;
            foreach ($templates as $type => $data)  {
                if($this->isValidType($type)) {
                    $valid_types++;
                    if($type != 'custom') {
                        if(is_array($data)) {
                            foreach ($data as $key => $template) {
                                if(!is_array($template)) {
                                    throw new waException('Шаблон должен быть передан в качестве массива данных');
                                }
                                if(!array_key_exists('path', $template)) {
                                    throw new waException('Не указан путь к шаблону ('.(string)$key.')!');
                                }
                                if(!array_key_exists('description', $template)) {
                                    $templates[$type][$key]['description'] = '';
                                }
                            }
                        }
                    }
                }
            }
            if($valid_types < 3 && $valid_types != count($templates)) {
                throw new waException('Переданы некорректные типы шаблонов!');
            }
        }
        $this->registered_templates = $templates;
    }

    /**
     * Устанавливает тип шаблонов
     * @param $type
     */
    public function setTemplatesType($type)  {
        if($this->isValidType($type)) {
            $this->templates_type = $type;
        }
    }

    /**
     * Проверяет тип шаблонов
     * @param $type string
     * @return bool
     */
    protected function isValidType($type = '') {
        return ($type == 'theme' || $type == 'plugin' || $type == 'custom');
    }

    public function getPluginId(){
        return $this->plugin_id;
    }
    public function getApp() {
        return $this->app_id;
    }

    /**
     *
     * Метод возвращает адрес статических файлов от корня домена
     * используется для получения URL css и JS
     * @param $key
     * @param null|waTheme|string $theme
     * @return string
     */
    public function getTemplateUrl($key, $theme = null)  {
        $template_type = $this->templates_type;
        try {
            $theme = $this->getTheme($theme);
        } catch (waException $e) {
            $theme = false;
            $template_type = 'plugin';
        }
        // Пробуем сформировать URL из файла темы дизайна
        if($template_type == 'theme' && $theme) {
            $template_theme = $this->getThemeTemplatePath($this->getTemplateFilepathByKey($key, true), $theme);
            if ($template_theme) {
                return $this->addPluginPathToTheme($theme->getUrl()).ltrim($this->getTemplateFilepathByKey($key, true), '/\\');
            }
            $template_type = 'plugin';
        }
        // Берем URL из файла плагина, если есть
        if($template_type == 'plugin') {
            $template_plugin = $this->getPluginPath($this->getTemplateFilepathByKey($key));
            if ($template_plugin) {
                return $this->plugin->getPluginStaticUrl().ltrim($this->getTemplateFilepathByKey($key), '/\\');
            }
        }
        return false;
    }

    /**
     * Возвращает подготовленый шаблон по ключу для дальнейшей обработки смарти  методе fetch($templates->getTemplate('key'))
     * если совсем не будет найден никакой шаблон вернет строку для смарти ('string: ')
     * @param $key - ключ шаблона в массиве шаблонов
     * @param $default_template_type - если шаблонв теме не найден , какой шаблон взять, из плагина или кастомный?
     * @return mixed
     */
    public function getTemplate($key,  $default_template_type = 'plugin') {
        $template_type = $this->templates_type;
        if(!isset($this->templates[$key])) {
            $this->templates[$key] = false;
            $template_theme = $this->getThemeTemplatePath($this->getTemplateFilepathByKey($key, true));
            $template_plugin = $this->getPluginPath($this->getTemplateFilepathByKey($key));

            if($template_type == 'theme') {
                if ($template_theme) {
                    $this->templates[$key] = 'file:'.$template_theme;
                } else {
                    $template_type = $default_template_type;
                }
            }
            if($template_type == 'custom') {
                $custom_templates = $this->getRegisteredTemplates('custom');
                if(is_array($custom_templates) && array_key_exists($key, $custom_templates)) {
                    $this->templates[$key] = 'string:'.(string)$custom_templates[$key];
                }
            }

            if($template_type == 'plugin') {
                if ($template_plugin) {
                    $this->templates[$key] = 'file:'.$template_theme;
                }
            }

            if($this->templates[$key] === false) {
                $this->templates[$key] = 'string: ';
            }
        }
        return  $this->templates[$key];
    }

    /**
     * Проверяет существуют ли файлы шаблонов плагина в темах дизайна
     * @param $themes - массив тем, состоящий из объектов waTheme
     * @return bool
     */
    public function themesTemplatesExists($themes = null) {
        if(!is_array($themes)) {
            $themes = self::getThemes($this->getApp());
        }
        if(!empty($themes)) {
            foreach ($themes as $theme) {
                if ($theme instanceof waTheme) {
                    if (!$this->themeTemplatesExists($theme)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Проверяет существуют ли файлы шаблонов плагина в теме дизайна из ячейки массива зарегистрированных шаблонов ['theme']
     * @param $theme - объект waTheme или строковый идентификатор темы, если тема не передана, будет взята текущая тема если запрос из фронтенда
     * @return bool
     */
    public function themeTemplatesExists($theme = null) {
        $theme = $this->getTheme($theme);
        if ($theme) {
            foreach ($this->getRegisteredTemplates('theme') as $key => $data) {
                if (!$this->themeTemplateExists($data['path'], $theme)) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Проверяет существует файл шаблона в теме дизайна
     * @param $file_path - путь к файлу от корня папки плагина в теме
     * @param $theme - объект waTheme или строковый идентификатор темы, если тема не передана, будет взята текущая тема если запрос из фронтенда
     * @return bool
     */
    public function themeTemplateExists($file_path, $theme = null) {
        try {
            $theme = $this->getTheme($theme);
        } catch (waException $e) {
            $theme = false;
        }
        if($theme) {
            if (file_exists($this->addPluginPathToTheme($theme->getPath()).ltrim($file_path, '/\\'))) {
                return true;
            }
        }
        return false;
    }


    /**
     * Добавление произвольного шаблона плагина в тему дизайна
     * Если вы хотите скопировать шаблон из плагина воспользуйтесь методом  self::templateCopyToTheme($key)
     * @param $theme $theme - объект waTheme или строковый идентификатор темы
     * @param string $filename
     * @param string $content
     * @param string $description
     * @return bool|false|int
     * @throws waException
     */
    public function templateAddToTheme($theme,  $filename = '' , $content = '',  $description = '') {
        if(is_string($theme) && waTheme::exists($theme)) {
            $theme = new waTheme($theme, $this->getApp());
        } elseif(!($theme instanceof waTheme)) {
            $theme = false;
        }
        if(!empty($filename) &&  ($theme instanceof waTheme))  {
            // Мини защита от перезаписи файлов темы
            $plugin_path = $this->getThemePluginPath();
            $_filename = $filename;
            if(stripos($plugin_path, $this->plugin_id) === false &&  !preg_match('#^'.'plugin.'.$this->plugin_id.'.'.'#', $filename))  {
                $_filename = 'plugin.'.$this->plugin_id.'.'.$filename;
            }
            $file_path = $this->addPluginPathToTheme($theme->getPath()).ltrim($_filename,'/\\');
            if (file_exists($file_path)) {
                return true;
            }
            $theme->addFile($this->getThemePluginPath($_filename), (string)$description);
            $theme->save();
            return  waFiles::write($file_path, $content);
        }
        return false;
    }

    /**
     * Копирование шаблона в тему дизайна по ключу шаблона
     * @param $theme - объект waTheme или строковый идентификатор темы
     * @param $key  - ключ шаблона в массиве шаблонов, должен присутствовать в массиве шаблонов темы и плагина
     * @param bool $force - принудительно перезаписать файл
     * @return bool|false|int
     * @throws waException
     */
    public function templateCopyToTheme($theme, $key, $force = false) {
        if(is_string($theme) && waTheme::exists($theme)) {
            $theme = new waTheme($theme, $this->getApp());
        } elseif(!($theme instanceof waTheme)) {
            $theme = false;
        }
        if(!empty($key) && ($theme instanceof waTheme)) {
            $plugin_templates = $this->getRegisteredTemplates('plugin');
            $theme_templates = $this->getRegisteredTemplates('theme');
            if(array_key_exists($key, $theme_templates)) {
                $theme_template = $theme_templates[$key];
                // Если есть файл с таким же ключем в плагине и нет контента в описании файла темы
                if(array_key_exists($key, $plugin_templates) && !array_key_exists('content', $theme_template)) {
                    $content = $this->getPluginTemplateContent($key);
                } elseif (array_key_exists('content', $theme_template)) {
                    $content = $theme_template['content'];
                } else {
                    $content = '';
                }
                $file_path = $this->addPluginPathToTheme($theme->getPath()).ltrim($theme_template['path'], '/\\');
                if (file_exists($file_path) && !$force) {
                    return true;
                }
                if(!file_exists($file_path)) {
                    $theme->addFile($this->getThemePluginPath($theme_template['path']), $theme_template['description']);
                    $theme->save();
                }

                /* Если файл не был создан ранее, создаем или перезаписываем */
                if (!file_exists($file_path) || $force) {
                    return  waFiles::write($file_path, $content);
                }
            }
        }
        return false;
    }

    /**
     * Возвращает путь с префиксом(директорией плагина) относительно корня темы дизайна
     * @param string $path - путь к файлу
     * @return string
     */
    public function getThemePluginPath($path = '') {
        return '/plugins/'.$this->getPluginId().'/'.ltrim($path, '/\\');
    }

    /**
     * Возвращает путь темы дизайна к директории плагина
     * @param string $theme_path - папка темы дизайна или URL
     * @return string
     */
    protected function addPluginPathToTheme($theme_path = '') {
        return rtrim((string)$theme_path, '/\\').$this->getThemePluginPath();
    }
    /**
     * Перезаписывает файл в теме дизайна принудительно, возвращает к исходному
     * @param $theme - объект waTheme или строковый идентификатор темы ('default', 'topshop'....)
     * @param $key - ключ шаблона в массиве шаблонов, должен присутствовать в массиве шаблонов темы и плагина
     * @return bool|false|int
     */
    public function templateRevertToTheme($theme, $key) {
        return $this->templateCopyToTheme($theme, $key, true);
    }

    /**
     * Копирует файлы шаблонов плагина в тему дизайна
     * @param $theme  - объект waTheme или строковый идентификатор темы ('default', 'topshop'....)
     * @param bool $force
     * @return bool
     */
    public function templatesCopyToTheme($theme, $force = false) {
        if(is_string($theme) && waTheme::exists($theme)) {
            $theme = new waTheme($theme, $this->getApp());
        } elseif(!($theme instanceof waTheme)) {
            $theme = false;
        }
        if($theme) {
            $return  = true;
            foreach (array_keys($this->getRegisteredTemplates('theme')) as $key) {
                if(!$this->templateCopyToTheme($theme, $key, $force)){
                    $return = false;
                }
            }
            return $return;
        }
        return false;

    }

    /**
     * Возвращает абсолютный путь к файлу шаблонов плагина от корня сервера
     * @param string $path - путь к файлу относительно корневой директории плагина
     * @return string
     */
    public function getPluginPath($path = '')  {
        if($path !== false) {
            $path = ltrim($path,'/\\');
            if(file_exists($this->plugin_path.DIRECTORY_SEPARATOR.$path)) {
                return  $this->plugin_path.DIRECTORY_SEPARATOR.$path;
            }
        }
        return false;
    }

    /**
     * Возвращает абсолютный путь к файлам шаблонов темы дизайна от кроня сервера
     * @param string $path - путь к файлу относительно корневой директории плагина в теме дизайна
     * @param null $theme - объект waTheme или строковый идентификатор темы, если тема не передана, будет взята текущая тема если запрос из фронтенда
     * @return bool|string
     */
    public function getThemeTemplatePath($path = '', $theme = null) {
        if($path !==false) {
            $theme = $this->getTheme($theme);
            if($theme) {
                $theme_file = $this->addPluginPathToTheme($theme->getPath()).ltrim($path, '/\\');
                if (file_exists($theme_file)) {
                    return $theme_file;
                }
            }
        }
        return false;
    }

    /**
     * Возвпращает объект темы дизайна для текущей витрины
     * @param $theme  - объект waTheme или строковый идентификатор темы, если тема не передана, будет взята текущая тема если запрос из фронтенда
     * @return false|waTheme
     * @throws waException
     */
    public  function getTheme($theme = null)
    {
        if($theme !== null) {
            if(is_string($theme) && waTheme::exists($theme)) {
                $theme = new waTheme($theme, $this->getApp());
            } elseif(!($theme instanceof waTheme)) {
                $theme = false;
            }
            return $theme;
        }
        if($theme === null && wa()->getEnv() == 'frontend') {
            $theme = new waTheme(waRequest::getTheme());
        } else {
            throw new waException('Использование шаблонов темы по умолчанию доступно только во фронтенде!');
        }
        return $theme;
    }

    /**
     * Устанавливает тему дизайна относительно которой бедт работать класс
     * @param null $theme
     * @throws waException
     */
    public function setTheme($theme = null) {
        if($theme === null && wa()->getEnv() == 'frontend') {
            $theme = new waTheme(waRequest::getTheme());
        }  else {
            if(is_string($theme) && waTheme::exists($theme)) {
                $theme = new waTheme($theme, $this->getApp());
            }
        }
        if(!($theme instanceof waTheme)) {
            throw new waException('Передана некорректная тема для установки!');
        }
        $this->theme = $theme;
    }

    /**
     * Возвращает название файла шаблона по его идентифиткатору (ключу), либо для файлов темы дизайна, либо для файлов плагина
     * @param string $key - ключ шаблона в массиве шаблонов, должен присутствовать в массиве шаблонов темы и плагина
     * @param bool $is_theme - искать в массие темы дизайна?
     * @return bool|mixed
     */
    public function getTemplateFilepathByKey($key = '', $is_theme = false) {
        $templates = $is_theme? $this->getRegisteredTemplates('theme') : $this->getRegisteredTemplates('plugin');
        if(isset($templates[$key])) {
            return $templates[$key]['path'];
        } elseif($is_theme && count(explode('.',(string)$key))>1) {
            // Для произвольных шаблонов если есть расширение файла, отдаем
            return $key;
        }
        return false;
    }

    /**
     * Возвращает все зарегистрированные шаблоны
     * либо для плагина, для темы дизайна, кастомные или все вместе
     * @param $type string - тип шаблонов
     * @return array
     */
    public function getRegisteredTemplates($type = null) {
        if($type === null) {
            return $this->registered_templates;
        } elseif(array_key_exists($type, $this->registered_templates)) {
            return $this->registered_templates[$type];
        }
        return array();
    }

    /**
     * Возвращает объекты тем для приложения
     * @param null $app_id
     * @return waTheme[]
     * @throws waException
     */
    public static function getThemes($app_id = null) {
        if($app_id === null) {
            $app_id = waSystem::getInstance()->getApp();
        }
        return wa()->getThemes($app_id);
    }
    /**
     * Возвращает код файла щаблона плагина по его ключу
     * @param $key
     * @return bool|string
     */
    public function getPluginTemplateContent($key) {
        $template_plugin = $this->getTemplateFilepathByKey($key);
        if($template_plugin) {
            $template_plugin = $this->getPluginPath($template_plugin);
            return file_get_contents($template_plugin);
        }
        return '';
    }
}
