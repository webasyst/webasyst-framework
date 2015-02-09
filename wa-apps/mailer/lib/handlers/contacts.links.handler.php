<?php

class mailerContactsLinksHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $contact_ids = $params;

        $links_to_check = array(
            array('mailer_message_log', 'contact_id', _wd('mailer', 'Recipient'), ''),
            array('mailer_subscriber', 'contact_id', _wd('mailer', 'Subscriber'), ''),
        );

        $result = array();
        $m = new waModel();
        foreach($links_to_check as $data) {
            list($table, $field, $role, $message) = $data;
            if ($message === true) {
                $message = "This contact can not be merged into other contacts since it has %s link(s) in $table.$field.";
            }
            if (!$role) {
                $role = $table.'.'.$field;
            }

            $sql = "SELECT $field AS id, COUNT(*) AS n
                    FROM $table
                    WHERE $field IN (".implode(',', $contact_ids).")
                    GROUP BY $field";
            foreach ($m->query($sql) as $row) {
                $link = array(
                    'role' => $role,
                    'links_number' => $row['n'],
                );
                if ($message) {
                    $link['forbid_merge_reason'] = sprintf($message, $row['n']);
                }

                if (empty($result[$row['id']])) {
                    $result[$row['id']] = array();
                }
                $result[$row['id']][] = $link;
            }
        }

        return $result ? $result : null;
    }
}

