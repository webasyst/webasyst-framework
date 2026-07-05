<?php
$img_path = wa()->getAppStaticUrl('site') . 'img/blocks/page_templates/articles/coffee_machine/';

$getList = function () {
  $data = [
    'pros' => [
      'heading' => '<b>Quae placent</b>',
      'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#42bd3b"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 0C18.6274 0 24 5.37258 24 12C24 18.6274 18.6274 24 12 24C5.37258 24 0 18.6274 0 12C0 5.37258 5.37258 0 12 0ZM18.7185 7.26765C18.3282 6.9306 17.7285 6.96286 17.3793 7.33957L10.6176 14.6335L7.03448 10.7675C6.68526 10.3908 6.08557 10.3589 5.69531 10.696C5.30514 11.0331 5.27186 11.6119 5.62096 11.9887L10.6176 17.3793L18.7928 8.56035C19.142 8.18358 19.1087 7.6048 18.7185 7.26765Z"></path></svg>',
      'svg_color' => '#42bd3b',
      'list' => [
        'Potiones coffeae, lacteae et nigrae uno tactu parantur',
        'Tabula moderandi simplex et clara sine superfluis optionibus',
        'Forma parva etiam in culina exigua decore stat',
      ]
    ],
    'cons' => [
      'heading' => '<b>Quae consideranda sunt</b>',
      'svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#e54949"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 0C18.6274 0 24 5.37258 24 12C24 18.6274 18.6274 24 12 24C5.37258 24 0 18.6274 0 12C0 5.37258 5.37258 0 12 0ZM17.0762 6.92376C16.6723 6.51977 16.0174 6.51978 15.6134 6.92376L12 10.5372L8.38658 6.92376C7.9826 6.51977 7.32775 6.51978 6.92376 6.92376C6.51982 7.32776 6.51979 7.98261 6.92376 8.38658L10.5372 12L6.92376 15.6134C6.51982 16.0174 6.51979 16.6723 6.92376 17.0762C7.32775 17.4801 7.98264 17.4801 8.38658 17.0762L12 13.4628L15.6134 17.0762C16.0174 17.4801 16.6723 17.4801 17.0762 17.0762C17.4802 16.6723 17.4801 16.0174 17.0762 15.6134L13.4628 12L17.0762 8.38658C17.4802 7.98264 17.4801 7.32776 17.0762 6.92376Z"></path></svg>',
      'svg_color' => '#e54949',
      'list' => [
        'Vas aquae frequenter replendum est in usu cotidiano',
        'Lavatio automatica paulum aquae consumit',
        'In loco silentissimo sonus molendi notabilis esse potest',
      ]
    ]
  ];

  $sub_column = [
    'type' => 'site.SubColumn',
    'data' => [
      'block_props' => [
        'padding-top' => 'p-t-4',
        'padding-bottom' => 'p-b-4',
        'full-width' => 'f-w',
      ],
      'wrapper_props' => [
        'justify-align' => 'j-s',
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_complex' => 'no_complex',
          ],
          'children' => ['' => []]
        ]
      ]
    ]
  ];
  $sub_columns = [];
  foreach ($data as $val) {
    $list = [];
    $list[] = [
      'type' => 'site.Heading',
      'data' => [
        'html' => $val['heading'],
        'tag' => 'h3',
        'block_props' => [
          'font-header' => 't-hdn',
          'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library',],
          'margin-top' => 'm-t-0',
          'margin-bottom' => 'm-b-12',
          'align' => 't-l',
        ],
      ],
      'children' => [],
    ];
    foreach ($val['list'] as $text) {
      $list[] = [
        'type' => 'site.Row',
        'data' => [
          'block_props' => [
            'padding-top' => 'p-t-4',
            'padding-bottom' => 'p-b-4',
          ],
          'wrapper_props' => [
            'justify-align' => 'j-s',
            'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
          ],
        ],
        'children' => [
          '' => [
            [
              'type' => 'site.VerticalSequence',
              'data' => [
                'is_horizontal' => true,
                'is_complex' => 'no_complex',
              ],
              'children' => [
                '' => [
                  [
                    'type' => 'site.Image',
                    'data' => [
                      'block_props' => [
                        'margin-bottom' => 'm-b-10',
                        'margin-right' => 'm-r-10',
                        'margin-top' => 'm-t-2',
                        'picture-size' => 'i-s',
                      ],
                      'image' => [
                        'type' => 'svg',
                        'color' => [
                          'type' => 'self_color',
                          'value' => $val['svg_color'],
                          'name' => 'Self color',
                        ],
                        'svg_html' => $val['svg'],
                        'fill' => 'removed',
                      ],
                    ],
                    'children' => [],
                  ],
                  [
                    'type' => 'site.Paragraph',
                    'data' => [
                      'html' => $text,
                      'tag' => 'p',
                      'block_props' => [
                        'font-header' => 't-rgl',
                        'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                        'margin-top' => 'm-t-0',
                        'margin-bottom' => 'm-b-10',
                        'align' => 't-l',
                      ],
                    ],
                    'children' => [],
                  ],
                ],
              ],
            ],
          ],
        ],
      ];
    }
    $sub_column['children'][''][0]['children'][''] = $list;
    $sub_columns[] = $sub_column;
  }

  return [
    'type' => 'site.Row',
    'data' => [
      'block_props' => [
        'padding-top' => 'p-t-4',
        'padding-bottom' => 'p-b-4',
      ],
      'wrapper_props' => [
        'justify-align' => 'j-s',
        'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb',
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_horizontal' => true,
            'is_complex' => 'no_complex',
          ],
          'children' => ['' => $sub_columns,],
        ],
      ],
    ],
  ];
};

