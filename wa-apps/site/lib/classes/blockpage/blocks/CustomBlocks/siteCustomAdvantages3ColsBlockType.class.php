<?php

class siteCustomAdvantages3ColsBlockType extends siteBlockType {
    /**
     * Элементы блока
     */
    protected array $elements = [
        'main'    => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
    ];

    public function __construct(array $options = []) {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 3;
        }
        $options['type'] = 'site.CustomAdvantages3Cols';
        parent::__construct($options);
    }

    /**
     * Возвращает данные для примера блока
     *
     * @return siteBlockData
     */
    public function getExampleBlockData(): siteBlockData {
        try {
            // Создаём основной блок
            $result = $this->getEmptyBlockData();

            // Создаём горизонтальную последовательность для колонок
            $hseq = $this->createHorizontalSequence();

            // Добавляем преимущества в горизонтальную последовательность
            $advantages = $this->getDefaultAdvantages();
            foreach ($advantages as $advantage) {
                $hseq->addChild($this->getAdvantage($advantage['heading'], $advantage['paragraph'], $advantage['icon']));
            }

            // Добавляем горизонтальную последовательность в основной блок
            $result->addChild($hseq, '');

            // Настраиваем свойства основного блока
            $block_props = [];
            $block_props[$this->elements['main']] = [
                'padding-top'    => 'p-t-18',
                'padding-bottom' => 'p-b-18',
                'padding-left' => 'p-l-blc',
                'padding-right' => 'p-r-blc',
            ];
            $block_props[$this->elements['wrapper']] = [
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
                'flex-align'     => 'y-c',
                'max-width'      => 'cnt',
            ];

            $result->data = [
                'block_props'   => $block_props,
                'wrapper_props' => ['justify-align' => 'y-j-cnt'],
                'elements'      => $this->elements,
            ];

            return $result;
        } catch (Exception $e) {
            waLog::log($e->getMessage());
            return $this->getEmptyBlockData();
        }
    }

    /**
     * Рендеринг блока
     *
     * @param siteBlockData $data
     * @param bool          $is_backend
     * @param array         $tmpl_vars
     * @return string
     */
    public function render(siteBlockData $data, bool $is_backend, array $tmpl_vars = []): string {
        return parent::render($data, $is_backend, $tmpl_vars + [
                'children' => array_reduce($data->getRenderedChildren($is_backend), 'array_merge', []),
            ]);
    }

    /**
     * Возвращает конфигурацию формы настроек блока
     *
     * @return array
     */
    public function getRawBlockSettingsFormConfig(): array {
        return [
                'type_name'    => _w('Block'),
                'type_name_original'    => _w('Advantages'),
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

    /**
     * Создаёт блок с преимуществом
     *
     * @param string $heading
     * @param string $paragraph
     * @return siteBlockData
     */
    private function getAdvantage(string $heading, string $paragraph, string $icon): siteBlockData {
        $sub_column = (new siteSubColumnBlockType())->getExampleBlockData();
        $vseq = reset($sub_column->children['']);
        $vseq->addChild($this->getHeading($heading));
        $vseq->addChild($this->getParagraph($paragraph));

        $row = $this->getRow([$this->getIcon($icon), $vseq]);

        $vseq = $this->createVerticalSequence();
        $vseq->addChild($row);

        // Создаём колонку и добавляем в неё вертикальную последовательность
        $column = $this->getColumn();
        $column->addChild($vseq, '');

        return $column;
    }

    /**
     * Создаёт колонку
     *
     * @return siteBlockData
     */
    private function getColumn(): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column_elements = [
            'main'    => 'site-block-column',
            'wrapper' => 'site-block-column-wrapper',
        ];

        $column->data = [
            'elements'       => $column_elements,
            'column'         => "st-4 st-4-lp st-12-tb st-12-mb",
            'indestructible' => false,
            'block_props'    => [
                $column_elements['main']    => [
                    'padding-top'    => "p-t-12",
                    'padding-bottom' => "p-b-12",
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $column_elements['wrapper'] => [
                    'flex-align'     => "y-c",
                    'padding-top'    => "p-t-12",
                    'padding-bottom' => "p-b-12",
                ],
            ],
        ];

        return $column;
    }

    /**
     * Создаёт ряд
     *
     * @return siteBlockData
     */
    private function getRow(array $children): siteBlockData {
        $row = (new siteRowBlockType())->getExampleBlockData();
        $row->data['block_props'] = ['padding-top' => 'p-t-10', 'padding-bottom' => 'p-b-10'];
        $row->data['wrapper_props'] = ['justify-align' => 'j-s', 'flex-wrap' => 'n-wr-ds'];

        $hseq = reset($row->children['']);
        foreach ($children as $child) {
            $hseq->addChild($child);
        }

        return $row;
    }

    /**
     * Создаёт параграф с текстом
     *
     * @param string $content
     * @return siteBlockData
     */
    private function getParagraph(string $content): siteBlockData {
        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();

        $paragraph->data = [
            'html'        => $content,
            'tag'         => 'p',
            'block_props' => [
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-top'    => 'm-t-0',
                'margin-bottom' => 'm-b-12',
                'align'         => 't-l',
            ],
        ];

        return $paragraph;
    }

    /**
     * Создаёт заголовок с текстом
     *
     * @param string $content
     * @return siteBlockData
     */
    private function getHeading(string $content): siteBlockData {
        $header = (new siteHeadingBlockType())->getEmptyBlockData();

        $header->data = [
            'html'        => $content,
            'tag'         => 'h3',
            'block_props' => [
                'font-header'   => 't-hdn',
                'font-size'     => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-top'    => 'm-t-0',
                'margin-bottom' => 'm-b-9',
                'align'         => 't-l',
            ],
        ];

        return $header;
    }

    /**
     * Создаёт иконку
     *
     * @param string $content
     * @return siteBlockData
     */
    private function getIcon(string $svg_html): siteBlockData {
        $icon = (new siteImageBlockType())->getEmptyBlockData();

        $icon->data = [
            'image'       => [
                'type'     => 'svg',
                'svg_html' => $svg_html,
            ],
            'block_props' => [
                'margin-bottom' => 'm-b-12',
                'margin-right' => 'm-r-14',
                'picture-size' => 'i-xxl',
            ],
        ];

        return $icon;
    }

    /**
     * Создаёт горизонтальную последовательность
     *
     * @return siteBlockData
     */
    private function createHorizontalSequence(): siteBlockData {
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;

        return $hseq;
    }

    /**
     * Создаёт вертикальную последовательность
     *
     * @return siteBlockData
     */
    private function createVerticalSequence(): siteBlockData {
        $vseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $vseq->data['is_complex'] = 'with_row';

        return $vseq;
    }

    /**
     * Возвращает массив с данными преимуществ по умолчанию
     *
     * @return array
     */
    private function getDefaultAdvantages(): array {
        return [
            [
                'heading'   => 'Auxilium eligendi',
                'paragraph' => 'Te adiuvabimus mercem aptam eligere et omnibus quaestionibus respondebo.',
                'icon'      => '<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M26.515 13.0803C30.9901 12.7832 32.7383 14.3588 33.9506 17.8087C35.4874 22.1857 29.6112 24.1209 29.3422 26.6231C30.9235 30.0741 52.8669 37.799 53.8246 43.1553C54.8184 48.7045 44.9599 47.5031 41.724 47.5058L19.0375 47.499C14.4286 47.5037 2.63285 49.1087 2.23766 44.0332C1.94653 40.2918 7.4543 38.034 10.3705 36.3672C15.5903 33.3854 20.9007 30.7418 25.9662 27.5802C27.234 27.078 26.7363 23.8054 27.5443 23.4982C32.7625 21.5143 33.6262 15.4416 26.9428 15.4699C24.6934 15.4798 24.9557 19.5474 22.7953 20.3263C21.4494 19.5194 21.8824 18.0184 22.2992 16.8107C23.0119 14.7435 24.4469 14.0136 26.515 13.0803ZM13.3597 37.4327C11.4217 38.5173 5.27379 41.4779 4.78063 43.1895C4.95235 44.3747 4.69262 43.8851 5.47497 44.7549C7.90906 45.4493 24.6622 45.0707 28.5043 45.082C32.448 45.0542 36.3936 45.045 40.3373 45.0547C43.3028 45.087 48.4601 45.7362 50.8861 44.2529C51.4125 43.3622 51.3988 43.7933 51.2318 42.9502C49.6393 41.3856 29.4456 29.5652 27.7211 29.4239L13.3597 37.4327Z" fill="#010101"/>
</svg>',
            ],
            [
                'heading'   => 'Probatio domi',
                'paragraph' => 'Domī placide proba sine metu, serva tantum id quod tibi perfecte aptum est.',
                'icon'      => '<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M27.1709 4.13672C29.0448 4.43581 35.6246 11.4881 37.4043 13.2637C37.3808 11.563 37.4477 9.91487 37.5098 8.21582C40.0681 8.05673 43.8255 8.17636 46.4717 8.1875C46.6987 12.5926 46.5661 17.6485 46.5547 22.1045C48.2469 23.7925 53.0486 27.3478 52.6465 29.3682C51.0758 30.6997 48.8208 30.2964 46.5566 30.2471C46.5671 37.4887 46.8156 46.1334 46.4512 53.2637C42.051 53.4721 36.5816 53.3327 32.1465 53.3057C31.8525 48.6683 32.0576 42.6178 32.0693 37.877C32.0736 37.3774 32.0598 37.5252 31.8311 37.0273C30.1835 36.5247 25.0266 36.7612 23.1357 36.793C23.1513 39.3989 23.6645 51.896 22.3574 53.1035C20.8632 53.4248 10.6132 53.6374 9.22852 52.9072C8.22748 50.6491 8.63555 33.8747 8.64941 30.2119C6.96395 30.2612 2.74028 30.7984 2.22754 29.0293C3.78504 26.2366 23.494 7.57485 27.1709 4.13672ZM27.3154 7.2168C20.5524 13.7761 12.6536 21.1024 6.21094 27.7842C7.7213 27.7524 9.22415 27.5742 10.5645 28.0928C11.4416 29.2974 11.0762 48.0842 11.0713 50.9551L20.7148 50.9619C20.6994 48.2654 20.156 36.0555 21.4883 34.7266C22.8055 34.367 31.8649 34.1414 33.6895 34.6807C34.1468 34.8159 34.1703 35.1486 34.4141 35.6211L34.3975 50.9619H44.207C44.2007 44.0017 43.8635 34.5659 44.2393 27.8125L48.9688 27.7695C47.5844 26.4252 45.4396 24.4624 44.2188 23.0879L44.2051 10.5146L39.7773 10.5117C39.834 12.5798 40.2774 15.2714 38.8857 16.8145C36.0008 17.1992 30.6872 8.53724 27.3154 7.2168Z" fill="#010101"/>
</svg>',
            ],
            [
                'heading'   => 'Redditus facilis',
                'paragraph' => 'Si mercis non convenit, redde eam uno ictu per tabularium personale tuum.',
                'icon'      => '<svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.2031 26.8916C20.1098 28.276 19.7764 31.5847 19.7177 33.2754C31.0024 33.4503 42.4289 33.1666 53.75 33.3447C53.8716 37.2228 53.7943 41.2267 53.7695 45.1162C46.1972 45.3801 37.327 45.195 29.6562 45.1885L19.6865 45.208C19.7315 46.9179 20.4948 51.4162 18.2334 51.6191C15.7136 50.3068 2.4015 41.0831 1.55366 39.0234C2.77372 37.0304 15.5977 28.3559 18.3818 26.3838L19.2031 26.8916ZM16.7832 30.4512C12.7716 33.3836 8.72974 36.2837 4.65816 39.1514C8.80916 42.1987 12.994 45.2107 17.2119 48.1836C17.1832 46.6147 17.0138 44.8216 17.4746 43.3467C19.1402 42.3282 32.0108 42.7578 34.6914 42.7676L51.4091 42.751L51.4052 35.7051L29.0849 35.7393C26.7887 35.7425 19.7441 35.8935 18.0283 35.5C16.0509 33.4339 18.1264 31.5684 16.7832 30.4512ZM36.2578 3.60938C38.4574 4.17945 52.7366 14.6023 53.6259 16.5566C52.987 17.8731 37.9425 28.8281 36.3779 28.6826C35.201 27.5814 35.5774 24.0355 35.6084 22.374L26.6621 22.377C22.5411 22.3899 4.61852 22.7298 1.96675 21.9023C0.92493 20.323 1.39732 12.9379 1.44331 10.6094C11.7623 10.1851 25.0748 10.5348 35.622 10.5381C35.5728 8.63872 35.2307 5.14947 36.2578 3.60938ZM37.9882 7.56641C37.973 9.20012 38.0221 11.352 37.9052 12.9404L3.8564 12.9502L3.8603 19.8857L21.7998 19.9004C26.5534 19.8955 33.3398 19.7147 37.9277 20.0127C37.9515 21.8066 38.0018 23.654 37.9765 25.4414C41.6666 22.6675 46.6819 18.8313 50.4931 16.3506C48.6033 14.9493 39.632 8.25788 37.9882 7.56641Z" fill="#010101"/>
</svg>',
            ],
        ];
    }
}
