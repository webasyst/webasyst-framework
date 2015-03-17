<?php

/**
 * Content block for recipients selection form.
 * Shows checklist to select contact categories.
 */
class mailerCampaignsRecipientsBlockCategoriesAction extends waViewAction
{
    public function execute()
    {
        // Fetch category names and counts
        $rows = array();
        $labels = array();
        $mrm = new mailerMessageRecipientsModel();
        $sql = "SELECT id AS value, name, system_id FROM wa_contact_category";
        foreach($mrm->query($sql) as $row) {
            $row['num'] = 0;
            $rows[$row['value']] = $row;

            // Use app name for app categories
            if ($row['system_id']) {
                if (wa()->appExists($row['system_id'])) {
                    $app = wa()->getAppInfo($row['system_id']);
                    $row['name'] = $app['name'];
                }
            }

            $labels[$row['value']] = $row['name'];
        }
        $sql = "SELECT category_id AS id, COUNT(*) AS num
                FROM  `wa_contact_categories`
                GROUP BY category_id";
        foreach($mrm->query($sql) as $row) {
            if (!empty($rows[$row['id']])) {
                $rows[$row['id']]['num'] = $row['num'];
            }
        }

        if (!$rows) {
            // Will be caught by RecipientsFormHandler and it will remove this whole block
            throw new waException('No categories');
        }

        $data = self::getChecklistOptions($rows, $this->params['selected'], $labels);
        usort($data, array($this, 'sortHelper'));

        // Does user have admin access to contacts app?
        $is_contacts_admin = wa()->getUser()->getRights('contacts', 'backend') > 1;

        // Disable checkboxes for categories that user does not have access rights for
        if (!$is_contacts_admin) {
            wa('contacts');
            $rm = new contactsRightsModel();
            $allowed = $rm->getAllowedCategories();
            if ($allowed !== true) {
                // For all categories that the user does not have access to:
                // Disable selected categories; do not show unselected categories at all.
                $something_to_show = false;
                foreach($data as $id => &$d) {
                    if (!empty($allowed[$id])) {
                        $something_to_show = true;
                    } else if ($d['checked']) {
                        $something_to_show = true;
                        $d['disabled'] = true;
                        unset($d['list_id']);
                    } else {
                        unset($data[$id]);
                    }
                }
                if (!$something_to_show) {
                    // Will be caught by RecipientsFormHandler and it will remove this whole block
                    throw new waException('No categories');
                }
            }
        }

        // Additional vars for categories template
        $this->view->assign('data', $data);
        $this->view->assign('is_contacts_admin', $is_contacts_admin);
        $this->view->assign('all_selected_id', $this->params['all_selected_id']);
    }

    /** Helper to get list options. */
    public static function getChecklistOptions($rows, $selected, $labels=array())
    {
        // Get distinct values from DB
        $options = array();
        foreach($rows as $row) {
            $v = $row['value'];
            $options[$v] = array(
                'label' => ifset($labels[$v], $v),
                'value' => $v,
                'checked' => false,
                'disabled' => false,
                'num' => $row['num'],
            );
        }

        // List of options that are checked
        foreach($selected as $list_id => $v) {
            $options[$v]['list_id'] = $list_id;
            $options[$v]['checked'] = true;
        }

        return $options;
    }

    public function sortHelper($a, $b)
    {
        return strcmp($a['label'], $b['label']);
    }
}

