<?php

class mailerMailerPersonalSettingsHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $theme_id = 'default';
        $routes = $params['routes'];
        if ($routes) {
            $route = current($routes);
            if (!empty($route['theme'])) {
                $theme_id = $route['theme'];
            }
        }

        $app_theme = new waTheme($theme_id, 'mailer');
        $theme_path = $app_theme->getPath();

        $files = array();
        foreach (array('my.subscriptions.html') as $f) {
            $file = $app_theme->getFile($f);
            $file['id'] = $f;
            $file_path = $theme_path.'/'.$f;
            $content = file_exists($file_path) ? file_get_contents($file_path) : '';
            $file['content'] = $content;
            $files[] = $file;
        }

        $parent_themes = array();
        $apps = wa()->getApps();

        foreach ($apps as $theme_app_id => $app) {
            if (!empty($app['themes']) && ($themes = wa()->getThemes($theme_app_id))) {
                $themes_data = array();
                foreach($themes as $id => $theme) {
                    $themes_data[$id] = $theme->name;
                }
                if ($themes_data) {
                    $parent_themes[$theme_app_id] = array(
                        'name'=>$app['name'],
                        'img'=>$app['img'],
                        'themes'=>$themes_data,
                    );
                }
            }
        }
        $view = wa()->getView();
        $view->assign('files', $files);

        $view->assign('theme', $app_theme);
        $view->assign('theme_id', $theme_id);
        $view->assign('parent_themes', $parent_themes);

        $template = wa()->getAppPath('templates/handlers/PersonalSettings.html', 'mailer');
        return $view->fetch($template);
    }
}