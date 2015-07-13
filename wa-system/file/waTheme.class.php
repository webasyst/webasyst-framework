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
 * @subpackage files
 */

/**
 * Theme design helper
 * @property string $name
 * @property string $description
 * @property string $about
 * @property string $version
 * @property int $edition Incremental counter of theme changes
 * @property-read string $id
 * @property-read string $slug
 * @property-read string $vendor
 * @property-read string $author
 * @property-read string $app_id
 * @property-read string $cover
 * @property-read string $path
 * @property-read string $path_custom
 * @property-read string $custom
 * @property-read string $path_original
 * @property-read string $original
 * @property-read string $type
 * @property-read string $url Theme directory URL
 * @property-read string $parent_theme_id Parent theme ID
 * @property-read string $source_theme_id Source theme ID for duplicated one
 * @property-read waTheme $parent_theme Parent theme instance or false
 * @property-read array $used theme settlement URLs
 * @property-read bool $system
 * @property-read string[] $thumbs
 */
class waTheme implements ArrayAccess
{
    /**
     * Original not modified theme
     * @var string
     */
    const ORIGINAL = 'original';

    /**
     * User theme
     * @var string
     */
    const CUSTOM = 'custom';

    /**
     * Overridden theme
     * @var string
     */
    const OVERRIDDEN = 'overridden';

    /**
     *
     * Undefined theme type
     * @var string
     */
    const NONE = 'none';
    const PATH = 'theme.xml';

    protected $app;
    protected $id;
    protected $info;
    protected $extra_info;
    protected $path_original;
    protected $path_custom;
    protected $path;
    protected $type;
    protected $url;
    protected $used;
    protected $system;
    protected $_version;

    /**
     * @var array
     */
    protected $settings;
    /**
     *
     * @var waTheme
     */
    protected $parent_theme;
    private $changed = array();
    private $readonly = false;

    /**
     * Get theme instance
     * @param $id
     * @param bool|string $app_id
     * @param bool|string $force true to create new custom theme or self::ORIGINAL to get original theme instance
     * @param bool $readonly
     */
    public function __construct($id, $app_id = true, $force = false, $readonly = false)
    {
        $this->readonly = $readonly;
        if (strpos($id, ':') !== false) {
            list($app_id, $id) = explode(':', $id, 2);
        }

        self::verify($id);
        $this->id = $id;
        $this->app = ($app_id === true || !$app_id) ? wa()->getApp() : $app_id;
        $this->initPath($force);
    }

    /**
     *
     * @param string $id Theme id
     * @param bool|string $app_id Application id
     * @param boolean $check_only_path skip verify theme description
     * @return boolean
     */
    public static function exists($id, $app_id = true, $check_only_path = false)
    {
        if (strpos($id, ':') !== false) {
            list($app_id, $id) = explode(':', $id, 2);
        }
        $app_id = ($app_id === true || !$app_id) ? wa()->getApp() : $app_id;
        self::verify($id);
        $path_custom = wa()->getDataPath('themes/', true, $app_id).$id;
        $path_original = wa()->getAppPath('themes/', $app_id).$id;
        if (!file_exists($path_custom) || (!$check_only_path && !file_exists($path_custom.'/'.self::PATH))) {
            $path_custom = false;
        }

        if (!file_exists($path_original) || (!$check_only_path && !file_exists($path_original.'/'.self::PATH))) {
            $path_original = false;
        }
        return ($path_custom || $path_original) ? true : false;
    }

    private function initPath($force = false)
    {
        /**
         * $this->info = null;
         * $this->extra_info = array();
         * $this->url = null;
         */

        $this->path_custom = wa()->getDataPath('themes/', true, $this->app).$this->id;
        $this->path_original = wa()->getAppPath('themes/', $this->app).$this->id;

        if (!file_exists($this->path_custom) || (!$force && !file_exists($this->path_custom.'/'.self::PATH))) {
            $this->path_custom = false;
        }

        if (!file_exists($this->path_original) || (!$force && !file_exists($this->path_original.'/'.self::PATH))) {
            $this->path_original = false;
        }

        if ($force === self::ORIGINAL) {
            $this->readonly = true;
            $this->path_custom = false;
        }

        if ($this->path_custom && $this->path_original) {
            $this->type = self::OVERRIDDEN;
            $this->path = $this->path_custom;
        } elseif ($this->path_custom) {
            $this->type = self::CUSTOM;
            $this->path = $this->path_custom;
        } elseif ($this->path_original) {
            $this->type = self::ORIGINAL;
            $this->path = $this->path_original;
        } else {
            $this->type = self::NONE;
            $this->path = false;
            //theme not found
        }

        if (!$force && !in_array($force, array(self::ORIGINAL), true)) {
            $this->check();
        }
    }

