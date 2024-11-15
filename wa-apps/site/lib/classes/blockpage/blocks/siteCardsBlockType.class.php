<?php
/**
 * Represents one or more cards of content.
 * Uses siteCardBlockType to store settings of individual cards.
 */
class siteCardsBlockType extends siteBlockType
{
    public $elements = [   
        'main' => 'site-block-cards',
        'wrapper' => 'site-block-cards-wrapper',
        ];

    public function __construct(array $options=[])
    {
        if (!isset($options['cards']) || !wa_is_int($options['cards'])) {
            $options['cards'] = ifset(ref(explode('.', ifset($options, 'type', ''), 3)), 2, 2);
        }
        $options['type'] = 'site.Cards.'.$options['cards'];
        parent::__construct($options);

    }

    public function getExampleBlockData()
    {
        $result = $this->getEmptyBlockData();
        $card_block_type = new siteCardBlockType();
        $card_count = ifset($this->options, 'cards', 6);

        $card_class = 'fx-8 st-6 st-6-lp st-6-tb st-12-mb';
        $card_props = array();
        $card_props[$this->elements['main']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20"];
        $card_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", "border-radius" => "b-r-l", 'flex-align' => "y-c"];
        $card_props_inline = array();
       
        if ($card_count > 1) {
            $card_class = 'st-6 st-6-lp st-6-tb st-12-mb';
            $card_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", "border-radius" => "b-r-l", 'flex-align-vertical' => "x-t"];
        }
        if ($card_count > 2) {
            $card_class = 'st-4 st-6-lp st-6-tb st-12-mb';
        }
        if ($card_count > 3) {
            $card_class = 'st-4 st-4-lp st-6-tb st-6-mb';
        }

        $result->data = ['block_props' => $card_props];

        $cards_arr = array();
        for($i = 1; $i <= $card_count; $i++) {
            $result->addChild($card_block_type->getExampleBlockData(), 'col'.$i);
            $cards_arr['card-'.$i] = $card_class;

        }
        $result->data['columns'] = $cards_arr;
        $result->data['elements'] = $this->elements;

        return $result;
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars=[])
    {
        return parent::render($data, $is_backend, $tmpl_vars + [
            'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
        ]);
    }

    public function getRawBlockSettingsFormConfig()
    {
        $card_count = $this->options['cards'];
        if ($card_count == 1) {
            return [
                'type_name' => _w('Cards'),
                'sections' => [
                    [   'type' => 'ColumnsGroup',
                        'name' => _w('Column'),
                    ],
                    [   'type' => 'ColumnsAlignGroup',
                        'name' => _w('Alignment'),
                    ],
                    [   'type' => 'TabsWrapperGroup',
                        'name' => _w('Tabs'),
                    ],
                    [   'type' => 'BackgroundColorGroup',
                        'name' => _w('Background'),
                    ],
                    [   'type' => 'PaddingGroup',
                        'name' => _w('Padding'),
                    ],  
                    [   'type' => 'MarginGroup',
                        'name' => _w('Margin'),
                    ],
                    [   'type' => 'BorderGroup',
                        'name' => _w('Border'),
                    ],
                    [   'type' => 'BorderRadiusGroup',
                        'name' => _w('Angle'),
                    ],
                    [   'type' => 'ShadowsGroup',
                        'name' => _w('Shadows'),
                    ],
                ],
                'elements' => $this->elements,
                'semi_headers' => [
                    'main' => _w('Whole block'),
                    'wrapper' => _w('Container'),
                ]
            ] + parent::getRawBlockSettingsFormConfig();
        };

        return [
            'type_name' => _w('Cards'),
            'sections' => [
                [   'type' => 'ColumnsGroup',
                    'name' => _w('Column'),
                ],
                [   'type' => 'ColumnsAlignVerticalGroup',
                    'name' => _w('Columns alignment'),
                ],
                [  'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'BackgroundColorGroup',
                    'name' => _w('Background'),
                ],
                [   'type' => 'PaddingGroup',
                    'name' => _w('Padding'),
                ],
                [   'type' => 'MarginGroup',
                    'name' => _w('Margin'),
                ],
                [   'type' => 'BorderGroup',
                    'name' => _w('Border'),
                ],
                [   'type' => 'BorderRadiusGroup',
                    'name' => _w('Angle'),
                ],
                [   'type' => 'ShadowsGroup',
                    'name' => _w('Shadows'),
                ],
            ],
            'elements' => $this->elements,
            'semi_headers' => [
                'main' => _w('Whole block'),
                'wrapper' => _w('Container'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
