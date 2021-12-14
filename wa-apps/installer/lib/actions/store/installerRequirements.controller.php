<?php

class installerRequirementsController extends waJsonController
{
    /**
     * @var array
     */
    protected $requirements;

    public function execute()
    {
        $this->addHeaders();

        $requirements = waRequest::post('requirements', array(), waRequest::TYPE_ARRAY_TRIM);
        $checker = new installerRequirementsChecker($requirements);
        $warning_requirements = $checker->check();
        if (!empty($warning_requirements)) {
            $this->errors = $warning_requirements;
        }
    }

    protected function addHeaders()
    {
        if ($origin = waRequest::server('HTTP_ORIGIN')) {
            $this->getResponse()->addHeader('Access-Control-Allow-Origin', $origin);
        }
        $this->getResponse()->addHeader('Access-Control-Allow-Methods', 'POST');
        $this->getResponse()->addHeader('Access-Control-Allow-Headers', 'Origin');
        $this->getResponse()->addHeader('Access-Control-Allow-Credentials', 'true');
        $this->getResponse()->addHeader('Vary', 'Origin');
        $this->getResponse()->addHeader('Content-Type', 'text/javascript; charset=utf-8');
        $this->getResponse()->sendHeaders();
    }
}
