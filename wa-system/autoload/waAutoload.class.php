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
                throw new Exception(sprintf('Not found file [%1$s] for class [%2$s]', $path, $class));
            }
            require $path;
            if (!class_exists($class, false) && !interface_exists($class, false)) {
               throw new Exception(sprintf('Not found class [%2$s] at file [%1$s]', $path, $class));
            }
        }
    }

    public function get($class)
    {
        if (isset($this->system_classes[$class])) {
            return $this->base_path.'/wa-system/'.$this->system_classes[$class];
        } elseif (substr($class, 0, 9) === 'waContact') {
            $result = $this->base_path.'/wa-system/contact/'.$class.'.class.php';
            if (is_readable($result)) {
                return $result;
            }
        } elseif (substr($class, 0, 4) === 'waDb') {
            return $this->base_path.'/wa-system/database/'.$class.'.class.php';
        } elseif (substr($class, 0, 2) == 'wa') {
            if (strpos($class, '.') !== false) return null;
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
        'waAPIController'          => 'api/waAPIController.class.php',
        'waAPIDecorator'           => 'api/waAPIDecorator.class.php',
        'waAPIDecoratorJSON'       => 'api/waAPIDecoratorJSON.class.php',
        'waAPIDecoratorXML'        => 'api/waAPIDecoratorXML.class.php',
        'waAPIException'           => 'api/waAPIException.class.php',
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

        'waAnalyticsConfig'        => 'config/waAnalyticsConfig.class.php',
        'waAppConfig'              => 'config/waAppConfig.class.php',
        'waConfig'                 => 'config/waConfig.class.php',
        'waRightConfig'            => 'config/waRightConfig.class.php',
        'waSystemConfig'           => 'config/waSystemConfig.class.php',

        'waContactsCollection'     => 'contact/waContactsCollection.class.php',

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

        'waDbAdapter'              => 'database/waDbAdapter.class.php',
        'waDbCacheIterator'        => 'database/waDbCacheIterator.class.php',
        'waDbConnector'            => 'database/waDbConnector.class.php',
        'waDbMysqlAdapter'         => 'database/waDbMysqlAdapter.class.php',
        'waDbMysqliAdapter'        => 'database/waDbMysqliAdapter.class.php',
        'waDbQuery'                => 'database/waDbQuery.class.php',
        'waDbQueryAnalyzer'        => 'database/waDbQueryAnalyzer.class.php',
        'waDbRecord'               => 'database/waDbRecord.class.php',
        'waDbResult'               => 'database/waDbResult.class.php',
        'waDbResultDelete'         => 'database/waDbResultDelete.class.php',
        'waDbResultInsert'         => 'database/waDbResultInsert.class.php',
        'waDbResultIterator'       => 'database/waDbResultIterator.class.php',
        'waDbResultSelect'         => 'database/waDbResultSelect.class.php',
        'waDbResultUpdate'         => 'database/waDbResultUpdate.class.php',
        'waDbStatement'            => 'database/waDbStatement.class.php',
        'waModel'                  => 'database/waModel.class.php',
        'waNestedSetModel'         => 'database/waNestedSetModel.class.php',

        'waSMS'                    => 'sms/waSMS.class.php',
        'waSMSAdapter'             => 'sms/waSMSAdapter.class.php',

        'waDateTime'               => 'datetime/waDateTime.class.php',

        'waEventHandler'           => 'event/waEventHandler.class.php',

        'waDbException'            => 'exception/waDbException.class.php',
        'waException'              => 'exception/waException.class.php',
        'waRightsException'        => 'exception/waRightsException.class.php',

        'waFiles'                  => 'file/waFiles.class.php',
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

        'waApiTokensModel'         => 'webasyst/lib/models/waApiTokens.model.php',
        'waApiAuthCodesModel'      => 'webasyst/lib/models/waApiAuthCodes.model.php',
        'waAppSettingsModel'       => 'webasyst/lib/models/waAppSettings.model.php',
        'waAnnouncementModel'      => 'webasyst/lib/models/waAnnouncement.model.php',
        'waContactModel'           => 'webasyst/lib/models/waContact.model.php',
        'waContactCategoriesModel' => 'webasyst/lib/models/waContactCategories.model.php',
        'waContactCategoryModel'   => 'webasyst/lib/models/waContactCategory.model.php',
        'waContactDataModel'       => 'webasyst/lib/models/waContactData.model.php',
        'waContactDataTextModel'   => 'webasyst/lib/models/waContactDataText.model.php',
        'waContactEmailsModel'     => 'webasyst/lib/models/waContactEmails.model.php',
        'waContactRightsModel'     => 'webasyst/lib/models/waContactRights.model.php',
        'waContactSettingsModel'   => 'webasyst/lib/models/waContactSettings.model.php',
        'waContactFieldValuesModel' => 'webasyst/lib/models/waContactFieldValues.model.php',
        'waCountryModel'           => 'webasyst/lib/models/waCountry.model.php',
        'waGroupModel'             => 'webasyst/lib/models/waGroup.model.php',
        'waLogModel'          => 'webasyst/lib/models/waLog.model.php',
        'waLoginLogModel'          => 'webasyst/lib/models/waLoginLog.model.php',
        'waUserGroupsModel'        => 'webasyst/lib/models/waUserGroups.model.php',
        'waRegionModel'            => 'webasyst/lib/models/waRegion.model.php',
        'waTransactionModel'       => 'webasyst/lib/models/waTransaction.model.php',
        'waTransactionDataModel'   => 'webasyst/lib/models/waTransactionData.model.php',

        'waPageModel'              => 'page/models/waPage.model.php',
        'waPageParamsModel'        => 'page/models/waPageParams.model.php',
        'waPageAction'             => 'page/actions/waPage.action.php',
        'waPageActions'            => 'page/actions/waPage.actions.php',

        'waDesignActions'          => 'design/actions/waDesign.actions.php',
    );
}
