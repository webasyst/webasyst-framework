<?php 

class siteHelper
{
    protected static $domain_id = null;
    protected static $domains = array();
    protected static $locale = null;
    protected static $themes = array();
    
    public static function getDomains($full = false)
    {
        if (!self::$domains) {
            $domain_model = new siteDomainModel();
            $q = $domain_model->select('*');
            if (!wa()->getUser()->isAdmin('site')) {
                $domain_ids = wa()->getUser()->getRights('site', 'domain.%', false);
                if ($domain_ids) {
                    $q->where("id IN ('".implode("','", $domain_ids)."')");
                } else {
                    $q->where('0');
                }
            }
            self::$domains = $q->fetchAll('id');
            if (wa()->getUser()->isAdmin('site')) {
                $routes = wa('wa-system')->getConfig()->getRouting();
                // hide default routing (for all domains)
                if (isset($routes['default'])) {
                    unset($routes['default']);
                }
                $ds = array();
                foreach (self::$domains as $d) {
                    $ds[] = $d['name'];
                }
                foreach ($routes as $r_id => $r) {
                    if (!is_array($r)) {
                        unset($routes[$r_id]);
                    }
                }
                $new_domains = array_diff(array_keys($routes), $ds);
                if ($new_domains) {
                    foreach ($new_domains as $d) {
                        $domain_model->insert(array('name' => $d));
                    }
                    self::$domains = $domain_model->select('*')->fetchAll('id');
                }
                if (!self::$domains) {
                    $domain_model->insert(array('name' => wa()->getConfig()->getDomain()));
                    self::$domains = $domain_model->select('*')->fetchAll('id');
                }
            }
            // hide default routing (for all domains)
            if (isset(self::$domains['default'])) {
                unset(self::$domains['default']);
            }
        }    
        $result = array();
        foreach (self::$domains as $id => $d) {
            $result[$id] = $d['title'] ? $d['title'] : $d['name'];
            if ($full) {
                $result[$id] = array(
                    'name' => $d['name'],
                    'title' => $result[$id],
                    'style' => $d['style']
                );
            }
        }
        return $result;
    }
    
    public static function getDomainId()
    {
        if (!self::$domain_id) {
            $domain_id = waRequest::get('domain_id');
            $domains = self::getDomains(true);
            if (is_numeric($domain_id)) {
                self::$domain_id = (int)$domain_id;
            } else {
                foreach ($domains as $d_id => $d) {
                    if ($d['name'] == $domain_id) {
                        self::$domain_id = $d_id;
                        break;
                    }
                }    
            }

            if (!self::$domain_id) {
                self::$domain_id = wa()->getUser()->getSettings('site', 'last_domain_id');
                if (!isset($domains[self::$domain_id])) {
                    self::$domain_id = null;
                }
            }
            if (!self::$domain_id) {
                self::$domain_id = current(array_keys($domains));
            }

            if (self::$domain_id && !isset($domains[self::$domain_id])) {
                throw new waException('Domain not found', 404);
            }
        }    
        return self::$domain_id;   
    }
    
    public static function getDomain($key = 'name')
    {
        self::getDomains();
        return self::$domains[self::getDomainId()][$key];
    }
    
    public static function getApp($info = true)
    {
        $app_id = waRequest::get('app');
        if (!$app_id) {
            $app_id = wa()->getConfig()->getApplication();
        }
        if ($info) {
            return wa()->getAppInfo($app_id);
        }
        return $app_id;
    }
    
    public static function getApps($app_key = false)
    {
        $wa = wa();
        $routes = $wa->getRouting()->getRoutes(self::getDomain());
        $all_apps = $wa->getApps();
        
        $apps = array();
        foreach ($routes as $route_id => $route) {
            if (isset($route['app'])) {
                $app_id = $route['app'];
                if (!isset($all_apps[$app_id])) {
                    continue;
                }
                if (isset($route['parent']) && isset($route['page_id'])) {
                    continue;
                }
                if (!isset($apps[$app_id])) {
                    if ($app_key && (!isset($all_apps[$app_id][$app_key]) || !$all_apps[$app_id][$app_key])) {
                        continue;
                    }
                    $apps[$app_id] = $all_apps[$app_id];
                    $apps[$app_id]['routes'] = array();
                }
                $apps[$app_id]['routes'][$route_id] = $route;
            }
        }
        return $apps;
        
    }

    public static function getThemes($app_id, $name_only = true)
    {
        if (!isset(self::$themes[$app_id])) {
            self::$themes[$app_id] = wa()->getThemes($app_id);
        }
        if ($name_only) {
            $themes = self::$themes[$app_id];
            foreach ($themes as &$theme) {
                if (!isset($theme['name'])) {
                    throw new waException("Invalid theme");
                }
                $theme = $theme['name'];
            }
            return $themes;
        } 
        return self::$themes[$app_id];
    }
    
    public static function copyTheme($source, $dest)
    {
        if (!file_exists($dest)) {
            waFiles::create($dest);
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
        $n = strlen($source);
        foreach ($iterator as $file) {
            $rel_path = str_replace('\\', '/', substr($file, $n));
            if (strpos($rel_path, '/.svn') !== false) {
                continue;
            }
            if ($file->isDir() && $file->getFileName() !== '.' && $file->getFileName() !== '..') {
                mkdir($dest.'/'.$rel_path);
            } elseif ($file->isFile()) {
                copy($file->getPathName(), $dest.'/'.$rel_path);
                if(basename($file->getPathName()) == 'theme.xml') {
                    @touch($dest.'/'.$rel_path);
                }
            }
        }
    }
    
    public static function getDomainUrl()
    {
        $u1 = rtrim(wa()->getRootUrl(false, false), '/');
        $u2 = rtrim(wa()->getRootUrl(false, true), '/');
        if ($u1 != $u2) {
            return substr($u2, strlen($u1));
        } else {
            return '';
        }
    }    
    
    public static function sortThemes($themes, $route)
    {
        $result = array();
        $t = isset($route['theme']) ? $route['theme'] : 'default';
        if (isset($themes[$t])) {
            $result[$t] = $themes[$t];
            unset($themes[$t]);
        }
        $t = isset($route['theme_mobile']) ? $route['theme_mobile'] : '';
        if ($t && $t != $route['theme'] && isset($themes[$t])) {
            $result[$t] = $themes[$t];
            unset($themes[$t]);
        }
        foreach ($themes as $t => $theme) {
            $result[$t] = $theme;
        }
        return $result;
    }    
}