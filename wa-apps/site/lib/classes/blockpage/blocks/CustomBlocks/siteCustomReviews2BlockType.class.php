<?php

class siteCustomReviews2BlockType extends siteBlockType {
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
            $options['columns'] = 1;
        }
        $options['type'] = 'site.CustomReviews2';
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

        // Добавляем последовательности в основной блок
        $hseq->addChild($this->getHeadingColumn());
        $hseq->addChild($this->getLinkColumn());

        // Создаём карточки отзывов
        $reviews = $this->getExampleReviews();
        foreach ($reviews as $review_data) {
            $hseq->addChild($this->getReviewColumn($review_data));
        }

        $hseq->addChild($this->getBtnColumn());

        $result->addChild($hseq, '');

        // Настраиваем свойства основного блока
        $result->data = [
            'block_props'   => $this->getMainBlockProps(),
            'wrapper_props' => ['justify-align' => 'j-s'],
            'elements'      => $this->elements,
        ];

        return $result;
    }

    /**
     * Получает данные для примера карточек
     *
     * @return array
     * @throws \waException
     */
    private function getExampleReviews(): array {
        $base_url = wa()->getAppStaticUrl('site') . 'img/blocks/reviews/';

        $qutes_img = '<svg width="19" height="16" viewBox="0 0 19 16" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M7.29488 0.830078C4.88256 4.7554 4.39644 5.33008 4.29488 7.63008C4.49488 7.56341 4.79488 7.53008 5.19488 7.53008C6.12821 7.53008 7.07643 7.98642 7.49488 8.53008C8.14481 9.18001 8.51744 10.5345 8.47771 11.23C8.47771 12.5633 8.06155 13.2967 7.19488 14.2301C6.32821 15.0967 5.26155 15.5301 3.99488 15.5301C2.86155 15.5301 1.96155 15.1967 1.29488 14.5301C0.40132 13.6365 0 12.1634 0 10.83C0 8.29669 0.732551 6.63875 2.64598 4.40643C3.24598 3.70643 4.42821 2.49674 7.29488 0.830078Z" fill="#252627"/>
<path d="M17.096 0.830078C14.6837 4.7554 14.1976 5.33008 14.096 7.63008C14.296 7.56341 14.596 7.53008 14.996 7.53008C15.9294 7.53008 16.8776 7.98642 17.296 8.53008C17.946 9.18001 18.3186 10.5345 18.2789 11.23C18.2789 12.5633 17.8627 13.2967 16.996 14.2301C16.1294 15.0967 15.0627 15.5301 13.796 15.5301C12.6627 15.5301 11.7627 15.1967 11.096 14.5301C10.2025 13.6365 9.80117 12.1634 9.80117 10.83C9.80117 8.29669 10.5337 6.63875 12.4471 4.40643C13.0471 3.70643 14.2294 2.49674 17.096 0.830078Z" fill="#252627"/>
</svg>';


        return [
            [
                'image'    => $base_url . '08.jpg',
                'author_pic'    => $base_url . '09.jpg',
                'author_name'   => 'Urn elementum',
                'quote_image'   => $qutes_img,
                'text'          => ['Vestibulum dictum ultrices elit a luctus. Sed in ante ut leo congue posuere at sit amet ligula. Pellentesque eget augue nec nisl sodales blandit sed et sem. Aenean quis finibus arcu, in hendrerit purus.'],
            ],
            [
                'image'    => $base_url . '07.jpg',
                'author_pic'    => $base_url . '10.jpg',
                'author_name'   => 'Accumsan velit',
                'quote_image'   => $qutes_img,
                'text'          => ['Auctor purus luctus enim egestas, ac scelerisque ante pulvinar. Donec ut rhoncus.', 'Duis felis ante, varius in neque eu, tempor suscipit sem. Maecenas ullamcorper gravida sem sit amet cursus.'],
            ],
            [
                'image'    => $base_url . '03.jpg',
                'author_pic'    => $base_url . '09.jpg',
                'author_name'   => 'Urn elementum',
                'quote_image'   => $qutes_img,
                'text'          => ['Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Praesent auctor purus luctus enim egestas, ac scelerisque ante pulvinar. Donec ut rhoncus ex. Suspendisse ac rhoncus nisl, eu tempor urna.'],
            ],
            [
                'image'    => $base_url . '02.jpg',
                'author_pic'    => $base_url . '10.jpg',
                'author_name'   => 'Accumsan velit',
                'quote_image'   => $qutes_img,
                'text'          => ['Habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas.', 'In vulputate lobortis ante, sed hendrerit mauris scelerisque ut.'],
            ],
        ];
    }

    /**
     * Получает свойства основного блока
     *
     * @return array
     */
    private function getMainBlockProps(): array {
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
            'max-width'      => 'cnt',
            'flex-align'     => 'y-c',
        ];

        return $block_props;
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
     * Получает секции для формы настроек
     *
     * @return array
     */
    private function getFormSections(): array {
        return [
            ['type' => 'ColumnsGroup', 'name' => _w('Columns')],
            ['type' => 'RowsAlignGroup', 'name' => _w('Columns alignment')],
            ['type' => 'RowsWrapGroup', 'name' => _w('Wrap line')],
            ['type' => 'TabsWrapperGroup', 'name' => _w('Tabs')],
            ['type' => 'CommonLinkGroup', 'name' => _w('Link or action'), 'is_hidden' => true],
            ['type' => 'MaxWidthToggleGroup', 'name' => _w('Max width')],
            ['type' => 'BackgroundColorGroup', 'name' => _w('Background')],
            ['type' => 'HeightGroup', 'name' => _w('Height')],
            ['type' => 'PaddingGroup', 'name' => _w('Padding')],
            ['type' => 'MarginGroup', 'name' => _w('Margin')],
            ['type' => 'BorderGroup', 'name' => _w('Border')],
            ['type' => 'BorderRadiusGroup', 'name' => _w('Angle')],
            ['type' => 'ShadowsGroup', 'name' => _w('Shadows')],
            ['type' => 'IdGroup', 'name' => _w('Identifier (ID)')],
        ];
    }

    /**
     * Создаёт колонку с настройками
     *
     * @param string        $column_classes
     * @param array         $block_props
     * @param siteBlockData $content
     * @return siteBlockData
     */
    private function createColumn(string $column_classes, array $block_props, siteBlockData $content, array $wrapper_props = ['flex-align' => 'y-l']): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = [
            'elements'    => $this->column_elements,
            'column'      => $column_classes,
            'block_props' => $block_props,
            'indestructible' => false,
            'wrapper_props'  => $wrapper_props,
        ];

        $column->addChild($content, '');

        return $column;
    }

    /**
     * Получает колонку с заголовком
     *
     * @return siteBlockData
     */
    public function getHeadingColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getTitle());

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
                'padding-top'         => 'p-t-0',
                'padding-bottom'      => 'p-b-0',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
            ],
        ];

        return $this->createColumn(
            'st-9-lp st-9-tb st-7-mb st-9',
            $block_props,
            $vseq
        );
    }

    /**
     * Получает колонку со ссылкой
     *
     * @return siteBlockData
     */
    public function getLinkColumn(): siteBlockData {
        $vseq = $this->createSequence();
        $vseq->addChild($this->getLink());

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
                'padding-top'         => 'p-t-8',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align' => 'y-c',
            ],
        ];

        return $this->createColumn(
            'st-3 st-3-lp st-3-tb st-5-mb',
            $block_props,
            $vseq
        );
    }

    /**
     * Получает колонку с карточкой отзыва
     *
     * @param array $card_data
     * @return siteBlockData
     */
    public function getReviewColumn(array $card_data): siteBlockData {
        $vseq = $this->createSequence();

        $vseq->addChild($this->getReviewImage($card_data['image']));
        $vseq->addChild($this->getQutesImage($card_data['quote_image']));
        foreach ($card_data['text'] as $text) {
            $vseq->addChild($this->getReviewText($text));
        }
        $vseq->addChild($this->getReviewAuthor($card_data['author_pic'], $card_data['author_name']));

        $block_props = [
            $this->column_elements['main']    => [
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'flex-align'     => 'y-c',
                'padding-top'    => 'p-t-12',
                'padding-bottom' => 'p-b-12',
            ],
        ];

        return $this->createColumn(
            'st-12-mb st-3 st-3-lp st-6-tb',
            $block_props,
            $vseq
        );
    }

    public function getBtnColumn(): siteBlockData {
        $vseq = $this->createSequence();

        $text = $this->createHeadingBlock(
            'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
            'p',
            [
                'align' => 't-c',
                'font-header' => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-14',
                'margin-top'    => 'm-t-16',
                'max-width'     => 'fx-9',
            ]
        );

        $btn = (new siteButtonBlockType())->getEmptyBlockData();
        $btn->data = [
            'html' => 'Pharetra nec',
            'tag' => 'a',
            'block_props' => [
                'border-radius' => 'b-r-r',
                'button-size' => 'inp-l p-l-14 p-r-14',
                'button-style' => 'btn-blc',
                'margin-bottom' => 'm-b-12',
            ],
        ];

        $vseq->addChild($text);
        $vseq->addChild($btn);

        $block_props = [
            $this->column_elements['main']    => [
                'flex-align-vertical' => 'a-c-c',
                'padding-top'         => 'p-t-0',
                'padding-bottom'      => 'p-b-0',
                'padding-left' => 'p-l-clm',
                'padding-right' => 'p-r-clm',
            ],
            $this->column_elements['wrapper'] => [
                'column-max-width' => 'fx-6',
                'flex-align' => 'y-c',
                'margin-left' => 'm-l-a',
                'margin-right' => 'm-r-a',
            ],
        ];

        return $this->createColumn(
            'st-12 st-12-lp st-12-tb st-12-mb',
            $block_props,
            $vseq,
            ['flex-align' => 'y-c']
        );
    }

    /**
     * Получает блок заголовка
     *
     * @return siteBlockData
     */
    private function getTitle(): siteBlockData {
        return $this->createHeadingBlock(
            'Dapibus',
            'h1',
            [
                'font-header' => 't-hdn',
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-8',
                'max-width'     => 'fx-9',
            ]
        );
    }

    /**
     * Получает блок ссылки
     *
     * @return siteBlockData
     */
    private function getLink(): siteBlockData {
        return $this->createHeadingBlock(
            '<span class="tx-bw-1"><u>Sollic</u>&nbsp;→&nbsp;</span>',
            'h1',
            [
                'align'      => 't-l',
                'margin-top' => 'm-t-0',
                'margin-left'   => 'm-l-a m-l-0-mb',
                'margin-bottom' => 'm-b-8',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            ]
        );
    }

    /**
     * Создаёт блок заголовка с настройками
     *
     * @param string $html
     * @param string $tag
     * @param array  $props
     * @return siteBlockData
     */
    private function createHeadingBlock(string $html, string $tag, array $props): siteBlockData {
        $block = (new siteHeadingBlockType())->getEmptyBlockData();

        $block->data = [
            'html'        => $html,
            'tag'         => $tag,
            'block_props' => $props,
        ];

        return $block;
    }

    /**
     * Получает блок изображения отзыва
     *
     * @param string $qutes_image
     * @return siteBlockData
     */
    private function getQutesImage(string $qutes_image): siteBlockData {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'svg',
                'svg_html' => $qutes_image,
            ],
            'block_props' => [
                'margin-bottom' => 'm-b-12',
            ],
        ];

        return $imageBlock;
    }

    /**
     * Получает блок изображения отзыва
     *
     * @param string $image_url
     * @return siteBlockData
     */
    private function getReviewImage(string $image_url): siteBlockData {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'address',
                'url_text' => $image_url,
            ],
            'block_props' => [
                'border-radius' => 'b-r-l',
                'margin-bottom' => 'm-b-14',
            ],
        ];

        return $imageBlock;
    }

    /**
     * Получает блок текста отзыва
     *
     * @param string $description
     * @return siteBlockData
     */
    private function getReviewText(string $text): siteBlockData {
        $descBlock = (new siteParagraphBlockType())->getEmptyBlockData();

        $descBlock->data = [
            'html'        => $text,
            'tag'         => 'p',
            'block_props' => [
                'align'         => 't-l',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-12',
                'margin-top'    => 'm-t-0',
            ],
        ];

        return $descBlock;
    }

    /**
     * Получает блок автора отзыва
     *
     * @param string $author_image
     * @param string $author_name
     * @return siteBlockData
     */
    private function getReviewAuthor(string $author_image, string $author_name): siteBlockData {
        $hseq = $this->createSequence(true, 'with_row', true);
        $hseq->addChild($this->getReviewAuthorImage($author_image));
        $hseq->addChild($this->getReviewAuthorName($author_name));

        return $hseq;
    }

    /**
     * Получает блок изображения автора
     *
     * @param string $image_url
     * @return siteBlockData
     */
    private function getReviewAuthorImage(string $image_url): siteBlockData {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'address',
                'url_text' => $image_url,
            ],
            'block_props' => [
                'border-radius' => 'b-r-m',
                'margin-bottom' => 'm-b-8',
                'margin-right'  => 'm-r-12',
                'picture-size'  => 'i-xl',
            ],
        ];

        return $imageBlock;
    }

    /**
     * Получает блок имени автора
     *
     * @param string $name
     * @return siteBlockData
     */
    private function getReviewAuthorName(string $name): siteBlockData {
        return $this->createHeadingBlock(
            $name,
            'h1',
            [
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-8',
                'margin-top'    => 'm-t-9',
                'font-header'   => 't-hdn',
                'align'         => 't-l',
            ]
        );
    }
}
