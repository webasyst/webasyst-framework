<?php

class mailerBackendLayout extends waLayout
{
    public function execute()
    {
        // Layout caching is forbidden
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Expires: " . date("r"));
        $this->executeAction('sidebar', new mailerBackendSidebarAction());

        // Plugin blocks
        $plugin_blocks = array(/*
            block_id => array(
                'id' => block_id
                'html' => ...
            )
        */);
        foreach(wa()->event('head.blocks') as $app_id => $one_or_more_blocks) {
            if (!is_array($one_or_more_blocks)) {
                $one_or_more_blocks = array(
                    'html' => (string) $one_or_more_blocks,
                );
            }
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

        $this->view->assign('plugin_blocks', $plugin_blocks);

        //$this->view->assign('admin', wa()->getUser()->getRights('helpdesk', 'backend') > 1);
        //$this->view->assign('global_admin', wa()->getUser()->getRights('webasyst', 'backend') > 0);
    }
}
