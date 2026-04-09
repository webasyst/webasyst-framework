<?php

class siteFooterBottomPoweredByBlockType extends siteBlockType
{
    public function __construct(array $options = [])
    {
        $options['type'] = 'site.FooterBottomPoweredBy';
        parent::__construct($options);
    }

    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $this->assignData($result);

        return $result;
    }

    private function assignData (siteBlockData $block_data) {
        $block_data->db_row = [
            'id' => uniqid('id'),
        ];

        $block_data->data['disabled'] = true;
    }
}
