<?php
/**
 * Save settings of block page (name, url, seo, etc.)
 */
class siteEditorPageSettingsSaveController extends waJsonController
{
    public function execute()
    {
        $page_id = waRequest::request('id', null, 'int');
        $data = waRequest::request('info', [], 'array');

        if (!$page_id) {
            // should never happen
            $this->errors = [[
                'code' => 'page_id_required',
                'description' => 'page_id is required',
            ]];
            return;
        }

        if (empty($data['name'])) {
            $this->errors[] = [
                'field' => 'info[name]',
                'description' => _w('This field is required.'),
                'code' => 'required',
            ];
        }

        // !!! Временная мера, пока нет логики иерархических страниц
        if (isset($data['url'])) {
            $data['url'] = trim($data['url'], '/');
            $data['full_url'] = $data['url'];
        }

        if (waRequest::request('set_main_page')) {
            // make silence for current homepage
            $domain_id = siteHelper::getDomainId();
            $main_page = new siteMainPage($domain_id, $routes);
            $main_page->silenceMainPage();
            /*if ($new['url'] === '*') {
                unset($routes[$domain][$route_id]);
            }*/
        }
        
        $data['update_datetime'] = date('Y-m-d H:i:s');

        $blockpage_params_model = new siteBlockpageParamsModel();
        if (isset($data['params']) && is_array($data['params'])) {
            /*!!!if (!empty($data['params']['og'])) {
                foreach ($data['params']['og'] as $k => $v) {
                    $data['params']['og_'.$k] = $v;
                }
                unset($data['params']['og']);
            }*/
            $blockpage_params_model->save($page_id, $data['params']);
        }
        unset($data['params']);

        if (!$this->errors) {
            $blockpage_model = new siteBlockpageModel();
            $blockpage_model->updateById($page_id, $data);
            $this->response = $blockpage_model->getById($page_id);
            $this->response['params'] = $blockpage_params_model->getById($page_id);
        }
    }
}
