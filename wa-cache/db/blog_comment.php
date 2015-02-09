<?php
return array (
  'id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'autoincrement' => 1,
  ),
  'left' => 
  array (
    'type' => 'int',
    'params' => '11',
  ),
  'right' => 
  array (
    'type' => 'int',
    'params' => '11',
  ),
  'depth' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'default' => '0',
  ),
  'parent' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'default' => '0',
  ),
  'post_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
  ),
  'blog_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
  ),
  'datetime' => 
  array (
    'type' => 'datetime',
    'null' => 0,
  ),
  'status' => 
  array (
    'type' => 'enum',
    'params' => '\'approved\',\'deleted\'',
    'null' => 0,
    'default' => 'approved',
  ),
  'text' => 
  array (
    'type' => 'text',
    'null' => 0,
  ),
  'contact_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
  ),
  'name' => 
  array (
    'type' => 'varchar',
    'params' => '255',
  ),
  'email' => 
  array (
    'type' => 'varchar',
    'params' => '255',
  ),
  'site' => 
  array (
    'type' => 'varchar',
    'params' => '255',
  ),
  'auth_provider' => 
  array (
    'type' => 'varchar',
    'params' => '100',
  ),
  'ip' => 
  array (
    'type' => 'int',
    'params' => '11',
  ),
);
