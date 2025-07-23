<?php
/**
 * Blockpage settings (name, url, SEO etc.)
 * Used as a separate controller as well as a part of main Editor screen,
 * @see siteEditorAction
 */
class siteMapPageSettingsDialogAction extends waViewAction
{
    public $page_id;

    public function __construct($params = null)
    {
        parent::__construct($params);
        if (isset($params['page_id'])) {
            $this->page_id = $params['page_id'];
        } else {
            $this->page_id = waRequest::request('page_id', null, 'int');
        }
    }

    public function execute()
    {
        $is_new = waRequest::request('is_new', 0, 'int');
        $defaults = waRequest::request('defaults', [], waRequest::TYPE_STRING_TRIM);
        if ($defaults) {
            $defaults = @urldecode($defaults);
            $defaults = @json_decode($defaults, true);
        }

        $page = [];
        $page_params = [];
        $og_params = [];
        $misconfigured_settlement = false;
        $blockpage_model = new siteBlockpageModel();
        if ($this->page_id) {
            $blockpage_params_model = new siteBlockpageParamsModel();

            $page = $blockpage_model->getById($this->page_id);
            if (!$page) {
                throw new waException('Page not found', 404);
            }

            $page_params = $blockpage_params_model->getById($this->page_id);
            $og_params = array();
            foreach ($page_params as $k => $v) {
                if (substr($k, 0, 3) == 'og_') {
                    $og_params[substr($k, 3)] = $v;
                    unset($page_params[$k]);
                }
            }

            $other_params_temp = explode("\n", ifset($page_params, 'other_params', ''));

            foreach ($other_params_temp as $string) {
                $string = trim($string);
                if ($string && strpos($string, '=') !== false) {
                    $string = explode('=', $string, 2);
                    if ($string[0]) {
                        $page['params'][$string[0]] = $string[1];
                    }
                }
            }

            $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());
            $has_root_settlement = false;

            foreach ($routes as $_route_id => $_route) {
                if ($page['id'] == $_route_id) {
                    $misconfigured_settlement = $has_root_settlement;
                    break;
                } else if ($_route['url'] === '*' && !$has_root_settlement) {
                    $has_root_settlement = true;
                }
            }
        } elseif(!$is_new) {
            throw new waException('page_id is required', 400);
        } elseif ($parent_id = waRequest::request('parent_id')) {
            $parent_page = $blockpage_model->getById($parent_id);
            if ($parent_page) {
                $page = [
                    'theme' => ifset($parent_page, 'theme', 'default'),
                    'parent_id' => $parent_id,
                    'full_url' => ifset($parent_page, 'full_url', ''),
                ];
            }
        }

        if ($is_new && !waLicensing::check('site')->hasPremiumLicense()) {
            throw new waException(_w('The premium license is required to create block pages.'), 403);
        }

        if (!$this->page_id) {
            $new_url = ifset($defaults, 'url', siteHelper::getIncrementUrl());
            $page['full_url'] = (!empty($page['full_url']) ? $page['full_url'].'/' : ''). $new_url;
            $page = $page + [
                'name' => ifset($defaults, 'name', _w('New page')),
                'url' => $new_url,
                'title' => ifset($defaults, 'title', ''),
                'is_new' => $is_new,
            ];
        }

        if (!empty($defaults['params'])) {
            $page_params['meta_keywords'] = ifset($defaults['params'], 'meta_keywords', '');
            $page_params['meta_description'] = ifset($defaults['params'], 'meta_description', '');
        }

        $domain_decoded = (new waIdna())->decode(siteHelper::getDomain());
        $has_url_overlap = (bool)siteHelper::blockpageHasUrlOverlap($page['full_url'], ifset($page['parent_id']));
        $this->view->assign([
            'domain_id' => siteHelper::getDomainId(),
            'domain_decoded' => $domain_decoded,
            'locales' => array('' => _w('Auto')) + waLocale::getAll('name'),
            'page' => $page,
            'page_params' => $page_params,
            'og_params' => $og_params,
            'misconfigured_settlement' => $misconfigured_settlement,
            'is_main_page' => !$has_url_overlap && !$misconfigured_settlement && ifset($page['url'], '') === '',
            'has_url_overlap' => $has_url_overlap,
            'preview_hash' => siteHelper::getPreviewHash(),
        ]);
    }
}
