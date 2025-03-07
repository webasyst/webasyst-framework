<?php
/**
 * Duplicate a block page or HTML page
 */
class siteMapDuplicateController extends waJsonController
{
    public function execute()
    {
        $entity_type = waRequest::request('type', null, 'string');
        try {
            switch ($entity_type) {
                case 'blockpage':
                    $this->executeBlockpage();
                    break;
                case 'htmlpage':
                    $this->executeHtmlpage();
                    break;
                default:
                    $this->errors[] = [
                        'error' => 'type_required',
                        'description' => 'type parameter is required',
                    ];
                    return;
            }
        } catch (Throwable $e) {
            $this->errors[] = self::formatError($e);
        }
    }

    public function executeBlockpage()
    {
        $page_id = waRequest::post('id', null, 'int');

        $blockpage_model = new siteBlockpageModel();
        if ($page_id) {
            $page = $blockpage_model->getById($page_id);
        }
        if (empty($page)) {
            $this->errors[] = [
                'error' => 'page_not_found',
                'description' => 'Page with this id does not exist',
            ];
            return;
        }

        $page['name'] = sprintf_wp('%s copy', $page['name']);
        $page['url'] = rtrim($page['url'], '/').'copy';
        $page['full_url'] = rtrim($page['full_url'], '/').'copy';
        $page['update_datetime'] = $page['create_datetime'] = date('Y-m-d H:i:s');
        $page['final_page_id'] = null;
        $page['status'] = 'draft';
        unset($page['id']);

        $new_page_id = $blockpage_model->insert($page);
        $blockpage_model->copyContents($page_id, $new_page_id);

        $this->response = [
            'id' => $new_page_id,
        ];
    }

    public function executeHtmlpage()
    {
        $app_id = waRequest::post('app', null, 'string');
        $page_id = waRequest::post('id', null, 'int');
        if (!$app_id) {
            $this->errors[] = [
                'error' => 'app_id_required',
                'description' => 'app_id parameter is required',
            ];
            return;
        }

        if ($app_id != 'site') {
            wa($app_id);
        }
        $class_name = $app_id.'PageModel';
        if (!class_exists($class_name)) {
            $this->errors[] = [
                'error' => 'no_app_page_model',
                'description' => 'Application does not support HTML pages',
            ];
            return;
        }
        $pages_model = new $class_name();
        if ($page_id) {
            $page = $pages_model->getById($page_id);
        }
        if (empty($page)) {
            $this->errors[] = [
                'error' => 'page_not_found',
                'description' => 'Page with this id does not exist',
            ];
            return;
        }

        $page['name'] = sprintf_wp('%s copy', $page['name']);
        $page['url'] = rtrim($page['url'], '/').'copy/';
        $page['full_url'] = rtrim($page['full_url'], '/').'copy/';;
        $page['update_datetime'] = $page['create_datetime'] = date('Y-m-d H:i:s');
        $page['create_contact_id'] = wa()->getUser()->getId();
        $page['status'] = 0;
        unset($page['id']);

        $new_page_id = $pages_model->insert($page);
        $params = $pages_model->getParams($page_id);
        $pages_model->setParams($new_page_id, $params);

        $this->response = [
            'id' => $new_page_id,
        ];
    }

    protected static function formatError(Throwable $e)
    {
        $result = [
            'error' => 'server_error',
            'description' => $e->getMessage().' ('.$e->getCode().')',
        ];
        if (waSystemConfig::isDebug()) {
            $result['stack'] = $e instanceof waException ? $e->getFullTraceAsString() : $e->getTraceAsString();
        }
        return $result;
    }
}
