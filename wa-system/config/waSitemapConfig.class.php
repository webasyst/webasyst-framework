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
* @subpackage config
*/
class waSitemapConfig
{
    const CHANGE_ALWAYS  = 'always';
    const CHANGE_HOURLY  = 'hourly';
    const CHANGE_DAILY   = 'daily';
    const CHANGE_WEEKLY  = 'weekly';
    const CHANGE_MONTHLY = 'monthly';
    const CHANGE_YEARLY  = 'yearly';
    const CHANGE_NEVER   = 'never';

    protected $domain;
    private $real_domain;
    /**
     * @var waRouting
     */
    protected $routing;

    public function __construct()
    {
        $this->routing = wa()->getRouting();
        $this->domain = $this->routing->getDomain(null, true);
        $this->real_domain = $this->routing->getDomain(null, true, false);
    }

    public function execute()
    {

    }

    public function display()
    {
        $system = waSystem::getInstance();
        $system->getResponse()->addHeader('Content-Type', 'application/xml; charset=UTF-8');
        $system->getResponse()->sendHeaders();

        echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="'.$system->getUrl(true).'wa-content/xml/sitemap.xsl"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';
        if ($this->domain) {
            $this->execute(func_num_args() ? func_get_arg(0) : 1);
        }
        echo '</urlset>';
    }

    /**
     * @param string $loc - URL
     * @param string|int $lastmod - datetime of last modification
     * @param string $changefreq
     * @param int $priority
     */
    public function addUrl($loc, $lastmod, $changefreq = null, $priority = null)
    {
        if (!is_numeric($lastmod)) {
            $lastmod = strtotime($lastmod);
        }

        $xml  = "<url>\n";
        $xml .= "\t<loc>".htmlspecialchars($loc, ENT_NOQUOTES)."</loc>\n";
        $xml .= "\t<lastmod>".date('c', $lastmod)."</lastmod>\n";
        if ($changefreq) {
            $xml .= "\t<changefreq>".$changefreq."</changefreq>\n";
        }
        if ($priority) {
            $xml .= "\t<priority>".str_replace(',', '.', min(1.0, max(0.0, $priority)))."</priority>\n";
        }
        $xml .= "</url>\n";
        echo $xml;
    }

    public function count()
    {
        return 1;
    }

    public function getRoutes($app_id = null)
    {
        if (!$app_id) {
            $app_id = wa()->getApp();
        }
        $routes = $this->routing->getRoutes($this->domain);
        foreach ($routes as $r_id => $r) {
            if (!isset($r['app']) || $r['app'] != $app_id || !empty($r['private'])) {
                unset($routes[$r_id]);
            }
        }
        return $routes ? $routes : array();
    }

    public function getUrlByRoute($route)
    {
        return $this->routing->getUrlByRoute($route, $this->real_domain);
    }

}

