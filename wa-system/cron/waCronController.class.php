<?php

require_once realpath(dirname(__FILE__).'/../').'/vendors/cron-expression/cron_init.php';

class waCronController
{
    const DEFAULT_EXECUTION_TIMEOUT = 60;

    protected static $app_settings_model;
    protected static $LOG_FILE = 'cron.log';

    protected $app_id;
    protected $alias;
    protected $action;
    protected $plugin;
    protected $params;
    protected $timeout;
    protected $cron_expression;
    protected $is_cli_context;

    public function __construct($app_id, $alias, $is_cli_context = false)
    {
        $this->is_cli_context = $is_cli_context;

        if (!waSystem::getInstance()->appExists($app_id)) {
            $this->resumeError('app_not_installed', 'App is not installed', 404, [
                'app' => $app_id
            ]);
        }

        $cron_config = wa($app_id)->getConfig()->getCron();
        if (!isset($cron_config[$alias])) {
            $this->resumeError('action_not_found', 'Action not found', 404, [
                'app' => $app_id,
                'action' => $alias,
            ]);
        }

        if (!$this->is_cli_context) {
            // do not check cron expression on cli context
            $expression = $cron_config[$alias]['expression'];
            if (!self::isValidExpression($expression)) {
                $this->resumeError('invalid_schedule', 'Action schedule is not valid', 404, [
                    'app' => $app_id,
                    'action' => $alias,
                    'cron_expression' => $expression,
                ]);
            }
            $this->cron_expression = $expression;
        }

        $this->app_id = $app_id;
        $this->alias = $alias;
        $this->action = ifset($cron_config[$alias]['action'], $alias);
        $this->plugin = ifset($cron_config[$alias]['plugin'], '');
        $this->params = ifset($cron_config[$alias]['params'], []);
        $this->timeout = ifset($cron_config[$alias]['timeout'], self::DEFAULT_EXECUTION_TIMEOUT);
    }

    public function execute()
    {
        $this->lock();

        if (!$this->is_cli_context) {
            // do not check cron scheduled on cli context
            $last_call_ts = self::lastRunTs($this->app_id, $this->alias);
            if (!empty($last_call_ts) && self::nextRunAt($this->cron_expression, $last_call_ts) > time() + 10) {
                // do nothing if the next scheduled run is in the future (to prevent DDOS attack)
                wa()->getResponse()->setStatus(204);
                wa()->getResponse()->sendHeaders();
                return;
            }
        }

        waSystem::getInstance($this->app_id, null, true);

        $class_name = $this->app_id.(empty($this->plugin) ? '' : ucfirst($this->plugin).'Plugin').ucfirst($this->action).'Cron';
        if (!class_exists($class_name)) {
            $this->resumeError('class_not_found', 'Action class not found', 404, [
                'app' => $this->app_id,
                'action' => $this->action,
            ]);
        }

        self::setRunTs($this->app_id, $this->alias);

        if (!$this->is_cli_context) {
            ignore_user_abort(true);
            set_time_limit($this->timeout);
            if ($this->timeout > 60) {
                ob_implicit_flush();
                echo "CRON long action start\n";
            }
        }

        try {
            $action_class = new $class_name();
            $action_class->execute($this->params);
            if ($this->is_cli_context || (!empty($this->timeout) && $this->timeout > 60)) {
                echo "\nCRON long action end\n";
            }
        } catch (Exception $e) {
            $this->resumeError($e->getCode(), $e->getMessage(), 500, [
                'app' => $this->app_id,
                'action' => $this->action,
            ]);
        }
    }

    public static function isValidExpression($cron_expression)
    {
        if (empty($cron_expression) || !Cron\CronExpression::isValidExpression($cron_expression)) {
            return false;
        }

        try {
            Cron\CronExpression::factory($cron_expression)->getNextRunDate();
        } catch (OutOfRangeException $ex) {
            return false;
        }

        return true;
    }

    public static function nextRunAt($cron_expression, $ts, $skip = 0)
    {
        if (!self::isValidExpression($cron_expression)) {
            return null;
        }

        $cron = Cron\CronExpression::factory($cron_expression);
        return $cron->getNextRunDate((new DateTime)->setTimestamp($ts), $skip)->getTimestamp();
    }

    public static function lastRunTs($app_id, $alias)
    {
        $res = self::getAppSettingsModel()->getByField([ 'app_id' => $app_id, 'name' => 'cron_ts:'.$alias ]);
        return ifset($res, 'value', null);
    }

    protected static function setRunTs($app_id, $alias)
    {
        return self::getAppSettingsModel()->set($app_id, 'cron_ts:'.$alias, time());
    }

    protected function lock()
    {
        $lock_name = 'wa/cron/'.$this->app_id.'/'.$this->alias;
        $is_lock_free = self::getAppSettingsModel()->query("SELECT IS_FREE_LOCK(?)", [$lock_name])->fetchField();
        if (empty($is_lock_free)) {
            $this->resumeError('job_already_run', _ws('A job is running now.'), 429, [
                'app' => $this->app_id,
                'alias' => $this->alias,
            ]);
        }

        try {
            self::getAppSettingsModel()->exec("SELECT GET_LOCK(?, -1)", [$lock_name]);
        } catch (Exception $e) {
            $this->resumeError('job_lock_fail', $e->getMessage(), 500, [
                'app' => $this->app_id,
                'alias' => $this->alias,
            ]);
        }
    }

/*
    protected function response($response, $code = null)
    {
        $response = empty($response) ? '' : waAPIDecorator::factory('JSON')->decorate($response);
        if ($this->is_cli_context) {
            echo "SUCCESS: {$response}\n";
            // do nothing in cli context
            return;
        }
        if (empty($code) && empty($response)) {
            $code = 204;
        }
        if (!empty($code)) {
            wa()->getResponse()->setStatus($code);
        }
        if ($code == 204) {
            wa()->getResponse()->sendHeaders();
            return;
        }
        wa()->getResponse()->addHeader('Content-type', 'application/json; charset=utf-8');
        wa()->getResponse()->sendHeaders();
        echo $response;
    } */

    protected static function getAppSettingsModel()
    {
        if (empty(self::$app_settings_model)) {
            self::$app_settings_model = new waAppSettingsModel();
        }
        return self::$app_settings_model;
    }

    protected function resumeError($error, $error_description = null, $status_code = null, $details = [])
    {
        if ($this->is_cli_context || (!empty($this->timeout) && $this->timeout > 60)) {
            waLog::dump([
                'error' => $error,
                'error_description' => $error_description,
                'details' => $details,
            ], self::$LOG_FILE);
            echo "\nERROR {$error}: {$error_description}\n";
            exit;
        }

        throw new waAPIException($error, $error_description, $status_code, $details);
    }
}
