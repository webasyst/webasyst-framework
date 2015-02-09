<?php
/**
 * Backend sidebar HTML to use in layout or request via XHR.
 */
class mailerBackendSidebarAction extends waViewAction
{
    public function execute()
    {
        // Filter drafts by access rights
        $access_sql = '';
        if (!mailerHelper::isInspector()) {
            $access_sql = ' AND create_contact_id='.wa()->getUser()->getId();
        }

        // List of drafts
        $mm = new mailerMessageModel();
        $drafts = $mm->select('*')->where('is_template=0 AND status IN (i:draft, i:pending)'.$access_sql, array('draft' => mailerMessageModel::STATUS_DRAFT, 'pending' => mailerMessageModel::STATUS_PENDING))->order('id DESC')->fetchAll('id');
        foreach($drafts as &$d) {
            $d['pic_src'] = '';
            if (!empty($d['create_contact_id'])) {
                try {
                    $d['pic_src'] = wao(new waContact($d['create_contact_id']))->getPhoto(20);
                } catch (Exception $e) {}
            }
        }
        unset($d);

        $this->view->assign('drafts', $drafts);

        // Count total number of sent messages and number of currently sending
        $mm = new mailerMessageModel();
        $cnt = $mm->countSent();

        // Plugin blocks
        $plugin_blocks = array(/*
            block_id => array(
                'id' => block_id
                'html' => ...
            )
        */);
        foreach(wa()->event('sidebar.blocks') as $app_id => $one_or_more_blocks) {
            if (!isset($one_or_more_blocks['html'])) {
                $i = '';
                foreach($one_or_more_blocks as $block) {
                    $key = isset($block['id']) ? $block['id'] : $app_id.$i;
                    $plugin_blocks[$key] = $block;
                    $i++;
                }
            } else {
                $key = isset($one_or_more_blocks['id']) ? $one_or_more_blocks['id'] : $app_id;
                $plugin_blocks[$key] = $one_or_more_blocks;
            }
        }

        $templates_count = $subscribers_count = $unsubscribers_count = $undeliverable_count = 0;
        if (mailerHelper::isAdmin()) {
            $tm = new mailerTemplateModel();
            $templates_count = $tm->countAll();

            $sm = new mailerSubscriberModel();
            $subscribers_count = $sm->countListView('');

            $um = new mailerUnsubscriberModel();
            $unsubscribers_count = $um->countAll();

            $sql = "SELECT COUNT(*)
                    FROM wa_contact_emails AS ce
                        JOIN wa_contact AS c
                            ON c.id=ce.contact_id
                    WHERE ce.status='unavailable'";
            $undeliverable_count = $um->query($sql)->fetchField();
        }

        $this->view->assign('plugin_blocks', $plugin_blocks);
        $this->view->assign('total_sent', $cnt['total_sent']);
        $this->view->assign('sending_count', $cnt['sending_count']);
        $this->view->assign('templates_count', $templates_count);
        $this->view->assign('subscribers_count', $subscribers_count);
        $this->view->assign('unsubscribers_count', $unsubscribers_count);
        $this->view->assign('undeliverable_count', $undeliverable_count);
    }
}

