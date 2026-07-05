<?php
$img_path = wa()->getAppStaticUrl('site') . 'img/blocks/page_templates/partners/';

$getCardRow = function ($item, $is_last = false) {
  return [
    'type' => 'site.Row',
    'data' => [
      'block_props' => ($is_last ? [
        'padding-top' => 'p-t-10',
        'border-color' => [
          'name' => '1-1',
          'value' => 'bd-tr-1',
          'type' => 'palette',
          'css' => '#0000001a',
        ],
        'border-width' => [
          'name' => _w('Width 1'),
          'value' => 'b-w-s',
          'unit' => 'px',
          'type' => 'library',
        ],
        'border-style' => [
          'value' => '',
          'type' => 'separate',
        ],
        'padding-bottom' => 'p-b-4',
      ] : [
        'padding-top' => 'p-t-10',
        'border-color' => [
          'name' => 'semi-transparent-black',
          'value' => 'br-b-opc-2',
          'type' => 'palette',
        ],
        'border-width' => [
          'name' => _w('Width 1'),
          'value' => 'b-w-s',
          'unit' => 'px',
          'type' => 'library',
        ],
        'border-style' => [
          'value' => 'b-d-b',
          'type' => 'separate',
        ],
        'padding-bottom' => 'p-b-4',
      ]),
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
                'type' => 'site.Heading',
                'data' => [
                  'html' => '<font color="" class="tx-bw-1">' . $item['name'] . '</font>',
                  'tag' => 'h3',
                  'block_props' => [
                    'align' => 't-l',
                    'font-header' => 't-hdn',
                    'font-size' => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library',],
                    'margin-bottom' => 'm-b-8',
                    'margin-top' => 'm-t-0',
                    'margin-right' => 'm-r-a',
                  ],
                ],
                'children' => [],
              ],
              [
                'type' => 'site.Heading',
                'data' => [
                  'html' => '<font color="" class="tx-bw-1">' . $item['value'] . '</font>',
                  'tag' => 'h3',
                  'block_props' => [
                    'align' => 't-r',
                    'font-header' => 't-hdn',
                    'font-size' => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library',],
                    'margin-bottom' => 'm-b-8',
                    'margin-top' => 'm-t-0',
                    'margin-left' => 'm-l-12',
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
$getCardColumn = function ($c) use ($getCardRow) {
  $rows = [];
  $count = count($c['items']);
  foreach ($c['items'] as $i => $item) {
    $rows[] = $getCardRow($item, $count - 1 === $i);
  }
  return [
    'type' => 'site.Column',
    'data' => [
      'elements' => [
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
      ],
      'column' => 'st-6 st-6-lp st-12-mb st-12-tb',
      'block_props' => [
        'site-block-column' => [
          'padding-bottom' => 'p-b-14',
          'padding-left' => 'p-l-clm',
          'padding-right' => 'p-r-clm',
          'padding-top' => 'p-t-14',
        ],
        'site-block-column-wrapper' => [
          'flex-align' => 'y-c',
          'padding-bottom' => 'p-b-12',
          'padding-left' => 'p-l-14',
          'padding-right' => 'p-r-14',
          'padding-top' => 'p-t-12',
          'border-radius' => 'b-r-l',
          'border-radius-corners' => [
            'type' => 'separate',
            'value' => '',
          ],
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
              ],
            ],
          ],
        ],
      ],
      'wrapper_props' => [],
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
            '' => array_merge([
              [
                'type' => 'site.Paragraph',
                'data' => [
                  'html' => '<b>' . $c['heading'] . '</b>',
                  'tag' => 'h3',
                  'block_props' => [
                    'align' => 't-l',
                    'font-header' => 't-rgl',
                    'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library',],
                    'margin-bottom' => 'm-b-12',
                    'margin-top' => 'm-t-0',
                  ],
                ],
                'children' => [],
              ],
            ], $rows),
          ],
        ],
      ],
    ],
  ];
};
$getCardColumns = function () use ($getCardColumn) {
  $data = [
    [
      'heading' => 'Minimus ordo',
      'items' => [
        ['name' => 'Primus ordo (probatio)', 'value' => 'a 15 000 mil.',],
        ['name' => 'Emptio semel facta', 'value' => 'a 30 000 mil.',],
        ['name' => 'Provisiones regulares', 'value' => 'a 50 000 mil./mens.',],
        ['name' => 'Numerus nominum', 'value' => 'sine limite',],
        ['name' => 'Assortimentum mixtum', 'value' => 'patet',],
      ]
    ],
    [
      'heading' => 'Solutio et traditio',
      'items' => [
        ['name' => 'Forma solutionis', 'value' => 'ratio, charta, pecunia numerata',],
        ['name' => 'Praesolutio', 'value' => '100% pro novis',],
        ['name' => 'Mora solutionis', 'value' => 'ad 30 dies',],
        ['name' => 'Tempus compositionis', 'value' => '1–3 dies operis',],
        ['name' => 'Ablatio propria', 'value' => 'II–VI feria, 9:00–18:00',],
      ]
    ],
    [
      'heading' => 'Auxilium nundinarium',
      'items' => [
        ['name' => 'Imagines et picturae', 'value' => 'gratis',],
        ['name' => 'Descriptiones et scripta', 'value' => 'gratis',],
        ['name' => 'Institutio ministrorum', 'value' => 'pro emptione a 300 000 mil.',],
        ['name' => 'Materia nundinaria', 'value' => 'ex petitione',],
        ['name' => 'Actiones communes', 'value' => 'tractantur',],
      ]
    ],
    [
      'heading' => 'Restitutio et permutatio',
      'items' => [
        ['name' => 'Merx vitiosa', 'value' => 'restitutio plena',],
        ['name' => 'Error generis', 'value' => 'permutatio intra 14 dies',],
        ['name' => 'Ex pacto provisionis', 'value' => 'secundum condiciones',],
        ['name' => 'Querelae', 'value' => 'tractatio ad 5 dies',],
      ]
    ],
  ];
  $cards = [];

  foreach ($data as $item) {
    $cards[] = $getCardColumn($item);
  }

  return $cards;
};

