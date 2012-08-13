<?php

class photosDialogManageAccessAction extends waViewAction
{
    public function execute()
    {
        $photo_id = waRequest::get('photo_id', array(), waRequest::TYPE_ARRAY_INT);

        if (!$photo_id) {
            throw new waException(_w('Empty photo list'));
        }
        $photo_model = new photosPhotoModel();
        // dialog for one photo
        if (count($photo_id) == 1) {
            $photo_id = current($photo_id);
            $photo = $photo_model->getById($photo_id);
            $photo_right_model = new photosPhotoRightsModel();
            if (!$photo_right_model->checkRights($photo, true)) {
                $rights = array(
                    0 => array(
                        'group_id' => 0,
                        'photo_id' => null
                    )
                );
            } else {
                $rights = $photo_right_model->getByField('photo_id', $photo_id, 'group_id');
            }
        } else { // dialog for several selected photos

            // dummies for correct template randering
            $photo = array(
                'status' => 1
            );
            $rights = array(
                0 => array(
                    'group_id' => 0,
                    'photo_id' => null
                )
            );

            $allowed_photo_id = (array)$photo_model->filterByField($photo_id, 'status', 1);
            $this->view->assign('photo_count', count($photo_id));
            $this->view->assign('disable_submit', count($allowed_photo_id) != count($photo_id));
        }

        $groups_model = new waGroupModel();
        $groups = $groups_model->getAll('id', true);

        $this->view->assign('groups', $groups);
        $this->view->assign('photo', $photo);
        $this->view->assign('rights', $rights);
    }
}