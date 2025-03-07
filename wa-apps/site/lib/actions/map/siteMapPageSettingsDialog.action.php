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
        if (!$this->page_id) {
            throw new waException('page_id is required', 400);
        }
    }

    public function execute()
    {
        $blockpage_model = new siteBlockpageModel();
        $blockpage_params_model = new siteBlockpageParamsModel();
        $page = $blockpage_model->getById($this->page_id);
        $page_params = $blockpage_params_model->getById($this->page_id);
        
        $og_params = array();
        foreach ($page_params as $k => $v) {
            if (substr($k, 0, 3) == 'og_') {
                $og_params[substr($k, 3)] = $v;
                unset($page_params[$k]);
            }
        }

        $other_params_temp = explode("\n", ifset($page_params, 'other_params', ''));
        //$other_params = array();

        foreach ($other_params_temp as $string) {
            $string = trim($string);
            if ($string && strpos($string, '=') !== false) {
                $string = explode('=', $string, 2);
                if ($string[0]) {
                    $page['params'][$string[0]] = $string[1];
                }
            }
        }

        if (!$page) {
            throw new waException('Page not found', 404);
        }

        //IS NEEDED?
        $routes = wa()->getRouting()->getRoutes(siteHelper::getDomain());
        $has_root_settlement = false;
        $misconfigured_settlement = false;
        foreach ($routes as $_route_id => $_route) {
            if ($page['id'] == $_route_id) {
                $misconfigured_settlement = $has_root_settlement;
                break;
            } else if ($_route['url'] === '*' && !$has_root_settlement) {
                $has_root_settlement = true;
            }
        }

        $idna = new waIdna();
        $domain_decoded = $idna->decode(siteHelper::getDomain());

        $this->view->assign([
            'page' => $page,
            'page_params' => $page_params,
            'og_params' => $og_params,
            //'other_params' => $other_params,
            'domain_id' => siteHelper::getDomainId(),
            'domain_decoded' => $domain_decoded,
            'locales' => array('' => _w('Auto')) + waLocale::getAll('name'),
            'misconfigured_settlement' => $misconfigured_settlement,
            'is_main_page' => rtrim(ifset($page['url'], ''), '*') === '' && !$misconfigured_settlement,
        ]);
    }
}
