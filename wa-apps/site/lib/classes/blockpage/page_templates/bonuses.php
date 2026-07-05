<?php
$img_path = wa()->getAppStaticUrl('site').'img/blocks/page_templates/bonuses/';

return [
  ['type' => 'site.MenuT1',],
  [
    'type' => 'site.CustomGallery4',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18',
          'padding-bottom' => 'p-b-18',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'background' => [
            'name' => 'complementary',
            'value' => 'bg-brn-a-1',
            'type' => 'palette',
            'scheme' => 'complementary',
            'uuid' => 1,
            'layers' => [
              [
                'name' => 'complementary',
                'value' => 'bg-brn-a-1',
                'type' => 'palette',
                'scheme' => 'complementary',
                'uuid' => 1,
              ],
            ],
          ],
          'border-radius' => 'b-r-l',
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-16 p-b-18-mb',
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
                  'column' => 'st-12-mb st-12 st-12-lp st-12-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-top' => 'p-t-8',
                      'padding-right' => 'p-r-clm',
                      'padding-left' => 'p-l-clm',
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
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Praemiīs solve ūsque ad 50% mercētūrae',
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
                  'column' => 'st-9 st-9-lp st-12-mb st-8-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-top' => 'p-t-0',
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
                              'html' => 'In tabernā nostrā praemia (puncta) sua sponte prō quāque mercētūra adduntur. Ratiō aperta: ūnum praemium ūnum sēstertium valet. Nūllae "citreae" nūllaeque condiciōnēs implicātae.

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
                      'flex-align-vertical' => 'a-c-c',
                      'padding-right' => 'p-r-clm',
                      'padding-left' => 'p-l-clm',
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
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'Praemia intuēre<br>',
                              'tag' => 'a',
                              'block_props' => [
                                'border-radius' => 'b-r-r',
                                'button-size' => 'inp-l p-l-14 p-r-14',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-blc-strk',
                                  'type' => 'palette',
                                ],
                                'margin-left' => 'm-l-0-mb',
                                'margin-right' => 'm-r-a-mb',
                                'full-width' => 'f-w',
                                'margin-bottom' => 'm-b-12 m-b-a-tb',
                                'margin-top' => 'm-t-a-tb',
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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="" class="tx-bw-6">01</font></b>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
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
                              'html' => 'Mercātūram facis',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Quōlibet modō mercēs solvis — praemia (puncta) statim in ratiōnem tuam adduntur.



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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="" class="tx-bw-6">02</font></b>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
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
                              'html' => 'Praemia accipis
',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Plerumque 5% summae mercis, sed in quibusdam mercibus maiōrēs centēsimae partēs addī solent.

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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b><font color="" class="tx-bw-6">03</font></b>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
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
                              'html' => 'Sine fīnibus ērogā
',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Proximā vice, cum mercāberis, ēlige solūtiōnem praemiīs faciendam — ūsque ad 50% (vel plūs, prō mercēs prōpōnunt).



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
                  'column' => 'st-12 st-12-lp st-12-tb st-12-mb',
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
                              'html' => 'Beneficia ad exemplum mercium
',
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
                                'url_text' => $img_path.'chair-1.jpg',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Sella “Orso”',
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
                                'margin-bottom' => 'm-b-8',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b>19 990 den.</b>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-a',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-2',
                                'padding-bottom' => 'p-b-3',
                                'background' => [
                                  'name' => 'complementary',
                                  'value' => 'bg-brn-a-1',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                  'uuid' => 1,
                                  'layers' => [
                                    [
                                      'name' => 'complementary',
                                      'value' => 'bg-brn-a-1',
                                      'type' => 'palette',
                                      'scheme' => 'complementary',
                                      'uuid' => 1,
                                    ],
                                  ],
                                ],
                                'border-radius' => 'b-r-m',
                                'padding-left' => 'p-l-9',
                                'padding-right' => 'p-r-9',
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
                                          'html' => '<font color="" class="tx-brn-a"><b>+1000</b> puncta in rationem</font>',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-4',
                                'padding-bottom' => 'p-b-10',
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
                                            'margin-right' => 'm-r-9',
                                            'picture-size' => 'i-s',
                                            'margin-top' => 'm-t-2',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M13.7337 0.819574C13.8175 0.561607 14.1825 0.561607 14.2663 0.819574L17.0803 9.48029C17.1178 9.59565 17.2253 9.67376 17.3466 9.67376H26.453C26.7243 9.67376 26.8371 10.0209 26.6176 10.1803L19.2504 15.5329C19.1522 15.6042 19.1112 15.7306 19.1487 15.846L21.9627 24.5067C22.0465 24.7646 21.7513 24.9791 21.5318 24.8197L14.1646 19.4671C14.0664 19.3958 13.9336 19.3958 13.8354 19.4671L6.46818 24.8197C6.24874 24.9791 5.95348 24.7646 6.0373 24.5067L8.85134 15.846C8.88882 15.7306 8.84776 15.6042 8.74962 15.5329L1.38238 10.1803C1.16294 10.0209 1.27572 9.67376 1.54696 9.67376H10.6534C10.7747 9.67376 10.8822 9.59565 10.9197 9.48029L13.7337 0.819574Z" fill="black"/>
</svg>
',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => ' Solvi potest punctis usque ad <b>9 995 den.</b>',
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
                                'url_text' => $img_path.'chair-2.jpg',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Sella “Nord mollis”',
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
                                'margin-bottom' => 'm-b-8',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b>27 490 den.</b>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-a',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-2',
                                'padding-bottom' => 'p-b-3',
                                'background' => [
                                  'name' => 'complementary',
                                  'value' => 'bg-brn-a-1',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                  'uuid' => 1,
                                  'layers' => [
                                    [
                                      'name' => 'complementary',
                                      'value' => 'bg-brn-a-1',
                                      'type' => 'palette',
                                      'scheme' => 'complementary',
                                      'uuid' => 1,
                                    ],
                                  ],
                                ],
                                'border-radius' => 'b-r-m',
                                'padding-left' => 'p-l-9',
                                'padding-right' => 'p-r-9',
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
                                          'html' => '<font color="" class="tx-brn-a"><b>+1350</b> puncta in rationem</font>',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-4',
                                'padding-bottom' => 'p-b-10',
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
                                            'margin-top' => 'm-t-2',
                                            'margin-right' => 'm-r-9',
                                            'picture-size' => 'i-s',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M13.7337 0.819574C13.8175 0.561607 14.1825 0.561607 14.2663 0.819574L17.0803 9.48029C17.1178 9.59565 17.2253 9.67376 17.3466 9.67376H26.453C26.7243 9.67376 26.8371 10.0209 26.6176 10.1803L19.2504 15.5329C19.1522 15.6042 19.1112 15.7306 19.1487 15.846L21.9627 24.5067C22.0465 24.7646 21.7513 24.9791 21.5318 24.8197L14.1646 19.4671C14.0664 19.3958 13.9336 19.3958 13.8354 19.4671L6.46818 24.8197C6.24874 24.9791 5.95348 24.7646 6.0373 24.5067L8.85134 15.846C8.88882 15.7306 8.84776 15.6042 8.74962 15.5329L1.38238 10.1803C1.16294 10.0209 1.27572 9.67376 1.54696 9.67376H10.6534C10.7747 9.67376 10.8822 9.59565 10.9197 9.48029L13.7337 0.819574Z" fill="black"/>
</svg>
',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => ' Solvi potest punctis usque ad <b>13 745 den.</b>',
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
                                'url_text' => $img_path.'chair-3.jpg',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Sella “Robur classicum”
',
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
                                'margin-bottom' => 'm-b-8',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b>31 900 den.</b>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-a',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-2',
                                'padding-bottom' => 'p-b-3',
                                'background' => [
                                  'name' => 'complementary',
                                  'value' => 'bg-brn-a-1',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                  'uuid' => 1,
                                  'layers' => [
                                    [
                                      'name' => 'complementary',
                                      'value' => 'bg-brn-a-1',
                                      'type' => 'palette',
                                      'scheme' => 'complementary',
                                      'uuid' => 1,
                                    ],
                                  ],
                                ],
                                'border-radius' => 'b-r-m',
                                'padding-left' => 'p-l-9',
                                'padding-right' => 'p-r-9',
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
                                          'html' => '<font color="" class="tx-brn-a"><b>+1600</b>&nbsp;puncta in rationem</font>',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-4',
                                'padding-bottom' => 'p-b-10',
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
                                            'picture-size' => 'i-s',
                                            'margin-top' => 'm-t-2',
                                            'margin-right' => 'm-r-9',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M13.7337 0.819574C13.8175 0.561607 14.1825 0.561607 14.2663 0.819574L17.0803 9.48029C17.1178 9.59565 17.2253 9.67376 17.3466 9.67376H26.453C26.7243 9.67376 26.8371 10.0209 26.6176 10.1803L19.2504 15.5329C19.1522 15.6042 19.1112 15.7306 19.1487 15.846L21.9627 24.5067C22.0465 24.7646 21.7513 24.9791 21.5318 24.8197L14.1646 19.4671C14.0664 19.3958 13.9336 19.3958 13.8354 19.4671L6.46818 24.8197C6.24874 24.9791 5.95348 24.7646 6.0373 24.5067L8.85134 15.846C8.88882 15.7306 8.84776 15.6042 8.74962 15.5329L1.38238 10.1803C1.16294 10.0209 1.27572 9.67376 1.54696 9.67376H10.6534C10.7747 9.67376 10.8822 9.59565 10.9197 9.48029L13.7337 0.819574Z" fill="black"/>
</svg>
',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => ' Solvi potest punctis usque ad&nbsp;<b>15 950 den.</b>',
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
                                'url_text' => $img_path.'chair-4.jpg',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-m',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Sella “Lux minima”
',
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
                                'margin-bottom' => 'm-b-8',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b>17 490 den.</b>',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'margin-top' => 'm-t-a',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-2',
                                'padding-bottom' => 'p-b-3',
                                'background' => [
                                  'name' => 'complementary',
                                  'value' => 'bg-brn-a-1',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                  'uuid' => 1,
                                  'layers' => [
                                    [
                                      'name' => 'complementary',
                                      'value' => 'bg-brn-a-1',
                                      'type' => 'palette',
                                      'scheme' => 'complementary',
                                      'uuid' => 1,
                                    ],
                                  ],
                                ],
                                'border-radius' => 'b-r-m',
                                'padding-left' => 'p-l-9',
                                'padding-right' => 'p-r-9',
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
                                          'html' => '<font color="" class="tx-brn-a"><b>+850</b> puncta in rationem</font>',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-4',
                                'padding-bottom' => 'p-b-10',
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
                                            'picture-size' => 'i-s',
                                            'margin-top' => 'm-t-2',
                                            'margin-right' => 'm-r-9',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M13.7337 0.819574C13.8175 0.561607 14.1825 0.561607 14.2663 0.819574L17.0803 9.48029C17.1178 9.59565 17.2253 9.67376 17.3466 9.67376H26.453C26.7243 9.67376 26.8371 10.0209 26.6176 10.1803L19.2504 15.5329C19.1522 15.6042 19.1112 15.7306 19.1487 15.846L21.9627 24.5067C22.0465 24.7646 21.7513 24.9791 21.5318 24.8197L14.1646 19.4671C14.0664 19.3958 13.9336 19.3958 13.8354 19.4671L6.46818 24.8197C6.24874 24.9791 5.95348 24.7646 6.0373 24.5067L8.85134 15.846C8.88882 15.7306 8.84776 15.6042 8.74962 15.5329L1.38238 10.1803C1.16294 10.0209 1.27572 9.67376 1.54696 9.67376H10.6534C10.7747 9.67376 10.8822 9.59565 10.9197 9.48029L13.7337 0.819574Z" fill="black"/>
</svg>
',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => ' Solvi potest punctis usque ad <b>8 745 den.</b>',
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
    'type' => 'site.CustomFaq',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-20',
          'padding-bottom' => 'p-b-20',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'padding-bottom' => 'p-b-20 p-b-12-tb',
          'padding-top' => 'p-t-20 p-t-12-tb',
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
                  'column' => 'st-12-mb st-12-lp st-12-tb st-8',
                  'block_props' => [
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'margin-right' => 'm-r-0',
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
                              'html' => 'Dē praemiīs quaestiōnes frequentēs',
                              'tag' => 'p',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-17',
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
                              'html' => 'Puncta intereunt?',
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
                              'html' => 'Minimē! Puncta tua nōn intereunt, sī vel ūnam mercētūram in annō facis. Fīdēlibus clientibus puncta aeterna sunt.',
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
                              'html' => 'Quō modō ratiōnem cognōscō?',
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
                              'html' => 'Apertissimē: ūnum punctum ūnum sēstertium valet. Nūllae "citreae" nūllaeque ūnitātes falsae.',
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
                              'html' => 'Quantum punctīs solvere possum?
',
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
                              'html' => 'Plerumque ad 50% summae mercētūrae. In diēbus venditiōnis, līmitem ad 70% augēmus.',
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
                              'html' => 'Cūr haec summa mihi additur?
',
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
                              'html' => 'Ipse ante mercātūram centēsimam partem vidēs. Fundamentum 5% est; prō mercibus grātīs, ūsque ad 15%.',
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
                              'html' => 'Estne fraus?
',
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
                              'html' => 'Nūllae condiciōnēs occultae. Omnī sīc est quem ad modum in tabernā: apertē et lūculentē — collige et ērogā.',
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
                              'html' => 'Quid sī māchina (amba) cessat?

',
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
                              'html' => 'Sī quid in rē māchināli (amphorā) dēficit, nōs tibi praemia manū scrībēmus, ex tabulā cerātā tuā. Adiūtōrēs nostrōs adī — mōmentō remedium parābimus.',
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
