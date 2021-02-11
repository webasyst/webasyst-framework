<?php
/**
 * App plugin may extend this class to make a view helper available in all templates.
 * For example, a `debug` plugin in `shop` app may create a file
 *   shopDebugPluginViewHelper class.php
 * that defines a class
 *   shopDebugPluginViewHelper extends waPluginViewHelper
 * Then in all templates a variable will become available
 *   {$wa->shop->debugPlugin}
 * that will be an instance of class shopDebugPluginViewHelper.
 *
 * Recommended syntax in templates to call plugins is therefore:
 *   {$wa->shop->debugPlugin->whatever()}
 *
 * This has an advantage over static function call: it will not break
 * even in case when plugin is removed from the system. Call will safely return
 * an empty string instead of a fatal error.
 *
 * Additionally, a safe check can be made whether a plugin exists:
 *   {if $wa->shop->debugPlugin->version()} ... {/if}
 * ->version() will return empty string if plugin named `debug`
 * is not installed or is disabled.
 *
 * @since 1.14.11
 */
class waPluginViewHelper
{
    protected $plugin_id;

    /** @var waPlugin main plugin class */
    protected $plugin;

    /**
     * waPluginViewHelper constructor
     *
     * @param null $plugin
     * @param string $plugin_id
     */
    public function __construct($plugin = null, $plugin_id = '')
    {
        $this->plugin    = $plugin;
        $this->plugin_id = $plugin_id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '';
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if (SystemConfig::isDebug() === true) {
            waLog::log(sprintf(
                _ws('%s: call of unknown method %s->%s() with arguments %s'),
                $this->plugin_id,
                get_class($this),
                $name,
                wa_dump_helper($arguments)
            ));
        }
    }

    /**
     * @param $name
     */
    public function __get($name)
    {
        if (SystemConfig::isDebug() === true) {
            waLog::log(sprintf(
                _ws('%s: attempt to read unknown property %s->%s'),
                $this->plugin_id,
                get_class($this),
                $name
            ));
        }
    }

    /**
     * Returns plugin version, if plugin is installed and enabled. Otherwise an empty string.
     *
     * @return string
     */
    public function version()
    {
        if ($this->plugin) {
            return $this->plugin->getVersion();
        }

        return '';
    }

    /**
     * @return waPlugin
     * @throws waException
     */
    protected function plugin()
    {
        if ($this->plugin) {
            return $this->plugin;
        }
        throw new waException(sprintf(_ws('Plugin “%s” not found.'), $this->plugin_id));
    }
}
