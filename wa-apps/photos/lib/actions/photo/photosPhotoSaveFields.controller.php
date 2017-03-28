<?php

class photosPhotoSaveFieldsController extends waJsonController
{
    /**
     *
     * Fields for update at entire stack
     * @var array
     */
    private $stack_fields = array(
        'description','rate',
    );
    /**
     *
     * Fields for update separately
     * @var array
     */
    private $generic_fields = array(
        'name', 'url',
    );

    public function execute()
    {
        $available_fields = array_merge($this->generic_fields, $this->stack_fields);
        $data = (array) waRequest::post('data');
        $photo_id = array();
        foreach ($data as &$item_data) {
            if (isset($item_data['id']) && ($id = array_unique(array_map('intval', explode(',', $item_data['id'])))) ) {
                unset ($item_data['id']);
                $fields = array_diff_key(array_keys($item_data), $available_fields);
                if ($fields) {
                    throw new waException("Invalid request format: unexpected field(s) ".implode(', ',$fields));
                }
                $photo_id = array_merge($photo_id,$id);
                $item_data['id'] = $id;
            } else {
                throw new waException("Invalid request format: missed or invalid item ID");
            }
        }
        unset($item_data);
        $this->response['update'] = array();


        if ($photo_id) {
            $photo_rights_model = new photosPhotoRightsModel();
            $allowed_photo_id = $photo_rights_model->filterAllowedPhotoIds($photo_id, true);
            $denied_photo_id = array_diff($photo_id, $allowed_photo_id);

            if ($allowed_photo_id) {
                $photo_model = new photosPhotoModel();
                $generic_fields = array_fill_keys($this->generic_fields, true);
                $stack_fields = array_fill_keys($this->stack_fields, true);
                foreach ($data as $item_data) {
                    if ($item_data_id = array_intersect($item_data['id'], $allowed_photo_id)) {
                        unset($item_data['id']);
                        foreach ($item_data as $field => &$value) {
                            $value = $this->validateField($field, $value);
                        }
                        unset($value);

                        if ($data =array_intersect_key($item_data, $stack_fields)) {
                            $photo_model->update($item_data_id, $data);
                            $this->response['update'][] = array('id'=>$item_data_id,'data'=>$data);
                        }
                        if ($data =array_intersect_key($item_data, $generic_fields)) {
                            $photo_model->updateById($item_data_id, $data);
                            $this->response['update'][] = array('id'=>$item_data_id,'data'=>$data);
                        }
                    }
                }
            }

            if (count($denied_photo_id) > 0 && count($photo_id) > 0) {
                $this->response['alert_msg'] = photosPhoto::sprintf_wplural(
                        "The operation was not performed to %d photo (%%s)",
                        "The operation was not performed to %d photos (%%s)",
                count($denied_photo_id),
                _w("out of %d selected", "out of %d selected", count($photo_id))
                ) . ', ' . _w("because you don't have sufficient access rights") . '.';
            }
            $allowed_photo_id_map = array();
            foreach ($allowed_photo_id as $id) {
                $allowed_photo_id_map[$id] = true;
            }
            $this->response['allowed_photo_id'] = $allowed_photo_id_map;
        }
    }

    private function validateField($name, $value)
    {
        switch($name) {
            case 'rate': {
                $value = max(0,min,5,intval($value));
                break;
            }
        }
        return $value;
    }

}
