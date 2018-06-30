<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2011 Webasyst LLC
 * @package wa-system
 * @subpackage autoload
 */
class waAutoload
{
    protected static $registered = false;
    protected static $instance = null;
    protected $classes = array();
    protected $base_path = null;

    protected function __construct()
    {
        $this->base_path = realpath(dirname(__FILE__).'/../..');
    }

    /**
     * @return waAutoload
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function register()
    {
        if (self::$registered) {
            return;
        }

        ini_set('unserialize_callback_func', 'spl_autoload_call');
        if (false === spl_autoload_register(array(self::getInstance(), 'autoload'))) {
            throw new Exception(sprintf('Unable to register %s::autoload as an autoloading method.', get_class(self::getInstance())));
        }

        self::$registered = true;
    }

    /**
     * Unregister waAutoload from spl autoloader.
     *
     * @return void
     */
    public static function unregister()
    {
        spl_autoload_unregister(array(self::getInstance(), 'autoload'));
        self::$registered = false;
    }

    public function autoload($class)
    {
        if ($path = $this->get($class)) {
            if (!file_exists($path)) {

                // Clear autoload cache of loaded apps
                if (!isset($this->system_classes[$class]) && class_exists('waSystem', false) && !waSystemConfig::isDebug()) {
                    foreach(array_keys(wa()->getApps()) as $app_id) {
                        if (waSystem::isLoaded($app_id)) {
                            waAppConfig::clearAutoloadCache($app_id);
                        }
                    }
                }

                $msg = sprintf('Not found file [%1$s] for class [%2$s]', $path, $class);
                if ($class == 'waException') {
                    throw new Exception($msg, 500);
                } else {
                    throw new waException($msg, 500);
                }
            }

            require_once $path;

            if (!class_exists($class, false) && !interface_exists($class, false) &&
                !(function_exists('trait_exists') && trait_exists($class, false))
            ) {
                $msg = sprintf('Not found class [%2$s] in file [%1$s]', $path, $class);
                if ($class == 'waException') {
                    throw new Exception($msg, 500);
                } else {
                    throw new waException($msg, 500);
                }
            }
        }
    }

