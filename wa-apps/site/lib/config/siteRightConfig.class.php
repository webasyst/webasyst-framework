<?php 

class siteRightConfig extends waRightConfig
{
    public function init()
    {
        $model = new siteDomainModel();
        $items = $model->select('id, name')->fetchAll('id', true);
        foreach ($items as &$item) {
            $item = waIdna::dec($item);
        }
        unset($item);
        $this->addItem('domain', _w('Available sites'), 'list', array('items' => $items, 'value' => bindec('11111111')));
    }
} 