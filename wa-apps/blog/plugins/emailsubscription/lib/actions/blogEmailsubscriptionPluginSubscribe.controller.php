<?php

class blogEmailsubscriptionPluginSubscribeController extends waJsonController
{
    public function execute()
    {
        if ($blog_id = waRequest::post('blog_id')) {
            $model = new blogEmailsubscriptionModel();
            if (waRequest::post('subscribe')) {
                $model->insert(array(
                    'blog_id' => $blog_id,
                    'contact_id' => wa()->getUser()->getId(),
                    'status' => 1,
                    'datetime' => date('Y-m-d H:i:s')
                ), 1);
            } else {
                $model->deleteByField(array(
                    'blog_id' => $blog_id,
                    'contact_id' => wa()->getUser()->getId()
                ));
            }
        }
    }
}