$getMachine = function (array $m) use ($img_path) {
  $btn = [
    'type' => 'site.Button',
    'data' => [
      'html' => 'Sestertii',
      'tag' => 'a',
      'block_props' => [
        'button-style' => ['name' => 'Palette', 'value' => 'btn-blc', 'type' => 'palette',],
        'button-size' => 'inp-m p-l-13 p-r-13',
        'full-width' => 'f-w',
      ],
    ],
    'children' => [],
  ];
  return [
    'type' => 'site.SubColumn',
    'data' => [
      'block_props' => [
        'padding-top' => 'p-t-10',
        'padding-bottom' => 'p-b-10',
        'full-width' => 'f-w',
        'margin-right' => 'm-r-19 m-r-16-tb m-r-0-mb',
      ],
      'wrapper_props' => ['justify-align' => 'j-s',],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_complex' => 'no_complex',
          ],
          'children' => [
            '' => [
              [
                'type' => 'site.Image',
                'data' => [
                  'block_props' => [
                    'margin-bottom' => 'm-b-12',
                  ],
                  'image' => [
                    'type' => 'address',
                    'url_text' => $img_path . $m['img'],
                  ],
                ],
                'children' => [],
              ],
              [
                'type' => 'site.Paragraph',
                'data' => [
                  'html' => $m['title'],
                  'tag' => 'p',
                  'block_props' => [
                    'font-header' => 't-hdn',
                    'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                    'margin-top' => 'm-t-0',
                    'margin-bottom' => 'm-b-9',
                    'align' => 't-l',
                  ],
                ],
                'children' => [],
              ],
              [
                'type' => 'site.Paragraph',
                'data' => [
                  'html' => $m['amount'],
                  'tag' => 'p',
                  'block_props' => [
                    'font-header' => 't-rgl',
                    'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                    'margin-top' => 'm-t-0',
                    'margin-bottom' => 'm-b-10',
                    'align' => 't-l',
                  ],
                ],
                'children' => [],
              ],
              $btn
            ]
          ]
        ]
      ]
    ]
  ];
};
$getMachines = function () use ($getMachine) {
  $data = [
    ['img' => 'coffee-machine-1.jpg', 'title' => 'Crema Home Pro', 'amount' => '<b>54 500 den.</b>',],
    ['img' => 'coffee-machine-2.jpg', 'title' => 'Daily Brew Compact', 'amount' => '<b>42 900 den.</b>',],
    ['img' => 'coffee-machine-3.jpg', 'title' => 'Aroma Unum Latte', 'amount' => '<b>49 900 den.</b>',],
  ];

  $list = [];
  foreach ($data as $m) {
    $list[] = $getMachine($m);
  }

  return [
    'type' => 'site.Row',
    'data' => [
      'block_props' => [
        'padding-top' => 'p-t-10',
        'padding-bottom' => 'p-b-10',
        'margin-bottom' => 'm-b-12',
      ],
      'wrapper_props' => [
        'justify-align' => 'j-s',
        'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb',
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_horizontal' => true,
            'is_complex' => 'no_complex',
          ],
          'children' => ['' => $list]
        ]
      ]
    ]
  ];
};

