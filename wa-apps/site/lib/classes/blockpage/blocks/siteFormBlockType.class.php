<?php
/**
 * Block containing custom Form content.
 */
class siteFormBlockType extends siteBlockType
{

    public function __construct(array $options=[])
    {
        if (!isset($options['form_type'])) {
            $options['form_type'] = ifset(ref(explode('.', ifset($options, 'type', ''), 3)), 2, 'crm');
        }
        $options['type'] = 'site.Form.'.$options['form_type'];

        parent::__construct($options);

    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        $data->data['html'] = $this->renderSmarty($data->data['textarea_html']);
        return parent::render($data, $is_backend, $tmpl_vars);
    }


    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $form_type = ifset($this->options, 'form_type', 'crm');
        $default_code = ['crm' => '{$wa->crm->form(1)}', 'helpdesk' => '{$wa->helpdesk->form(1)}', 'mailer' => '{$wa->mailer->form(1)}'];

        $result->data = [
            'block_props' => ['margin-bottom' => "m-b-16"],
            'textarea_html' => $default_code[$form_type],
            'form_type' => $form_type
        ];
        return $result;
    }

    protected function getRawBlockSettingsFormConfig()
    {
        $app_id = $this->options['form_type'];
        $form_names = [
            'crm' => _w('CRM lead form'),
            'helpdesk' => _w('Help desk support request form'),
            'mailer' => _w('Subscription form'),
        ];
        $app_disabled = !wa()->appExists($app_id);

        $section_type = 'CustomFormGroup';
        if ($app_id !== 'helpdesk') {
            $section_type = 'CustomFormSelectionGroup';
        }

        $app_url = wa_backend_url();
        if ($app_disabled) {
            $app_url .= 'installer/store/app/'.$app_id.'/';
        } else {
            switch ($app_id) {
                case 'mailer':
                    $app_url .= $app_id.'/#/subscribers/';
                    break;
                case 'crm':
                    $app_url .= $app_id.'/settings/form/';
                    break;
            }
        }

        return [
            'type_name' => $form_names[$app_id],
            'tags' => 'element',
            'sections' => [
                [
                    'type' => $section_type,
                    'name' => _w('Embed code'),
                    'options' => $this->getFormList(),
                    'app_url' => $app_url,
                    'app_disabled' => $app_disabled,
                ],
                [   'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'MarginGroup',
                    'name' => _w('Margin'),
                ],
                [   'type' => 'BorderGroup',
                    'name' => _w('Border'),
                    'is_block' => true, //Exception Row element
                ],
                [   'type' => 'BorderRadiusGroup',
                    'name' => _w('Angle'),
                ],
                [   'type' => 'ShadowsGroup',
                    'name' => _w('Shadows'),
                ],
                [   'type' => 'VisibilityGroup',
                    'name' => _w('Visibility on devices'),
                ],
                [   'type' => 'IdGroup',
                    'name' => _w('Identifier (ID)'),
                ],
            ],
        ] + parent::getRawBlockSettingsFormConfig();
    }

    private function getFormList()
    {
        $app_id = $this->options['form_type'];
        $form_list = [];
        if (wa()->appExists($app_id)) {
            wa($app_id);
            $formModel = $app_id.'FormModel';
            if (!class_exists($formModel)) {
                return [];
            }

            $forms = (new $formModel())->getAll();
            foreach ($forms as $form) {
                $form_list[] = [
                    'value' => '{$wa->'.$app_id.'->form('.$form['id'].')}',
                    'name' => $form['name'],
                    'icon' => 'fa-th-list',
                ];
            }
            $app_urls = [
                'mailer' => '#/subscribers/form/new/',
                'crm' => 'settings/form/new/',
            ];
            $form_list[] = [
                'value' => 'form_add',
                'icon' => 'fa-plus-circle text-blue',
                'name' => _w('Add new'),
                'link_url' => wa()->getAppUrl($app_id).$app_urls[$app_id],
                'link_target' => '_blank'
            ];
        }

        return $form_list;
    }
}
