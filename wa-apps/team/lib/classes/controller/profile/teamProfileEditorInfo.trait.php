<?php

trait teamProfileEditorInfoTrait
{
    protected function getEditorOptions()
    {
        $tasm = new teamWaAppSettingsModel();
        
        return [
            'saveUrl' => $this->getSaveUrl($this->canEdit()),
            'contact_id' => $this->profile_contact->getId(),
            'current_user_id' => $this->getUserId(),
            'justCreated' => false,
            'geocoding' => $tasm->getGeocodingOptions(),
            'wa_app_url' => $this->isOwnProfile() ? wa()->getConfig()->getBackendUrl(true) : wa()->getAppUrl(null, true),
            'contactType' => $this->profile_contact['is_company'] ? 'company' : 'person'
        ];
    }

    protected function getSaveUrl($can_edit)
    {
        if ($can_edit === 'limited_own_profile') {
            $app_url = wa()->getConfig()->getBackendUrl(true);
        } else {
            $app_url = wa()->getUrl();
        }
        return $app_url.'?module=profile&action=save';
    }

    protected function getEditorProfileData()
    {
        $contactFields = waContactFields::getInfo($this->profile_contact['is_company'] ? 'company' : 'person', true);

        // Main contact editor data
        $fieldValues = $this->profile_contact->load('js', true);
        if (!empty($fieldValues['company_contact_id'])) {
            $m = new waContactModel();
            if (!$m->getById($fieldValues['company_contact_id'])) {
                $fieldValues['company_contact_id'] = 0;
                $this->profile_contact->save(array('company_contact_id' => 0));
            }
        }

        // Only show fields that are allowed in own profile
        $can_edit = $this->canEdit();
        if ($can_edit === 'limited_own_profile') {
            $allowed = array();
            foreach (waContactFields::getAll('person') as $f) {
                if ($f->getParameter('allow_self_edit')) {
                    $allowed[$f->getId()] = true;
                }
            }

            $fieldValues = array_intersect_key($fieldValues, $allowed);
            $contactFields = array_intersect_key($contactFields, $allowed);
        }

        // Normalize field values
        foreach ($contactFields as $field_info) {
            if (is_object($field_info) && $field_info instanceof waContactField) {
                $field_info = $field_info->getInfo();
            }
            if ($field_info['multi'] && isset($fieldValues[$field_info['id']])) {
                $fieldValues[$field_info['id']] = array_values($fieldValues[$field_info['id']]);
            }
            if ($field_info['id'] === 'timezone') {
                // This hack is here rather than correct definition in waContactTimezoneField
                // because of backwards compatibility with older version of Contacts app
                // that does not know nothing about special Timezone field type.
                $contactFields[$field_info['id']]['type'] = 'Timezone';
            }
        }

        return [
            'contactFields' => $contactFields,
            'contactFieldsOrder' => array_keys($contactFields),
            'fieldValues' => $fieldValues
        ];
    }

    protected function getCoverThumbnails()
    {
        $list = new waContactCoverList($this->profile_contact['id']);
        return $list->getThumbnails();
    }
}