$getCardColumn = function (array $c)  use ($img_path) {
  return [
    'type' => 'site.Column',
    'data' => [
      'indestructible' => false,
      'wrapper_props' => [
        'flex-align' => 'y-c',
      ],
      'elements' => [
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
      ],
      'column' => 'st-12-mb st-4 st-4-lp st-12-tb',
      'block_props' => [
        'site-block-column' => [
          'padding-bottom' => 'p-b-14',
          'padding-left' => 'p-l-clm',
          'padding-right' => 'p-r-clm',
          'padding-top' => 'p-t-14',
        ],
        'site-block-column-wrapper' => [
          'border-radius' => 'b-r-l',
          'background' => [
            'layers' => [
              [
                'css' => 'palette',
                'name' => 'black and white',
                'type' => 'palette',
                'value' => 'bg-wh',
              ],
            ],
            'name' => 'black and white',
            'type' => 'palette',
            'value' => 'bg-wh',
          ],
          'border-radius-corners' => [
            'value' => '',
            'type' => 'separate',
          ],
          'flex-align' => 'y-c',
        ],
      ],
      'inline_props' => [
        'site-block-column-wrapper' => [
          'min-height' => [
            'name' => 'Parent height',
            'type' => 'parent',
            'value' => '100%',
          ],
        ],
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_horizontal' => false,
            'is_complex' => 'with_row',
          ],
          'children' => [
            '' => [
              [
                'type' => 'site.Image',
                'data' => [
                  'image' => [
                    'type' => 'address',
                    'url_text' => $img_path . $c['img'],
                  ],
                  'block_props' => [
                    'border-radius' => 'b-r-l',
                    'border-radius-corners' => [
                      'value' => 'b-r-u-bl b-r-u-br',
                      'type' => 'separate',
                    ],
                  ],
                ],
                'children' => [],
              ],
              [
                'type' => 'site.SubColumn',
                'data' => [
                  'block_props' => [
                    'full-width' => 'f-w',
                    'padding-bottom' => 'p-b-12',
                    'padding-left' => 'p-l-14',
                    'padding-right' => 'p-r-14',
                    'padding-top' => 'p-t-12',
                    'border-radius-corners' => [
                      'value' => ' b-r-u-tr b-r-u-tl',
                      'type' => 'separate',
                    ],
                    'border-radius' => 'b-r-l',
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
                  'wrapper_props' => [
                    'justify-align' => 'j-s',
                    'flex-align' => 'y-l',
                  ],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_complex' => 'no_complex',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => $c['heading'],
                              'tag' => 'h3',
                              'block_props' => [
                                'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library',],
                                'margin-bottom' => 'm-b-10',
                                'margin-top' => 'm-t-0',
                                'align' => 't-l',
                                'font-header' => 't-hdn',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => $c['text'],
                              'tag' => 'p',
                              'block_props' => [
                                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                                'margin-bottom' => 'm-b-8',
                                'margin-top' => 'm-t-a',
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                              ],
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ],
  ];
};
$getCardsColumn = function () use ($getCardColumn) {
  $cards = [];
  $data = [
    ['img' => 'coffee-close-up.jpg', 'heading' => 'Aliae res lectu dignae', 'text' => 'Comparantur machinae cultratae et molares; explicamus quomodo gradus molendi saporem afficiat et cur constantia praevaleat super functionibus supervacuis.'],
    ['img' => 'coffee-farm.jpg', 'heading' => 'Dux ad grana coffeae eligenda', 'text' => 'Quomodo intellegatur differentia inter Arabica et Robusta, quid torrefactio efficiat et quod genus grani aptissimum sit ad usum domesticum.'],
    ['img' => 'ice-coffee.jpg', 'heading' => 'Coffea frigida domi: recepta aestiva simplicia', 'text' => 'Lacte glaciale, potio cum aqua tonica et alia refrigerantia celeria, quae sine arte difficili facile praeparari possunt.'],
  ];
  foreach ($data as $item) {
    $cards[] = $getCardColumn($item);
  }

  return $cards;
};

return [
  [
    'type' => 'site.Menu.',
    'data' => [
      'block_props' => [
        'site-block-menu' => [
          'padding-top' => 'p-t-6',
          'padding-bottom' => 'p-b-6',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-menu-wrapper' => [
          'flex-align-vertical' => 'x-c',
          'max-width' => 'cnt',
        ],
      ],
      'inline_props' => [
        'site-block-menu' => [
          'scroll-margin-top' => [
            'value' => '',
            'unit' => 'px',
            'id' => 'menut1',
          ],
        ],
      ],
      'wrapper_props' => [
        'justify-align' => 'y-j-cnt',
        'flex-align-vertical' => 'x-c',
      ],
      'elements' => [
        'main' => 'site-block-menu',
        'wrapper' => 'site-block-menu-wrapper',
      ],
      'id' => [
        'site-block-menu' => [
          'id' => 'menut1',
        ],
      ],
      'app_template' => [
        'disabled' => false,
        'active' => false,
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_horizontal' => true,
            'is_complex' => 'only_columns',
            'indestructible' => true,
          ],
          'children' => [
            'col1' => [
              [
                'type' => 'site.MenuLogoT1',
                'data' => [
                  'block_props' => [
                    'site-block-column' => [
                      'margin-left' => 'm-l-0',
                      'margin-right' => 'm-r-a',
                      'padding-bottom' => 'p-b-6',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-6',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'inline_props' => [
                    'site-block-column' => [
                      'scroll-margin-top' => [
                        'value' => '',
                        'unit' => 'px',
                        'id' => 'logo',
                      ],
                    ],
                  ],
                  'id' => [
                    'site-block-column' => [
                      'id' => 'logo',
                    ],
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'indestructible' => false,
                  'column' => 'st-3 st-3-lp st-4-tb st-10-mb',
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => true,
                        'indestructible' => true,
                        'is_complex' => 'no_complex',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-6',
                                'padding-bottom' => 'p-b-6',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-mb',
                              ],
                              'inline_props' => [],
                              'id' => '',
                            ],
                            'children' => [
                              '' => [
                                [
                                  'type' => 'site.VerticalSequence',
                                  'data' => [
                                    'is_horizontal' => true,
                                    'is_complex' => 'no_complex',
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-8',
                                            'margin-right' => 'm-r-12',
                                            'margin-top' => 'm-t-5',
                                            'pictures-size' => 'i-xl',
                                          ],
                                          'indestructible' => false,
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64" viewBox="0 0 64 64" fill-rule="evenodd">
        <path d="M32,47 C40.2842712,47 47,40.2842712 47,32 C47,23.7157288 40.2842712,17 32,17 C23.7157288,17 17,23.7157288 17,32 C17,40.2842712 23.7157288,47 32,47 Z M32,50 C41.9411255,50 50,41.9411255 50,32 C50,22.0588745 41.9411255,14 32,14 C22.0588745,14 14,22.0588745 14,32 C14,41.9411255 22.0588745,50 32,50 Z"/>
        <path d="M32,61 C15.9837423,61 3,48.0162577 3,32 C3,15.9837423 15.9837423,3 32,3 C48.0162577,3 61,15.9837423 61,32 C61,48.0162577 48.0162577,61 32,61 Z M32,52 C20.954305,52 12,43.045695 12,32 C12,20.954305 20.954305,12 32,12 C43.045695,12 52,20.954305 52,32 C52,43.045695 43.045695,52 32,52 Z"/>
        </svg>',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.SubColumn',
                                        'data' => [
                                          'block_props' => [
                                            'padding-top' => 'p-t-8',
                                            'padding-bottom' => 'p-b-6',
                                          ],
                                          'wrapper_props' => [
                                            'justify-align' => 'j-s',
                                          ],
                                          'inline_props' => [],
                                          'id' => '',
                                        ],
                                        'children' => [
                                          '' => [
                                            [
                                              'type' => 'site.VerticalSequence',
                                              'data' => [
                                                'is_complex' => 'no_complex',
                                              ],
                                              'children' => [
                                                '' => [
                                                  [
                                                    'type' => 'site.Heading',
                                                    'data' => [
                                                      'html' => '<b>Nomen Societatis</b>',
                                                      'tag' => 'h3',
                                                      'block_props' => [
                                                        'font-size' => [
                                                          'name' => 'Size #7',
                                                          'value' => 't-7',
                                                          'type' => 'library',
                                                          'unit' => 'px',
                                                        ],
                                                        'font-header' => 't-hdn',
                                                        'margin-top' => 'm-t-0',
                                                        'margin-bottom' => 'm-b-2',
                                                        'align' => 't-l',
                                                      ],
                                                    ],
                                                    'children' => [],
                                                  ],
                                                  [
                                                    'type' => 'site.Heading',
                                                    'data' => [
                                                      'html' => '<font color="" class="tx-bw-3">Motto succinctum</font>',
                                                      'block_props' => [
                                                        'font-size' => [
                                                          'name' => 'Size #8',
                                                          'value' => 't-8',
                                                          'type' => 'library',
                                                          'unit' => 'px',
                                                        ],
                                                        'margin-top' => 'm-t-0',
                                                        'margin-bottom' => 'm-b-6',
                                                        'align' => 't-l',
                                                        'font-header' => 't-rgl',
                                                      ],
                                                      'tag' => 'p',
                                                    ],
                                                    'children' => [],
                                                  ],
                                                ],
                                              ],
                                            ],
                                          ],
                                        ],
                                      ],
                                    ],
                                  ],
                                ],
                              ],
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
            'col2' => [
              [
                'type' => 'site.MenuContactsT1',
                'data' => [
                  'block_props' => [
                    'site-block-column' => [
                      'margin-bottom' => 'm-b-a',
                      'margin-left' => 'm-l-a m-l-0-tb',
                      'margin-right' => 'm-r-a',
                      'margin-top' => 'm-t-a',
                      'padding-top' => 'p-t-6',
                      'padding-bottom' => 'p-b-6',
                      'padding-left' => 'p-l-0',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'border-radius-corners' => [
                        'value' => '',
                        'type' => 'all',
                      ],
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'id' => [
                    'site-block-column' => [
                      'id' => 'menut1itms',
                    ],
                  ],
                  'indestructible' => true,
                  'column' => 'st-0-tb st-0-mb st-6-lp st-6',
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => true,
                        'is_complex' => 'no_complex',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-bottom' => 'p-b-6',
                                'padding-top' => 'p-t-8',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-ds n-wr-lp',
                                'justify-align' => 'j-end',
                              ],
                              'inline_props' => [],
                              'id' => '',
                            ],
                            'children' => [
                              '' => [
                                [
                                  'type' => 'site.VerticalSequence',
                                  'data' => [
                                    'is_horizontal' => true,
                                    'is_complex' => 'no_complex',
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.MenuItem',
                                        'data' => [
                                          'html' => 'De Nobis',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'width' => 'cnt-w',
                                            'border-radius' => 'b-r-r',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-blc-lnk',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-s p-l-12 p-r-12',
                                            'margin-bottom' => 'm-b-12',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.MenuItem',
                                        'data' => [
                                          'html' => 'Servitia',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'width' => 'cnt-w',
                                            'border-radius' => 'b-r-r',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-blc-lnk',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-s p-l-12 p-r-12',
                                            'margin-bottom' => 'm-b-12',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.MenuItem',
                                        'data' => [
                                          'html' => 'Opera',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'width' => 'cnt-w',
                                            'border-radius' => 'b-r-r',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-blc-lnk',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-s p-l-12 p-r-12',
                                            'margin-bottom' => 'm-b-12',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.MenuItem',
                                        'data' => [
                                          'html' => 'Auxilium',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'width' => 'cnt-w',
                                            'border-radius' => 'b-r-r',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-blc-lnk',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-s p-l-12 p-r-12',
                                            'margin-bottom' => 'm-b-12',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                    ],
                                  ],
                                ],
                              ],
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
            'col3' => [
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-0-mb st-7-tb st-3-lp st-3',
                  'block_props' => [
                    'site-block-column' => [
                      'margin-bottom' => 'm-b-a',
                      'margin-left' => 'm-l-a',
                      'margin-top' => 'm-t-a',
                      'padding-bottom' => 'p-b-6',
                      'padding-left' => 'p-l-0',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-6',
                      'visibility' => 'd-n-mb',
                    ],
                    'site-block-column-wrapper' => [
                      'border-radius' => 'b-r-l',
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [],
                  'inline_props' => [],
                  'indestructible' => false,
                  'id' => '',
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => true,
                        'is_complex' => 'no_complex',
                        'indestructible' => true,
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'Contactate',
                              'tag' => 'a',
                              'block_props' => [
                                'border-radius' => 'b-r-r',
                                'button-size' => 'inp-m p-l-13 p-r-13',
                                'margin-bottom' => 'm-b-12',
                                'margin-left' => 'm-l-a',
                                'margin-top' => 'm-t-8',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-blc',
                                  'type' => 'palette',
                                ],
                              ],
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
            'col4' => [
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-0-lp st-0 st-1-tb st-2-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'margin-bottom' => 'm-b-a',
                      'margin-left' => 'm-l-a',
                      'margin-top' => 'm-t-a',
                      'padding-bottom' => 'p-b-0',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-0',
                      'visibility' => 'd-n-lp d-n-ds',
                    ],
                    'site-block-column-wrapper' => [
                      'padding-top' => 'p-t-10',
                      'padding-bottom' => 'p-b-10',
                      'border-radius' => 'b-r-l',
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [],
                  'inline_props' => [],
                  'indestructible' => false,
                  'id' => [
                    'site-block-column' => [
                      'id' => 'menut1gmb',
                    ],
                  ],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => true,
                        'is_complex' => 'no_complex',
                        'indestructible' => true,
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-bottom' => 'p-b-10',
                                'padding-top' => 'p-t-7',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-ds n-wr-lp',
                                'justify-align' => 'j-end',
                              ],
                              'inline_props' => [],
                              'id' => '',
                            ],
                            'children' => [
                              '' => [
                                [
                                  'type' => 'site.VerticalSequence',
                                  'data' => [
                                    'is_horizontal' => true,
                                    'is_complex' => 'no_complex',
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.MenuButton',
                                        'data' => [
                                          'image' => [
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-blc',
                                              'type' => 'palette',
                                            ],
                                            'fill' => 'removed',
                                            'open_menu_svg_html' => '<svg viewBox="0 0 16 16" fill="var(--black)" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.2914 1.56903L0.70099 1.56903C0.313844 1.56903 -3.61259e-08 1.88288 0 2.27002C3.61259e-08 2.65717 0.313844 2.97101 0.70099 2.97101L15.2914 2.97101C15.6786 2.97101 16 2.65717 16 2.27002C16 1.88287 15.6786 1.56903 15.2914 1.56903Z"></path>
                        <path d="M15.2914 7.29901L0.70099 7.29902C0.313844 7.29902 -3.61259e-08 7.61286 0 8.00001C3.61259e-08 8.38715 0.313844 8.70099 0.70099 8.70099L15.2914 8.70099C15.6786 8.70099 16 8.38715 16 8C16 7.61285 15.6786 7.29901 15.2914 7.29901Z"></path>
                        <path d="M15.2914 13.0286L0.70099 13.0286C0.313844 13.0286 -3.61259e-08 13.3424 0 13.7296C3.61259e-08 14.1167 0.313844 14.4305 0.70099 14.4305L15.2914 14.4305C15.6786 14.4305 16 14.1167 16 13.7296C16 13.3424 15.6786 13.0286 15.2914 13.0286Z"></path>
                    </svg>',
                                            'close_menu_svg_html' => '<svg viewBox="0 0 16 16" fill="var(--black)" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.33952 1.3482L8 7.00848L13.6605 1.3482C13.9342 1.07444 14.3781 1.07444 14.6518 1.3482C14.9256 1.62195 14.9256 2.06579 14.6518 2.33955L8.99154 8.00003L14.6518 13.6605C14.9256 13.9343 14.9256 14.3781 14.6518 14.6519C14.3781 14.9256 13.9342 14.9256 13.6605 14.6519L8 8.99157L2.33952 14.6519C2.06576 14.9256 1.62192 14.9256 1.34817 14.6519C1.07441 14.3781 1.07441 13.9343 1.34817 13.6605L7.00845 8.00003L1.34817 2.33955C1.07441 2.06579 1.07441 1.62195 1.34817 1.3482C1.62192 1.07444 2.06576 1.07444 2.33952 1.3482Z"></path>
                    </svg>',
                                          ],
                                          'block_props' => [
                                            'visibility' => 'd-n-ds d-n-lp',
                                            'margin-bottom' => 'm-b-8-mb',
                                            'picture-size' => 'i-s',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                    ],
                                  ],
                                ],
                              ],
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
            'col5' => [
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-12-tb st-0-lp st-0',
                  'block_props' => [
                    'site-block-column' => [
                      'background' => [
                        'layers' => [
                          [
                            'name' => 'black and white',
                            'value' => 'bg-wh',
                            'type' => 'palette',
                          ],
                        ],
                        'name' => 'black and white',
                        'value' => 'bg-wh',
                        'type' => 'palette',
                      ],
                      'visibility' => 'd-n-lp d-n-ds',
                      'padding-left' => 'p-l-0',
                      'padding-right' => 'p-r-0',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [],
                  'inline_props' => [],
                  'indestructible' => false,
                  'id' => [
                    'site-block-column' => [
                      'id' => 'menut1bg',
                    ],
                  ],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => true,
                        'is_complex' => 'no_complex',
                        'indestructible' => true,
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [],
                              'wrapper_props' => [],
                              'inline_props' => [],
                              'id' => '',
                            ],
                            'children' => [
                              '' => [
                                [
                                  'type' => 'site.VerticalSequence',
                                  'data' => [
                                    'is_horizontal' => true,
                                    'is_complex' => 'no_complex',
                                  ],
                                  'children' => [],
                                ],
                              ],
                            ],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomHero',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-20-tb p-t-20 p-t-12-mb',
          'padding-bottom' => 'p-b-20-tb p-b-20 p-b-12-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-0 p-t-0-mb',
          'padding-bottom' => 'p-b-0 p-b-0-mb',
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
        ],
      ],
      'inline_props' => [
        'site-block-columns' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'linear-gradient(#00000099, #00000099), center center / cover url(' . $img_path . 'top-bg.png)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'name' => 'Self color',
                'css' => '#00000099',
                'value' => 'linear-gradient(#00000099, #00000099)',
              ],
              [
                'type' => 'image',
                'value' => 'center center / cover url(' . $img_path . 'top-bg.png)',
                'alignmentX' => 'center',
                'alignmentY' => 'center',
                'file_name' => 'top-bg.png',
                'file_url' => $img_path . 'top-bg',
                'space' => 'cover',
                'name' => 'Image',
              ],
            ],
            'uuid' => 1,
          ],
        ],
      ],
      'wrapper_props' => [
        'justify-align' => 'y-j-cnt',
      ],
      'elements' => [
        'main' => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_horizontal' => true,
            'is_complex' => 'only_columns',
            'indestructible' => true,
          ],
          'children' => [
            '' => [
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12 st-12-lp st-12-tb st-12-mb',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-12-tb p-t-0-mb',
                      'padding-bottom' => 'p-b-12-tb p-b-0-mb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'column-max-width' => 'fx-9',
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-12-tb p-t-0-mb',
                      'padding-bottom' => 'p-b-12-tb p-b-0-mb',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-4',
                                'padding-bottom' => 'p-b-4',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                              ],
                            ],
                            'children' => [
                              '' => [
                                [
                                  'type' => 'site.VerticalSequence',
                                  'data' => [
                                    'is_horizontal' => true,
                                    'is_complex' => 'no_complex',
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-wh">Domus</font>',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-0',
                                            'margin-bottom' => 'm-b-8',
                                            'align' => 't-l',
                                            'margin-right' => 'm-r-8',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-wh">&nbsp;/&nbsp;</font>',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-0',
                                            'margin-bottom' => 'm-b-8',
                                            'align' => 't-l',
                                            'margin-right' => 'm-r-8',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="#ffffff"><span style="caret-color: rgb(255, 255, 255);">Acta</span></font>',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-0',
                                            'margin-bottom' => 'm-b-8',
                                            'align' => 't-l',
                                            'margin-right' => 'm-r-8',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-wh">&nbsp;/ &nbsp;</font>',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-0',
                                            'margin-bottom' => 'm-b-8',
                                            'align' => 't-l',
                                            'margin-right' => 'm-r-8',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                    ],
                                  ],
                                ],
                              ],
                            ],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<span class="tx-wh">Utrum machina automatica coffeae domui conveniat
</span>',
                              'tag' => 'h1',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #2',
                                  'value' => 't-2',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-w-opc-7">Explicamus cui talis machina utilis sit, quid ante emptionem spectandum sit et quae proprietates vere momenti sint in usu cotidiano.

</font>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomText',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-12-mb p-t-16',
          'padding-bottom' => 'p-b-12-mb p-b-16',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-0-mb',
          'padding-bottom' => 'p-b-0-mb',
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
        ],
      ],
      'wrapper_props' => [
        'justify-align' => 'j-s',
      ],
      'elements' => [
        'main' => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_horizontal' => true,
            'is_complex' => 'only_columns',
            'indestructible' => true,
          ],
          'children' => [
            '' => [
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-tb st-12-mb st-8-lp st-8',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-12',
                      'padding-top' => 'p-t-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'margin-right' => 'm-r-a',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'inline_props' => [],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => false,
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Machina automatica iam non solum officinis aut tabernis destinatur. In domo quoque sensum habet: tactu uno potio paratur, sine mora matutina. Sed commoditas pretium habet.

',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-16',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Image',
                            'data' => [
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path . 'coffee-machine-1--interior.jpg',
                              ],
                              'block_props' => [
                                'border-radius' => 'b-r-l',
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          $getList(),
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Si coffeam regulariter bibis et exitum praedictabilem sine difficultate instrumentorum manualium desideras, machina automatica multas res domesticas vere solvit. Praesertim apta est iis qui potiones lacteas amant neque volunt singulis vicibus lac separatim spumare, temperaturam aquae observare aut gradum molendi eligere.',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-14',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-14',
                                'padding-bottom' => 'p-b-14',
                                'padding-left' => 'p-l-16',
                                'padding-right' => 'p-r-16',
                                'border-radius' => 'b-r-l',
                                'background' => [
                                  'name' => 'grey shades',
                                  'value' => 'bg-bw-7',
                                  'type' => 'palette',
                                  'uuid' => 1,
                                  'layers' => [
                                    [
                                      'name' => 'grey shades',
                                      'value' => 'bg-bw-7',
                                      'type' => 'palette',
                                      'uuid' => 1,
                                    ],
                                  ],
                                ],
                                'margin-bottom' => 'm-b-16',
                                'margin-top' => 'm-t-12',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
                              ],
                            ],
                            'children' => [
                              '' => [
                                [
                                  'type' => 'site.VerticalSequence',
                                  'data' => [
                                    'is_horizontal' => true,
                                    'is_complex' => 'no_complex',
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-14',
                                            'margin-right' => 'm-r-16',
                                            'picture-size' => 'i-xl',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-b-opc-3',
                                              'type' => 'palette',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="32" viewBox="0 0 40 32" fill="var(--b-opc-3)">
<path d="M31.3911 29.6961C28.2551 29.6961 26.0151 28.6881 24.6711 26.6721C23.3271 24.5814 22.6551 22.3788 22.6551 20.0641C22.6551 18.1228 22.9537 16.1068 23.5511 14.0161C24.1484 11.8508 25.0817 9.79744 26.3511 7.8561C27.6204 5.91477 29.2631 4.34677 31.2791 3.1521C33.3697 1.95744 35.8711 1.36011 38.7831 1.36011C38.8577 1.36011 38.8577 1.65877 38.7831 2.25611C38.7831 2.85344 38.7831 3.4881 38.7831 4.1601C38.7831 4.75744 38.7831 5.05611 38.7831 5.05611C38.7831 5.05611 38.3724 5.20544 37.5511 5.50411C36.7297 5.72811 35.7591 6.21344 34.6391 6.96011C33.5191 7.63211 32.5484 8.60277 31.7271 9.8721C30.9057 11.1414 30.4951 12.7841 30.4951 14.8001C30.6444 14.7254 30.8311 14.6881 31.0551 14.6881C31.2791 14.6881 31.4657 14.6881 31.6151 14.6881C33.7804 14.6881 35.4977 15.3601 36.7671 16.7041C38.1111 18.0481 38.7831 19.7654 38.7831 21.8561C38.7831 24.1708 38.0737 26.0748 36.6551 27.5681C35.3111 28.9868 33.5564 29.6961 31.3911 29.6961ZM9.77506 29.6961C6.63906 29.6961 4.39906 28.6881 3.05506 26.6721C1.71106 24.5814 1.03906 22.3788 1.03906 20.0641C1.03906 18.1228 1.33773 16.1068 1.93506 14.0161C2.5324 11.8508 3.46573 9.79744 4.73506 7.8561C6.0044 5.91477 7.64706 4.34677 9.66306 3.1521C11.7537 1.95744 14.2551 1.36011 17.1671 1.36011C17.2417 1.36011 17.2417 1.65877 17.1671 2.25611C17.1671 2.85344 17.1671 3.4881 17.1671 4.1601C17.1671 4.75744 17.1671 5.05611 17.1671 5.05611C17.1671 5.05611 16.7564 5.20544 15.9351 5.50411C15.1137 5.72811 14.1431 6.21344 13.0231 6.96011C11.9031 7.63211 10.9324 8.60277 10.1111 9.8721C9.28973 11.1414 8.87906 12.7841 8.87906 14.8001C9.0284 14.7254 9.21506 14.6881 9.43906 14.6881C9.66306 14.6881 9.84973 14.6881 9.99906 14.6881C12.1644 14.6881 13.8817 15.3601 15.1511 16.7041C16.4951 18.0481 17.1671 19.7654 17.1671 21.8561C17.1671 24.1708 16.4577 26.0748 15.0391 27.5681C13.6951 28.9868 11.9404 29.6961 9.77506 29.6961Z"></path>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => 'Bona machina domestica non est de “sicut in taberna coffeae”, sed de sapore constanti, celeritate et consuetudine usus quae matutino tempore non molestat.',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-0',
                                            'margin-bottom' => 'm-b-10',
                                            'align' => 't-l',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                    ],
                                  ],
                                ],
                              ],
                            ],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Suspendisse ac rhoncus nisl, eu tempor urna. Curabitur vel bibendum lorem. Morbi convallis convallis diam sit amet lacinia. Aliquam in elementum tellus.',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-14',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-tb st-12-mb st-3 st-3-lp',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-12',
                      'padding-top' => 'p-t-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'margin-top' => 'm-t-12',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-14',
                      'padding-top' => 'p-t-14',
                      'background' => [
                        'name' => 'grey shades',
                        'value' => 'bg-bw-7',
                        'type' => 'palette',
                        'uuid' => 1,
                        'layers' => [
                          [
                            'name' => 'grey shades',
                            'value' => 'bg-bw-7',
                            'type' => 'palette',
                            'uuid' => 1,
                          ],
                        ],
                      ],
                      'border-radius' => 'b-r-l',
                      'padding-left' => 'p-l-14',
                      'padding-right' => 'p-r-14',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'inline_props' => [],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => false,
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Image',
                            'data' => [
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path . 'coffee-machine-1-1.jpg',
                              ],
                              'block_props' => [
                                'border-radius' => 'b-r-l',
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Aroma Compactum Nigrum',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b>50 000 den.</b>',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'Sestertii<br>',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-bottom' => 'm-b-8',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-blc',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-m p-l-13 p-r-13',
                                'full-width' => 'f-w',
                              ],
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-tb st-12-mb st-8-lp st-8',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-12',
                      'padding-top' => 'p-t-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'margin-right' => 'm-r-a',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'inline_props' => [],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => false,
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Cui hic modus re vera commodus sit',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #4',
                                  'value' => 't-4',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-16',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'h3',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Image',
                            'data' => [
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path . 'coffee-machine-2--interior.jpg',
                              ],
                              'block_props' => [
                                'border-radius' => 'b-r-l',
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-4"> Exemplar cum systemate lactis automatico in vero interiori usu</font>',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-16',
                                'margin-left' => 'm-l-18 m-l-16-tb',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Hic modus maxime convenit familiae, parvo officio aut iis qui coffeam cotidie diligunt, sed in ritus manuales coquendi se immergere nolunt. Aliis celeritas maximi momenti est, aliis sapor constans et intellegibilis, tertiis facultas cito capuccinum hospitibus parandi. Hac de causa machinae automaticae optime se accommodant ad usum cotidianum.

',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Similia exemplaria
',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-10',
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          $getMachines(),
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Si ipsa idea machinae automaticae tibi placet, sed vis melius intellegere differentias inter usus diversos, utile est exemplaria non solum pretio comparare. Considera quam facile sit vas aquae extrahere, utrum systema lactis celeriter purgari possit et quam clara sit ratio moderandi. Hae res maxime influunt in experientiam cotidianam.',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-14',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Aliis emptoribus compactio praevalebit, aliis commoditas capuccinatoris, tertiis aspectus severior qui interiori culinae conveniat. Bona pagina recensionis non solum debet permittere exemplaria inspicere, sed etiam adiuvare ut intellegas quis usus tibi maxime conveniat.',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-14',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
              [
                'type' => 'site.Column',
                'data' => [
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-12',
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-3 st-6-tb st-12-mb st-3-lp',
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '1500+',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #2',
                                  'value' => 't-2',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'pocula coffeae per annum parare potest machina domestica in familia, ubi coffea cotidie bibitur et potiones lacteae amantur.

',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomMailerSubscribe2',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-16',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-16',
          'background' => [
            'layers' => [
              [
                'name' => 'grey shades',
                'type' => 'palette',
                'uuid' => 1,
                'value' => 'bg-bw-7',
              ],
            ],
            'name' => 'grey shades',
            'type' => 'palette',
            'uuid' => 1,
            'value' => 'bg-bw-7',
          ],
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'padding-bottom' => 'p-b-0',
          'padding-top' => 'p-t-0',
        ],
      ],
      'wrapper_props' => [
        'justify-align' => 'y-j-cnt',
      ],
      'elements' => [
        'main' => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_horizontal' => true,
            'is_complex' => 'only_columns',
            'indestructible' => true,
          ],
          'children' => [
            '' => [
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-7 st-6-lp st-9-tb st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-s',
                      'padding-top' => 'p-t-12 p-t-0-tb',
                      'padding-right' => 'p-r-clm',
                      'padding-bottom' => 'p-b-12 p-b-0-tb',
                      'padding-left' => 'p-l-clm',
                      'border-radius' => 'b-r-l',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-12 p-t-0-tb',
                      'padding-bottom' => 'p-b-12 p-b-0-tb',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'inline_props' => [],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => false,
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Accipe epistulas de novis recensionibus et consiliis circa coffeam',
                              'tag' => 'h3',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #4',
                                  'value' => 't-4',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                                'margin-top' => 'm-t-0',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-4">Semel in hebdomada mittimus solum utilia: articuli recentes, duces practici de granis, molis coffeae, modis coquendi atque inventa utilia ad usum domesticum coffeae.</font>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #7',
                                  'value' => 't-7',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-5 st-6-lp st-9-tb st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-s',
                      'padding-top' => 'p-t-12 p-t-0-tb',
                      'padding-right' => 'p-r-clm',
                      'padding-bottom' => 'p-b-12 p-b-0-tb',
                      'padding-left' => 'p-l-clm',
                      'border-radius' => 'b-r-l',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-12 p-t-0-tb',
                      'padding-bottom' => 'p-b-12 p-b-0-tb',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'inline_props' => [],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => false,
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Form.mailer',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                                'margin-left' => 'm-l-0-tb m-l-19-lp m-l-0-mb',
                                'margin-top' => 'm-t-8',
                              ],
                              'textarea_html' => '{$wa->mailer->form(6)}',
                              'form_type' => 'mailer',
                              'html' => '{$wa->mailer->form(6)}',
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomCategories4',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-20 p-b-16-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-16-mb p-t-16',
          'background' => [
            'name' => 'grey shades',
            'value' => 'bg-bw-7',
            'type' => 'palette',
            'uuid' => 1,
            'layers' => [
              [
                'name' => 'grey shades',
                'value' => 'bg-bw-7',
                'type' => 'palette',
                'uuid' => 1,
              ],
            ],
          ],
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
        ],
      ],
      'wrapper_props' => [
        'justify-align' => 'j-s',
      ],
      'elements' => [
        'main' => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_horizontal' => true,
            'is_complex' => 'only_columns',
            'indestructible' => true,
          ],
          'children' => [
            '' => array_merge([
              [
                'type' => 'site.Column',
                'data' => [
                  'indestructible' => false,
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-12 st-12-lp st-12-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-8',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-8',
                    ],
                    'site-block-column-wrapper' => [
                      'border-radius' => 'b-r-l',
                      'border-radius-corners' => [
                        'value' => '',
                        'type' => 'separate',
                      ],
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'inline_props' => [
                    'site-block-column-wrapper' => [
                      'min-height' => [
                        'name' => 'Parent height',
                        'type' => 'parent',
                        'value' => '100%',
                      ],
                    ],
                  ],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => false,
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Aliae res lectu dignae',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-8',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ], $getCardsColumn()),
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.FooterBottom2.2',
    'data' => [
      'block_props' => [
        'site-block-footer' => [
          'padding-top' => 'p-t-10',
          'padding-bottom' => 'p-b-10',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'background' => [
            'name' => 'grey shades',
            'value' => 'bg-bw-2',
            'type' => 'palette',
            'uuid' => 1,
            'layers' => [
              [
                'name' => 'grey shades',
                'value' => 'bg-bw-2',
                'type' => 'palette',
                'uuid' => 1,
              ],
            ],
          ],
        ],
        'site-block-footer-wrapper' => [
          'padding-top' => 'p-t-10',
          'padding-bottom' => 'p-b-10',
          'flex-align-vertical' => 'x-c',
          'max-width' => 'cnt',
        ],
      ],
      'wrapper_props' => [
        'justify-align' => 'y-j-cnt',
      ],
      'elements' => [
        'main' => 'site-block-footer',
        'wrapper' => 'site-block-footer-wrapper',
      ],
      'app_template' => [
        'disabled' => false,
        'active' => false,
      ],
    ],
    'children' => [
      '' => [
        [
          'type' => 'site.VerticalSequence',
          'data' => [
            'is_horizontal' => true,
            'is_complex' => 'only_columns',
            'indestructible' => true,
          ],
          'children' => [
            '' => [
              [
                'type' => 'site.FooterColumn',
                'data' => [
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-0',
                      'padding-bottom' => 'p-b-0',
                    ],
                    'site-block-column-wrapper' => [
                      'padding-top' => 'p-t-0',
                      'padding-bottom' => 'p-b-0',
                      'border-radius' => 'b-r-l',
                      'flex-align' => 'y-l',
                    ],
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-7-lp st-8 st-12-tb',
                  'wrapper_props' => [
                    'justify-align' => 'j-s',
                  ],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => true,
                        'is_complex' => 'no_complex',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Image',
                            'data' => [
                              'block_props' => [
                                'picture-size' => 'i-l',
                                'margin-left' => 'm-l-11-mb',
                                'margin-bottom' => 'm-b-0',
                                'margin-right' => 'm-r-10',
                                'margin-top' => 'm-t-6',
                                'border-radius' => 'b-r-l',
                                'width' => 'i-xxl',
                              ],
                              'indestructible' => false,
                              'image' => [
                                'type' => 'svg',
                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64" height="64" viewBox="0 0 64 64" fill-rule="evenodd" fill="#ffffff">
        <path d="M32,47 C40.2842712,47 47,40.2842712 47,32 C47,23.7157288 40.2842712,17 32,17 C23.7157288,17 17,23.7157288 17,32 C17,40.2842712 23.7157288,47 32,47 Z M32,50 C41.9411255,50 50,41.9411255 50,32 C50,22.0588745 41.9411255,14 32,14 C22.0588745,14 14,22.0588745 14,32 C14,41.9411255 22.0588745,50 32,50 Z"></path>
        <path d="M32,61 C15.9837423,61 3,48.0162577 3,32 C3,15.9837423 15.9837423,3 32,3 C48.0162577,3 61,15.9837423 61,32 C61,48.0162577 48.0162577,61 32,61 Z M32,52 C20.954305,52 12,43.045695 12,32 C12,20.954305 20.954305,12 32,12 C43.045695,12 52,20.954305 52,32 C52,43.045695 43.045695,52 32,52 Z"></path>
        </svg>',
                                'color' => 'ffffff',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.MenuItem',
                            'data' => [
                              'html' => 'Tempor',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-top' => 'm-t-8',
                                'margin-bottom' => 'm-b-8',
                                'width' => 'cnt-w',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-wht-lnk',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-s p-l-12 p-r-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.MenuItem',
                            'data' => [
                              'html' => 'Consequat',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-top' => 'm-t-8',
                                'margin-bottom' => 'm-b-8',
                                'width' => 'cnt-w',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-wht-lnk',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-s p-l-12 p-r-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.MenuItem',
                            'data' => [
                              'html' => 'Curabitur',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-top' => 'm-t-8',
                                'margin-bottom' => 'm-b-8',
                                'width' => 'cnt-w',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-wht-lnk',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-s p-l-12 p-r-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.MenuItem',
                            'data' => [
                              'html' => 'Commodo',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-top' => 'm-t-8',
                                'margin-bottom' => 'm-b-8',
                                'width' => 'cnt-w',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-wht-lnk',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-s p-l-12 p-r-12',
                              ],
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
              [
                'type' => 'site.FooterColumn',
                'data' => [
                  'block_props' => [
                    'site-block-column' => [
                      'margin-left' => 'm-l-a m-l-0-mb m-l-0-tb',
                      'padding-top' => 'p-t-0',
                      'padding-bottom' => 'p-b-0',
                    ],
                    'site-block-column-wrapper' => [
                      'padding-top' => 'p-t-0',
                      'padding-bottom' => 'p-b-0',
                      'border-radius' => 'b-r-l',
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-5-lp st-4 st-12-tb',
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [
                        'is_horizontal' => true,
                        'is_complex' => 'no_complex',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-5">© 2025 Vestibulum accumsan</font>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #7',
                                  'value' => 't-7',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-12',
                                'margin-bottom' => 'm-b-0',
                                'margin-left' => 'm-l-11-mb',
                                'align' => 't-r',
                              ],
                            ],
                            'children' => [],
                          ],
                        ],
                      ],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ],

  ],
];
