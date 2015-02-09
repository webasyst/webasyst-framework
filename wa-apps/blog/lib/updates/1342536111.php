<?php

$category_model = new waContactCategoryModel();
$category = $category_model->getBySystemId('blog');

$contact_model = new waContactModel();
$sql = "SELECT id FROM wa_contact WHERE create_app_id='blog'";
$contact_ids = $contact_model->query($sql)->fetchAll(null, true);

if ($contact_ids) {
    $contact_categories_model = new waContactCategoriesModel();
    $contact_categories_model->add($contact_ids, $category['id']);
}