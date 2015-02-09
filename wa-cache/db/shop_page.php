<?php
return array (
  'id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'autoincrement' => 1,
  ),
  'parent_id' => 
  array (
    'type' => 'int',
    'params' => '11',
  ),
  'domain' => 
  array (
    'type' => 'varchar',
    'params' => '255',
  ),
  'route' => 
  array (
    'type' => 'varchar',
    'params' => '255',
  ),
  'name' => 
  array (
    'type' => 'varchar',
    'params' => '255',
    'null' => 0,
  ),
  'title' => 
  array (
    'type' => 'varchar',
    'params' => '255',
    'null' => 0,
    'default' => '',
  ),
  'url' => 
  array (
    'type' => 'varchar',
    'params' => '255',
  ),
  'full_url' => 
  array (
    'type' => 'varchar',
    'params' => '255',
  ),
  'content' => 
  array (
    'type' => 'text',
    'null' => 0,
  ),
  'create_datetime' => 
  array (
    'type' => 'datetime',
    'null' => 0,
  ),
  'update_datetime' => 
  array (
    'type' => 'datetime',
    'null' => 0,
  ),
  'create_contact_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
  ),
  'sort' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'default' => '0',
  ),
  'status' => 
  array (
    'type' => 'tinyint',
    'params' => '1',
    'null' => 0,
    'default' => '0',
  ),
);
