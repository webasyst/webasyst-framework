<?php
/**
 * Represents one or more cards of content.
 * Uses siteCardBlockType to store settings of individual cards.
 */
class siteFooterBottomBlockType extends siteBlockType
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
        $options['type'] = 'site.FooterBottom.'.$options['columns'];
        parent::__construct($options);
    }

    public function getExampleBlockData()
    {
        //construct columns START

        $column_left = (new siteFooterColumnBlockType())->getExampleBlockData();
        $column_left->data['column'] = 'st-12-mb st-7-lp st-8 st-12-tb';
        $column_left->data['block_props']['site-block-column'] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0"];
        $column_left->data['block_props']['site-block-column-wrapper'] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0", "border-radius" => "b-r-l", 'flex-align' => "y-l"];
        $column_left->data['wrapper_props'] = ['justify-align' => "j-s"];
        $column_right = (new siteFooterColumnBlockType())->getExampleBlockData();
        $column_right->data['column'] = 'st-12-mb st-5-lp st-4 st-12-tb';
        $column_right->data['block_props']['site-block-column'] = ['margin-left' => 'm-l-a m-l-0-mb m-l-0-tb', 'padding-top' => "p-t-0", 'padding-bottom' => "p-b-0"];
        $column_right->data['block_props']['site-block-column-wrapper'] = ['padding-top' => "p-t-0", 'padding-bottom' => "p-b-0", "border-radius" => "b-r-l", 'flex-align' => "y-c"];
        /* First column */
        $paragraph = (new siteParagraphBlockType())->getExampleBlockData();
        $paragraph->data['html'] = '<font color="" class="tx-bw-4">© 2025 Vestibulum accumsan</font>';
        $paragraph->data['block_props'] = ['font-header' => "t-rgl", 'font-size' => ["name" => "Size #7", "value" => "t-7", "unit" => "px", "type" => "library"], 'margin-top' => "m-t-15", 'margin-bottom' => "m-b-0",  'margin-left' => "m-l-11-mb", 'align' => "t-r"];
        
        $hseq_column = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq_column->data['is_horizontal'] = true;
        $hseq_column->data['is_complex'] = 'no_complex';
        $hseq_column->addChild($paragraph);
        $column_right->addChild($hseq_column);

        /* Second column */
        $logo = (new siteImageBlockType())->getExampleBlockData();
        $logo->data['block_props'] = ["picture-size" => "i-l", "margin-left" => "m-l-11-mb", "margin-bottom" => "m-b-0", "margin-right" => "m-r-10", 'border-radius' => "b-r-l",'width' => 'i-xxl'];
        $logo->data['indestructible'] = false;
        $logo->data['default_image_url'] = wa()->getAppStaticUrl('site').'img/image.svg';
        $svg_html = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64" height="64" viewBox="0 0 64 64" fill-rule="evenodd">
        <path d="M32,47 C40.2842712,47 47,40.2842712 47,32 C47,23.7157288 40.2842712,17 32,17 C23.7157288,17 17,23.7157288 17,32 C17,40.2842712 23.7157288,47 32,47 Z M32,50 C41.9411255,50 50,41.9411255 50,32 C50,22.0588745 41.9411255,14 32,14 C22.0588745,14 14,22.0588745 14,32 C14,41.9411255 22.0588745,50 32,50 Z"></path>
        <path d="M32,61 C15.9837423,61 3,48.0162577 3,32 C3,15.9837423 15.9837423,3 32,3 C48.0162577,3 61,15.9837423 61,32 C61,48.0162577 48.0162577,61 32,61 Z M32,52 C20.954305,52 12,43.045695 12,32 C12,20.954305 20.954305,12 32,12 C43.045695,12 52,20.954305 52,32 C52,43.045695 43.045695,52 32,52 Z"></path>
        </svg>';
        $logo->data['image'] = ['type' => 'svg', 'svg_html' => $svg_html];

        $menu_item = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item->data = ['html' => 'Tempor', 'tag' => 'a', 'block_props' => ["margin-top" => "m-t-8", "margin-bottom" => "m-b-8", 'width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-blc-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12']];
        $menu_item1 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item1->data = ['html' => 'Consequat', 'tag' => 'a', 'block_props' => ["margin-top" => "m-t-8", "margin-bottom" => "m-b-8", 'width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-blc-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12']];
        $menu_item2 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item2->data = ['html' => 'Curabitur', 'tag' => 'a', 'block_props' => ["margin-top" => "m-t-8", "margin-bottom" => "m-b-8", 'width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-blc-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12']];
        $menu_item3 = (new siteMenuItemBlockType())->getExampleBlockData();
        $menu_item3->data = ['html' => 'Commodo', 'tag' => 'a', 'block_props' => ["margin-top" => "m-t-8", "margin-bottom" => "m-b-8", 'width' => 'cnt-w', 'button-style' => ["name" => "Palette", "value" => "btn-blc-lnk", "type" => "palette"], 'button-size' => 'inp-s p-l-12 p-r-12']];
        
        $hseq_column = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq_column->data['is_horizontal'] = true;
        $hseq_column->data['is_complex'] = 'no_complex';
        $hseq_column->addChild($logo);
        $hseq_column->addChild($menu_item);
        $hseq_column->addChild($menu_item1);
        $hseq_column->addChild($menu_item2);
        $hseq_column->addChild($menu_item3);
        $column_left->addChild($hseq_column);

        //construct main block
        $hseq = (new siteVerticalSequenceBlockType())->getEmptyBlockData();
        $hseq->data['is_horizontal'] = true;
        $hseq->data['is_complex'] = 'only_columns';
        $hseq->data['indestructible'] = true;
        $column_count = ifset($this->options, 'columns', 2);
        $column_props = array();
        $column_props[$this->elements['main']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", 'padding-left' => "p-l-blc", 'padding-right' => "p-r-blc", 'background' => ["name" => "grey shades","value" => "bg-bw-8", "type" => "palette","uuid" => 1, "layers" => [["name" => "grey shades", "value" => "bg-bw-8", "type" => "palette", "uuid" => 1]]]];
        $column_props[$this->elements['wrapper']] = ['padding-top' => "p-t-10", 'padding-bottom' => "p-b-10", 'flex-align-vertical' => "x-c", 'max-width' => "cnt"];
        $result = $this->getEmptyBlockData();
        //$columns_arr = array();
        $hseq->addChild($column_left);
        $hseq->addChild($column_right);
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
            'type_name_original' => _w('Footer bottom'),
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
