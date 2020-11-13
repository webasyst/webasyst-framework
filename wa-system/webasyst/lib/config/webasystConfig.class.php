<?php

class webasystConfig extends waAppConfig
{
    public function getAppPath($path = null)
    {
        return $this->getRootPath() . '/wa-system/' . $this->application . ($path ? '/' . $path : '');
    }

    public function getWidgetPath($widget_id)
    {
        return $this->getRootPath()."/wa-widgets/".$widget_id;
    }

    public function getLogActions($full = false, $ignore_system = false)
    {
        $result = array(
            'contact_edit' => array(
                'name' => _ws('edited contact')
            ),
        );
        if (!$ignore_system) {
            $result = array_merge($result, $this->getSystemLogActions());
        }
        return $result;
    }

    public function onCount()
    {
        wa('webasyst');
        webasystHelper::backgroundClearCache();

        $n = array(
            //'settings' => 1,
        );

        return $n;
    }

    public function initUserWidgets($force = false, waContact $contact = null)
    {
        if (!$contact) {
            $contact = wa()->getUser();
        }
        if (!$force && $contact->getSettings('webasyst', 'dashboard')) {
            return;
        }

        $contact_id = $contact->getId();
        $contact->setSettings('webasyst', 'dashboard', 1);

        // Delete existing widgets
        $widget_model = new waWidgetModel();
        foreach($widget_model->getByContact($contact_id) as $row) {
            $widget_model->delete($row['id']);
        }

        // Read config file and fetch list of widget blocks
        // depending on contact's locale.
        $locale_blocks = $this->getInitWidgetsConfig();
        if (!$locale_blocks) {
            return;
        }
        $locale = $contact->getLocale();
        if (isset($locale_blocks[$locale])) {
            $blocks = $locale_blocks[$locale];
        } else {
            $blocks = reset($locale_blocks);
        }

        // Read config block by block and save widgets to DB
        $block_id = 0;
        $app_widgets = array();
        $empty_row = $widget_model->getEmptyRow();
        foreach ($blocks as $block) {
            if (isset($block['widget'])) {
                $block = array($block);
            }
            $sort = 0;
            foreach($block as $widget_data) {
                $app_id = ifset($widget_data['app_id'], 'webasyst');
                if (!isset($app_widgets[$app_id])) {
                    $app_widgets[$app_id] = array();
                    if (wa()->appExists($app_id) && ($app_id == 'webasyst' || $contact->getRights($app_id, 'backend'))) {
                        $app_widgets[$app_id] = wa($app_id)->getConfig()->getWidgets();
                    }
                }

                $widget_data['widget'] = ifset($widget_data['widget'], '');
                if (empty($app_widgets[$app_id][$widget_data['widget']])) {
                    waLog::log('Unable to instantiate widget app_id='.$app_id.' widget='.$widget_data['widget'].' for user contact_id='.$contact_id.' (does not exist or no access to app)');
                    continue;
                }

                $w = $app_widgets[$app_id][$widget_data['widget']];
                if (!empty($w['rights'])) {
                    if (!waWidget::checkRights($w['rights'])) {
                        waLog::log('Unable to instantiate widget app_id='.$app_id.' widget='.$widget_data['widget'].' for user contact_id='.$contact_id.' (access denied)');
                        continue;
                    }
                }

                $widget_data['name'] = $w['name'];
                $widget_data['app_id'] = $app_id;
                $widget_data['dashboard_id'] = null;
                $widget_data['size'] = ifset($widget_data['size'], reset($w['sizes']));
                $widget_data['create_datetime'] = date('Y-m-d H:i:s');
                $widget_data['contact_id'] = $contact_id;
                $widget_data['block'] = $block_id;
                $widget_data['sort'] = $sort;

                $params = ifset($widget_data['params'], array());
                unset($widget_data['params']);

                $id = null;
                try {
                    $id = $widget_model->insert(array_intersect_key($widget_data, $empty_row) + $empty_row);
                    wa()->getWidget($id)->setSettings($params);
                    $sort++;
                } catch (Exception $e) {
                    waLog::log('Unable to instantiate widget app_id='.$app_id.' widget='.$widget_data['widget'].' for user contact_id='.$contact_id.': '.$e->getMessage());
                    $id && $widget_model->delete($id);
                }
            }
            if ($sort > 0) {
                $block_id++;
            }
        }
    }

    protected function getInitWidgetsConfig()
    {
        $custom_config = wa('webasyst')->getConfig()->getConfigPath('init_widgets.php');
        if (file_exists($custom_config)) {
            return include($custom_config);
        } else {
            $basic_config = wa('webasyst')->getConfig()->getAppPath('lib/config/init_widgets.php');
            if (file_exists($basic_config)) {
                return include($basic_config);
            }
        }
        return array();
    }

    public function throwFrontControllerDispatchException()
    {
        // see waFrontController
        // When route is not found in backend routing, usually app throws exception.
        // But since internal webasyst app is also responsible for dashboard,
        // we skip here to use defaults. Complicated.
    }

