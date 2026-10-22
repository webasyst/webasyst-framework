<?php
$img_path = wa()->getAppStaticUrl('site') . 'img/blocks/page_templates/promo/black_friday/';

$getCard = function (array $c) use ($img_path) {
  return [
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
      'column' => 'st-2 st-6-mb st-4-tb st-2-lp',
      'block_props' => [
        'site-block-column' => [
          'padding-bottom' => 'p-b-14 p-b-12-mb',
          'padding-left' => 'p-l-clm',
          'padding-right' => 'p-r-clm',
          'padding-top' => 'p-t-14 p-t-12-mb',
        ],
        'site-block-column-wrapper' => [
          'border-radius' => 'b-r-l',
          'flex-align' => 'y-c',
          'padding-bottom' => 'p-b-8',
          'padding-left' => 'p-l-10',
          'padding-right' => 'p-r-10',
          'padding-top' => 'p-t-8',
        ],
      ],
      'inline_props' => [
        'site-block-column-wrapper' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'linear-gradient(360deg,  #04040400 60%,  #00000075 100%), center center / cover url(' . $img_path . $c['img'] . ')',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(360deg,  #04040400 60%,  #00000075 100%)',
                'name' => 'Self color',
                'css' => 'gradient',
                'gradient' => [
                  'degree' => 360,
                  'type' => 'linear-gradient',
                  'stops' => [
                    [
                      'color' => '#04040400',
                      'stop' => 60,
                    ],
                    [
                      'color' => '#00000075',
                      'stop' => 100,
                    ],
                  ],
                ],
              ],
              [
                'alignmentX' => 'center',
                'alignmentY' => 'center',
                'css' => '',
                'file_name' => $c['img'],
                'file_url' => $img_path . $c['img'],
                'name' => 'Image',
                'space' => 'cover',
                'type' => 'image',
                'value' => 'center center / cover url(' . $img_path . $c['img'] . ')',
              ],
            ],
            'uuid' => 1,
          ],
          'min-height' => [
            'name' => 'Fill parent',
            'value' => '100%',
            'type' => 'parent',
          ],
        ],
        'site-block-column' => [
          'min-height' => [
            'name' => 'Custom',
            'value' => '180px',
            'unit' => 'px',
            'type' => 'custom',
          ],
        ],
      ],
      'link_props' => [],
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
                  'html' => '<span class="tx-wh">' . $c['title'] . '</span>',
                  'tag' => 'h3',
                  'block_props' => [
                    'font-size' => [
                      'name' => 'Size #7',
                      'value' => 't-7',
                      'unit' => 'px',
                      'type' => 'library',
                    ],
                    'margin-bottom' => 'm-b-24',
                    'margin-top' => 'm-t-0',
                    'align' => 't-l',
                    'font-header' => 't-hdn',
                  ],
                ],
                'children' => [],
              ],
              [
                'type' => 'site.SubColumn',
                'data' => [
                  'block_props' => [
                    'padding-top' => 'p-t-2',
                    'padding-bottom' => 'p-b-2',
                    'padding-left' => 'p-l-8',
                    'padding-right' => 'p-r-8',
                    'border-radius' => 'b-r-l',
                    'margin-top' => 'm-t-a',
                    'margin-bottom' => 'm-b-4',
                  ],
                  'wrapper_props' => [
                    'justify-align' => 'j-s',
                  ],
                  'inline_props' => [
                    'background' => [
                      'type' => 'self_color',
                      'value' => 'linear-gradient(#ba2e64, #ba2e64)',
                      'name' => 'Self color',
                      'layers' => [
                        [
                          'type' => 'self_color',
                          'value' => 'linear-gradient(#ba2e64, #ba2e64)',
                          'name' => 'Self color',
                          'css' => '#ba2e64',
                          'uuid' => 1,
                        ],
                      ],
                      'uuid' => 1,
                    ],
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-wh">' . $c['discount'] . '</font>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #8',
                                  'value' => 't-8',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-0',
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
  ];
};
$getCards = function () use ($getCard) {
  $data = [
    ['img' => 'cream.jpg', 'title' => 'Cura Cutis', 'discount' => 'Ad −60%',],
    ['img' => 'perfume.jpg', 'title' => 'Odores', 'discount' => 'Ad −70%',],
    ['img' => 'lipstick.jpg', 'title' => 'Ornatus', 'discount' => 'Ad −50%',],
    ['img' => 'shampoo.jpg', 'title' => 'Cura Capilli', 'discount' => 'Ad −45%',],
    ['img' => 'spf-cream.jpg', 'title' => 'Tutela Solis', 'discount' => 'Ad −70%',],
    ['img' => 'tools.jpg', 'title' => 'Instrumenta', 'discount' => 'Ad −65%',],
  ];
  $cards = [];

  foreach ($data as $item) {
    $cards[] = $getCard($item);
  }

  return   [
    'type' => 'site.CustomCategories2',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-18 p-b-16-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-18 p-t-16-mb',
          'background' => [
            'name' => 'grey shades',
            'value' => 'bg-bw-1',
            'type' => 'palette',
            'uuid' => 1,
            'layers' => [
              [
                'name' => 'grey shades',
                'value' => 'bg-bw-1',
                'type' => 'palette',
                'uuid' => 1,
              ],
            ],
          ],
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'padding-bottom' => 'p-b-0-mb p-b-12',
          'padding-top' => 'p-t-0-mb p-t-12',
          'padding-left' => 'p-l-0-tb',
          'padding-right' => 'p-r-0-tb',
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
          'children' => ['' => $cards]
        ]
      ]
    ]
  ];
};

