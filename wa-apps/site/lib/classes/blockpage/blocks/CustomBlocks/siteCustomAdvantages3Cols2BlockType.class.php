<?php

class siteCustomAdvantages3Cols2BlockType extends siteBlockType {
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
        $options['type'] = 'site.CustomAdvantages3Cols2';
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
        $vseq = $this->createVerticalSequence();

        $row = $this->getRow([$this->getIcon($icon), $this->getHeading($heading)]);
        $vseq->addChild($row);

        $vseq->addChild($this->getParagraph($paragraph));

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
        $row->data['block_props'] = ['padding-top' => 'p-t-8', 'padding-bottom' => 'p-b-8'];
        $row->data['wrapper_props'] = ['justify-align' => 'j-s'];

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
                'margin-top'    => 'm-t-1',
                'margin-bottom' => 'm-b-8',
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
                'margin-bottom' => 'm-b-4',
                'margin-right' => 'm-r-12',
                'picture-size' => 'i-m',
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
                'heading'   => 'Collectiones exclusivas',
                'paragraph' => 'Series limitatae et merces exclusivae, quae apud competitores non inveniuntur.',
                'icon'      => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M14.0528 4.24414C14.7393 4.94573 18.5319 12.8608 19.2637 14.3428C20.2841 13.4353 25.8858 7.90263 26.836 8.51172C27.1295 9.55845 24.3509 18.5834 24.0928 20.3877C23.9099 21.6692 23.9086 22.4454 23.919 23.7969C21.2781 23.9822 16.9035 23.8555 14.1377 23.8516C12.6033 23.8571 4.64238 24.1156 3.91215 23.5254C3.70534 23.1022 3.7465 22.1746 3.74809 21.6846C3.75594 18.2984 0.530882 11.4636 0.866251 8.47656L1.06156 8.3457C2.44756 8.78605 7.02288 13.0848 8.43949 14.3359C9.84901 11.4707 12.0215 6.73643 13.6123 4.09961L14.0528 4.24414ZM21.7745 20.4678C20.6982 20.2872 17.6502 20.386 16.3916 20.3887L5.72074 20.4014C5.71713 20.8932 5.64622 21.5677 5.87113 21.9473C6.67609 22.12 9.82125 22.0332 10.8233 22.0332L21.9141 22.0312C21.927 21.5069 22.0073 20.893 21.7745 20.4678ZM13.8721 8.23437C12.5229 10.9209 11.098 13.8049 9.67387 16.3662L9.12602 17.3525L8.21293 16.6895C7.1003 15.8815 5.96634 14.908 4.86918 13.9453C4.59406 13.7039 4.32076 13.4641 4.05082 13.2266L5.49613 18.8193L13.9786 18.8203L22.2051 18.8125C22.6576 17.0174 23.1227 15.1468 23.6016 13.3018C23.3737 13.5019 23.1429 13.7059 22.9112 13.9062C22.2494 14.4786 21.5809 15.0419 21.0137 15.4853C20.7309 15.7064 20.463 15.9058 20.2276 16.0645C20.0156 16.2073 19.7527 16.3711 19.502 16.458L19.1778 16.5703L18.8536 16.46C18.406 16.3083 18.0612 15.9608 17.8272 15.6855C17.5666 15.3789 17.3087 14.9973 17.0606 14.5879C16.5631 13.7669 16.0376 12.7216 15.5332 11.6777C15.0166 10.6084 14.5353 9.56666 14.083 8.65039C14.011 8.50435 13.9391 8.36633 13.8721 8.23437Z" fill="#010101"/>
</svg>',
            ],
            [
                'heading'   => 'Recognitiones verae',
                'paragraph' => 'Legite opiniones honestas cum imaginibus et experientiam vestram communicate.',
                'icon'      => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M2.75464 4.16323C4.30168 3.98832 10.0367 4.05513 11.7527 4.08218C16.0067 4.14897 21.0926 3.94304 25.2742 4.15542C27.2504 5.28954 27.5293 6.15885 27.4793 8.23356C27.383 12.1884 27.6975 16.5523 27.3269 20.4563C27.0596 23.2687 22.3589 22.7586 20.0193 22.7307H11.282C10.3471 23.536 6.30692 27.7789 5.23999 27.0891C4.94985 26.1835 5.0801 23.7918 5.09839 22.7317C4.61093 22.7396 3.67879 22.7528 3.22046 22.6789C2.32026 22.5285 1.53071 22.051 1.04077 21.3615C-0.0020673 19.8685 0.567688 8.56131 0.690188 6.20914C0.730001 5.46473 2.05249 4.5497 2.75464 4.16323ZM14.8084 6.3566C12.2423 6.35953 9.62481 6.37698 7.50367 6.39371C5.67895 6.40811 4.21271 6.42084 3.46851 6.42496C3.32398 6.52425 3.18197 6.63391 3.06714 6.74527C2.98175 6.82817 2.93249 6.89162 2.90894 6.93082L2.90796 6.9318C2.73767 11.1633 2.76493 15.4926 2.90992 19.7365L2.91089 19.7404C2.91861 19.7643 2.93483 19.805 2.96265 19.8586C3.0198 19.9684 3.10637 20.096 3.20777 20.2111C3.27993 20.2929 3.34765 20.3516 3.3982 20.3908C4.19088 20.233 4.89049 20.167 5.47925 20.2639C6.19006 20.3811 6.76013 20.7431 7.09546 21.3791C7.35628 21.8743 7.42641 22.4592 7.448 23.0022L10.2 20.5969L10.4832 20.3488L20.6912 20.3674H20.7078C21.6094 20.3843 22.3877 20.4542 23.2214 20.427C23.9201 20.4041 24.4752 20.3086 24.8923 20.1184C24.9873 19.9223 25.0324 19.6317 25.0935 19.1115C25.1957 18.2433 25.2639 15.8157 25.2683 13.3293C25.2727 10.8269 25.2115 8.45336 25.0857 7.65641L25.0095 7.26578C24.9766 7.12298 24.9355 6.97183 24.8855 6.83121C24.8413 6.70707 24.7956 6.61028 24.7556 6.54117C24.6087 6.52337 24.3997 6.50611 24.1296 6.48941C23.555 6.45391 22.7577 6.42663 21.8005 6.40641C19.8884 6.36602 17.376 6.35367 14.8084 6.3566ZM13.5925 11.1896C13.8749 11.196 14.1549 11.2301 14.4285 11.2922C16.6621 11.8076 16.8019 14.5558 14.3503 15.6193C11.3143 15.4108 10.966 12.4926 13.5925 11.1896ZM7.70289 11.2052C10.7304 11.3405 11.2008 14.2466 8.6482 15.6076C5.54685 15.4755 5.07099 12.5918 7.70289 11.2052ZM19.3181 11.2277C19.505 11.2261 19.6936 11.2261 19.8796 11.2404C20.5254 11.2945 21.1155 11.5891 21.5037 12.0519C22.5861 13.3365 21.7657 14.7891 20.3992 15.5773C17.3987 15.7253 16.6207 12.6444 19.3181 11.2277Z" fill="#010101"/>
</svg>',
            ],
            [
                'heading'   => 'Programma fidelitatis',
                'paragraph' => 'Accumulate puncta bonorum ex quaque emptione et ea in deductiones commutate.',
                'icon'      => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M8.95203 1.50892C11.9899 1.4483 13.0079 3.89353 13.8798 6.00403C14.6394 4.23033 15.787 1.68817 18.3046 1.57044C19.1444 1.52791 19.9669 1.80228 20.5682 2.3263C21.2459 2.91606 21.6203 3.72973 21.6005 4.57337C21.5798 5.83035 21.0305 6.40621 20.1014 7.22083C22.1879 7.20802 24.2747 7.22058 26.3612 7.25892C26.5163 8.72851 26.4838 11.2258 26.4003 12.754C26.3788 13.1436 26.1872 13.2534 25.912 13.4406C25.4548 13.4967 25.2929 13.5055 24.8475 13.632C24.6877 14.4015 24.7278 15.7575 24.7294 16.5753L24.7596 22.795C24.7693 23.8445 25.1477 25.9889 24.2362 26.6115L11.5116 26.6202C9.14672 26.6262 6.72816 26.6553 4.36805 26.6007C3.96239 26.5913 3.25266 26.4849 3.19129 26.09C2.58913 22.2196 3.57333 17.4109 2.84949 13.5802C2.82086 13.4294 2.0384 13.7664 1.44422 13.1603C1.27798 12.1171 1.19073 8.3506 1.56531 7.45814C2.53683 7.06655 6.42195 7.22007 7.67957 7.22962C5.29088 5.10422 5.64954 2.5355 8.95203 1.50892ZM5.24402 14.0294L5.25281 24.3224L12.8104 24.3234L12.8075 14.0294H5.24402ZM15.245 24.3165L22.5419 24.3214L22.5302 14.0255L15.2499 14.0236L15.245 24.3165ZM3.55555 11.8253L12.8007 11.8234C12.8082 11.0277 12.8106 10.232 12.8075 9.43665L3.56336 9.42982L3.55555 11.8253ZM14.9725 9.42982L14.9686 11.8243H24.1542C24.1731 11.0264 24.1805 10.228 24.1786 9.42982H14.9725ZM9.16981 3.75403C8.43049 4.18105 8.25358 4.46685 8.22156 4.5431C8.24071 4.58117 8.29742 4.66703 8.44031 4.78821C8.75054 5.05111 9.27164 5.32397 9.92664 5.56946C10.4868 5.77939 11.0892 5.94643 11.6268 6.06751C11.4828 5.75289 11.2776 5.40523 11.0272 5.07142C10.7394 4.6877 10.4183 4.35117 10.1132 4.11243C9.7892 3.85901 9.57302 3.78532 9.48719 3.7765H9.48328C9.3796 3.76561 9.27471 3.75838 9.16981 3.75403ZM18.1425 3.77357C17.1572 4.36967 16.6888 5.06039 16.2645 6.04407C16.4205 6.01946 16.5766 5.99105 16.7362 5.95423C17.4494 5.78973 18.2556 5.58011 18.83 5.22376C19.1051 5.05299 19.2649 4.88499 19.3466 4.73353C19.4127 4.61084 19.4592 4.44197 19.411 4.17103C19.4074 4.16774 19.4032 4.16344 19.3983 4.15931C19.3382 4.10858 19.2238 4.03869 19.0448 3.96985C18.7649 3.86228 18.4308 3.79675 18.1425 3.77357Z" fill="#010101"/>
</svg>',
            ],
        ];
    }
}
