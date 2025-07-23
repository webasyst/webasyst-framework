<?php

/**
 * Access to all blocks available for blockpages to use.
 */
class siteBlockpageLibrary {
    public $all_blocks = null;
    public $all_elements = null;

    public function getById($id) {
        $blocks = array_merge($this->getAllBlocks(), $this->getAllElements());
        return ifset($blocks, $id, null);
    }

    public function getByTypeName($id) {
        $blocks = array_merge($this->getAllBlocks(), $this->getAllElements());
        foreach ($blocks as $key => $val) {
            if (strpos($key, $id) !== false) {
                return $val;
            }
        }
        return null;
    }

    public function getAllBlocks() {
        if ($this->all_blocks !== null) {
            return $this->all_blocks;
        }

        $blocks = $this->getSiteBlocks();

        $blocks = array_merge(array_values($blocks), $this->getThirdPartyBlocks());

        $result = [];
        foreach ($blocks as $b) {
            if (empty($b['data'])) {
                continue; // should not happen
            }
            if (empty($b['id']) || isset($result[$b['id']])) {
                $b['id'] = $this->hashBlockId($b['data']);
            }
            $b['tags'] = ifempty($b, 'tags', []);
            $result[$b['id']] = $b;
        }
        $this->all_blocks = $result;

        return $result;
    }

    /**
     * @deprecated !!! TODO remove
     */
    public function getAllElements(string $is_complex = '') {
        if ($this->all_elements !== null) {
            return $this->all_elements;
        }

        $blocks = $this->getAllBlocks();
        $blocks = array_filter($blocks, function ($b) {
            return in_array('element', $b['tags']);
        });

        $result = [];
        foreach ($blocks as $b) {
            if (empty($b['data'])) {
                continue; // should not happen
            }
            if ($is_complex) {
                $has_tag_complex_column = in_array('complex-column', $b['tags']);
                if ($is_complex == 'only_columns' && !$has_tag_complex_column) {
                    continue; // skip all except column elements
                }
                if ($is_complex == 'with_row' && $has_tag_complex_column) {
                    continue; // skip all complex elements except row
                }
                $has_tag_complex_row = in_array('complex-row', $b['tags']);
                if ($is_complex == 'no_complex' && ($has_tag_complex_row || $has_tag_complex_column)) {
                    continue; // skip all complex elements
                }
            }

            if (empty($b['id']) || isset($result[$b['id']])) {
                $b['id'] = $this->hashBlockId($b['data']);
            }
            $result[$b['id']] = $b;
        }
        $this->all_elements = $result;

        return $result;
    }

