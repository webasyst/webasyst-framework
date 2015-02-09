<?php
return array (
  'id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'autoincrement' => 1,
  ),
  'name' => 
  array (
    'type' => 'varchar',
    'params' => '150',
    'null' => 0,
  ),
  'firstname' => 
  array (
    'type' => 'varchar',
    'params' => '50',
    'null' => 0,
    'default' => '',
  ),
  'middlename' => 
  array (
    'type' => 'varchar',
    'params' => '50',
    'null' => 0,
    'default' => '',
  ),
  'lastname' => 
  array (
    'type' => 'varchar',
    'params' => '50',
    'null' => 0,
    'default' => '',
  ),
  'title' => 
  array (
    'type' => 'varchar',
    'params' => '50',
    'null' => 0,
    'default' => '',
  ),
  'company' => 
  array (
    'type' => 'varchar',
    'params' => '150',
    'null' => 0,
    'default' => '',
  ),
  'jobtitle' => 
  array (
    'type' => 'varchar',
    'params' => '50',
    'null' => 0,
    'default' => '',
  ),
  'company_contact_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'default' => '0',
  ),
  'is_company' => 
  array (
    'type' => 'tinyint',
    'params' => '1',
    'null' => 0,
    'default' => '0',
  ),
  'is_user' => 
  array (
    'type' => 'tinyint',
    'params' => '1',
    'null' => 0,
    'default' => '0',
  ),
  'login' => 
  array (
    'type' => 'varchar',
    'params' => '32',
  ),
  'password' => 
  array (
    'type' => 'varchar',
    'params' => '32',
    'null' => 0,
    'default' => '',
  ),
  'last_datetime' => 
  array (
    'type' => 'datetime',
  ),
  'sex' => 
  array (
    'type' => 'enum',
    'params' => '\'m\',\'f\'',
  ),
  'birth_day' => 
  array (
    'type' => 'tinyint',
    'params' => '2',
    'unsigned' => 1,
  ),
  'birth_month' => 
  array (
    'type' => 'tinyint',
    'params' => '2',
    'unsigned' => 1,
  ),
  'birth_year' => 
  array (
    'type' => 'smallint',
    'params' => '4',
  ),
  'about' => 
  array (
    'type' => 'text',
  ),
  'photo' => 
  array (
    'type' => 'int',
    'params' => '10',
    'unsigned' => 1,
    'null' => 0,
    'default' => '0',
  ),
  'create_datetime' => 
  array (
    'type' => 'datetime',
    'null' => 0,
  ),
  'create_app_id' => 
  array (
    'type' => 'varchar',
    'params' => '32',
    'null' => 0,
    'default' => '',
  ),
  'create_method' => 
  array (
    'type' => 'varchar',
    'params' => '32',
    'null' => 0,
    'default' => '',
  ),
  'create_contact_id' => 
  array (
    'type' => 'int',
    'params' => '11',
    'null' => 0,
    'default' => '0',
  ),
  'locale' => 
  array (
    'type' => 'varchar',
    'params' => '8',
    'null' => 0,
    'default' => '',
  ),
  'timezone' => 
  array (
    'type' => 'varchar',
    'params' => '64',
    'null' => 0,
    'default' => '',
  ),
);
