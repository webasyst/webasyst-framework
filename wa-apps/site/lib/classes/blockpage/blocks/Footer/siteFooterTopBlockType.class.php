<?php
/**
 * Represents one or more cards of content.
 * Uses siteCardBlockType to store settings of individual cards.
 */
class siteFooterTopBlockType extends siteBlockType
{
    public $elements = [
        'main' => 'site-block-footer',
        'wrapper' => 'site-block-footer-wrapper',
    ];

    public function __construct(array $options=[])
    {
        if (!isset($options['columns']) || !wa_is_int($options['columns'])) {
            $options['columns'] = ifset(ref(explode('.', ifset($options, 'type', ''), 3)), 2, 2);
        }
        $options['type'] = 'site.FooterTop.'.$options['columns'];
        parent::__construct($options);
    }

    public function getExampleBlockData()
    {
        //construct columns START

        $column_left = (new siteFooterColumnBlockType())->getExampleBlockData();
        $column_center = (new siteFooterColumnBlockType())->getExampleBlockData();
        $column_right = (new siteFooterColumnBlockType())->getExampleBlockData();
        $column_logo = (new siteFooterColumnBlockType())->getExampleBlockData();
        $column_logo->data['inline_props'] = ['site-block-column-wrapper' => ['min-height' => ['name' => 'Fill parent', 'value' => '100%', 'type' => 'parent']]];
        
        /* First column */
        $paragraph = (new siteParagraphBlockType())->getExampleBlockData();
        $paragraph->data['html'] = '<font color="" class="tx-bw-5">© 2025 Emporium Modernum</font>';
        $paragraph->data['block_props'] = ['font-header' => "t-rgl", 'font-size' => ["name" => "Size #7", "value" => "t-7", "unit" => "px", "type" => "library"], 'margin-top' => "m-t-a", 'margin-bottom' => "m-b-0"];
    
        $images_data = [
            [
                'block_props' => ['margin-bottom' => "m-b-12", 'margin-right' => "m-r-12", 'picture-size' => "i-l"],
                'link_props' => ['data-value' => "external-link", 'href' => "https://faq.whatsapp.com/5913398998672934"],
                'image' => ['type' => 'svg', 'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto" viewBox="0 0 33 32" fill="var(--bw-5)"><g clip-path="url(#clip0_10717_116298)"><path d="M16.9131 0.492188C20.8704 0.515741 24.6661 2.06705 27.5068 4.82227C30.3476 7.57755 32.0137 11.3244 32.1582 15.2793C32.2333 17.3038 31.9066 19.323 31.1973 21.2207C30.4879 23.1185 29.4101 24.8571 28.0254 26.3359C26.6405 27.8149 24.9761 29.0055 23.1289 29.8379C21.2819 30.6702 19.2879 31.1277 17.2627 31.1855H16.8242C14.5219 31.1862 12.2484 30.6693 10.1729 29.6729L2.13965 31.46H2.11719C2.10047 31.4599 2.08357 31.4562 2.06836 31.4492C2.05308 31.4422 2.03937 31.4316 2.02832 31.4189C2.01739 31.4063 2.00873 31.3919 2.00391 31.376C1.99906 31.3599 1.99781 31.3428 2 31.3262L3.35742 23.2041C2.07929 20.8656 1.43132 18.2349 1.47852 15.5703C1.52574 12.9058 2.26674 10.2995 3.62695 8.00781C4.98713 5.7162 6.92004 3.81771 9.23633 2.5C11.5528 1.18229 14.1728 0.490219 16.8379 0.492188H16.9131ZM10.8066 7.7002C10.6519 7.71881 10.5003 7.7605 10.3574 7.82422C10.167 7.90918 9.99589 8.03217 9.85352 8.18457C9.45116 8.59719 8.32581 9.5904 8.26074 11.6758C8.19581 13.7607 9.65239 15.8235 9.85645 16.1133C10.0602 16.4026 12.6426 20.9076 16.8955 22.7344C19.395 23.8112 20.4907 23.9961 21.2012 23.9961C21.494 23.9961 21.7152 23.9649 21.9463 23.9512C22.7256 23.903 24.4838 23.0026 24.8672 22.0234C25.2506 21.0442 25.2759 20.1878 25.1748 20.0166C25.0737 19.8456 24.7963 19.7219 24.3789 19.5029C23.9608 19.2836 21.9116 18.1882 21.5264 18.0342C21.3836 17.9677 21.2294 17.9276 21.0723 17.916C20.9701 17.9214 20.8711 17.9524 20.7832 18.0049C20.6952 18.0573 20.6208 18.1303 20.5674 18.2178C20.225 18.6441 19.4398 19.5694 19.1758 19.8369C19.1183 19.9031 19.0472 19.9569 18.9678 19.9941C18.8883 20.0312 18.8016 20.0511 18.7139 20.0527C18.5519 20.0456 18.3927 20.0029 18.249 19.9277C17.0078 19.4005 15.8757 18.6456 14.9121 17.7021C14.0118 16.8148 13.2479 15.7989 12.6455 14.6875C12.4127 14.256 12.6461 14.0331 12.8584 13.8311C13.0706 13.629 13.2976 13.3498 13.5166 13.1084C13.6964 12.9023 13.8464 12.6718 13.9619 12.4238C14.0216 12.3087 14.052 12.1804 14.0498 12.0508C14.0478 11.921 14.0137 11.7939 13.9502 11.6807C13.8492 11.4648 13.0947 9.34441 12.7402 8.49316C12.4528 7.76587 12.1101 7.741 11.8105 7.71875C11.5642 7.70164 11.2814 7.69313 10.999 7.68457H10.9629L10.8066 7.7002Z"></path></g><defs><clipPath id="clip0_10717_116298"><rect width="32" height="32" transform="translate(0.866699)"></rect></clipPath></defs></svg>', 'fill' => 'removed', 'color' => ['name' => 'Palette', 'type' => 'palette', 'value' => 'tx-bw-5']],
            ],
            [
                'block_props' => ['margin-bottom' => "m-b-12", 'margin-right' => "m-r-12", 'picture-size' => "i-l"],
                'link_props' => ['data-value' => "external-link", 'href' => "https://t.me/"],
                'image' => ['type' => 'svg', 'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto " viewBox="0 0 33 32" fill="var(--bw-5)"><g clip-path="url(#clip0_10717_116287)"><path d="M16.6499 0C25.4865 0 32.6499 7.16344 32.6499 16C32.6499 24.8366 25.4865 32 16.6499 32C7.81335 32 0.649902 24.8366 0.649902 16C0.649902 7.16344 7.81335 0 16.6499 0ZM23.1919 9.63184C22.5901 9.64249 21.6661 9.96352 17.2231 11.8115C15.6668 12.4589 12.557 13.7991 7.89307 15.8311C7.13574 16.1322 6.73883 16.4268 6.70264 16.7148C6.6333 17.2679 7.42934 17.44 8.43115 17.7656C9.24801 18.0312 10.3471 18.3421 10.9185 18.3545C11.4366 18.3657 12.0153 18.1523 12.6538 17.7139C17.0118 14.7721 19.2616 13.285 19.4028 13.2529C19.5025 13.2303 19.6404 13.202 19.7339 13.2852C19.8271 13.3681 19.818 13.5246 19.8081 13.5674C19.7289 13.9051 15.6367 17.6282 15.3999 17.874C14.4996 18.8091 13.4756 19.3809 15.0552 20.4219C16.422 21.3226 17.2175 21.8973 18.6255 22.8203C19.5254 23.4102 20.2315 24.1099 21.1606 24.0244C21.588 23.9849 22.0292 23.5831 22.2534 22.3848C22.7835 19.5511 23.8261 13.4108 24.0669 10.8809C24.0879 10.6592 24.0609 10.3754 24.0396 10.251C24.0182 10.1265 23.9736 9.94958 23.812 9.81836C23.6203 9.66281 23.3243 9.6295 23.1919 9.63184Z"></path></g><defs><linearGradient id="paint0_linear_10717_116287" x1="1600.65" y1="0" x2="1600.65" y2="3176.27" gradientUnits="userSpaceOnUse"><stop stop-color="#2AABEE"></stop><stop offset="1" stop-color="#229ED9"></stop></linearGradient><clipPath id="clip0_10717_116287"><rect width="32" height="32" transform="translate(0.649902)"></rect></clipPath></defs></svg>', 'fill' => 'removed', 'color' => ['name' => 'Palette', 'type' => 'palette', 'value' => 'tx-bw-5']],
            ],
            [
                'block_props' => ['margin-bottom' => "m-b-12", 'margin-right' => "m-r-12", 'picture-size' => "i-l"],
                'link_props' => ['data-value' => "external-link", 'href' => "https://www.instagram.com/"],
                'image' => ['type' => 'svg', 'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto" viewBox="0 0 32 32" fill="var(--bw-5)"><path d="M15.972 1.6022C19.8827 1.59471 20.3767 1.61213 21.9095 1.67837C23.4417 1.74463 24.4883 1.98607 25.4046 2.34048C26.3529 2.70563 27.1572 3.19671 27.9583 3.99576C28.7595 4.79496 29.2542 5.59695 29.6243 6.54361C29.9833 7.45896 30.2272 8.50166 30.3001 10.0368C30.373 11.5724 30.3903 12.0634 30.3977 15.9733C30.4052 19.8828 30.3897 20.3738 30.3226 21.9108C30.256 23.4423 30.0143 24.4901 29.6595 25.4059C29.2934 26.3543 28.8021 27.158 28.0042 27.9596C27.2062 28.7613 26.4039 29.2553 25.4573 29.6256C24.5421 29.9833 23.501 30.2286 21.9651 30.3014C20.4295 30.3743 19.9367 30.3916 16.0276 30.3991C12.119 30.4066 11.6254 30.391 10.0931 30.3239C8.56041 30.2568 7.51277 30.0147 6.59696 29.6608C5.64661 29.2939 4.84375 28.8041 4.04228 28.0055C3.24077 27.2069 2.74674 26.4043 2.37724 25.4577C2.01786 24.5428 1.77366 23.4971 1.70048 21.9655C1.62618 20.4293 1.60933 19.9384 1.60185 16.028C1.59436 12.1177 1.60936 11.6245 1.67704 10.0915C1.74474 8.55885 1.98472 7.51191 2.33915 6.59536C2.70549 5.64727 3.19707 4.84385 3.9954 4.04263C4.79359 3.24158 5.59584 2.74649 6.54228 2.37759C7.45687 2.01821 8.50355 1.77413 10.0354 1.70181C11.5713 1.62693 12.0614 1.60969 15.972 1.6022ZM15.9856 8.60611C11.9022 8.61432 8.59895 11.9321 8.60673 16.0153C8.61517 20.0987 11.9313 23.4019 16.014 23.3942C20.0967 23.3863 23.4024 20.0696 23.3948 15.986C23.3869 11.9023 20.069 8.5982 15.9856 8.60611ZM14.1546 11.569C15.0308 11.204 15.9957 11.1074 16.927 11.2907C17.8584 11.4741 18.7154 11.9294 19.388 12.5993C20.0603 13.2692 20.5193 14.1238 20.7063 15.0543C20.8932 15.9848 20.8001 16.9502 20.4388 17.8278C20.0772 18.7055 19.4633 19.457 18.6751 19.986C17.8869 20.5149 16.9592 20.7984 16.0101 20.8004C15.3799 20.8018 14.7549 20.679 14.1722 20.4391C13.5894 20.1991 13.0591 19.8457 12.6126 19.401C12.1662 18.9564 11.811 18.428 11.5686 17.8463C11.3263 17.2645 11.2017 16.6397 11.2005 16.0094C11.1986 15.0602 11.4784 14.1318 12.0042 13.3415C12.5301 12.5511 13.2782 11.934 14.1546 11.569ZM23.6683 6.57388C23.2101 6.57481 22.7709 6.75714 22.4476 7.08169C22.1242 7.40627 21.9429 7.84619 21.9436 8.30435C21.9443 8.646 22.0463 8.98056 22.2366 9.26431C22.4269 9.54779 22.6973 9.76891 23.013 9.89908C23.3289 10.0292 23.677 10.063 24.012 9.99576C24.347 9.92845 24.6546 9.76314 24.8958 9.52115C25.137 9.2791 25.3014 8.97062 25.3675 8.6354C25.4335 8.30029 25.3981 7.9527 25.2669 7.63736C25.1356 7.32205 24.9135 7.05226 24.6292 6.86294C24.3447 6.67366 24.0099 6.57325 23.6683 6.57388Z"></path></svg>', 'fill' => 'removed', 'color' => ['name' => 'Palette', 'type' => 'palette', 'value' => 'tx-bw-5']],
            ],
            [
                'block_props' => ['margin-bottom' => "m-b-12", 'margin-right' => "m-r-12", 'margin-top' => "m-t-6", 'picture-size' => "i-l"],
                'link_props' => ['data-value' => "external-link", 'href' => "https://www.youtube.com/"],
                'image' => ['type' => 'svg', 'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="33" height="auto" viewBox="0 0 33 23" fill="var(--bw-5)"><g clip-path="url(#clip0_10717_116293)"><path d="M16.6162 0.000976563C16.6638 0.000980376 26.6461 0.00236964 29.127 0.673828C30.527 1.03796 31.5797 2.09758 31.9492 3.46582C32.6325 5.89341 32.6328 11.0137 32.6328 11.0137C32.6328 11.0574 32.6296 16.177 31.9492 18.6162C31.5796 19.9513 30.527 21.033 29.127 21.4082C26.6461 22.0686 16.6638 22.0703 16.6162 22.0703C16.6162 22.0703 6.61426 22.0702 4.13867 21.4082C2.76105 21.033 1.686 19.9513 1.29395 18.6162C0.635938 16.177 0.632825 11.0574 0.632812 11.0137C0.632812 11.0137 0.633112 5.89341 1.29395 3.46582C1.68598 2.09758 2.76103 1.03796 4.13867 0.673828C6.61423 0.000762989 16.6162 0.000976563 16.6162 0.000976563ZM13.3223 15.7246L21.5977 11.0352L13.3223 6.3457V15.7246Z"></path></g><defs><clipPath id="clip0_10717_116293"><rect width="32" height="22.069" transform="translate(0.632812)"></rect></clipPath></defs></svg>', 'fill' => 'removed', 'color' => ['name' => 'Palette', 'type' => 'palette', 'value' => 'tx-bw-5']],
            ],
        ];

        // Создаём ряд с изображениями
        $row = (new siteRowBlockType())->getExampleBlockData();
        $row->data['block_props'] = [
            "padding-bottom" => "p-b-10",
            "padding-top" => "p-t-10",
        ];
        $row->data['wrapper_props'] = [
            "justify-align" => "j-s",
        ];
        $rhseq = reset($row->children['']);
        $default_image_url = wa()->getAppStaticUrl('site') . 'img/image.svg';
        foreach ($images_data as $image_data) {
            $image = (new siteImageBlockType())->getExampleBlockData();
            $image_data['indestructible'] = false;
            $image_data['default_image_url'] = $default_image_url;
            $image->data = $image_data;
            $rhseq->addChild($image);
        }

        $hseq_column = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq_column->data['is_complex'] = 'no_complex';
        $hseq_column->addChild($row);
        $hseq_column->addChild($paragraph);
        $column_logo->addChild($hseq_column);

        /* Second column */
        $menu_item_bold = (new siteHeadingBlockType())->getExampleBlockData();
        $menu_item_bold->data = ['html' => '<font color="" class="tx-wh">De Societate</font>', 'tag' => 'h4', 'block_props' => ["font-header" => "t-hdn", 'margin-top' => 'm-t-0', 'margin-bottom' => 'm-b-10', 'font-size' => ['name' => "Size #6", 'type' => "library", 'value' => 't-6', 'unit' => 'px']]];
        $menu_item = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item->data = ['html' => 'De nobis', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12', 'margin-top' => 'm-t-4', 'margin-bottom' => 'm-b-4', 'font-header' => 't-hdn', 'nobutton' => 'nobutton']];
        $menu_item2 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item2->data = ['html' => 'Historia nostra', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12', 'margin-top' => 'm-t-4', 'margin-bottom' => 'm-b-4', 'font-header' => 't-hdn', 'nobutton' => 'nobutton']];
        $menu_item3 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item3->data = ['html' => 'Cursus honorum', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12', 'margin-top' => 'm-t-4', 'margin-bottom' => 'm-b-4', 'font-header' => 't-hdn', 'nobutton' => 'nobutton']];
        
        $hseq_column = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq_column->addChild($menu_item_bold);
        $hseq_column->addChild($menu_item);
        $hseq_column->addChild($menu_item2);
        $hseq_column->addChild($menu_item3);
        $column_left->addChild($hseq_column);

        /* 3 column */
        $menu_item_bold = (new siteHeadingBlockType())->getExampleBlockData();
        $menu_item_bold->data = ['html' => '<font color="" class="tx-wh">Auxilium</font>', 'tag' => 'h4', 'block_props' => ["font-header" => "t-hdn", 'margin-top' => 'm-t-0', 'margin-bottom' => 'm-b-10', 'font-size' => ['name' => "Size #6", 'type' => "library", 'value' => 't-6', 'unit' => 'px']]];
        $menu_item = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item->data = ['html' => 'De nobis', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12', 'margin-top' => 'm-t-4', 'margin-bottom' => 'm-b-4', 'font-header' => 't-hdn', 'nobutton' => 'nobutton']];
        $menu_item2 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item2->data = ['html' => 'Restitutio bonorum', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12', 'margin-top' => 'm-t-4', 'margin-bottom' => 'm-b-4', 'font-header' => 't-hdn', 'nobutton' => 'nobutton']];
        $menu_item3 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item3->data = ['html' => 'Modi solutionis', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12', 'margin-top' => 'm-t-4', 'margin-bottom' => 'm-b-4', 'font-header' => 't-hdn', 'nobutton' => 'nobutton']];
        
        $hseq_column = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq_column->data['is_complex'] = 'no_complex';
        $hseq_column->addChild($menu_item_bold);
        $hseq_column->addChild($menu_item);
        $hseq_column->addChild($menu_item2);
        $hseq_column->addChild($menu_item3);
        $column_center->addChild($hseq_column);
        /* 4 column */
        $menu_item_bold = (new siteHeadingBlockType())->getExampleBlockData();
        $menu_item_bold->data = ['html' => '<font color="" class="tx-wh">Commercium</font>', 'tag' => 'h4', 'block_props' => ["font-header" => "t-hdn", 'margin-top' => 'm-t-0', 'margin-bottom' => 'm-b-10', 'font-size' => ['name' => "Size #6", 'type' => "library", 'value' => 't-6', 'unit' => 'px']]];
        $menu_item = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item->data = ['html' => 'Responsio clientum', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10', 'button-size' => 'inp-s p-l-12 p-r-12', 'margin-top' => 'm-t-4', 'margin-bottom' => 'm-b-4', 'font-header' => 't-hdn', 'nobutton' => 'nobutton']];
        $menu_item2 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item2->data = ['html' => 'Auxilium technicum', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12', 'margin-top' => 'm-t-4', 'margin-bottom' => 'm-b-4', 'font-header' => 't-hdn', 'nobutton' => 'nobutton']];
        $menu_item3 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item3->data = ['html' => 'Sociis nostris', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12', 'margin-top' => 'm-t-4', 'margin-bottom' => 'm-b-4', 'font-header' => 't-hdn', 'nobutton' => 'nobutton']];
   
        $hseq_column = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq_column->data['is_complex'] = 'no_complex';
        $hseq_column->addChild($menu_item_bold);
        $hseq_column->addChild($menu_item);
        $hseq_column->addChild($menu_item2);
        $hseq_column->addChild($menu_item3);
        $column_right->addChild($hseq_column);
        //construct columns END

        //construct main block
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;

        $column_props = array();
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", 'padding-left' => "p-l-blc", 'padding-right' => "p-r-blc", 'background' => ["name" => "grey shades","value" => "bg-bw-2", "type" => "palette","uuid" => 1, "layers" => [["name" => "grey shades", "value" => "bg-bw-2", "type" => "palette"]]]];
        $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", 'flex-align-vertical' => "x-c", 'max-width' => "cnt"];
        $result = $this->getEmptyBlockData();
        //$columns_arr = array();
        $hseq->addChild($column_left);
        $hseq->addChild($column_center);
        $hseq->addChild($column_right);
        $hseq->addChild($column_logo);
        $result->addChild($hseq, '');
        $result->data = ['block_props' => $column_props, 'wrapper_props' => ['justify-align' => "y-j-cnt"]];
        $result->data['elements'] = $this->elements;
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
            'type_name_original' => _w('Footer top'),
            'sections' => [
                [   'type' => 'MenuToggleGroup',
                    'name' => _w('Footer toggle'),
                ],
                [   'type' => 'ColumnsGroup',
                    'name' => _w('Columns'),
                ],
                [   'type' => 'ColumnsAlignGroup',
                    'name' => _w('Alignment'),
                ],
                [  'type' => 'TabsWrapperGroup',
                    'name' => _w('Tabs'),
                ],
                [   'type' => 'CommonLinkGroup',
                    'name' => _w('Link or action'),
                    'is_hidden' => true,
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
}