    protected function getSiteBlocks() {
        $img_url = wa()->getAppStaticUrl('site') . 'img/blocks/';
        return [
            [
                'image'    => $img_url . 'footer/footer-8-dark.png',
                'image_2x' => $img_url . 'footer/footer-8-dark@2x.png',
                'title'    => _w('Site footer top block'),
                'data'     => (new siteFooterTopBlockType(['columns' => 4]))->getExampleBlockData(),
                'tags'     => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image'    => $img_url . 'footer/footer-8-light.png',
                'image_2x' => $img_url . 'footer/footer-8-light@2x.png',
                'title'    => _w('Site footer top block'),
                'data'     => (new siteFooterTop2BlockType(['columns' => 4]))->getExampleBlockData(),
                'tags'     => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image'    => $img_url . 'footer/footer-4.jpg',
                'image_2x' => $img_url . 'footer/footer-4@2x.jpg',
                'title'    => _w('Site footer bottom block'),
                'data'     => (new siteFooterBottom2BlockType())->getExampleBlockData(),
                'tags'     => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image'    => $img_url . 'footer/footer-3.jpg',
                'image_2x' => $img_url . 'footer/footer-3@2x.jpg',
                'title'    => _w('Site footer bottom block'),
                'data'     => (new siteFooterBottomBlockType())->getExampleBlockData(),
                'tags'     => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image'    => $img_url . 'footer/footer-9-dark.png',
                'image_2x' => $img_url . 'footer/footer-9-dark@2x.png',
                'title'    => _w('Site footer bottom block'),
                'data'     => (new siteFooterBottom3BlockType())->getExampleBlockData(),
                'tags'     => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image'    => $img_url . 'footer/footer-9-light.png',
                'image_2x' => $img_url . 'footer/footer-9-light@2x.png',
                'title'    => _w('Site footer bottom block'),
                'data'     => (new siteFooterBottom4BlockType())->getExampleBlockData(),
                'tags'     => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image'    => $img_url . 'footer/footer-5.png',
                'image_2x' => $img_url . 'footer/footer-5@2x.png',
                'title'    => _w('Site footer bottom block'),
                'data'     => (new siteFooterBottom5BlockType())->getExampleBlockData(),
                'tags'     => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image'    => $img_url . 'footer/footer-6.png',
                'image_2x' => $img_url . 'footer/footer-6@2x.png',
                'title'    => _w('Site footer bottom block'),
                'data'     => (new siteFooterBottom6BlockType())->getExampleBlockData(),
                'tags'     => ['category_footer'],
                'disabled' => false,
            ],
            [
                'image'    => $img_url . 'hero/block-1-cover.jpg',
                'image_2x' => $img_url . 'hero/block-1-cover@2x.jpg',
                'title'    => _w('Hero'),
                'data'     => (new siteCustomHeroBlockType())->getExampleBlockData(),
                'tags'     => ['category_main_page'],
            ],
            [
                'image'    => $img_url . 'hero/hero.jpg',
                'image_2x' => $img_url . 'hero/hero@2x.jpg',
                'title'    => _w('Hero 2'),
                'data'     => (new siteCustomHero2BlockType())->getExampleBlockData(),
                'tags'     => ['category_main_page'],
            ],
            [
                'image'    => $img_url . 'hero/main-screen-2.jpg',
                'image_2x' => $img_url . 'hero/main-screen-2@2x.jpg',
                'title'    => _w('Hero 3'),
                'data'     => (new siteCustomHero3BlockType())->getExampleBlockData(),
                'tags'     => ['category_banners'],
            ],
            [
                'image'    => $img_url . 'hero/sandals.jpg',
                'image_2x' => $img_url . 'hero/sandals@2x.jpg',
                'title'    => _w('Hero 4'),
                'data'     => (new siteCustomHero4BlockType())->getExampleBlockData(),
                'tags'     => ['category_main_page'],
            ],
            [
                'image'    => $img_url . 'cta/cta.jpg',
                'image_2x' => $img_url . 'cta/cta@2x.jpg',
                'title'    => _w('Call to action'),
                'data'     => (new siteCustomCtaBlockType())->getExampleBlockData(),
                'tags'     => ['category_cta'],
            ],
            [
                'image'    => $img_url . 'cta/cta-contacts.png',
                'image_2x' => $img_url . 'cta/cta-contacts@2x.png',
                'title'    => _w('Call to action 2'),
                'data'     => (new siteCustomCta2BlockType())->getExampleBlockData(),
                'tags'     => ['category_cta'],
            ],
            /*[
                'image' => '',
                'title' => _w('Two columns'),
                'data'  => (new siteColumnsBlockType(['columns' => 2]))->getExampleBlockData(),
                'tags'  => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('One column'),
                'data'  => (new siteColumnsBlockType(['columns' => 1]))->getExampleBlockData(),
                'tags'  => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Two columns'),
                'data'  => (new siteColumnsBlockType(['columns' => 2]))->getExampleBlockData(),
                'tags'  => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Three columns'),
                'data'  => (new siteColumnsBlockType(['columns' => 3]))->getExampleBlockData(),
                'tags'  => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Four columns'),
                'data'  => (new siteColumnsBlockType(['columns' => 4]))->getExampleBlockData(),
                'tags'  => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Cards'),
                'data'  => (new siteCardsBlockType(['cards' => 7]))->getExampleBlockData(),
                'tags'  => ['category_main_page'],
            ],
            [
                'image' => '',
                'title' => _w('Menu'),
                'data'  => (new siteMenuBlockType(['columns' => 4]))->getExampleBlockData(),
                'tags'  => ['category_menu'],
            ],*/
            [
                'image'    => $img_url . 'menu/menu-1.png',
                'image_2x' => $img_url . 'menu/menu-1@2x.png',
                'title' => _w('Menu type 1'),
                'data'  => (new siteMenuT1BlockType(['columns' => 3]))->getExampleBlockData(),
                'tags'  => ['category_menu'],
            ],
            [
                'image'    => $img_url . 'menu/menu-2.png',
                'image_2x' => $img_url . 'menu/menu-2@2x.png',
                'title' => _w('Menu type 2'),
                'data'  => (new siteMenuT3BlockType(['columns' => 4]))->getExampleBlockData(),
                'tags'  => ['category_menu'],
            ],
            [
                'image'    => $img_url . 'menu/menu-3.png',
                'image_2x' => $img_url . 'menu/menu-3@2x.png',
                'title' => _w('Menu type 3'),
                'data'  => (new siteMenuT2BlockType(['columns' => 3]))->getExampleBlockData(),
                'tags'  => ['category_menu'],
            ],
            [
                'image'    => $img_url . 'menu/menu-4.png',
                'image_2x' => $img_url . 'menu/menu-4@2x.png',
                'title' => _w('Menu type 4'),
                'data'  => (new siteMenuT4BlockType(['columns' => 4]))->getExampleBlockData(),
                'tags'  => ['category_menu'],
            ],
            [
                'image' => '',
                'icon'  => 'code',
                'title' => _w('Custom code'),
                'data'  => (new siteCustomCodeBlockType(['is_block' => true]))->getExampleBlockData(),
                'tags'  => ['category_custom_code'],
            ],
            [
                'image'    => $img_url . 'categories/categories.jpg',
                'image_2x' => $img_url . 'categories/categories@2x.jpg',
                'title'    => _w('Categories'),
                'data'     => (new siteCustomCategoriesBlockType())->getExampleBlockData(),
                'tags'     => ['category_sections_and_categories'],
            ],
            [
                'image'    => $img_url . 'categories/categories2.jpg',
                'image_2x' => $img_url . 'categories/categories2@2x.jpg',
                'title'    => _w('Categories 2'),
                'data'     => (new siteCustomCategories2BlockType())->getExampleBlockData(),
                'tags'     => ['category_sections_and_categories'],
            ],
            [
                'image'    => $img_url . 'categories/group-of-buttons.png',
                'image_2x' => $img_url . 'categories/group-of-buttons@2x.png',
                'title'    => _w('Categories 3'),
                'data'     => (new siteCustomCategories3BlockType())->getExampleBlockData(),
                'tags'     => ['category_sections_and_categories'],
            ],
            [
                'image'    => $img_url . 'categories/categories4.jpg',
                'image_2x' => $img_url . 'categories/categories4@2x.jpg',
                'title'    => _w('Categories 4'),
                'data'     => (new siteCustomCategories4BlockType())->getExampleBlockData(),
                'tags'     => ['category_sections_and_categories'],
            ],
            [
                'image'    => $img_url . 'products/products.jpg',
                'image_2x' => $img_url . 'products/products@2x.jpg',
                'title'    => _w('Products'),
                'data'     => (new siteCustomProductsBlockType())->getExampleBlockData(),
                'tags'     => ['category_products'],
            ],
            [
                'image'    => $img_url . 'products/3-products.jpg',
                'image_2x' => $img_url . 'products/3-products@2x.jpg',
                'title'    => _w('Products 3'),
                'data'     => (new siteCustomProducts3BlockType())->getExampleBlockData(),
                'tags'     => ['category_products'],
            ],
            [
                'image'    => $img_url . 'products/2-products.jpg',
                'image_2x' => $img_url . 'products/2-products@2x.jpg',
                'title'    => _w('Products 2'),
                'data'     => (new siteCustomProducts2BlockType())->getExampleBlockData(),
                'tags'     => ['category_products'],
            ],
            [
                'image'    => $img_url . 'reviews/reviews.jpg',
                'image_2x' => $img_url . 'reviews/reviews@2x.jpg',
                'title'    => _w('Reviews'),
                'data'     => (new siteCustomReviewsBlockType())->getExampleBlockData(),
                'tags'     => ['category_reviews'],
            ],
            [
                'image'    => $img_url . 'reviews/reviews2.jpg',
                'image_2x' => $img_url . 'reviews/reviews2@2x.jpg',
                'title'    => _w('Reviews 2'),
                'data'     => (new siteCustomReviews2BlockType())->getExampleBlockData(),
                'tags'     => ['category_reviews'],
            ],
            [
                'image'    => $img_url . 'faq/faq.jpg',
                'image_2x' => $img_url . 'faq/faq@2x.jpg',
                'title'    => _w('FAQ'),
                'data'     => (new siteCustomFaqBlockType())->getExampleBlockData(),
                'tags'     => ['category_faq'],
            ],
            [
                'image'    => $img_url . 'faq/faq-1-cl.png',
                'image_2x' => $img_url . 'faq/faq-1-cl@2x.png',
                'title'    => _w('FAQ 1'),
                'data'     => (new siteCustomFaq1BlockType())->getExampleBlockData(),
                'tags'     => ['category_faq'],
            ],
            [
                'image'    => $img_url . 'banners/banner.jpg',
                'image_2x' => $img_url . 'banners/banner@2x.jpg',
                'title'    => _w('Banner'),
                'data'     => (new siteCustomBannerBlockType())->getExampleBlockData(),
                'tags'     => ['category_banners'],
            ],
            [
                'image'    => $img_url . 'banners/banner-2.jpg',
                'image_2x' => $img_url . 'banners/banner-2@2x.jpg',
                'title'    => _w('Banner 2'),
                'data'     => (new siteCustomBanner2BlockType())->getExampleBlockData(),
                'tags'     => ['category_banners'],
            ],
            [
                'image'    => $img_url . 'banners/promo-bar.png',
                'image_2x' => $img_url . 'banners/promo-bar@2x.png',
                'title'    => _w('Banner 3'),
                'data'     => (new siteCustomBanner3BlockType())->getExampleBlockData(),
                'tags'     => ['category_banners'],
            ],
            [
                'image'    => $img_url . 'banners/sales-bar.png',
                'image_2x' => $img_url . 'banners/sales-bar@2x.png',
                'title'    => _w('Banner 4'),
                'data'     => (new siteCustomBanner4BlockType())->getExampleBlockData(),
                'tags'     => ['category_banners'],
            ],
            [
                'image'    => $img_url . 'text/1-column-text.jpg',
                'image_2x' => $img_url . 'text/1-column-text@2x.jpg',
                'title'    => _w('Text'),
                'data'     => (new siteCustomTextBlockType())->getExampleBlockData(),
                'tags'     => ['category_text'],
            ],
            [
                'image'    => $img_url . 'text/text-2-columns.jpg',
                'image_2x' => $img_url . 'text/text-2-columns@2x.jpg',
                'title'    => _w('Text 2'),
                'data'     => (new siteCustomText2BlockType())->getExampleBlockData(),
                'tags'     => ['category_text'],
            ],
            [
                'image'    => $img_url . 'images_wd/guide.jpg',
                'image_2x' => $img_url . 'images_wd/guide@2x.jpg',
                'title'    => _w('Images with description'),
                'data'     => (new siteCustomImagesWithDescriptionBlockType())->getExampleBlockData(),
                'tags'     => ['category_images_with_description'],
            ],
            [
                'image'    => $img_url . 'images_wd/package.jpg',
                'image_2x' => $img_url . 'images_wd/package@2x.jpg',
                'title'    => _w('Images with description 2'),
                'data'     => (new siteCustomImagesWithDescription2BlockType())->getExampleBlockData(),
                'tags'     => ['category_images_with_description'],
            ],
            [
                'image'    => $img_url . 'images_wd/youth-cover.jpg',
                'image_2x' => $img_url . 'images_wd/youth-cover@2x.jpg',
                'title'    => _w('Images with description 3'),
                'data'     => (new siteCustomImagesWithDescription3BlockType())->getExampleBlockData(),
                'tags'     => ['category_images_with_description'],
            ],
            [
                'image'    => $img_url . 'images_wd/baker-cover.jpg',
                'image_2x' => $img_url . 'images_wd/baker-cover@2x.jpg',
                'title'    => _w('Images with description 4'),
                'data'     => (new siteCustomImagesWithDescription4BlockType())->getExampleBlockData(),
                'tags'     => ['category_images_with_description'],
            ],
            [
                'image'    => $img_url . 'contacts.jpg',
                'image_2x' => $img_url . 'contacts@2x.jpg',
                'title'    => _w('Contacts'),
                'data'     => (new siteCustomContactsBlockType())->getExampleBlockData(),
                'tags'     => ['category_contacts'],
            ],
            [
                'image'    => $img_url . 'facts.jpg',
                'image_2x' => $img_url . 'facts@2x.jpg',
                'title'    => _w('Facts'),
                'data'     => (new siteCustomFactsBlockType())->getExampleBlockData(),
                'tags'     => ['category_advantages'],
            ],
            [
                'image'    => $img_url . 'advantages.jpg',
                'image_2x' => $img_url . 'advantages@2x.jpg',
                'title'    => _w('Advantages'),
                'data'     => (new siteCustomAdvantagesBlockType())->getExampleBlockData(),
                'tags'     => ['category_advantages'],
            ],
            [
                'image'    => $img_url . 'logos/logos.jpg',
                'image_2x' => $img_url . 'logos/logos@2x.jpg',
                'title'    => _w('Partner logos'),
                'data'     => (new siteCustomPartnerlogosBlockType())->getExampleBlockData(),
                'tags'     => ['category_partner_logos'],
            ],
            [
                'image'    => $img_url . 'gallery/gallery.jpg',
                'image_2x' => $img_url . 'gallery/gallery@2x.jpg',
                'title'    => _w('Gallery'),
                'data'     => (new siteCustomGalleryBlockType())->getExampleBlockData(),
                'tags'     => ['category_gallery'],
            ],
            [
                'image'    => $img_url . 'dividers/divider-dark.png',
                'image_2x' => $img_url . 'dividers/divider-dark@2x.png',
                'title'    => _w('Divider (dark)'),
                'data'     => (new siteCustomDividerBlockType())->getExampleBlockData(),
                'tags'     => ['category_dividers'],
            ],
            [
                'image'    => $img_url . 'dividers/divider-light.png',
                'image_2x' => $img_url . 'dividers/divider-light@2x.png',
                'title'    => _w('Divider light'),
                'data'     => (new siteCustomDivider2BlockType())->getExampleBlockData(),
                'tags'     => ['category_dividers'],
            ],
            [
                'image'    => $img_url . 'dividers/pattern-top.png',
                'image_2x' => $img_url . 'dividers/pattern-top@2x.png',
                'title'    => _w('Divider (top)'),
                'data'     => (new siteCustomDivider4BlockType())->getExampleBlockData(),
                'tags'     => ['category_dividers'],
            ],
            [
                'image'    => $img_url . 'dividers/pattern-bottom.png',
                'image_2x' => $img_url . 'dividers/pattern-bottom@2x.png',
                'title'    => _w('Divider (bottom)'),
                'data'     => (new siteCustomDivider3BlockType())->getExampleBlockData(),
                'tags'     => ['category_dividers'],
            ],
            [
                'image'    => $img_url . 'dividers/angle-bg-border--top.png',
                'image_2x' => $img_url . 'dividers/angle-bg-border--top@2x.png',
                'title'    => _w('Angle divider (top)'),
                'data'     => (new siteCustomDivider5BlockType())->getExampleBlockData(),
                'tags'     => ['category_dividers'],
            ],
            [
                'image'    => $img_url . 'dividers/angle-bg-border--bottom.png',
                'image_2x' => $img_url . 'dividers/angle-bg-border--bottom@2x.png',
                'title'    => _w('Angle divider (bottom)'),
                'data'     => (new siteCustomDivider6BlockType())->getExampleBlockData(),
                'tags'     => ['category_dividers'],
            ],
            [
                'image'    => $img_url . 'video/video.jpg',
                'image_2x' => $img_url . 'video/video@2x.jpg',
                'title'    => _w('Video'),
                'data'     => (new siteCustomVideoBlockType())->getExampleBlockData(),
                'tags'     => ['category_video'],
            ],
            [
                'image'    => $img_url . 'video/bg-video-sale.jpg',
                'image_2x' => $img_url . 'video/bg-video-sale@2x.jpg',
                'title'    => _w('Video 2'),
                'data'     => (new siteCustomVideo2BlockType())->getExampleBlockData(),
                'tags'     => ['category_video'],
            ],
            [
                'image'    => $img_url . 'video/video-bg-collection.jpg',
                'image_2x' => $img_url . 'video/video-bg-collection@2x.jpg',
                'title'    => _w('Video 3'),
                'data'     => (new siteCustomVideo3BlockType())->getExampleBlockData(),
                'tags'     => ['category_video'],
            ],
            [
                'image' => '',
                'icon'  => 'heading',
                'title' => _w('Heading'),
                'data'  => (new siteHeadingBlockType())->getExampleBlockData(),
                'tags'  => ['element'],
            ],
            [
                'image' => '',
                'icon'  => 'paragraph',
                'title' => _w('Text'),
                'data'  => (new siteParagraphBlockType())->getExampleBlockData(),
                'tags'  => ['element'],
            ],
            [
                'image' => '',
                'icon'  => 'list',
                'title' => _w('List'),
                'data'  => (new siteListBlockType())->getExampleBlockData(),
                'tags'  => ['element'],
            ],
            [
                'image' => '',
                'icon'  => 'minus-square',
                'title' => _w('Button or link'),
                'data'  => (new siteButtonBlockType())->getExampleBlockData(),
                'tags'  => ['element'],
            ],
            [
                'image' => '',
                'icon'  => 'image',
                'title' => _w('Image'),
                'data'  => (new siteImageBlockType())->getExampleBlockData(),
                'tags'  => ['element'],
            ],
            [
                'image' => '',
                'icon'  => 'video',
                'title' => _w('Video'),
                'data'  => (new siteVideoBlockType())->getExampleBlockData(),
                'tags'  => ['element'],
            ],
            [
                'image' => '',
                'icon'  => 'map-marker-alt',
                'title' => _w('Map'),
                'data'  => (new siteMapBlockType())->getExampleBlockData(),
                'tags'  => ['element'],
            ],
            [
                'image' => '',
                'icon'  => 'minus',
                'title' => _w('Horizontal ruler'),
                'data'  => (new siteHrBlockType())->getExampleBlockData(),
                'tags'  => ['element'],
            ],
            [
                'image' => '',
                'app_icon'  => 'wa-apps/site/img/icons/crm.svg',
                'title' => _w('CRM lead form'),
                'data'  => (new siteFormBlockType(['form_type' => 'crm']))->getExampleBlockData(),
                'tags'  => ['element', 'form'],
            ],
            [
                'image' => '',
                'app_icon'  => 'wa-apps/site/img/icons/mailer.svg',
                'title' => _w('Email subscription form'),
                'data'  => (new siteFormBlockType(['form_type' => 'mailer']))->getExampleBlockData(),
                'tags'  => ['element', 'form'],
            ],
            [
                'image' => '',
                'app_icon'  => 'wa-apps/site/img/icons/helpdesk.svg',
                'title' => _w('Help desk support request form'),
                'data'  => (new siteFormBlockType(['form_type' => 'helpdesk']))->getExampleBlockData(),
                'tags'  => ['element', 'form'],
            ],
            [
                'image' => '',
                'icon'  => 'arrow-circle-right',
                'title' => _w('Row'),
                'data'  => (new siteRowBlockType())->getExampleBlockData(),
                'tags'  => ['element'], // ['element', 'complex-row'],
            ],
            [
                'image' => '',
                'icon'  => 'arrow-circle-down',
                'title' => _w('Subcolumn'),
                'data'  => (new siteSubColumnBlockType())->getExampleBlockData(),
                'tags'  => ['element'], // ['element', 'complex-row'],
            ],
            [
                'image' => '',
                'icon'  => 'table',
                'title' => _w('Column'),
                'data'  => (new siteColumnBlockType())->getExampleBlockData(),
                'tags'  => ['element', 'complex-column'],
            ],
            [
                'image' => '',
                'icon'  => 'code',
                'title' => _w('Custom code'),
                'data'  => (new siteCustomCodeBlockType(['is_block' => false]))->getExampleBlockData(),
                'tags'  => ['element'],
            ],

        ];
    }