    public function get($class)
    {
        if (isset($this->system_classes[$class])) {
            return $this->base_path.'/wa-system/'.$this->system_classes[$class];
        } elseif (substr($class, 0, 2) == 'wa') {
            if (strpos($class, '.') !== false) return null;

            if (substr($class, 0, 4) === 'waDb') {
                $file = $this->base_path.'/wa-system/database/'.$class.'.class.php';
                if (is_readable($file)) {
                    return $file;
                }
            } elseif (substr($class, -5) == 'Model') {
                $path = $this->base_path.'/wa-system/webasyst/lib/models/'.substr($class, 0, -5).'.model.php';
                if (is_readable($path)) {
                    return $path;
                }
            } elseif (substr($class, 0, 9) === 'waContact') {
                if (substr($class, 0, 16) === 'waContactAddress') {
                    // formatters live in the same file as waContactAddressField
                    $result = $this->base_path.'/wa-system/contact/waContactAddressField.class.php';
                } else {
                    $result = $this->base_path.'/wa-system/contact/'.$class.'.class.php';
                }
                if (is_readable($result)) {
                    return $result;
                }
            }

            $dir = preg_replace("/^wai?([A-Z][a-z]+).*?$/", "$1", $class);
            $path = $this->base_path.'/wa-system/'.strtolower($dir).'/'.$class.'.'.(substr($class, 0, 3) === 'wai' ? 'interface' : 'class').'.php';
            if (file_exists($path)) {
                return $path;
            } else {
                $dir = preg_replace("/^wa.*?([A-Z][a-z]+)$/", "$1", $class);
                $path = $this->base_path.'/wa-system/'.strtolower($dir).'/'.$class.'.class.php';
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        if (isset($this->classes[$class])) {
            return $this->base_path.'/'.$this->classes[$class];
        }
        return null;
    }

    public function add($class, $path = null)
    {
        if (is_array($class)) {
            foreach ($class as $class_name => $path) {
                $this->classes[$class_name] = $path;
            }
        } else {
            $this->classes[$class] = $path;
        }
    }

    /**
     * Get all classes that are available for autoloading.
     * @return array classname => file path relative to wa-root, no leading slash
     */
    public function getClasses()
    {
        $result = $this->classes;
        foreach ($this->system_classes as $class => $path) {
            $result[$class] = 'wa-system/'.$path;
        }
        return $result;
    }

    protected $system_classes = array(
        'waAPIException'           => 'api/waAPIException.class.php',
        'waAPIController'          => 'api/waAPIController.class.php',
        'waAPIDecorator'           => 'api/waAPIDecorator.class.php',
        'waAPIDecoratorXML'        => 'api/waAPIDecoratorXML.class.php',
        'waAPIDecoratorJSON'       => 'api/waAPIDecoratorJSON.class.php',
        'waAPIRightsMethod'        => 'api/waAPIRightsMethod.class.php',
        'waAPIMethod'              => 'api/waAPIMethod.class.php',

        'waAuth'                   => 'auth/waAuth.class.php',
        'waAuthAdapter'            => 'auth/waAuthAdapter.class.php',
        'waOAuth2Adapter'          => 'auth/waOAuth2Adapter.class.php',
        'waiAuth'                  => 'auth/waiAuth.interface.php',

        'waAutoload'               => 'autoload/waAutoload.class.php',

        'waFileCache'              => 'cache/waFileCache.class.php',
        'waMemcachedCacheAdapter'  => 'cache/adapters/waMemcachedCacheAdapter.class.php',
        'waFileCacheAdapter'       => 'cache/adapters/waFileCacheAdapter.class.php',
        'waXcacheCacheAdapter'     => 'cache/adapters/waXcacheCacheAdapter.class.php',
        'waRuntimeCache'           => 'cache/waRuntimeCache.class.php',
        'waSerializeCache'         => 'cache/waSerializeCache.class.php',
        'waSystemCache'            => 'cache/waSystemCache.class.php',
        'waVarExportCache'         => 'cache/waVarExportCache.class.php',
        'waiCache'                 => 'cache/waiCache.interface.php',

        'waAppConfig'              => 'config/waAppConfig.class.php',
        'waConfig'                 => 'config/waConfig.class.php',
        'waRightConfig'            => 'config/waRightConfig.class.php',
        'waSystemConfig'           => 'config/waSystemConfig.class.php',

        'waAction'                 => 'controller/waAction.class.php',
        'waActions'                => 'controller/waActions.class.php',
        'waController'             => 'controller/waController.class.php',
        'waDefaultViewController'  => 'controller/waDefaultViewController.class.php',
        'waFrontController'        => 'controller/waFrontController.class.php',
        'waJsonActions'            => 'controller/waJsonActions.class.php',
        'waJsonController'         => 'controller/waJsonController.class.php',
        'waUploadJsonController'   => 'controller/waUploadJsonController.class.php',
        'waLoginAction'            => 'controller/waLoginAction.class.php',
        'waForgotPasswordAction'   => 'controller/waForgotPasswordAction.class.php',
        'waSignupAction'           => 'controller/waSignupAction.class.php',
        'waLongActionController'   => 'controller/waLongActionController.class.php',
        'waMyNavAction'            => 'controller/waMyNavAction.class.php',
        'waMyProfileAction'        => 'controller/waMyProfileAction.class.php',
        'waViewAction'             => 'controller/waViewAction.class.php',
        'waViewActions'            => 'controller/waViewActions.class.php',
        'waViewController'         => 'controller/waViewController.class.php',
        'waWidget'                 => 'widget/waWidget.class.php',

        'waCurrency'               => 'currency/waCurrency.class.php',

        'waCaptcha'                => 'captcha/waCaptcha.class.php',
        'waReCaptcha'              => 'captcha/recaptcha/waReCaptcha.class.php',
        'waPHPCaptcha'              =>'captcha/phpcaptcha/waPHPCaptcha.class.php',

        'waModel'                  => 'database/waModel.class.php',
        'waModelExpr'              => 'database/waModelExpr.class.php',
        'waNestedSetModel'         => 'database/waNestedSetModel.class.php',

        'waSMS'                    => 'sms/waSMS.class.php',
        'waSMSAdapter'             => 'sms/waSMSAdapter.class.php',

        'waDateTime'               => 'datetime/waDateTime.class.php',

        'waEventHandler'           => 'event/waEventHandler.class.php',

        'waDbException'            => 'exception/waDbException.class.php',
        'waException'              => 'exception/waException.class.php',
        'waRightsException'        => 'exception/waRightsException.class.php',

        'waFiles'                  => 'file/waFiles.class.php',
        'waNet'                    => 'file/waNet.class.php',
        'waTheme'                  => 'file/waTheme.class.php',

        'waLayout'                 => 'layout/waLayout.class.php',

        'waGettext'                => 'locale/waGettext.class.php',
        'waLocale'                 => 'locale/waLocale.class.php',
        'waLocaleAdapter'          => 'locale/waLocaleAdapter.class.php',

        'waAppPayment'             => 'payment/waAppPayment.class.php',
        'waOrder'                  => 'payment/waOrder.class.php',
        'waPayment'                => 'payment/waPayment.class.php',

        'waRequest'                => 'request/waRequest.class.php',
        'waRequestFile'            => 'request/waRequestFile.class.php',
        'waRequestFileIterator'    => 'request/waRequestFileIterator.class.php',

        'waResponse'               => 'response/waResponse.class.php',

        'waSessionStorage'         => 'storage/waSessionStorage.class.php',
        'waStorage'                => 'storage/waStorage.class.php',

        'waAuthUser'               => 'user/waAuthUser.class.php',
        'waUser'                   => 'user/waUser.class.php',

        'waArrayObject'            => 'util/waArrayObject.class.php',
        'waArrayObjectDiff'        => 'util/waArrayObjectDiff.class.php',
        'waLazyDisplay'            => 'util/waLazyDisplay.class.php',
        'waCSV'                    => 'util/waCSV.class.php',
        'waHtmlControl'            => 'util/waHtmlControl.class.php',
        'waString'                 => 'util/waString.class.php',
        'waUtils'                  => 'util/waUtils.class.php',

        'waEmailValidator'         => 'validator/waEmailValidator.class.php',
        'waRegexValidator'         => 'validator/waRegexValidator.class.php',
        'waStringValidator'        => 'validator/waStringValidator.class.php',
        'waUrlValidator'           => 'validator/waUrlValidator.class.php',
        'waValidator'              => 'validator/waValidator.class.php',

        'waIdna'                   => 'vendors/idna/waIdna.class.php',
        'Smarty'                   => 'vendors/smarty3/Smarty.class.php',

        'waSmarty3View'            => 'view/waSmarty3View.class.php',
        'waView'                   => 'view/waView.class.php',
        'waViewHelper'             => 'view/waViewHelper.class.php',
        'waAppViewHelper'          => 'view/waAppViewHelper.class.php',

        'waWorkflow'               => 'workflow/waWorkflow.class.php',
        'waWorkflowAction'         => 'workflow/waWorkflowAction.class.php',
        'waWorkflowEntity'         => 'workflow/waWorkflowEntity.class.php',
        'waWorkflowState'          => 'workflow/waWorkflowState.class.php',

        'waSystem'                 => 'waSystem.class.php',

        'waPageModel'              => 'page/models/waPage.model.php',
        'waPageParamsModel'        => 'page/models/waPageParams.model.php',
        'waPageAction'             => 'page/actions/waPage.action.php',
        'waPageActions'            => 'page/actions/waPage.actions.php',

        'waDesignActions'          => 'design/actions/waDesign.actions.php',
        'waPluginsActions'         => 'plugin/actions/waPlugins.actions.php',
    );
}
