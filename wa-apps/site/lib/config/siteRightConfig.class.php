<?php 

class siteRightConfig extends waRightConfig
{
    public function init()
    {
        $model = new siteDomainModel();
        $items = $model->select('id, name')->fetchAll('id', true);
        $this->addItem('domain', _w('Available sites'), 'list', array('items' => $items, 'value' => bindec('11111111')));
    }
} 