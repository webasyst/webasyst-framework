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
        $is_new = (bool)waRequest::request('is_new', 0, 'int');

        if (!$page_id && !$is_new) {
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
            return;
        }

        $domain_id = siteHelper::getDomainId();
        $blockpage_model = new siteBlockpageModel();
        $page_data = [];
        if ($page_id) {
            $page_data = $blockpage_model->getById($page_id);
            if (empty($page_data)) {
                throw new waException('Page not found', 404);
            }
            $is_new = false;
        }
        if ($is_new && !waLicensing::check('site')->hasPremiumLicense()) {
            $this->errors[] = [
                'field' => 'info[name]',
                'description' => _w('The premium license is required to create block pages.'),
                'code' => 'required',
            ];
            return;
        }

        $old_url = ifset($page_data, 'url', '');
        $old_full_url = ifset($page_data, 'full_url', '');
        if (isset($data['url'])) {
            $data['url'] = trim($data['url'], '/');

            // if changed URL
            if ($old_url !== $data['url']) {
                $data['full_url'] = $data['url'];
                if (!empty($data['parent_id'])) {
                    $parent_page = $blockpage_model->getById($data['parent_id']);
                    if (isset($parent_page['full_url'])) {
                        $parent_full_url = ltrim($parent_page['full_url'], '/');
                        $data['full_url'] = $parent_full_url.($parent_full_url ? '/' : '').$data['url'];
                    }
                }
            }

            $full_url = ifset($data, 'full_url', ifset($page_data['full_url'], $data['url']));
            if (($is_new || $old_url !== $data['url']) && $this->checkDupeUrl($blockpage_model, $full_url)) {
                return;
            }

            $parent_id = ifset($page_data, 'parent_id', ifset($data, 'parent_id', null));
            if (waRequest::request('set_main_page')) {
                $main_page = new siteMainPage($domain_id);
                if ($main_page->isAllowedAsMainPage('site', 'blockpage', $page_id)) {
                    $main_page->silenceMainPage();
                    $main_page->setNewMainPage('site', 'blockpage', $page_id);
                    $main_page->saveRoutes();
                } else {
                    $this->errors[] = [
                        'error' => 'unsuitable_main_page',
                        'description' => _w('Unable to set this page or section as the site’s home page.'),
                    ];
                }
            } elseif ($this->shouldCheckUrlOverlap($full_url, $parent_id)) {
                return;
            }
        }

        if ($is_new) {
            $page_id = $blockpage_model->createUnpublishedPage($domain_id, $data);
        }

        $data['update_datetime'] = date('Y-m-d H:i:s');

        $blockpage_params_model = new siteBlockpageParamsModel();
        if (isset($data['params']) && is_array($data['params'])) {
            $blockpage_params_model->save($page_id, $data['params']);
            if (!$is_new && !empty($page_data['final_page_id'])) {
                $blockpage_params_model->save($page_data['final_page_id'], $data['params']);
            }
        }
        unset($data['params']);

        if (!$this->errors) {
            $blockpage_model->updateById($page_id, $data);

            if (!$is_new) {
                if (!empty($page_data['final_page_id'])) {
                    // Saving draft page immediately udates published page, too
                    $final_page_id = $page_data['final_page_id'];
                    $final_page_data = $blockpage_model->getById($final_page_id);
                    $blockpage_model->updateById($page_data['final_page_id'], $data);
                    $old_url = $final_page_data['url'];
                } else {
                    // Saving published page updates its draft, too
                    $final_page_id = $page_id;
                    $final_page_data = $page_data;
                    $blockpage_model->updateByField([
                        'final_page_id' => $final_page_id,
                    ], $data);
                }

                if (isset($data['full_url']) && $old_full_url !== $data['full_url']) {
                    $child_ids = $blockpage_model->getChildIds($final_page_id);
                    if ($child_ids) {
                        $blockpage_model->updateFullUrl($child_ids, $data['full_url'], $old_full_url);
                    }
                }
            }

            $this->response = $blockpage_model->getById($page_id);
            $this->response['params'] = $blockpage_params_model->getById($page_id);
            $this->response['add'] = $is_new;

            try {
                wa('site')->getConfig()->ensureSettlementForDomain($domain_id, true);
            } catch (Throwable $e) {
            }
        }
    }

    private function checkDupeUrl(siteBlockpageModel $blockpage_model, $full_url)
    {
        if ($blockpage_model->countByField([
                'domain_id' => siteHelper::getDomainId(),
                'full_url' => $full_url,
                'final_page_id' => null
        ]) > 0) {
            $this->errors[] = _w('The specified URL already exists.');
            return true;
        }

        return false;
    }
    private function shouldCheckUrlOverlap($full_url, $parent_id)
    {
        if (waRequest::request('url_overlap')) {
            return false;
        }
        if (siteHelper::blockpageHasUrlOverlap($full_url, $parent_id)) {
            $this->errors[] = [
                'field' => 'info[url]',
                'description' => _w('The URL exists.'),
                'code' => 'required',
                'bottom' => true
            ];
            return true;
        }

        return false;
    }
}
