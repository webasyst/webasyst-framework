<?php

class contactsShopBackend_customers_listHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $plugins_res = wa('contacts')->event('shop.backend_customers_list', $params);
        if (empty($plugins_res)) {
            return null;
        }

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

}