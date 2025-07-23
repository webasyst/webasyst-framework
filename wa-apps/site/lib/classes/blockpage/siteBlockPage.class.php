<?php
/**
 * Represents a page made of blocks.
 *
 */
class siteBlockPage
{
    public $data;
    public $params;

    public function __construct($page, $params=null)
    {
        if (is_scalar($page)) {
            $blockpage_model = new siteBlockpageModel();
            $page = $blockpage_model->getById($page);
        }
        if (is_array($page)) {
            $this->data = $page;
        }
        if (empty($this->data['id']) || empty($this->data['status'])) {
            throw new waException('Bad page data');
        }
        if ($params === null) {
            $blockpage_params_model = new siteBlockpageParamsModel();
            $params = $blockpage_params_model->getById($page['id']);
        }
        $this->params = $params;
    }

    public function getId()
    {
        return $this->data['id'];
    }

    public function renderFrontend()
    {
        return $this->render(false);
    }

    public function renderBackend($only_block_id = null)
    {
        if ($this->data['status'] === 'final_published') {
            return $this->getDraftPage()->render(true, $only_block_id);
        }
        return $this->render(true, $only_block_id);
    }

    public function prepareBlocksForRender($is_backend, $only_block_id = null)
    {
        // Get all page blocks
        $blocks = [];
        $block_types = [
            'site.VerticalSequence' => new siteVerticalSequenceBlockType(),
        ];
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        $rows = $blockpage_blocks_model->getByPage($this->getId());
        foreach($rows as $row) {
            $block_type = ifset($block_types, $row['type'], null);
            if ($block_type === null) {
                try {
                    $block_type = $block_types[$row['type']] = siteBlockType::factory($row['type']);
                //} catch (siteUnknownBlockTypeException $e) {
                //    $block_types[$row['type']] = false;
                } catch (Throwable $e) {
                    $block_type = $block_types[$row['type']] = new siteBrokenBlockType($row['type'], $e->getMessage()." (".get_class($e)." ".$e->getCode().")\n".$e->getTraceAsString());
                }
            }
            if (!$block_type) {
                continue; // ignore blocks with unknown or broken type
            }

            $blocks[$row['id']] = $block_type->getEmptyBlockData()->setDbRow($row);
        }

        // Get all block files
        $blockpage_file_model = new siteBlockpageFileModel();
        $block_files = $blockpage_file_model->getByBlocks(array_keys($blocks));
        foreach($block_files as $block_id => $files) {
            $blocks[$block_id]->setFiles($files);
        }

        // Build tree hierarchy of blocks
        $only_block = null;
        foreach($blocks as $id => $block) {
            $parent_id = $block->db_row['parent_id'];
            if ($only_block_id && $only_block_id == $id) {
                $only_block = $block;
            }
            if ($parent_id) {
                if (!isset($blocks[$parent_id])) {
                    continue;
                }
                $blocks[$parent_id]->addChild($block);
            }
        }

        if ($only_block_id) {
            if (!$only_block) {
                throw new waException('no block id='.htmlspecialchars($only_block_id).' on page');
            }
            $blocks = [$only_block_id => $only_block];
            $block_types = $this->getAllBlockTypesFromData($only_block);
        } else {
            $blocks = array_filter($blocks, function($b) {
                return empty($b->db_row['parent_id']);
            });
        }

        return [$blocks, $block_types];
    }

    protected function render($is_backend, $only_block_id = null)
    {
        list($blocks, $block_types) = $this->prepareBlocksForRender($is_backend, $only_block_id);
        if ($only_block_id) {
            $only_block = reset($blocks);
        }

        // Prerender all block types, gather global assets.
        $global_js = [];
        $global_css = [];
        $global_html = [];
        foreach($block_types as $type => $block_type) {
            // !!! TODO: Substitute styles, scripts and/or templates for block types if required by design theme.
            try {
                $global_js[$type] = $block_type->getGlobalJS($is_backend);
                $global_css[$type] = $block_type->getGlobalCSS($is_backend);
                $global_html[$type] = $block_type->prerender($is_backend);
            } catch (Throwable $e) {
                $block_type = $block_types[$type] = new siteBrokenBlockType($row['type'], $e->getMessage()." (".get_class($e)." ".$e->getCode().")\n".$e->getTraceAsString());
                $global_js[$type] = [];
                $global_css[$type] = [];
                $global_html[$type] = '';
            }
        }

        $page_locale = ifset($this->params, 'locale', null);
        if ($page_locale && wa()->getEnv() != 'backend') {
            wa()->setLocale($page_locale);
        }

        if (!empty($only_block)) {
            $global_block_type = $only_block->block_type;
            $global_block_data = $only_block;
        } else {
            // wrap blocks into a sequence class
            $global_block_type = $block_types['site.VerticalSequence'];
            $global_block_data = $global_block_type->getEmptyBlockData()->addChildren($blocks)->setDbRow([
                'page_id' => $this->getId(),
            ]);

            // Render theme styles and scripts
            if ($this->data['theme']) {
                $theme = new waTheme($this->data['theme'], 'site');
                $theme_view = new siteEditorView(wa('site'));
                if($theme_view->setThemeTemplate($theme, 'blockpage.wrapper.html')) {
                    waRequest::setParam('theme', $this->data['theme']);
                } else {
                    $theme_view = null;
                }
            }
        }

        // render global block, it recursively takes care of the rest
        $all_blocks_html = $global_block_type->render($global_block_data->ensureAdditionalData(), $is_backend);

        if ($is_backend) {
            // Editor-specific data for JS: block settings forms, breadcrumbs
            $all_blocks_html .= $this->renderScriptUpdateEditorData($global_block_data);
        }

        $res = $this->formatGlobalJS($global_js)."\n".
               $this->formatGlobalCSS($global_css)."\n\n".
               $this->formatGlobalHTML($global_html)."\n\n".
               $all_blocks_html;
       if (!empty($theme_view)) {
           $theme_view->assign([
               'content' => $res,
           ]);
           $res = $theme_view->fetch('blockpage.wrapper.html');
       }
       return $res;
    }

