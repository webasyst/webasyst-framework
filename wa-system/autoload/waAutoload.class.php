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
            throw new waException(sprintf('Unable to register %s::autoload as an autoloading method.', get_class(self::getInstance())));
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
            require $path;
            return true;
        }
        return false;
    }

    public function get($class)
    {
        $original_class = $class;
        $class = strtolower($class);
        if (isset($this->system_classes[$class])) {
            return $this->base_path.'/wa-system/'.$this->system_classes[$class];
        } elseif (substr($class, 0, 2) === 'wa') {
            $dir = preg_replace("/^wai?([A-Z][a-z]+).*?$/", "$1", $original_class);
            if ($dir == 'Db') {
                $dir = 'database';
            }
            $path = $this->base_path.'/wa-system/'.strtolower($dir).'/'.$original_class.'.'.(substr($original_class, 0, 3) === 'wai' ? 'interface' : 'class').'.php';
            if (file_exists($path)) {
                return $path;
            } else {
                $dir = preg_replace("/^wa.*?([A-Z][a-z]+)$/", "$1", $original_class);
                $path = $this->base_path.'/wa-system/'.strtolower($dir).'/'.$original_class.'.class.php';
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
                $this->classes[strtolower($class_name)] = $path;
            }
        } else {
            $this->classes[strtolower($class)] = $path;
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
        'waapicontroller' => 'api/waApiController.class.php',
        'waapimethod' => 'api/waApiMethod.class.php',
        'waapidecorator' => 'api/waApiDecorator.class.php',
        'waapidecoratorjson' => 'api/waApiDecoratorJSON.class.php',
        'waapidecoratorxml' => 'api/waApiDecoratorXML.class.php',
        'waapiexception' => 'api/waApiException.class.php',
        'waautoload' => 'autoload/waAutoload.class.php',
        'wacontactscollection' => 'contact/waContactsCollection.class.php',
        'waconfig' => 'config/waConfig.class.php',
        'waappconfig' => 'config/waAppConfig.class.php',
        'warightconfig' => 'config/waRightConfig.class.php',
        'waanalyticsconfig' => 'config/waAnalyticsConfig.class.php',
        'wasystemconfig' => 'config/waSystemConfig.class.php',
        'wasystem' => 'waSystem.class.php',
        'waexception' => 'exception/waException.class.php',
        'warightsexception' => 'exception/waRightsException.class.php',
        'wacontroller' => 'controller/waController.class.php',
        'wafrontcontroller' => 'controller/waFrontController.class.php',
        'wadefaultcontroller' => 'controller/waDefaultController.class.php',
        'waviewactions' => 'controller/waViewActions.class.php',
        'wajsonactions' => 'controller/waJsonActions.class.php',
        'waviewcontroller' => 'controller/waViewController.class.php',
        'wajsoncontroller' => 'controller/waJsonController.class.php',
        'waicontroller' => 'controller/waiController.interface.php',
        'waviewaction' => 'controller/waViewAction.class.php',
        'wawidget' => 'controller/waWidget.class.php',
        'wafiles' => 'file/waFiles.class.php',
        'wamodel' => 'database/waModel.class.php',
        'wadbexception' => 'exception/waDbException.class.php',
        'wadbconnector' => 'database/waDbConnector.class.php',
        'wadbqueryanalyzer' => 'database/waDbQueryAnalyzer.class.php',
        'wadbresultselect' => 'database/waDbResultSelect.class.php',
        'wadbresultinsert' => 'database/waDbResultInsert.class.php',
        'wadbresultupdate' => 'database/waDbResultUpdate.class.php',
        'wadbresultdelete' => 'database/waDbResultDelete.class.php',
        'wadbresult' => 'database/waDbResult.class.php',
        'wadbresultiterator' => 'database/waDbResultIterator.class.php',
        'wadbstatement' => 'database/waDbStatement.class.php',
        'wadbrecord' => 'database/waDbRecord.class.php',
        'wanestedsetmodel' => 'database/waNestedSetModel.class.php',
        'warequest' => 'request/waRequest.class.php',
        'waresponse' => 'response/waResponse.class.php',
        'wastorage' => 'storage/waStorage.class.php',
        'wasessionstorage' => 'storage/waSessionStorage.class.php',
        'wauser' => 'user/waUser.class.php',
        'waauthuser' => 'user/waAuthUser.class.php',
        'waauth' => 'auth/waAuth.class.php',
        'waauthadapter' => 'auth/waAuthAdapter.class.php',
        'wacookieauthadapter' => 'auth/waCookieAuthAdapter.class.php',
        'waiauthadapter' => 'auth/waiAuthAdapter.interface.php',
        'walocale' => 'locale/waLocale.class.php',
        'wagettext' => 'locale/waGettext.class.php',
        'walayout' => 'layout/waLayout.class.php',
        'wautils' => 'util/waUtils.class.php',
        'waarrayobject' => 'util/waArrayObject.class.php',
        'waarrayobjectdiff' => 'util/waArrayObjectDiff.class.php',
        'wacsv' => 'util/waCSV.class.php',
        'waworkflow' => 'workflow/waWorkflow.class.php',
        'waworkflowaction' => 'workflow/waWorkflowAction.class.php',
        'waworkflowstate' => 'workflow/waWorkflowState.class.php',
        'wadatetime' => 'datetime/waDateTime.class.php',
        'waemailmessage' => 'message/waEmailMessage.class.php',
        'waeventhandler' => 'event/waEventHandler.class.php',
        'wacurrency' => 'currency/waCurrency.class.php',
        'wawidgets' => 'widget/waWidgets.class.php',
        'wapayment' => 'payment/waPayment.class.php',
        'waapppayment' => 'payment/waAppPayment.class.php',
        'warequestfile' => 'request/waRequestFile.class.php',
        'warequestfileiterator' => 'request/waRequestFileIterator.class.php',
        'walongactioncontroller' => 'controller/waLongActionController.class.php',
        'waaction' => 'controller/waAction.class.php',
        'waloginaction' => 'controller/waLoginAction.class.php',
        'wahtmlcontrol' => 'util/waHtmlControl.class.php',
        'walocalizedcollection' => 'util/waLocalizedCollection.php',

        'smarty' => 'vendors/smarty3/Smarty.class.php',
        'waidna' => 'vendors/idna/waIdna.class.php',

        'wacontactmodel' => 'webasyst/lib/models/waContact.model.php',
        'wacontactdatamodel' => 'webasyst/lib/models/waContactData.model.php',
        'wacontactdatatextmodel' => 'webasyst/lib/models/waContactDataText.model.php',
        'wacontactemailsmodel' => 'webasyst/lib/models/waContactEmails.model.php',
        'wacontactrightsmodel' => 'webasyst/lib/models/waContactRights.model.php',
    	'wacontactsettingsmodel' => 'webasyst/lib/models/waContactSettings.model.php',
        'wacontactcategorymodel' => 'webasyst/lib/models/waContactCategory.model.php',
    	'wacontactcategoriesmodel' => 'webasyst/lib/models/waContactCategories.model.php',
        'wausergroupsmodel' => 'webasyst/lib/models/waUserGroups.model.php',
    	'wagroupmodel' => 'webasyst/lib/models/waGroup.model.php',

        'waloginlogmodel' => 'webasyst/lib/models/waLoginLog.model.php',
        'waappsettingsmodel' => 'webasyst/lib/models/waAppSettings.model.php'
    );
}
