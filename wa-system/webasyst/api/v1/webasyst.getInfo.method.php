<?php

class webasystGetInfoMethod extends waAPIMethod
{
    public function execute()
    {
        if (!$this->getRights('backend')) {
            throw new waAPIException('access_denied', 403);
        }

        $this->response = [
            'name' => wa()->accountName()
        ];
    }
}
