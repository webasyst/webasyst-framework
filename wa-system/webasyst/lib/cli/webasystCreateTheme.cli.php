<?php

class webasystCreateThemeCli extends webasystCreateCliController
{
    protected $theme_id;

    protected $default_params = array(
        'prototype' => 'default',
    );

    protected function showHelp()
    {
        echo <<<HELP
Usage: php wa.php createTheme [app_id[,app_id_2[,...]]] [theme_id] [parameters]
    app_id - App ID (string in lower case for one app, or asterisk in quotes "*" for all enabled apps with theme support)
    theme_id - theme ID (string in lower case)
Optional parameters:
    -name (theme name; if comprised of several words, enclose in quotes; e.g., 'My theme')
    -parent (parent theme’s app_id)
    -version (theme version; e.g., 1.0.0)
    -vendor (numerical vendor ID)
    -prototype (original theme ID, default is `default`, also recommended value is `dummy`)
Example: php wa.php createTheme someapp mytheme -name 'My theme' -version 1.0.0 -vendor 123456 -frontend -settings
HELP;
        parent::showHelp();
    }

    protected function init()
    {
        $init = parent::init();
        $this->theme_id = waRequest::param(1);
        return $init && !empty($this->theme_id);
    }

    protected function initPath()
    {
        parent::initPath();
    }

    protected function verifyParams($params = array())
    {
        $errors = parent::verifyParams($params);

        if (!preg_match('@^[a-z][a-z0-9]+$@', $this->theme_id)) {
            $errors['theme_id'] = "Invalid theme ID";
        }
        if (!empty($errors['app_id'])) {
            if (trim($this->app_id, '\'"`') === '*') {
                unset($errors['app_id']);
            } elseif (strpos($this->app_id, ',')) {
                $this->app_id = preg_split('@[,\s]+@', $this->app_id);
                unset($errors['app_id']);
            }
        }

        if ($this->app_id === '*') {
            $this->app_id = array();
            $apps = wa()->getApps();
            foreach ($apps as $app_id => $app) {
                if (!empty($app['themes']) && !empty($app['frontend'])) {
                    $this->app_id[$app_id] = $app_id;
                }
            }
        } else {
            $this->app_id = array_combine((array)$this->app_id, (array)$this->app_id);
        }

        $errors['app_id'] = array();

        foreach ($this->app_id as $app_id) {
            if ($info = wa()->getAppInfo($app_id)) {
                if (empty($info['frontend'])) {
                    $errors['app_id'][] = sprintf("Application [%s] doesn't support frontend", $app_id);
                    unset($this->app_id[$app_id]);
                } elseif (empty($info['themes'])) {
                    $errors['app_id'][] = sprintf("Application [%s] doesn't support themes", $app_id);
                    unset($this->app_id[$app_id]);
                }
            } else {
                $errors['app_id'][] = sprintf("Application [%s] not found", $app_id);
                unset($this->app_id[$app_id]);
            }
        }

        if (!empty($errors['app_id'])) {
            $errors['app_id'] = implode("\n", $errors['app_id']);
        } else {
            unset($errors['app_id']);
        }
        return $errors;
    }

    protected function create($params = array())
    {
        $params += $this->default_params;

        $data = $this->fillThemeData($params);

        $apps = (array)$this->app_id;

        $themes = array();
        foreach ($apps as $this->app_id) {
            try {
                $themes[$this->app_id] = $this->createTheme($params, $data);
            } catch (waException $ex) {
                $themes[$this->app_id] = $ex->getMessage();
            }
        }
        return $themes;
    }

    protected function fillThemeData($params)
    {
        $data = array(
            'vendor'      => ifempty($params, 'vendor', $this->getDefaults('vendor')),
            'author'      => ifempty($params, 'author', $this->getDefaults('author')),
            'version'     => ifempty($params, 'version', $this->getDefaults('version')),
            'name'        => ifempty($params, 'name', $this->getDefaults('name')),
            'description' => '',
            'about'       => '',
        );

        $locales = array(
            wa()->getLocale(),
        );

        if (!empty($params['prototype'])) {
            $locales[] = 'en_US';
        }

        $locales = array_unique($locales);

        $data['name'] = array_fill_keys($locales, $data['name']);
        $data['description'] = array_fill_keys($locales, $data['description']);
        return $data;
    }

