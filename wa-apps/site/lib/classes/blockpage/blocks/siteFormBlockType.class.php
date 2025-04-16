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
        $form_type = $this->options['form_type'];

        $form_names = [
            'crm' => _w('CRM lead form'),
            'helpdesk' => _w('Help desk support request form'),
            'mailer' => _w('Email subscription form'),
        ];

        return [
            'type_name' => $form_names[$form_type],
            'tags' => 'element',
            'sections' => [
                [   'type' => 'CustomFormGroup',
                    'name' => _w('Embed code'),
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
}