    private function init($param = null)
    {
        if (empty($this->info)) {
            $path = $this->path.'/'.self::PATH;
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            switch ($extension) {
                case 'xml':
                    $locale = self::getLocale();

                    $this->info = array(
                        'name'            => array($locale => $this->id),
                        'files'           => array(),
                        'settings'        => array(),
                        'parent_theme_id' => '',
                        'version'         => '',
                        'edition'         => 0,
                        'source_theme_id' => '',
                    );
                    if (!$xml = $this->getXML()) {
                        trigger_error("Invalid theme description {$path}", E_USER_WARNING);
                        break;
                    }
                    /**
                     * @var SimpleXMLElement $xml
                     */
                    $ml_fields = array('name', 'description', 'about');

                    foreach ($ml_fields as $field) {
                        $this->info[$field] = array();
                    }
                    foreach ($xml->attributes() as $field => $value) {
                        $this->info[(string)$field] = (string)$value;
                    }

                    $this->info['edition'] = (int)$this->info['edition'];
                    $this->info['system'] = isset($this->info['system']) ? (bool)$this->info['system'] : false;

                    foreach ($ml_fields as $field) {
                        if ($xml->{$field}) {
                            foreach ($xml->{$field} as $value) {
                                if ($value && ($locale = (string)$value['locale'])) {
                                    $this->info[$field][$locale] = (string)$value;
                                }
                            }
                        } elseif ($field == 'name') {
                            $locale = self::getLocale();
                            $this->info[$field][$locale] = $this->id;
                        }
                    }
                    if (!empty($this->info['parent_theme_id'])) {
                        $parent_exists = self::exists($this->info['parent_theme_id'], $this->app);
                    } else {
                        $parent_exists = false;
                    }

                    $this->info['files'] = array();
                    if ($files = $xml->{'files'}) {
                        /**
                         * @var SimpleXMLElement $files
                         */
                        foreach ($files->children() as $file) {
                            $path = (string)$file['path'];
                            if (in_array(pathinfo($path, PATHINFO_EXTENSION), array('js', 'html', 'css'))) {
                                $this->info['files'][$path] = array(
                                    'custom' => isset($file['custom']) && (string)$file['custom'] ? true : false,
                                );
                                if (isset($file['modified']) && (string)$file['modified']) {
                                    $this->info['files'][$path]['modified'] = true;
                                }

                                $this->info['files'][$path]['parent'] = isset($file['parent']) && (string)$file['parent'] ? true : false;
                                if ($this->info['files'][$path]['parent']) {
                                    $this->info['files'][$path]['parent_exists'] = $parent_exists;
                                }
                                foreach ($file->{'description'} as $value) {
                                    if ($value && ($locale = (string)$value['locale'])) {
                                        $this->info['files'][$path]['description'][$locale] = (string)$value;
                                    }
                                }
                            }
                        }
                        ksort($this->info['files']);
                    }
                    $this->info['settings'] = array();
                    if ($settings = $xml->{'settings'}) {
                        /**
                         * @var SimpleXMLElement $settings
                         */
                        foreach ($settings->children() as $setting) {
                            /**
                             * @var SimpleXMLElement $setting
                             */
                            $var = (string)$setting['var'];
                            $this->info['settings'][$var] = array(
                                'control_type' => isset($setting['control_type']) ? (string)$setting['control_type'] : 'text',
                                'value'        => (string)$setting->{'value'},
                            );
                            $s = &$this->info['settings'][$var];
                            foreach ($setting->{'value'} as $value) {
                                if ($value && ($locale = (string)$value['locale'])) {
                                    if (!is_array($s['value'])) {
                                        $s['value'] = array();
                                    }
                                    $s['value'][$locale] = (string)$value;
                                }
                            }

                            if ($setting->{'filename'}) {
                                $s['filename'] = (string)$setting->{'filename'};
                            }

                            foreach ($setting->{'name'} as $value) {
                                if ($value && ($locale = (string)$value['locale'])) {
                                    $s['name'][$locale] = (string)$value;
                                }
                            }

                            foreach ($setting->{'description'} as $value) {
                                if ($value && ($locale = (string)$value['locale'])) {
                                    $s['description'][$locale] = (string)$value;
                                }
                            }

                            if ($setting->{'options'}) {
                                $this->info['settings'][$var]['options'] = array();
                                foreach ($setting->{'options'}->children() as $option) {
                                    $s['options'][(string)$option['value']] = array();
                                    foreach ($option->{'name'} as $value) {
                                        if ($value && ($locale = (string)$value['locale'])) {
                                            $s['options'][(string)$option['value']]['name'][$locale] = (string)$value;
                                        }
                                    }
                                    if ($option->{'description'}) {
                                        foreach ($option->{'description'} as $value) {
                                            if ($value && ($locale = (string)$value['locale'])) {
                                                $s['options'][(string)$option['value']]['description'][$locale] = (string)$value;
                                            }
                                        }
                                    }
                                }
                            }
                            unset($s);
                        }
                    }

                    $this->info['thumbs'] = array();
                    if ($thumbs = $xml->{'thumbs'}) {
                        /**
                         * @var SimpleXMLElement $thumbs
                         */
                        foreach ($thumbs->children() as $thumb) {
                            $var = (string)$thumb;
                            if ($var) {
                                $this->info['thumbs'][] = $var;
                            }
                        }
                    }

                    $this->info['locales'] = array();
                    if ($locales = $xml->{'locales'}) {
                        /**
                         * @var SimpleXMLElement $locales
                         */
                        foreach ($locales->children() as $locale) {
                            $msgid = (string)$locale->{'msgid'};
                            foreach ($locale->{'msgstr'} as $msgstr) {
                                $this->info['locales'][$msgid][(string)$msgstr['locale']] = (string)$msgstr;
                            }
                        }
                    }
                    break;
                case 'php':
                    //deprecated
                    if (file_exists($path)) {
                        $this->info = include($path);
                    }
                    break;
                default:
                    $this->info = array();
                    break;
            }
        }
        return ($param === null) ? true : isset($this->info[$param]);


        //TODO check info and construct params
    }

    /**
     * Append file into theme
     *
     * @param string $path
     * @param array|string $description
     * @param array $options
     * @throws waException
     * @return waTheme
     */
    public function addFile($path, $description = null, $options = array())
    {
        if ($description) {
            $options['description'] = $description;
        }
        if (!in_array(pathinfo($path, PATHINFO_EXTENSION), array('css', 'js', 'html'))) {
            throw new waException("Unexpected file extension");
        }
        $options['custom'] = 1;
        $options['modified'] = 1;
        $this->setFiles(array($path => $options));
        return $this;
    }

    /**
     *
     * Remove file from theme
     * @param string $path
     * @throws waException
     * @return waTheme
     */
    public function removeFile($path)
    {
        if (preg_match('@(^|[\\/])..[\\/]@', $path)) {
            throw new waException("Invalid theme's file path");
        }
        $this->init();
        if (isset($this->info['files'][$path])) {
            if (empty($this->info['files'][$path]['custom']) || !$this->info['files'][$path]['custom']) {
                throw new waException("Theme's required files can not be deleted");
            }
            unset($this->info['files'][$path]);
            if (!isset($this->changed['files'])) {
                $this->changed['files'] = array();
            }
            $this->changed['files'][$path] = true;
            waFiles::delete($this->path."/".$path);
        }
        return $this;
    }

    public function changeFile($file, $description)
    {
        $this->init();
        $this->setFiles(array($file => array('modified' => true)));
        if (!isset($this->info['files'][$file]) || !$this->info['files'][$file]['custom']) {
            return $this->save();
        }
        if (is_array($description)) {
            if ($this->info['files'][$file]['description'] == $description) {
                return $this->save();
            }
        } else {
            if (self::prepareField($this->info['files'][$file]['description']) == $description) {
                return $this->save();
            }
        }
        $this->setFiles(array($file => array('description' => $description)));
        return $this->save();
    }

    /**
     * @param bool $as_dom
     * @throws waException
     * @return DOMDocument|SimpleXMLElement
     */
    private function getXML($as_dom = false)
    {

        if ($as_dom && !class_exists('DOMDocument')) {
            throw new waException('PHP extension DOM required');
        }
        $path = $this->path.'/'.self::PATH;
        if (file_exists($path) && filesize($path)) {
            if ($as_dom) {
                $xml = new DOMDocument(1.0, 'UTF-8');
                $xml->preserveWhiteSpace = false;
                $xml->formatOutput = true;
                $xml->load($path);
                $xml->preserveWhiteSpace = false;
                $xml->formatOutput = true;
            } else {
                $xml = @simplexml_load_file($path, null, LIBXML_NOCDATA);
            }
        } else {
            $data = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE theme PUBLIC "wa-app-theme" "http://www.webasyst.com/wa-content/xml/wa-app-theme.dtd" >
<theme id="{$this->id}" system="0" vendor="unknown" author="unknown" app="{$this->app}" version="1.0.0" parent_theme_id="">
    <name locale="en_US">{$this->id}</name>
    <description locale="en_US">There's no description</description>
    <files></files>
    <about locale="en_US">There's no about</about>
</theme>
XML;
            if ($as_dom) {
                $xml = new DOMDocument(1.0, 'UTF-8');
                $xml->preserveWhiteSpace = false;
                $xml->formatOutput = true;
                $xml->loadXML($data);
                $xml->preserveWhiteSpace = false;
                $xml->formatOutput = true;
            } else {
                $xml = @simplexml_load_string($data, null, LIBXML_NOCDATA);
            }
        }
        return $xml;
    }

