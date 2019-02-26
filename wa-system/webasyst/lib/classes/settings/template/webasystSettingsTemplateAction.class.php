<?php

abstract class webasystSettingsTemplateAction extends webasystSettingsViewAction
{
    /**
     * @var waVerificationChannel
     */
    protected $channel;

    /**
     * cached models for models getters
     * @var array
     */
    protected $models;

    public function __construct($params = null)
    {
        parent::__construct($params);

        $channel_id = $this->getRequestId();
        $this->channel = waVerificationChannel::factory($channel_id);

        $this->view->assign(array(
            'templates_list' => $this->channel->getTemplatesList(),
        ));
    }

    /**
     * @return mixed
     */
    protected function getRequestId()
    {
        $id = waRequest::param('id', null, waRequest::TYPE_INT);
        if ($id === null) {
            $id = waRequest::request('id', null, waRequest::TYPE_INT);
        }
        return $id;
    }


    /**
     * @return waVerificationChannelModel
     */
    protected function getVerificationChannelModel()
    {
        return $this->getModel('verification_channel', 'waVerificationChannelModel');
    }

    /**
     * @param $key
     * @param $class
     * @throws waException
     * @return waModel
     */
    private function getModel($key, $class)
    {
        if (!isset($this->models[$key]) || get_class($this->models[$key]) !== $class) {
            $this->models[$key] = new $class();
        }
        if (!($this->models[$key] instanceof waModel)) {
            throw new waException('Class must be instance of waModel');
        }
        return $this->models[$key];
    }
}
