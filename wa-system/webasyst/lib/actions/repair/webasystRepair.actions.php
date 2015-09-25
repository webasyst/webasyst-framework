<?php

class webasystRepairActions extends waActions
{
    public function widgetsAction()
    {
        $contact_id = $this->getUserId();
        $widget_model = new waWidgetModel();
        $rows = $widget_model->getByContact($contact_id);

        $data = array();
        foreach ($rows as $row) {
            $data[$row['block']][] = $row;
        }

        $w = $b = 0;

        $real_block = 0;
        foreach ($data as $block => $block_data) {
            if ($real_block != $block) {
                $b++;
                $widget_model->updateByField(array(
                    'contact_id' => $contact_id,
                    'dashboard_id' => null,
                    'block' => $block
                ), array('block' => $real_block));
            }
            foreach ($block_data as $sort => $row) {
                if ($row['sort'] != $sort) {
                    $widget_model->updateById($row['id'], array('sort' => $sort));
                    $w++;
                }
            }
            $real_block++;
        }

        echo 'OK';
        if ($b) {
            echo "\t".$b.' block(s) has been fixed.'.PHP_EOL;
        }
        if ($w) {
            echo "\t".$w.' widgets(s) has been fixed.'.PHP_EOL;
        }
    }
}