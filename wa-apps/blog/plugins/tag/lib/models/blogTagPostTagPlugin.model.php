<?php
class blogTagPostTagPluginModel extends waModel
{
    protected $table = 'blog_post_tag';
    protected $id = null;

    /**
     * Delete posts related tags by post ID
     * @param int|array $post_id
     * @return bool
     */
    public function deletePost($post_id)
    {
        return $this->deleteByField('post_id',$post_id);
    }
}
//EOF