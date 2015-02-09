<?php
class blogPostValidateDateController extends waJsonController
{
    public function execute()
    {
        $this->getResponse()->addHeader('Content-type', 'application/json');
        $post_id = waRequest::post('post_id', null);
        $date = waRequest::post('date');

        if (!is_null($post_id)) {

            $post_model = new blogPostModel();

            $post = $post_model->getFieldsById($post_id, array('status'));

            $status = $post['status'];

            if ($status == blogPostModel::STATUS_DEADLINE || $status == blogPostModel::STATUS_DRAFT) {
                if (strlen($date) == 0) {
                    $this->response['valid'] = true;
                    return;
                }
            }
        }

        $this->response['valid'] = true;

        if (!waDateTime::parse('date', $date, wa()->getUser()->getTimezone())) {
            $this->response['valid'] = false;
        }
    }
}