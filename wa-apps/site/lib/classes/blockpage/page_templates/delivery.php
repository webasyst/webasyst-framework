<?php
$img_path = wa()->getAppStaticUrl('site').'img/blocks/page_templates/delivery/';

return [
  ['type' => 'site.MenuT1',],
  [
    'type' => 'site.CustomAdvantages',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-16-mb p-t-12',
          'padding-bottom' => 'p-b-16-mb p-b-12',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-0-tb',
          'padding-bottom' => 'p-b-0-tb',
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
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
                      'column-max-width' => 'fx-9',
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
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Traditio mandatorum ',
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
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Hic collegimus responsa ad quaestiones principales de traditione mercium:
quantum ea constat, quando mandatum adveniet, et quomodo recipi possit.',
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
                      'padding-top' => 'p-t-12',
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                      'padding-left' => 'p-l-12',
                      'padding-right' => 'p-r-12',
                      'border-radius' => 'b-r-l',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-0',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds',
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
                                            'picture-size' => 'i-xxl',
                                          ],
                                          'image' => [
                                            'type' => 'address',
                                            'url_text' => $img_path.'box.png',
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
                                            'background' => [
                                              'name' => 'grey shades',
                                              'value' => 'bg-bw-4',
                                              'type' => 'palette',
                                              'uuid' => 1,
                                              'layers' => [
                                                [
                                                  'name' => 'grey shades',
                                                  'value' => 'bg-bw-4',
                                                  'type' => 'palette',
                                                  'uuid' => 1,
                                                ],
                                              ],
                                            ],
                                            'padding-left' => 'p-l-10',
                                            'padding-right' => 'p-r-10',
                                            'border-radius' => 'b-r-r',
                                            'margin-left' => 'm-l-a',
                                            'margin-top' => 'm-t-8',
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
                                                      'html' => '<font color="" class="tx-wh">Populariter</font>',
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
                                                        'align' => 't-l',
                                                        'line-height' => 't-lh-xs',
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
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Locus receptionis mercium',
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
                              'html' => 'Mandatum recipere potes tempore tibi commodo in proximo loco traditionis.',
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
                      'padding-top' => 'p-t-12',
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                      'padding-left' => 'p-l-12',
                      'padding-right' => 'p-r-12',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-0',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds',
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
                                            'picture-size' => 'i-xxl',
                                          ],
                                          'image' => [
                                            'type' => 'address',
                                            'url_text' => $img_path.'courier.png',
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
                                            'background' => [
                                              'name' => 'grey shades',
                                              'value' => 'bg-bw-4',
                                              'type' => 'palette',
                                              'uuid' => 1,
                                              'layers' => [
                                                [
                                                  'name' => 'grey shades',
                                                  'value' => 'bg-bw-4',
                                                  'type' => 'palette',
                                                  'uuid' => 1,
                                                ],
                                              ],
                                            ],
                                            'padding-left' => 'p-l-10',
                                            'padding-right' => 'p-r-10',
                                            'border-radius' => 'b-r-r',
                                            'margin-left' => 'm-l-a',
                                            'margin-top' => 'm-t-8',
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
                                                      'html' => '<font color="" class="tx-wh">Ab uno die</font>',
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
                                                        'align' => 't-l',
                                                        'line-height' => 't-lh-xs',
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
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Per nuntium',
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
                              'html' => 'Merces ad ipsam domum die opportuno affertur.&nbsp;',
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
                      'padding-top' => 'p-t-12',
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-0-tb',
                      'padding-bottom' => 'p-b-0-tb',
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
                      'padding-left' => 'p-l-12',
                      'padding-right' => 'p-r-12',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-0',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds',
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
                                          ],
                                          'image' => [
                                            'type' => 'address',
                                            'url_text' => $img_path.'store.png',
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
                                            'background' => [
                                              'name' => 'grey shades',
                                              'value' => 'bg-bw-4',
                                              'type' => 'palette',
                                              'uuid' => 1,
                                              'layers' => [
                                                [
                                                  'name' => 'grey shades',
                                                  'value' => 'bg-bw-4',
                                                  'type' => 'palette',
                                                  'uuid' => 1,
                                                ],
                                              ],
                                            ],
                                            'padding-left' => 'p-l-10',
                                            'padding-right' => 'p-r-10',
                                            'border-radius' => 'b-r-r',
                                            'margin-left' => 'm-l-a',
                                            'margin-top' => 'm-t-8',
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
                                                      'html' => '<font color="" class="tx-wh">Sine pretio</font>',
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
                                                        'align' => 't-l',
                                                        'line-height' => 't-lh-xs',
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
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Ipsa receptio',
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
                              'html' => 'Mandatum ipse recipere potes
ex taberna aut horreo.',
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
          'padding-top' => 'p-t-12',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12 st-12-lp st-12-tb st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-10-mb',
                      'padding-bottom' => 'p-b-10-mb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'indestructible' => false,
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
                              'html' => 'Servitium nuntiorum',
                              'tag' => 'h2',
                              'block_props' => [
                                'font-size' => [
                                  'name' => 'Size #4',
                                  'value' => 't-4',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                                'margin-top' => 'm-t-0-mb m-t-4',
                                'font-header' => 't-hdn',
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
                  'column' => 'st-12-mb st-8 st-8-lp st-8-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-12 p-t-10-mb',
                      'padding-bottom' => 'p-b-12 p-b-10-mb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'indestructible' => false,
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="#04af00">Zona 1</font> — regiones urbi propinqua</b>. Traditio intra 1–2 horas.&nbsp;',
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
                                'margin-bottom' => 'm-b-9',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="#d19a00">Zona 2</font> — regiones urbi propinquae</b>. Traditio intra 2–3 horas.&nbsp;<br>',
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
                                'margin-bottom' => 'm-b-9',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="#db3a3a">Zona 3</font> — regiones remotiores</b>. Traditio intra 3–5 horas.',
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
                                'margin-bottom' => 'm-b-9',
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
                  'column' => 'st-4 st-4-lp st-12-mb st-4-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-12 p-t-10-mb',
                      'padding-bottom' => 'p-b-12 p-b-10-mb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'indestructible' => false,
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-4">Inscriptio horrei</font>',
                              'tag' => 'h1',
                              'block_props' => [
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-10',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-1">Roma, via Floralis, 12.&nbsp;Cotidie sine diebus festis, ab hora 10 usque ad 20</font>',
                              'tag' => 'h1',
                              'block_props' => [
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-0',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
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
                  'column' => 'st-12 st-12-lp st-12-tb st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-10-mb',
                      'padding-bottom' => 'p-b-10-mb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'padding-top' => 'p-t-12',
                      'padding-bottom' => 'p-b-12',
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'indestructible' => false,
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
                            'type' => 'site.Map',
                            'data' => [
                              'html' => '',
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
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomFaq',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-16',
          'padding-bottom' => 'p-b-20 p-b-16-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
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
                  'column' => 'st-6-lp st-6-tb st-12-mb st-5',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [],
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
                              'html' => 'Quando mandatum mittitur?',
                              'tag' => 'h1',
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
                  'column' => 'st-6-lp st-6-tb st-12-mb st-5',
                  'block_props' => [
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-10',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                  ],
                  'wrapper_props' => [],
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
                              'html' => 'Plerumque intra unum diem operarium post solutionem.',
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
                                'margin-bottom' => 'm-b-14',
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
                  'column' => 'st-6-lp st-6-tb st-12-mb st-5',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [],
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
                              'html' => 'Potestne mutari inscriptio traditionis?',
                              'tag' => 'h1',
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
                  'column' => 'st-6-lp st-6-tb st-12-mb st-5',
                  'block_props' => [
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-bottom' => 'p-b-10',
                    ],
                  ],
                  'wrapper_props' => [],
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
                              'html' => 'Ita, si mandatum adhuc non est traditum servitio traditionis.',
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
                                'margin-bottom' => 'm-b-14',
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
                  'column' => 'st-6-lp st-6-tb st-12-mb st-5',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [],
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
                              'html' => 'Quomodo mandatum sequi possum?',
                              'tag' => 'h1',
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
                  'column' => 'st-6-lp st-6-tb st-12-mb st-5',
                  'block_props' => [
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-bottom' => 'p-b-10',
                    ],
                  ],
                  'wrapper_props' => [],
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
                              'html' => 'Numerus ad mandatum sequendum mittitur postquam mandatum traditum est servitio nuntiorum.',
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
                                'margin-bottom' => 'm-b-14',
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
                  'column' => 'st-6-lp st-6-tb st-12-mb st-5',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [],
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
                              'html' => 'Quid faciendum est si traditio moratur?',
                              'tag' => 'h1',
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
                  'column' => 'st-6-lp st-6-tb st-12-mb st-5',
                  'block_props' => [
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-bottom' => 'p-b-10',
                    ],
                  ],
                  'wrapper_props' => [],
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
                              'html' => 'Cum nostro auxilio coniunge — statum traditionis inspiciemus.',
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
                                'margin-bottom' => 'm-b-14',
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
  ['type' => 'site.FooterBottom2.2',],
];