    /**
     * @param DOMDocument $dom
     * @param DOMXPath $xpath
     * @param DOMNode $parent
     * @param string $name
     * @param string $value
     * @return DOMElement
     */
    private function &addNode($dom, $xpath, $parent, $name, $value = '')
    {
        $path = explode('/', rtrim($name, '/'));
        $name = end($path);

        $ref_node = null;
        $context = $parent->getNodePath();
        switch ($context) {
            case '/theme':
                $before = array(
                    'name',
                    'description',
                    'files',
                    'about',
                    //'requirements',
                    'settings',
                    'thumbs',
                    'locales',
                );
                break;
            case '/theme/settings/setting':
                $before = array(
                    'value',
                    'filename',
                    'name',
                    'description',
                    'options',
                );
                break;
            case '/theme/settings/setting/options/option':
                $before = array(
                    'name',
                    'description',
                );
                break;
            default:
                $before = array();
                break;
        }

        if (!empty($before)) {
            do {
                ;
            } while ($before && ($name != array_shift($before)));
            #find next element
            do {
                if ($query = array_shift($before)) {

                    if (empty($xpath)) {
                        $xpath = new DOMXPath($dom);
                    }
                    $query = $context.'/'.$query;

                    if (($result = $xpath->query($query)) && $result->length) {
                        $ref_node = $result->item(0);
                    }
                }
            } while (!$ref_node && $before);
        }

        $element = $dom->createElement($name, $value);
        if ($ref_node) {
            $element = $parent->insertBefore($element, $ref_node);
        } else {
            $element = $parent->appendChild($element);
        }
        return $element;
    }

    /**
     * @param DOMDocument $dom
     * @param DOMXPath $xpath
     * @param DOMElement $context
     * @param string $field
     * @param string|string[string] $value
     */
    private function addLocalizedField($dom, $xpath, $context, $field, $value)
    {

        if (is_array($value)) {
            foreach ($value as $locale => $_value) {
                $query = "{$field}[@locale='{$locale}']";
                if ($node = $xpath->query($query, $context)->item(0)) {
                    if ($_value === null) {
                        $context->removeChild($node);
                    } else {
                        $node->nodeValue = $_value;
                    }

                } else {
                    $node = $this->addNode($dom, $xpath, $context, $field, $_value);
                    $node->setAttribute('locale', $locale);
                }

            }
        } else {
            if ($field !== 'value') {
                $value = array(self::getLocale() => $value);
                $this->addLocalizedField($dom, $xpath, $context, $field, $value);
            } else {
                $query = "{$field}";
                if ($node = $xpath->query($query, $context)->item(0)) {
                    $node->nodeValue = $value;
                } else {
                    $this->addNode($dom, $xpath, $context, $field, $value);
                }
            }
        }
    }

    /**
     * @param DOMDocument $dom
     * @param DOMXPath $xpath
     * @param DOMElement $setting
     * @param array $info
     */
    private function updateSetting($dom, $xpath, $setting, $info)
    {
        $setting->setAttribute('control_type', ifset($info['control_type'], 'text'));

        if (!empty($info['filename'])) {
            $this->addNode($dom, $xpath, $setting, 'filename', $info['filename']);
        }

        $this->addLocalizedField($dom, $xpath, $setting, 'name', ifempty($info['name'], ''));
        if (!empty($info['description'])) {
            $this->addLocalizedField($dom, $xpath, $setting, 'description', $info['description']);
        }

        if (!empty($info['options'])) {
            if ($options = $xpath->query('options', $setting)) {
                for ($i = 0; $i < $options->length; $i++) {
                    $option = $options->item($i);
                    $option->parentNode->removeChild($option);
                }
            }
            $options = $this->addNode($dom, $xpath, $setting, 'options');


            foreach ($info['options'] as $value => $o) {
                $option = $this->addNode($dom, $xpath, $options, 'option');
                $option->setAttribute('value', $value);
                foreach (array('name', 'description') as $field) {
                    if (isset($o[$field])) {
                        $this->addLocalizedField($dom, $xpath, $option, $field, $o[$field]);
                    }
                }
            }
            unset($o);

        }

    }

    /**
     * @param bool $validate
     * @return bool|array
     */
    public function save($validate = false)
    {
        $res = null;
        if (!$this->readonly && $this->changed && $this->path) {
            if ($this->path_original && !$this->path_custom) {
                $this->copy();
            }
            $path = $this->path.'/'.self::PATH;
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            switch ($extension) {
                case 'xml':
                    $dom = $this->getXML(true);
                    /**
                     * @var DOMDocument $dom
                     */
                    $xpath = new DOMXPath($dom);
                    $theme = $xpath->query('/theme')->item(0);
                    /**
                     * @var DOMElement $theme
                     */

                    $ml_fields = array('name', 'description', 'about');
                    foreach ($ml_fields as $field) {
                        if (isset($this->changed[$field]) && $this->info[$field]) {
                            $this->addLocalizedField($dom, $xpath, $theme, $field, $this->info[$field]);
                        }
                        unset($this->changed[$field]);
                    }

                    if (isset($this->changed['files'])) {

                        //files workaround
                        $query = "/theme/files";
                        if (!($files = $xpath->query($query)->item(0))) {
                            $files = $this->addNode($dom, $xpath, $theme, 'files');
                        }
                        foreach ($this->changed['files'] as $file_path => $changed) {

                            if (isset($this->info['files'][$file_path]) && $this->info['files'][$file_path]) {
                                $info = $this->info['files'][$file_path];
                                $query = "/theme/files/file[@path='{$file_path}']";
                                if (!($file = $xpath->query($query)->item(0))) {
                                    $file = $this->addNode($dom, $xpath, $files, 'file');
                                    $file->setAttribute('path', $file_path);
                                }

                                $file->setAttribute('custom', !empty($info['custom']) ? '1' : '0');

                                if (!empty($info['modified']) || (string)$file->getAttribute('modified')) {
                                    $file->setAttribute('modified', $info['modified'] ? '1' : '0');
                                }
                                if (!empty($info['parent'])) {
                                    $file->setAttribute('parent', $info['parent'] ? '1' : '0');
                                }

                                if (!empty($info['description'])) {
                                    $this->addLocalizedField($dom, $xpath, $file, 'description', $info['description']);
                                }


                            } else {
                                $query = "/theme/files/file[@path='{$file_path}']";
                                if (($file = $xpath->query($query)->item(0))) {
                                    $files->removeChild($file);
                                }
                            }
                        }
                        unset($this->changed['files']);
                    }

                    if (!empty($this->changed['settings'])) {
                        //settings workaround
                        $query = "/theme/settings";
                        $settings = $xpath->query($query)->item(0);
                        if (!$settings) {
                            $settings = $this->addNode($dom, $xpath, $theme, 'settings');
                        }
                        foreach ($this->changed['settings'] as $var => $changed) {
                            $query = "/theme/settings/setting[@var='{$var}']/value";

                            $value_items = $xpath->query($query);
                            if (!$value_items->length) {
                                $setting = $this->addNode($dom, $xpath, $settings, 'setting');
                                $setting->setAttribute('var', $var);
                                $this->addNode($dom, $xpath, $setting, 'value');
                                if (!empty($this->info['settings'][$var])) {
                                    $this->updateSetting($dom, $xpath, $setting, $this->info['settings'][$var]);
                                }
                                $value_items = $xpath->query($query);
                            } elseif (!empty($this->info['settings'][$var]['changed'])) {
                                $setting = $value_items->item(0)->parentNode;
                                /**
                                 * @var DOMElement $setting
                                 */
                                $this->updateSetting($dom, $xpath, $setting, $this->info['settings'][$var]);
                                unset($this->info['settings'][$var]['changed']);
                            }
                            $length = $value_items->length;

                            if ($length && ($value = $value_items->item(0))) {
                                /**
                                 * @var DOMElement $value
                                 */
                                if (ifset($this->settings[$var]['control_type']) == 'text') {
                                    $value->nodeValue = '';
                                    $value->appendChild(new DOMCdataSection(self::prepareField(ifempty($this->settings[$var]['value'], ''))));
                                } else {
                                    $value->nodeValue = self::prepareField(ifempty($this->settings[$var]['value'], ''));
                                }

                                if ($value->hasAttribute('locale')) {
                                    $value->removeAttribute('locale');
                                }
                                $parent = $value->parentNode;
                                for ($index = 1; $index < $length; $index++) {
                                    $parent->removeChild($value_items->item($index));
                                }
                            }
                        }
                        unset($this->changed['settings']);
                    }

                    //todo add save locales support
                    if ($this->changed) {
                        foreach ($this->changed as $field => $changed) {
                            if ($changed) {
                                $value = ifset($this->info[$field], '');
                                if (in_array($field, array('system'))) {
                                    $value = sprintf('%d', $value);
                                }
                                $theme->setAttribute($field, $value);
                            }
                            unset($this->changed[$field]);
                        }
                    }

                    $dom->preserveWhiteSpace = false;
                    $dom->formatOutput = true;

                    if (($res = $dom->save($path, LIBXML_COMPACT)) && $validate) {
                        $res = $this->validate($dom, true);
                    }
                    break;
                case 'php':
                    $res = waUtils::varExportToFile($this->info, $path);
                    break;
                default:
                    //nothing todo
                    break;
            }
            if ($res) {
                self::protect($this->app, $this->path_custom ? true : false);
            }
        }

        return $res;
    }


