<?php

class mailerBackendAction extends waViewAction
{
    public function execute()
    {
        $this->setLayout(new mailerBackendLayout());
    }
}
