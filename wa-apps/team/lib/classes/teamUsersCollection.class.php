<?php

class teamUsersCollection extends waContactsCollection
{
    public function orderBy($field, $order = 'ASC')
    {
        $order_by = null;

        if ($field == '_display_name') {

            // Read user name display fields from settings
            $asm = new waAppSettingsModel();
            $user_name_display = $asm->get('webasyst', 'user_name_display', 'name');
            if ($user_name_display) {
                $user_name_display = explode(',', $user_name_display);
            } else {
                // Fall back to fields order for a plain contact
                $user_name_display = waContactNameField::getNameOrder();
            }

            $actual_name_fields = array();

            $allowed_fields = array('firstname', 'middlename', 'lastname', 'login');
            $allowed_fields = array_fill_keys($allowed_fields, true);
            foreach ($user_name_display as $field) {
                $field = trim($field);
                if (empty($allowed_fields[$field])) {
                    continue;
                }
                $actual_name_fields[] = 'TRIM(' . $field . ')';
            }

            if ($actual_name_fields) {
                $sort_field = join(',', $actual_name_fields);
                $sort_field = "CONCAT({$sort_field})";

                if (strtolower(trim($order)) == 'desc') {
                    $order = 'DESC';
                } else {
                    $order = 'ASC';
                }

                $order_by = $sort_field . ' ' . $order;

            }
        }

        if ($order_by !== null) {
            return $this->order_by = $order_by;
        } else {
            return parent::orderBy($field, $order);
        }
    }
}
