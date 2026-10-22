<?php

/**
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * Creates the first backend user, the initial routing config and the default
 * email transport. Shared between the web installer (webasystLoginFirstAction)
 * and the console installer (wa-installer/cli.php). Requires a fully loaded
 * framework environment (waSystem + webasyst app).
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @package wa-system
 * @subpackage webasyst
 */
class webasystFirstUser
{
    /**
     * Create the first backend user.
     *
     * @param waUser $user
     * @param string $login
     * @param string|null $password When empty, a random one is generated.
     * @param string|null $account_name
     * @return array array(
     *     'errors'  => array list of errors, empty on success,
     *     'password' => string password actually set (useful for auto-login),
     * )
     */
    public static function createFirstUser(waUser $user, $login, $password = null, $account_name = null)
    {
        $result = array(
            'errors'  => array(),
            'password' => $password,
        );
        if (empty($account_name)) {
            $account_name = _ws('My company');
        }
        $app_settings_model = new waAppSettingsModel();
        $app_settings_model->set('webasyst', 'name', $account_name);
        $email = $user->get('email', 'default');
        if ($email) {
            $app_settings_model->set('webasyst', 'email', $email);
            $app_settings_model->set('webasyst', 'sender', $email);
        }

        if (empty($password)) {
            $password = waUtils::getRandomHexString(16);
            $result['password'] = $password;
        }

        $user['locale'] = empty($user['locale']) ? wa()->getLocale() : $user['locale'];
        $user['is_user'] = 1;
        $user['login'] = $login;
        $user['password'] = $password;
        $user['create_method'] = 'install';
        if ($errors = $user->save()) {
            foreach ($errors as $k => $v) {
                $result['errors']['all'][] = $k.": ".(is_array($v) ? implode(', ', $v) : $v);
            }
            return $result;
        }

        $user->setRight('webasyst', 'backend', 1);

        self::createRouting();
        self::setEmailTransport();

        return $result;
    }

    /**
     * Write wa-config/routing.php with frontend rules of installed apps
     * (site app gets the catch-all rule), if it does not exist yet.
     *
     * @return void
     */
    public static function createRouting()
    {
        $path = wa()->getConfig()->getPath('config');
        if (file_exists($path.'/routing.php')) {
            return;
        }

        $apps = wa()->getApps();
        $data = array();
        $domain = wa()->getConfig()->getDomain();
        $site = false;
        foreach ($apps as $app_id => $app) {
            if ($app_id == 'site') {
                $site = true;
            } elseif (!empty($app['frontend'])) {
                $routing = array(
                    'url' => $app_id.'/*',
                    'app' => $app_id,
                );

                if (!empty($app['routing_params']) && is_array($app['routing_params'])) {
                    wa($app_id);
                    foreach ($app['routing_params'] as $routing_param => $routing_param_value) {
                        if (is_callable($routing_param_value)) {
                            $app['routing_params'][$routing_param] = call_user_func($routing_param_value);
                        }
                    }
                    $routing = array_merge($routing, $app['routing_params']);
                }
                $data[$domain][] = $routing;
            }
        }
        if ($site) {
            $data[$domain][] = [
                'url' => '*',
                'app' => 'site',
                '_name' => _ws('Home page')
            ];
        }
        waUtils::varExportToFile($data, $path.'/routing.php');
    }

    /**
     * Set up the default email transport provided by Webasyst ID connection,
     * when the latter is set up and no mail config exists yet.
     *
     * @return void
     */
    public static function setEmailTransport()
    {
        $path = wa()->getConfig()->getPath('config');
        if (file_exists($path.'/mail.php')) {
            return;
        }

        $auth = new waWebasystIDWAAuth();
        if (!$auth->isClientConnected()) {
            return;
        }

        waUtils::varExportToFile([ 'default' => [ 'type' => 'wasender' ] ], $path.'/mail.php');
    }
}
