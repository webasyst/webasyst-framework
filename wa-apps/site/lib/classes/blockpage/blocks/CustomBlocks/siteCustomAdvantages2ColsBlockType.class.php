<?php

class siteCustomAdvantages2ColsBlockType extends siteBlockType {
    /**
     * Элементы блока
     */
    protected array $elements = [
        'main'    => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
    ];

    public function __construct(array $options = []) {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 2;
        }
        $options['type'] = 'site.CustomAdvantages2Cols';
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
            'column'         => "st-6 st-6-lp st-12-tb st-12-mb",
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
        $row->data['wrapper_props'] = ['justify-align' => 'j-s', 'flex-wrap' => 'n-wr-ds n-wr-lp'];

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
                'font-size'     => ['name' => 'Size #4', 'value' => 't-4', 'unit' => 'px', 'type' => 'library'],
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
                'margin-right' => 'm-r-16',
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
                'heading'   => 'Recognitio ante expeditionem',
                'paragraph' => 'Quaevis ordinatio inspicitur ad integritatem, complexum et defectum absentiam. Diligenter et cum singulari cura integritatis involvimus, ut res perfecta condicione perveniat.',
                'icon'      => '<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M20.9815 7.89062C22.3546 7.84095 23.7057 7.80483 24.5145 8.62862C24.9778 9.10047 24.8274 9.87944 24.2596 10.2183C20.977 12.1768 12.3137 10.4756 11.042 23.01C10.9741 23.679 10.633 24.4229 9.96459 24.4963C9.9348 24.4995 9.90391 24.5023 9.8715 24.5046C9.41541 24.538 8.94267 24.4002 8.6551 24.0446C6.72044 21.6525 9.23906 16.5302 10.7149 14.4053C13.2281 10.7865 16.3733 8.89916 20.9815 7.89062Z" fill="#010101"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M22.6358 0C44.926 0.0794765 52.694 23.4969 40.7861 38.5791C41.8952 39.7399 42.9891 41.2015 44.585 41.5137C51.779 38.8247 55.6984 48.1003 60.3086 51.4609C65.3513 55.1361 65.7051 63.6662 58.0342 63.9961C54.3573 64.1539 50.5869 59.5458 48.5039 57.3359C44.0673 53.0737 39.0551 50.2502 42.1299 44.2764L38.6406 40.7529C30.3815 47.9048 19.0349 48.836 9.82228 42.2285C-4.22046 32.1572 -3.26467 10.0721 13.0693 2.30371C16.3704 0.733204 18.9387 0.261833 22.6358 0ZM48.6336 44.3174C48.0079 43.8591 47.1619 43.9304 46.5712 44.433C41.8233 48.4722 45.2836 49.4288 49.3936 53.9922C50.8595 55.6192 54.5431 59.3721 56.8887 60.935C57.4288 61.2948 58.126 61.2643 58.6624 60.899C62.9406 57.9852 60.3857 55.7218 57.0342 52.4365C55.069 50.5096 51.2773 46.2538 48.6336 44.3174ZM25.917 43.3926C50.7579 40.1737 46.9274 0 20.458 3.18848C-5.50238 7.89062 0.482752 45.2012 25.917 43.3926Z" fill="#010101"/>
</svg>',
            ],
            [
                'heading'   => 'Ratio individuorum',
                'paragraph' => 'Adde singularitatem emptioni tuae — insculpere, acu pingere vel imprimere possumus secundum designamentum tuum. Fac producto, qui indolem tuam exprimat!',
                'icon'      => '<svg width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
<path fill-rule="evenodd" clip-rule="evenodd" d="M30.3821 41.6123C39.1237 38.689 43.1825 52.0327 34.7685 54.5048C26.3544 56.977 21.6405 44.5357 30.3821 41.6123ZM35.3349 45.8445C34.4084 44.9198 33.0023 44.5292 31.6689 44.8255C29.6931 45.2643 28.4545 47.0561 28.8735 48.873C29.2924 50.6898 31.2137 51.8751 33.2157 51.5502C34.5692 51.331 35.6833 50.4568 36.1244 49.2694C36.5655 48.0818 36.2633 46.7695 35.3349 45.8445Z" fill="#010101"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M11.5455 29.1258C19.2614 26.8433 22.3516 38.5241 14.6694 40.8215C6.98722 43.119 3.82964 31.4083 11.5455 29.1258ZM15.894 34.5815C15.6563 33.1999 14.2807 32.2267 12.756 32.362C11.6865 32.457 10.764 33.0846 10.3571 33.9924C9.95225 34.9002 10.1297 35.939 10.8205 36.6897C11.5114 37.4406 12.6012 37.78 13.6505 37.5717C15.147 37.2743 16.1317 35.9632 15.894 34.5815Z" fill="#010101"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M48.0539 16.2417C50.2157 16.1381 53.3171 18.0779 54.0704 20.876C54.4953 22.5048 54.175 24.2217 53.1841 25.6304C51.9192 27.4181 50.887 28.0132 48.4313 28.0174C45.9756 28.0217 44.0317 26.898 42.9985 24.4633C42.3298 22.8556 42.4307 21.0656 43.2766 19.5287C44.4045 17.4651 45.892 16.3454 48.0539 16.2417ZM51.0936 21.4066C50.5518 20.071 48.9382 19.3758 47.4638 19.8416C46.481 20.1519 45.76 20.9237 45.5787 21.8583C45.3954 22.7931 45.7822 23.7444 46.5877 24.3452C47.3934 24.9458 48.4911 25.102 49.4579 24.7529C50.9081 24.2291 51.6354 22.7423 51.0936 21.4066Z" fill="#010101"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M16.4621 14.0064C24.1929 11.234 27.9821 22.9199 20.4986 25.4586C13.0151 27.9972 8.73131 16.7789 16.4621 14.0064ZM21.1631 18.8923C20.6616 17.5612 19.1006 16.8257 17.6202 17.2212C16.5991 17.4944 15.8237 18.2589 15.6041 19.2109C15.3846 20.1627 15.757 21.1474 16.5726 21.7732C17.3883 22.399 18.5144 22.5644 19.5053 22.2035C20.9374 21.6809 21.6666 20.2236 21.1631 18.8923Z" fill="#010101"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M33.9575 8.45741C41.8125 9.44723 42.3137 18.6114 34.519 20.3449C32.1309 20.876 29.588 19.0991 28.4742 16.5736C27.7875 14.982 27.8419 13.2036 28.6252 11.6494C29.6746 9.55828 32.463 8.26909 33.9575 8.45741ZM36.5894 13.552C36.1422 12.1409 34.5229 11.3316 32.9781 11.7474C31.4454 12.1604 30.5672 13.6262 31.0123 15.0275C31.4555 16.4287 33.0569 17.2386 34.5957 16.8397C36.1425 16.4379 37.0365 14.9632 36.5894 13.552Z" fill="#010101"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M28.6237 1.23927C36.4163 0.224953 44.6099 2.50563 50.8678 6.75222C57.6776 11.3726 62.5478 18.6548 63.7099 26.3538C66.3606 43.9309 50.064 38.1938 46.5775 43.3584C44.5051 46.4265 48.0318 49.9168 48.8234 52.9141C49.2483 54.5282 49.1296 56.3422 48.1326 57.7681C46.1245 60.6456 41.4719 61.7076 38.0579 62.5052L37.5685 62.5834C19.0747 65.4159 3.21716 53.4696 0.431522 36.9192C-1.02068 28.5215 1.2251 19.9407 6.67538 13.0475C12.2002 6.18225 19.5016 2.50595 28.6237 1.23927ZM31.7877 4.22459C23.4311 4.69473 16.7783 7.02177 11.0561 12.8507C5.43871 18.6681 2.61294 26.3014 3.21513 34.0379C3.765 41.5845 7.57759 48.6222 13.8154 53.6004C20.1035 58.5577 27.5861 60.4432 35.7877 59.6461C39.119 58.9881 47.1515 57.932 45.5807 53.1596C44.1909 48.9363 40.8756 46.6188 43.5181 41.9652C46.8112 36.1639 55.5042 39.6634 59.2647 34.5783C61.3352 31.6367 61.1075 27.5793 60.1569 24.2871C58.1286 17.3308 53.172 11.3883 46.3643 7.75593C41.2584 5.04459 37.534 4.43643 31.7877 4.22459Z" fill="#010101"/>
</svg>',
            ],
        ];
    }
}
