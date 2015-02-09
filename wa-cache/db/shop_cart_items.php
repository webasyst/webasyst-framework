<?php
return array (
  'id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'autoincrement' => 1,
  ),
  'code' => 
  array (
    'type' => 'varchar',
    'params' => '32',
  ),
  'contact_id' => 
  array (
    'type' => 'int',
    'params' => '11',
  ),
  'product_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
  ),
  'sku_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
  ),
  'create_datetime' => 
  array (
    'type' => 'datetime',
    'null' => 0,
  ),
  'quantity' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'default' => '1',
  ),
  'type' => 
  array (
    'type' => 'enum',
    'params' => '\'product\',\'service\'',
    'null' => 0,
    'default' => 'product',
  ),
  'service_id' => 
  array (
    'type' => 'int',
    'params' => '11',
  ),
  'service_variant_id' => 
  array (
    'type' => 'int',
    'params' => '11',
  ),
  'parent_id' => 
  array (
    'type' => 'int',
    'params' => '11',
  ),
);
