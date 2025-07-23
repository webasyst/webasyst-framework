<?php
/**
 * 2.0 WYSIWYG editor for block pages.
 */
class siteEditorAction extends waViewAction
{
    public function execute()
    {
        $page_id = waRequest::param('page_id', null, 'int');
        $blockpage_model = new siteBlockpageModel();
        if (!$page_id) {
            $domain_id = waRequest::param('domain_id', null, 'int');
            if (!$domain_id) {
                throw new waException('page_id is required', 400);
            }
            $pages = $blockpage_model->getByDomain($domain_id);
            if (!$pages) {
                // Page templates are currently not used, but may come back in future
                //$templates = new siteBlockpageTemplates();
                //$templates->createFromDefaultTemplate($domain_id);
                //$pages = $blockpage_model->getByDomain($domain_id);
            }
            $page = reset($pages);
        } else {
            $page = $blockpage_model->getById($page_id);
        }
        if (!$page) {
            throw new waException('Page not found', 404);
        }

        if (!empty($page['final_page_id'])) {
            $draft_page = $page;
            $page = $blockpage_model->getById($page['final_page_id']);
        } else {
            $draft_page = (new siteBlockPage($page))->getDraftPage()->data;
        }

        $this->setLayout(new siteBackendLayout([
            'hide_wa_app_icons' => true,
        ]));

        $page_settings_dialog_html = (new siteMapPageSettingsDialogAction(['page_id' => $draft_page['id']]))->display();

        list($block_data, $block_form_config) = $this->getBlockDataAndConfig($draft_page);

        $this->view->assign([
            'page' => $draft_page,
            'published_page' => $page,
            'domain_id' => $page['domain_id'],
            'block_data' => $block_data,
            'block_form_config' => $block_form_config,
            'domain_root_url' => $this->getDomainRootUrl($page['domain_id']),
            'page_settings_dialog_html' => $page_settings_dialog_html,
            'has_unpublished_changes' => $draft_page['create_datetime'] !== $draft_page['update_datetime'],
            'is_published' => $page['status'] === 'final_published',
            'preview_hash' => siteHelper::getPreviewHash(),
        ]);
    }

    protected function getDomainRootUrl($domain_id)
    {
        $domain = (new siteDomainModel())->getById($domain_id);
        if (empty($domain)) {
            throw new waException('domain not found');
        }

        if (waRequest::isHttps()) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }

        return $protocol.$domain['name'].'/';
    }

    protected function getBlockDataAndConfig($page)
    {
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        $blocks = $blockpage_blocks_model->getByPage($page['id']);
        $block_form_config = [];

        $blockpage_file_model = new siteBlockpageFileModel();
        $files = $blockpage_file_model->getByBlocks(array_keys($blocks));

        foreach($blocks as &$b) {
            try {
                $b['data'] = json_decode($b['data'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $b['data'] = null;
            }

            if (!isset($block_form_config[$b['type']])) {
                try {
                    $block_form_config[$b['type']] = siteBlockType::factory($b['type'])->getBlockSettingsFormConfig();
                } catch (Throwable $e) {
                    // will cause a warning in JS console and refuse to show settings for this block
                    $block_form_config[$b['type']] = false;
                }
            }

            $b['files'] = ifset($files, $b['id'], []);
            foreach($b['files'] as &$f) {
                $f['url'] = siteBlockData::getBlockpageFileUrl($f);
            }
            unset($f);

            $parents = [];
            $bb = $b;
            while (!empty($bb['parent_id']) && !empty($blocks[$bb['parent_id']])) {
                $bb = $blocks[$bb['parent_id']];
                if ($bb['type'] != 'site.VerticalSequence') {
                    $parents[] = [
                        'id' => $bb['id'],
                        'type_name' => ifset($block_form_config, $bb['type'], 'type_name', $bb['type']),
                    ];
                }
            }
            if (!empty($bb['parent_id'])) {
                unset($blocks[$bb['id']]);
                continue;
            }

            $b = array_intersect_key($b, [
                'id' => 0,
                'parent_id' => 0,
                //'child_key' => 0,
                //'sort' => 0,
                'type' => 0,
                'data' => 0,
                'files' => 0,
            ]);
            $b['parents'] = $parents;
        }
        unset($b);
        return [$blocks, $block_form_config];
    }
}
