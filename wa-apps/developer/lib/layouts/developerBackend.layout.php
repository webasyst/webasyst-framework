<?php

class developerBackendLayout extends waLayout
{
    public function execute()
    {
        $selected_tab_id = ifempty(ref($this->view->getVars('page')), waRequest::get('tab_id', '', 'string'));

        /**
         * @event backend_assets
         * @return array[string][string]array $return[%plugin_id%]['assets'] Extra head tag content
         * @return array[string][string]array $return[%plugin_id%]['tabs'] Tabs in main layout
         * @return array[string][string][string]string $return[%plugin_id%]['tabs']['title'] Tab human-readable name
         * @return array[string][string][string]string $return[%plugin_id%]['tabs']['url'] Tab url
         */
        $backend_assets = wa('developer')->event('backend_assets', ref(array(
            'tab_id' => $selected_tab_id,
        )));
        $plugin_tabs = array();
        $plugin_assets = array();
        foreach($backend_assets as $plugin_id => $data) {
            foreach((array)ifset($data, 'assets', array()) as $a) {
                $plugin_assets[] = $a;
            }
            foreach(ifset($data, 'tabs', array()) as $name => $tab) {
                if (!is_array($tab)) {
                    $tab = array(
                        'name' => $name,
                        'url' => $tab,
                    );
                }
                if (empty($tab['url'])) {
                    continue;
                }
                if (empty($tab['id']) || isset($plugin_tabs[$tab['id']])) {
                    $tab['id'] = $plugin_id;
                    if (isset($plugin_tabs[$tab['id']])) {
                        $tab['id'] .= '-'.count($plugin_tabs);
                    }
                }
                if (substr($tab['url'], 0, 11) != 'javascript:') {
                    $p = 'tab_id='.$tab['id'];
                    if (false === strpos($tab['url'], '?')) {
                        $tab['url'] .= '?';
                    } else {
                        $tab['url'] .= '&';
                    }
                    $tab['url'] .= $p;
                }
                $tab['name'] = ifset($tab, 'name', str_replace('-plugin', '', $plugin_id));
                $tab['selected'] = $tab['id'] === $selected_tab_id;
                $plugin_tabs[$tab['id']] = $tab;
            }
        }

        $this->view->assign(array(
            'plugin_assets' => $plugin_assets,
            'plugin_tabs' => $plugin_tabs,
            'page' => $selected_tab_id,
        ));

        parent::execute();
    }
}
