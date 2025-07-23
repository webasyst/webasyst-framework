<?php
/**
 * Represents one or more cards of content.
 * Uses siteCardBlockType to store settings of individual cards.
 */
class siteMenuT1BlockType extends siteBlockType
{
    public $elements = [   
        'main' => 'site-block-menu',
        'wrapper' => 'site-block-menu-wrapper',
        ];

    public $column_elements = [
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
    ];

    public function __construct(array $options=[])
    {
        $options['type'] = 'site.Menu.';
        parent::__construct($options);
    }

    public function getExampleBlockData()
    {
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;
        
        $logo_column = (new siteMenuLogoT1BlockType())->getExampleBlockData();
        $logo_column->data['column'] = "st-3 st-3-lp st-4-tb st-10-mb";
        
        $contacts_column = (new siteMenuContactsT1BlockType())->getExampleBlockData();
        $contacts_column->data['column'] = "st-0-tb st-0-mb st-6-lp st-6";

        $hseq->addChild($logo_column, 'col1');
        $hseq->addChild($contacts_column, 'col2');
        $hseq->addChild($this->getActionColumn(), 'col3');
        $hseq->addChild($this->getBurgerColumn(), 'col4');
        $hseq->addChild($this->getBgColumn(), 'col5');

        $result = $this->getEmptyBlockData();
        $result->addChild($hseq, '');

        $column_props = array();
        $column_props[$this->elements['main']] = [
            'padding-top' => "p-t-6", 
            'padding-bottom' => "p-b-6", 
            'padding-left' => "p-l-blc", 
            'padding-right' => "p-r-blc"
        ];
        $column_props[$this->elements['wrapper']] = [
            'flex-align-vertical' => "x-c", 
            'max-width' => "cnt"
        ];

        $result->data = [
            'block_props' => $column_props,
            'inline_props' => [
                $this->elements['main'] => [
                    'croll-margin-top' => [
                        'value' => '',
                        'unit' => 'px',
                        'id' => 'menut1',
                    ],
                ],
            ],
            'wrapper_props' => [
                'justify-align' => "y-j-cnt", 
                'flex-align-vertical' => "x-c"
                ]
            ];

        $result->data['elements'] = $this->elements;

        $result->data['id'][$this->elements['main']] = [
            'id' => 'menut1'
        ];
       
        $app_template_prop = array();
        $app_template_prop['disabled'] = false;
        $app_template_prop['active'] = false;

        $result->data['app_template'] = $app_template_prop;

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
        return [
            'type_name' => _w('Block'),
            'type_name_original' => _w('Menu'),
            'sections' => [
                [   'type' => 'MenuToggleGroup',
                    'name' => _w('Menu toggle'),
                ],
                [   'type' => 'ColumnsGroup',
                    'name' => _w('Columns'),
                ],
                [   'type' => 'ColumnsAlignVerticalGroup',
                    'name' => _w('Vertical alignment'),
                ],
                [   'type' => 'MenuDecorationGroup',
                    'name' => _w('Decoration'),
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
                'main' => _w('Whole block'),
                'wrapper' => _w('Container'),
            ]
        ] + parent::getRawBlockSettingsFormConfig();
    }

    public function getBgColumn(): siteBlockData {
        $hseq = $this->createSequence(true, 'no_complex', true);
        $hseq->addChild($this->createRow([], []));

        return $this->createColumn([
            'block_props' => [
                $this->column_elements['main'] => [
                    'background' => [
                        'layers' => [
                            [
                                'name' => 'black and white',
                                'value' => "bg-wh",
                                'type' => 'palette',
                            ],
                        ],
                        'name' => 'black and white',
                        'value' => "bg-wh",
                        'type' => 'palette',
                    ],
                    'visibility' => "d-n-lp d-n-ds",
                    'padding-left' => "p-l-0",
                    'padding-right' => "p-r-0"
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align' => "y-c",
                ],
            ],
            'id' => [
                $this->column_elements['main'] => [
                    'id' => 'menut1bg'
                ]
            ],
            'column' => "st-12-mb st-12-tb st-0-lp st-0",
        ], $hseq);
    }

    public function getActionColumn(): siteBlockData {
        $hseq = $this->createSequence(true, 'no_complex', true);

        $menu_item = (new siteButtonBlockType())->getExampleBlockData();
        $menu_item->data = [
            'html' => 'Contactate',
            'tag' => 'a',
            'block_props' => [
                'border-radius' => "b-r-r",
                'button-size' => "inp-m p-l-13 p-r-13",
                'margin-bottom' => "m-b-12",
                'margin-left' => "m-l-a",
                'margin-top' => "m-t-8",
                'button-style' => [
                    "name" => "Palette",
                    "value" => "btn-blc",
                    "type" => "palette"
                ],
            ],
        ];
        $hseq->addChild($menu_item);

        return $this->createColumn([
            'block_props' => [
                $this->column_elements['main'] => [
                    'margin-bottom' => "m-b-a",
                    'margin-left' => "m-l-a",
                    'margin-top' => "m-t-a",
                    'padding-bottom' => "p-b-6",
                    'padding-left' => "p-l-0",
                    'padding-right' => "p-r-clm",
                    'padding-top' => "p-t-6",
                    'visibility' => "d-n-mb"
                ],
                $this->column_elements['wrapper'] => [
                    "border-radius" => "b-r-l",
                    'flex-align' => "y-c",
                ],
            ],
            'column' => "st-0-mb st-7-tb st-3-lp st-3",
        ], $hseq);
    }

    public function getBurgerColumn(): siteBlockData {
        $hseq = $this->createSequence(true, 'no_complex', true);

        $item1 = (new siteImageBlockType())->getExampleBlockData();
        $item1->data = [
            'image' => [
                'color' => [
                    'name' => 'Palette',
                    'value' => 'tx-blc',
                    'type' => 'palette'
                ],
                'fill' => 'removed',
                'type' => 'svg',
                'svg_html' => '<!-- Open icon (top), close icon (below, hidden on site) -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                  <path d="M15.2914 1.56903L0.70099 1.56903C0.313844 1.56903 -3.61259e-08 1.88288 0 2.27002C3.61259e-08 2.65717 0.313844 2.97101 0.70099 2.97101L15.2914 2.97101C15.6786 2.97101 16 2.65717 16 2.27002C16 1.88287 15.6786 1.56903 15.2914 1.56903Z"></path>
                  <path d="M15.2914 7.29901L0.70099 7.29902C0.313844 7.29902 -3.61259e-08 7.61286 0 8.00001C3.61259e-08 8.38715 0.313844 8.70099 0.70099 8.70099L15.2914 8.70099C15.6786 8.70099 16 8.38715 16 8C16 7.61285 15.6786 7.29901 15.2914 7.29901Z"></path>
                  <path d="M15.2914 13.0286L0.70099 13.0286C0.313844 13.0286 -3.61259e-08 13.3424 0 13.7296C3.61259e-08 14.1167 0.313844 14.4305 0.70099 14.4305L15.2914 14.4305C15.6786 14.4305 16 14.1167 16 13.7296C16 13.3424 15.6786 13.0286 15.2914 13.0286Z"></path>
                </svg>
                <!-- Close icon (below, hidden on site, shown in editor) -->
                <svg viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.33952 1.3482L8 7.00848L13.6605 1.3482C13.9342 1.07444 14.3781 1.07444 14.6518 1.3482C14.9256 1.62195 14.9256 2.06579 14.6518 2.33955L8.99154 8.00003L14.6518 13.6605C14.9256 13.9343 14.9256 14.3781 14.6518 14.6519C14.3781 14.9256 13.9342 14.9256 13.6605 14.6519L8 8.99157L2.33952 14.6519C2.06576 14.9256 1.62192 14.9256 1.34817 14.6519C1.07441 14.3781 1.07441 13.9343 1.34817 13.6605L7.00845 8.00003L1.34817 2.33955C1.07441 2.06579 1.07441 1.62195 1.34817 1.3482C1.62192 1.07444 2.06576 1.07444 2.33952 1.3482Z"></path>
                  </svg>',
            ],
            'block_props' => [
                'visibility' => 'd-n-ds d-n-lp',
                'margin-bottom' => "m-b-8-mb",
                'picture-size' => "i-s",
            ],
        ];

        /*$item2 = (new siteImageBlockType())->getExampleBlockData();
        $item2->data = [
            'image' => [
                'color' => [
                    'name' => 'Palette',
                    'value' => 'tx-blc',
                    'type' => 'palette'
                ],
                'fill' => 'removed',
                'type' => 'svg',
                'svg_html' => '<svg id="icn-cls" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2.33952 1.3482L8 7.00848L13.6605 1.3482C13.9342 1.07444 14.3781 1.07444 14.6518 1.3482C14.9256 1.62195 14.9256 2.06579 14.6518 2.33955L8.99154 8.00003L14.6518 13.6605C14.9256 13.9343 14.9256 14.3781 14.6518 14.6519C14.3781 14.9256 13.9342 14.9256 13.6605 14.6519L8 8.99157L2.33952 14.6519C2.06576 14.9256 1.62192 14.9256 1.34817 14.6519C1.07441 14.3781 1.07441 13.9343 1.34817 13.6605L7.00845 8.00003L1.34817 2.33955C1.07441 2.06579 1.07441 1.62195 1.34817 1.3482C1.62192 1.07444 2.06576 1.07444 2.33952 1.3482Z"></path>
                  </svg>',
            ],
            'block_props' => [
                'visibility' => 'd-n-ds d-n-lp',
                'margin-bottom' => "m-b-8-mb",
                'picture-size' => "i-s",
            ],
        ];*/
        $hseq->addChild($this->createRow([
            'block_props' => [
                'padding-bottom' => "p-b-10",
                'padding-top' => "p-t-7",
            ],
            'wrapper_props' => [
                'flex-wrap' => "n-wr-ds n-wr-lp",
                'justify-align' => "j-end"
            ],
        ], [$item1]));

        return $this->createColumn([
            'block_props' => [
                $this->column_elements['main'] => [
                    'margin-bottom' => "m-b-a",
                    'margin-left' => "m-l-a",
                    'margin-top' => "m-t-a",
                    'padding-bottom' => "p-b-0",
                    'padding-left' => "p-l-clm",
                    'padding-right' => "p-r-clm",
                    'padding-top' => "p-t-0",
                    'visibility' => "d-n-lp d-n-ds"
                ],
                $this->column_elements['wrapper'] => [
                    'padding-top' => "p-t-10",
                    'padding-bottom' => "p-b-10",
                    "border-radius" => "b-r-l",
                    'flex-align' => "y-c",
                ],
            ],
            'column' => "st-0-lp st-0 st-1-tb st-2-mb",
            'id' => [
                $this->column_elements['main'] => [
                    'id' => 'menut1gmb'
                ]
            ],
        ], $hseq);
    }

    /**
     * Создаёт ряд
     *
     * @param array $props
     * @param array $content
     * @return siteBlockData
     */
    public function createRow(array $props, array $content): siteBlockData {
        $row = (new siteRowBlockType())->getExampleBlockData();
        $row->data['block_props'] = $props['block_props'] ?? [];
        $row->data['wrapper_props'] = $props['wrapper_props'] ?? [];
        $row->data['inline_props'] = $props['inline_props'] ?? [];
        $row->data['id'] = $props['id'] ?? '';

        $hseq = reset($row->children['']);

        foreach ($content as $item) {
            $hseq->addChild($item);
        }

        return $row;
    }


    /**
     * Создаёт подколонку
     *
     * @param array $params
     * @param array $content
     * @return siteBlockData
     */
    public function createSubColumn(array $params, $content): siteBlockData {
        $sub_column = (new siteSubColumnBlockType())->getExampleBlockData();
        $sub_column->data['block_props'] = $params['block_props'] ?? [];
        $sub_column->data['wrapper_props'] = $params['wrapper_props'] ?? [];
        $sub_column->data['inline_props'] = $params['inline_props'] ?? [];
        $sub_column->data['id'] = $params['id'] ?? '';

        $vseq = reset($sub_column->children['']);
 
         foreach ($content as $item) {
            $vseq->addChild($item);
        }

        return $sub_column;
    }

    /**
     * Создаёт колонку с настройками
     *
     * @param array         $params
     * @param siteBlockData $content
     * @return siteBlockData
     */
    private function createColumn(array $params, siteBlockData $content): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = [
            'elements'      => $this->column_elements,
            'column'        => $params['column'] ?? 'st-12 st-12-lp st-12-tb st-12-mb',
            'block_props'   => $params['block_props'] ?? [],
            'wrapper_props' => $params['wrapper_props'] ?? [],
            'inline_props'  => $params['inline_props'] ?? [],
            'indestructible' => $params['indestructible'] ?? false,
            'id' => $params['id'] ?? '',
        ];

        $column->addChild($content, '');

        return $column;
    }

    /**
     * Создаёт последовательность блоков
     *
     * @param bool   $is_horizontal
     * @param string $complex_type
     * @param bool   $indestructible
     * @return siteBlockData
     */
    private function createSequence(bool $is_horizontal = false, string $complex_type = 'with_row', bool $indestructible = false): siteBlockData {
        $seq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $seq->data['is_horizontal'] = $is_horizontal;
        $seq->data['is_complex'] = $complex_type;

        if ($indestructible) {
            $seq->data['indestructible'] = true;
        }

        return $seq;
    }
}
