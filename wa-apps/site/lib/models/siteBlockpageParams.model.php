<?php
class siteBlockpageParamsModel extends waPageParamsModel
{
    protected $table = 'site_blockpage_params';

    public function setOne($page_id, $name, $value)
    {
        $this->multipleInsert([[
            'page_id' => $page_id,
            'name' => $name,
            'value' => $value,
        ]], ['value']);
    }

    public function deleteOne($page_id, $name)
    {
        $this->deleteByField([
            'page_id' => $page_id,
            'name' => $name,
        ]);
    }
}
