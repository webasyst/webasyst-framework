<?php

/**
 * Implements core recipient selection criteria.
 * See recipients.prepare event description for details.
 */
class mailerMailerRecipientsPrepareHandler extends waEventHandler
{
    public function execute(&$params)
    {
        $message_id = (int) $params['id'];
        $recipients = &$params['recipients'];
        $mlm = new mailerMessageLogModel();
        $msm = new mailerSubscriberModel();
        $msl = new mailerSubscribeListModel();
        $cem = new waContactEmailsModel();

        $categories = null;
        $total_non_unique = 0;
        $insert_rows = array();
        $insert_sql = "INSERT IGNORE INTO mailer_draft_recipients (message_id, contact_id, name, email) VALUES ";
        foreach($recipients as $r_id => &$r) {
            $value = $r['value'];

            // Being paranoid...
            if (!strlen($value)) {
                continue;
            }

            // Skip list types supported by plugins
            if ($value{0} == '@') {
                continue;
            }

            // Is it subscribers list id?
            if (wa_is_int($value)) {
                $where_sql = "";
//                if ($params['action'] === 'NameAndCountRecipients') {
                    if ($value > 0) {
                        $list = $msl->getListById($value);

                        if (!$list) {
                            continue;
                        }
                    }
                    $r['name'] = $value ? $list['name'] : _w('All subscribers');
                    $r['count'] = $msm->countListView("", $value);
                    $r['group'] = _w('Subscribers');
//                }
                if ($params['action'] === 'UpdateDraftRecipientsTable') {
                    if ($value > 0) {
                        $where_sql = " WHERE s.list_id = ".(int)$value;
                    }

                    $sql = "INSERT IGNORE INTO mailer_draft_recipients (message_id, contact_id, name, email)
                                    SELECT ".$message_id.", IFNULL(c.id, 0), IFNULL(c.name, ''), e.email
                                    FROM mailer_subscriber AS s
                                    LEFT JOIN wa_contact AS c ON c.id = s.contact_id
                                    LEFT JOIN wa_contact_emails AS e ON e.id = s.contact_email_id
                                    {$where_sql}";
                    $mlm->exec($sql);
                }
                $total_non_unique += $r['count'];
                continue;
            }

            // Is it a ContactsCollection hash?
            if ($value{0} == '/') {
                $cc = new waContactsCollection($value);
                if (strpos($value, "prosearch") !== false || strpos($value, "/contacts/view") !== false) {
                    // todo: remove? Contacts Pro
                    wa('contacts');
                    $cc = new waContactsCollection($value);
                }

                if ($params['action'] === 'UpdateDraftRecipientsTable') {
                    $cc->saveToTable("mailer_draft_recipients", array(
                        'contact_id' => 'id',
                        'message_id' => $message_id,
                        'name',
                        'email' => '_email',
                    ), true);
                }
//                if ($params['action'] === 'NameAndCountRecipients') {

                    $r['count'] = $cc->count();
                    $r['group'] = null;
                    $r['name'] = null;

                    // See if the hash is of one of supported types
                    if (false !== strpos($value, '/category/')) {
                        $category_id = explode('/', $value);
                        $category_id = end($category_id);
                        if ($category_id && wa_is_int($category_id)) {
                            if ($categories === null) {
                                $ccm = new waContactCategoryModel();
                                $categories = $ccm->getNames();
                            }
                            $r['name'] = ifset($categories[$category_id], $category_id);
                            $r['group'] = _w('Categories');
                        }
                    } else if (false !== strpos($value, '/contacts/view/')) {
                        $contacts_list_id = explode('/', $value);
                        $contacts_list_id = end($contacts_list_id);

                        $ccm = new contactsViewModel();
                        $list = $ccm->get($contacts_list_id);
                        $r['name'] = $list['name'];
                        $r['group'] = _w('Contacts Lists');
                    } else if (false !== strpos($value, '/contacts/prosearch')) {
                        $r['name'] = $cc->getTitle();
                        $r['group'] = _w('Prosearch');
                    } else if (false !== strpos($value, '/locale=')) {
                        $locale = explode('=', $value);
                        $locale = end($locale);
                        if ($locale) {
                            $l = waLocale::getInfo($locale);
                            if ($l) {
                                $r['name'] = $l['name'];
                            }
                        } else {
                            $r['name'] = _w('not set');
                        }
                        $r['group'] = _w('Languages');
                        if (!$r['name']) {
                            $r['name'] = $locale;
                        }
                    } else if ($value == '/') {
                        $r['name'] = _w('All contacts');
                    }
                    if (!$r['name']) {
                        $r['name'] = $value;
                    }
//                }
                $total_non_unique += $r['count'];
                continue;
            }

            // Otherwise, it is a list of emails.
            $mail_addresses = wao(new mailerMailAddressParser($value))->parse();
//            if ($params['action'] === 'NameAndCountRecipients') {
                $r['count'] = count($mail_addresses);
                $r['group'] = null;
                $r['name'] = _w('Additional emails');
//            }
            if ($params['action'] === 'UpdateDraftRecipientsTable') {
                foreach ($mail_addresses as $address) {
                    $contact_id = (int)$cem->getContactIdByEmail($address['email']);
                    $insert_rows[] = sprintf("(%d,%d,'%s','%s')", $message_id, $contact_id, $mlm->escape($address['name']), $mlm->escape($address['email']));
                    if (count($insert_rows) > 50) {
                        $mlm->exec($insert_sql . implode(',', $insert_rows));
                        $insert_rows = array();
                    }
                }
            }
            $total_non_unique += $r['count'];
        }

        if ($params['action'] === 'UpdateDraftRecipientsTable') {
            if ($insert_rows) {
                $mlm->exec($insert_sql . implode(",", $insert_rows));
            }
            unset($insert_rows);
        }
        $params['recipients_count_not_unique'] = $total_non_unique;
    }
}

