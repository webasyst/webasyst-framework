<?php

//create first blog at install
$name = wa()->accountName();
$blog = array(
	'status'=>blogBlogModel::STATUS_PUBLIC,
	'name'=>$name,
	'icon'=>'blog',
	'color'=>'b-white',
	'url'=>blogHelper::transliterate($name),

);
$app = wa()->getApp();
$blog_model = new blogBlogModel();
$blog_id = $blog_model->insert($blog);
$user = wa()->getUser();
if (!$user->isAdmin($app)) {
    $user->setRight($app, "blog.{$blog_id}", blogRightConfig::RIGHT_FULL);
}