    /**
     * @param DOMDocument $dom
     * @param bool $strict
     * @return array
     */
    private function validate($dom, $strict = false)
    {
        libxml_use_internal_errors(true);
        $valid = @$dom->validate();
        $report = array();
        if ((!$valid || $strict) && ($r = libxml_get_errors())) {
            $report = array();
            if ($valid) {
                $report[] = array(
                    'level'   => 'info',
                    'message' => 'theme XML is valid',
                );
            } else {

                $report[] = array(
                    'level'   => 'error',
                    'message' => 'theme XML contains errors',
                );
            }
            foreach ($r as $er) {
                /**
                 * @var libXMLError $er
                 */

                $level_name = '';
                switch ($er->level) {
                    case LIBXML_ERR_WARNING:
                        $level_name = 'LIBXML_ERR_WARNING';
                        break;
                    case LIBXML_ERR_ERROR:
                        $level_name = 'LIBXML_ERR_ERROR';
                        break;
                    case LIBXML_ERR_FATAL:
                        $level_name = 'LIBXML_ERR_FATAL';
                        break;

                }
                if ($er->code != 505) {
                    $report[] = array(
                        'level'   => $valid ? 'warning' : 'error',
                        'message' => "{$level_name} #{$er->code} [{$er->line}:{$er->column}]: {$er->message}",
                    );
                }

            }
            if ($valid && (count($report) == 1)) {
                $report = array();
            }

        }
        if (!empty($this->info['files'])) {

            $files = array();

            foreach ($this->info['files'] as $path => $file) {
                if (empty($file['parent'])) {
                    if (!file_exists($this->path.'/'.$path)) {
                        $files[] = $path;
                    }
                }
            }
            if (!empty($files)) {
                $report[] = array(
                    'level'   => 'warning',
                    'message' => sprintf(_w('Missed theme file(s): %s'), implode(', ', $files)),
                );
            }
        }
        return $report;
    }

    public function __destruct()
    {
        $this->save();
    }

    /**
     * Copy existing theme
     * @param string $id
     * @param array $params
     * @throws waException
     * @return waTheme
     */
    public function copy($id = null, $params = array())
    {
        if ($id) {
            self::verify($id);
        } else {
            $id = $this->id;
        }
        $target = wa()->getDataPath("themes/{$id}", true, $this->app, false);
        if (file_exists($target.'/'.self::PATH)) {
            throw new waException(sprintf(_ws("Theme %s already exists"), $id));
        }
        self::protect($this->app);
        waFiles::copy($this->path, $target, '/\.(files\.md5|cvs|svn|git|php\d*)$/');
        @touch($target.'/'.self::PATH);
        if ($this->id != $id) {
            //hack for extended classes
            $class = get_class($this);
            /**
             * @var $instance waTheme
             */
            $instance = new $class($id, $this->app);
            $instance->init();
            $instance->info['id'] = $id;
            $instance->changed['id'] = true;
            foreach ($params as $param => $value) {
                $instance[$param] = $value;
            }
            $instance['system'] = false;
            $instance->save();
            return $instance;
        } else {
            $this->initPath();
            if ($params) {
                foreach ($params as $param => $value) {
                    $this[$param] = $value;
                }
                $this->save();
            }
            return $this;
        }
    }

    public function update($only_if_not_modified = true)
    {
        if (!$this->path_custom || ($this->type != self::OVERRIDDEN)) {
            return true;
        }
        $files = $this->getFiles();
        $modified = array();
        foreach ($files as $f_id => $f) {
            if (!empty($f['modified'])) {
                $modified[] = $f_id;
                break;
            }
        }

        if ($only_if_not_modified && $modified) {
            return false;
        }

        $img_files = array();
        foreach ($this->getSettings() as $s) {
            if (ifset($s['control_type']) == 'image' && !empty($s['value'])) {
                $img_files[] = $s['value'];
            }
        }

        $source_path = $this->path_original;
        $target_path = $this->path_custom;

        $list_files = waFiles::listdir($source_path);
        $skip_pattern = '/\.(files\.md5|cvs|svn|git|php\d*)$/';

        foreach ($list_files as $f) {
            // ignore files
            if ($f !== 'build.php') {
                foreach ((array)$skip_pattern as $pattern) {
                    if (preg_match($pattern, $f)) {
                        continue 2;
                    }
                }
            }
            // ignore image settings and modified
            if ($f == 'theme.xml' || in_array($f, $img_files) || in_array($f, $modified)) {
                continue;
            }
            try {
                waFiles::copy($source_path.'/'.$f, $target_path.'/'.$f);
            } catch (waException $e) {
            }
        }

        if ($this->type == self::OVERRIDDEN) {
            $theme_original = new waTheme($this->id, $this->app_id, self::ORIGINAL);
            $this->version = $theme_original->version;

            foreach ($theme_original->getFiles(true) as $f_id => $f) {
                if (empty($files[$f_id])) {
                    $this->setFiles(array($f_id => $f));
                }
            }

            foreach ($theme_original->getSettings('full') as $var => $s) {
                if (!isset($this->info['settings'][$var])) {
                    $this->info['settings'][$var] = $s;
                    $this->settings[$var]['value'] = ifset($s['value'], '');
                    $this->changed['settings'][$var] = true;
                } else {
                    $old_s = $this->info['settings'][$var];
                    if ((ifset($old_s['control_type']) != ifset($s['control_type'])) ||
                        (ifset($old_s['options']) != ifset($s['options']))
                    ) {
                        $s['value'] = ifset($old_s['value'], '');
                        $this->info['settings'][$var] = $s;
                        $this->settings[$var]['value'] = $s['value'];
                        $this->changed['settings'][$var] = true;
                    }
                }
            }
            $this->save();
        }
        return true;
    }

