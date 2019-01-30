<?php

class contactsShopBackend_customers_listHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $plugins_res = wa('contacts')->event('shop.backend_customers_list', $params);

        if (!empty($plugins_res)) {
            $res = array();
            foreach ($plugins_res as $plugin_name => $pl_res) {
                if (!empty($pl_res)) {
                    foreach ($pl_res as $k => $r) {
                        $res[$k] = ifset($res[$k], array());
                        $res[$k][] = $r;
                    }
                }
            }
            foreach ($res as $k => &$r) {
                $r = implode('', $r);
            }
            unset($r);
            return $res;
        }

        if (!empty($params['hash'])) {
            $hash = $params['hash'];
            $hash_ar = explode('/', $hash, 2);
            if (!empty($hash_ar[1])) {
                $hash_ar[1] = str_replace('/', '\\\\\\\/', $hash_ar[1]);
            }
            $hash = implode('/', $hash_ar);

            $url = wa()->getRootUrl(true).wa()->getConfig()->getBackendUrl()."/contacts/#contacts/";
            if (strpos($hash, 'search/') === 0) {
                $url .= str_replace('search/', 'search/shop_customers\\\/', $hash);
                return array(
                    'top_li' => '<input type="button" onclick="location.href=\''.$url.'\'" value="'._wd('contacts', 'Open in Contacts').'">',
                );
            } else if (preg_match('/^([a-z_0-9]*)\//', $hash, $match)) {
                $url .= str_replace($match[1] . '/', "search/shop_customers\\\/{$match[1]}=", $hash);
                return array(
                    'top_li' => '<input type="button" onclick="location.href=\''.$url.'\'" value="'._wd('contacts', 'Open in Contacts').'">',
                );
            } else {
                $url .= 'search/shop_customers\\\/' . $hash;
                return array(
                    'top_li' => '<input type="button" onclick="location.href=\''.$url.'\'" value="'._wd('contacts', 'Open in Contacts').'">',
                );
            }
        }

        return null;
    }

}