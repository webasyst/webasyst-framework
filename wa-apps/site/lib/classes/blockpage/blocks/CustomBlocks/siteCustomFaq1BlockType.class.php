<?php

class siteCustomFaq1BlockType extends siteBlockType {
    /** @var array Элементы основного блока */
    public array $elements = [
        'main'    => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
    ];

    /** @var array Элементы колонок */
    public array $column_elements = [
        'main'    => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
    ];

    public function __construct(array $options = []) {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 1;
        }
        $options['type'] = 'site.CustomFaq1';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        $hseq->addChild($this->getMainColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => [
                $this->elements['main']    => [
                    'padding-bottom' => 'p-b-18',
                    'padding-left'   => 'p-l-blc',
                    'padding-right'  => 'p-r-blc',
                    'padding-top'    => 'p-t-18',
                ],
                $this->elements['wrapper'] => [
                    'flex-align' => 'y-c',
                    'max-width' => 'cnt',
                    'padding-bottom' => 'p-b-12',
                    'padding-top'    => 'p-t-12',
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
            'elements'      => $this->elements,
        ];

        return $result;
    }

    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars = []) {
        return parent::render($data, $is_backend, $tmpl_vars + [
                'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
            ]);
    }

    public function getRawBlockSettingsFormConfig() {
        return [
                'type_name'    => _w('Block'),
                'sections'     => [
                    [
                        'type' => 'ColumnsGroup',
                        'name' => _w('Columns'),
                    ],
                    [
                        'type' => 'RowsAlignGroup',
                        'name' => _w('Columns alignment'),
                    ],
                    [
                        'type' => 'RowsWrapGroup',
                        'name' => _w('Wrap line'),
                    ],
                    [
                        'type' => 'TabsWrapperGroup',
                        'name' => _w('Tabs'),
                    ],                    
                    [   'type' => 'CommonLinkGroup',
                        'name' => _w('Link or action'),
                        'is_hidden' => true,
                    ],
                    [
                        'type' => 'MaxWidthToggleGroup',
                        'name' => _w('Max width'),
                    ],
                    [
                        'type' => 'BackgroundColorGroup',
                        'name' => _w('Background'),
                    ],
                    [   'type' => 'HeightGroup',
                        'name' => _w('Height'),
                    ],
                    [
                        'type' => 'PaddingGroup',
                        'name' => _w('Padding'),
                    ],
                    [
                        'type' => 'MarginGroup',
                        'name' => _w('Margin'),
                    ],
                    [
                        'type' => 'BorderGroup',
                        'name' => _w('Border'),
                    ],
                    [
                        'type' => 'BorderRadiusGroup',
                        'name' => _w('Angle'),
                    ],
                    [
                        'type' => 'ShadowsGroup',
                        'name' => _w('Shadows'),
                    ],
                    [
                        'type' => 'IdGroup',
                        'name' => _w('Identifier (ID)'),
                    ],
                ],
                'elements'     => $this->elements,
                'semi_headers' => [
                    'main'    => _w('Whole block'),
                    'wrapper' => _w('Container'),
                ],
            ] + parent::getRawBlockSettingsFormConfig();
    }

    private function getHeading() {
        $vseq = $this->createSequence();

        $heading = (new siteHeadingBlockType())->getEmptyBlockData();
        $heading->data = [
            'html'        => '<font color="#010203">Quaestiones et Responsiones</font>',
            'tag'         => 'h3',
            'block_props' => [
                'align' => 't-l',
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-13',
                'margin-top' => 'm-t-0',
            ],
        ];

        $vseq->addChild($heading);
        return $vseq;
    }

    private function getExampleData() {
        
        return [[
            'q' => 'Quomodo emere res in nostro mercatu possum?',
            'a' => 'Elige rem desideratam et preme «Addere in cistam». Deinde ad cistam procede (saepe in angulo dextro superiore), res electas inspice et preme «Confirmare emptionem». Comple notitias pro traditione, elige modum solvendi et emptionem confirma. Postea litteras confirmationis per nuntios accipies.',
        ], [
            'q' => 'Qui modi traditionis offeruntur?',
            'a' => 'Offerimus traditionem per nuntium urbanum, per tabellarios publicos, et per loca receptionis in regione tua. Pretium et tempus traditionis a loco tuo et modo electo pendent — notitias exactas in confirmatione emptionis videbis. Traditio fere inter 2 et 7 dies utiles durat.',
        ], [
            'q' => 'Estne possibile res reddere si non convenit?',
            'a' => 'Ita, res intra 14 dies post acceptum reddere potes, si non usata est et involucrum ac forma pristina servata sunt. Pro reditu, continge auxilium nostrum per formulam in loco nostro aut per vocem. Monstrabimus quomodo res remittere et pecuniam recuperare possis.',
        ]];
    }

    private function getMainColumn(): siteBlockData {
        $vseq = $this->createSequence();

        // Добавляем последовательности в основной блок
        $vseq->addChild($this->getHeading());
         foreach ($this->getExampleData() as $item) {
            $vseq->addChild($this->createSubColumn($item));
        }

        $block_props = [
            $this->column_elements['main'] => [
                'padding-bottom' => 'p-b-12',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
                'padding-top' => 'p-t-12',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
                'padding-bottom' => 'p-b-12',
                'padding-top' => 'p-t-12',
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-12-tb st-12-mb st-8-lp st-6',
                'block_props'   => $block_props,
                'wrapper_props' => [
                    'flex-align' => 'y-l',
                ],
            ],
            $vseq
        );
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
        ];

        $column->addChild($content, '');

        return $column;
    }

    private function createSubColumn(array $qa_item): siteBlockData {
        $sub_column = (new siteSubColumnBlockType())->getExampleBlockData();
        $sub_column->data['block_props'] = [
            'padding-bottom' => 'p-b-12',
            'padding-top' => 'p-t-12',
        ];
        $sub_column->data['wrapper_props'] = [
            'justify-align' => 'j-s',
        ];

        $vseq = reset($sub_column->children['']);

        $q = (new siteHeadingBlockType())->getEmptyBlockData();
        $q->data = [
            'html'        => $qa_item['q'],
            'tag'         => 'h3',
            'block_props' => [
                'align' => 't-l',
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top' => 'm-t-0',
            ],
        ];
        $vseq->addChild($q);

        $a = (new siteHeadingBlockType())->getEmptyBlockData();
        $a->data = [
            'html'        => $qa_item['a'],
            'tag'         => 'p',
            'block_props' => [
                'align' => 't-l',
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top' => 'm-t-0',
            ],
        ];
        $vseq->addChild($a);

        return $sub_column;
    }
}