$ellipse_bg = [
  'type' => 'self_color',
  'value' => 'center center / contain no-repeat url(' . $img_path . 'ellipse.svg)',
  'name' => 'Self color',
  'layers' => [
    [
      'space' => 'contain no-repeat',
      'alignmentX' => 'center',
      'alignmentY' => 'center',
      'type' => 'image',
      'name' => 'Image',
      'css' => '',
      'file_name' => 'ellipse.svg',
      'file_url' => $img_path . 'ellipse.svg',
      'value' => 'center center / contain no-repeat url(' . $img_path . 'ellipse.svg)',
      'uuid' => 1,
    ],
  ],
  'uuid' => 3,
];
$getStepColumn = function ($item) use ($ellipse_bg) {
  return [
    'type' => 'site.Column',
    'data' => [
      'elements' => [
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
      ],
      'column' => 'st-12-mb st-3 st-3-lp st-6-tb',
      'indestructible' => false,
      'block_props' => [
        'site-block-column' => [
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-12',
          'padding-left' => 'p-l-clm',
          'padding-right' => 'p-r-clm',
        ],
        'site-block-column-wrapper' => [
          'flex-align' => 'y-c',
          'padding-top' => 'p-t-12 p-t-0-mb',
          'padding-bottom' => 'p-b-12 p-b-0-mb',
        ],
      ],
      'wrapper_props' => [
        'flex-align' => 'y-c',
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
                'type' => 'site.SubColumn',
                'data' => [
                  'block_props' => [
                    'padding-top' => 'p-t-14',
                    'padding-bottom' => 'p-b-14',
                    'padding-left' => 'p-l-17',
                    'padding-right' => 'p-r-17',
                    'margin-bottom' => 'm-b-12',
                  ],
                  'wrapper_props' => [
                    'justify-align' => 'j-s',
                    'flex-align' => 'y-c',
                  ],
                  'inline_props' => [
                    'background' => $ellipse_bg,
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
                              'html' => '<b><font color="" class="tx-bw-5">' . $item['step'] . '</font></b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => ['name' => 'Size #1', 'value' => 't-1', 'unit' => 'px', 'type' => 'library',],
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
                'type' => 'site.Paragraph',
                'data' => [
                  'html' => '<b>' . $item['title'] . '</b>',
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
                    'margin-bottom' => 'm-b-8',
                    'align' => 't-c',
                  ],
                ],
                'children' => [],
              ],
              [
                'type' => 'site.Paragraph',
                'data' => [
                  'html' => $item['text'],
                  'tag' => 'p',
                  'block_props' => [
                    'font-header' => 't-rgl',
                    'font-size' => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library',],
                    'margin-top' => 'm-t-0',
                    'margin-bottom' => 'm-b-12',
                    'align' => 't-c',
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
$getStepColumns = function () use ($getStepColumn) {
  $data = [
    ['step' => '01', 'title' => 'Petitionem mitte', 'text' => 'Formam in hac pagina comple aut curatorem officinae grossariae appella.',],
    ['step' => '02', 'title' => 'Condiciones componimus', 'text' => 'Curator intra duas horas respondebit, assortimentum recognoscet et oblationem propriam computabit.',],
    ['step' => '03', 'title' => 'Pactum signamus', 'text' => 'Pactum provisionis conficimus, rationes permutamus et aditum ad indicem grossarium aperimus.',],
    ['step' => '04', 'title' => 'Prima provisio', 'text' => 'Ordinem facis, nos componimus et tradimus cum tabulis clausurae completis.',],
  ];

  $columns = [];
  foreach ($data as $step) {
    $columns[] = $getStepColumn($step);
  }

  return $columns;
};

$getPartnerColumn = function (array $partner) use ($img_path) {
  return [
    'type' => 'site.Column',
    'data' => [
      'elements' => [
        'main' => 'site-block-column',
        'wrapper' => 'site-block-column-wrapper',
      ],
      'column' => 'st-4 st-4-lp st-12-mb st-6-tb',
      'indestructible' => false,
      'block_props' => [
        'site-block-column' => [
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-12',
          'padding-left' => 'p-l-clm',
          'padding-right' => 'p-r-clm',
        ],
        'site-block-column-wrapper' => [
          'flex-align' => 'y-c',
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-12',
        ],
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
                'type' => 'site.Image',
                'data' => [
                  'block_props' => [
                    'margin-bottom' => 'm-b-14',
                    'border-radius' => 'b-r-l',
                    'margin-right' => 'm-r-19',
                  ],
                  'image' => [
                    'type' => 'address',
                    'url_text' => $img_path . $partner['img'],
                  ],
                ],
                'children' => [],
              ],
              [
                'type' => 'site.Paragraph',
                'data' => [
                  'html' => '<b>' . $partner['name'] . '</b>',
                  'tag' => 'p',
                  'block_props' => [
                    'font-header' => 't-rgl',
                    'font-size' => ['name' => 'Size #5', 'value' => 't-5', 'unit' => 'px', 'type' => 'library',],
                    'margin-top' => 'm-t-0',
                    'margin-bottom' => 'm-b-8',
                    'align' => 't-l',
                  ],
                ],
                'children' => [],
              ],
              [
                'type' => 'site.Paragraph',
                'data' => [
                  'html' => $partner['job'],
                  'tag' => 'p',
                  'block_props' => [
                    'font-header' => 't-rgl',
                    'font-size' => ['name' => 'Size #7', 'value' => 't-7', 'unit' => 'px', 'type' => 'library',],
                    'margin-top' => 'm-t-0',
                    'margin-bottom' => 'm-b-12',
                    'align' => 't-l',
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
                              'image' => [
                                'type' => 'svg',
                                'svg_html' => '<svg viewBox="0 0 19 19" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_230_27981)"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.49785 0.489403L7.49453 4.12227C7.75271 4.592 7.59461 5.18175 7.13609 5.45936L5.75765 6.29395C5.29478 6.57419 5.13879 7.17178 5.40563 7.64251C6.10523 8.87662 6.93846 9.97711 7.90531 10.944C8.87217 11.9108 9.97266 12.7441 11.2068 13.4437C11.6775 13.7105 12.2751 13.5545 12.5554 13.0916L13.4114 11.6778C13.6802 11.2338 14.244 11.0692 14.7094 11.2989L17.8166 12.8326C18.2869 13.0647 18.498 13.6207 18.3002 14.1064L17.4293 16.2453C16.6961 18.0462 14.711 18.9913 12.8509 18.4251C9.49955 17.405 6.84563 15.9167 4.88911 13.9602C2.89131 11.9624 1.38171 9.23744 0.360304 5.78537C-0.169394 3.99514 0.609772 2.07657 2.2377 1.16259L4.13194 0.0990932C4.61351 -0.171282 5.22309 -6.92904e-05 5.49346 0.481507C5.49494 0.484132 5.4964 0.486764 5.49785 0.489403Z" /></g><defs><clipPath id="clip0_230_27981"><rect width="19" height="19" fill="white"/></clipPath></defs></svg>'
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-8',
                                'margin-right' => 'm-r-14',
                                'picture-size' => 'i-s',
                                'margin-top' => 'm-t-2',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '+1 234 567 89 10',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-4',
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
                            'type' => 'site.Image',
                            'data' => [
                              'image' => [
                                'type' => 'svg',
                                'svg_html' => '<svg viewBox="0 0 20 14" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_1174_362679)"><path fill-rule="evenodd" clip-rule="evenodd" d="M0 2L9.4855 7.6913C9.80219 7.88131 10.1978 7.88131 10.5145 7.6913L20 2V12C20 13.1046 19.1046 14 18 14H2C0.89543 14 0 13.1046 0 12V2ZM0 0.5C0 0.223858 0.223858 0 0.5 0L19.5 0C19.7761 0 20 0.223858 20 0.5C20 0.810199 19.8372 1.09765 19.5713 1.25725L10.5145 6.6913C10.1978 6.88131 9.80219 6.88131 9.4855 6.6913L0.428746 1.25725C0.162753 1.09765 0 0.810199 0 0.5Z" /></g><defs><clipPath id="clip0_1174_362679"><rect width="20" height="14" fill="white"/></clipPath></defs></svg>',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-8',
                                'margin-right' => 'm-r-14',
                                'picture-size' => 'i-s',
                                'margin-top' => 'm-t-2',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'fames@aliquip.nisi',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-4',
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
                            'type' => 'site.Image',
                            'data' => [
                              'image' => [
                                'type' => 'svg',
                                'svg_html' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17.2591 1.08545C17.9184 1.08437 18.6059 1.06374 19.2522 1.10596C20.5458 1.19058 21.752 1.97652 22.4065 3.0835C22.8957 3.91096 22.9806 4.64258 22.9876 5.57862L22.9866 11.4077L22.9886 13.1704C22.9892 13.7547 23.0087 14.4421 22.887 15.0093C22.7298 15.7672 22.3582 16.4641 21.8167 17.0171C21.3225 17.5145 20.4207 18.0406 19.7249 18.1499C18.9892 18.2651 18.0898 18.2327 17.3421 18.2329H14.1507C13.7309 18.234 12.3287 18.1833 12.0364 18.2925C11.7135 18.4131 11.4041 18.7966 11.1419 19.02C10.1992 19.826 9.26355 20.6402 8.33426 21.4614L7.36649 22.3267C7.10428 22.5641 6.84124 22.8167 6.55301 23.021C6.17948 23.2846 5.4323 23.1163 5.37332 22.606C5.31996 22.1439 5.34174 21.652 5.34207 21.1841L5.35477 18.2476C4.21744 18.2012 3.43004 17.9811 2.53543 17.2466C0.96956 15.9605 1.08454 14.3678 1.08328 12.5776L1.08231 9.56495L1.08328 6.84424C1.08263 5.2646 0.97367 3.84825 2.08426 2.5503C2.80991 1.70503 3.84157 1.18286 4.95242 1.09815C5.04823 1.09091 5.14444 1.08655 5.24051 1.08643C6.18622 1.07598 7.14918 1.08979 8.09695 1.09034L14.1604 1.08448L17.2591 1.08545ZM11.8968 8.15577C11.0999 8.2459 10.5262 8.96316 10.6136 9.76026C10.701 10.5573 11.4166 11.134 12.2141 11.0493C13.0155 10.9641 13.5951 10.2438 13.5071 9.44288C13.4191 8.64194 12.6976 8.06522 11.8968 8.15577ZM8.77957 9.36866C8.65064 8.5754 7.90298 8.03655 7.10965 8.16553C6.31657 8.29461 5.77861 9.04237 5.9075 9.83545C6.03651 10.6284 6.78342 11.1671 7.57645 11.0386C8.36972 10.9097 8.90843 10.1618 8.77957 9.36866ZM16.5452 8.16358C15.7526 8.26676 15.1932 8.99314 15.2962 9.78565C15.399 10.578 16.1248 11.1372 16.9173 11.0347C17.7103 10.932 18.2703 10.2055 18.1673 9.4126C18.0643 8.61987 17.3378 8.06062 16.5452 8.16358Z" fill="black"/></svg>',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-8',
                                'margin-right' => 'm-r-14',
                                'picture-size' => 'i-s',
                                'margin-top' => 'm-t-2',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'WhatsApp / Telegram',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => ['name' => 'Size #6', 'value' => 't-6', 'unit' => 'px', 'type' => 'library',],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-4',
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
$getPartnerColumns = function () use ($getPartnerColumn) {
  $data = [
    ['img' => 'woman-1@2x.jpg', 'name' => 'Julia Mara', 'job' => 'Praefecta officinae',],
    ['img' => 'man@2x.jpg', 'name' => 'Marcus Aemilius', 'job' => 'Curator sociorum',],
    ['img' => 'woman-2@2x.jpg', 'name' => 'Claudia Valeria', 'job' => 'Curator provinciarum',],
  ];
  $columns = [];

  foreach ($data as $item) {
    $columns[] = $getPartnerColumn($item);
  }

  return $columns;
};

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
          'scroll-fix' => 'psn-abs-tl f-w',
        ],
        'site-block-menu-wrapper' => [
          'flex-align-vertical' => 'x-c',
          'max-width' => 'cnt',
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomImagesWithDescription3',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-12-mb p-b-20',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-12-mb p-t-16',
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'padding-bottom' => 'p-b-12-mb p-b-12',
          'padding-top' => 'p-t-12-mb p-t-12',
          'max-width' => 'cnt',
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
        'site-block-columns' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'linear-gradient(#000000c9, #000000c9), center center / cover url(' . $img_path . 'top-bg.png)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#000000c9, #000000c9)',
                'name' => 'Self color',
                'css' => '#000000c9',
                'uuid' => 1,
              ],
              [
                'space' => 'cover',
                'alignmentX' => 'center',
                'alignmentY' => 'center',
                'type' => 'image',
                'name' => 'Image',
                'css' => '',
                'file_name' => 'top-bg.png',
                'file_url' => $img_path . 'top-bg.png',
                'value' => 'center center / cover url(' . $img_path . 'top-bg.png)',
                'uuid' => 2,
              ],
            ],
            'uuid' => 2,
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
                  'indestructible' => false,
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-12 st-12-lp st-12-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-20-mb p-b-28',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-30 p-t-28-mb',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-0',
                      'padding-top' => 'p-t-0',
                      'column-max-width' => 'fx-8',
                      'margin-left' => 'm-l-a',
                      'margin-right' => 'm-r-a',
                    ],
                  ],
                  'inline_props' => [
                    'site-block-column' => [],
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-w-opc-8">PROVISIONES IN GROSSO
</font>',
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
                              'html' => '<font color="" class="tx-wh">&nbsp;Cum negotiatoribus directo agimus</font><br>',
                              'tag' => 'h2',
                              'block_props' => [
                                'align' => 't-c',
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #2',
                                  'value' => 't-2',
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
                              'html' => '<font color="" class="tx-bw-8">Condiciones utiles retentoribus, distributoribus et emptoribus corporatis. Curator proprius, mora solutionis et tabulae clausurae.

</font>',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-10',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'y-j-cnt',
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
                                        'type' => 'site.Button',
                                        'data' => [
                                          'html' => 'Petitionem mittere<br>',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-12',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-wht-strk',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-l p-l-14 p-r-14',
                                            'margin-right' => 'm-r-8',
                                            'margin-left' => 'm-l-8',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Button',
                                        'data' => [
                                          'html' => 'Condiciones operis<br>',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-12',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-wht-strk',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-l p-l-14 p-r-14',
                                            'margin-left' => 'm-l-8',
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
                        ],
                      ],
                    ],
                  ],
                ],
              ],
              [
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
                  'column' => 'st-12-mb st-4 st-4-lp st-4-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-0',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-12 p-t-4-tb',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-0',
                      'padding-top' => 'p-t-0',
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
                              'html' => '<font color="" class="tx-wh"><b>850+</b></font>',
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
                                'margin-bottom' => 'm-b-8',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-wh"><b>Socii per totum Imperium</b></font>',
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
                                'margin-bottom' => 'm-b-8',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-w-opc-7">Cum retentoribus regionalibus</font><br>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-c',
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
                  'indestructible' => false,
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-4 st-4-lp st-4-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-0',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-12 p-t-4-tb',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-0',
                      'padding-top' => 'p-t-0',
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
                              'html' => '<font color="" class="tx-wh"><b>12
</b></font>',
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
                                'margin-bottom' => 'm-b-8',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-wh"><b>Anni in foro</b></font>',
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
                                'margin-bottom' => 'm-b-8',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-w-opc-7">Provisiones stabiles ab anno 2012</font><br>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-c',
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
                  'indestructible' => false,
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-4 st-4-lp st-4-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-0',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-12 p-t-4-tb',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-0',
                      'padding-top' => 'p-t-0',
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
                              'html' => '<font color="" class="tx-wh"><b>4 200
</b></font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #2',
                                  'value' => 't-2',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-8',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-wh"><b>Nomina in indice grossario</b></font>',
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
                                'margin-bottom' => 'm-b-8',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-w-opc-7">Semper in horreis praesto</font><br>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-c',
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
    'type' => 'site.CustomCta2',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-20 p-b-12-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-20 p-t-18-mb',
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-12-tb st-12 st-12-lp',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'border-radius' => 'b-r-l',
                      'border-radius-corners' => [
                        'type' => 'separate',
                        'value' => '',
                      ],
                    ],
                  ],
                  'wrapper_props' => [],
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-3">CONDICIONES MERCATURAE</font>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #7',
                                  'value' => 't-7',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-8',
                                'margin-top' => 'm-t-0',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-blc">Pacta societatis</font>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-16',
                                'margin-top' => 'm-t-0',
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
            ], $getCardColumns()),
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.Columns',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18 p-t-18-mb',
          'padding-bottom' => 'p-b-18 p-b-12-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-12 p-t-0-mb',
          'padding-bottom' => 'p-b-12 p-b-0-mb',
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-tb st-12-mb st-12 st-12-lp',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'INITIUM SOCIETATIS',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b>Quomodo nobiscum operari incipias</b><br>',
                              'tag' => 'p',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Processus coniunctionis 1 ad 3 dies operis requirit.',
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
                                'align' => 't-c',
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
            ], $getStepColumns()),
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomCta2',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-20',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-20',
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
                  'column' => 'st-12-mb st-12-tb st-12 st-12-lp',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'border-radius' => 'b-r-l',
                      'border-radius-corners' => [
                        'type' => 'separate',
                        'value' => '',
                      ],
                      'column-max-width' => 'fx-8',
                    ],
                  ],
                  'wrapper_props' => [],
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-3">TABULAE REQUISITAE

</font>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #7',
                                  'value' => 't-7',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-8',
                                'margin-top' => 'm-t-0',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Quid ad initium operis opus sit<br>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
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
                              'html' => 'Cum societatibus, mercatoribus singulis et aliis agimus. Index tabularum a forma constitutionis pendet.

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
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-6 st-6-lp st-12-mb st-12-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-14',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-14',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-14',
                      'padding-right' => 'p-r-14',
                      'padding-top' => 'p-t-12',
                      'border-radius' => 'b-r-l',
                      'border-radius-corners' => [
                        'type' => 'separate',
                        'value' => '',
                      ],
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
                          ],
                        ],
                      ],
                    ],
                  ],
                  'wrapper_props' => [],
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'value' => 'br-b-opc-2',
                                  'type' => 'palette',
                                ],
                                'border-width' => [
                                  'name' => _w('Width 1'),
                                  'value' => 'b-w-s',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'border-style' => [
                                  'value' => 'b-d-b',
                                  'type' => 'separate',
                                ],
                                'padding-bottom' => 'p-b-4',
                                'margin-bottom' => 'm-b-14',
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
                                            'margin-bottom' => 'm-b-12',
                                            'margin-right' => 'm-r-14',
                                            'margin-top' => 'm-t-4',
                                            'picture-size' => 'i-xxl',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M8.72111 2.04301C9.67625 2.02096 14.9964 1.89228 15.478 2.22316C15.713 2.68639 15.6231 4.02681 15.6131 4.59372C16.1048 4.59886 17.6437 4.53931 17.949 4.78048C18.1993 5.34959 18.1001 7.88853 18.0882 8.66206C18.9265 8.67382 22.8406 8.49146 23.2738 8.90469C23.4192 9.04366 23.4342 9.6966 23.445 9.90322C23.5562 12.0275 23.462 14.2054 23.4614 16.334C23.4609 18.2141 23.5651 20.1641 23.4331 22.0369C23.4181 22.2487 23.3994 22.6398 23.2425 22.7942C22.9155 23.117 16.0249 22.9641 15.0851 22.9619L5.68878 22.9648C4.24657 22.9663 1.96764 23.0354 0.595696 22.9067C0.51636 22.4354 0.506479 21.884 0.506701 21.4038C0.50906 17.5561 0.482516 13.7061 0.521225 9.85837C0.524175 9.56205 0.589501 9.1172 0.705777 8.84661C1.24211 8.58044 5.1363 8.64367 5.91381 8.69661C5.94729 7.87603 5.76863 5.41871 6.17807 4.76945C6.32863 4.53048 7.91647 4.60181 8.33276 4.60769C8.31875 3.86505 8.137 2.5158 8.72111 2.04301ZM9.72056 21.2707C10.4156 21.2788 13.6971 21.4016 14.207 21.2582C14.1585 20.6215 14.1483 19.8421 14.173 19.1965C14.2168 18.0502 14.1122 16.7708 14.2116 15.6399C12.8738 15.6333 11.0111 15.6921 9.72845 15.6333C9.73619 17.3494 9.80181 19.606 9.72056 21.2707ZM18.0789 13.4193C19.0088 13.4348 19.9389 13.4444 20.8689 13.4488C20.8898 12.5951 20.8956 11.7407 20.8862 10.887C20.285 10.9003 18.7064 10.9628 18.1729 10.8591C18.0301 11.6554 18.1455 12.612 18.0789 13.4193ZM13.2469 10.9709C13.2525 11.7547 13.2827 12.7047 13.2491 13.4752C13.5839 13.4833 15.3697 13.5473 15.5977 13.4635L15.6168 10.9944C14.833 10.9929 14.0286 11.0025 13.2469 10.9709ZM18.0744 17.5289C18.9898 17.5333 19.9415 17.5524 20.8537 17.5414C20.915 16.8723 20.8919 15.8987 20.8926 15.2017C20.2239 15.1995 18.713 15.2355 18.09 15.1693C18.1116 15.8715 18.1133 16.823 18.0744 17.5289ZM8.54076 13.462C9.26261 13.4929 9.98496 13.5061 10.7075 13.5032C10.7096 12.7091 10.6901 11.7819 10.7254 10.9988C10.0095 10.9966 9.26762 11.0047 8.55425 10.9782C8.5544 11.7297 8.58323 12.7311 8.54076 13.462ZM3.27234 10.84C3.22906 11.7128 3.23857 12.5605 3.24285 13.4341C4.06187 13.4385 5.12967 13.4179 5.92561 13.4833L5.93682 10.8981C5.28369 10.9135 3.86043 10.9878 3.27234 10.84ZM5.94699 15.209C5.32521 15.2061 3.79628 15.234 3.25022 15.1554L3.2371 17.5377C3.95754 17.5296 5.27625 17.5825 5.91529 17.523L5.94699 15.209ZM15.6058 9.06867L15.6109 7.02825C15.0593 7.02825 13.76 7.05546 13.2729 6.99075C13.2564 7.67898 13.2552 8.39 13.2327 9.07308C13.6149 9.0819 15.3095 9.13779 15.6058 9.06867ZM8.54157 9.05543C9.26548 9.08558 9.99005 9.09954 10.7146 9.0966C10.7102 8.41058 10.7108 7.72382 10.7163 7.0378C9.99905 7.03927 9.27728 7.04663 8.56082 7.02016C8.55603 7.67162 8.5662 8.41059 8.54157 9.05543Z" fill="black"/>
</svg>
',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.SubColumn',
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
                                                'is_complex' => 'no_complex',
                                              ],
                                              'children' => [
                                                '' => [
                                                  [
                                                    'type' => 'site.Paragraph',
                                                    'data' => [
                                                      'html' => '<b>Societas
</b>',
                                                      'tag' => 'p',
                                                      'block_props' => [
                                                        'font-header' => 't-hdn',
                                                        'font-size' => [
                                                          'name' => 'Size #5',
                                                          'value' => 't-5',
                                                          'unit' => 'px',
                                                          'type' => 'library',
                                                        ],
                                                        'margin-top' => 'm-t-0',
                                                        'margin-bottom' => 'm-b-4',
                                                        'align' => 't-l',
                                                      ],
                                                    ],
                                                    'children' => [],
                                                  ],
                                                  [
                                                    'type' => 'site.Paragraph',
                                                    'data' => [
                                                      'html' => 'Persona iuridica

',
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
                                                        'margin-bottom' => 'm-b-4',
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
                            'type' => 'site.List',
                            'data' => [
                              'html' => '<li>Tabula constitutionis societatis (numerus registri)&nbsp;</li><li>Tabula inscriptionis ad vectigalia (numerus fisci)&nbsp;</li><li>Statuta societatis (editio novissima)&nbsp;</li><li>Decretum de creatione directoris&nbsp;</li><li>Rationes argentariae et schedula societatis&nbsp;</li><li>Libellus signatoris (aut procuratio legati)<br></li>',
                              'tag' => 'ul',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-14',
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
                  'column' => 'st-6 st-6-lp st-12-mb st-12-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-14',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-14',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-14',
                      'padding-right' => 'p-r-14',
                      'padding-top' => 'p-t-12',
                      'border-radius' => 'b-r-l',
                      'border-radius-corners' => [
                        'type' => 'separate',
                        'value' => '',
                      ],
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
                  ],
                  'wrapper_props' => [],
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'value' => 'br-b-opc-2',
                                  'type' => 'palette',
                                ],
                                'border-width' => [
                                  'name' => _w('Width 1'),
                                  'value' => 'b-w-s',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'border-style' => [
                                  'value' => 'b-d-b',
                                  'type' => 'separate',
                                ],
                                'padding-bottom' => 'p-b-4',
                                'margin-bottom' => 'm-b-14',
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
                                            'margin-bottom' => 'm-b-12',
                                            'margin-right' => 'm-r-14',
                                            'margin-top' => 'm-t-4',
                                            'picture-size' => 'i-xxl',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M9.86722 12.9382C14.7849 12.7423 21.4916 13.1564 22.4773 19.4301C22.5644 19.9843 22.4013 21.9641 21.9785 22.2564C20.0954 23.558 16.5514 23.5603 14.362 23.6522C11.5535 23.7575 4.66014 23.823 2.04255 22.3258C1.68184 21.9916 1.53702 21.0943 1.51304 20.6691C1.23359 15.7073 5.49008 13.296 9.86722 12.9382Z" fill="black"/>
<path d="M11.1037 0.362346C14.2008 -0.0962555 17.086 2.02644 17.5499 5.1049C18.0137 8.18334 15.881 11.0534 12.7849 11.5173C9.68494 11.9817 6.79371 9.85816 6.32915 6.7759C5.86472 3.69368 8.00309 0.821511 11.1037 0.362346Z" fill="black"/>
</svg>
',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.SubColumn',
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
                                                'is_complex' => 'no_complex',
                                              ],
                                              'children' => [
                                                '' => [
                                                  [
                                                    'type' => 'site.Paragraph',
                                                    'data' => [
                                                      'html' => '<b>Mercator singulus</b>',
                                                      'tag' => 'p',
                                                      'block_props' => [
                                                        'font-header' => 't-hdn',
                                                        'font-size' => [
                                                          'name' => 'Size #5',
                                                          'value' => 't-5',
                                                          'unit' => 'px',
                                                          'type' => 'library',
                                                        ],
                                                        'margin-top' => 'm-t-0',
                                                        'margin-bottom' => 'm-b-4',
                                                        'align' => 't-l',
                                                      ],
                                                    ],
                                                    'children' => [],
                                                  ],
                                                  [
                                                    'type' => 'site.Paragraph',
                                                    'data' => [
                                                      'html' => 'Persona propria negotians

',
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
                                                        'margin-bottom' => 'm-b-4',
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
                            'type' => 'site.List',
                            'data' => [
                              'html' => '<li>Tabula registrationis mercatoris singuli&nbsp;</li><li>Numerus fisci personae privatae&nbsp;</li><li>Libellus mercatoris singuli&nbsp;</li><li>Rationes argentariae</li><li>Schedula rationis tributorum (si exstat)<br></li>',
                              'tag' => 'ul',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-14',
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
                  'column' => 'st-12-mb st-12-tb st-12 st-12-lp',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'border-radius' => 'b-r-l',
                      'border-radius-corners' => [
                        'type' => 'separate',
                        'value' => '',
                      ],
                      'column-max-width' => 'fx-9',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-10',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'y-j-cnt',
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
                                            'picture-size' => 'i-s',
                                            'margin-top' => 'm-t-4',
                                            'margin-right' => 'm-r-10',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-bw-4',
                                              'type' => 'palette',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="var(--bw-4)">
<path d="M3.3865 0.615734C4.66803 0.487969 4.70249 1.38183 4.70681 2.37843C4.73051 7.8893 4.71565 13.4017 4.72634 18.9126C4.72867 20.1022 4.80036 21.3878 4.61599 22.5552C4.56825 22.8571 4.23977 23.042 3.99685 23.1636C3.50133 23.1747 3.0571 23.2206 2.78103 22.7417C2.62319 22.4678 2.60623 22.0944 2.60036 21.7886C2.56839 20.1243 2.58072 18.4588 2.57985 16.7944L2.5779 7.66554C2.57824 5.75128 2.5243 3.31404 2.64724 1.36671C2.66834 1.03318 3.11469 0.768775 3.3865 0.615734Z"></path>
<path d="M9.13357 1.24952C14.1276 0.947786 16.5405 3.97559 21.5183 1.32277C21.5398 3.45927 21.5471 5.59633 21.5398 7.73292C21.5406 8.55765 21.6909 11.7585 21.281 12.4868C20.8903 13.181 18.923 13.3854 18.0886 13.4302C17.2621 13.4618 15.9006 13.4001 15.158 13.2241C12.1924 12.5213 9.26577 11.3702 6.41579 13.19C6.2358 13.3048 6.10214 13.2842 5.91091 13.2124C5.69256 12.4206 5.73446 3.87591 5.83181 2.82472C6.58796 1.74626 7.935 1.49163 9.13357 1.24952Z"></path>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Omnes tabulae per viam electronicam mitti possunt — imagine clara — ad curatorem tuum aut per formam petitionis.</font>',
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
            ],
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.Columns',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18 p-t-12-mb',
          'padding-bottom' => 'p-b-18 p-b-12-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-12 p-t-0-mb',
          'padding-bottom' => 'p-b-12 p-b-0-mb',
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-12 st-12-lp st-6-tb',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'flex-align-vertical' => 'a-c-s',
                      'padding-top' => 'p-t-18-tb',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'OFFICINA PROVISIONUM GROSSARIARUM',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Curatores tui',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Accede directe — sine officio publico et translationibus inter partes.',
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
                                'align' => 't-c',
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
            ], $getPartnerColumns()),
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomCrmForm',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-20-tb p-b-16-mb p-b-21',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-20-tb p-t-21 p-t-14-mb',
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'padding-bottom' => 'p-t-20 p-t-16-mb',
          'padding-top' => 'p-b-20 p-b-16-mb',
        ],
      ],
      'inline_props' => [
        'site-block-columns' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'linear-gradient(#000000a6, #000000a6), center center / cover url(' . $img_path . 'form-bg.jpg)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#000000a6, #000000a6)',
                'name' => 'Self color',
                'css' => '#000000a6',
              ],
              [
                'type' => 'image',
                'value' => 'center center / cover url(' . $img_path . 'form-bg.jpg)',
                'alignmentX' => 'center',
                'alignmentY' => 'center',
                'file_name' => 'form-bg.jpg',
                'file_url' => $img_path . 'form-bg.jpg',
                'space' => 'cover',
                'name' => 'Image',
              ],
            ],
            'uuid' => 5,
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
                  'column' => 'st-6 st-6-lp st-6-tb st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-bottom' => 'p-b-16 p-b-16-mb p-b-12-tb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-12-tb p-t-16 p-t-14-mb',
                      'margin-bottom' => 'm-b-14',
                    ],
                    'site-block-column-wrapper' => [
                      'border-radius' => 'b-r-l',
                      'background' => [
                        'name' => 'black and white',
                        'type' => 'palette',
                        'uuid' => 1,
                        'value' => 'bg-wh',
                        'layers' => [
                          [
                            'name' => 'black and white',
                            'type' => 'palette',
                            'uuid' => 1,
                            'value' => 'bg-wh',
                          ],
                        ],
                      ],
                      'column-max-width' => 'fx-9',
                      'flex-align' => 'y-c',
                      'margin-left' => 'm-l-a',
                      'margin-right' => 'm-r-a',
                      'padding-top' => 'p-t-14 p-t-12-tb',
                      'padding-bottom' => 'p-b-16 p-b-16-tb',
                      'padding-left' => 'p-l-16 p-l-14-tb',
                      'padding-right' => 'p-r-16 p-r-14-tb',
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
                              'html' => '<b>Postulatio de societate mercatoria<br></b>',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
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
                            'type' => 'site.Form.crm',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-0',
                              ],
                              'textarea_html' => '{$wa->crm->form(4)}',
                              'form_type' => 'crm',
                              'html' => '{$wa->crm->form(4)}',
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
                  'column' => 'st-6 st-6-lp st-6-tb st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-12-tb p-b-16',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-12-tb p-t-16',
                      'border-radius' => 'b-r-l',
                      'flex-align-vertical' => 'a-c-c',
                    ],
                    'site-block-column-wrapper' => [
                      'column-max-width' => 'fx-9',
                      'flex-align' => 'y-c',
                      'margin-left' => 'm-l-a',
                      'margin-right' => 'm-r-a',
                      'padding-top' => 'p-t-0-mb p-t-8-tb',
                      'padding-bottom' => 'p-b-0-mb p-b-8-tb',
                      'padding-left' => 'p-l-19 p-l-0-mb p-l-12-tb',
                      'padding-right' => 'p-r-0-mb p-r-8-tb p-r-19',
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
                              'html' => '<font color="" class="tx-w-opc-7">SOCIETATEM INIRE</font>',
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
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-wh"><b>Petitionem mitte et socius noster fias</b></font>',
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
                              ],
                              'tag' => 'h3',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-w-opc-7">Rationes tuas relinque et curator oblationem propriam parabit pro mercibus quas quaeris et provincia tua.
