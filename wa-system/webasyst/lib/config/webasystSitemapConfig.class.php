<?php 

class webasystSitemapConfig extends waSitemapConfig
{
    public function execute()
    {
        $system = waSystem::getInstance();
        $apps = $system->getApps();
        $routes = $this->routing->getRoutes($this->domain);
        $domain_apps = array();
        foreach ($routes as $r) {
            if (isset($r['app']) && empty($r['private']) && isset($apps[$r['app']])) {
                if (!isset($domain_apps[$r['app']])) {
                    $domain_apps[$r['app']] = $apps[$r['app']];
                }
            }
        }

        foreach ($domain_apps as $app_id => $app) {
            $path = $system->getAppPath('lib/config/'.$app_id.'SitemapConfig.class.php', $app_id);
            if (file_exists($path)) {
                include_once($path);
                $class_name = $app_id.'SitemapConfig';
                /**
                 * @var waSitemapConfig $sitemap
                 */
                $sitemap = new $class_name;
                $n = $sitemap->count();
                for ($i = 0; $i < $n; $i++) {
                    echo '<sitemap>
<loc>'.$system->getRootUrl(true, true).'sitemap-'.$app_id.($n > 1 ? '-'.($i + 1) : '').'.xml</loc>
      <lastmod>'.date('c').'</lastmod>
</sitemap>';
                }
            }
        }
    }
    
    public function display()
    {
        $system = waSystem::getInstance();
        $system->getResponse()->addHeader('Content-Type', 'application/xml; charset=UTF-8');
        $system->getResponse()->sendHeaders();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet type="text/xsl" href="'.$system->getUrl(true).'wa-content/xml/sitemap-index.xsl"?>
<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"
         xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $this->execute();
        echo '</sitemapindex>';
    }
}