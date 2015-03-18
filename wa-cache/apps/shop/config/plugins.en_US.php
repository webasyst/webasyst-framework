<?php
return array (
  'productbrands' => 
  array (
    'name' => 'Brands',
    'description' => 'Storefront’s product filtering by brand (manufacturer). You can upload image and add description for the brand.',
    'version' => '2.1',
    'vendor' => 809114,
    'img' => 'wa-apps/shop/plugins/productbrands/img/brands.png',
    'shop_settings' => true,
    'frontend' => true,
    'icons' => 
    array (
      16 => 'img/brands.png',
    ),
    'handlers' => 
    array (
      'frontend_nav' => 'frontendNav',
      'backend_products' => 'backendProducts',
      'products_collection' => 'productsCollection',
      'sitemap' => 'sitemap',
      'routing' => 'routing',
    ),
    'id' => 'productbrands',
    'app_id' => 'shop',
  ),
  'backtop' => 
  array (
    'name' => 'Кпопка вверх',
    'description' => 'Кнопка прокрутки страницы вверх, полный комплект настроек',
    'img' => 'wa-apps/shop/plugins/backtop/img/Backtop.png',
    'version' => '1.1.1',
    'vendor' => 965055,
    'shop_settings' => true,
    'frontend' => true,
    'icons' => 
    array (
      16 => 'img/Backtop.png',
    ),
    'handlers' => 
    array (
      'frontend_header' => 'frontendHeader',
      'frontend_head' => 'frontendHead',
      'routing' => 'routing',
    ),
    'id' => 'backtop',
    'app_id' => 'shop',
  ),
  'size' => 
  array (
    'name' => 'Размеры',
    'description' => 'Connect sizes.',
    'version' => '2.0',
    'img' => 'wa-apps/shop/plugins/size/img/size.png',
    'shop_settings' => false,
    'frontend' => false,
    'icons' => 
    array (
      16 => 'img/size.png',
    ),
    'handlers' => 
    array (
      'backend_products' => 'backendProducts',
      'backend_menu' => 'backendMenu',
    ),
    'id' => 'size',
    'app_id' => 'shop',
  ),
  'ip' => 
  array (
    'name' => 'Телефоны',
    'description' => 'Выбор телефона в зависимости от региона',
    'vendor' => 'Кайда Дмитрий',
    'version' => '1.0',
    'img' => 'wa-apps/shop/plugins/ip/img/phone.png',
    'shop_settings' => true,
    'icons' => 
    array (
      16 => 'img/phone.png',
    ),
    'id' => 'ip',
    'app_id' => 'shop',
  ),
  'posts' => 
  array (
    'name' => 'Последние записи',
    'description' => 'Вывод последних  трех записей блога',
    'vendor' => 'Кайда Дмитрий',
    'version' => '1.0',
    'img' => 'wa-apps/shop/plugins/posts/img/posts.png',
    'shop_settings' => false,
    'icons' => 
    array (
      16 => 'img/posts.png',
    ),
    'handlers' => 
    array (
      'frontend_my' => 'frontendMy',
    ),
    'id' => 'posts',
    'app_id' => 'shop',
  ),
  'dbf' => 
  array (
    'name' => 'DBF',
    'description' => 'Загрузка .dbf файлов',
    'vendor' => 'Кайда Дмитрий',
    'version' => '1.0',
    'img' => 'wa-apps/shop/plugins/dbf/img/xls.png',
    'shop_settings' => true,
    'id' => 'dbf',
    'app_id' => 'shop',
  ),
);
