<?php
return array (
  'id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'autoincrement' => 1,
  ),
  'blog_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'default' => '1',
  ),
  'contact_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
  ),
  'contact_name' => 
  array (
    'type' => 'varchar',
    'params' => '150',
    'default' => '',
  ),
  'datetime' => 
  array (
    'type' => 'datetime',
  ),
  'title' => 
  array (
    'type' => 'varchar',
    'params' => '255',
    'null' => 0,
    'default' => '',
  ),
  'status' => 
  array (
    'type' => 'enum',
    'params' => '\'draft\',\'deadline\',\'scheduled\',\'published\'',
    'null' => 0,
    'default' => 'draft',
  ),
  'text' => 
  array (
    'type' => 'mediumtext',
    'null' => 0,
  ),
  'text_before_cut' => 
  array (
    'type' => 'text',
  ),
  'cut_link_label' => 
  array (
    'type' => 'varchar',
    'params' => '255',
  ),
  'url' => 
  array (
    'type' => 'varchar',
    'params' => '255',
    'null' => 0,
    'default' => '',
  ),
  'comments_allowed' => 
  array (
    'type' => 'tinyint',
    'params' => '1',
    'null' => 0,
    'default' => '1',
  ),
  'meta_title' => 
  array (
    'type' => 'varchar',
    'params' => '255',
  ),
  'meta_keywords' => 
  array (
    'type' => 'text',
  ),
  'meta_description' => 
  array (
    'type' => 'text',
  ),
  'album_id' => 
  array (
    'type' => 'int',
    'params' => '11',
  ),
  'album_link_type' => 
  array (
    'type' => 'enum',
    'params' => '\'blog\',\'photos\'',
  ),
);
