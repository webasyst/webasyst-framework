<?php
class blogCategoryPostModel extends waModel
{
    protected $table = 'blog_post_category';
    protected $id = null;


    public function changePost($post_id, $categories)
    {
        $categories = array_map('intval', $categories);
        $current_categories = array_keys($this->getByField('post_id',$post_id,'category_id'));

        if ($new_categories = array_diff($categories, $current_categories)) {
            foreach ($new_categories as $key => $id) {
                $this->insert(array('post_id'=>$post_id,'category_id'=>$id), 2);
            }
        }

        if ( $excluded_categories = array_diff($current_categories, $categories)) {
            $this->deleteByField(array('post_id'=>$post_id,'category_id'=>$excluded_categories));
        }
        if($updated = array_merge($excluded_categories, $new_categories,$current_categories)) {
            $category_model = new blogCategoryModel();
            $category_model->recalculate($updated);
        }
    }
}
