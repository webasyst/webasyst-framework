<?php

class webasystSettingsTemplateSMSSaveController extends webasystSettingsTemplateSaveController
{
    public function execute()
    {
        if (!$this->channel) {
            return;
        }

        $data = $this->getData();
        $errors = $this->validate($data);
        if (!$this->isErrorsEmpty($errors)) {
            return $this->sendErrors($errors);
        }

        $this->channel->save($data);

        $this->setResponse(array(
            'channel' => $this->channel->getInfo()
        ));
    }

    protected function setResponse($response)
    {
        $this->response = $response;
    }

    protected function sendErrors($errors, $ns = 'data')
    {
        foreach ($errors as $key => $error) {
            if ((!is_scalar($error) && !is_array($error)) || empty($error)) {
                unset($errors[$key]);
                continue;
            }
        }
        return $this->errors = $errors;
    }

    protected function getData()
    {
        $data = $this->getRequest()->post('data');
        $data['name'] = isset($data['name']) && is_scalar($data['name']) ? $data['name'] : '';
        $data['address'] = isset($data['address']) && is_scalar($data['address']) ? $data['address'] : '';
        return $data;
    }

    protected function validate($data)
    {
        $errors = array(
            'name' => array(),
            'address' => array()
        );
        if (strlen($data['name']) <= 0) {
            $errors['name'] = _ws('This field is required');
        }
        if (strlen($data['address']) <= 0) {
            $errors['address'] = _ws('This field is required');
        }

        return $errors;
    }

    protected function isErrorsEmpty($errors)
    {
        foreach ($errors as $error) {
            if (!empty($error)) {
                return false;
            }
        }
        return true;
    }
}