<?php

abstract class waAPIRightsMethod extends waAPIMethod
{
    protected $app = null; // override

    public function execute()
    {
        if (!$this->app || !wa()->appExists($this->app)) {
            throw new waAPIException('server_error', 500);
        }
        $this->response = self::getAppRights($this->app);
    }

    public static function getAppRights($app_id, waContact $contact = null) {
        $contact = ifset($contact, wa()->getUser());
        $rights = $contact->getRights($app_id);
        if (ifset($rights['backend'], 0) <= 0) {
            return array();
        }

        $class_name = wa($app_id)->getConfig()->getPrefix().'RightConfig';
        if (class_exists($class_name)) {
            $right_config = new $class_name();
            $rights += $right_config->getRights($contact->getId());
            $default_value = $rights['backend'] >= 2 ? $rights['backend'] : 0;
            foreach($right_config->getItems() as $it) {
                if (isset($it['params']['items'])) {
                    foreach($it['params']['items'] as $subid => $subname) {
                        if (empty($rights[$it['name'].'.'.$subid])) {
                            $rights[$it['name'].'.'.$subid] = $default_value;
                        }
                    }
                } else {
                    if (empty($rights[$it['name']])) {
                        $rights[$it['name']] = $default_value;
                    }
                }
            }
        }

        return $rights;
    }
}