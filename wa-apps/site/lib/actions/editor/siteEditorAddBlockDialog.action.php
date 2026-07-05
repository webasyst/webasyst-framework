<?php
/**
 * HTML for dialog that selects a block to add to a block page
 */
class siteEditorAddBlockDialogAction extends waViewAction
{
    protected $library;

    public function __construct($params = null)
    {
        parent::__construct($params);
        $this->library = siteBlockpageLibrary::getInstance();
    }

    public function execute()
    {
        $parent_block_id = waRequest::request('parent_block_id', null, 'int');
        $before_block_id = waRequest::request('before_block_id', null, 'int');
        $after_block_id = waRequest::request('after_block_id', null, 'int');

        list($parent_block, $page_id) = self::getParentBlockFromParams($parent_block_id, $before_block_id, $after_block_id);
        if (empty($parent_block)) {
            if (empty($page_id)) {
                $page_id = waRequest::request('page_id', null, 'int');
            }
            if ($page_id) {
                $parent_page = (new siteBlockpageModel())->getById($page_id);
            }
        }

        if (!empty($parent_block)) {
            $insert_place_params = [
                'parent_block_id' => $parent_block['id'],
                'before_block_id' => $before_block_id,
                'after_block_id' => $after_block_id,
            ];
        } else if (!empty($parent_page)) {
            $insert_place_params = [
                'page_id' => $page_id,
                'before_block_id' => $before_block_id,
                'after_block_id' => $after_block_id,
            ];
        } else {
            throw new waException('bad paramenets', 400);
        }

        $this->view->assign([
            'library' => $this->getLibraryContents($parent_block),
            'templates' => $this->getPageTemplates($parent_block),
            'insert_place_params' => $insert_place_params,
            'is_premium' => waLicensing::check('site')->isPremium(),
            'domain_id' => siteHelper::getDomainId(),
        ]);
    }

    /* used in siteEditorAddBlockController */
    public static function getParentBlockFromParams($parent_block_id, $before_block_id, $after_block_id)
    {
        $blockpage_blocks_model = new siteBlockpageBlocksModel();
        if ($before_block_id) {
            $b = $blockpage_blocks_model->getById($before_block_id);
        } else if ($after_block_id) {
            $b = $blockpage_blocks_model->getById($after_block_id);
        }
        if (!empty($b)) {
            if ($b['parent_id']) {
                return [$blockpage_blocks_model->getById($b['parent_id']), $b['page_id']];
            } else {
                return [null, $b['page_id']];
            }
        }
        if ($parent_block_id) {
            $parent_block = $blockpage_blocks_model->getById($parent_block_id);
            return [$parent_block, $parent_block['page_id']];
        }
    }

    /** overriden in siteEditorAddElementsListAction */
    protected function getLibraryContents($parent_block)
    {
        $sorter = new siteBlockCategories();
        $categories = $sorter->getBlockCategories();

        $blocks = $this->library->getAllBlocks();
        $blocks = array_filter($blocks, function($b) {
            return !in_array('element', $b['tags']) && !in_array('template', $b['tags']);
        });

        $uncategorized_blocks = null;
        $categories = $sorter->categorizeBlocks($categories, $blocks, $uncategorized_blocks);
        $categories[] = [
            'title' => '',
            'blocks' => $uncategorized_blocks,
        ];

        $categories = array_filter($categories, function($c) {
            return !empty($c['blocks']);
        });

        return $categories;
    }

    /** overriden in siteEditorAddElementsListAction */
    protected function getPageTemplates($parent_block): array
    {
        // Page templates are only shown when page is empty
        if (!empty($parent_block)) {
            return [];
        }

        $sorter = new siteBlockCategories();
        $categories = $sorter->getPageTemplatesCategories();

        $blocks = $this->library->getAllBlocks();
        $blocks = array_filter($blocks, function($b) {
            return in_array('template', $b['tags']);
        });

        $categories = $sorter->categorizeBlocks($categories, $blocks, $uncategorized_blocks);
        $categories = array_filter($categories, function($c) {
            return !empty($c['blocks']);
        });

        return $categories;
    }
}
