<?php

/**
 * @author WebAsyst Team
 *
 */
class blogSettingsAction extends waViewAction
{
    public function execute()
    {
        $is_admin = $this->getUser()->isAdmin($this->getApp());
        $storage = $this->getStorage();

        if (waRequest::post('save')) {

            $this->save();

            $save_id = rand(10000,99999);

            $storage->write('blog_setttings_save_id', $save_id);

            $this->redirect(array('module' => 'settings', 'save' => $save_id));

        }

        if ($is_admin) {
            $e_g = blogHelper::getAvailable();
            reset($e_g);

            $user = $this->getUser();

            $this->view->assign('e_g', current($e_g));
            $this->view->assign('user_email', $user->get('email', 'default'));

            // Checking if have saved now
            $save_id = $storage->read('blog_setttings_save_id');
        }

        // Checking if have saved now
        $save_id = $storage->read('blog_setttings_save_id');
        if ($save_id && $save_id == waRequest::get('save', 0)) {
            $storage->del('blog_setttings_save_id');
            $this->view->assign('saved', 1);
        }
        $this->getResponse()->setTitle(_w('Blog settings page'));

        $this->setLayout(new blogDefaultLayout());


        $this->view->assign('user_settings', $res = $this->getUserSettings());
        if ($is_admin) {
            $this->view->assign('backend_settings', $this->getFrontendSettings());
            $this->view->assign('routing_settings_url',blogHelper::getRouteSettingsUrl());
        }
        
        $app_settings_model = new waAppSettingsModel();
        $this->view->assign(
                'last_reminder_cron_time', 
                $app_settings_model->get('blog', 'last_reminder_cron_time')
        );
        $this->view->assign('cron_command', 'php '.wa()->getConfig()->getRootPath().'/cli.php blog reminder');
    }

    protected function getFrontendSettingsDefinition()
    {
        return array(
            'show_comments' => array(
                    'default' => '1',
                    'post_default' => '0',        // default for waRequest::post function
                    'validate' => array('vAvailable', array('0', '1')),
                    'type' => waRequest::TYPE_INT,
        ),
            'request_captcha' => array(
                    'default' => '1',
                    'post_default' => '0',        // default for waRequest::post function
                    'validate' => array('vAvailable', array('0', '1')),
                    'type' => waRequest::TYPE_INT,
        ),
            'require_authorization' => array(
                    'default' => '0',
                    'post_default' => '0',
                    'validate' => array('vAvailable', array('0', '1')),
                    'type' => waRequest::TYPE_INT,
        ),
            'rss_posts_number' => array(
                    'default' => '10',
                    'type' => waRequest::TYPE_INT,
                    'validate' => 'vUnint',
        ),
            'rss_author_tag' => array(
                    'default' => 'author',
                    'validate' => array('vAvailable', array('author', '')),
                    'type' => waRequest::TYPE_STRING_TRIM,
        ),
        );
    }

    protected function getFrontendSettings()
    {
        $settings = array();

        foreach ($this->getFrontendSettingsDefinition() as $name => $descriptor) {
            $settings[$name] = $this->appSettings($name, $descriptor['default']);
        }
        return $settings;
    }

    protected function getUserSettingsDefinition()
    {
        return array(
            'type_items_count' => array(
                    'post_default' => 'none',
                    'default' => 'posts:overdue:comments',
                    'validate' => array('vAvailable', array('none', 'posts', 'overdue', 'comments', 'comments_to_my_post')),
                    'callback' => create_function('$a', '
                 $a = explode(":",$a);
                 $res = array();
                 foreach ($a as $b) {
                     $res[preg_replace("/_.*/","",$b)] = $b;
                 };
                 return $res;
            '
                   ),
            ),
            'reminder' => array(
                'post_default' => null,
                'default' => null,
                'validate' => array('vAvailable', array('0', '1', '2', '3', '7'))
            ),
        );
    }

    protected function getUserSettings()
    {
        $app = $this->getApp();
        $user = waSystem::getInstance()->getUser();

        $settings = array();

        foreach ($this->getUserSettingsDefinition() as $name => $descriptor) {
            $settings[$name] = $user->getSettings($app, $name);
            $settings[$name] = !is_null($settings[$name]) ? $settings[$name] : $descriptor['default'];
            if(isset($descriptor['callback']) && is_callable($descriptor['callback'])) {
                $settings[$name] = call_user_func($descriptor['callback'],$settings[$name]);
            }
        }
        
        return $settings;
    }

    protected function save()
    {
        if ($this->getUser()->isAdmin($this->getApp())) {
            $all_defenition['frontend'] = $this->getFrontendSettingsDefinition();
        }

        $all_defenition['user'] = $this->getUserSettingsDefinition();

        // parse defenition and do type casting and validation
        foreach ($all_defenition as $type => $defenition) {

            $settings = array();

            foreach ($defenition as $name => $descriptor) {

                $value = waRequest::post($name,
                isset($descriptor['post_default']) ? $descriptor['post_default'] : $descriptor['default'],
                isset($descriptor['type'])?$descriptor['type']:null
                );


                if (isset($descriptor['validate'])) {

                    $validate = (array) $descriptor['validate'];

                    $validator = array_shift($validate);
                    $args = $validate;                    
                    if (method_exists($this, $validator)) {
                        $value = call_user_func_array(array($this, $validator), array_merge(array($value), $args));
                        if ($value === false) {
                            $value = $descriptor['default'];
                        }
                    }
                }

                $settings[$name] = $value;

            }

            $this->_save($type, $settings);
        }        
    }

    private function _save($type = 'frontend', $settings)
    {
        $app = $this->getApp();

        if ($type == 'frontend') {

            $settings_model = new waAppSettingsModel();

            foreach ($settings as $name => $value) {
                $settings_model->set($app, $name, $value);
            }

        } elseif ($type == 'user') {

            $user = waSystem::getInstance()->getUser();

            foreach ($settings as $name => $value) {
                if ($value !== null) {
                    $user->setSettings($app, $name, $value);
                } else {
                    $user->delSettings($app, $name);
                }

                if ($name === 'reminder') {
                    if ($value !== null) {
                        $user->setSettings($app, 'last_reminder_cron_time', 0);
                        
                        /**
                    * Notify plugins about saving reminder settings
                    * @event reminder_save
                    * @return void
                    */
                        wa()->event('reminder_save');
                    } else {
                        $user->delSettings($app, 'last_reminder_cron_time');
                    }
                }
                
            }
        }
        
        // save backend url for cron
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set('blog', 'backend_url', wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl());
        
    }

    private function vUnint($value) {
        $value = intval($value);
        return ($value > 0) ? $value : false;
    }

    private function vAvailable($value, $available) {
        if (is_array($value)) {
            foreach($value as $key => $item_value) {
                if (!in_array($item_value, $available)) {
                    unset($value[$key]);
                }
            }
            return $value ? implode(':',$value) : false;
        } else {
            return (in_array($value, $available)) ? $value : false;
        }
    }
}