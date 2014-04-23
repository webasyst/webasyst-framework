<?php
if (class_exists('blogBlogModel')) {
    $blog_model = new blogBlogModel();
} elseif (class_exists('waAutoload', false)) {
    waAutoload::getInstance()->add('blogBlogModel', 'wa-apps/blog/lib/models/blogBlog.model.php');
    waAutoload::getInstance()->add('blogItemModel', 'wa-apps/blog/lib/models/blogItem.model.php');

    $blog_model = new blogBlogModel();
} else {
    $blog_model = null;
}

return array(
    'params' => array(
        'blog_url_type' => array(
            'name'  => _w('Published blogs & URLs'),
            'type'  => 'radio_select',
            'items' => array(
                -1 => array(
                    'name'        => sprintf(_w('All blogs. Plain URLs (%s)'), '/post_url/'),
                    'description' => sprintf(_w('<br />Post URLs: <strong>%s</strong>, e.g. %s<br />Blog post list URLs: <strong>%s</strong>, e.g. %s'),
                        '/post_url/', '/my-first-post/',
                        '/blog/blog_url/', '/blog/misc/, /blog/family/'),
                ),
                0  => array(
                    'name'        => sprintf(_w('All blogs. Hierarchical URLs (%s)'), '/blog_url/post_url/'),
                    'description' => sprintf(_w('<br />Post URLs: <strong>%s</strong>, e.g. %s<br />Blog post list URLs: <strong>%s</strong>, e.g. %s'),
                        '/blog_url/post_url/', '/misc/my-first-post/',
                        '/blog_url/', '/misc/, /family/'),
                ),
                array(
                    'name'        => _w('One blog'),
                    'description' => sprintf(_w('Post URLs: <strong>%s</strong>'), '/post_url/', 'none'),
                    'items'       => $blog_model ? $blog_model->select('id,name')->where("status='".blogBlogModel::STATUS_PUBLIC."'")->fetchAll('id', true) : array(),
                ),
            ),
        ),
        'post_url_type' => array(
            'name'  => sprintf(_w('Post sub-URL format (%s)'), 'post_url'),
            'type'  => 'radio_select',
            'items' => array(
                array(
                    'name'        => _w('Post URL only'),
                    'description' => _w('e.g.').' /my-first-post/',
                ),
                array(
                    'name'        => _w('Year/post URL'),
                    'description' => _w('e.g.').' /2011/my-first-post/',
                ),
                array(
                    'name'        => _w('Year/month/post URL'),
                    'description' => _w('e.g.').' /2011/12/my-first-post/',
                ),
                array(
                    'name'        => _w('Year/month/date/post URL'),
                    'description' => _w('e.g.').' /2011/12/13/my-first-post/',
                ),
            ),
        ),
        'title_type'    => array(
            'name'    => _w('Title format'),
            'type'    => 'radio_select',
            'items'   => array(
                'post'      => array(
                    'name'        => _w('Post title only'),
                    'description' => _w('e.g.').' &lt;title&gt;Post&lt;/title&gt;',
                ),
                'blog_post' => array(
                    'name'        => _w('Blog » Post title'),
                    'description' => _w('e.g.').' &lt;title&gt;Blog » Post&lt;/title&gt;',
                ),
            ),
            'default' => 'blog_post',
        ),
        'title'         => array(
            'name'        => _w('Homepage &lt;title&gt;'),
            'type'        => 'radio_text',
            'description' => '',
            'items'       => array(
                array(
                    'name'        => wa()->accountName(),
                    'description' => _ws('Company name'),
                ),
                array(
                    'name' => _w('As specified'),
                ),
            ),
        ),
        'meta_keywords' => array(
            'name' => _w('Homepage META Keywords'),
            'type' => 'input'
        ),
        'meta_description' => array(
            'name' => _w('Homepage META Description'),
            'type' => 'textarea'
        ),
        'rss_title' => array(
            'name' => _w('RSS feed title'),
            'type' => 'radio_text',
            'description' => '',
            'items' => array(
                array(
                    'name' => wa()->accountName(),
                    'description' => _ws('Company name'),
                ),
                array(
                    'name' => _w('As specified')
                ),
            ),
        ),
    ),
    'vars'   => array(

        'index.html'  => array(
            '$content' => 'Core content loaded according to the requested resource: a blog post, post stream, a page, etc.',
        ),
        'stream.html' => array(
            '$posts' => array(
                '$id'              => '',
                '$blog_id'         => '',
                '$contact_id'      => '',
                '$title'           => '',
                '$text'            => '',
                '$status'          => '0 for published, 1 for deleted',
                '$datetime'        => '',
                '$datetime_public' => '',
                '$url'             => '',
                '$comment_count'   => '',
                '$user'            => '',
            ),
        ),
        'post.html'   => array(
            /*
                        '$current_auth.source' => '',
                        '$current_auth.source_id' => '',
                        '$current_auth.url' => '',
                        '$current_auth.name' => '',
                        '$current_auth.firstname' => '',
                        '$current_auth.lastname' => '',
                        '$current_auth.login' => '',
                        '$current_auth.photo_url' => '',
                        '$current_auth_source' => '',
                        '$auth_adapters' => '',
                        '$theme' => '',
            */
            '$post.id'                => '',
            '$post.contact_id'        => '',
            '$post.datetime'          => '',
            '$post.title'             => '',
            '$post.text'              => '',
            '$post.user'              => '',
            '$post.comments'          => array(
                '$id'            => '',
                '$post_id'       => '',
                '$contact_id'    => '',
                '$text'          => '',
                '$datetime'      => '',
                '$name'          => '',
                '$email'         => '',
                '$status'        => '',
                '$depth'         => '',
                '$parent'        => '',
                '$site'          => '',
                '$auth_provider' => '',
                '$ip'            => '',
                '$user'          => '',
                '$new'           => '',
            ),
            '$post.comment_count'     => '',
            '$post.comment_new_count' => '',
        ),
        '$wa'         => array(
            '$wa->blog->blog(<em>blog_id</em>)'                               => _w('Returns blog info by <em>blog_id</em> as an array with the following structure: (<em>"id"</em>, <em>"url"</em>, <em>"name"</em>, <em>"status"</em>, <em>"icon"</em>, <em>"qty"</em>, <em>"color"</em>, <em>"sort"</em>, <em>"icon_url"</em>, <em>"icon_html"</em>, <em>"link"</em>)'),
            '$wa->blog->post(<em>post_id</em>[,<em>fields</em>])'             => _w('Returns post info by <em>post_id</em> as an array with the following structure: (<em>"id"</em>, <em>"contact_id"</em>, <em>"contact_name"</em>, <em>"datetime"</em>, <em>"title"</em>, <em>"text"</em>, <em>"status"</em>, <em>"url"</em>, <em>"blog_id"</em>, <em>"comments_allowed"</em>, <em>"user"</em>, <em>"comment_count"</em>, <em>"comment_new_count"</em>, <em>"plugins"</em>, <em>"icon"</em>, <em>"color"</em>, <em>"blog_status"</em>, <em>"blog_url"</em>, <em>"blog_name"</em>, <em>"link"</em>)'),
            '$wa->blog->blogs()'                                              => _w('Returns the array of all public blogs. Each blog is an array presenting the blog data'),
            '$wa->blog->posts([<em>blog_id</em>[,<em>number_of_posts</em>]])' => _w('Returns the array of all posts by <em>blog_id</em>. If <em>blog_id</em> is <em>null</em>, the array of all public posts is returned. Optional parameter <em>number_of_posts</em> limits the number of elements returned'),
            '$wa->blog->themePath("<em>theme_id</em>")' => _ws('Returns path to theme folder by <em>theme_id</em>'),
        ),
    ),
    'blocks' => array(
        'latest_posts'    => array(
            'description' => _w('Latest blog posts'),
            'content'     => '
<style>
  .post { margin-bottom: 80px; margin-right: 50px; }
  .post h3 { font-size: 2em; margin-right: 5px; margin-bottom: 3px; }
  .post .credentials { color: #AAA; font-size: .9em; margin-bottom: 5px; }
  .post .username { color: #777; padding: 0; display: inline; }
</style>
{$latest_posts = $wa->blog->posts()}
<div>
{foreach $latest_posts as $post}
<div class="post">
  <h3>
    <a href="{$post.link}">{$post.title}</a>
    {* @event prepare_posts_frontend.%plugin_id%.post_title *}
    {if !empty($post.plugins.post_title)}
      {foreach $post.plugins.post_title as $plugin => $output}{$output}{/foreach}
    {/if}
  </h3>
  <div class="credentials">
    {if $post.user.posts_link}
      <a href="{$post.user.posts_link}" class="username">{$post.user.name}</a>
    {else}
      <span class="username">{$post.user.name}</span>
    {/if}
    {$post.datetime|wa_datetime:"humandate"}
  </div>
  <p>
    {$post.text|strip_tags|truncate:400}
  </p>
</div>
{/foreach}
</div>'
        ),
        'latest_comments' => array(
            'description' => _w('Latest comments of all blog posts'),
            'content'     => '
<style>
  .comments { list-style: none; line-height: 1.1em; }
  .comments .comment { }
  .comments .credentials { overflow: hidden; line-height: 1.3em; }
  .comments .credentials a, .comments .credentials span { display: inline-block; vertical-align: middle; padding-left: 0 !important; }
  .comments .credentials a { padding: 5px 6px; }
  .comments .credentials .userpic { margin-top: -3px; margin-right: 0; min-width: 20px; display: block; float: left; }
  .comments .credentials .username, .comments .credentials .username a { color: #777; padding: 0; display: inline; }
  .comments .credentials .username { font-size: 0.9em; margin: 0; }
  .comments .credentials .date { margin: 0; }
  .comments .text { margin: 5px 0 5px 30px; }
</style>
<ul class="menu-v with-icons comments">
{$latest_comments = $wa->blog->comments()}
{foreach $latest_comments as $comment}
  <li>
    <div class="comment" >
      <div class="credentials">
        <a name="comment{$comment.id}"{if $comment.site} href="{$comment.site}"{/if}>
            <img src="{$comment.user.photo_url_20|default:$comment.user.photo_url}" class="userpic{if $comment.auth_provider && ($comment.auth_provider neq blogCommentModel::AUTH_GUEST) && ($comment.auth_provider neq blogCommentModel::AUTH_USER)} icon16{/if}" alt="">
        </a>
        {if empty($comment.site)}
            <span class="username">{$comment.name}</span>
        {else}
            <a href="{$comment.site}" class="username">{$comment.name}</a>
        {/if}

        {* @event prepare_comments_frontend.%plugin_id%.authorname_suffix *}
        {if !empty($comment.plugins.authorname_suffix)}
            {foreach $comment.plugins.authorname_suffix as $plugin => $output}{$output}{/foreach}
        {/if}
        <span class="hint date" title="{$comment.datetime|wa_datetime}">{$comment.datetime|wa_datetime:"humandatetime"}</span>


    {if isset($comment.post) && $comment.post && (!isset($single_view) || !$single_view)}
      <span class="hint">
        [`on`]
        {if isset($comment.post) && $comment.post && $comment.editable || (isset($comment.post) && $comment.post && $comment.post.status eq blogPostModel::STATUS_PUBLISHED)}
          <a href="{if $comment.post.status eq blogPostModel::STATUS_PUBLISHED && $comment.post.blog_status eq blogBlogModel::STATUS_PUBLIC && $comment.post.link}{$comment.post.link}{else}?module=post&amp;id={$comment.post_id}{if $comment.post.status eq blogPostModel::STATUS_PUBLISHED}#comments{else}&amp;action=edit{/if}{/if}" class="bold">{$comment.post.title|escape}</a>
        {else}
          [`unpublished post`]
        {/if}
      </span>
    {/if}
    </div>

    {* @event prepare_comments_frontend.%plugin_id%.before *}
    {if !empty($comment.plugins.before)}
        {foreach $comment.plugins.before as $plugin => $output}{$output}{/foreach}
    {/if}

    <div class="text">{$comment.text|nl2br}</div>

    {* @event prepare_comments_frontend.%plugin_id%.after *}
    {if !empty($comment.plugins.after)}
        {foreach $comment.plugins.after as $plugin => $output}{$output}{/foreach}
    {/if}

    </div>
  </li>
{foreachelse}
  <li>[`no comments`]</li>
{/foreach}
</ul>'
        ),
    ),
);