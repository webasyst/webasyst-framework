<?php

class webasystSettingsFieldConditionalValuesDialogAction extends webasystSettingsViewAction
{
    public function execute()
    {
        $field = waRequest::get('field', null, waRequest::TYPE_STRING_TRIM);
        if (!$field) {
            throw new waException(_ws("Unknown field"));
        }

        // List of field values
        $cfvm = new waContactFieldValuesModel();
        $fields = $cfvm->getInfo($field);

        // Possible parent fields this conditional field may depend on
        $parent_fields = array();
        foreach(waContactFields::getAll('person') as $f) {
            /** @var waContactField $f */
            if (!($f instanceof waContactCompositeField) && !$f->isMulti()) {
                $parent_fields[$f->getId()] = $f->getName();
            }
        }
        $field_ids = explode(':', $field);
        $f = waContactFields::get($field_ids[0]);
        if (!empty($field_ids[1]) && $f && $f instanceof waContactCompositeField) {
            $subfields = $f->getFields();
            foreach($subfields as $sfid => $sf) {
                $pid = $f->getId().':'.$sfid;
                if ($pid !== $field) {
                    $parent_fields[$pid] = $f->getName().' — '.$sf->getName();
                }
            }
        }

        // Selected parent field
        $parent_selected = null;
        if ($fields) {
            $parent_selected = reset($fields);
            $parent_selected = $parent_selected['field'];
        }

        // Human readable name of current field
        if (!empty($field_ids[1]) && !empty($subfields[$field_ids[1]])) {
            $title = $subfields[$field_ids[1]]->getName();
        } else if ($f) {
            $title = $f->getName();
        } else {
            // Loose guess on whether this field has just been created
            $new_field = false;
            if (substr($field, 0, 2) == '__') {
                $new_field = true;
            } else if (!empty($field_ids[1]) && substr($field_ids[1], 0, 2) == '__') {
                $new_field = true;
            }

            if ($new_field) {
                $title = _ws('Conditional field');
            } else {
                $title = _ws(ucfirst($field));
            }
        }

        $this->view->assign(array(
            'field'           => $field,
            'title'           => $title,
            'fields'          => $fields,
            'parent_fields'   => $parent_fields,
            'parent_selected' => $parent_selected,
        ));
    }
}