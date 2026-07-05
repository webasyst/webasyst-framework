<?php

class siteCustomHero5BlockType extends siteBlockType {
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
            $options['columns'] = 2;
        }
        $options['type'] = 'site.CustomHero5';
        parent::__construct($options);
    }

    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getMainBlock();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getImageColumn());
        $hseq->addChild($this->getTextColumn());

        $result->addChild($hseq, '');

        return $result;
    }

    private function getMainBlock()
    {
        $result = $this->getEmptyBlockData();

        // Настраиваем свойства основного блока
        $img_url = wa()->getAppStaticUrl('site').'img/blocks/hero/road.jpg';
        $result->data = [
            'block_props'   => [
                $this->elements['main'] => [
                    'padding-top'    => 'p-t-30',
                ],
                $this->elements['wrapper'] => [
                    'flex-align'     => "y-c",
                    'margin-top'     => 'm-t-a',
                ],
            ],
            'inline_props' => [
                $this->elements['main'] => [
                    'background' => [
                        'type' => 'self_color',
                        'value' => 'linear-gradient(#0055e826, #0055e826), center top / cover url('.$img_url.')',
                        'name' => 'Self color',
                        'layers' => [
                            [
                                'type' => 'self_color',
                                'value' => 'linear-gradient(#0055e826, #0055e826)',
                                'name' => 'Self color',
                                'css' => '#0055e826',
                            ],
                            [
                                'type' => 'image',
                                'value' => 'center top / cover url('.$img_url.')',
                                'alignmentX' => 'center',
                                'alignmentY' => 'top',
                                'file_name' => 'road.jpg',
                                'file_url' => $img_url,
                                'space' => 'cover',
                                'name' => 'Image',
                            ],
                        ],
                        'uuid' => 1,
                    ],
                ],
                $this->elements['wrapper'] => [
                    'background' => [
                        'type' => 'self_color',
                        'value' => 'linear-gradient(180deg,  #FFFFFF00 0%,  #ffffff00 50%,  #ffffff 50%)',
                        'name' => 'Self color',
                        'layers' => [
                            [
                                'type' => 'self_color',
                                'value' => 'linear-gradient(180deg,  #FFFFFF00 0%,  #ffffff00 50%,  #ffffff 50%)',
                                'name' => 'Self color',
                                'css' => 'gradient',
                                'gradient' => [
                                    'type' => 'linear-gradient',
                                    'degree' => 180,
                                    'stops' => [
                                        ['color' => '#FFFFFF00', 'stop' => '0'],
                                        ['color' => '#ffffff00', 'stop' => '50'],
                                        ['color' => '#ffffff', 'stop' => '50'],
                                    ],
                                ],
                                'uuid' => 1,
                            ],
                        ],
                        'uuid' => 1,
                    ],
                ],
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
            'elements'      => $this->elements,
        ];

        return $result;
    }

    private function getImageColumn(): siteBlockData
    {
        $vseq = $this->createSequence();

        $img = (new siteImageBlockType())->getExampleBlockData();
        $img->data = [
            'block_props' => [
                'border-color' => [
                    'name' => 'black and white',
                    'value' => 'br-wh',
                    'type' => 'palette',
                ],
                'border-width' => [
                    'name' => _w('Width 2'),
                    'value' => 'b-w-m',
                    'unit' => 'px',
                    'type' => 'library',
                ],
                'border-style' => [
                    'value' => 'b-d-a',
                    'type' => 'all',
                ],
                'border-radius' => 'b-r-r',
                'margin-left' => 'm-l-a',
                'margin-right' => 'm-r-a',
            ],
            'image' => [
                'type' => 'svg',
                'svg_html' => '<svg width="120" height="120" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_3602_3253)"><circle cx="100" cy="100" r="100" fill="black"/><path d="M146.275 72.0319C156.475 71.1838 166.938 74.9341 173.919 82.4959C180.049 89.0341 183.258 97.7834 182.808 106.735C181.965 127.013 163.659 141.474 143.835 138.459C135.694 137.222 128.901 133.672 123.376 127.558C117.494 121.091 114.415 112.557 114.814 103.824C115.127 96.8572 117.654 90.1717 122.029 84.7399C122.912 83.6351 123.903 82.4642 124.95 81.5185C125.664 80.8529 126.534 79.8656 127.514 79.8115C128.036 79.7851 128.545 79.9751 128.923 80.3366C131.078 82.362 128.424 84.2745 127.227 85.5843C109.629 104.835 123.374 134.454 149.503 134.298C157.285 134.235 164.719 131.057 170.139 125.471C175.503 119.973 178.433 112.55 178.271 104.87C178.196 97.2849 175.102 90.0416 169.67 84.7453C163.957 79.1624 156.209 76.1599 148.226 76.4355C146.075 76.4924 144.298 76.8903 142.187 77.1123C140.354 77.305 139.856 75.4842 139.942 74.0573C140.744 72.7503 141.752 72.5889 143.192 72.3844C144.218 72.2387 145.243 72.1371 146.275 72.0319Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M134.986 43.7939C137.879 43.8004 140.092 44.2129 142.231 46.3892C143.684 47.8418 144.483 49.8236 144.445 51.8779C144.39 57.1343 140.206 60.6295 135.101 60.402C132.03 60.1178 132.043 55.8139 135.641 56.0743C139.247 56.3351 141.455 51.7434 138.781 49.229C138.228 48.7142 137.537 48.3713 136.793 48.2421C135.722 48.0527 132.356 48.1122 131.098 48.1268C128.461 48.1575 125.721 48.0756 123.104 48.1242C123.927 49.9704 124.482 51.7773 125.226 53.629C126.917 57.8761 128.714 62.0805 130.615 66.2383C134.239 74.1767 138.078 82.0155 142.125 89.746C144.892 94.9742 147.763 100.15 150.652 105.322C151.524 106.712 151.418 108.375 149.759 109.102C147.742 109.985 146.532 106.961 145.748 105.6C144.56 103.534 143.402 101.452 142.274 99.3536C137.923 91.4965 133.846 83.4904 130.051 75.3499C129.047 73.1963 127.189 69.6392 126.441 67.4954C125.111 69.0436 123.852 70.7203 122.546 72.2951L109.8 87.7794C103.968 94.9446 94.9192 106.944 85.326 108.397C85.2282 109.447 84.9609 110.793 84.7164 111.836C82.6583 120.897 77.0683 128.762 69.1873 133.685C61.2531 138.557 51.7027 140.06 42.6553 137.863C33.989 135.767 26.5189 130.296 21.9059 122.666C17.2556 115.075 15.9729 105.515 18.082 96.8837C20.5799 86.911 27.8906 78.4668 37.3863 74.5432C45.0077 71.3941 51.7379 71.3598 59.6009 73.3006C61.4921 73.8652 63.3303 74.5937 65.0945 75.4783C68.0415 76.9472 70.6355 78.7382 73.0433 80.9891C74.9787 82.7983 76.3375 85.5116 72.8311 86.4715C71.8885 86.232 71.5481 85.9245 70.9047 85.1948C69.8799 84.0324 68.8128 83.1287 67.5649 82.2094C64.4214 79.8582 60.8339 78.1688 57.0191 77.2432C49.5751 75.4549 41.7247 76.7387 35.2379 80.8049C28.7333 84.8062 24.0849 91.228 22.3159 98.6573C20.5966 106.163 21.9087 114.044 25.9673 120.588C30.0338 127.134 36.5345 131.797 44.0389 133.549C51.683 135.297 60.1113 134.039 66.7674 129.842C72.5379 126.217 76.9367 120.773 79.2694 114.37C79.9498 112.485 80.2946 110.876 80.7348 108.93C77.94 109.029 75.1375 109.234 72.339 109.334C67.5925 109.475 62.8442 109.548 58.0957 109.554C55.7661 109.565 53.4293 109.6 51.1017 109.544C49.7581 109.511 49.462 108.51 49.1848 107.409C49.2566 107.087 49.4776 106.257 49.7244 106.089C51.5207 104.88 53.5562 103.977 55.4224 102.85C63.8637 97.7483 71.6926 91.7164 79.5798 85.8159C80.5086 85.1211 81.3966 84.4135 82.3618 83.7677C81.467 82.5103 79.7327 78.1623 79.0788 76.571C76.6642 70.6949 74.0885 64.8185 71.7087 58.9371C69.2355 58.6865 67.8696 57.8159 65.6996 56.8812C65.0319 56.5935 63.2314 56.9941 62.4876 56.4846C61.794 56.0094 61.6868 55.3094 61.495 54.589C61.5787 54.2758 61.6487 53.941 61.7667 53.6386C61.9677 53.1233 62.3028 52.7742 62.8078 52.553C63.596 52.2078 82.3531 52.3345 84.397 52.4092C84.8416 52.4254 85.5077 52.4042 85.9178 52.5916C86.4504 52.8351 86.8372 53.3939 87.0066 53.9404C87.1973 54.556 87.1048 55.1242 86.7936 55.686C85.7143 57.6351 82.5004 56.3081 80.7325 56.8338C79.9721 57.0598 79.225 57.5484 78.5061 57.8875C77.7747 58.2324 77.0151 58.4526 76.2364 58.6626C76.7508 59.6128 77.7297 62.1053 78.203 63.2167C79.6532 66.5981 81.0734 69.9925 82.4636 73.3991C83.5043 75.9183 85.1161 78.9567 85.8793 81.4553C86.6049 80.7798 87.3547 80.2881 88.1663 79.7128C97.4541 73.1284 107.335 67.145 117.931 62.9144C119.73 62.1964 121.654 61.3898 123.491 60.9384C121.796 57.5761 120.498 53.4943 119.105 49.974C118.73 48.6787 117.817 47.1376 117.686 45.8196C117.536 44.2962 118.772 43.8469 119.907 43.7983L134.986 43.7939ZM121.801 66.0003C120.536 66.6201 119.241 67.0737 117.949 67.6311C115.614 68.6367 113.31 69.7135 111.041 70.8598C101.95 75.4897 93.505 81.1739 85.2027 87.0834C76.8228 93.048 68.6016 100.162 59.6791 105.312C63.4956 104.995 67.8727 105.197 71.8507 104.985C74.962 104.891 78.1621 104.662 81.2202 104.589C81.1939 102.637 81.1986 100.941 80.9793 98.9884C80.8734 98.0431 80.274 95.8365 80.3229 95.1686C80.372 94.5049 80.6454 93.8776 81.098 93.3899C83.3514 90.9953 84.5744 94.613 84.9501 96.304C85.5605 99.0518 85.7185 101.203 85.7249 103.977C89.145 102.639 91.1862 101.461 93.8977 98.963C97.4057 95.7315 100.531 91.9773 103.606 88.3418C107.868 83.3019 112.078 78.2172 116.234 73.0887C116.697 72.5155 121.892 66.1639 121.92 66.043L121.801 66.0003Z" fill="white"/></g><defs><clipPath id="clip0_3602_3253"><rect width="200" height="200" fill="white"/></clipPath></defs></svg>',
            ]
        ];

        $vseq->addChild($img);

        $block_props = [
            $this->column_elements['main'] => [
                'flex-align-vertical' => 'a-c-c',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'          => 'y-c',
            ],
        ];
        $inline_props = [
            $this->column_elements['main'] => [
                'min-height' => [
                    'name' => 'Content',
                    'value' => 'none',
                    'type' => 'content',
                ],
                'background' => [
                    'type' => 'self_color',
                    'value' => 'linear-gradient(180deg,  #FFFFFF00 0%,  #ffffff00 50%,  #ffffff 50%)',
                    'name' => 'Self color',
                    'layers' => [
                        [
                            'type' => 'self_color',
                            'value' => 'linear-gradient(180deg,  #FFFFFF00 0%,  #ffffff00 50%,  #ffffff 50%)',
                            'name' => 'Self color',
                            'css' => 'gradient',
                            'gradient' => [
                                'type' => 'linear-gradient',
                                'degree' => 180,
                                'stops' => [
                                    ['color' => '#FFFFFF00','stop' => '0'],
                                    ['color' => '#ffffff00','stop' => '50'],
                                    ['color' => '#ffffff','stop' => '50'],
                                ],
                            ],
                            'uuid' => 1,
                        ],
                    ],
                    'uuid' => 1,
                ],
            ],
        ];

        return $this->createColumn([
            'column' => 'st-12 st-12-lp st-12-tb st-12-mb',
            'block_props' => $block_props,
            'inline_props' => $inline_props,
            'wrapper_props' => ['flex-align' => 'y-c',],
        ], $vseq);
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

    public function getTextColumn(): siteBlockData {
        $vseq = $this->createSequence();

        // Заголовок
        $vseq->addChild(
            $this->createParagraph('Marco Velo', [
                'font-header' => 't-hdn',
                'font-size' => ['name' => 'Size #4','value' => 't-4','unit' => 'px','type' => 'library',],
                'margin-top' => 'm-t-8',
                'margin-bottom' => 'm-b-12',
                'align' => 't-c',
            ])
        );


        $vseq->addChild($this->getLogosRow());

        $vseq->addChild(
            $this->createParagraph('Birotae bonae qualitatis, instrumenta atque ornamenta cyclistarum pro iis qui omni itinere gaudent.', [
                'font-header' => 't-rgl',
                'font-size' => ['name' => 'Size #6','value' => 't-6','unit' => 'px','type' => 'library',],
                'margin-top' => 'm-t-0',
                'margin-bottom' => 'm-b-12',
                'align' => 't-c',
            ])
        );

        $vseq->addChild($this->getButtonsRow());

        $block_props = [
            $this->column_elements['main']    => [
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
                'flex-align-vertical' => 'a-c-c',
                'background' => [
                    'name' => 'black and white',
                    'value' => 'bg-wh',
                    'type' => 'palette',
                    'uuid' => 1,
                    'layers' => [
                        [
                            'name' => 'black and white',
                            'value' => 'bg-wh',
                            'type' => 'palette',
                            'uuid' => 1,
                        ],
                    ],
                ],
            ],
            $this->column_elements['wrapper'] => [
                'column-max-width' => 'fx-6',
                'flex-align' => 'y-c',
                'margin-left' => 'm-l-a',
                'margin-right' => 'm-r-a',
            ],
        ];

        $inline_props = [
            $this->column_elements['main']    => [
                'min-height' => [
                    'name' => 'Content',
                    'value' => 'none',
                    'type' => 'content',
                ],
            ],
        ];

        return $this->createColumn(
            [
                'column'        => 'st-12 st-12-lp st-12-tb st-12-mb',
                'block_props'   => $block_props,
                'wrapper_props' => ['flex-align' => 'y-c'],
                'inline_props'  => $inline_props,
            ],
            $vseq
        );
    }

    private function getLogosRow() {
        $hseq = $this->createSequence(true);
        $logos = [
            '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="black"/><path d="M21.6712 17.406L20.9901 16.3534C20.7802 16.029 20.6685 15.6509 20.6685 15.2645V10.8432C20.6685 9.07834 20.7964 5.31836 15.7576 5.31836C11.6585 5.31836 10.391 7.50288 9.99905 8.85402C9.84344 9.39043 10.2091 9.93662 10.7647 9.99395L12.3535 10.1579C12.7274 10.1964 13.086 9.99795 13.2482 9.65879C13.5562 9.01462 14.093 8.05528 15.7496 8.05528C16.3914 8.05528 17.2938 8.32671 17.2938 9.12035C17.2938 10.2719 17.2923 10.8177 17.2923 10.8177C17.2966 11.058 17.103 11.2552 16.8626 11.2552H15.3097C14.138 11.2552 9.68555 11.856 9.68555 16.3085C9.68555 20.7609 14.4102 20.2486 14.7238 20.1751C15.3091 20.0379 16.4056 19.6375 17.4361 18.6548C17.6092 18.4897 17.8855 18.5041 18.0359 18.6902L18.868 19.72C19.1915 20.1202 19.7865 20.1623 20.1631 19.8116L21.531 18.5376C21.8459 18.2443 21.905 17.7673 21.6712 17.406ZM14.841 17.1579C13.2592 17.1579 13.3042 15.7278 13.6692 15.0489C14.0107 14.4136 14.913 13.2875 17.3352 13.2108L17.3564 14.3925C17.3723 15.2709 16.9681 16.1147 16.2496 16.6202C15.8044 16.9335 15.3187 17.1579 14.841 17.1579Z" fill="white"/><path d="M4.41465 21.2236C4.17409 21.0063 4.43587 20.6213 4.72668 20.7646C6.77053 21.7714 10.6183 23.328 14.8715 23.328C19.218 23.328 22.8541 22.2898 24.6842 21.649C24.9818 21.5448 25.1962 21.9344 24.9512 22.1329C23.4993 23.3088 20.3812 25.1281 14.9858 25.1281C9.61059 25.1281 6.11903 22.7639 4.41465 21.2236Z" fill="white"/><path d="M22.7722 20.5897C23.3391 20.1699 24.7478 19.4462 26.8531 20.079C27.0465 20.1372 27.1784 20.3161 27.1799 20.5181C27.185 21.2088 27.0361 22.8304 25.6841 24.178C25.5903 24.2715 25.4322 24.1797 25.4669 24.0518C25.6978 23.2015 26.1672 21.3777 26.0183 21.1232C25.8476 20.8315 23.7139 20.8176 22.8494 20.824C22.7233 20.825 22.6708 20.6648 22.7722 20.5897Z" fill="white"/></svg>',
            '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="32" rx="16" fill="black"/><path d="M28.2374 13.0039L25.907 17.933L23.5671 13.0012H21.9387L22.3558 13.8243C21.8052 12.9571 20.7164 12.7369 19.7213 12.7369C16.8101 12.7369 16.6242 14.4162 16.6242 14.6832H18.0723C18.0723 14.6832 18.148 13.7032 19.6208 13.7032C20.5774 13.7032 21.3179 14.1643 21.3179 15.0507V15.3673H19.6208C17.7378 15.3673 16.5981 15.8504 16.2719 16.8305C16.3049 16.613 16.3228 16.3873 16.3228 16.1546C16.3228 14.2703 15.117 12.7562 13.0799 12.7562C11.1721 12.7562 10.5789 13.8408 10.5789 13.8408V10.2139H9.17901V15.7348C9.15836 14.5359 8.41921 12.7383 5.83423 12.7383C3.89756 12.7397 2.28711 13.6027 2.28711 16.2111C2.28711 18.2758 3.37175 19.5765 5.88378 19.5765C8.8404 19.5765 9.03035 17.5256 9.03035 17.5256H7.60022C7.60022 17.5256 7.29189 18.6295 5.79706 18.6295C4.58028 18.6295 3.70486 17.7637 3.70486 16.5511H9.18176V18.2661C9.18176 18.7231 9.1501 19.3659 9.1501 19.3659H10.5169C10.5169 19.3659 10.5665 18.9048 10.5665 18.4836C10.5665 18.4836 11.2409 19.5958 13.0771 19.5958C14.6435 19.5958 15.8025 18.6694 16.1865 17.242C16.1796 17.3164 16.1741 17.3934 16.1741 17.4733C16.1741 18.8594 17.2753 19.6137 18.7646 19.6137C20.7935 19.6137 21.4473 18.434 21.4473 18.434C21.4473 18.9034 21.4817 19.3659 21.4817 19.3659H22.7687C22.7687 19.3659 22.7192 18.7919 22.7192 18.4258V15.2572C22.7192 14.8319 22.6531 14.4781 22.5334 14.1808L25.1445 19.337L23.9167 21.7857H25.4652L29.7157 13.0039H28.2387H28.2374ZM3.73651 15.5793C3.73651 14.39 4.76748 13.7128 5.7833 13.7128C6.94227 13.7128 7.73236 14.4602 7.73236 15.5793H3.73651ZM12.7344 18.6006C11.3332 18.6006 10.5775 17.4485 10.5775 16.1725C10.5775 14.9833 11.2547 13.7569 12.7248 13.7569C14.0379 13.7569 14.872 14.7823 14.872 16.1588C14.872 17.6343 13.9085 18.6006 12.7344 18.6006V18.6006ZM21.3166 16.7369C21.3166 17.2875 20.9945 18.6501 19.1005 18.6501C18.0654 18.6501 17.6194 18.1051 17.6194 17.4733C17.6194 16.3239 19.117 16.3171 21.3166 16.3171V16.7369V16.7369Z" fill="white"/></svg>',
            '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_3536_3161)"><rect width="32" height="32" rx="16" fill="black"/><mask id="mask0_3536_3161" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="32" height="32"><rect width="32" height="32" rx="16" fill="black"/></mask><g mask="url(#mask0_3536_3161)"><path d="M8.11015 8.29756L-5.97852 26.732L-2.24288 30.9943L8.21689 17.1418L7.14956 24.7074L13.0198 26.732L20.1709 15.3304C19.8507 17.4615 19.317 22.3631 24.0133 23.8549C31.3778 26.0926 37.7817 12.8795 40.7703 6.16642L36.501 3.92871C33.1923 10.8549 28.0691 18.5271 26.0412 17.9943C24.0133 17.4615 25.8277 10.9615 27.0018 6.80576V6.6992L20.4911 4.4615L12.6996 17.1418L13.767 10.2156L8.11015 8.29756Z" fill="white"/></g></g><defs><clipPath id="clip0_3536_3161"><rect width="32" height="32" fill="white"/></clipPath></defs></svg>',
            '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="32" rx="16" fill="black"/><path d="M23.232 12.5575C22.2862 12.5559 21.3619 12.8396 20.58 13.3715V8.68555H18.461V17.3285C18.461 19.9585 20.601 22.0725 23.221 22.0725C23.8484 22.0744 24.47 21.9524 25.0502 21.7134C25.6304 21.4745 26.1577 21.1234 26.6018 20.6802C27.046 20.2371 27.3984 19.7106 27.6386 19.131C27.8789 18.5513 28.0024 17.93 28.002 17.3025C28.002 14.6335 25.884 12.5575 23.232 12.5575ZM13.627 17.9355L11.6889 12.8905H10.204L8.25395 17.9355L6.30495 12.8905H4.00195L7.40895 21.7605H8.89395L10.938 16.4775L12.993 21.7605H14.478L17.875 12.8905H15.577L13.627 17.9355ZM23.221 19.9535C22.8738 19.9547 22.5298 19.8872 22.2089 19.7549C21.8879 19.6226 21.5963 19.4281 21.3508 19.1827C21.1054 18.9372 20.9109 18.6456 20.7786 18.3246C20.6463 18.0037 20.5788 17.6597 20.58 17.3125C20.58 15.8175 21.715 14.6815 23.232 14.6815C24.748 14.6815 25.884 15.8645 25.884 17.3115C25.884 18.7595 24.6739 19.9535 23.216 19.9535H23.221Z" fill="white"/></svg>',
            '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.00195312" width="32" height="32" rx="16" fill="black"/><mask id="mask0_3536_3163" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="33" height="32"><rect x="0.00390625" width="32" height="32" rx="16" fill="black"/></mask><g mask="url(#mask0_3536_3163)"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.7636 16.0438L15.2924 14.2186L18.4021 12.1328L22.1213 12.3888L22.6641 14.1728L39.6163 32.4092L13.1016 40.4441L15.7636 16.0438Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M3.40329 21.3916C2.19999 21.8891 0.855675 21.0863 0.670086 19.7596C0.543553 18.8553 1.04056 17.9646 1.86084 17.6255C3.06418 17.1281 4.40844 17.9308 4.59409 19.2574C4.72055 20.1619 4.22354 21.0526 3.40329 21.3916ZM2.0146 15.7433C-0.311007 16.1191 -1.6773 18.5678 -0.828402 20.8388C-0.200317 22.5196 1.52854 23.5518 3.24946 23.2737C5.57516 22.898 6.9414 20.4494 6.09257 18.1782C5.46441 16.4975 3.73553 15.4653 2.0146 15.7433Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.21503 14.4208C6.69227 14.5608 6.41007 15.1543 6.66615 15.6759C6.85394 16.0584 7.30048 16.2342 7.70587 16.1256L10.06 15.4948L7.71203 21.3859C7.63501 21.5792 7.81013 21.7803 8.00917 21.727L13.7557 20.1872C14.1611 20.0786 14.4598 19.7029 14.4312 19.2778C14.3922 18.6981 13.8511 18.3253 13.3284 18.4654L10.4602 19.2339L12.8063 13.3472C12.8841 13.152 12.7074 12.9491 12.5065 13.0029L7.21503 14.4208Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M30.1614 8.23467C29.79 8.42378 29.6188 8.86615 29.7276 9.27227L30.4919 12.1246L24.914 9.67751C24.7194 9.59224 24.5145 9.77055 24.5697 9.97677L26.1476 15.8657C26.2564 16.2717 26.6258 16.5693 27.042 16.5474C27.6225 16.5169 27.9937 15.9676 27.8506 15.4338L27.0799 12.5572L32.6578 15.0043C32.8522 15.0895 33.0574 14.9113 33.002 14.7051L31.4185 8.79471C31.2754 8.2609 30.6791 7.97083 30.1614 8.23467Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M18.1829 11.2902C15.3892 12.0388 13.5666 14.2959 14.1121 16.3317C14.6576 18.3674 17.3645 19.4109 20.1583 18.6623C22.9519 17.9136 24.7746 15.6565 24.229 13.6208C23.6835 11.5851 20.9767 10.5417 18.1829 11.2902ZM18.6439 13.0106C20.5739 12.4935 22.2943 13.1448 22.5427 14.0727C22.7914 15.0005 21.6274 16.4247 19.6973 16.9418C17.7672 17.4591 16.0469 16.8077 15.7983 15.8798C15.5497 14.952 16.7138 13.5278 18.6439 13.0106Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M18.6444 13.0109C20.5745 12.4938 22.2948 13.1451 22.5433 14.073C22.792 15.0008 21.628 16.425 19.6979 16.9421C17.7678 17.4594 16.0475 16.808 15.7989 15.8801C15.5503 14.9523 16.7144 13.5281 18.6444 13.0109Z" fill="black"/></g></svg>',
        ];
        $block_props = [
            'margin-bottom' => 'm-b-14',
            'margin-right' => 'm-r-14-lp m-r-12',
            'picture-size' => 'i-l',
        ];

        foreach ($logos as $svg_html) {
            $hseq->addChild($this->getImage($svg_html, $block_props));
        }

        $props = [
            'block_props' => [
                'padding-top' => 'p-t-0',
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
        ];

        return $this->createRow($props, [$hseq]);
    }

    private function getButtonsRow() {
        $hseq = $this->createSequence(true);

        $buttons = ['Servitium', 'De nobis', 'Servitium'];
        $block_props = [
            'button-size' => 'inp-m p-l-13 p-r-13',
            'button-style' => ['name' => 'Palette', 'value' => 'btn-blc-strk', 'type' => 'palette'],
            'margin-bottom' => 'm-b-12',
            'margin-right' => 'm-r-12',
        ];

        foreach ($buttons as $text) {
            $button = $this->createButton($text, $block_props);
            $hseq->addChild($button);
        }

        $props = [
            'block_props' => [
                'padding-top' => 'p-t-10',
                'padding-bottom' => 'p-b-10',
            ],
            'wrapper_props' => [
                'justify-align' => 'y-j-cnt',
            ],
        ];

        return $this->createRow($props, [$hseq]);
    }


    private function createParagraph(string $text, array $block_props = [], $tag = 'p') {
        $paragraph = (new siteParagraphBlockType())->getEmptyBlockData();

        $paragraph->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $paragraph;
    }

    private function createButton(string $text, array $block_props = [], $tag = 'a') {
        $button = (new siteButtonBlockType())->getEmptyBlockData();

        $button->data = [
            'html'        => $text,
            'block_props' => $block_props,
            'tag'         => $tag,
        ];

        return $button;
    }

    private function getImage($svg_html, $block_props = []) {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'svg',
                'svg_html' => $svg_html,
            ],
            'block_props' => $block_props,
        ];

        return $imageBlock;
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

    /**
     * Создаёт ряд блоков
     *
     * @param array $props
     * @param array $content
     * @return siteBlockData
     */

    private function createRow(array $props, array $content): siteBlockData {
        $row = (new siteRowBlockType())->getExampleBlockData();
        $row->data['block_props'] = $props['block_props'] ?? [];
        $row->data['wrapper_props'] = $props['wrapper_props'] ?? [];
        $row->data['inline_props'] = $props['inline_props'] ?? [];

        $hseq = reset($row->children['']);

        foreach ($content as $item) {
            $hseq->addChild($item);
        }

        return $row;
    }
}
