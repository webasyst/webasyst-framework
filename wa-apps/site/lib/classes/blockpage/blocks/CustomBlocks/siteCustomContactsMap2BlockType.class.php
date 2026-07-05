<?php

class siteCustomContactsMap2BlockType extends siteBlockType
{
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

    /**
     * Конструктор класса
     *
     * @param array $options
     */
    public function __construct(array $options = []) {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = 4;
        }
        $options['type'] = 'site.CustomContactsMap2';
        parent::__construct($options);
    }

    /**
     * Создаёт пример блока с данными
     *
     * @return siteBlockData
     * @throws \waException
     */
    public function getExampleBlockData(): siteBlockData {
        // Создаём основной блок
        $result = $this->getEmptyBlockData();

        // Создаём горизонтальную последовательность
        $hseq = $this->createSequence(true, 'only_columns', true);

        // Добавляем колонку с информацией
        foreach ($this->getInfoColumns() as $column) {
            $hseq->addChild($column);
        }

        // Добавляем колонку с картой
        $hseq->addChild($this->getMapColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => $this->getMainBlockProps(),
            'wrapper_props' => ['justify-align' => 'y-j-cnt'],
            'elements'      => $this->elements,
        ];

        return $result;
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
     * Получает свойства основного блока
     *
     * @return array
     */
    private function getMainBlockProps(): array {
        return [
            $this->elements['main']    => [
                'padding-top'    => 'p-t-18',
                'padding-bottom' => 'p-b-18',
                'padding-left' => 'p-l-blc',
                'padding-right' => 'p-r-blc',
            ],
            $this->elements['wrapper'] => [
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
                'max-width'      => 'cnt',
                'flex-align'     => 'y-c',
            ],
        ];
    }

    /**
     * Создаёт колонки с информацией о контактах
     *
     * @return siteBlockData[]
     */
    private function getInfoColumns(): array {
        // Добавляем заголовок
        $vseq = $this->createSequence();
        $vseq->addChild($this->createHeadingBlock(
            'Contingēns',
            'h2',
            [
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-14',
                'margin-top'    => 'm-t-0-mb m-t-4',
                'font-header'   => 't-hdn',
                'align'         => 't-c',
            ]
        ));
        // Создаём колонку для заголовка
        $header_column = $this->createColumn(
            'st-12 st-12-lp st-12-tb st-12-mb',
            [
                $this->column_elements['main']    => [
                    'padding-top'    => 'p-t-10-mb',
                    'padding-bottom' => 'p-b-10-mb',
                    'padding-left'   => 'p-l-clm',
                    'padding-right'  => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align'     => 'y-c',
                ],
            ],
            $vseq
        );

        $contact_column_1 = $this->createContactColumn([
            $this->createHeadingBlock(
                '<font color="" class="tx-blc">+1 234 567 89 10</font>',
                'h1',
                [
                    'font-size'     => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                    'margin-bottom' => 'm-b-10',
                    'margin-top'    => 'm-t-0',
                    'font-header'   => 't-rgl',
                    'align'         => 't-c',
                ]
            ),
            $this->createHeadingBlock(
                '<font color="" class="tx-blc">epistula@tabellario.romano</font>',
                'h1',
                [
                    'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                    'margin-bottom' => 'm-b-8',
                    'margin-top'    => 'm-t-4',
                    'font-header'   => 't-rgl',
                    'align'         => 't-c',
                ]
            )
        ]);

        $contact_column_2 = $this->createContactColumn([
            $this->createHeadingBlock(
                '<font color="" class="tx-bw-4">Fora Venalia</font>',
                'h1',
                [
                    'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                    'margin-bottom' => 'm-b-8',
                    'margin-top'    => 'm-t-0',
                    'font-header'   => 't-rgl',
                    'align'         => 't-c',
                ]
            ),
            $this->getMarketplaceLinks()
        ]);

        $contact_column_3 = $this->createContactColumn([
            $this->createHeadingBlock(
                '<font color="" class="tx-bw-4">Nos in Socialibus</font>',
                'h1',
                [
                    'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                    'margin-bottom' => 'm-b-8',
                    'margin-top'    => 'm-t-0',
                    'font-header'   => 't-rgl',
                    'align'         => 't-c',
                ]
            ),
            $this->getSocialNetworkLinks()
        ]);

        $contact_column_4 = $this->createContactColumn([
            $this->createHeadingBlock(
                '<font color="" class="tx-bw-4">Inscriptio Tabernae</font>',
                'h1',
                [
                    'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                    'margin-bottom' => 'm-b-12',
                    'margin-top'    => 'm-t-0',
                    'font-header'   => 't-rgl',
                    'align'         => 't-c',
                ]
            ),
            $this->createHeadingBlock(
                '<font color="" class="tx-bw-1">Roma, Via Augusta 10</font>',
                'h1',
                [
                    'font-size'     => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                    'margin-bottom' => 'm-b-0',
                    'margin-top'    => 'm-t-0',
                    'font-header'   => 't-rgl',
                    'align'         => 't-c',
                ]
            )
        ]);

        return [$header_column, $contact_column_1, $contact_column_2, $contact_column_3, $contact_column_4];
    }

    /**
     * Создаёт колонку с картой
     *
     * @return siteBlockData
     */
    private function getMapColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->createMapBlock());

        // Создаём колонку
        return $this->createColumn(
            'st-12 st-12-lp st-12-tb st-12-mb',
            [
                $this->column_elements['main']    => [
                    'padding-top'    => 'p-t-10-mb',
                    'padding-bottom' => 'p-b-10-mb',
                    'padding-left' => 'p-l-clm',
                    'padding-right' => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => [
                    'padding-top'    => 'p-t-12',
                    'padding-bottom' => 'p-b-12',
                    'flex-align'     => 'y-c',
                ],
            ],
            $vseq
        );
    }

    /**
     * Создаёт блок с заголовком
     *
     * @param string $html
     * @param string $tag
     * @param array  $props
     * @return \siteBlockData
     */
    private function createHeadingBlock(string $html, string $tag, array $props): siteBlockData {
        $heading = (new siteHeadingBlockType())->getEmptyBlockData();

        $heading->data = [
            'html'        => $html,
            'tag'         => $tag,
            'block_props' => $props,
        ];

        return $heading;
    }

    /**
     * Создаёт блок с картой
     *
     * @return siteBlockData
     */
    private function createMapBlock(): siteBlockData {
        $map = (new siteMapBlockType())->getEmptyBlockData();

        $map->data = [
            'html'        => '',
            'block_props' => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-0-mb m-b-0-lp m-b-0',
            ],
            'inline_props' => [
                'min-height' => [
                    'name' => 'Custom',
                    'type' => 'custom',
                    'unit' => 'px',
                    'value' => '450px',

                ]
            ],
        ];

        return $map;
    }

    /**
     * Создаёт колонку
     *
     * @param string $column_classes
     * @param array  $block_props
     * @return siteBlockData
     */
    private function createColumn(string $column_classes, array $block_props, siteBlockData $content): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = [
            'elements'       => $this->column_elements,
            'column'         => $column_classes,
            'block_props'    => $block_props,
            'indestructible' => false,
        ];

        $column->addChild($content, '');

        return $column;
    }

    /**
     * Создаёт колонку с контактной информацией
     *
     * @param siteBlockData[] $content
     * @return siteBlockData
     */
    private function createContactColumn(array $content): siteBlockData {
        $vseq = (new siteVerticalSequenceBlockType())->getExampleBlockData();

        $column = $this->createColumn(
            'st-3 st-6-lp st-6-tb st-12-mb',
            [
                $this->column_elements['main'] => [
                    'padding-top'    => 'p-t-12 p-t-10-mb',
                    'padding-bottom' => 'p-b-12 p-b-10-mb',
                    'padding-left'   => 'p-l-clm',
                    'padding-right'  => 'p-r-clm',
                ],
                $this->column_elements['wrapper'] => [
                    'flex-align'     => 'y-c',
                ],
            ],
            $vseq
        );

        foreach ($content as $c) {
            $vseq->addChild($c);
        }

        return $column;
    }

    private function getSocialNetworkLinks(): siteBlockData
    {
        $icon_links = [
            [
                'href' => 'https://t.me/',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto " viewBox="0 0 33 32" fill="var(--bw-1)"><g clip-path="url(#clip0_10717_116287)"><path d="M16.6499 0C25.4865 0 32.6499 7.16344 32.6499 16C32.6499 24.8366 25.4865 32 16.6499 32C7.81335 32 0.649902 24.8366 0.649902 16C0.649902 7.16344 7.81335 0 16.6499 0ZM23.1919 9.63184C22.5901 9.64249 21.6661 9.96352 17.2231 11.8115C15.6668 12.4589 12.557 13.7991 7.89307 15.8311C7.13574 16.1322 6.73883 16.4268 6.70264 16.7148C6.6333 17.2679 7.42934 17.44 8.43115 17.7656C9.24801 18.0312 10.3471 18.3421 10.9185 18.3545C11.4366 18.3657 12.0153 18.1523 12.6538 17.7139C17.0118 14.7721 19.2616 13.285 19.4028 13.2529C19.5025 13.2303 19.6404 13.202 19.7339 13.2852C19.8271 13.3681 19.818 13.5246 19.8081 13.5674C19.7289 13.9051 15.6367 17.6282 15.3999 17.874C14.4996 18.8091 13.4756 19.3809 15.0552 20.4219C16.422 21.3226 17.2175 21.8973 18.6255 22.8203C19.5254 23.4102 20.2315 24.1099 21.1606 24.0244C21.588 23.9849 22.0292 23.5831 22.2534 22.3848C22.7835 19.5511 23.8261 13.4108 24.0669 10.8809C24.0879 10.6592 24.0609 10.3754 24.0396 10.251C24.0182 10.1265 23.9736 9.94958 23.812 9.81836C23.6203 9.66281 23.3243 9.6295 23.1919 9.63184Z"></path></g><defs><linearGradient id="paint0_linear_10717_116287" x1="1600.65" y1="0" x2="1600.65" y2="3176.27" gradientUnits="userSpaceOnUse"><stop stop-color="#2AABEE"></stop><stop offset="1" stop-color="#229ED9"></stop></linearGradient><clipPath id="clip0_10717_116287"><rect width="32" height="32" transform="translate(0.649902)"></rect></clipPath></defs></svg>',
                'color' => 'tx-bw-1',
            ],
            [
                'href' => '',
                'icon' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_3536_3279)"><rect x="-0.00195312" width="32" height="32" rx="16" fill="black"/><path d="M16.0008 7C13.5566 7 13.2498 7.01069 12.2898 7.05438C11.3317 7.09825 10.6777 7.24994 10.1054 7.4725C9.51346 7.70238 9.01133 8.00988 8.51108 8.51031C8.01045 9.01056 7.70294 9.51269 7.47232 10.1044C7.24919 10.6769 7.09731 11.3311 7.05419 12.2888C7.01125 13.2488 7 13.5558 7 16C7 18.4443 7.01088 18.7501 7.05438 19.7101C7.09844 20.6682 7.25013 21.3222 7.47251 21.8944C7.70257 22.4864 8.01007 22.9885 8.51052 23.4888C9.01058 23.9894 9.51271 24.2976 10.1043 24.5275C10.6769 24.7501 11.3311 24.9018 12.2891 24.9456C13.2491 24.9893 13.5556 25 15.9997 25C18.4442 25 18.75 24.9893 19.71 24.9456C20.6681 24.9018 21.3229 24.7501 21.8955 24.5275C22.4873 24.2976 22.9887 23.9894 23.4887 23.4888C23.9894 22.9885 24.2969 22.4864 24.5275 21.8946C24.7487 21.3222 24.9006 20.668 24.9456 19.7103C24.9887 18.7503 25 18.4443 25 16C25 13.5558 24.9887 13.249 24.9456 12.289C24.9006 11.3309 24.7487 10.6769 24.5275 10.1046C24.2969 9.51269 23.9894 9.01056 23.4887 8.51031C22.9881 8.00969 22.4875 7.70219 21.895 7.4725C21.3212 7.24994 20.6668 7.09825 19.7087 7.05438C18.7487 7.01069 18.4431 7 15.998 7H16.0008ZM15.1935 8.62187C15.4331 8.6215 15.7005 8.62187 16.0008 8.62187C18.4039 8.62187 18.6887 8.6305 19.6376 8.67363C20.5151 8.71375 20.9914 8.86038 21.3086 8.98356C21.7287 9.14669 22.0281 9.34169 22.3429 9.65669C22.6579 9.97169 22.8529 10.2717 23.0164 10.6917C23.1396 11.0086 23.2864 11.4848 23.3264 12.3623C23.3695 13.3111 23.3789 13.5961 23.3789 15.9979C23.3789 18.3998 23.3695 18.6848 23.3264 19.6336C23.2862 20.5111 23.1396 20.9873 23.0164 21.3042C22.8533 21.7242 22.6579 22.0233 22.3429 22.3381C22.0279 22.6531 21.7288 22.8481 21.3086 23.0112C20.9918 23.1349 20.5151 23.2812 19.6376 23.3213C18.6889 23.3644 18.4039 23.3738 16.0008 23.3738C13.5976 23.3738 13.3128 23.3644 12.3641 23.3213C11.4865 23.2808 11.0103 23.1342 10.6929 23.011C10.2728 22.8479 9.97284 22.6529 9.65784 22.3379C9.34284 22.0229 9.14783 21.7236 8.98433 21.3034C8.86114 20.9866 8.71433 20.5103 8.67439 19.6328C8.63127 18.6841 8.62264 18.3991 8.62264 15.9957C8.62264 13.5923 8.63127 13.3088 8.67439 12.3601C8.71452 11.4826 8.86114 11.0063 8.98433 10.6891C9.14746 10.2691 9.34284 9.96906 9.65784 9.65406C9.97284 9.33906 10.2728 9.14406 10.6929 8.98056C11.0101 8.85681 11.4865 8.71056 12.3641 8.67025C13.1943 8.63275 13.5161 8.6215 15.1935 8.61963V8.62187ZM20.805 10.1162C20.2088 10.1162 19.725 10.5994 19.725 11.1959C19.725 11.7921 20.2088 12.2759 20.805 12.2759C21.4013 12.2759 21.885 11.7921 21.885 11.1959C21.885 10.5996 21.4013 10.1162 20.805 10.1162ZM16.0008 11.3781C13.4484 11.3781 11.3789 13.4476 11.3789 16C11.3789 18.5524 13.4484 20.6209 16.0008 20.6209C18.5533 20.6209 20.622 18.5524 20.622 16C20.622 13.4476 18.5533 11.3781 16.0008 11.3781ZM16.0008 13C17.6576 13 19.0009 14.3431 19.0009 16C19.0009 17.6567 17.6576 19 16.0008 19C14.3439 19 13.0008 17.6567 13.0008 16C13.0008 14.3431 14.3439 13 16.0008 13Z" fill="white"/></g><defs><clipPath id="clip0_3536_3279"><rect width="32" height="32" fill="white"/></clipPath></defs></svg>',
            ],
            [
                'href' => 'https://faq.whatsapp.com/5913398998672934',
                'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto" viewBox="0 0 33 32" fill="var(--bw-1)"><g clip-path="url(#clip0_10717_116298)"><path d="M16.9131 0.492188C20.8704 0.515741 24.6661 2.06705 27.5068 4.82227C30.3476 7.57755 32.0137 11.3244 32.1582 15.2793C32.2333 17.3038 31.9066 19.323 31.1973 21.2207C30.4879 23.1185 29.4101 24.8571 28.0254 26.3359C26.6405 27.8149 24.9761 29.0055 23.1289 29.8379C21.2819 30.6702 19.2879 31.1277 17.2627 31.1855H16.8242C14.5219 31.1862 12.2484 30.6693 10.1729 29.6729L2.13965 31.46H2.11719C2.10047 31.4599 2.08357 31.4562 2.06836 31.4492C2.05308 31.4422 2.03937 31.4316 2.02832 31.4189C2.01739 31.4063 2.00873 31.3919 2.00391 31.376C1.99906 31.3599 1.99781 31.3428 2 31.3262L3.35742 23.2041C2.07929 20.8656 1.43132 18.2349 1.47852 15.5703C1.52574 12.9058 2.26674 10.2995 3.62695 8.00781C4.98713 5.7162 6.92004 3.81771 9.23633 2.5C11.5528 1.18229 14.1728 0.490219 16.8379 0.492188H16.9131ZM10.8066 7.7002C10.6519 7.71881 10.5003 7.7605 10.3574 7.82422C10.167 7.90918 9.99589 8.03217 9.85352 8.18457C9.45116 8.59719 8.32581 9.5904 8.26074 11.6758C8.19581 13.7607 9.65239 15.8235 9.85645 16.1133C10.0602 16.4026 12.6426 20.9076 16.8955 22.7344C19.395 23.8112 20.4907 23.9961 21.2012 23.9961C21.494 23.9961 21.7152 23.9649 21.9463 23.9512C22.7256 23.903 24.4838 23.0026 24.8672 22.0234C25.2506 21.0442 25.2759 20.1878 25.1748 20.0166C25.0737 19.8456 24.7963 19.7219 24.3789 19.5029C23.9608 19.2836 21.9116 18.1882 21.5264 18.0342C21.3836 17.9677 21.2294 17.9276 21.0723 17.916C20.9701 17.9214 20.8711 17.9524 20.7832 18.0049C20.6952 18.0573 20.6208 18.1303 20.5674 18.2178C20.225 18.6441 19.4398 19.5694 19.1758 19.8369C19.1183 19.9031 19.0472 19.9569 18.9678 19.9941C18.8883 20.0312 18.8016 20.0511 18.7139 20.0527C18.5519 20.0456 18.3927 20.0029 18.249 19.9277C17.0078 19.4005 15.8757 18.6456 14.9121 17.7021C14.0118 16.8148 13.2479 15.7989 12.6455 14.6875C12.4127 14.256 12.6461 14.0331 12.8584 13.8311C13.0706 13.629 13.2976 13.3498 13.5166 13.1084C13.6964 12.9023 13.8464 12.6718 13.9619 12.4238C14.0216 12.3087 14.052 12.1804 14.0498 12.0508C14.0478 11.921 14.0137 11.7939 13.9502 11.6807C13.8492 11.4648 13.0947 9.34441 12.7402 8.49316C12.4528 7.76587 12.1101 7.741 11.8105 7.71875C11.5642 7.70164 11.2814 7.69313 10.999 7.68457H10.9629L10.8066 7.7002Z"></path></g><defs><clipPath id="clip0_10717_116298"><rect width="32" height="32" transform="translate(0.866699)"></rect></clipPath></defs></svg>',
                'color' => 'tx-bw-1',
            ],
            [
                'href' => 'https://vk.com/',
                'icon' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="black"/><path d="M17.029 23.0534C9.73568 23.0534 5.57568 18.0534 5.40234 9.7334H9.05568C9.17568 15.8401 11.869 18.4267 14.0023 18.9601V9.7334H17.4423V15.0001C19.549 14.7734 21.7623 12.3734 22.509 9.7334H25.949C25.6676 11.1026 25.1068 12.399 24.3015 13.5415C23.4962 14.684 22.4639 15.6481 21.269 16.3734C22.6028 17.0361 23.7808 17.9742 24.7255 19.1257C25.6701 20.2771 26.3598 21.6158 26.749 23.0534H22.9623C22.6129 21.8048 21.9027 20.687 20.9208 19.8403C19.9388 18.9935 18.7288 18.4554 17.4423 18.2934V23.0534H17.029Z" fill="white"/></svg>',
            ],
            [
                'href' => 'https://max.ru/',
                'icon' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_3522_11884)"><circle cx="16" cy="16" r="16" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M16.4633 25.4389C14.4221 25.4389 13.4734 25.1409 11.8245 23.9489C10.7816 25.2899 7.47881 26.3379 7.33478 24.5449C7.33478 23.199 7.03678 22.0616 6.69906 20.82C6.29677 19.2903 5.83984 17.5868 5.83984 15.1184C5.83984 9.2231 10.6773 4.78796 16.4087 4.78796C22.145 4.78796 26.6398 9.44163 26.6398 15.173C26.649 17.8831 25.5827 20.4861 23.6748 22.4107C21.7669 24.3354 19.1733 25.4245 16.4633 25.4389ZM16.5477 9.88365C13.7565 9.73962 11.5812 11.6716 11.0994 14.7012C10.7021 17.2093 11.4074 20.2638 12.0083 20.4227C12.2964 20.4922 13.0215 19.9062 13.4734 19.4542C14.2208 19.9705 15.091 20.2806 15.9965 20.3532C17.3867 20.42 18.7477 19.9388 19.787 19.0129C20.8262 18.0869 21.4607 16.7902 21.554 15.4015C21.6084 14.0099 21.1142 12.6525 20.178 11.6215C19.2417 10.5905 17.9381 9.96825 16.5477 9.88862V9.88365Z" fill="white"/></g><defs><clipPath id="clip0_3522_11884"><rect width="32" height="32" fill="white"/></clipPath></defs></svg>',
            ],
        ];

        return $this->getIconLinks($icon_links);
    }

    private function getMarketplaceLinks(): siteBlockData
    {
        $icon_links = [
            [
                'href' => 'https://www.amazon.com/',
                'icon' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="black"/><path d="M21.6712 17.406L20.9901 16.3534C20.7802 16.029 20.6685 15.6509 20.6685 15.2645V10.8432C20.6685 9.07834 20.7964 5.31836 15.7576 5.31836C11.6585 5.31836 10.391 7.50288 9.99905 8.85402C9.84344 9.39043 10.2091 9.93662 10.7647 9.99395L12.3535 10.1579C12.7274 10.1964 13.086 9.99795 13.2482 9.65879C13.5562 9.01462 14.093 8.05528 15.7496 8.05528C16.3914 8.05528 17.2938 8.32671 17.2938 9.12035C17.2938 10.2719 17.2923 10.8177 17.2923 10.8177C17.2966 11.058 17.103 11.2552 16.8626 11.2552H15.3097C14.138 11.2552 9.68555 11.856 9.68555 16.3085C9.68555 20.7609 14.4102 20.2486 14.7238 20.1751C15.3091 20.0379 16.4056 19.6375 17.4361 18.6548C17.6092 18.4897 17.8855 18.5041 18.0359 18.6902L18.868 19.72C19.1915 20.1202 19.7865 20.1623 20.1631 19.8116L21.531 18.5376C21.8459 18.2443 21.905 17.7673 21.6712 17.406ZM14.841 17.1579C13.2592 17.1579 13.3042 15.7278 13.6692 15.0489C14.0107 14.4136 14.913 13.2875 17.3352 13.2108L17.3564 14.3925C17.3723 15.2709 16.9681 16.1147 16.2496 16.6202C15.8044 16.9335 15.3187 17.1579 14.841 17.1579Z" fill="white"/><path d="M4.41465 21.2236C4.17409 21.0063 4.43587 20.6213 4.72668 20.7646C6.77053 21.7714 10.6183 23.328 14.8715 23.328C19.218 23.328 22.8541 22.2898 24.6842 21.649C24.9818 21.5448 25.1962 21.9344 24.9512 22.1329C23.4993 23.3088 20.3812 25.1281 14.9858 25.1281C9.61059 25.1281 6.11903 22.7639 4.41465 21.2236Z" fill="white"/><path d="M22.7722 20.5897C23.3391 20.1699 24.7478 19.4462 26.8531 20.079C27.0465 20.1372 27.1784 20.3161 27.1799 20.5181C27.185 21.2088 27.0361 22.8304 25.6841 24.178C25.5903 24.2715 25.4322 24.1797 25.4669 24.0518C25.6978 23.2015 26.1672 21.3777 26.0183 21.1232C25.8476 20.8315 23.7139 20.8176 22.8494 20.824C22.7233 20.825 22.6708 20.6648 22.7722 20.5897Z" fill="white"/></svg>',
            ],
            [
                'href' => 'https://www.ebay.com/',
                'icon' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="32" rx="16" fill="black"/><path d="M28.2374 13.0039L25.907 17.933L23.5671 13.0012H21.9387L22.3558 13.8243C21.8052 12.9571 20.7164 12.7369 19.7213 12.7369C16.8101 12.7369 16.6242 14.4162 16.6242 14.6832H18.0723C18.0723 14.6832 18.148 13.7032 19.6208 13.7032C20.5774 13.7032 21.3179 14.1643 21.3179 15.0507V15.3673H19.6208C17.7378 15.3673 16.5981 15.8504 16.2719 16.8305C16.3049 16.613 16.3228 16.3873 16.3228 16.1546C16.3228 14.2703 15.117 12.7562 13.0799 12.7562C11.1721 12.7562 10.5789 13.8408 10.5789 13.8408V10.2139H9.17901V15.7348C9.15836 14.5359 8.41921 12.7383 5.83423 12.7383C3.89756 12.7397 2.28711 13.6027 2.28711 16.2111C2.28711 18.2758 3.37175 19.5765 5.88378 19.5765C8.8404 19.5765 9.03035 17.5256 9.03035 17.5256H7.60022C7.60022 17.5256 7.29189 18.6295 5.79706 18.6295C4.58028 18.6295 3.70486 17.7637 3.70486 16.5511H9.18176V18.2661C9.18176 18.7231 9.1501 19.3659 9.1501 19.3659H10.5169C10.5169 19.3659 10.5665 18.9048 10.5665 18.4836C10.5665 18.4836 11.2409 19.5958 13.0771 19.5958C14.6435 19.5958 15.8025 18.6694 16.1865 17.242C16.1796 17.3164 16.1741 17.3934 16.1741 17.4733C16.1741 18.8594 17.2753 19.6137 18.7646 19.6137C20.7935 19.6137 21.4473 18.434 21.4473 18.434C21.4473 18.9034 21.4817 19.3659 21.4817 19.3659H22.7687C22.7687 19.3659 22.7192 18.7919 22.7192 18.4258V15.2572C22.7192 14.8319 22.6531 14.4781 22.5334 14.1808L25.1445 19.337L23.9167 21.7857H25.4652L29.7157 13.0039H28.2387H28.2374ZM3.73651 15.5793C3.73651 14.39 4.76748 13.7128 5.7833 13.7128C6.94227 13.7128 7.73236 14.4602 7.73236 15.5793H3.73651ZM12.7344 18.6006C11.3332 18.6006 10.5775 17.4485 10.5775 16.1725C10.5775 14.9833 11.2547 13.7569 12.7248 13.7569C14.0379 13.7569 14.872 14.7823 14.872 16.1588C14.872 17.6343 13.9085 18.6006 12.7344 18.6006V18.6006ZM21.3166 16.7369C21.3166 17.2875 20.9945 18.6501 19.1005 18.6501C18.0654 18.6501 17.6194 18.1051 17.6194 17.4733C17.6194 16.3239 19.117 16.3171 21.3166 16.3171V16.7369V16.7369Z" fill="white"/></svg>',
            ],
            [
                'href' => 'https://market.yandex.ru/',
                'icon' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_3536_3161)"><rect width="32" height="32" rx="16" fill="black"/><mask id="mask0_3536_3161" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="32" height="32"><rect width="32" height="32" rx="16" fill="black"/></mask><g mask="url(#mask0_3536_3161)"><path d="M8.11015 8.29756L-5.97852 26.732L-2.24288 30.9943L8.21689 17.1418L7.14956 24.7074L13.0198 26.732L20.1709 15.3304C19.8507 17.4615 19.317 22.3631 24.0133 23.8549C31.3778 26.0926 37.7817 12.8795 40.7703 6.16642L36.501 3.92871C33.1923 10.8549 28.0691 18.5271 26.0412 17.9943C24.0133 17.4615 25.8277 10.9615 27.0018 6.80576V6.6992L20.4911 4.4615L12.6996 17.1418L13.767 10.2156L8.11015 8.29756Z" fill="white"/></g></g><defs><clipPath id="clip0_3536_3161"><rect width="32" height="32" fill="white"/></clipPath></defs></svg>',
            ],
            [
                'href' => 'https://www.wildberries.ru/',
                'icon' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="32" height="32" rx="16" fill="black"/><path d="M23.232 12.5575C22.2862 12.5559 21.3619 12.8396 20.58 13.3715V8.68555H18.461V17.3285C18.461 19.9585 20.601 22.0725 23.221 22.0725C23.8484 22.0744 24.47 21.9524 25.0502 21.7134C25.6304 21.4745 26.1577 21.1234 26.6018 20.6802C27.046 20.2371 27.3984 19.7106 27.6386 19.131C27.8789 18.5513 28.0024 17.93 28.002 17.3025C28.002 14.6335 25.884 12.5575 23.232 12.5575ZM13.627 17.9355L11.6889 12.8905H10.204L8.25395 17.9355L6.30495 12.8905H4.00195L7.40895 21.7605H8.89395L10.938 16.4775L12.993 21.7605H14.478L17.875 12.8905H15.577L13.627 17.9355ZM23.221 19.9535C22.8738 19.9547 22.5298 19.8872 22.2089 19.7549C21.8879 19.6226 21.5963 19.4281 21.3508 19.1827C21.1054 18.9372 20.9109 18.6456 20.7786 18.3246C20.6463 18.0037 20.5788 17.6597 20.58 17.3125C20.58 15.8175 21.715 14.6815 23.232 14.6815C24.748 14.6815 25.884 15.8645 25.884 17.3115C25.884 18.7595 24.6739 19.9535 23.216 19.9535H23.221Z" fill="white"/></svg>',
            ],
            [
                'href' => 'https://www.ozon.ru/',
                'icon' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><rect x="0.00195312" width="32" height="32" rx="16" fill="black"/><mask id="mask0_3536_3163" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="33" height="32"><rect x="0.00390625" width="32" height="32" rx="16" fill="black"/></mask><g mask="url(#mask0_3536_3163)"><path fill-rule="evenodd" clip-rule="evenodd" d="M15.7636 16.0438L15.2924 14.2186L18.4021 12.1328L22.1213 12.3888L22.6641 14.1728L39.6163 32.4092L13.1016 40.4441L15.7636 16.0438Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M3.40329 21.3916C2.19999 21.8891 0.855675 21.0863 0.670086 19.7596C0.543553 18.8553 1.04056 17.9646 1.86084 17.6255C3.06418 17.1281 4.40844 17.9308 4.59409 19.2574C4.72055 20.1619 4.22354 21.0526 3.40329 21.3916ZM2.0146 15.7433C-0.311007 16.1191 -1.6773 18.5678 -0.828402 20.8388C-0.200317 22.5196 1.52854 23.5518 3.24946 23.2737C5.57516 22.898 6.9414 20.4494 6.09257 18.1782C5.46441 16.4975 3.73553 15.4653 2.0146 15.7433Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M7.21503 14.4208C6.69227 14.5608 6.41007 15.1543 6.66615 15.6759C6.85394 16.0584 7.30048 16.2342 7.70587 16.1256L10.06 15.4948L7.71203 21.3859C7.63501 21.5792 7.81013 21.7803 8.00917 21.727L13.7557 20.1872C14.1611 20.0786 14.4598 19.7029 14.4312 19.2778C14.3922 18.6981 13.8511 18.3253 13.3284 18.4654L10.4602 19.2339L12.8063 13.3472C12.8841 13.152 12.7074 12.9491 12.5065 13.0029L7.21503 14.4208Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M30.1614 8.23467C29.79 8.42378 29.6188 8.86615 29.7276 9.27227L30.4919 12.1246L24.914 9.67751C24.7194 9.59224 24.5145 9.77055 24.5697 9.97677L26.1476 15.8657C26.2564 16.2717 26.6258 16.5693 27.042 16.5474C27.6225 16.5169 27.9937 15.9676 27.8506 15.4338L27.0799 12.5572L32.6578 15.0043C32.8522 15.0895 33.0574 14.9113 33.002 14.7051L31.4185 8.79471C31.2754 8.2609 30.6791 7.97083 30.1614 8.23467Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M18.1829 11.2902C15.3892 12.0388 13.5666 14.2959 14.1121 16.3317C14.6576 18.3674 17.3645 19.4109 20.1583 18.6623C22.9519 17.9136 24.7746 15.6565 24.229 13.6208C23.6835 11.5851 20.9767 10.5417 18.1829 11.2902ZM18.6439 13.0106C20.5739 12.4935 22.2943 13.1448 22.5427 14.0727C22.7914 15.0005 21.6274 16.4247 19.6973 16.9418C17.7672 17.4591 16.0469 16.8077 15.7983 15.8798C15.5497 14.952 16.7138 13.5278 18.6439 13.0106Z" fill="white"/><path fill-rule="evenodd" clip-rule="evenodd" d="M18.6444 13.0109C20.5745 12.4938 22.2948 13.1451 22.5433 14.073C22.792 15.0008 21.628 16.425 19.6979 16.9421C17.7678 17.4594 16.0475 16.808 15.7989 15.8801C15.5503 14.9523 16.7144 13.5281 18.6444 13.0109Z" fill="black"/></g></svg>',
            ],
        ];

        return $this->getIconLinks($icon_links);
    }

    /**
     * @param array $icon_links
     * @return siteBlockData
     */
    private function getIconLinks(array $icon_links): siteBlockData
    {
        $row = (new siteRowBlockType())->getExampleBlockData();
        $row->data['block_props'] = ['padding-top' => 'p-t-4', 'margin-bottom' => 'm-b-12'];
        $row->data['wrapper_props'] = ['justify-align' => 'y-j-cnt', 'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb'];
        $hseq = reset($row->children['']);

        foreach ($icon_links as $link) {
            $image = (new siteImageBlockType())->getExampleBlockData();
            $image->data = [
                'image' => [
                    'type'     => 'svg',
                    'fill' => 'removed', // удаляем заливку
                    'svg_html' => $link['icon'],
                ],
                'block_props' => [
                    'margin-bottom' => 'm-b-8',
                    'margin-right' => 'm-r-11',
                    'margin-top' => 'm-t-2',
                    'picture-size' => 'i-l',
                ],
                'link_props' => [
                    'href' => $link['href'],
                    'data-value' => 'external-link',
                ],
            ];
            if (isset($item['color'])) {
                $image->data['image']['color'] = [
                    'name' => 'Palette',
                    'type' => 'palette',
                    'value' => $item['color'],
                ];
            }

            $hseq->addChild($image);
        }

        return $row;
    }

    /**
     * Рендерит блок
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
     * Получает конфигурацию формы настроек блока
     *
     * @return array
     */
    public function getRawBlockSettingsFormConfig(): array {
        return [
                'type_name'    => _w('Block'),
                'sections'     => $this->getFormSections(),
                'elements'     => $this->elements,
                'semi_headers' => [
                    'main'    => _w('Whole block'),
                    'wrapper' => _w('Container'),
                ],
            ] + parent::getRawBlockSettingsFormConfig();
    }

    /**
     * Получает секции формы настроек блока
     *
     * @return array
     */
    private function getFormSections(): array {
        return [
            [
                'type' => 'ColumnsGroup',
                'name' => _w('Columns'),
            ],
            [
                'type' => 'RowsAlignGroup',
                'name' => _w('Columns alignment'),
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
        ];
    }
}