</font>',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-16',
                                'align' => 't-l',
                              ],
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-4',
                                'padding-bottom' => 'p-b-0',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
                              ],
                              'inline_props' => [],
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
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150" fill="var(--white)"><path d="M73.7084 119.66C77.754 121.168 77.5599 121.401 79.0961 125.079C78.8262 129.037 78.9767 128.908 77.0316 132.28C73.1525 131.354 73.1343 131.001 71.6371 127.757C71.7505 123.593 71.828 123.46 73.7084 119.66Z"></path><path d="M72.519 36.4493C75.3891 36.8571 75.3998 36.6235 77.5688 38.4454C80.2405 44.3918 78.9156 65.5321 78.809 73.4737C84.4625 78.803 90.7793 84.6066 95.9018 90.4259C98.7131 93.6189 98.1033 94.1814 97.808 97.7315C90.5702 98.2558 77.0675 81.8833 71.767 76.3917C71.7132 67.071 71.1401 44.6001 72.519 36.4493Z"></path><path d="M20.0961 71.3868C23.7075 71.3095 26.3556 70.7776 29.3236 72.6427C30.5538 75.7379 30.1418 74.8822 29.6147 78.6358C25.8117 78.6496 23.9357 78.9846 20.5336 77.5372C18.928 74.4715 19.392 75.551 20.0961 71.3868Z"></path><path d="M120.957 71.4015C124.434 71.3408 126.593 70.9735 129.742 72.4386C131.434 75.6941 131 74.417 130.388 78.6534C126.749 78.6231 124.123 79.0341 121.051 77.2198C119.717 73.7392 120.003 75.5923 120.957 71.4015Z"></path><path d="M72.5229 17.8556C75.4703 18.28 75.85 18.0549 78.0717 20.0616C79.6452 23.2109 78.7218 26.3668 78.2162 29.9435C73.8539 29.3459 75.5317 30.2484 72.5238 27.9737C71.0061 24.711 71.9693 21.4987 72.5229 17.8556Z"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M66.3246 7.46982C103.608 2.52591 137.839 28.7431 142.786 66.0284C147.731 103.314 121.518 137.549 84.2357 142.5C46.9505 147.449 12.7144 121.228 7.76798 83.9395C2.82274 46.6514 29.0395 12.4147 66.3246 7.46982ZM135.891 69.1788C132.663 35.7688 103.014 11.2672 69.5971 14.3936C36.036 17.5331 11.4071 47.3396 14.6498 80.8946C17.8917 114.45 47.7694 138.988 81.3109 135.645C114.709 132.314 139.119 102.589 135.891 69.1788Z"></path></svg>',
                                          ],
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-12',
                                            'picture-size' => 'i-l',
                                            'margin-right' => 'm-r-14',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-wh">Lun–Ven, hora 9:00 — 19:00&nbsp;</font>',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-4',
                                            'margin-bottom' => 'm-b-8',
                                            'align' => 't-l',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-4',
                                'padding-bottom' => 'p-b-0',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
                              ],
                              'inline_props' => [],
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
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150" fill="var(--white)"><path fill-rule="evenodd" clip-rule="evenodd" d="M57.793 6.40249C66.1975 6.31447 96.8127 3.86674 101.492 9.48355C102.828 14.6044 99.5334 19.1546 97.3096 23.6388C91.9202 34.5074 86.8851 45.5386 81.7812 56.546C88.8617 56.4942 112.943 54.9581 116.681 58.9503C116.613 59.9048 116.544 60.8592 116.477 61.8126C105.811 75.9882 79.0393 109.279 69.248 121.546C65.1534 126.909 54.3574 143.071 48.1904 143.394L46.8359 141.8C45.7993 133.243 58.0326 97.5284 61.2178 87.1222C50.2217 87.132 39.4521 87.1863 28.457 86.7589C29.922 81.1764 32.1729 75.3481 34.2305 69.9054C42.1919 48.8446 49.4022 27.2804 57.793 6.40249ZM38.4355 79.9865C45.9833 79.8975 61.4475 78.662 67.4082 82.1525C68.9367 84.6542 69.5209 86.0913 68.4541 89.3302C64.6206 100.967 61.0955 112.038 57.7803 123.827C63.1658 118.375 69.0938 110.345 73.9268 104.176C83.9203 91.6514 96.8737 76.3472 106.099 63.6349C99.2637 63.7181 79.599 64.4713 74.1016 62.7736C71.778 56.8252 90.0623 21.6716 93.6377 13.5021L62.5605 13.4767L38.4355 79.9865Z"></path></svg>',
                                          ],
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-12',
                                            'picture-size' => 'i-l',
                                            'margin-right' => 'm-r-14',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-wh">Intra 2 horas ad te perveniemus</font>',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-4',
                                            'margin-bottom' => 'm-b-8',
                                            'align' => 't-l',
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

  ['type' => 'site.FooterBottom2.2',]
];