    public function revertFile($file)
    {
        if ($f = $this->getFile($file)) {
            if ($f['parent'] && $this->parent_theme_id) {
                $this->getParentTheme()->revertFile($file);
                return;
            } else {
                waFiles::copy($this->path_original.'/'.$file, $this->path.'/'.$file);
            }
            $this->setFiles(array($file => array('modified' => 0)));
            $this->save();
        }
    }


    /**
     *
     * Fork theme into custom user theme with current id as theme_parent_id
     * @param string $id
     * @param array $params
     * @throws waException
     * @return waTheme
     */
    public function fork($id, $params = array())
    {
        throw new waException('Incomplete code', 500);
    }


    /**
     * Rename existing theme
     * @param string $id
     * @param array $params
     * @throws waException
     * @return waTheme
     */
    public function move($id, $params = array())
    {
        if (($this->id != $id) && (!$this->init('system') || empty($this->info['system']))) {
            self::verify($id);

            $target = wa()->getDataPath("themes/{$id}", true, $this->app, false);
            if (file_exists($target)) {
                throw new waException(sprintf(_ws("Theme %s already exists"), $id));
            }
            self::protect($this->app);

            waFiles::move($this->path, $target);
            $class = get_class($this);
            /**
             * @var waTheme $instance
             */
            $instance = new $class($id, $this->app);
            $instance->init();
            $instance->info['id'] = $id;
            $instance->changed['id'] = true;
            foreach ($params as $param => $value) {
                $instance[$param] = $value;
            }
            $instance->save();
            return $instance;
        } elseif (($this->type == self::ORIGINAL) || !empty($this->info['system'])) {
            return $this->copy($id, $params);
        } else {
            foreach ($params as $param => $value) {
                $this[$param] = $value;
            }
            if ($params) {
                $this->save();
            }
            return $this;
        }
    }

    /**
     *
     * Reset overriden theme changes
     * @return bool
     */
    public function reset()
    {
        if ($this->path_custom && ($this->type == self::OVERRIDDEN)) {
            $res = waFiles::delete($this->path_custom);
            $this->initPath();
            $this->flush();
            return $res;
        } else {
            return false;
        }
    }

    /**
     *
     * Flush template's caches
     */
    public function flush()
    {
        //wa-cache/apps/$app_id/templates/
        waFiles::delete(wa()->getAppCachePath('templates', $this->app));
    }

    /**
     *
     * Delete custom theme
     */
    public function delete()
    {
        if ($this->path_custom && in_array($this->type, array(self::OVERRIDDEN, self::CUSTOM))) {
            $res = waFiles::delete($this->path_custom);
            $this->flush();
            return $res;
        } else {
            return false;
        }
    }

    protected static function protect($app, $custom = true)
    {
        // create .htaccess to ney access to *.php and *.html files

        if ($custom) {
            $path = wa()->getDataPath('themes/.htaccess', true, $app, false);
        } else {
            $path = wa()->getAppPath('themes/.htaccess', $app);
        }
        if (!file_exists($path)) {
            waFiles::create($path);
            $htaccess = <<<HTACCESS

<FilesMatch "\\.(php\\d*|html?|xml)$">
    Deny from all
</FilesMatch>

HTACCESS;
            @file_put_contents($path, $htaccess);
        }
    }

    public function getPath()
    {
        return $this->path;
    }

    private function getPathCustom()
    {
        return $this->path_custom;
    }

    private function getCustom()
    {
        return self::preparePath($this->path_custom);
    }

    private function getPathOriginal()
    {
        return $this->path_original;
    }

    private function getOriginal()
    {
        return self::preparePath($this->path_original);
    }

    private function getApp()
    {
        return $this->app;
    }

    /**
     *
     * @todo app or app_id at theme description?
     */
    private function getAppId()
    {
        return $this->app;
    }

    private function getId()
    {
        return $this->id;
    }

    private function getSlug()
    {
        return "{$this->app}/themes/{$this->id}";
    }

    public function getUrl()
    {
        if (is_null($this->url)) {
            switch ($this->type) {
                case self::CUSTOM:
                case self::OVERRIDDEN:
                    $this->url = wa()->getDataUrl('themes', true, $this->app).'/'.$this->id.'/';
                    break;
                case self::ORIGINAL:
                    $this->url = wa()->getAppStaticUrl($this->app).'themes/'.$this->id.'/';
                    break;
                default:
                    $this->url = false;
                    break;
            }
        }
        return $this->url;
    }

    protected function getUsed()
    {
        if ($this->used === null) {
            $this->used = self::getRoutingRules($domains = null, $this->app, $this->id);
            if ($this->used) {
                $urls = array();
                foreach ($this->used as &$url) {
                    $id = $url['domain'].$url['url'];
                    if (!($url['met'] = isset($urls[$id]))) {
                        $urls[$id] = true;
                    }
                }
                unset($url);
            }
        }
        return $this->used;
    }

