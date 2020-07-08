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

        $this->requirements = waRequest::post('requirements', array(), waRequest::TYPE_ARRAY_TRIM);
        $this->decodeRequirements();

        $warning_requirements = $this->getWarningRequirements();
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

    protected function decodeRequirements()
    {
        foreach ($this->requirements as &$requirement) {
            $json = str_replace("~", '"', $requirement['requirements']);
            $requirement['requirements'] = waUtils::jsonDecode($json, true);
        }
        unset($requirement);
    }

    protected function getWarningRequirements()
    {
        $this->testRequirements();

        $warning_requirements = array();

        foreach ($this->requirements as $requirement) {
            $product_id = $requirement['product_id'];
            foreach ($requirement['requirements'] as $subject => $r) {
                if (!empty($r['strict']) && !empty($r['warning'])) {
                    $text = $r['warning'];

                    if (preg_match('~^(phpini\.)~', $subject)) {
                        $text = !empty($r['name']) ? $r['name'] .": ". $text : $subject .": ". $text;
                    }

                    $warning_requirements[$product_id][] = $text;
                }
            }
        }

        return $warning_requirements;
    }

    protected function testRequirements()
    {
        $wa_installer_apps = 'wa-installer/lib/classes/wainstallerapps.class.php';
        $wa_installer_requirements = 'wa-installer/lib/classes/wainstallerrequirements.class.php';

        if (!class_exists('waInstallerApps') && file_exists(wa()->getConfig()->getRootPath() .'/'. $wa_installer_apps)) {
            $autoload = waAutoload::getInstance();
            $autoload->add('waInstallerApps', $wa_installer_apps);
            $autoload->add('waInstallerRequirements', $wa_installer_requirements);
        }

        if (class_exists('waInstallerApps')) {
            $current_app = wa()->getApp();
            if ($current_app != 'installer') {
                wa('installer', 1);
            }

            foreach ($this->requirements as &$requirement) {
                waInstallerApps::checkRequirements($requirement['requirements'], false, waInstallerApps::ACTION_UPDATE);
            }
            unset($requirement);

            if ($current_app != 'installer') {
                wa($current_app, 1);
            }
        }
    }
}