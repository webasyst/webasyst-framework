<?php
class blogContactsLinksHandler extends waEventHandler
{
    /**
     * @param array $params deleted contact_id
     * @return array|void
     */
    public function execute(&$params)
    {
        waLocale::loadByDomain('blog');
        $post_model = new blogPostModel();
        $comment_model = new blogCommentModel();
        $links = array();
        foreach ($params as $contact_id) {
            $links[$contact_id] = array();
            if ($count = $post_model->countByField('contact_id',$contact_id)) {
                $links[$contact_id][] = array(
                    'role' => _wd('blog', 'Posts author'),
                    'links_number' => $count,
                );
            }
            if ($count = $comment_model->countByField('contact_id',$contact_id)) {
                $links[$contact_id][] = array(
                    'role' => _wd('blog', 'Comments author'),
                    'links_number' => $count,
                );
            }
        }
        return $links;
    }
}

