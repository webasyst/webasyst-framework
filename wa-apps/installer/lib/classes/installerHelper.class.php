<?php
class installerHelper
{
    /**
     *
     * @var waAppSettingsModel
     */
    private static $model;
    /**
     *
     * @var waInstallerApps
     */
    private static $installer;

    /**
     *
     * @return waInstallerApps
     */
    public static function &getInstaller()
    {
        if (!self::$model) {
            self::$model = new waAppSettingsModel();
        }
        if (!self::$installer) {
            self::$installer = new waInstallerApps(self::$model->get('webasyst', 'license', false), wa()->getLocale());
        }
        return self::$installer;
    }

    public static function getHash()
    {
        return self::getInstaller()->getHash();
    }

    public static function checkUpdates(&$messages)
    {
        try {
            self::getInstaller()->checkUpdates();
        } catch (Exception $ex) {
            $text = $ex->getMessage();
            $message = array('text'=>$text, 'result'=>'fail');
            if (strpos($text, "\n")) {
                $texts = array_filter(array_map('trim',explode("\n",$message['text'])),'strlen');
                while($message['text'] = array_shift($texts)) {
                    $messages[] = $message;
                }
            } else {
                $messages[] = $message;
            }
        }

    }

    public static function getApps(&$messages, &$update_counter = null, $filter = array())
    {
        if ($update_counter !== null) {
            $update_counter = is_array($update_counter) ? array_fill_keys(array('total','applicable','payware'), 0) : 0;
        }
        $app_list = array();

        try {
            $app_list = self::getInstaller()->getApplicationsList(false, array(), wa()->getDataPath('images', true), $messages);
            self::$model->ping();
            if ($update_counter !== null) {
                $minimize = is_array($update_counter)? true : false;
                $update_counter=waInstallerApps::getUpdateCount($app_list, $minimize);
                self::$model->ping();
                if (!$minimize) {
                    wa('installer')->getConfig()->setCount($update_counter ? $update_counter : null);
                }
            }

        } catch (Exception $ex) {
            if ($messages === null) {
                throw $ex;
            } else {
                $messages[] = array('text'=>$ex->getMessage(), 'result'=>'fail');
            }
        }

        foreach ($app_list as $key => &$item) {
            if ($item['slug'] == 'developer') {
                $item['downloadable'] = $item['current'] ? true: false;
                break;
            }
        }
        unset($item);

        if ($filter) {
            foreach ($app_list as $key => &$item) {

                if (!empty($filter['enabled']) && empty($item['enabled']) && empty($item['current'])) { //not present
                    unset($app_list[$key]);
                    continue;
                }

                if (!empty($filter['extras'])) {
                    $extras = $filter['extras'];
                    if ( empty($item['current'][$extras]) || empty($item['extras'][$extras]) ) {//themes not supported or not available
                        unset($app_list[$key]);
                        continue;
                    }
                }

            }
            unset($item);
        }
        return $app_list;
    }

    public static function isDeveloper()
    {
        $result = false;
        $paths = array();
        $paths[] = dirname(__FILE__).'/.svn';
        $paths[] = dirname(__FILE__).'/.git';
        $root_path = wa()->getConfig()->getRootPath();
        $paths[] = $root_path.'/.svn';
        $paths[] = $root_path.'/.git';
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $result = true;
                break;
            }
        }
        return $result;
    }


    /**
     *
     * Search first entry condition
     * @param array $items
     * @param array $filter
     * @return mixed
     */
    public static function search($items, $filter)
    {
        $match = null;
        foreach ($items as &$item) {
            $matched = true;
            foreach ($filter as $field => $value) {
                if ($value && ($item[$field] != $value)) {
                    $matched = false;
                    break;
                }
            }
            if ($matched) {
                $match = $item;
                break;
            }
        }
        return $match;
    }


    /**
     *
     * Compare arrays by specified fields
     * @param array $a
     * @param array $b
     * @param array $fields
     * @return bool
     */
    public static function equals($a, $b, $fields = array('vendor','edition'))
    {
        $equals = true;
        foreach ($fields as $field) {
            if (empty($a[$field]) && empty($b[$field])) {
                //do nothing
            } else if ($a[$field] != $b[$field]) {
                $equals = false;
                break;
            }
        }

        return $equals;
    }
}
