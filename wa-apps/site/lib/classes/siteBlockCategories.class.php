<?php

/**
 * All categories for Add Block dialog.
 *
 * Each category has a tag assigned. Blocks are put into categories depending on block's tags.
 */
class siteBlockCategories {
    const BLOCK_CATEGORIES_LIMIT = 5;

    public function getAll() {
        $result = [
            [
                'title' => _wd('site', 'Main screen'),
                'tag'   => 'category_main_page',
            ], [
                'title' => _wd('site', 'Video'),
                'tag'   => 'category_video',
            ], [
                'title' => _wd('site', 'Menu'),
                'tag'   => 'category_menu',
            ], [
                'title' => _wd('site', 'Banners'),
                'tag'   => 'category_banners',
            ], [
                'title' => _wd('site', 'Partner logos'),
                'tag'   => 'category_partner_logos',
            ], [
                'title' => _wd('site', 'Products'),
                'tag'   => 'category_products',
            ], [
                'title' => _wd('site', 'Reviews'),
                'tag'   => 'category_reviews',
            ], [
                'title' => _wd('site', 'Advantages'),
                'tag'   => 'category_advantages',
            ], [
                'title' => _wd('site', 'Images with description'),
                'tag'   => 'category_images_with_description',
            ], [
                'title' => _wd('site', 'Gallery'),
                'tag'   => 'category_gallery',
            ], [
                'title' => _wd('site', 'Text'),
                'tag'   => 'category_text',
            ], [
                'title' => _wd('site', 'FAQ'),
                'tag'   => 'category_faq',
            ], [
                'title' => _wd('site', 'Sections and categories'),
                'tag'   => 'category_sections_and_categories',
            ], [
                'title' => _wd('site', 'Call to action'),
                'tag'   => 'category_cta',
            ], [
                'title' => _wd('site', 'Contacts'),
                'tag'   => 'category_contacts',
            ], [
                'title' => _wd('site', 'Site footer'),
                'tag'   => 'category_footer',
            ], [
                'title' => _wd('site', 'Dividers'),
                'tag'   => 'category_dividers',
            ], [
                'title' => _wd('site', 'Custom code'),
                'tag'   => 'category_custom_code',
            ],
        ];

        foreach ($result as &$category) {
            $category['blocks'] = [];
        }
        unset($category);

        return $result;
    }

    public function categorizeBlocks($categories, $blocks, &$uncategorized_blocks) {
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
