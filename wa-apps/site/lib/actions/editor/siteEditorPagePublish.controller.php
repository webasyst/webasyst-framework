<?php
/**
 * Allows to publish blockpage, unpublish previously published page
 * or rollback changes, resetting a draft to published state.
 */
class siteEditorPagePublishController extends waJsonController
{
    public function execute()
    {
        $page_id = waRequest::request('id', null, 'int');
        $operation = waRequest::request('operation', 'publish', 'string');

        if (!$page_id) {
            $this->errors = [[
                'code' => 'page_id_required',
                'description' => 'page_id is required',
            ]];
            return;
        }

        if ($operation !== 'unpublish' && !waLicensing::check('site')->hasPremiumLicense()) {
            throw new waException(_w('Only available with the premium license.'));
        }

        $blockpage_model = new siteBlockpageModel();
        $page = $blockpage_model->getById($page_id);
        if (!$page) {
            $this->errors = [[
                'code' => 'page_not_found',
                'description' => 'Page with diven id not found on server.',
            ]];
            return;
        }
        if ($page['final_page_id']) {
            $draft_page = $page;
            $page = $blockpage_model->getById($draft_page['final_page_id']);
        } else {
            $draft_page = (new siteBlockPage($page))->getDraftPage()->data;
        }

        $datetime_now = date('Y-m-d H:i:s');

        switch ($operation) {
            case 'publish':
                if ($page['status'] === 'final_unpublished') {
                    $blockpage_model->updateById($page['id'], [
                        'update_datetime' => $datetime_now,
                        'status' => 'final_published',
                    ]);
                } else {
                    $blockpage_model->updateById($page['id'], [
                        'url' => $draft_page['url'],
                        'full_url' => $draft_page['full_url'],
                        'update_datetime' => $datetime_now,
                        'theme' => $draft_page['theme'],
                        'title' => $draft_page['title'],
                        'name' => $draft_page['name'],
                    ]);

                    if ($draft_page['id'] != $page['id']) {
                        $blockpage_model->cleanupPage($page['id']);
                        $blockpage_model->copyContents($draft_page['id'], $page['id']);
                        $blockpage_model->updateById($draft_page['id'], [
                            'create_datetime' => $datetime_now,
                            'update_datetime' => $datetime_now,
                        ]);
                    }
                }
                break;

            case 'unpublish':
                if ($page['status'] !== 'final_published') {
                    $this->errors = [[
                        'code' => 'page_not_published',
                        'description' => 'Given page is not published',
                    ]];
                    return;
                }
                $blockpage_model->updateById($page['id'], [
                    'update_datetime' => $datetime_now,
                    'status' => 'final_unpublished',
                ]);
                if ($draft_page['id'] != $page['id']) {
                    $blockpage_model->cleanupPage($page['id']);
                    $blockpage_model->copyContents($draft_page['id'], $page['id']);
                    $blockpage_model->deleteById($draft_page['id']);
                    unset($draft_page);
                }
                break;

            case 'rollback':
                if ($draft_page['id'] != $page['id']) {
                    $blockpage_model->cleanupPage($draft_page['id']);
                    $blockpage_model->copyContents($page['id'], $draft_page['id']);
                    $blockpage_model->updateById($draft_page['id'], [
                        'url' => $page['url'],
                        'full_url' => $page['full_url'],
                        'create_datetime' => $datetime_now,
                        'update_datetime' => $datetime_now,
                        'theme' => $page['theme'],
                        'title' => $page['title'],
                        'name' => $page['name'],
                    ]);
                }
                break;

            default:
                $this->errors = [[
                    'code' => 'unknown_operation',
                    'description' => 'operation must be one of: publish (default), unpublish, rollback',
                ]];
                return;
        }

        $this->response = [
            'page_id' => $page['id'],
            'draft_page_id' => ifset($draft_page, 'id', null),
        ];
    }
}