$getProductColumn = function (array $p) use ($img_path) {
  return [
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
      'column' => 'st-3 st-3-lp st-6-tb st-12-mb',
      'block_props' => [
        'site-block-column' => [
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-12',
          'padding-left' => 'p-l-clm',
          'padding-right' => 'p-r-clm',
        ],
        'site-block-column-wrapper' => [
          'flex-align' => 'y-c',
          'padding-top' => 'p-t-12 p-t-0-tb',
          'padding-bottom' => 'p-b-12 p-b-0-tb',
        ],
      ],
      'inline_props' => [
        'site-block-column-wrapper' => [
          'min-height' => [
            'name' => 'Fill parent',
            'value' => '100%',
            'type' => 'parent',
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
                    'url_text' => $img_path . $p['img'],
                  ],
                  'block_props' => [
                    'margin-bottom' => 'm-b-12',
                    'border-radius' => 'b-r-m',
                  ],
                ],
                'children' => [],
              ],
              [
                'type' => 'site.SubColumn',
                'data' => [
                  'block_props' => [
                    'padding-top' => 'p-t-1',
                    'padding-bottom' => 'p-b-2',
                    'border-radius' => 'b-r-m',
                    'padding-left' => 'p-l-8',
                    'padding-right' => 'p-r-8',
                    'margin-bottom' => 'm-b-10',
                  ],
                  'wrapper_props' => [
                    'justify-align' => 'j-s',
                  ],
                  'inline_props' => [
                    'background' => [
                      'type' => 'self_color',
                      'value' => 'linear-gradient(#ff529552, #ff529552)',
                      'name' => 'Self color',
                      'layers' => [
                        [
                          'type' => 'self_color',
                          'value' => 'linear-gradient(#ff529552, #ff529552)',
                          'name' => 'Self color',
                          'css' => '#ff529552',
                          'uuid' => 1,
                        ],
                      ],
                      'uuid' => 1,
                    ],
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="#ba2e64">' . $p['discount'] . '</font><br>',
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
                                'margin-bottom' => 'm-b-0',
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
                'type' => 'site.Heading',
                'data' => [
                  'html' => '<font color="" class="tx-wh">' . $p['title'] . '</font>',
                  'tag' => 'h3',
                  'block_props' => [
                    'font-header' => 't-hdn',
                    'align' => 't-l',
                    'margin-top' => 'm-t-0',
                    'font-size' => [
                      'name' => 'Size #6',
                      'value' => 't-6',
                      'unit' => 'px',
                      'type' => 'library',
                    ],
                    'margin-bottom' => 'm-b-8',
                  ],
                ],
                'children' => [],
              ],
              [
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="#ba2e64">' . $p['price'] . '</font></b>',
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
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                                'margin-right' => 'm-r-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-5"><strike>' . $p['compare_price'] . '</strike></font>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-4',
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
                'type' => 'site.Button',
                'data' => [
                  'html' => 'Eme',
                  'tag' => 'a',
                  'block_props' => [
                    'margin-bottom' => 'm-b-12',
                    'button-style' => [
                      'name' => 'Palette',
                      'value' => 'btn-wht',
                      'type' => 'palette',
                    ],
                    'button-size' => 'inp-m p-l-13 p-r-13',
                    'full-width' => 'f-w',
                    'margin-top' => 'm-t-a',
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
};

$getProductsBlock = function (string $heading, array $data) use ($getProductColumn) {
  $products = [];
  foreach ($data as $p) {
    $products[] = $getProductColumn($p);
  }

  return [
    'type' => 'site.CustomProducts',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18 p-t-16-mb',
          'padding-bottom' => 'p-b-18 p-b-16-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'background' => [
            'name' => 'grey shades',
            'value' => 'bg-bw-1',
            'type' => 'palette',
            'uuid' => 1,
            'layers' => [
              [
                'name' => 'grey shades',
                'value' => 'bg-bw-1',
                'type' => 'palette',
                'uuid' => 1,
              ],
            ],
          ],
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-0-tb',
          'padding-bottom' => 'p-b-0-tb',
          'max-width' => 'cnt',
          'flex-align' => 'y-c',
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
                      'flex-align-vertical' => 'a-c-c',
                      'padding-top' => 'p-t-0',
                      'padding-bottom' => 'p-b-0',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-6',
                                'padding-bottom' => 'p-b-6',
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
                                        'type' => 'site.Heading',
                                        'data' => [
                                          'html' => '<font color="" class="tx-wh">' . $heading . '</font>',
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
                                            'margin-bottom' => 'm-b-12',
                                            'align' => 't-l',
                                            'margin-right' => 'm-r-a',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Button',
                                        'data' => [
                                          'html' => 'Vide omnia<br>',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-10',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-wht-trnsp',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-m p-l-13 p-r-13',
                                            'margin-left' => 'm-l-0-mb m-l-12-tb m-l-18 m-l-16-lp',
                                            'margin-top' => 'm-t-8',
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
            ], $products)
          ]
        ]
      ]
    ]
  ];
};

$products_block_data1 = [
  ['img' => 'cosmetic-product-1.jpg', 'title' => 'Liquor colorans “Cutis Nuda”', 'discount' => '−60%', 'price' => '2 990 den.', 'compare_price' => '7 990 den.',],
  ['img' => 'cosmetic-product-2.jpg', 'title' => 'Nebula fixans “Velum Recens”', 'discount' => '−50%', 'price' => '1 490 den.', 'compare_price' => '2 980 den.',],
  ['img' => 'cosmetic-product-3.jpg', 'title' => 'Rubor cum penicillo “Color Vivus”', 'discount' => '−35%', 'price' => '6 900 den.', 'compare_price' => '10 600 den.',],
  ['img' => 'cosmetic-product-4.jpg', 'title' => 'Fuco ciliari “Ater Longus”', 'discount' => '−40%', 'price' => '890 den.', 'compare_price' => '1 490 den.',],
];

$products_block_data2 = [
  ['img' => 'toner.jpg', 'title' => 'Tonicarum "Rosa Viva"', 'discount' => '−65%', 'price' => '12 490 den.', 'compare_price' => '18 990 den.',],
  ['img' => 'face-oil.jpg', 'title' => 'Serum "Aurum Drop"', 'discount' => '−40%', 'price' => '2 490 den.', 'compare_price' => '4 150 den.',],
  ['img' => 'eye-cream.jpg', 'title' => 'Unguentum oculare “Lux Velutina”', 'discount' => '−55%', 'price' => '999 den.', 'compare_price' => '2 220 den.',],
  ['img' => 'mask.jpg', 'title' => 'Persona textilis “Nox Candida”', 'discount' => '−70%', 'price' => '3 490 den.', 'compare_price' => '11 600 den.',],
];

return [
  [
    'type' => 'site.MenuT2',
    'replace_data' => [
      'block_props' => [
        'site-block-menu' => [
          'padding-top' => 'p-t-6',
          'padding-bottom' => 'p-b-6',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'background' => [
            'name' => 'grey shades',
            'value' => 'bg-bw-1',
            'type' => 'palette',
            'uuid' => 1,
            'layers' => [
              [
                'name' => 'grey shades',
                'value' => 'bg-bw-1',
                'type' => 'palette',
              ],
            ],
          ],
        ],
        'site-block-menu-wrapper' => [
          'flex-align-vertical' => 'x-c',
          'max-width' => 'cnt',
        ],
      ],
    ]
  ],

  [
    'type' => 'site.CustomGallery4',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-0',
          'padding-bottom' => 'p-b-0',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'background' => [
            'name' => 'grey shades',
            'value' => 'bg-bw-1',
            'type' => 'palette',
            'uuid' => 1,
            'layers' => [
              [
                'name' => 'grey shades',
                'value' => 'bg-bw-1',
                'type' => 'palette',
                'uuid' => 1,
              ],
            ],
          ],
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'border-radius' => 'b-r-l',
          'padding-top' => 'p-t-0',
          'padding-bottom' => 'p-b-0 p-b-0-mb',
          'padding-left' => 'p-l-14 p-l-10-mb',
          'padding-right' => 'p-r-14 p-r-10-mb',
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
                  'column' => 'st-12-mb st-5 st-12-tb st-5-lp',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-top' => 'p-t-16',
                      'padding-right' => 'p-r-clm',
                      'padding-bottom' => 'p-b-16',
                      'padding-left' => 'p-l-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
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
                              'html' => '<font color="" class="tx-wh">ACTIO AD DIEM 31.11 &nbsp;&nbsp;</font>',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="#ba2e64">Dies Atra. Remissiones ad&nbsp;−70%</font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #2',
                                  'value' => 't-2',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-15',
                                'align' => 't-c',
                                'line-height' => 't-lh-xs',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-5"> Electa unguenta, odores et ornatus — pretiis quae semel anno fiunt.

</font>',
                              'block_props' => [
                                'align' => 't-c',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #7',
                                  'value' => 't-7',
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
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'Rape remissiones<br>',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-wht-trnsp',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-m p-l-13 p-r-13',
                                'margin-top' => 'm-t-12',
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
                  'column' => 'st-12-mb st-7 st-8-tb st-7-lp',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-e',
                      'padding-right' => 'p-r-clm',
                      'padding-left' => 'p-l-clm',
                      'padding-top' => 'p-t-12',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'inline_props' => [
                    'site-block-column-wrapper' => [
                      'min-height' => [
                        'name' => 'Fill parent',
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
                              'block_props' => [],
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path . 'woman.jpg',
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
    'type' => 'site.CustomBanner4',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'color' => 'f-w',
        ],
        'site-block-columns-wrapper' => [
          'border-radius' => 'b-r-l',
          'flex-align' => 'y-c',
        ],
      ],
      'inline_props' => [
        'site-block-columns' => [],
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
                  'block_props' => [
                    'site-block-columns' => [],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
                  ],
                  'inline_props' => [
                    'site-block-column' => [
                      'min-height' => [
                        'name' => 'Custom',
                        'value' => '54px',
                        'unit' => 'px',
                        'type' => 'custom',
                      ],
                      'background' => [
                        'type' => 'self_color',
                        'value' => 'center center url(' . $img_path . 'BF.svg), linear-gradient(#ba2e64, #ba2e64)',
                        'name' => 'Self color',
                        'layers' => [
                          [
                            'alignmentX' => 'center',
                            'alignmentY' => 'center',
                            'css' => '',
                            'file_name' => 'BF.svg',
                            'file_url' => $img_path . 'BF.svg',
                            'name' => 'Image',
                            'space' => 'contain',
                            'type' => 'image',
                            'value' => 'center center url(' . $img_path . 'BF.svg)',
                          ],
                          [
                            'type' => 'self_color',
                            'value' => 'linear-gradient(#ba2e64, #ba2e64)',
                            'name' => 'Self color',
                            'css' => '#ba2e64',
                          ],
                        ],
                        'uuid' => 1,
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
  $getCards(),
  $getProductsBlock('Maxime Vendita', $products_block_data1),
  $getProductsBlock('Ultima Occasio', $products_block_data2),
  [
    'type' => 'site.CustomGallery4',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18 p-t-16-mb',
          'padding-bottom' => 'p-b-22 p-b-20-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'background' => [
            'name' => 'grey shades',
            'value' => 'bg-bw-1',
            'type' => 'palette',
            'uuid' => 1,
            'layers' => [
              [
                'name' => 'grey shades',
                'value' => 'bg-bw-1',
                'type' => 'palette',
                'uuid' => 1,
              ],
            ],
          ],
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'border-radius' => 'b-r-l',
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-18-mb p-b-12',
          'padding-left' => 'p-l-14 p-l-10-mb',
          'padding-right' => 'p-r-14 p-r-10-mb',
        ],
      ],
      'wrapper_props' => [
        'justify-align' => 'y-j-cnt',
      ],
      'elements' => [
        'main' => 'site-block-columns',
        'wrapper' => 'site-block-columns-wrapper',
      ],
      'inline_props' => [
        'site-block-columns-wrapper' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'linear-gradient(#d95689, #d95689)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#d95689, #d95689)',
                'name' => 'Self color',
                'css' => '#d95689',
                'uuid' => 1,
              ],
            ],
            'uuid' => 1,
          ],
        ],
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
                  'column' => 'st-9 st-9-lp st-12-mb st-8-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-top' => 'p-t-11',
                      'padding-right' => 'p-r-clm',
                      'padding-bottom' => 'p-b-0',
                      'padding-left' => 'p-l-clm',
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
                              'html' => '<font color="" class="tx-blc">OBLATIO SINGULARIS</font>',
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
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-blc">−15% amplius remissum signo</font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #2',
                                  'value' => 't-2',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-16',
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
                  'column' => 'st-3 st-3-lp st-12-mb st-4-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-e',
                      'padding-right' => 'p-r-clm',
                      'padding-left' => 'p-l-clm',
                      'padding-top' => 'p-t-12',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'inline_props' => [
                    'site-block-column-wrapper' => [
                      'min-height' => [
                        'name' => 'Fill parent',
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
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-8',
                                'padding-bottom' => 'p-b-8',
                                'full-width' => 'f-w',
                                'margin-bottom' => 'm-b-12',
                                'border-radius-corners' => [
                                  'value' => '',
                                  'type' => 'all',
                                ],
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'value' => 'br-b-opc-6',
                                  'type' => 'palette',
                                ],
                                'border-width' => [
                                  'name' => _w('Width 1'),
                                  'value' => 'b-w-s',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'border-style' => [
                                  'value' => 'b-d-a',
                                  'type' => 'all',
                                ],
                                'border-radius' => 'b-r-l',
                                'margin-top' => 'm-t-a',
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
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-blc">FRIDAY60</font>',
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
                                            'align' => 't-c',
                                            'margin-bottom' => 'm-b-0',
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
          ],
        ],
      ],
    ],
  ],

  [
    'type' => 'site.FooterBottom2.2',
    'replace_data' => [
      'block_props' => [
        'site-block-footer' => [
          'padding-top' => 'p-t-10',
          'padding-bottom' => 'p-b-10',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'background' => [
            'name' => 'grey shades',
            'value' => 'bg-bw-1',
            'type' => 'palette',
            'uuid' => 1,
            'layers' => [
              [
                'name' => 'grey shades',
                'value' => 'bg-bw-1',
                'type' => 'palette',
                'uuid' => 1,
              ],
            ],
          ],
          'border-color' => [
            'name' => 'semi-transparent-white',
            'value' => 'br-w-opc-3',
            'type' => 'palette',
          ],
          'border-width' => [
            'name' => _w('Width 1'),
            'value' => 'b-w-s',
            'unit' => 'px',
            'type' => 'library',
          ],
          'border-style' => [
            'value' => 'b-d-t',
            'type' => 'separate',
          ],
        ],
        'site-block-footer-wrapper' => [
          'padding-top' => 'p-t-10',
          'padding-bottom' => 'p-b-10',
          'flex-align-vertical' => 'x-c',
          'max-width' => 'cnt',
        ],
      ],
    ]
  ],
];
