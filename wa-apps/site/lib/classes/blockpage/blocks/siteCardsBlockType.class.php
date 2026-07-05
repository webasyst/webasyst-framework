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
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;
        $card_block_type = new siteCardBlockType();
        $card_count = ifset($this->options, 'cards', 7);

        $card_wrapper_class = 'st-3 st-3-lp st-4-tb st-6-mb';
        if ($card_count < 3) {
            $card_wrapper_class = 'st-6 st-6-lp st-6-tb st-12-mb';
        }
        if ($card_count < 2) {
            $card_wrapper_class = 'st-12 st-12-lp st-12-tb st-12-mb';
        }

        $card_block_type = (new siteCardBlockType())->getExampleBlockData();
        $card_block_type->data['indestructible'] = false;
        $card_block_type->data['column'] = $card_wrapper_class;

        $card_props = array();
        $card_props[$this->elements['main']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'padding-left' => "p-l-blc", 'padding-right' => "p-r-blc"];
        $card_props[$this->elements['wrapper']] = ['padding-top' => "p-t-20", 'padding-bottom' => "p-b-20", 'flex-align' => "y-c", 'max-width' => "cnt"];

        $result = $this->getEmptyBlockData();

        for($i = 1; $i <= $card_count; $i++) {
            $hseq->addChild($card_block_type);
        }
        $result->addChild($hseq, '');
        $result->data = ['block_props' => $card_props, 'wrapper_props' => ['justify-align' => "y-j-cnt"]];
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

        return [
            'type_name' => _w('Block'),
            'type_name_original' => _w('Cards'),
            'sections' => [
                [   'type' => 'CardsGroup',
                    'name' => _w('Cards'),
                ],
                [   'type' => 'RowsAlignGroup',
                    'name' => _w('Alignment of the last row when not filled in'),
                ],
                [  'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'MaxWidthToggleGroup',
                    'name' => _w('Max width'),
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
                [   'type' => 'IdGroup',
                    'name' => _w('Identifier (ID)'),
                ],
            ],
            'elements' => $this->elements,
            'semi_headers' => [
                'main' => _w('Outside'),
                'wrapper' => _w('Inside'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }
}
