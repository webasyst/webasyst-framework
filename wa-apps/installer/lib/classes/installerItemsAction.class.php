<?php

abstract class installerItemsAction extends waViewAction
{
    protected $module = null;

    protected $store_path;

    abstract protected function buildStorePath($params);

    public function execute()
    {
        $filters = array();

        $slug = $this->getSlug();
        $tag = $this->getFilters('tag');

        // System plugins
        if (preg_match('~^wa-plugins/~', $slug)) {
            $filters['type'] = 'plugin';
            $filters['category'] = 'plugins:' . preg_replace('~^wa-plugins/~', '', $slug);
        } else {
            $filters['app'] = $slug;

            // For not-themes to pass the product type
            if ($this->module != 'theme') {
                $filters['type'] = $this->module;
            }

            if ($tag) {
                $filters['tag'] = $tag;
            }
        }

        $params = array(
            'filters'    => $filters,
            'in_app'     => true,
            //'return_url' => $this->getReturnUrl(), TODO: Remove later @ No longer pass return_url to the Store, but pass it to the Installer template (see wa-apps/installer/js/store.js, method productInstall)
        );

        $this->store_path = $this->buildStorePath($params);
    }

    public function display($clear_assign = true)
    {
        $this->preExecute();
        $this->execute();
        $this->afterExecute();

        $store_params = array(
            'store_path' => $this->store_path,
            'in_app'     => true,
            'return_url' => $this->getReturnUrl(),
        );

        $options = $this->getOptions();
        if (!empty($options)) {
            $store_params['options'] = $options;
        }

        $store_action = new installerStoreAction($store_params);
        return $store_action->display($clear_assign);
    }

    protected function getFilters($filter = null, $default = null)
    {
        $filters = waRequest::get('filter', array(), waRequest::TYPE_ARRAY_TRIM);
        if ($filter) {
            return ifempty($filters, $filter, $default);
        }
        return $filters;
    }

    protected function getSlug()
    {
        $slug = waRequest::get('slug', null, waRequest::TYPE_STRING_TRIM);
        if (!$slug) {
            $slug = $this->getFilters('slug');
        }

        return $slug;
    }

    /*
     * In the options, applications can pass such a parameter.
     * Ex: how to skip the confirm when installing a free product in the in_app product list.
     */
    protected function getOptions($option = null, $default = null)
    {
        $options = waRequest::get('options', null, waRequest::TYPE_ARRAY_TRIM);
        if ($option) {
            return ifempty($options, $option, $default);
        }
        return $options;
    }

    /**
     * This param is used in the in_app lists of plugins and design themes.
     * It is transmitted to the Store as a get-parameter and is added as a data attribute to the "Install" button for free products.
     * @return string
     */
    protected function getReturnUrl()
    {
        $url = waRequest::get('return_url', waRequest::server('HTTP_REFERER'));
        $hash = preg_replace('@^#@', '', waRequest::get('return_hash'));
        if ($hash) {
            $url .= '#'.$hash;
        }
        return $url;
    }
}