    public function getCover()
    {
        if ($this->path && file_exists($this->path.'/cover.png')) {
            return $this->getUrl().'cover.png';
        } elseif (!empty($this->extra_info['cover'])) {
            return $this->extra_info['cover'];
        } else {
            return null;
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function setName($name)
    {
        $this->info['name'] = self::prepareSetField($this->init('name') ? $this->info['name'] : '', $name);
        $this->changed['name'] = true;
    }

    public function setVendor($vendor)
    {
        $this->init();
        $this->info['vendor'] = $vendor;
        $this->changed['vendor'] = true;
    }

    public function getVendor()
    {
        return $this->init('vendor') ? $this->info['vendor'] : 'unknow';
    }

    public function setDescription($name)
    {
        $this->info['description'] = self::prepareSetField($this->init('description') ? $this->info['description'] : '', $name);
        $this->changed['description'] = true;
    }

    public function setAbout($text)
    {
        $this->info['about'] = self::prepareSetField($this->init('about') ? $this->info['about'] : '', $text);
        $this->changed['about'] = true;
    }

    private static function prepareSetField($field, $value)
    {
        $field = self::prepareField($field, true);
        if (is_array($value)) {
            foreach ($value as $locale => $item_value) {
                $field[$locale] = $item_value;
            }
        } else {
            $locale = self::getLocale($field);
            $field[$locale] = $value;
        }
        return $field;
    }

    private static function prepareField($field, $full = false)
    {
        if (is_array($field)) {
            if ($full) {
                return $field;
            } else {
                $locale = self::getLocale($field);
                return !empty($field[$locale]) ? $field[$locale] : current($field);
            }
        } elseif ($full) {
            $locale = self::getLocale();
            return array($locale => $field);
        } else {
            return $field;
        }
    }

    private static function preparePath($path)
    {
        static $root;
        if (!$root) {
            $root = wa()->getConfig()->getRootPath();
        }

        if ($path && (strpos($path, $root) === 0)) {
            $path = str_replace($root, '', $path);
            $path = preg_replace(array('@[\\\\/]+@', '@^/@'), array('/', ''), $path);
        }
        return $path;
    }

    public function getName($full = false)
    {
        return self::prepareField($this->init('name') ? $this->info['name'] : $this->id, $full);
    }

    public function getDescription($full = false)
    {
        return self::prepareField($this->init('description') ? $this->info['description'] : '', $full);
    }

    public function getAbout($full = false)
    {
        return self::prepareField($this->init('about') ? $this->info['about'] : '', $full);
    }

    /**
     * Hook for offsetSet('files')
     * @param array $file
     */
    private function setFiles($file)
    {
        $this->init();
        foreach ($file as $path => $properties) {
            if (!in_array(pathinfo($path, PATHINFO_EXTENSION), array('js', 'html', 'css'))) {
                $properties = null;
            }
            if (!isset($this->changed['files'])) {
                $this->changed['files'] = array();
            }
            $this->changed['files'][$path] = true;
            if (!$properties) {
                unset($this->info['files'][$path]);
            } else {
                $description = isset($properties['description']) ? $properties['description'] : '';
                if (!isset($this->info['files'][$path])) {
                    $this->info['files'][$path] = array('description' => array(), 'custom' => true);
                }
                if (isset($properties['custom'])) {
                    $this->info['files'][$path]['custom'] = $properties['custom'] ? true : false;
                }
                if (isset($properties['modified'])) {
                    $this->info['files'][$path]['modified'] = $properties['modified'] ? true : false;
                }
                if (isset($properties['parent'])) {
                    $this->info['files'][$path]['parent'] = $properties['parent'] ? true : false;
                }
                if (!isset($this->info['files'][$path]['description'])) {
                    $this->info['files'][$path]['description'] = '';
                }
                $this->info['files'][$path]['description'] = self::prepareSetField($this->info['files'][$path]['description'], $description);
            }
        }
    }

    private function setSettings($settings)
    {
        if (!isset($this->changed['settings'])) {
            $this->changed['settings'] = array();
        }
        $this->getSettings();
        foreach ($settings as $var => $value) {
            if (isset($this->settings[$var])) {
                if (ifset($this->settings[$var]['value']) != $value) {
                    $this->settings[$var]['value'] = $value;
                    $this->changed['settings'][$var] = true;
                }
            }
        }
    }

    private function setEdition($value)
    {
        if ($value === true) {
            ++$this->info['edition'];
            $this->changed['edition'] = true;
        } else {
            $this->changed['edition'] = !empty($this->changed['edition']) || ($this->info['edition'] != $value);
            $this->info['edition'] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        if ($this->getMethod($offset)) {
            return true;
        } else {
            return $this->init($offset) ? true : isset($this->extra_info[$offset]);
        }
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $value = null;
        if ($method_name = $this->getMethod($offset)) {
            $value = $this->{$method_name}();
        } elseif ($this->init($offset)) {
            $value =  &$this->info[$offset];
        } elseif (isset($this->extra_info[$offset])) {
            $value = $this->extra_info[$offset];
        }
        return $value;
    }

    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    public function __set($offset, $value)
    {
        return $this->offsetSet($offset, $value);
    }

    /**
     * @param string $offset
     * @param mixed $value
     * @return mixed
     */
    public function offsetSet($offset, $value)
    {
        if ($method_name = $this->getMethod($offset, 'set')) {
            //hook for $theme['name']=array('ru_RU' => 'name'); and etc
            $value = $this->{$method_name}($value);
        } elseif ($this->init($offset)) {
            $this->changed[$offset] = true;
            $this->info[$offset] = $value;
        } else {
            $this->extra_info[$offset] = $value;
        }
        return $value;
    }

    private function getMethod($offset, $type = 'get')
    {
        static $methods = array(
            'get' => array(),
            'set' => array(),
        );
        if (!isset($methods[$type][$offset])) {
            $methods[$type][$offset] = $type.preg_replace_callback('/(^|_)([a-z])/', array($this, 'getMethodCallback'), $offset);
            if (!method_exists($this, $methods[$type][$offset])) {
                $methods[$type][$offset] = false;
            }
        }
        return $methods[$type][$offset];
    }

    private function getMethodCallback($m)
    {
        return strtoupper($m[2]);
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        if (is_null($this->info)) {
            $this->init();
        }
        if (isset($this->info[$offset])) {
            $this->changed[$offset] = true;
            unset($this->info[$offset]);
        } elseif (isset($this->extra_info[$offset])) {
            unset($this->extra_info[$offset]);
        }
    }

    private function getMtime()
    {
        $path = $this->path.'/'.self::PATH;
        return file_exists($path) ? filemtime($path) : false;
    }

    public function getFile($file)
    {
        $this->init();
        if (!$file || !isset($this->info['files'][$file])) {
            return array();
        }
        $res = $this->info['files'][$file];
        $res['description'] = isset($res['description']) ? self::prepareField($res['description']) : '';
        return $res;
    }

    public function getFiles($full = false)
    {
        $this->init();
        if ($full) {
            return $this->info['files'];
        } else {
            $files = $this->info['files'];
            foreach ($files as &$file) {
                $file['description'] = isset($file['description']) ? self::prepareField($file['description']) : '';
            }
            unset($file);
            return $files;
        }
    }


    public function getSettings($values_only = false)
    {
        $this->init();
        if ($values_only === 'full') {
            return $this->info['settings'];
        }
        if (!isset($this->settings)) {
            $this->settings = $this->info['settings'];
            foreach ($this->settings as $var => &$s) {
                $s['name'] = isset($s['name']) ? self::prepareField($s['name']) : $var;
                if (isset($s['description'])) {
                    $s['description'] = self::prepareField($s['description']);
                }
                if (isset($s['options'])) {
                    foreach ($s['options'] as &$o) {
                        if ($s['control_type'] == 'radio') {
                            $o['name'] = self::prepareField($o['name']);
                            if (isset($o['description'])) {
                                $o['description'] = self::prepareField($o['description']);
                            }
                        } else {
                            if (isset($o['name'])) {
                                $o = self::prepareField($o['name']);
                            } else {
                                $o = '';
                            }
                        }
                    }
                    unset($o);
                }
                if (isset($s['value']) && is_array($s['value'])) {
                    $s['value'] = $s['value'][self::getLocale($s['value'])];
                }
            }
            unset($s);
        }
        if ($values_only) {
            $settings = array();
            foreach ($this->settings as $var => $v) {
                $settings[$var] = ifset($v['value']);
            }
            return $settings;
        }
        return $this->settings;
    }


    public function getLocales()
    {
        $this->init();
        $locale = wa()->getLocale();
        $result = array();
        foreach ($this->info['locales'] as $id => $str) {
            $result[$id] = !empty($str[$locale]) ? $str[$locale] : null;
        }
        return $result;
    }

    /**
     * @param array $data
     * @return string
     */
    private static function getLocale($data = array())
    {
        $locale = wa()->getLocale();
        if ($data) {
            if (!isset($data[$locale])) {
                $locale = 'en_US';
                if (!isset($data[$locale])) {
                    reset($data);
                    $locale = key($data);
                }
            }
        }

        return $locale;
    }

    public static function verify($id)
    {
        if (!preg_match('/^[a-z0-9_\-]+$/i', $id)) {
            throw new waException(sprintf(_ws("Invalid theme id %s"), $id));
        }
    }


    public static function sort(&$themes)
    {
        uasort($themes, array(__CLASS__, 'sortThemesHandler'));
        return $themes;
    }

    /**
     * @param $domains
     * @param $app_id
     * @param $theme_id
     * @return array|bool
     */
    private static function getRoutingRules($domains, $app_id, $theme_id)
    {
        static $themes;
        if (!is_array($themes)) {
            $themes = array();
            $theme_types = array('desktop' => 'theme', 'mobile' => 'theme_mobile');
            $routing = wa()->getRouting();
            if ($domains === null) {
                $domains = $routing->getDomains();
            }
            foreach ((array)$domains as $domain) {
                $rules = $routing->getRoutes($domain);
                foreach ($rules as $rule) {
                    if (isset($rule['app'])) {
                        foreach ($theme_types as $type => $source) {
                            $id = isset($rule[$source]) ? $rule[$source] : 'default';
                            $app = $rule['app'];

                            if (!isset($themes[$app])) {
                                $themes[$app] = array();
                            }

                            if (!isset($themes[$app][$id])) {
                                $themes[$app][$id] = array();
                            }

                            $themes[$app][$id][] = array(
                                'domain'  => $domain,
                                'url'     => $rule['url'],
                                'type'    => $type,
                                'preview' => $routing->getUrlByRoute($rule, $domain)
                            );
                        }
                    }
                }
            }
        }
        return isset($themes[$app_id][$theme_id]) ? $themes[$app_id][$theme_id] : false;
    }

    /**
     *
     * @return waTheme
     */
    private function getParentTheme()
    {
        if (!isset($this->parent_theme)) {
            if ($id = $this->offsetGet('parent_theme_id')) {
                $this->parent_theme = new self($id);
            } else {
                $this->parent_theme = false;
            }
        }
        return $this->parent_theme;
    }

    /**
     * @return bool
     */
    private function getSystem()
    {
        if (!isset($this->system)) {
            $this->system = ($this->id == 'default');
        } elseif ($this->init('system')) {
            $this->system = !!$this->info['system'];
        }
        return $this->system;
    }

    private static function sortThemesHandler($theme1, $theme2)
    {
        return min(1, max(-1, $theme2['mtime'] - $theme1['mtime']));
    }

    /**
     *
     * @param string $slug
     * @throws waException
     * @return waTheme
     */
    public static function getInstance($slug)
    {
        $slug = urldecode($slug);
        if (preg_match('@^/?([a-z_0-9]+)/themes/([a-zA-Z_0-9\-]+)/?$@', $slug, $matches)) {
            return new self($matches[2], $matches[1]);
        } else {
            throw new waException(_w('Invalid theme slug').$slug);
        }
    }

    public function check()
    {
        if (!$this->path) {
            throw new waException(sprintf(_w("Theme %s not found"), $this->id));
        }
        if (!file_exists($this->path) || !file_exists($this->path.'/'.self::PATH)) {
            self::throwThemeException('MISSING_THEME_XML', $this->id);
        }
    }


    /**
     *
     * @return waTheme
     */
    public function brush()
    {
        if ($this->type == self::OVERRIDDEN) {
            if (empty($this->path_original)) {
                self::throwThemeException('ORIGINAL_THEME_NOT_FOUND', _w('Brush custom theme not available'));
            }

            waFiles::delete($this->path_custom, false);
        }
        $this->flush();
        $instance = new self($this->id, $this->app);
        return $instance;
    }

    public function purge()
    {
        $this->init();
        waFiles::delete($this->path_custom);
        if (!$this->system) {
            waFiles::delete($this->original);
        }
        $this->flush();
    }

    /**
     *
     * @throws waException
     * @return waTheme
     */
    public function duplicate()
    {
        $numerator = 0;
        $available = null;
        do {
            $id = $this->id.++$numerator;
            if ($numerator > 1000) {
                break;
            }
        } while ($available = self::exists($id, $this->app, true));

        if ($available) {
            throw new waException(_w("Duplicate theme failed"));
        }
        $names = $this->getName(true);
        foreach ($names as &$name) {
            $name .= ' '.$numerator;
        }
        unset($name);
        $params = array(
            'name'            => $names,
            'system'          => false,
            'source_theme_id' => $this->id,
        );
        return $this->copy($this->id.$numerator, $params);
    }

    /**
     *
     * Extract theme from archive
     * @throws Exception
     * @param string $source_path archive path
     *
     * @return waTheme
     */
    public static function extract($source_path)
    {
        /** @var string[] $white_list */
        static $white_list = array(
            'js',
            'css',
            'html',
            'txt',
            'png',
            'jpg',
            'jpeg',
            'jpe',
            'tiff',
            'bmp',
            'gif',
            'svg',
            'htc',
            'cur',
            'ttf',
            'eot',
            'otf',
            'woff',
            'woff2',
            '',
        );

        $autoload = waAutoload::getInstance();
        $autoload->add('Archive_Tar', 'wa-installer/lib/vendors/PEAR/Tar.php');
        $autoload->add('PEAR', 'wa-installer/lib/vendors/PEAR/PEAR.php');
        $instance = null;
        if (class_exists('Archive_Tar')) {
            try {
                $tar_object = new Archive_Tar($source_path, true);
                $files = $tar_object->listContent();
                if (!$files) {
                    self::throwArchiveException('INVALID_OR_EMPTY_ARCHIVE');
                }

                //search theme info
                $info = false;
                $pattern = "@(/|^)".wa_make_pattern(self::PATH, '@')."$@";
                foreach ($files as $file) {
                    if (preg_match($pattern, $file['filename'])) {
                        $info = $tar_object->extractInString($file['filename']);
                        break;
                    }
                }

                if (!$info) {
                    self::throwThemeException('MISSING_THEME_XML');
                }

                $xml = @simplexml_load_string($info);
                $app_id = (string)$xml['app'];
                $id = (string)$xml['id'];

                if (!$app_id) {
                    self::throwThemeException('MISSING_APP_ID');
                } elseif (!$id) {
                    self::throwThemeException('MISSING_THEME_ID');
                } else {
                    if ($app_info = wa()->getAppInfo($app_id)) {
                        //TODO check theme support
                        if ($parent_theme = (string)$xml['parent_theme_id']) {
                            $parent_theme = explode(':', $parent_theme, 2);
                            try {
                                if (count($parent_theme) == 2) {
                                    new waTheme($parent_theme[1], $parent_theme[0]);
                                } else {
                                    new waTheme($parent_theme[0], $app_id);
                                }
                            } catch (Exception $ex) {
                                self::throwThemeException('PARENT_THEME_NOT_FOUND', $ex->getMessage());
                            }
                        }
                    } else {
                        $message = sprintf(_w('Theme “%s” is for app “%s”, which is not installed in your Webasyst. Install the app, and upload theme once again.'), $id, $app_id);
                        throw new waException($message);
                    }
                }


                $wa_path = "wa-apps/{$app_id}/themes/{$id}";
                $wa_pattern = wa_make_pattern($wa_path, '@');

                $file = reset($files);
                if (preg_match("@^{$wa_pattern}(/|$)@", $file['filename'])) {
                    $extract_path = $wa_path;
                    $extract_pattern = $wa_pattern;
                } else {
                    $extract_path = $id;
                    $extract_pattern = wa_make_pattern($id, '@');
                    if (!preg_match("@^{$extract_pattern}(/|$)@", $file['filename'])) {
                        $extract_path = '';
                        $extract_pattern = false;
                    }
                }
                if ($extract_path) {
                    $extract_path = trim($extract_path, '/').'/';
                }

                $missed_files = array();
                foreach ($xml->xpath('/theme/files/file') as $theme_file) {
                    $path = (string)$theme_file['path'];
                    $parent = intval((string)$theme_file['parent']);
                    if (!in_array(pathinfo($theme_file['path'], PATHINFO_EXTENSION), array('html', 'js', 'css'))) {
                        self::throwThemeException('UNEXPECTED_EDITABLE_FILE_TYPE', $theme_file['path']);
                    }
                    if (!$parent) {
                        $missed_files[$path] = $extract_path.$path;
                    }
                }

                #angry check
                foreach ($files as $file) {
                    if ($extract_pattern && !preg_match("@^{$extract_pattern}(/|$)@", $file['filename'])) {
                        self::throwThemeException('UNEXPECTED_FILE_PATH', "{$file['filename']}. Expect files in [{$extract_path}] directory");
                    } elseif (preg_match('@\\.(php\d*|pl)@', $file['filename'], $matches)) {
                        if (preg_match('@(^|/)build\\.php$@', $file['filename'])) {
                            $file['content'] = $tar_object->extractInString($file['filename']);
                            if (!preg_match('@^<\\?php[\\s\\n]+return[\\s\\n]+\\d+;[\\s\\n]*$@', $file['content'])) {
                                self::throwThemeException('UNEXPECTED_FILE_CONTENT', $file['filename']);
                            }
                        } else {
                            self::throwThemeException('UNEXPECTED_FILE_TYPE', $file['filename']);
                        }
                    } else {
                        if (preg_match('@(^|/)\\.htaccess$@', $file['filename'])) {
                            $file['content'] = $tar_object->extractInString($file['filename']);
                            if (preg_match('@\\b(add|set)Handler\\b@ui', $file['content'])) {
                                self::throwThemeException('INVALID_HTACCESS', $file['filename']);
                            }
                        } elseif (!in_array(pathinfo($file['filename'], PATHINFO_EXTENSION), $white_list)) {
                            if (!in_array(strtolower(basename($file['filename'])), array('theme.xml', 'build.php', '.htaccess', 'readme',))) {
                                self::throwThemeException('UNEXPECTED_FILE_TYPE', $file['filename']);
                            }
                        }
                        if ($extract_pattern) {
                            $file['filename'] = preg_replace("@^{$extract_pattern}/?@", '', $file['filename']);
                        }

                        if (empty($file['typeflag']) && !empty($file['filename']) && isset($missed_files[$file['filename']])) {
                            unset($missed_files[$file['filename']]);
                        }
                    }
                }

                if (!empty($missed_files)) {
                    self::throwThemeException('MISSING_DESCRIBED_FILES', implode(', ', $missed_files));
                }
                self::verify($id);
                self::protect($app_id);
                $target_path = wa()->getDataPath("themes/{$id}", true, $app_id, false);
                waFiles::delete($target_path);
                if ($extract_path && !$tar_object->extractModify($target_path, $extract_path)) {
                    self::throwArchiveException('INTERNAL_ARCHIVE_ERROR');
                } elseif (!$tar_object->extract($target_path)) {
                    self::throwArchiveException('INTERNAL_ARCHIVE_ERROR');
                }

                $instance = new self($id, $app_id);
                $instance->check();
            } catch (Exception $ex) {
                if (isset($target_path) && $target_path) {
                    waFiles::delete($target_path, true);
                }
                throw $ex;
            }
        } else {
            self::throwArchiveException('UNSUPPORTED_ARCHIVE_TYPE');
        }
        return $instance;
    }

    private static function throwThemeException($code, $details = '')
    {
        $link = sprintf(_w('http://www.webasyst.com/framework/docs/site/themes/#%s'), $code);
        $message = _w('Invalid theme archive structure (%s). <a href="%s" target="_blank">See help</a> for details');
        if (!empty($details)) {
            $details = " ({$details})";
        }
        throw new waException(sprintf($message, $code, $link).$details);
    }

    /**
     * @param $code
     * @param string $details
     * @throws waException
     */
    private static function throwArchiveException($code, $details = '')
    {
        $link = sprintf(_w('http://www.webasyst.com/framework/docs/site/themes/#%s'), $code);
        throw new waException(sprintf(_w('Failed to extract files from theme archive (%s). <a href="%s" target="_blank">See help</a> for details'), $code, $link));
    }

    /**
     *
     * Compress theme into archive file
     * @param string $path target archive path
     * @param string $name archive filename
     * @return string arcive path
     */
    public function compress($path, $name = null)
    {
        if (!$name) {
            $name = "webasyst.{$this->app}.theme.{$this->id}.tar.gz";
        }
        $target_file = "{$path}/{$this->app}/{$name}";

        $autoload = waAutoload::getInstance();
        $autoload->add('Archive_Tar', 'wa-installer/lib/vendors/PEAR/Tar.php');
        $autoload->add('PEAR', 'wa-installer/lib/vendors/PEAR/PEAR.php');

        if (file_exists($this->path) && class_exists('Archive_Tar', true)) {
            waFiles::create($target_file);
            $tar_object = new Archive_Tar($target_file, true);
            $tar_object->setIgnoreRegexp('@(\.(php\d?|svn|git|fw_|files\.md5$))@');
            $path = getcwd();
            chdir(dirname($this->path));
            if (!$tar_object->create('./'.basename($this->path))) {
                waFiles::delete($target_file);
            }
            chdir($path);
        }
        return $target_file;
    }

    public static function getThemesPath($app_id = null, $relative = true)
    {
        static $path;
        if (!$path) {
            $path = wa()->getDataPath("themes", true, $app_id);
        }
        return $relative ? self::preparePath($path) : $path;
    }

    public function version($edition = false)
    {
        static $build;
        $this->init();
        if ($this->_version === null || $edition) {
            $this->_version = !empty($this->info['version']) ? $this->info['version'] : '0.0.1';
            if (SystemConfig::isDebug()) {
                $this->_version .= ".".time();
            } else {
                if ($edition === true) {
                    $edition = $this->edition;
                }
                if ($build === null) {
                    $file = $this->path.'/build.php';
                    if (file_exists($file)) {
                        $build = include($file);
                    } else {
                        $build = 0;
                    }
                }
                if ($build) {
                    if ($edition) {
                        $build += $edition;
                        return $this->_version.'.'.$build;
                    }
                    $this->_version .= '.'.$build;
                } elseif ($edition) {
                    return $this->_version.'.'.$edition;
                }
            }
        }
        return $this->_version;
    }
}
