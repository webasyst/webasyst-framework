<?php
if (class_exists('blogBlogModel')) {
	$blog_model = new blogBlogModel();
} elseif (class_exists('waAutoload',false)) {
	waAutoload::getInstance()->add('blogBlogModel','wa-apps/blog/lib/models/blogBlog.model.php');
	waAutoload::getInstance()->add('blogItemModel','wa-apps/blog/lib/models/blogItem.model.php');

	$blog_model = new blogBlogModel();
} else {
	$blog_model = null;
}

return array(
	'params' => array(
	    'blog_url_type' => array(
	        'name' => _w('Published blogs & URLs'),
	        'type' => 'radio_select',
	        'items' => array(
	            -1=>array(
	            	'name' => sprintf(_w('All blogs. Plain URLs (%s)'),'/post_url/'),
	            	'description' => sprintf(_w('<br />Post URLs: <strong>%s</strong>, e.g. %s<br />Blog post list URLs: <strong>%s</strong>, e.g. %s'),
	                '/post_url/','/my-first-post/',
	                '/blog/blog_url/','/blog/misc/, /blog/family/'),
	            ),
	            0=>array(
	            	'name' => sprintf(_w('All blogs. Hierarchical URLs (%s)'),'/blog_url/post_url/'),
	                'description' => sprintf(_w('<br />Post URLs: <strong>%s</strong>, e.g. %s<br />Blog post list URLs: <strong>%s</strong>, e.g. %s'),
	                '/blog_url/post_url/','/misc/my-first-post/',
	                '/blog_url/','/misc/, /family/'),
	            ),
	            array(
	                'name' => _w('One blog'),
	                'description' => sprintf(_w('Post URLs: <strong>%s</strong>'),'/post_url/','none'),
	                'items' => $blog_model ? $blog_model->select('id,name')->where("status='".blogBlogModel::STATUS_PUBLIC."'")->fetchAll('id', true):array(),
	            ),
	         ),
	    ),
	    'post_url_type' => array(
	        'name' => sprintf(_w('Post sub-URL format (%s)'),'post_url'),
	        'type' => 'radio_select',
	        'items' => array(
	            array(
	            	'name' => _w('Post URL only'),
	                'description' => _w('e.g.').' /my-first-post/',
	            ),
	            array(
	            	'name' => _w('Year/post URL'),
	                'description' => _w('e.g.').' /2011/my-first-post/',
	            ),
	            array(
	            	'name' => _w('Year/month/post URL'),
	                'description' => _w('e.g.').' /2011/12/my-first-post/',
	            ),
	            array(
	            	'name' => _w('Year/month/date/post URL'),
	                'description' => _w('e.g.').' /2011/12/13/my-first-post/',
	            ),
	        ),
	    ),
	    'title_type'=>array(
    	    'name'=>_w('Title format'),
    	    'type'=>'radio_select',
    	    'items'=>array(
    	    		'post' => array(
    	            	'name' => _w('Post title only'),
    	                'description' => _w('e.g.').' &lt;title&gt;Post&lt;/title&gt;',
    	            ),
    	            'blog_post' => array(
    	            	'name' => _w('Blog » Post title'),
    	                'description' => _w('e.g.').' &lt;title&gt;Blog » Post&lt;/title&gt;',
    	            ),
    	    ),
    	    'default' => 'blog_post',
	    ),
	    'title' => array(
	        'name'=>_w('Homepage title'),
	        'type'=>'radio_text',
	        'description'=>'',
	        'items'=>array(
	            array(
	            	'name'=>wa()->accountName(),
	                'description'=>_ws('Company name'),
	            ),
	            array(
	                'name'=>'Задать явно',
	            ),
	        ),
	    ),
	),
	'vars' => array(

        '$wa' => array(
            '$wa->blog->blog(<em>blog_id</em>)' => _w('Returns blog info by <em>blog_id</em> as an array with the following structure: (<em>"id"</em>, <em>"url"</em>, <em>"name"</em>, <em>"status"</em>, <em>"icon"</em>, <em>"qty"</em>, <em>"color"</em>, <em>"sort"</em>, <em>"icon_url"</em>, <em>"icon_html"</em>, <em>"link"</em>)'),
            '$wa->blog->post(<em>post_id</em>[,<em>fields</em>])' => _w('Returns post info by <em>post_id</em> as an array with the following structure: (<em>"id"</em>, <em>"contact_id"</em>, <em>"contact_name"</em>, <em>"datetime"</em>, <em>"title"</em>, <em>"text"</em>, <em>"status"</em>, <em>"url"</em>, <em>"blog_id"</em>, <em>"comments_allowed"</em>, <em>"user"</em>, <em>"comment_datetime"</em>, <em>"comment_count"</em>, <em>"comment_str_translate"</em>, <em>"comment_new_count"</em>, <em>"plugins"</em>, <em>"icon"</em>, <em>"color"</em>, <em>"blog_status"</em>, <em>"blog_url"</em>, <em>"blog_name"</em>, <em>"link"</em>)'),
            '$wa->blog->blogs()' => _w('Returns the array of all public blogs. Each blog is an array presenting the blog data'),
            '$wa->blog->posts([<em>blog_id</em>[,<em>number_of_posts</em>]])' => _w('Returns the array of all posts by <em>blog_id</em>. If <em>blog_id</em> is <em>null</em>, the array of all public posts is returned. Optional parameter <em>number_of_posts</em> limits the number of elements returned'),
        ),
	    'index.html' => array(
	        '$content' => 'Core content loaded according to the requested resource: a blog post, post stream, a page, etc.',
	    ),
	    'stream.html' => array(
            '$posts' => array(
               '$id' => '',
               '$blog_id' => '',
               '$contact_id' => '',
               '$title' => '',
               '$text' => '',
               '$status' => '0 for published, 1 for deleted',
               '$datetime' => '',
               '$datetime_public' => '',
               '$url' => '',
               '$comment_count' => '',
               '$comment_str_translate' => 'Entire string representing the number of comments',
               '$user' => '',
	        ),
	    ),
	    'post.html' => array(
            '$current_auth.source' => '',
            '$current_auth.source_id' => '',
            '$current_auth.source_link' => '',
            '$current_auth.name' => '',
            '$current_auth.firstname' => '',
            '$current_auth.lastname' => '',
            '$current_auth.login' => '',
            '$current_auth.photo_url' => '',
            '$current_auth_source' => '',
            '$auth_adapters' => array(
               '$name' => '',
               '$photo_url' => '',
            ),
            '$current_user' => '',
            '$theme' => '',
            '$post.id' => '',
            '$post.contact_id' => '',
            '$post.datetime' => '',
            '$post.title' => '',
            '$post.text' => '',
            '$post.user' => '',
            '$post.comments' => array(
            	'$id' => '',
                '$post_id' => '',
                '$contact_id' => '',
                '$text' => '',
                '$datetime' => '',
                '$name' => '',
                '$email' => '',
                '$status' => '',
                '$depth' => '',
                '$parent' => '',
                '$site' => '',
                '$auth_provider' => '',
                '$ip' => '',
                '$user' => '',
                '$new' => '',
            ),
            '$post.comment_count' => '',
            '$post.comment_new_count' => '',
            '$post.comment_str_translate' => '',
	    ),
	),
);