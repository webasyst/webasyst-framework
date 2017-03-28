<?php

class photosAlbumSaveController extends waJsonController
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var photosAlbumModel
     */
    private $album_model = null;

    public function execute()
    {
        $this->id = waRequest::post('id', null, waRequest::TYPE_INT);
        $this->album_model = new photosAlbumModel();

        $parent = null;
        $parent_id = waRequest::get('parent_id', 0, waRequest::TYPE_INT);
        if ($parent_id) {
            $parent = $this->album_model->getById($parent_id);
        }

        $url = waRequest::post('url', null, waRequest::TYPE_STRING_TRIM);

        $group_ids = null;
        $status = waRequest::post('status', 0, waRequest::TYPE_INT);
        if (!$status) {
            $group_ids = waRequest::post('groups', array(), waRequest::TYPE_ARRAY_INT);
            if (!$group_ids) {
                // visible only for creator
                $status = -1;
                $group_ids = array(-$this->getUser()->getId());
            }
        }

        $name = waRequest::post('name', '', waRequest::TYPE_STRING_TRIM);
        if ($name === '0') {
            $name = '0 ';
        }

        if (!$this->id) {
            if (!$this->getRights('upload')) {
                throw new waException(_w("You don't have sufficient access rights"));
            }
            if ($parent && $parent['status'] <= 0 && $status == 1) {
                throw new waException(_w("Parent album is private"));
            }

            $type = waRequest::post('type', 0, waRequest::TYPE_INT);
            if ($parent && $parent['type'] == photosAlbumModel::TYPE_DYNAMIC && $type == photosAlbumModel::TYPE_STATIC) {
                throw new waException(_w("Parent album is smart"));
            }

            $data = array(
                'name' => $name,
                'status' => $status,
                'type' => $type,
                'group_ids' => $group_ids
            );
            if ($status <= 0) {
                $data['hash'] = md5(uniqid(time(), true));
            } else {
                $data['url'] = $this->album_model->suggestUniqueUrl(photosPhoto::suggestUrl(strlen($url) ? $url : $name));
            }
            if ($type == photosAlbumModel::TYPE_DYNAMIC) {
                $data['conditions'] = $this->getPrepareConditions();
            }
            $this->save($data);
            if ($parent) {
                $child = $this->album_model->getFirstChild($parent['id']);
                $this->album_model->move($this->id, $child ? $child['id'] : 0, $parent['id']);
            }
            $this->response = array(
                'id' => $this->id,
                'name' => photosPhoto::escape($name),
                'type' => $type,
                'status' => $status
            );
        } else {
            $album_rights_model = new photosAlbumRightsModel();
            if (!$album_rights_model->checkRights($this->id, true)) {
                throw new waException(_w("You don't have sufficient access rights"));
            }

            $conditions = $this->getPrepareConditions();

            $params = array();
            $album_params = waRequest::post('params', '', waRequest::TYPE_STRING_TRIM);
            $album_params = explode(PHP_EOL, $album_params);
            foreach ($album_params as $param) {
                $param = explode('=', $param);
                if (count($param) < 2) {
                    continue;
                }
                $params[$param[0]] = trim($param[1]);
            }
            $params = $params ? $params : null;

            $description = waRequest::post('description', null, waRequest::TYPE_STRING_TRIM);

            $data = array(
                'status' => $status,
                'group_ids' => $group_ids,
                'conditions' => $conditions,
                'url' => $url,
                'description' => $description,
                'params' => $params,
                'name' => $name
            );
            if ($status <= 0) {
                $data['hash'] = md5(uniqid(time(), true));
            }
            if (waRequest::post('order') == 'rate') {
                $data['params']['order'] = 'rate';
            }
            if (!$this->validate($data)) {
                return;
            }

            $this->save($data);
            $apply_all_photos = waRequest::post('apply_all_photos', 0, waRequest::TYPE_INT);
            if ($apply_all_photos) {
                // apply to first of $count photos
                $count = waRequest::post('count', 50, waRequest::TYPE_INT);
                $collection = new photosCollection('album/'.$this->id);
                $total_count = $collection->count();
                $photos = $collection->getPhotos('*', 0, $count, false);
                $photo_model = new photosPhotoModel();

                $photo_ids = array();
                foreach ($photos as $photo) {
                    if ($photo['status'] == 1 && $status == 1) {
                        continue;
                    }
                    if ($photo['stack_count'] > 0) {
                        $photo_ids = array_merge($photo_ids, $photo_model->getIdsByParent($photo['id']));
                    } else {
                        $photo_ids[] = $photo['id'];
                    }
                }

                $photo_rights_model = new photosPhotoRightsModel();
                $allowed_photo_ids = $photo_rights_model->filterAllowedPhotoIds($photo_ids, true);

                $photo_model->updateAccess($allowed_photo_ids, $status, $group_ids);
                $this->response['total_count'] = $total_count;
                $this->response['count'] = $count;
                $this->response['status'] = $status;
                $this->response['groups'] = $group_ids;
            }
        }
    }

    private function save($data)
    {
        $just_created = false;
        if (!$this->id) {
            $this->log('album_create', 1);
            $this->id = $this->album_model->add($data);
            $just_created = true;
        } else {
            $album = $this->album_model->getById($this->id);
            if (!$album) {
                throw new Exception("Album doesn't exist");
            }
            if ($album['parent_id']) {
                $parent = $this->album_model->getById($album['parent_id']);
                if ($parent && $parent['status'] <= 0) {
                    $data['status'] = 0;
                }
            }
            $name = $album['name'];
            if (empty($data['name'])) {
                $data['name'] = $name;
            }
            if ($album['type'] != photosAlbumModel::TYPE_DYNAMIC && isset($data['conditions'])) {
                unset($data['conditions']);
            }
            if ($data['status'] <= 0) {
                if (isset($data['url']) && !$data['url']) {
                    unset($data['url']);
                }
            } else {
                if (!isset($data['url']) || !strlen($data['url'])) {
                    $data['url'] = photosPhoto::suggestUrl($data['name']);
                }
            }
            $this->album_model->update($this->id, $data);
            $album_params = new photosAlbumParamsModel();
            $album_params->set($this->id, $data['params']);
        }
        $album_rights_model = new photosAlbumRightsModel();
        if ($data['status'] <= 0 && $data['group_ids']) {
            $album_rights_model->setRights($this->id, $data['group_ids']);
        } else {
            $album_rights_model->setRights($this->id, 0);
        }

        /**
         * Extend save new album or update album settings process
         * Make extra workup
         * @event album_save
         * @params array[string]int $params['id'] Album id
         * @params array[string]bool $params['just_created'] Has album be just created
         */
        $params = array('id' => $this->id, 'just_created' => $just_created);
        wa()->event('album_save', $params);

    }

    private function validate($data)
    {
        $album = $this->album_model->getById($this->id);

        // check url
        $parent_id = $album['parent_id'];
        if ($data['url'] != null) {
            if ($this->album_model->urlExists($data['url'], $this->id, $parent_id)) {
                $this->errors['url'] = _w('URL is in use');
            }
        }
        return empty($this->errors);
    }

    private function getPrepareConditions()
    {
        $raw_condition = waRequest::post('condition');
        $raw_condition = array_fill_keys((array)$raw_condition, false);

        $conditions = array();
        if (isset($raw_condition['rate'])) {
            $raw_condition['rate'] = waRequest::post('rate');
            $conditions[] = 'rate' . $raw_condition['rate'][0] . $raw_condition['rate'][1];
        }
        if (isset($raw_condition['tag'])) {
            $tags = (array)waRequest::post('tag', array());
            if (!empty($tags)) {
                $conditions[] = 'tag=' . implode('||', $tags);
            }
        }
        if (isset($raw_condition['access'])) {
            $raw_condition['access'] = waRequest::post('access');
            if ($raw_condition['access'] == 'public') {
                $conditions[] = 'status=1';
            } else {
                $conditions[] = 'status<=0';
            }
            $raw_condition['access'] = false;
        }
        $conditions = implode('&', $conditions);
        return $conditions;
    }
}
