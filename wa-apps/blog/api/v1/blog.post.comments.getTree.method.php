<?php

class blogPostCommentsGetTreeMethod extends waAPIMethod
{
    public function execute()
    {
        $post_id = $this->get('post_id', true);
        $post_model = new blogPostModel();
        $post = $post_model->getById($post_id);
        
        if (!$post) {
            throw new waAPIException('invalid_param', 'Post not found', 404);
        }

        $parent_id = waRequest::get('parent_comment_id');
        
        $comment_model = new blogCommentModel();
        $comments = $comment_model->getSubtree($post_id, $parent_id);

        $stack = array();
        $result = array();
        foreach ($comments as $r) {
            $r['comments'] = array();

            // Number of stack items
            $l = count($stack);

            // Check if we're dealing with different levels
            while($l > 0 && $stack[$l - 1]['depth'] >= $r['depth']) {
                array_pop($stack);
                $l--;
            }

            // Stack is empty (we are inspecting the root)
            if ($l == 0) {
                // Assigning the root node
                $i = count($result);
                $result[$i] = $r;
                $stack[] = & $result[$i];
            } else {
                // Add node to parent
                $i = count($stack[$l - 1]['comments']);
                $stack[$l - 1]['comments'][$i] = $r;
                $stack[] = & $stack[$l - 1]['comments'][$i];
            }
        }
        
        $this->response = $result;
        $this->response['_element'] = 'comment';
    }
}