    protected function getThirdPartyBlocks() {
        $result = [];
        $plugin_results = wa('site')->event('blockpage_blocks');
        foreach ($plugin_results as $plugin_id => $blocks) {
            foreach ($blocks as $b) {
                if (empty($b['data'])) {
                    try {
                        if (empty($b['block_type'])) {
                            if (!empty($b['block_type_class']) && class_exists($b['block_type_class'])) {
                                $b['block_type'] = new $b['block_type_class'];
                            }
                        }
                        if (!empty($b['block_type'])) {
                            $b['data'] = $b['block_type']->getExampleBlockData();
                        }
                    } catch (Throwable $e) {
                        continue;
                    }
                }
                unset($b['block_type'], $b['block_type_class']);

                if (empty($b['data']) || !($b['data'] instanceof siteBlockData)) {
                    continue;
                }

                $result[] = $b + [
                        'image' => '',
                        'icon'  => '',
                        'title' => get_class($b['data']->block_type),
                        'tags'  => [],
                    ];
            }
        }

        return $result;
    }

    protected function hashBlockId(siteBlockData $data) {
        $block_type_id = $data->block_type->getTypeId();
        $parts = [
            $block_type_id,
            json_encode($data->data),
        ];
        foreach ($data->children as $child_key => $arr) {
            $parts[] = $child_key;
            foreach ($arr as $d) {
                $parts[] = $this->hashBlockId($d);
            }
        }
        return $block_type_id . '_' . md5(join(';', $parts));
    }
}
