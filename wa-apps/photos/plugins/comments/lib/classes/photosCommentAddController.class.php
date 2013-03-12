<?php

class photosCommentAddController extends waJsonController
{
    /**
     * @var photosCommentModel
     */
    protected $comment_model = null;

    /**
     * @var waAuthUser
     */
    protected $author = null;

    protected $added_comment = null;

    public function __construct() {
        $this->comment_model = new photosCommentModel();
        $this->author = $this->getUser();
        $this->view = wa()->getView();
    }

    public function execute()
    {
        $data = $this->getReqiestData();
        $comment_id = $data['comment_id'];
        unset($data['comment_id']);
        $data['datetime'] = date('Y-m-d H:i:s');

        $contact_data = $this->getContactData();

        $data = array_merge($data, $contact_data);

        $this->errors += $this->comment_model->validate($data);

        if ($this->errors) {
            return false;
        }

        // taking into account possibility of stack
        $photo_id = $data['photo_id'];
        $photo_model = new photosPhotoModel();
        $parent_id = $photo_model->getStackParentId($photo_id);
        if ($parent_id) {    // if it is stack work with parent
            $photo_id = $parent_id;
        }
        $data['photo_id'] = $photo_id;

        if (!isset($data['ip']) && ($ip = waRequest::getIp())) {
            $ip = ip2long($ip);
            if ($ip > 2147483647) {
                $ip -= 4294967296;
            }
            $data['ip'] = $ip;
        }

        $id = $this->comment_model->add($data, $comment_id);
        $this->added_comment = $this->comment_model->getById($id);

        if (preg_match('/(\d+)/', $data['photo_comments_count_text'], $m)) {
            $count = $m[1] + 1;
            $this->response['photo_comments_count_text'] = _wp('%d comment', '%d comments', $count);
        }

        $comment = $data;
        $comment['id'] = $id;
        $comment['author'] = $this->getResponseAuthorData();
        $comment['status'] = photosCommentModel::STATUS_PUBLISHED;
        $photo_id = $comment['photo_id'];

        $this->view->assign('wrap_li', true);
        $this->view->assign('comment', $comment);
        $this->view->assign('contact_rights', wa()->getUser()->getRights('contacts', 'backend'));
        $this->response['html'] = $this->view->fetch($this->template);

    }

    protected function getReqiestData()
    {
        $photo_id = waRequest::post('photo_id', null, waRequest::TYPE_INT);
        $comment_id = waRequest::post('comment_id', 0, waRequest::TYPE_INT); // id of parent in tree
        $photo_comments_count_text = waRequest::post('photo_comments_count_text', '', waRequest::TYPE_STRING);
        if (!$photo_id && !$comment_id) {
            throw new waException("Can't add comment: unknown photo for comment or comment for reply");
        }
        if (!$photo_id) {
            $parent_comment = $this->comment_model->getById($comment_id);
            $photo_id = $parent_comment['photo_id'];
        }
        $text = waRequest::post('text', '', waRequest::TYPE_STRING_TRIM);
        return array(
            'photo_id' => $photo_id,
            'comment_id' => $comment_id,
            'text' => $text,
            'photo_comments_count_text' => $photo_comments_count_text
        );
    }

    protected function getContactData()
    {
        $this->author = $this->getUser();
        return array('contact_id' => $this->author->getId());
    }

    protected function getResponseAuthorData()
    {
        $this->author = $this->getUser();
        return array(
            'id' => $this->author->getId(),
            'name' => $this->author->getName(),
            'photo' => $this->author->getPhoto(photosCommentModel::SMALL_AUTHOR_PHOTO_SIZE)
        );
    }
}