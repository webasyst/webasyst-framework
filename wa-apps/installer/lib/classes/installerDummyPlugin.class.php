<?php

class installerDummyPlugin extends waPlugin
{
    public function __construct($info)
    {
        $this->info = $info;
        $this->id = $this->info['id'];
        if (isset($this->info['app_id'])) {
            $this->app_id = $this->info['app_id'];
        } else {
            throw new waException('missed app_id');
        }
        $this->path = wa()->getAppPath('plugins/'.$this->id, $this->app_id);
    }
}
