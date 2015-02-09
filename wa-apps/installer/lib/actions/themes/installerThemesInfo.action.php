<?php
class installerThemesInfoAction extends waViewAction
{
    public function execute()
    {
        $filter = array();

        $filter['enabled'] = true;
        $filter['extras'] = 'themes';

        $options = array(
            'installed' => true,
        );
        $applications = installerHelper::getInstaller()->getApps($options, $filter);


        $search = array();
        $search['slug'] = waRequest::get('slug');
        $search['vendor'] = waRequest::get('vendor', 'webasyst');
        $search['vendor'] = waRequest::get('theme_vendor', 'webasyst');
        if (!empty($search['slug'])) {
            $options = array(
                'action'       => true,
                'requirements' => true,
            //XXX    'vendor'       => waRequest::get('theme_vendor', 'webasyst'),
                'inherited'    => array_keys($applications),

            );
            if ($theme = installerHelper::getInstaller()->getItemInfo('*/themes/'.$search['slug'], $options)) {
                $theme['app'] = preg_replace('@/.+$@', '', $theme['slug']);
            }

            $this->view->assign('identity_hash', installerHelper::getHash());
            $this->view->assign('promo_id', installerHelper::getPromoId());
            $this->view->assign('domain', installerHelper::getDomain());
            $this->view->assign('theme', $theme);
            $this->view->assign('query', waRequest::get('query', '', waRequest::TYPE_STRING_TRIM).'/');
        } else {
            throw new waException(_w('Theme not found'), 404);
        }
    }
}
