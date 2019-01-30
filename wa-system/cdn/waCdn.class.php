<?php

/*
 * This file is part of Webasyst framework.
 *
 * Licensed under the terms of the GNU Lesser General Public License (LGPL).
 * http://www.webasyst.com/framework/license/
 *
 * @link http://www.webasyst.com/
 * @author Webasyst LLC
 * @copyright 2018 Webasyst LLC
 * @package wa-system
 * @subpackage cdn
 */

class waCdn
{
    /**
     * @var array
     */
    protected $cdn_list = array();

    protected $url;

    public function __construct($url = null)
    {
        $this->url = $url;
        if (wa()->getEnv() == 'frontend') {
            $domain = wa()->getRouting()->getDomain(null, true);
            $domain_config_path = wa()->getConfig()->getConfigPath('domains/'.$domain.'.php', true, 'site');
            if (file_exists($domain_config_path)) {
                $domain_config = include($domain_config_path);
                if (!empty($domain_config['cdn_list'])) {
                    return $this->cdn_list = (array)$domain_config['cdn_list'];
                }
            }
        }
    }

    public function __toString()
    {
        return $this->getRandom().$this->url;
    }

    public function getRandom()
    {
        if (!empty($this->cdn_list)) {
            return rtrim($this->cdn_list[array_rand($this->cdn_list, 1)], '/');
        }
        return '';
    }

    public function count()
    {
        return count($this->cdn_list);
    }
}