<?php
/**
 * Represents a page made of blocks.
 *
 */
class siteBlockPage
{
    public $data;

    public function __construct($page)
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

    protected function render($is_backend, $only_block_id = null)
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

        if ($only_block) {
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
                if(!$theme_view->setThemeTemplate($theme, 'blockpage.wrapper.html')) {
                    $theme_view = null;
                }
            }
        }

        // render global block, it recursively takes care of the rest
        $all_blocks_html = $global_block_type->render($global_block_data, $is_backend);

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
        throw new waException(_w('Editing published pages is unavailable.')); // !!! TODO
    }

}
