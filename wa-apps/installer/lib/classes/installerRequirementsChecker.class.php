<?php

class installerRequirementsChecker
{
    /**
     * @var array
     */
    protected $requirements;

    /**
     * installerRequirementsChecker constructor.
     * @param array $requirements - array of $requirement
     *          int $requirement['product_id']
     *              Product ID
     *          string|array $requirement['requirements']
     *              Json-string OR associative array of requirements gotten from store (waid)
     */
    public function __construct(array $requirements)
    {
        $this->requirements = $requirements;
        $this->decodeRequirements();
    }

    /**
     * @return array
     */
    public function check()
    {
        $warning_requirements = $this->getWarningRequirements();
        if (!empty($warning_requirements)) {
            return $warning_requirements;
        }
        return [];
    }

    protected function decodeRequirements()
    {
        foreach ($this->requirements as &$requirement) {
            if (is_array($requirement['requirements'])) {
                // check for ~
                $str = waUtils::jsonEncode($requirement['requirements']);
                if (strpos($str, '~') === false) {
                    continue;
                }
                $requirement['requirements'] = $str;
            }

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
