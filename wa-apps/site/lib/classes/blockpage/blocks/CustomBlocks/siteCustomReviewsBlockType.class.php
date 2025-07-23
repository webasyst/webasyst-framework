<?php

class siteCustomReviewsBlockType extends siteBlockType {
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

    /** @var array Стандартные настройки текста */
    private array $text_base_props = [
        'align'      => 't-l',
        'margin-top' => 'm-t-0',
    ];

    /** @var array Стандартные настройки заголовков */
    private array $heading_base_props = [
        'font-header' => 't-hdn',
    ];

    /** @var array Настройки выравнивания колонки */
    private array $column_base_props = [
        'indestructible' => false,
        'wrapper_props'  => [
            'flex-align' => 'y-l',
        ],
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
        $options['type'] = 'site.CustomReviews';
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

        $raitin_stars_4 = '<svg width="135" height="27" viewBox="0 0 135 27" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12.5489 2.92705C12.8483 2.00574 14.1517 2.00574 14.4511 2.92705L16.3064 8.63729C16.4403 9.04931 16.8243 9.32827 17.2575 9.32827H23.2616C24.2303 9.32827 24.6331 10.5679 23.8494 11.1373L18.9919 14.6664C18.6415 14.9211 18.4948 15.3724 18.6287 15.7844L20.484 21.4947C20.7834 22.416 19.7289 23.1821 18.9452 22.6127L14.0878 19.0836C13.7373 18.8289 13.2627 18.8289 12.9122 19.0836L8.0548 22.6127C7.27108 23.1821 6.2166 22.416 6.51596 21.4947L8.37132 15.7844C8.5052 15.3724 8.35854 14.9211 8.00805 14.6664L3.15064 11.1373C2.36692 10.5679 2.7697 9.32827 3.73842 9.32827H9.74252C10.1757 9.32827 10.5597 9.04931 10.6936 8.63729L12.5489 2.92705Z" fill="#E68B1D"/>
<path d="M39.5489 2.92705C39.8483 2.00574 41.1517 2.00574 41.4511 2.92705L43.3064 8.63729C43.4403 9.04931 43.8243 9.32827 44.2575 9.32827H50.2616C51.2303 9.32827 51.6331 10.5679 50.8494 11.1373L45.9919 14.6664C45.6415 14.9211 45.4948 15.3724 45.6287 15.7844L47.484 21.4947C47.7834 22.416 46.7289 23.1821 45.9452 22.6127L41.0878 19.0836C40.7373 18.8289 40.2627 18.8289 39.9122 19.0836L35.0548 22.6127C34.2711 23.1821 33.2166 22.416 33.516 21.4947L35.3713 15.7844C35.5052 15.3724 35.3585 14.9211 35.0081 14.6664L30.1506 11.1373C29.3669 10.5679 29.7697 9.32827 30.7384 9.32827H36.7425C37.1757 9.32827 37.5597 9.04931 37.6936 8.63729L39.5489 2.92705Z" fill="#E68B1D"/>
<path d="M66.5489 2.92705C66.8483 2.00574 68.1517 2.00574 68.4511 2.92705L70.3064 8.63729C70.4403 9.04931 70.8243 9.32827 71.2575 9.32827H77.2616C78.2303 9.32827 78.6331 10.5679 77.8494 11.1373L72.9919 14.6664C72.6415 14.9211 72.4948 15.3724 72.6287 15.7844L74.484 21.4947C74.7834 22.416 73.7289 23.1821 72.9452 22.6127L68.0878 19.0836C67.7373 18.8289 67.2627 18.8289 66.9122 19.0836L62.0548 22.6127C61.2711 23.1821 60.2166 22.416 60.516 21.4947L62.3713 15.7844C62.5052 15.3724 62.3585 14.9211 62.0081 14.6664L57.1506 11.1373C56.3669 10.5679 56.7697 9.32827 57.7384 9.32827H63.7425C64.1757 9.32827 64.5597 9.04931 64.6936 8.63729L66.5489 2.92705Z" fill="#E68B1D"/>
<path d="M93.5489 2.92705C93.8483 2.00574 95.1517 2.00574 95.4511 2.92705L97.3064 8.63729C97.4403 9.04931 97.8243 9.32827 98.2575 9.32827H104.262C105.23 9.32827 105.633 10.5679 104.849 11.1373L99.9919 14.6664C99.6415 14.9211 99.4948 15.3724 99.6287 15.7844L101.484 21.4947C101.783 22.416 100.729 23.1821 99.9452 22.6127L95.0878 19.0836C94.7373 18.8289 94.2627 18.8289 93.9122 19.0836L89.0548 22.6127C88.2711 23.1821 87.2166 22.416 87.516 21.4947L89.3713 15.7844C89.5052 15.3724 89.3585 14.9211 89.0081 14.6664L84.1506 11.1373C83.3669 10.5679 83.7697 9.32827 84.7384 9.32827H90.7425C91.1757 9.32827 91.5597 9.04931 91.6936 8.63729L93.5489 2.92705Z" fill="#E68B1D"/>
<path d="M120.549 2.92705C120.848 2.00574 122.152 2.00574 122.451 2.92705L124.306 8.63729C124.44 9.04931 124.824 9.32827 125.257 9.32827H131.262C132.23 9.32827 132.633 10.5679 131.849 11.1373L126.992 14.6664C126.641 14.9211 126.495 15.3724 126.629 15.7844L128.484 21.4947C128.783 22.416 127.729 23.1821 126.945 22.6127L122.088 19.0836C121.737 18.8289 121.263 18.8289 120.912 19.0836L116.055 22.6127C115.271 23.1821 114.217 22.416 114.516 21.4947L116.371 15.7844C116.505 15.3724 116.359 14.9211 116.008 14.6664L111.151 11.1373C110.367 10.5679 110.77 9.32827 111.738 9.32827H117.743C118.176 9.32827 118.56 9.04931 118.694 8.63729L120.549 2.92705Z" fill="#DEDEDE"/>
</svg>';

        $raitin_stars_4_5 = '<svg width="135" height="27" viewBox="0 0 135 27" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12.5489 2.92705C12.8483 2.00574 14.1517 2.00574 14.4511 2.92705L16.3064 8.63729C16.4403 9.04931 16.8243 9.32827 17.2575 9.32827H23.2616C24.2303 9.32827 24.6331 10.5679 23.8494 11.1373L18.9919 14.6664C18.6415 14.9211 18.4948 15.3724 18.6287 15.7844L20.484 21.4947C20.7834 22.416 19.7289 23.1821 18.9452 22.6127L14.0878 19.0836C13.7373 18.8289 13.2627 18.8289 12.9122 19.0836L8.0548 22.6127C7.27108 23.1821 6.2166 22.416 6.51596 21.4947L8.37132 15.7844C8.5052 15.3724 8.35854 14.9211 8.00805 14.6664L3.15064 11.1373C2.36692 10.5679 2.7697 9.32827 3.73842 9.32827H9.74252C10.1757 9.32827 10.5597 9.04931 10.6936 8.63729L12.5489 2.92705Z" fill="#E68B1D"/>
<path d="M39.5489 2.92705C39.8483 2.00574 41.1517 2.00574 41.4511 2.92705L43.3064 8.63729C43.4403 9.04931 43.8243 9.32827 44.2575 9.32827H50.2616C51.2303 9.32827 51.6331 10.5679 50.8494 11.1373L45.9919 14.6664C45.6415 14.9211 45.4948 15.3724 45.6287 15.7844L47.484 21.4947C47.7834 22.416 46.7289 23.1821 45.9452 22.6127L41.0878 19.0836C40.7373 18.8289 40.2627 18.8289 39.9122 19.0836L35.0548 22.6127C34.2711 23.1821 33.2166 22.416 33.516 21.4947L35.3713 15.7844C35.5052 15.3724 35.3585 14.9211 35.0081 14.6664L30.1506 11.1373C29.3669 10.5679 29.7697 9.32827 30.7384 9.32827H36.7425C37.1757 9.32827 37.5597 9.04931 37.6936 8.63729L39.5489 2.92705Z" fill="#E68B1D"/>
<path d="M66.5489 2.92705C66.8483 2.00574 68.1517 2.00574 68.4511 2.92705L70.3064 8.63729C70.4403 9.04931 70.8243 9.32827 71.2575 9.32827H77.2616C78.2303 9.32827 78.6331 10.5679 77.8494 11.1373L72.9919 14.6664C72.6415 14.9211 72.4948 15.3724 72.6287 15.7844L74.484 21.4947C74.7834 22.416 73.7289 23.1821 72.9452 22.6127L68.0878 19.0836C67.7373 18.8289 67.2627 18.8289 66.9122 19.0836L62.0548 22.6127C61.2711 23.1821 60.2166 22.416 60.516 21.4947L62.3713 15.7844C62.5052 15.3724 62.3585 14.9211 62.0081 14.6664L57.1506 11.1373C56.3669 10.5679 56.7697 9.32827 57.7384 9.32827H63.7425C64.1757 9.32827 64.5597 9.04931 64.6936 8.63729L66.5489 2.92705Z" fill="#E68B1D"/>
<path d="M93.5489 2.92705C93.8483 2.00574 95.1517 2.00574 95.4511 2.92705L97.3064 8.63729C97.4403 9.04931 97.8243 9.32827 98.2575 9.32827H104.262C105.23 9.32827 105.633 10.5679 104.849 11.1373L99.9919 14.6664C99.6415 14.9211 99.4948 15.3724 99.6287 15.7844L101.484 21.4947C101.783 22.416 100.729 23.1821 99.9452 22.6127L95.0878 19.0836C94.7373 18.8289 94.2627 18.8289 93.9122 19.0836L89.0548 22.6127C88.2711 23.1821 87.2166 22.416 87.516 21.4947L89.3713 15.7844C89.5052 15.3724 89.3585 14.9211 89.0081 14.6664L84.1506 11.1373C83.3669 10.5679 83.7697 9.32827 84.7384 9.32827H90.7425C91.1757 9.32827 91.5597 9.04931 91.6936 8.63729L93.5489 2.92705Z" fill="#E68B1D"/>
<path d="M120.549 2.92705C120.848 2.00574 122.152 2.00574 122.451 2.92705L124.306 8.63729C124.44 9.04931 124.824 9.32827 125.257 9.32827H131.262C132.23 9.32827 132.633 10.5679 131.849 11.1373L126.992 14.6664C126.641 14.9211 126.495 15.3724 126.629 15.7844L128.484 21.4947C128.783 22.416 127.729 23.1821 126.945 22.6127L122.088 19.0836C121.737 18.8289 121.263 18.8289 120.912 19.0836L116.055 22.6127C115.271 23.1821 114.217 22.416 114.516 21.4947L116.371 15.7844C116.505 15.3724 116.359 14.9211 116.008 14.6664L111.151 11.1373C110.367 10.5679 110.77 9.32827 111.738 9.32827H117.743C118.176 9.32827 118.56 9.04931 118.694 8.63729L120.549 2.92705Z" fill="#DEDEDE"/>
<path fill-rule="evenodd" clip-rule="evenodd" d="M121.499 2.23604C121.099 2.23619 120.698 2.46652 120.549 2.92703L118.693 8.63726C118.56 9.04928 118.176 9.32824 117.742 9.32824H111.738C110.77 9.32824 110.367 10.5679 111.15 11.1373L116.008 14.6664C116.358 14.921 116.505 15.3724 116.371 15.7844L114.516 21.4947C114.216 22.416 115.271 23.1821 116.055 22.6127L120.912 19.0836C121.087 18.9563 121.293 18.8927 121.499 18.8926V2.23604Z" fill="#E68B1D"/>
</svg>';

        $raitin_stars_5 = '<svg width="135" height="27" viewBox="0 0 135 27" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M12.5489 2.92705C12.8483 2.00574 14.1517 2.00574 14.4511 2.92705L16.3064 8.63729C16.4403 9.04931 16.8243 9.32827 17.2575 9.32827H23.2616C24.2303 9.32827 24.6331 10.5679 23.8494 11.1373L18.9919 14.6664C18.6415 14.9211 18.4948 15.3724 18.6287 15.7844L20.484 21.4947C20.7834 22.416 19.7289 23.1821 18.9452 22.6127L14.0878 19.0836C13.7373 18.8289 13.2627 18.8289 12.9122 19.0836L8.0548 22.6127C7.27108 23.1821 6.2166 22.416 6.51596 21.4947L8.37132 15.7844C8.5052 15.3724 8.35854 14.9211 8.00805 14.6664L3.15064 11.1373C2.36692 10.5679 2.7697 9.32827 3.73842 9.32827H9.74252C10.1757 9.32827 10.5597 9.04931 10.6936 8.63729L12.5489 2.92705Z" fill="#E68B1D"/>
<path d="M39.5489 2.92705C39.8483 2.00574 41.1517 2.00574 41.4511 2.92705L43.3064 8.63729C43.4403 9.04931 43.8243 9.32827 44.2575 9.32827H50.2616C51.2303 9.32827 51.6331 10.5679 50.8494 11.1373L45.9919 14.6664C45.6415 14.9211 45.4948 15.3724 45.6287 15.7844L47.484 21.4947C47.7834 22.416 46.7289 23.1821 45.9452 22.6127L41.0878 19.0836C40.7373 18.8289 40.2627 18.8289 39.9122 19.0836L35.0548 22.6127C34.2711 23.1821 33.2166 22.416 33.516 21.4947L35.3713 15.7844C35.5052 15.3724 35.3585 14.9211 35.0081 14.6664L30.1506 11.1373C29.3669 10.5679 29.7697 9.32827 30.7384 9.32827H36.7425C37.1757 9.32827 37.5597 9.04931 37.6936 8.63729L39.5489 2.92705Z" fill="#E68B1D"/>
<path d="M66.5489 2.92705C66.8483 2.00574 68.1517 2.00574 68.4511 2.92705L70.3064 8.63729C70.4403 9.04931 70.8243 9.32827 71.2575 9.32827H77.2616C78.2303 9.32827 78.6331 10.5679 77.8494 11.1373L72.9919 14.6664C72.6415 14.9211 72.4948 15.3724 72.6287 15.7844L74.484 21.4947C74.7834 22.416 73.7289 23.1821 72.9452 22.6127L68.0878 19.0836C67.7373 18.8289 67.2627 18.8289 66.9122 19.0836L62.0548 22.6127C61.2711 23.1821 60.2166 22.416 60.516 21.4947L62.3713 15.7844C62.5052 15.3724 62.3585 14.9211 62.0081 14.6664L57.1506 11.1373C56.3669 10.5679 56.7697 9.32827 57.7384 9.32827H63.7425C64.1757 9.32827 64.5597 9.04931 64.6936 8.63729L66.5489 2.92705Z" fill="#E68B1D"/>
<path d="M93.5489 2.92705C93.8483 2.00574 95.1517 2.00574 95.4511 2.92705L97.3064 8.63729C97.4403 9.04931 97.8243 9.32827 98.2575 9.32827H104.262C105.23 9.32827 105.633 10.5679 104.849 11.1373L99.9919 14.6664C99.6415 14.9211 99.4948 15.3724 99.6287 15.7844L101.484 21.4947C101.783 22.416 100.729 23.1821 99.9452 22.6127L95.0878 19.0836C94.7373 18.8289 94.2627 18.8289 93.9122 19.0836L89.0548 22.6127C88.2711 23.1821 87.2166 22.416 87.516 21.4947L89.3713 15.7844C89.5052 15.3724 89.3585 14.9211 89.0081 14.6664L84.1506 11.1373C83.3669 10.5679 83.7697 9.32827 84.7384 9.32827H90.7425C91.1757 9.32827 91.5597 9.04931 91.6936 8.63729L93.5489 2.92705Z" fill="#E68B1D"/>
<path d="M120.549 2.92705C120.848 2.00574 122.152 2.00574 122.451 2.92705L124.306 8.63729C124.44 9.04931 124.824 9.32827 125.257 9.32827H131.262C132.23 9.32827 132.633 10.5679 131.849 11.1373L126.992 14.6664C126.641 14.9211 126.495 15.3724 126.629 15.7844L128.484 21.4947C128.783 22.416 127.729 23.1821 126.945 22.6127L122.088 19.0836C121.737 18.8289 121.263 18.8289 120.912 19.0836L116.055 22.6127C115.271 23.1821 114.217 22.416 114.516 21.4947L116.371 15.7844C116.505 15.3724 116.359 14.9211 116.008 14.6664L111.151 11.1373C110.367 10.5679 110.77 9.32827 111.738 9.32827H117.743C118.176 9.32827 118.56 9.04931 118.694 8.63729L120.549 2.92705Z" fill="#E68B1D"/>
</svg>';


        return [
            [
                'author_pic'    => $base_url . 'ava-1.jpg',
                'author_pic@2x' => $base_url . 'ava-1@2x.jpg',
                'author_name'   => 'Bibendum Pretium',
                'title'         => 'Sed in ante ut leo congue posuere at sit amet ligula',
                'text'          => ['Vestibulum dictum ultrices elit a luctus. Sed in ante ut leo congue posuere at sit amet ligula. Pellentesque eget augue nec nisl sodales blandit sed et sem. Aenean quis finibus arcu, in hendrerit purus.'],
                'raiting_image' => $raitin_stars_5,
            ],
            [
                'author_pic'    => $base_url . 'ava-2.jpg',
                'author_pic@2x' => $base_url . 'ava-2@2x.jpg',
                'author_name'   => 'Aenean Quis',
                'title'         => 'Morbi convallis convallis diam sit amet lacinia',
                'text'          => ['Auctor purus luctus enim egestas, ac scelerisque ante pulvinar. Donec ut rhoncus.', 'Duis felis ante, varius in neque eu, tempor suscipit sem. Maecenas ullamcorper gravida sem sit amet cursus.'],
                'raiting_image' => $raitin_stars_5,
            ],
            [
                'author_pic'    => $base_url . 'ava-3.jpg',
                'author_pic@2x' => $base_url . 'ava-3@2x.jpg',
                'author_name'   => 'Morbi Tristique',
                'title'         => 'Viverra arcu dignissim vehicula',
                'text'          => ['Justo augue, finibus id sollicitudin et, rutrum eget metus. Suspendisse ut mauris eu massa pulvinar sollicitudin vel sed enim. Pellentesque viverra arcu et dignissim vehicula. Donec a velit ac dolor dapibus pellentesque sit amet at erat.'],
                'raiting_image' => $raitin_stars_4_5,
            ],
            [
                'author_pic'    => $base_url . 'ava-4.jpg',
                'author_pic@2x' => $base_url . 'ava-4@2x.jpg',
                'author_name'   => 'Congue Posuere',
                'title'         => 'In vulputate lobortis ante',
                'text'          => ['Praesent auctor purus luctus enim egestas, ac scelerisque ante pulvinar. Donec ut rhoncus ex. Suspendisse ac rhoncus nisl, eu tempor urna. Curabitur vel bibendum lorem. Morbi convallis convallis diam sit amet lacinia. Aliquam in elementum tellus.'],
                'raiting_image' => $raitin_stars_5,
            ],
            [
                'author_pic'    => $base_url . 'ava-5.jpg',
                'author_pic@2x' => $base_url . 'ava-5@2x.jpg',
                'author_name'   => 'Bibendum Pretium',
                'title'         => 'Pellentesque interdum vulputate elementum',
                'text'          => ['Nunc tempor interdum ex, sed cursus nunc egestas aliquet. Pellentesque interdum vulputate elementum. Donec erat diam, pharetra nec enim ut, bibendum pretium tellus.', 'Suspendisse ut mauris eu massa pulvinar sollicitudin vel sed enim.'],
                'raiting_image' => $raitin_stars_4,
            ],
            [
                'author_pic'    => $base_url . 'ava-6.jpg',
                'author_pic@2x' => $base_url . 'ava-6@2x.jpg',
                'author_name'   => 'Morbi Tristique',
                'title'         => 'Suspendisse ac rhoncus nisl',
                'text'          => ['Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Praesent auctor purus luctus enim egestas, ac scelerisque ante pulvinar. Donec ut rhoncus ex. Suspendisse ac rhoncus nisl, eu tempor urna.'],
                'raiting_image' => $raitin_stars_5,
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
    private function createColumn(string $column_classes, array $block_props, siteBlockData $content): siteBlockData {
        $column = (new siteColumnBlockType())->getEmptyBlockData();

        $column->data = array_merge($this->column_base_props, [
            'elements'    => $this->column_elements,
            'column'      => $column_classes,
            'block_props' => $block_props,
        ]);

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

        $vseq->addChild($this->getReviewRaitingImage($card_data['raiting_image']));
        $vseq->addChild($this->getReviewTitle($card_data['title']));
        foreach ($card_data['text'] as $text) {
            $vseq->addChild($this->getReviewText($text));
        }
        $vseq->addChild($this->getReviewAuthor($card_data['author_pic@2x'], $card_data['author_name']));

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
            'st-6-tb st-12-mb st-4 st-4-lp',
            $block_props,
            $vseq
        );
    }

    /**
     * Получает блок заголовка
     *
     * @return siteBlockData
     */
    private function getTitle(): siteBlockData {
        return $this->createHeadingBlock(
            'In vulputate',
            'h1',
            array_merge($this->heading_base_props, $this->text_base_props, [
                'font-size'     => ['name' => 'Size #3', 'value' => 't-3', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-8',
                'max-width'     => 'fx-9',
            ])
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
            array_merge($this->text_base_props, [
                'margin-left'   => 'm-l-a m-l-0-mb',
                'margin-bottom' => 'm-b-8',
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
            ])
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
     * Получает блок изображения рейтинга
     *
     * @param string $raiting_image
     * @return siteBlockData
     */
    private function getReviewRaitingImage(string $raiting_image): siteBlockData {
        $imageBlock = (new siteImageBlockType())->getEmptyBlockData();

        $imageBlock->data = [
            'image'       => [
                'type'     => 'svg',
                'svg_html' => $raiting_image,
            ],
            'block_props' => [
                'margin-bottom' => 'm-b-14',
            ],
        ];

        return $imageBlock;
    }

    /**
     * Получает блок заголовка отзыва
     *
     * @param string $title
     * @return siteBlockData
     */
    private function getReviewTitle(string $title): siteBlockData {
        return $this->createHeadingBlock(
            '“' . $title . '”',
            'h2',
            array_merge($this->heading_base_props, $this->text_base_props, [
                'font-size'     => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-10',
            ])
        );
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
            'block_props' => array_merge($this->text_base_props, [
                'font-header'   => 't-rgl',
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-16',
            ]),
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
                'margin-bottom' => 'm-b-8',
                'border-radius' => 'b-r-r',
                'margin-right'  => 'm-r-12',
                'picture-size'  => 'i-l',
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
            '<span class="tx-bw-4">' . $name . '</span>',
            'h1',
            [
                'font-size'     => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library'],
                'margin-bottom' => 'm-b-8',
                'margin-top'    => 'm-t-4',
                'font-header'   => 't-rgl',
                'align'         => 't-l',
            ]
        );
    }
}
