<?php

/** Merge contacts. */
class contactsContactsMergeController extends waJsonController
{
    public function execute()
    {
        // only allowed to admin
        if ($this->getRights('backend') <= 1) {
            throw new waRightsException('Access denied.');
        }

        // Ids of contacts to merge
        $master_id = waRequest::post('master_id', waRequest::TYPE_INT, 0);
        $merge_ids = waRequest::post('slave_ids', array(), 'array_int');
        if (!$merge_ids) {
            throw new waException('No contacts to merge.');
        }

        // Merge
        $merge_result = self::merge($merge_ids, $master_id);

        // Prepare response
        $this->response['all'] = $merge_result['total_requested'];
        $this->response['users'] = $merge_result['users'];
        if (!empty($merge_result['error'])) {
            $this->response['message'] = $merge_result['error'];
            return;
        }

        // Prepare UI message
        if ($merge_result['total_merged'] > 1) {
            $message = sprintf(_w("%s of %s contacts have been merged"), $merge_result['total_merged'], $merge_result['total_requested']);
            $this->log("contact_merge", $merge_result['total_merged']);
        } else {
            $message = _w("No contacts were merged");
        }
        if ($merge_result['users']) {
            $message .= '<br />'.$merge_result['users']." "._w("contact", "contacts", $merge_result['users'])._w(" were skipped because they have user accounts");
        }
        $this->response['message'] = $message;
    }

    /**
     * Merge given contacts into master contact, save, send merge event, then delete slaves.
     *
     * !!! Probably should move it into something like contactsHelper
     *
     * @param array $merge_ids list of contact ids
     * @param int $master_id contact id to merge others into
     * @return array
     */
    public static function merge($merge_ids, $master_id)
    {
        $merge_ids[] = $master_id;

        // List of contacts to merge
        $collection = new contactsCollection('id/'.implode(',', $merge_ids));
        $contacts_data = $collection->getContacts('*');

        // Master contact data
        if (!$master_id || !isset($contacts_data[$master_id])) {
            throw new waException('No contact to merge into.');
        }
        $master_data = $contacts_data[$master_id];
        unset($contacts_data[$master_id]);
        $master = new waContact($master_id);

        $result = array(
            'total_requested' => count($contacts_data) + 1,
            'total_merged' => 0,
            'error' => '',
            'users' => 0,
        );

        if ($master_data['photo']) {
            $filename = wa()->getDataPath(waContact::getPhotoDir($master_data['id'])."{$master_data['photo']}.original.jpg", true, 'contacts');
            if (!file_exists($filename)) {
                $master_data['photo'] = null;
            }
        }
        
        $data_fields = waContactFields::getAll('enabled');
        $check_duplicates = array();    // field_id => true
        $update_photo = null;               // if need to update photo here it is file paths
        
        // merge loop
        foreach ($contacts_data as $id => $info) {
            if ($info['is_user']) {
                $result['users']++;
                unset($contacts_data[$id]);
                continue;
            }

            foreach ($data_fields as $f => $field) {
                if (!empty($info[$f])) {
                    if ($field->isMulti()) {
                        $master->add($f, $info[$f]);
                        $check_duplicates[$f] = true;
                    } else {
                        // Field does not allow multiple values.
                        // Set value if no value yet.
                        if (empty($master_data[$f])) {
                            $master[$f] = $master_data[$f] = $info[$f];
                        }
                    }
                }
            }
            
            // photo
            if (!$master_data['photo'] && $info['photo'] && !$update_photo) {
                $filename_original = wa()->getDataPath(waContact::getPhotoDir($info['id'])."{$info['photo']}.original.jpg", true, 'contacts');
                if (file_exists($filename_original)) {
                    $update_photo = array(
                        'original' => $filename_original
                    );
                    $filename_crop = wa()->getDataPath(waContact::getPhotoDir($info['id'])."{$info['photo']}.jpg", true, 'contacts');
                    if (file_exists($filename_crop)) {
                        $update_photo['crop'] = $filename_crop;
                    }
                }
            }
            
        }

        // Remove duplicates
        foreach(array_keys($check_duplicates) as $f) {
            $values = $master[$f];
            if (!is_array($values) || count($values) <= 1) {
                continue;
            }

            $unique_values = array(); // md5 => true
            foreach($values as $k => $v) {
                if (is_array($v)) {
                    if (isset($v['value']) && is_string($v['value'])) {
                        $v = $v['value'];
                    } else {
                        unset($v['ext'], $v['status']);
                        ksort($v);
                        $v = serialize($v);
                    }
                }
                $hash = md5(mb_strtolower($v));
                if (!empty($unique_values[$hash])) {
                    unset($values[$k]);
                    continue;
                }
                $unique_values[$hash] = true;
            }
            $master[$f] = array_values($values);
        }

        // Save master contact
        $errors = $master->save(array(), 42); // 42 == do not validate anything at all
        if ($errors) {
            $errormsg = array();
            foreach ($errors as $field => $err) {
                if (!is_array($err)) {
                    $err = array($err);
                }
                foreach($err as $str) {
                    $errormsg[] = $field.': '.$str;
                }
            }

            $result['error'] = implode("\n<br>", $errormsg);
            return $result;
        }

        // Merge categories
        $category_ids = array();
        $ccm = new waContactCategoriesModel();
        foreach($ccm->getContactsCategories($merge_ids) as $cid => $cats) {
            $category_ids += array_flip($cats);
        }
        $category_ids = array_keys($category_ids);
        $ccm->add($master_id, $category_ids);
        
        // update photo
        if ($update_photo) {
            $rand = mt_rand();
            $apth = waContact::getPhotoDir($master['id']);
            
            // delete old image
            if (file_exists($path)) {
                waFiles::delete($path);
            }
            waFiles::create($path);
            
            $filename = $path."/".$rand.".original.jpg";
            waFiles::create($filename);
            waImage::factory($update_photo['original'])->save($filename, 90);
            
            if (!empty($update_photo['crop'])) {
                $filename = $path."/".$rand.".jpg";
                waFiles::create($filename);
                waImage::factory($update_photo['crop'])->save($filename, 90);
            } else {
                waFiles::copy($filename, $path."/".$rand.".jpg");
            }
            
            $master->save(array(
                'photo' => $rand
            ));
        }

        $result['total_merged'] = count($contacts_data) + 1;

        $contact_ids = array_keys($contacts_data);
        
        // Merge event
        $params = array('contacts' => $contact_ids, 'id' => $master_data['id']);
        wa()->event('merge', $params);
        
        // Delete all merged contacts
        $contact_model = new waContactModel();
        $contact_model->delete($contact_ids, false); // false == do not trigger event
        
        $history_model = new contactsHistoryModel();
        foreach ($contact_ids as $contact_id) {
            $history_model->deleteByField(array(
                'type' => 'add',
                'hash' => '/contact/' . $contact_id
            ));
        }

        return $result;
    }
}

