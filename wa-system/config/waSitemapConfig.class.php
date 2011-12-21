<?php

class waSitemapConfig
{
    public function execute()
    {
        
    }
    
    public function display()
    {
        $system = waSystem::getInstance();
        $system->getResponse()->addHeader('Content-Type', 'application/xml; charset=UTF-8');
        $system->getResponse()->sendHeaders();
        
        echo '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';
        $this->execute();
        echo '</urlset>';        
    }
    
    public function addUrl($loc, $lastmod, $changefreq = null, $priority = null)
    {
        if (!is_numeric($lastmod)) {
            $lastmod = strtotime($lastmod);
        }
        
        $xml  = "<url>\n";
        $xml .= "\t<loc>".$loc."</loc>\n";
        $xml .= "\t<lastmod>".date('c', $lastmod)."</lastmod>\n";
        if ($changefreq) {
            $xml .= "\t<changefreq>".$changefreq."</changefreq>\n";
        }
        if ($priority) {
            $xml .= "\t<priority>".$priority."</priority>\n";
        }
        $xml .= "</url>\n";
        echo $xml;        
    }
}

