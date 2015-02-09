<?php

class blogPostSaveFieldController extends waJsonController
{

    private $allowed_fields = array(
        'datetime', 'status'
    );

    public function execute()
    {
        $data = waRequest::post('data', null);
        if (!$data) {
            return;
        }
        foreach ($data as $name => $value) {
            if (in_array($name, $this->allowed_fields) === false) {
                throw new waException("Can't update post: editing of this field is denied");
            }
            if ($name == 'status') {
                if (in_array($value, array(
                        blogPostModel::STATUS_DRAFT,
                        blogPostModel::STATUS_DEADLINE,
                        blogPostModel::STATUS_SCHEDULED,
                        blogPostModel::STATUS_PUBLISHED
                    )) === false)
                {
                    throw new waException("Can't change status: unknown value");
                }

            }
        }

        $post_id = waRequest::post('post_id', null, waRequest::TYPE_INT);

        $post_model = new blogPostModel();
        $post = null;
        if ($post_id) {
            $post = $post_model->getFieldsById($post_id, array(
                'id', 'blog_id', 'contact_id', 'datetime'
            ));
        }
        if (!$post) {
            throw new waException("Unknown post");
        }

        $contact = wa()->getUser();
        $contact_id = $contact->getId();

        $allow = blogHelper::checkRights(
            $post['blog_id'],
            $contact_id,
            ($contact_id != $post['contact_id']) ? blogRightConfig::RIGHT_FULL : blogRightConfig::RIGHT_READ_WRITE
        );
        if (!$allow) {
            throw new waException("Access denied");
        }

        if (!$post_model->updateById($post_id, $data)) {
            throw new waException("Error when updating data");
        }

        $post = array_merge($post, $data);

        if ($post['status'] == blogPostModel::STATUS_DEADLINE) {
            $user = wa()->getUser();
            $timezone = $user->getTimezone();
            $current_datetime =  waDateTime::date("Y-m-d", null, $timezone);
            $datetime = waDateTime::date("Y-m-d", $post['datetime'], $timezone);
            if ($datetime  <= $current_datetime) {
                $post['overdue'] = true;
            }
        }
        $this->response['post'] = $post;
    }
}