    protected function createTheme($params, $data)
    {
        $original_path = wa()->getAppPath('themes/', $this->app_id).$this->theme_id;
        $custom_path = wa()->getDataPath('themes/', true, $this->app_id).$this->theme_id;

        if (file_exists($original_path) || file_exists($custom_path)) {
            return sprintf('Theme [%s] already exists', $this->theme_id);
        }
        if (!empty($params['prototype']) && ($params['prototype'] !== 'null')) {

            $prototype = new waTheme($params['prototype'], $this->app_id, false, true);
            $theme = $prototype->copy($this->theme_id, $data);
            if ($prototype->parent_theme_id) {
                $theme->parent_theme = preg_replace('@(:)(.+)$@', '$1'.$this->theme_id, $prototype->parent_theme_id);
            }
        } else { #experimental case for new applications and absolutely new themes
            $path = wa()->getDataPath('themes/', true, $this->app_id).$this->theme_id.'/';
            $file = $path.'theme.xml';
            waFiles::create($file);
            @touch($file);
            @touch($path.'cover.png');
            $theme = new waTheme($this->theme_id, $this->app_id, waTheme::CUSTOM);

            $theme->vendor = $data['vendor'];
            $theme->author = $data['author'];
            $theme->version = $data['version'];
            $theme->name = $data['name'];
            $theme->description = $data['description'];

            $theme_files = $this->getThemeFiles();
            foreach ($theme_files as $class => $class_files) {
                foreach ($class_files as $file) {
                    $theme->addFile($file, sprintf('template used at [%s]', $class));
                    @touch($path.$file);
                }
            }

        }

        waCreateTheme::dry($theme);

        $theme = new waTheme($this->theme_id, $this->app_id);
        return $theme;
    }

    protected function getThemeFiles()
    {
        $app_config = wa($this->app_id, true)->getConfig();
        $classes = $app_config->getClasses();
        $controllers = array(
            'waViewAction',
            'waViewActions',
            'waViewController',
            'waLayout',
            'waPageAction',
        );
        $pattern = '@Frontend@';


        $files = array();
        $instances = array();
        foreach ($classes as $class => $file) {
            $file = preg_replace('@([\\]+|[/]{2,})@', '/', $file);
            try {
                if (!preg_match('@/plugins/@', $file)
                    &&
                    !preg_match('@/lib/(config|handlers)/@', $file)
                    &&
                    $class && class_exists($class, true)
                ) {
                    $frontend = (preg_match($pattern, $class) || preg_match('@/lib/actions/frontend/@', $file));
                    if ($frontend && ($parents = class_parents($class, true))) {
                        foreach ($controllers as $controller) {
                            if (in_array($controller, $parents)) {
                                $instance = new $class();


                                if ($instance instanceof $controller) {
                                    $reflection_class = new ReflectionClass($controller);
                                    #set method public for test purpose
                                    try {
                                        $method = $reflection_class->getMethod('getTemplate');
                                        $method->setAccessible(true);
                                        $files[$class] = $method->invoke($instance);
                                    } catch (Exception $ex) {
                                        //print $ex->getMessage();
                                    }
                                    $instances[$class] = $instance;
                                    break;
                                }
                            }
                        }

                        if (!isset($files[$class])) {
                            $files[$class] = false;
                        }
                    }
                }
            } catch (waException $ex) {
                print sprintf("Exception: %s\n", $ex->getMessage());
            }
        }

        $theme_files = array();
        $pattern = '@->setThemeTemplate\(\s*([\'|"])([^\)]+)\1\s*\);@';
        $templates_path = $app_config->getAppPath('templates/');
        foreach ($files as $class => $file) {
            if ($file && file_exists($templates_path.$file)) {
                unset($instances[$class]);
                unset($files[$class]);
                unset($classes[$class]);
            } else {
                $content = file_get_contents($classes[$class]);
                if (preg_match_all($pattern, $content, $matches)) {
                    $theme_files[$class] = array();
                    foreach ($matches[2] as $id => $value) {
                        if (strpos($value, $matches[1][$id]) === false) {
                            $theme_files[$class][] = $value;
                        } else {
                            echo "Skip template {$value} at {$class}\n";
                        }
                    }
                }
            }
        }

        $theme_files['_'] = array(
            sprintf('%s.js', $this->app_id),
            sprintf('%s.css', $this->app_id),
        );

        return $theme_files;
    }

    protected function showReport($themes = array(), $params = array())
    {
        /** @var waTheme|string[] $themes */
        foreach ($themes as $app_id => $theme) {
            if ($theme instanceof waTheme) {
                print "Theme created:\n";
                print sprintf("\tapp_id: %s\n", $app_id);
                print sprintf("\tpath: %s\n", $theme->path);
                print sprintf("\tversion: %s\n", $theme->version);
                if ($theme->parent_theme_id) {
                    print sprintf("\tparent: %s\n", $theme->parent_theme_id);
                }
            } else {
                print sprintf("Theme isn't created for app [%s].\n\tError: %s\n", $app_id, $theme);
            }
            print "\n";
        }
    }
}

class waCreateTheme extends waTheme
{
    public static function dry(waTheme $theme)
    {
        foreach ($theme->info['files'] as $path => &$properties) {
            $properties['custom'] = 0;
            $properties['modified'] = 0;
            unset($properties);
        }
        $theme->save();

        $original_path = wa()->getAppPath('themes/', $theme->app_id).$theme->id;
        waFiles::move($theme->path_custom, $original_path);
    }
}
