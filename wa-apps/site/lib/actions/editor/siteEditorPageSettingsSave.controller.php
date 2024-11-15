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

        if (!$this->errors) {
            $blockpage_model = new siteBlockpageModel();
            $blockpage_model->updateById($page_id, $data);
            $this->response = $blockpage_model->getById($page_id);
        }
    }
}
