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
        $tag_ids = array_keys($this->getByField('post_id', $post_id, 'tag_id'));
        if (!$this->deleteByField('post_id', $post_id)) {
            return false;
        }
        
        if ($tag_ids) {
            $delete_tag_ids = array_keys($this->query("
            SELECT t.id, COUNT(pt.tag_id) cnt FROM `blog_tag` t 
            LEFT JOIN `blog_post_tag` pt ON t.id = pt.tag_id
            WHERE t.id IN(".implode(',', $tag_ids).")
            GROUP BY t.id
            HAVING cnt = 0")->
                fetchAll('id')
            );
            if ($delete_tag_ids) {
                $tag_model = new blogTagPluginModel();
                $tag_model->deleteById($delete_tag_ids);
            }
        }
        
        return true;
    }
}
//EOF