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
        
        /* First column */
        $paragraph = (new siteParagraphBlockType())->getExampleBlockData();
        $paragraph->data['html'] = '<font color="" class="tx-bw-5">© 2025 Vestibulum accumsan</font>';
        $paragraph->data['block_props'] = ['font-header' => "t-rgl", 'font-size' => ["name" => "Size #7", "value" => "t-7", "unit" => "px", "type" => "library"], 'margin-top' => "m-t-13", 'margin-bottom' => "m-b-0", 'align' => "t-l"];
        $logo = (new siteImageBlockType())->getExampleBlockData();
        $logo->data['block_props'] = ["margin-bottom" => "m-b-10", "margin-right" => "m-r-8", 'border-radius' => "b-r-l",'width' => 'i-xxl'];
        $logo->data['indestructible'] = false;
        $logo->data['default_image_url'] = wa()->getAppStaticUrl('site').'img/image.svg';
        $svg_html = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64" height="64" viewBox="0 0 64 64" fill-rule="evenodd" fill="#ffffff">
        <path d="M32,47 C40.2842712,47 47,40.2842712 47,32 C47,23.7157288 40.2842712,17 32,17 C23.7157288,17 17,23.7157288 17,32 C17,40.2842712 23.7157288,47 32,47 Z M32,50 C41.9411255,50 50,41.9411255 50,32 C50,22.0588745 41.9411255,14 32,14 C22.0588745,14 14,22.0588745 14,32 C14,41.9411255 22.0588745,50 32,50 Z"></path>
        <path d="M32,61 C15.9837423,61 3,48.0162577 3,32 C3,15.9837423 15.9837423,3 32,3 C48.0162577,3 61,15.9837423 61,32 C61,48.0162577 48.0162577,61 32,61 Z M32,52 C20.954305,52 12,43.045695 12,32 C12,20.954305 20.954305,12 32,12 C43.045695,12 52,20.954305 52,32 C52,43.045695 43.045695,52 32,52 Z"></path>
        </svg>';
        $logo->data['image'] = ['type' => 'svg', 'svg_html' => $svg_html, 'color' => "ffffff"];
        
        $hseq_column = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq_column->data['is_complex'] = 'no_complex';
        $hseq_column->addChild($logo);
        $hseq_column->addChild($paragraph);
        $column_logo->addChild($hseq_column);

        /* Second column */
        $menu_item_bold = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item_bold->data = ['html' => 'Morbi convallis', 'tag' => 'a', 'block_props' => ["font-header" => "t-hdn", 'width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-m p-l-10 p-r-10']];
        $menu_item = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item->data = ['html' => 'Tempor sapien', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        $menu_item2 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item2->data = ['html' => 'Consequat molestie', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        $menu_item3 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item3->data = ['html' => 'Etiam pulvinar', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        
        $hseq_column = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq_column->addChild($menu_item_bold);
        $hseq_column->addChild($menu_item);
        $hseq_column->addChild($menu_item2);
        $hseq_column->addChild($menu_item3);
        $column_left->addChild($hseq_column);

        /* 3 column */
        $menu_item_bold = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item_bold->data = ['html' => 'Malesuada', 'tag' => 'a', 'block_props' => ["font-header" => "t-hdn", 'width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-m p-l-10 p-r-10']];
        $menu_item = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item->data = ['html' => 'Mauris consequat', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        $menu_item2 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item2->data = ['html' => 'Sed vitae nec', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        $menu_item3 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item3->data = ['html' => '"Duis felis ante', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        
        $hseq_column = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq_column->data['is_complex'] = 'no_complex';
        $hseq_column->addChild($menu_item_bold);
        $hseq_column->addChild($menu_item);
        $hseq_column->addChild($menu_item2);
        $hseq_column->addChild($menu_item3);
        $column_center->addChild($hseq_column);
        /* 4 column */
        $menu_item_bold = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item_bold->data = ['html' => 'Nullam dictum', 'tag' => 'a', 'block_props' => ["font-header" => "t-hdn","font" => "t-7", 'width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-m p-l-10 p-r-10']];
        $menu_item = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item->data = ['html' => 'Aenean gravida', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        $menu_item2 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item2->data = ['html' => 'Curabitur vel bibendum', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
        $menu_item3 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item3->data = ['html' => 'Suspendisse ac rhoncus', 'tag' => 'a', 'block_props' => ['width' => 'cnt-w', 'border-radius' => "b-r-r", 'button-style' => ["name" => "Palette", "value" => "btn-wht-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-10 p-r-10']];
   
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
        //$hseq->data['indestructible'] = true;
        $column_count = ifset($this->options, 'columns', 4);
        $column_props = array();
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", 'background' => ["name" => "grey shades","value" => "bg-bw-2", "type" => "palette","uuid" => 1, "layers" => [["name" => "grey shades", "value" => "bg-bw-2", "type" => "palette", "uuid" => 1]]]];
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
            'type_name' => _w('Footer top'),
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
