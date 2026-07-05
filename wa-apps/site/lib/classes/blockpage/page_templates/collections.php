<?php
$root_img_path = wa()->getAppStaticUrl('site').'img/blocks/';
$img_path = $root_img_path.'page_templates/collections/1/';

return [
  ['type' => 'site.MenuT1',],
  [
    'type' => 'site.CustomHero2',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18 p-t-12-mb',
          'padding-bottom' => 'p-b-18',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-12 p-t-0-mb',
          'padding-bottom' => 'p-b-12 p-b-0-mb',
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
        ],
      ],
      'inline_props' => [
        'site-block-columns' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'center center / cover url('.$img_path.'bg.png)',
            'name' => 'Self color',
            'layers' => [
              [
                'space' => 'cover',
                'alignmentX' => 'center',
                'alignmentY' => 'center',
                'type' => 'image',
                'name' => 'Image',
                'css' => '',
                'file_name' => 'bg.png',
                'file_url' => $img_path.'bg.png',
                'value' => 'center center / cover url('.$img_path.'bg.png)',
              ],
            ],
            'uuid' => 1,
          ],
          'min-height' => [
            'name' => 'Browser height',
            'value' => '100vh',
            'type' => 'browser',
          ],
        ],
        'site-block-columns-wrapper' => [],
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
                  'column' => 'st-6-lp st-12-mb st-12-tb st-5',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-bottom' => 'p-b-10-mb',
                      'padding-top' => 'p-t-0-mb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'border-radius' => 'b-r-l',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-12',
                      'padding-top' => 'p-t-8',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
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
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
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
                                'padding-left' => 'p-l-10',
                                'padding-right' => 'p-r-10',
                                'padding-top' => 'p-t-6',
                                'padding-bottom' => 'p-b-6',
                                'border-radius-corners' => [
                                  'value' => '',
                                  'type' => 'separate',
                                ],
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
                                        'type' => 'site.Heading',
                                        'data' => [
                                          'html' => '<font class="tx-blc" color="">Autumnus 2026</font>',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #4',
                                              'value' => 't-4',
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
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
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
                                'padding-left' => 'p-l-10',
                                'padding-right' => 'p-r-10',
                                'padding-top' => 'p-t-6',
                                'padding-bottom' => 'p-b-10',
                                'border-radius-corners' => [
                                  'value' => '',
                                  'type' => 'separate',
                                ],
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
                                        'type' => 'site.Heading',
                                        'data' => [
                                          'html' => '<font color="" class="tx-blc">Conclave domesticum</font>',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #4',
                                              'value' => 't-4',
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
          'padding-top' => 'p-t-20-tb p-t-12-mb p-t-26',
          'padding-bottom' => 'p-b-20-tb p-b-12-mb p-b-26',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-16-mb',
          'padding-bottom' => 'p-b-16-mb',
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
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
                      'margin-left' => 'm-l-a',
                      'margin-right' => 'm-r-a',
                      'padding-top' => 'p-t-12-tb p-t-0-mb',
                      'padding-bottom' => 'p-b-12-tb p-b-0-mb',
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
                              'html' => '<span class="tx-wh"><font color="" class="tx-blc">Conclave ad vesperas agendas
</font></span>',
                              'tag' => 'h2',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<span class="tx-wh"><font color="" class="tx-blc">Omnia ad unam cameram composita sunt — a supellectile principali usque ad parva ornamenta. Inter se apte conveniunt et modum sumptuum servant, sine cura postea.</font></span>',
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
                                'margin-bottom' => 'm-b-18 m-b-12-mb',
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
            ],
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomVideo',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-20',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-20',
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
                  'column' => 'st-12-mb st-12-tb st-9-lp st-9',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-12',
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
                            'type' => 'site.Image',
                            'data' => [
                              'block_props' => [
                                'border-radius' => 'b-r-l',
                              ],
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path.'brisa.jpg',
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
                  'column' => 'st-12-mb st-12-tb st-3-lp st-3',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-s',
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-4">Novitas</font>',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #7',
                                  'value' => 't-7',
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
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Lectus mollis «Brisa»
',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'h3',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Lectus mollis forma simplici et textili neutro, qui facile in quodlibet interior inseritur. Ad quietem cotidianam aptus est et per tempus aptus manet.
',
                              'block_props' => [
                                'align' => 't-l',
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
                              'tag' => 'h3',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-bottom' => 'p-b-18',
                                'padding-left' => 'p-l-0',
                                'padding-right' => 'p-r-0',
                                'padding-top' => 'p-t-18',
                                'margin-top' => 'm-t-a',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                              ],
                              'inline_props' => [
                                'background' => [
                                  'layers' => [
                                    [
                                      'alignmentX' => 'center',
                                      'alignmentY' => 'center',
                                      'css' => '',
                                      'file_name' => 'sticker.svg',
                                      'file_url' => $root_img_path.'video/sticker.svg',
                                      'name' => 'Image',
                                      'space' => 'contain no-repeat',
                                      'type' => 'image',
                                      'value' => 'center center / contain no-repeat url('.$root_img_path.'video/sticker.svg)',
                                    ],
                                  ],
                                  'name' => 'Self color',
                                  'type' => 'self_color',
                                  'value' => 'center center / contain no-repeat url('.$root_img_path.'video/sticker.svg)',
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
                                          'html' => '<font color="" class="tx-wh"><b>19.993 den.</b></font>',
                                          'block_props' => [
                                            'align' => 't-c',
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-0',
                                            'margin-top' => 'm-t-10',
                                            'margin-left' => 'm-l-14',
                                            'margin-right' => 'm-r-14',
                                          ],
                                          'tag' => 'p',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-6"><strike>29.490 den.</strike></font>',
                                          'block_props' => [
                                            'align' => 't-c',
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #8',
                                              'value' => 't-8',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-10',
                                            'margin-top' => 'm-t-0',
                                            'margin-left' => 'm-l-10',
                                            'margin-right' => 'm-r-10',
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
  [
    'type' => 'site.CustomProducts3',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18',
          'padding-bottom' => 'p-b-18',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-12 p-t-0-mb',
          'padding-bottom' => 'p-b-12 p-b-0-mb',
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
            '' => [
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
                  'column' => 'st-9-lp st-9 st-12-mb st-7-tb',
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
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Textilia ornamenta',
                              'tag' => 'h1',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-8',
                                'max-width' => 'fx-9',
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
                    'flex-align' => 'y-l',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-3 st-3-lp st-12-mb st-5-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-top' => 'p-t-8',
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
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<span class="tx-bw-1"><u>Omnia ornamenta</u>&nbsp;→&nbsp;</span>',
                              'tag' => 'h1',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'margin-left' => 'm-l-a m-l-0-mb',
                                'margin-bottom' => 'm-b-12',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
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
                  'column' => 'st-12-mb st-4 st-4-lp st-4-tb',
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
                                'url_text' => $img_path.'soft-form.png',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Sella mollis rotunda «Soft Form»',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<b>30 000 den.</b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
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
                    'flex-align' => 'y-l',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-4 st-4-lp st-4-tb',
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
                                'url_text' => $img_path.'warm-fold.png',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Stragulum e gossypio «Warm Fold»',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<b>9 000 den.</b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
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
                    'flex-align' => 'y-l',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-4 st-4-lp st-4-tb',
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
                                'url_text' => $img_path.'calm-shape.png',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Pulvinus ornatus «Calm Shape»',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<b>2000 den.</b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
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
    'type' => 'site.CustomCategories4',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-16-mb p-b-22',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-16-mb p-t-18',
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
                  'column' => 'st-12-mb st-3 st-3-lp st-3-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-14',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-14',
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
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                              ],
                              'link_props' => [
                                'href' => '/',
                                'data-value' => 'internal-link',
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
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-10',
                                            'border-radius' => 'b-r-l',
                                          ],
                                          'image' => [
                                            'type' => 'address',
                                            'url_text' => $img_path.'oak-round.jpg',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => 'Mensa parva rotunda «Oak Round»',
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
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Heading',
                                        'data' => [
                                          'html' => '<b>15 000&nbsp;den.</b>',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
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
                          [
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                              ],
                              'link_props' => [
                                'href' => '/',
                                'data-value' => 'internal-link',
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
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-10',
                                            'border-radius' => 'b-r-l',
                                          ],
                                          'image' => [
                                            'type' => 'address',
                                            'url_text' => $img_path.'soft-clay.jpg',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => 'Vas fictile «Soft Clay»',
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
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Heading',
                                        'data' => [
                                          'html' => '<b>20 000&nbsp;den.</b>',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
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
                  'column' => 'st-12-mb st-9 st-9-lp st-9-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-14',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-14',
                    ],
                    'site-block-column-wrapper' => [
                      'border-radius' => 'b-r-l',
                      'border-radius-corners' => [
                        'value' => '',
                        'type' => 'separate',
                      ],
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-25-mb',
                      'padding-bottom' => 'p-b-25-mb',
                    ],
                  ],
                  'inline_props' => [
                    'site-block-column-wrapper' => [
                      'min-height' => [
                        'name' => 'Parent height',
                        'type' => 'parent',
                        'value' => '100%',
                      ],
                      'background' => [
                        'type' => 'self_color',
                        'value' => 'center center / cover url('.$img_path.'vas-et-candelae.png)',
                        'name' => 'Self color',
                        'layers' => [
                          [
                            'space' => 'cover',
                            'alignmentX' => 'center',
                            'alignmentY' => 'center',
                            'type' => 'image',
                            'name' => 'Image',
                            'css' => '',
                            'file_name' => 'vas-et-candelae.png',
                            'file_url' => $img_path.'vas-et-candelae.png',
                            'value' => 'center center / cover url('.$img_path.'vas-et-candelae.png)',
                            'uuid' => 1,
                          ],
                        ],
                        'uuid' => 1,
                      ],
                    ],
                  ],
                  'link_props' => [
                    'site-block-column' => [
                      'href' => '/',
                      'data-value' => 'internal-link',
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
  [
    'type' => 'site.CustomImagesWithDescription3',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-0-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-20 p-t-16-mb',
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'padding-bottom' => 'p-b-0-mb',
          'padding-top' => 'p-t-20 p-t-0-mb',
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
                  'indestructible' => false,
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-6-lp st-6-tb st-12-mb st-6',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-0',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-0',
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
                              'html' => 'Interior levis ad tempus tepidum',
                              'tag' => 'h3',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
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
                    'flex-align' => 'y-l',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-6-lp st-6-tb st-12-mb st-5',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-0',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-12 p-t-4-tb',
                      'margin-left' => 'm-l-a',
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
                              'html' => 'Texturae clarae, materiae naturales et formae tranquillae ad domum, in qua multum aeris et luminis est. In hac collectione — supellex, textilia et ornamenta, quae inter se facile conveniunt.',
                              'tag' => 'p',
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
                    'flex-align' => 'y-l',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-12 st-12-lp st-12-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-12',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
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
                                'url_text' => $img_path.'duo-vasa.png',
                              ],
                              'block_props' => [
                                'border-radius' => 'b-r-l',
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
    'type' => 'site.CustomProducts2',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-0',
          'padding-bottom' => 'p-b-18 p-b-16-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-bottom' => 'p-b-12',
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
            '' => [
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
                  'column' => 'st-12-mb st-6 st-6-lp st-6-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-12',
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-12 p-t-0-mb p-t-0-tb',
                      'padding-bottom' => 'p-b-12 p-b-0-mb p-b-0-tb',
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
                                'url_text' => $img_path.'maro-1.png',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Armarium ligneum «Maro 1»',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<b>45 000 den.</b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10 m-b-0-tb m-b-10-mb',
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
                    'flex-align' => 'y-l',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-6 st-6-lp st-6-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-12',
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-12 p-t-0-mb p-t-0-tb',
                      'padding-bottom' => 'p-b-12 p-b-0-mb p-b-0-tb',
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
                                'url_text' => $img_path.'maro-2.png',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Armarium ligneum «Maro 2»
',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<b>50 000 den.</b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10 m-b-0-tb m-b-10-mb',
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
    'type' => 'site.CustomAdvantages',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-20 p-t-18-mb',
          'padding-bottom' => 'p-b-20 p-b-18-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
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
          'padding-top' => 'p-t-0-tb',
          'padding-bottom' => 'p-b-0-tb',
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
                  'column' => 'st-12-mb st-12-lp st-12-tb st-12',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'flex-align-vertical' => 'a-c-s',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                    'site-block-column' => [
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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="#828282"><span style="caret-color: rgb(130, 130, 130);">Index&nbsp;</span></font>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-0 m-t-a-lp',
                                'margin-bottom' => 'm-b-10',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-blc">Quid ad conclave commodum opus est</font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-16',
                                'align' => 't-l',
                                'line-height' => 't-lh-xs',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-4 st-4-lp st-12-tb',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="" class="tx-b-opc-3">01</font></b>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-10',
                                'margin-bottom' => 'm-b-0',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-blc">Lectus mollis aut sella


</font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
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
                              'html' => '<font color="" class="tx-bw-4">Locus principalis ad quietem, omnia circa eum disponuntur



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
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-4 st-4-lp st-12-tb',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="" class="tx-b-opc-3">02</font></b>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-10',
                                'margin-bottom' => 'm-b-0',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-blc">Lucerna stativa aut parietaria


</font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
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
                              'html' => '<font color="" class="tx-bw-4">Lumen tepidum additum vesperi atmosphaeram mutat



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
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-4 st-4-lp st-12-tb',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="" class="tx-b-opc-3">03</font></b>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-10',
                                'margin-bottom' => 'm-b-0',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-blc">Tapete pavimenti


</font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
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
                              'html' => '<font color="" class="tx-bw-4">Supellectilem coniungit et conclave visu tepidius reddit



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
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-4 st-4-lp st-12-tb',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="" class="tx-b-opc-3">04</font></b>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-0',
                                'align' => 't-l',
                                'margin-top' => 'm-t-10',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-blc">Repositio




</font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
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
                              'html' => '<font color="" class="tx-bw-4"> Armarium parvum, pluteus aut corbis — ut res non passim iaceant





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
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-mb st-4 st-4-lp st-12-tb',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="" class="tx-b-opc-3">05</font></b>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-10',
                                'margin-bottom' => 'm-b-0',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-blc">Ornamenta: III–V res






</font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
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
                              'html' => '<font color="" class="tx-bw-4">Vasa, candelae, pulvini — ultimus tactus qui omnia animat







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
    'type' => 'site.CustomProducts',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18',
          'padding-bottom' => 'p-b-18',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-12 p-t-0-tb',
          'padding-bottom' => 'p-b-12 p-b-0-tb',
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
            '' => [
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
                  'column' => 'st-9-lp st-9-tb st-9 st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-top' => 'p-t-0',
                      'padding-bottom' => 'p-b-0 p-b-12-tb p-b-0-mb',
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
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Ornamenta',
                              'tag' => 'h2',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-8',
                                'max-width' => 'fx-9',
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
                    'flex-align' => 'y-l',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-3 st-3-lp st-3-tb st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-top' => 'p-t-8',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-bottom' => 'p-b-12-tb',
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
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<span class="tx-bw-1"><u>Omnia spectare</u>&nbsp;→&nbsp;</span>',
                              'tag' => 'h1',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'margin-left' => 'm-l-a m-l-0-mb',
                                'margin-bottom' => 'm-b-8',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
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
                                'url_text' => $img_path.'oak-tray.png',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Tabula lignea «Oak Tray»',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<b>2000 den.</b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
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
                                'url_text' => $img_path.'calm.jpg',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Candela in vase fictili «Calm»',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<b>1500 den.</b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
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
                                'url_text' => $img_path.'natural-weave.png',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Corbis textilis «Natural Weave»',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<b>9000 den.</b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
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
                                'url_text' => $img_path.'oak-frame.jpg',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Frame lignea «Oak Frame»
',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<b>5000 den.</b>',
                              'tag' => 'h3',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'align' => 't-l',
                                'margin-top' => 'm-t-0',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
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
          'padding-top' => 'p-t-18',
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
          'padding-bottom' => 'p-b-12',
          'padding-top' => 'p-t-12',
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
                              'html' => 'Subscribite epistulis nostris et nuntios de rebus novis atque pretiis imminutis accipite.',
                              'tag' => 'h3',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
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
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                              'textarea_html' => '{$wa->mailer->form(1)}',
                              'form_type' => 'mailer',
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
          'padding-bottom' => 'p-b-20 p-b-16-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-16-mb p-t-18',
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
                                'url_text' => $root_img_path.'categories/categories4-1.jpg',
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
                                  'value' => '',
                                  'type' => 'separate',
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
                                          'html' => '<b>Culina cum insula&nbsp;</b><br>',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
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
                                          'html' => 'Spatium ad coquendum et communicandum, ubi omnia ad manum sunt.
',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
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
                  'column' => 'st-12-mb st-4 st-4-lp st-6-tb',
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
                                'url_text' => $root_img_path.'categories/categories4-3.jpg',
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
                                  'value' => '',
                                  'type' => 'separate',
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
                                          'html' => '<b>Conclave modernum&nbsp;</b><br>',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
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
                                          'html' => 'Aequilibrium inter commoditatem et simplicitatem, ubi tempus cotidie agere libet.
',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
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
                  'column' => 'st-12-mb st-4 st-4-lp st-6-tb',
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
                                'url_text' => $root_img_path.'categories/categories4-2.jpg',
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
                                  'value' => '',
                                  'type' => 'separate',
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
                                          'html' => '<b>Balneum</b><br>',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
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
                                          'html' => 'Locus quietis et relaxationis, cum luce et materiis bene compositis.
',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
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
              ],
            ],
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.Menu.',
    'data' => [
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
      'inline_props' => [
        'site-block-menu' => [
          'scroll-margin-top' => [
            'value' => '',
            'unit' => 'px',
            'id' => 'menut4',
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
          'id' => 'menut4',
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
                'type' => 'site.MenuLogoT4',
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
                                            'margin-top' => 'm-t-0',
                                            'pictures-size' => 'i-xl',
                                          ],
                                          'indestructible' => false,
                                          'default_image_url' => '/wa/wa-apps/site/img/image.svg',
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64" viewBox="0 0 64 64" fill-rule="evenodd" fill="var(--white)">
        <path d="M32,47 C40.2842712,47 47,40.2842712 47,32 C47,23.7157288 40.2842712,17 32,17 C23.7157288,17 17,23.7157288 17,32 C17,40.2842712 23.7157288,47 32,47 Z M32,50 C41.9411255,50 50,41.9411255 50,32 C50,22.0588745 41.9411255,14 32,14 C22.0588745,14 14,22.0588745 14,32 C14,41.9411255 22.0588745,50 32,50 Z"/>
        <path d="M32,61 C15.9837423,61 3,48.0162577 3,32 C3,15.9837423 15.9837423,3 32,3 C48.0162577,3 61,15.9837423 61,32 C61,48.0162577 48.0162577,61 32,61 Z M32,52 C20.954305,52 12,43.045695 12,32 C12,20.954305 20.954305,12 32,12 C43.045695,12 52,20.954305 52,32 C52,43.045695 43.045695,52 32,52 Z"/>
        </svg>',
                                            'fill' => 'removed',
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-wh',
                                              'type' => 'palette',
                                            ],
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.SubColumn',
                                        'data' => [
                                          'block_props' => [
                                            'padding-top' => 'p-t-4',
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
                                                      'html' => '<b><font color="" class="tx-wh">Nomen Societatis</font></b>',
                                                      'tag' => 'h3',
                                                      'block_props' => [
                                                        'font-size' => [
                                                          'name' => 'Size #7',
                                                          'value' => 't-7',
                                                          'type' => 'library',
                                                          'unit' => 'px',
                                                        ],
                                                        'font-header' => 't-hdn',
                                                        'margin-top' => 'm-t-6',
                                                        'margin-bottom' => 'm-b-2',
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
            ],
            'col2' => [
              [
                'type' => 'site.MenuContactsT4',
                'data' => [
                  'block_props' => [
                    'site-block-column' => [
                      'margin-bottom' => 'm-b-0',
                      'margin-left' => 'm-l-0-tb m-l-a',
                      'margin-right' => 'm-r-0',
                      'margin-top' => 'm-t-0',
                      'padding-bottom' => 'p-b-6',
                      'padding-left' => 'p-l-0',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-6',
                    ],
                    'site-block-column-wrapper' => [
                      'border-radius' => 'b-r-l',
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-r',
                  ],
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'id' => [
                    'site-block-column' => [
                      'id' => 'menut4itms',
                    ],
                  ],
                  'indestructible' => true,
                  'column' => 'st-0-tb st-0-mb st-9 st-9-lp',
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
                                'padding-top' => 'p-t-6',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-ds n-wr-lp',
                                'justify-align' => 'y-j-cnt',
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
                                              'value' => 'btn-wht-lnk',
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
                                              'value' => 'btn-wht-lnk',
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
                                              'value' => 'btn-wht-lnk',
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
                                              'value' => 'btn-wht-lnk',
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
                                          'html' => 'Contactus',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'width' => 'cnt-w',
                                            'border-radius' => 'b-r-r',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-wht-lnk',
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
                      'id' => 'menut4gmb',
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
                                              'value' => 'tx-wh',
                                              'type' => 'palette',
                                            ],
                                            'fill' => 'removed',
                                            'open_menu_svg_html' => '<svg viewBox="0 0 16 16" fill="var(--white)" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.2914 1.56903L0.70099 1.56903C0.313844 1.56903 -3.61259e-08 1.88288 0 2.27002C3.61259e-08 2.65717 0.313844 2.97101 0.70099 2.97101L15.2914 2.97101C15.6786 2.97101 16 2.65717 16 2.27002C16 1.88287 15.6786 1.56903 15.2914 1.56903Z"></path>
                        <path d="M15.2914 7.29901L0.70099 7.29902C0.313844 7.29902 -3.61259e-08 7.61286 0 8.00001C3.61259e-08 8.38715 0.313844 8.70099 0.70099 8.70099L15.2914 8.70099C15.6786 8.70099 16 8.38715 16 8C16 7.61285 15.6786 7.29901 15.2914 7.29901Z"></path>
                        <path d="M15.2914 13.0286L0.70099 13.0286C0.313844 13.0286 -3.61259e-08 13.3424 0 13.7296C3.61259e-08 14.1167 0.313844 14.4305 0.70099 14.4305L15.2914 14.4305C15.6786 14.4305 16 14.1167 16 13.7296C16 13.3424 15.6786 13.0286 15.2914 13.0286Z"></path>
                    </svg>',
                                            'close_menu_svg_html' => '<svg viewBox="0 0 16 16" fill="var(--white)" xmlns="http://www.w3.org/2000/svg">
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
            'col4' => [
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
                            'name' => 'grey shades',
                            'value' => 'bg-bw-2',
                            'type' => 'palette',
                          ],
                        ],
                        'name' => 'grey shades',
                        'value' => 'bg-bw-2',
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
                      'id' => 'menut4bg',
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
];
