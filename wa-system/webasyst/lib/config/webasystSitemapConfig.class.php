<?php 

class webasystSitemapConfig
{
    public function execute()
    {
        $system = waSystem::getInstance();
        $system->getResponse()->addHeader('Content-Type', 'application/xml; charset=UTF-8');
        $system->getResponse()->sendHeaders();
        
        /* <?xml-stylesheet type="text/xsl" href="'.$system->getRootUrl(true).'wa-content/css/sitemap.xsl"?> */
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd"
         xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $apps = $system->getApps();
        foreach ($apps as $app_id => $app) {
            if (file_exists($system->getAppPath('lib/config/'.$app_id.'SitemapConfig.class.php', $app_id))) {
                echo '<sitemap>
<loc>'.$system->getRootUrl(true).'sitemap-'.$app_id.'.xml</loc>
      <lastmod>2004-10-01T18:23:17+00:00</lastmod>
</sitemap>';
            }
        }
		echo '</sitemapindex>';
		exit;
    }
}