    // This JS script updates block data inside backend editor after new block has been added to page and whole block is reloaded
    protected function renderScriptUpdateEditorData($block_data, $wrap_with_script=true)
    {
        $c = $block_data;
        $result = '$.wa.editor.updateBlockSettingsFormConfig('.
            waUtils::jsonEncode($c->getId()).', '.
            waUtils::jsonEncode($c->block_type->getBlockSettingsFormConfig(), JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE).' || {}'.
        ");\n";
        $result .= '$.wa.editor.updateBlockData('.
            waUtils::jsonEncode($c->getId()).', '.
            waUtils::jsonEncode($c->data, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE).' || {}, '.
            waUtils::jsonEncode(ifset($c->db_row, 'parent_id', null)).
        ");\n";

        foreach ($c->files as $file_key => $file) {
            $result .= '$.wa.editor.updateBlockFile('.
                waUtils::jsonEncode($c->getId()).', '.
                waUtils::jsonEncode($file_key).', '.
                waUtils::jsonEncode($file, JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE).' || {}'.
            ");\n";
        }

        foreach($c->children as $child_key => $arr) {
            foreach($arr as $child) {
                $result .= $this->renderScriptUpdateEditorData($child, false);
            }
        }

        if ($wrap_with_script) {
            $result = "<script executor>$(()=>setTimeout(()=>{\n{$result}\n$('script[executor]').remove();},0));</script>";
        }
        return $result;
    }

    protected function getAllBlockTypesFromData(siteBlockData $data)
    {
        $block_types = [
            $data->block_type->getTypeId() => $data->block_type,
        ];
        foreach($data->children as $child_key => $arr) {
            foreach($arr as $child) {
                $block_types += $this->getAllBlockTypesFromData($child);
            }
        }
        return $block_types;
    }

    protected function filterFiles($global_js)
    {
        $result = [];
        $root_url = wa()->getRootUrl(false);
        foreach($global_js as $type => $files) {
            foreach($files as $filepath) {
                $result[$filepath] = wa()->getCdn($root_url.$filepath);
            }
        }
        return $result;
    }

    protected function formatGlobalJS($global_js)
    {
        $result = [];
        $files = $this->filterFiles($global_js);
        foreach($files as $url) {
            $result[] = '<script src="'.htmlspecialchars((string)$url).'"></script>';
        }
        return join("\n", $result);
    }

    protected function formatGlobalCSS($global_css)
    {
        $result = [];
        $files = $this->filterFiles($global_css);
        foreach($files as $url) {
            $result[] = '<link rel="stylesheet" href="'.htmlspecialchars((string)$url).'">';
        }
        return join("\n", $result);
    }

    protected function formatGlobalHTML($global_html)
    {
        return join("\n\n", $global_html);
    }

    public function getDraftPage()
    {
        if ($this->data['status'] !== 'final_published') {
            return $this;
        }

        $blockpage_model = new siteBlockpageModel();
        $draft_page = $blockpage_model->getDraftById($this->data['id']);
        if ($draft_page) {
            return new self($draft_page);
        }

        $dt = date('Y-m-d H:i:s');
        $insert_data = [
            'status' => 'draft',
            'final_page_id' => $this->data['id'],
            'create_datetime' => $dt,
            'update_datetime' => $dt,
        ] + $this->data;
        unset($insert_data['id']);
        $insert_data['id'] = $blockpage_model->insert($insert_data);

        $blockpage_model->copyContents($this->data['id'], $insert_data['id']);

        return new self($insert_data);
    }

    public function updateDateTime($datetime=null)
    {
        if (!$datetime) {
            $datetime = date('Y-m-d H:i:s');
        }
        $old_dt = $this->data['update_datetime'];
        $this->data['update_datetime'] = $datetime;

        (new siteBlockpageModel())->updateById($this->data['id'], [
            'update_datetime' => $this->data['update_datetime'],
        ]);

        return [$this->data['update_datetime'], $old_dt];
    }
}
