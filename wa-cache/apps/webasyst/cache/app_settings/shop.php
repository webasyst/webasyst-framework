<?php
return array (
  0 => 
  array (
    'app_id' => 'shop',
    'name' => 'country',
    'value' => 'rus',
  ),
  1 => 
  array (
    'app_id' => 'shop',
    'name' => 'currency',
    'value' => 'RUB',
  ),
  2 => 
  array (
    'app_id' => 'shop',
    'name' => 'use_product_currency',
    'value' => '0',
  ),
  3 => 
  array (
    'app_id' => 'shop',
    'name' => 'update_time',
    'value' => '1400504207',
  ),
  4 => 
  array (
    'app_id' => 'shop',
    'name' => 'theme_hash',
    'value' => '54e5b6732bd71.1424340595',
  ),
  5 => 
  array (
    'app_id' => 'shop.productbrands',
    'name' => 'update_time',
    'value' => '1421409095',
  ),
  6 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'status',
    'value' => '1',
  ),
  7 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'bg',
    'value' => '#03b3ee',
  ),
  8 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'bg2',
    'value' => '#ffffff',
  ),
  9 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'border_color',
    'value' => '#038aee',
  ),
  10 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'border_size',
    'value' => '1',
  ),
  11 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'border_radius',
    'value' => '10',
  ),
  12 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'button_width',
    'value' => '70',
  ),
  13 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'button_height',
    'value' => '40',
  ),
  14 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'opacity',
    'value' => '0.8',
  ),
  15 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'text_size',
    'value' => '16',
  ),
  16 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'text',
    'value' => '▲',
  ),
  17 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'link_color',
    'value' => '#ffffff',
  ),
  18 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'link_hover',
    'value' => '#03b3ee',
  ),
  19 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'position_ver',
    'value' => 'b',
  ),
  20 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'position_hor',
    'value' => 'r',
  ),
  21 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'pos_ver',
    'value' => '100',
  ),
  22 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'pos_hor',
    'value' => '50',
  ),
  23 => 
  array (
    'app_id' => 'shop.backtop',
    'name' => 'update_time',
    'value' => '1',
  ),
  24 => 
  array (
    'app_id' => 'shop.size',
    'name' => 'update_time',
    'value' => '1411895695',
  ),
  25 => 
  array (
    'app_id' => 'shop.posts',
    'name' => 'update_time',
    'value' => '1',
  ),
  26 => 
  array (
    'app_id' => 'shop',
    'name' => 'preview_hash',
    'value' => '54ddbba7c30a3.1423817639',
  ),
  27 => 
  array (
    'app_id' => 'shop.productbrands',
    'name' => 'feature_id',
    'value' => '2',
  ),
  28 => 
  array (
    'app_id' => 'shop.productbrands',
    'name' => 'sizes',
    'value' => '',
  ),
  29 => 
  array (
    'app_id' => 'shop.productbrands',
    'name' => 'template_nav',
    'value' => '<ul class="menu-v brands">
{foreach $brands as $b}
    <li {if $b.name == $wa->param(\'brand\')}class="selected"{/if}>
        <a href="{$b.url}">{$b.name|escape}</a>
    </li>
{/foreach}
</ul>
',
  ),
  30 => 
  array (
    'app_id' => 'shop.productbrands',
    'name' => 'template_search',
    'value' => '<div class="brand">
    {if $brand.image}
        <img src="{$wa_url}wa-data/public/shop/brands/{$brand.id}/{$brand.id}{$brand.image}" align="left">
    {/if}
    {$brand.description}
</div>
<!-- categories -->
{if $categories}
<br clear="left">
<div class="sub-categories">
    {foreach $categories as $sc}
    <a href="{$sc.url}">{$sc.name|escape}</a><br />
    {/foreach}
</div>
{/if}

<br clear="left">',
  ),
  31 => 
  array (
    'app_id' => 'shop.productbrands',
    'name' => 'categories_filter',
    'value' => '0',
  ),
  32 => 
  array (
    'app_id' => 'shop.productbrands',
    'name' => 'brands_name',
    'value' => 'Все бренды',
  ),
  33 => 
  array (
    'app_id' => 'shop.productbrands',
    'name' => 'template_brands',
    'value' => '{foreach $brands as $b}
<div class="brand">
    <a href="{$b.url}">{if $b.image}<img src="{$wa_url}wa-data/public/shop/brands/{$b.id}/{$b.id}{$b.image}">{else}{$b.name}{/if}</a>
    <br>
    {$b.summary}
</div>
<br clear="left">
{/foreach}
',
  ),
  34 => 
  array (
    'app_id' => 'shop',
    'name' => 'ignore_stock_count',
    'value' => '0',
  ),
  35 => 
  array (
    'app_id' => 'shop',
    'name' => 'update_stock_count_on_create_order',
    'value' => '1',
  ),
  36 => 
  array (
    'app_id' => 'shop',
    'name' => 'discount_coupons',
    'value' => '',
  ),
  37 => 
  array (
    'app_id' => 'shop',
    'name' => 'discount_customer_total',
    'value' => '1',
  ),
);