    /**
     * CheatSheet help for webasyst/webasyst/settings/email/template/ pages
     * @return array
     */
    public function getEmailChannelTemplatesHelp()
    {
        // Here will be vars for each Email template
        $email_template_vars = array();

        // "Empty" Email channel, need to extract template names
        $email_channel = waVerificationChannel::factory(waVerificationChannelModel::TYPE_EMAIL);

        // List of templates: template_id => localized template name
        $template_list = $email_channel->getTemplatesList();

        // Name of vars tabs
        $vars_tab_names = array();

        foreach ($template_list as $template_name => $loc_template_name) {

            // template vars
            $vars = $email_channel->getTemplateVars($template_name, true);

            // each var name need to prefix with $
            // for it separate keys and values, prefix each key with $ and than combine arrays back
            $var_names = array_keys($vars);
            $var_values = array_values($vars);
            $var_names = array_map(wa_lambda('$name', 'return \'$\' . $name;'), $var_names);
            $vars = array_combine($var_names, $var_values);

            // vars for each email template put into own section
            $email_template_vars[ 'email_template_' . $template_name ] = $vars;

            // name of tab where this vars enumerated
            $vars_tab_names[ 'email_template_' . $template_name ] = $loc_template_name;
        }


        // If you need to add new vars not related with Email templates merge they with $email_template_vars
        return array(
            'vars_tab_names' => $vars_tab_names,
            'vars' => $email_template_vars
        );
    }

    /**
     * CheatSheet help for webasyst/webasyst/settings/sms/template/ pages
     * @return array
     */
    public function getSMSChannelTemplatesHelp()
    {
        // Here will be vars for each SMS template
        $sms_template_vars = array();

        // "Empty" SMS channel, need to extract template names
        $sms_channel = waVerificationChannel::factory(waVerificationChannelModel::TYPE_SMS);

        // List of templates: template_id => localized template name
        $template_list = $sms_channel->getTemplatesList();

        foreach ($template_list as $template_name => $loc_template_name) {

            // template vars
            $vars = $sms_channel->getTemplateVars($template_name, true);

            // each var name need to prefix with $
            // for it separate keys and values, prefix each key with $ and than combine arrays back
            $var_names = array_keys($vars);
            $var_values = array_values($vars);
            $var_names = array_map(wa_lambda('$name', 'return \'$\' . $name;'), $var_names);
            $vars = array_combine($var_names, $var_values);

            // merge all vars
            foreach ($vars as $var => $description) {
                if (!isset($sms_template_vars['sms_templates'][$var])) {
                    $sms_template_vars['sms_templates'][$var] = array();
                }
                $sms_template_vars['sms_templates'][$var][] = sprintf(_ws('<strong>%s</strong> in <strong>%s</strong> template.'), $description, $loc_template_name);
            }
        }

        // join descriptions into strings
        foreach ($sms_template_vars['sms_templates'] as $var => &$descriptions) {
            $descriptions = join("<br>", $descriptions);
        }
        unset($descriptions);

        // If you need to add new vars not related with Email templates merge they with $email_template_vars
        return array(
            'vars_tab_names' => array('sms_templates' => _ws('SMS templates')),
            'vars' => $sms_template_vars
        );
    }

    /**
     * CheatSheet help for webasyst/webasyst/settings/email/template/ pages
     *   +
     * CheatSheet help for webasyst/webasyst/settings/sms/template/ pages
     *
     * @return array
     */
    public function getAllChannelTemplatesHelp()
    {
        $email_templates_help = $this->getEmailChannelTemplatesHelp();
        $sms_templates_help = $this->getSMSChannelTemplatesHelp();
        return array(
            'vars_tab_names' => $email_templates_help['vars_tab_names'] + $sms_templates_help['vars_tab_names'],
            'vars' => $email_templates_help['vars'] + $sms_templates_help['vars']
        );

    }

    /**
     * Get identity hash (aka installation hash)
     * @return string
     */
    public function getIdentityHash()
    {
        $value = $this->getSystemOption('identity_hash');
        if (is_scalar($value)) {
            return strval($value);
        }
        return '';
    }

    public function dispatchAppToken($data)
    {
        $app_tokens_model = new waAppTokensModel();

        // Unknown token type?
        if ($data['type'] != 'webasyst_id_invite') {
            $app_tokens_model->deleteById($data['token']);
            throw new waException("Page not found", 404);
        }

        // Make sure contact is still ok
        $contact = new waContact($data['contact_id']);
        if (!$contact->exists() || $contact['is_user'] < 0) {
            $app_tokens_model->deleteById($data['token']);
            throw new waException("Page not found", 404);
        }

        $auth = wa()->getAuth();
        $auth->auth(['id' => $contact->getId()]);

        $webasyst_id_auth = new waWebasystIDWAAuth();

        // bind webasyst id
        $url = $webasyst_id_auth->getUrl();
        wa()->getResponse()->redirect($url);

    }
}

