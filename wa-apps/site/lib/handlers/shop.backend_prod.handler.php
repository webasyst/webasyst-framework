<?php

class siteShopBackend_prodHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $label = _wd('site', 'Create landing');
        $domain_id = siteHelper::getDomainId();

        /** @var shopProduct */
        $product = $params['product'];
        $meta_title = $product->getData('meta_title');
        $meta_description = $product->getData('meta_description');
        $meta_keywords = $product->getData('meta_keywords');
        $defaults = urlencode(json_encode([
            'name' => $product->getData('name'),
            'url' => $product->getData('url').'-lp',
            'title' => ifempty($meta_title, shopProduct::getDefaultMetaTitle($product)),
            'params' => [
                'meta_description' => ifempty($meta_description, shopProduct::getDefaultMetaDescription($product)),
                'meta_keywords' => ifempty($meta_keywords, shopProduct::getDefaultMetaKeywords($product)),
            ]
        ]));
        $href = wa()->getAppUrl('site').'map/overview/?domain_id='.$domain_id.'&is_new_blockpage=1&defaults='.$defaults;

        return [
            'sidebar_item' => <<<HTML
<li><a class="text-purple semibold" href="{$href}" target="_blank">
    <span>{$label} <i class="fas fa-external-link-alt text-purple" style="font-size: 0.75rem;margin-top: 0.125rem;"></i></span>
    <span class="count" data-tooltip-id="site_create_landing_page">
        <span class="s-icon"><i class="fas fa-question-circle"></i></span>
    </span>
</a></li>
HTML,
            'sidebar_item_tooltip_id' => 'site_create_landing_page',
            'sidebar_item_tooltip_html' => _wd('site', 'Replace the standard storefront page of this product with a custom landing page with its own design using the Site app’s WYSIWYG page builder. Specify the current product page URL for the lander and enable its “Main section page” setting. This product’s properties will be automatically copied to the landing page. This product’s properties will be automatically copied to the landing page’s settings.'),
        ];
    }
}
