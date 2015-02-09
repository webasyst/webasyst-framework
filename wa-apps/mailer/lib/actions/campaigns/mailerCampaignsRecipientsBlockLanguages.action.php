<?php

/**
 * Content block for recipients selection form.
 * Shows checklist to select contact locales.
 */
class mailerCampaignsRecipientsBlockLanguagesAction extends waViewAction
{
    public function execute()
    {
        // Fetch category names and counts
        $rows = array();
        $labels = array();
        $mrm = new mailerMessageRecipientsModel();
        $sql = "SELECT locale AS value, COUNT(*) AS num FROM wa_contact GROUP BY locale";
        foreach($mrm->query($sql) as $row) {
            $rows[$row['value']] = $row;
        }

        if (count($rows) <= 1 && empty($this->params['selected'])) {
            throw new waException('Nothing to show.');
        }

        // Does user have admin access to contacts app?
        $is_contacts_admin = wa()->getUser()->getRights('contacts', 'backend') > 1;

        $labels = waLocale::getAll('name');
        $labels[''] = _w('not set');
        $data = mailerCampaignsRecipientsBlockCategoriesAction::getChecklistOptions($rows, $this->params['selected'], $labels);

        if (!$is_contacts_admin) {
            foreach($data as $v => &$row) {
                if ($row['checked']) {
                    $row['disabled'] = true;
                } else {
                    unset($data[$v]);
                }
            }
            if (!$data) {
                throw new waException('Nothing to show.');
            }
        }

        $this->view->assign('data', $data);
        $this->view->assign('is_contacts_admin', $is_contacts_admin);
    }
}

