<?php

/**
 * @property-read int    $call_limit If the execution time (in seconds) is less - the result will not be cached.
 * @property-read string $namespace  Namespace, which will be passed to waVarExportCache as app_id. The default id of the current app.
 * @property-read int    $ttl        The maximum lifetime of the cache, which is considered relevant.
 * @property-read bool   $hard_clean If you are not sure that the recorded cache will clear itself - use this flag.
 *                                   In this case, the Framework will definitely remove it when the $this->ttl time expires.
 * @property-read string $hash_salt  The salt that will be used to generate the hash. This could be the user locale or something else.
 */
class waFunctionCache
{
    protected $function;
    protected $options = array(
        'call_limit' => 1,
        'namespace'  => null,
        'ttl'        => 1800, // 30 mins
        'hard_clean' => false,
        'hash_salt'  => '',
    );

    /**
     * @var bool $disable_cache
     * Debug helper. Set to true to make all ->call()s ignore cache and never write there
     */
    public $disable_cache = false;

    /**
     * @var string $last_call_cache_status
     * Debug helper.
     * after ->call() this is set to one of: 'from_cache', 'just_cached',
     * 'not_cached' (when call_limit option is not met), or 'error' (when callable throws exception)
     */
    public $last_call_cache_status = false;

    /**
     * @param callable $function
     * @param array $options
     * @throws waException
     */
    public function __construct($function, $options = array())
    {
        if (!is_callable($function)) {
            throw new waException('Function or method not exists');
        }

        $this->function = $function;

        if (!empty($options) && is_array($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $this->disable_cache = defined('WA_DISABLE_FUNCTIONS_CACHE');
    }

    public function __get($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    public function __isset($option)
    {
        return isset($this->options[$option]);
    }

    public function call()
    {
        $args = func_get_args();

        if ($this->disable_cache) {
            $this->last_call_cache_status = 'not_cached';
            return call_user_func_array($this->function, $args);
        }

        $hash_data = var_export(array($this->function, $args), true).$this->hash_salt;
        $hash = md5($hash_data);
        //waLog::log($hash_data, 'wa_fun_cache/'.$hash.'.log');
        unset($hash_data);

        $app_id = $this->namespace ? $this->namespace : wa()->getApp();
        $cache = new waVarExportCache('fn_'.$hash, $this->ttl, $app_id, $this->hard_clean);
        $result = $cache->get();
        if (!$result) {
            $t = microtime(true);
            $this->last_call_cache_status = 'error';
            $result = call_user_func_array($this->function, $args);
            if ((microtime(true) - $t) > $this->call_limit) {
                $cache->set($result);
                $this->last_call_cache_status = 'just_cached';
            } else {
                $this->last_call_cache_status = 'not_cached';
            }
        } else {
            $this->last_call_cache_status = 'from_cache';
        }

        return $result;
    }
}