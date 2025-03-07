<?php
/**
 * All categories for Add Block dialog.
 *
 * Each category has a tag assigned. Blocks are put into categories depending on block's tags.
 */
class siteBlockCategories
{
    const BLOCK_CATEGORIES_LIMIT = 3;

    public function getAll()
    {
        $result = [
            [
                'title' => _wd('site', 'Main screen'),
                'tag' => 'category_main_page',
            ], [
                'title' => _wd('site', 'Menu'),
                'tag' => 'category_menu',
            ], [
                'title' => _wd('site', 'Advantages'),
                'tag' => 'category_advantages',
            ], [
                'title' => _wd('site', 'Reviews'),
                'tag' => 'category_reviews',
            ], [
                'title' => _wd('site', 'Prices'),
                'tag' => 'category_rates',
            ], [
                'title' => _wd('site', 'Contacts'),
                'tag' => 'category_contacts',
            ], [
                'title' => _wd('site', 'Site footer'),
                'tag' => 'category_footer',
            ],
        ];

        foreach ($result as &$category) {
            $category['blocks'] = [];
        }
        unset($category);

        return $result;
    }

    public function categorizeBlocks($categories, $blocks, &$uncategorized_blocks)
    {
        $uncategorized_blocks = [];

        $category_by_tag = [];
        foreach ($categories as &$c) {
            $category_by_tag[$c['tag']] =& $c;
            $c['blocks'] = [];
        }
        unset($c);

        foreach ($blocks as $b) {
            $b['tags'] = ifempty($b, 'tags', []);
            $categories_count = 0;
            foreach ($b['tags'] as $t) {
                if (isset($category_by_tag[$t])) {
                    $category_by_tag[$t]['blocks'][] = $b;
                    $categories_count++;
                    if ($categories_count >= self::BLOCK_CATEGORIES_LIMIT) {
                        break;
                    }
                }
            }
            if ($categories_count <= 0) {
                $uncategorized_blocks[] = $b;
            }
        }

        return $categories;
    }
}
