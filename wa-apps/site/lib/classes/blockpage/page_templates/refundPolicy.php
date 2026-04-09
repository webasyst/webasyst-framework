<?php
return [
  ['type' => 'site.MenuT1',],
  [
    'type' => 'site.CustomAdvantages',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-12 p-t-12-mb',
          'padding-bottom' => 'p-b-12 p-b-12-mb',
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
                              'html' => 'Conditiones restitutionis
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
                              'html' => 'Restitutionem petere potes intra <b>14 dies calendarios</b> a momento quo mandatum acceptum est, nisi aliae condiciones pro certa mercium genere statutae sunt.<br>',
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
                  'column' => 'st-6 st-6-lp st-12-mb st-6-tb',
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
                        '' => [
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Si res non convenit',
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
                              'html' => '<font color="" class="tx-bw-3">Permutatio cum re simili aut restitutio pecuniae fieri potest.</font>',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-6 st-6-lp st-12-mb st-6-tb',
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
                        '' => [
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Si vitium est',
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
                              'html' => '<font color="" class="tx-bw-3">Causam considerabimus et, pro ratione rei, permutatio, restitutio aut aliud consilium proponetur.</font>',
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
          'padding-top' => 'p-t-12-mb p-t-12',
          'padding-bottom' => 'p-b-18 p-b-12-mb',
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
                  'column' => 'st-12-tb st-12-mb st-8-lp st-7',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-12',
                      'padding-top' => 'p-t-12',
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
                              'html' => 'Quae merces restitui possunt',
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
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '• merces quae non conveniunt magnitudine, colore aut natura; <br>•&nbsp;merces vitio officinae affectae; <br>•&nbsp;merces in traditione laesae.',
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
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Merces quae restitui non possunt',
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
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '• res ad curam personalem pertinentes; <br>• vestimenta interiora; <br>• medicamenta vel unguenta post aperturam; <br>• merces digitales post traditionem accessus; <br>• merces ad mandatum singulare confectae.',
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
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Quomodo restitutionem facere',
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
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '1. Nobiscum contactum fac et numerum mandati indica; <br>2. Causam restitutionis describe; <br>3. Si opus est, imagines adde; <br>4. Instructiones de re mittenda accipe; <br>5. Exspecta probationem et confirmationem restitutionis.',
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
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Restitutio pecuniae',
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
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Pecunia eodem modo solvendi restituitur quo in emptione usus est, nisi aliter convenerit.',
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
                              'html' => 'Tempus restitutionis plerumque est usque ad <b>10</b> <b>dies</b> <b>operativos</b> post confirmationem petitionis et receptionem mercium in horreo.',
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
          'padding-top' => 'p-t-16 p-t-13-mb',
          'padding-bottom' => 'p-b-18',
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
                              'html' => 'Contactus',
                              'tag' => 'h2',
                              'block_props' => [
                                'font-size' => [
                                  'name' => 'Size #3',
                                  'value' => 't-3',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-14',
                                'margin-top' => 'm-t-0-mb m-t-4',
                                'font-header' => 't-hdn',
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
                  'column' => 'st-4 st-4-lp st-6-tb st-12-mb',
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
                              'html' => '<font color="" class="tx-bw-4">Telephonum</font>',
                              'tag' => 'h1',
                              'block_props' => [
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-1">+1 234 567 89 10</font>',
                              'tag' => 'h1',
                              'block_props' => [
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-0',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
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
                  'column' => 'st-4 st-4-lp st-6-tb st-12-mb',
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
                              'html' => '<font color="" class="tx-bw-4">Nos in Communitatibus Socialibus</font>',
                              'tag' => 'h1',
                              'block_props' => [
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-6',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-4',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'y-j-cnt',
                                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
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
                                            'fill' => 'removed',
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto " viewBox="0 0 33 32" fill="var(--bw-1)"><g clip-path="url(#clip0_10717_116287)"><path d="M16.6499 0C25.4865 0 32.6499 7.16344 32.6499 16C32.6499 24.8366 25.4865 32 16.6499 32C7.81335 32 0.649902 24.8366 0.649902 16C0.649902 7.16344 7.81335 0 16.6499 0ZM23.1919 9.63184C22.5901 9.64249 21.6661 9.96352 17.2231 11.8115C15.6668 12.4589 12.557 13.7991 7.89307 15.8311C7.13574 16.1322 6.73883 16.4268 6.70264 16.7148C6.6333 17.2679 7.42934 17.44 8.43115 17.7656C9.24801 18.0312 10.3471 18.3421 10.9185 18.3545C11.4366 18.3657 12.0153 18.1523 12.6538 17.7139C17.0118 14.7721 19.2616 13.285 19.4028 13.2529C19.5025 13.2303 19.6404 13.202 19.7339 13.2852C19.8271 13.3681 19.818 13.5246 19.8081 13.5674C19.7289 13.9051 15.6367 17.6282 15.3999 17.874C14.4996 18.8091 13.4756 19.3809 15.0552 20.4219C16.422 21.3226 17.2175 21.8973 18.6255 22.8203C19.5254 23.4102 20.2315 24.1099 21.1606 24.0244C21.588 23.9849 22.0292 23.5831 22.2534 22.3848C22.7835 19.5511 23.8261 13.4108 24.0669 10.8809C24.0879 10.6592 24.0609 10.3754 24.0396 10.251C24.0182 10.1265 23.9736 9.94958 23.812 9.81836C23.6203 9.66281 23.3243 9.6295 23.1919 9.63184Z"></path></g><defs><linearGradient id="paint0_linear_10717_116287" x1="1600.65" y1="0" x2="1600.65" y2="3176.27" gradientUnits="userSpaceOnUse"><stop stop-color="#2AABEE"></stop><stop offset="1" stop-color="#229ED9"></stop></linearGradient><clipPath id="clip0_10717_116287"><rect width="32" height="32" transform="translate(0.649902)"></rect></clipPath></defs></svg>',
                                            'color' => [
                                              'name' => 'Palette',
                                              'type' => 'palette',
                                              'value' => 'tx-bw-1',
                                            ],
                                          ],
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-8',
                                            'margin-right' => 'm-r-13',
                                            'margin-top' => 'm-t-2',
                                            'picture-size' => 'i-l',
                                          ],
                                          'link_props' => [
                                            'href' => 'https://t.me/',
                                            'data-value' => 'external-link',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'image' => [
                                            'type' => 'svg',
                                            'fill' => 'removed',
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto" viewBox="0 0 33 32" fill="var(--bw-1)"><g clip-path="url(#clip0_10717_116298)"><path d="M16.9131 0.492188C20.8704 0.515741 24.6661 2.06705 27.5068 4.82227C30.3476 7.57755 32.0137 11.3244 32.1582 15.2793C32.2333 17.3038 31.9066 19.323 31.1973 21.2207C30.4879 23.1185 29.4101 24.8571 28.0254 26.3359C26.6405 27.8149 24.9761 29.0055 23.1289 29.8379C21.2819 30.6702 19.2879 31.1277 17.2627 31.1855H16.8242C14.5219 31.1862 12.2484 30.6693 10.1729 29.6729L2.13965 31.46H2.11719C2.10047 31.4599 2.08357 31.4562 2.06836 31.4492C2.05308 31.4422 2.03937 31.4316 2.02832 31.4189C2.01739 31.4063 2.00873 31.3919 2.00391 31.376C1.99906 31.3599 1.99781 31.3428 2 31.3262L3.35742 23.2041C2.07929 20.8656 1.43132 18.2349 1.47852 15.5703C1.52574 12.9058 2.26674 10.2995 3.62695 8.00781C4.98713 5.7162 6.92004 3.81771 9.23633 2.5C11.5528 1.18229 14.1728 0.490219 16.8379 0.492188H16.9131ZM10.8066 7.7002C10.6519 7.71881 10.5003 7.7605 10.3574 7.82422C10.167 7.90918 9.99589 8.03217 9.85352 8.18457C9.45116 8.59719 8.32581 9.5904 8.26074 11.6758C8.19581 13.7607 9.65239 15.8235 9.85645 16.1133C10.0602 16.4026 12.6426 20.9076 16.8955 22.7344C19.395 23.8112 20.4907 23.9961 21.2012 23.9961C21.494 23.9961 21.7152 23.9649 21.9463 23.9512C22.7256 23.903 24.4838 23.0026 24.8672 22.0234C25.2506 21.0442 25.2759 20.1878 25.1748 20.0166C25.0737 19.8456 24.7963 19.7219 24.3789 19.5029C23.9608 19.2836 21.9116 18.1882 21.5264 18.0342C21.3836 17.9677 21.2294 17.9276 21.0723 17.916C20.9701 17.9214 20.8711 17.9524 20.7832 18.0049C20.6952 18.0573 20.6208 18.1303 20.5674 18.2178C20.225 18.6441 19.4398 19.5694 19.1758 19.8369C19.1183 19.9031 19.0472 19.9569 18.9678 19.9941C18.8883 20.0312 18.8016 20.0511 18.7139 20.0527C18.5519 20.0456 18.3927 20.0029 18.249 19.9277C17.0078 19.4005 15.8757 18.6456 14.9121 17.7021C14.0118 16.8148 13.2479 15.7989 12.6455 14.6875C12.4127 14.256 12.6461 14.0331 12.8584 13.8311C13.0706 13.629 13.2976 13.3498 13.5166 13.1084C13.6964 12.9023 13.8464 12.6718 13.9619 12.4238C14.0216 12.3087 14.052 12.1804 14.0498 12.0508C14.0478 11.921 14.0137 11.7939 13.9502 11.6807C13.8492 11.4648 13.0947 9.34441 12.7402 8.49316C12.4528 7.76587 12.1101 7.741 11.8105 7.71875C11.5642 7.70164 11.2814 7.69313 10.999 7.68457H10.9629L10.8066 7.7002Z"></path></g><defs><clipPath id="clip0_10717_116298"><rect width="32" height="32" transform="translate(0.866699)"></rect></clipPath></defs></svg>',
                                            'color' => [
                                              'name' => 'Palette',
                                              'type' => 'palette',
                                              'value' => 'tx-bw-1',
                                            ],
                                          ],
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-8',
                                            'margin-right' => 'm-r-13',
                                            'margin-top' => 'm-t-2',
                                            'picture-size' => 'i-l',
                                          ],
                                          'link_props' => [
                                            'href' => 'https://faq.whatsapp.com/5913398998672934',
                                            'data-value' => 'external-link',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'image' => [
                                            'type' => 'svg',
                                            'fill' => 'removed',
                                            'svg_html' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="black"/><path d="M17.029 23.0534C9.73568 23.0534 5.57568 18.0534 5.40234 9.7334H9.05568C9.17568 15.8401 11.869 18.4267 14.0023 18.9601V9.7334H17.4423V15.0001C19.549 14.7734 21.7623 12.3734 22.509 9.7334H25.949C25.6676 11.1026 25.1068 12.399 24.3015 13.5415C23.4962 14.684 22.4639 15.6481 21.269 16.3734C22.6028 17.0361 23.7808 17.9742 24.7255 19.1257C25.6701 20.2771 26.3598 21.6158 26.749 23.0534H22.9623C22.6129 21.8048 21.9027 20.687 20.9208 19.8403C19.9388 18.9935 18.7288 18.4554 17.4423 18.2934V23.0534H17.029Z" fill="white"/></svg>',
                                          ],
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-8',
                                            'margin-right' => 'm-r-13',
                                            'margin-top' => 'm-t-2',
                                            'picture-size' => 'i-l',
                                          ],
                                          'link_props' => [
                                            'href' => 'https://vk.com/',
                                            'data-value' => 'external-link',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'image' => [
                                            'type' => 'svg',
                                            'fill' => 'removed',
                                            'svg_html' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_3522_11884)"><circle cx="16" cy="16" r="16" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M16.4633 25.4389C14.4221 25.4389 13.4734 25.1409 11.8245 23.9489C10.7816 25.2899 7.47881 26.3379 7.33478 24.5449C7.33478 23.199 7.03678 22.0616 6.69906 20.82C6.29677 19.2903 5.83984 17.5868 5.83984 15.1184C5.83984 9.2231 10.6773 4.78796 16.4087 4.78796C22.145 4.78796 26.6398 9.44163 26.6398 15.173C26.649 17.8831 25.5827 20.4861 23.6748 22.4107C21.7669 24.3354 19.1733 25.4245 16.4633 25.4389ZM16.5477 9.88365C13.7565 9.73962 11.5812 11.6716 11.0994 14.7012C10.7021 17.2093 11.4074 20.2638 12.0083 20.4227C12.2964 20.4922 13.0215 19.9062 13.4734 19.4542C14.2208 19.9705 15.091 20.2806 15.9965 20.3532C17.3867 20.42 18.7477 19.9388 19.787 19.0129C20.8262 18.0869 21.4607 16.7902 21.554 15.4015C21.6084 14.0099 21.1142 12.6525 20.178 11.6215C19.2417 10.5905 17.9381 9.96825 16.5477 9.88862V9.88365Z" fill="white"/></g><defs><clipPath id="clip0_3522_11884"><rect width="32" height="32" fill="white"/></clipPath></defs></svg>',
                                          ],
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-8',
                                            'margin-right' => 'm-r-13',
                                            'margin-top' => 'm-t-2',
                                            'picture-size' => 'i-l',
                                          ],
                                          'link_props' => [
                                            'href' => 'https://max.ru/',
                                            'data-value' => 'external-link',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-4 st-4-lp st-6-tb st-12-mb',
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
                              'html' => '<font color="" class="tx-bw-4">Auxilium</font>',
                              'tag' => 'h1',
                              'block_props' => [
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-1">epistula@tabellario.romano</font>',
                              'tag' => 'h1',
                              'block_props' => [
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-0',
                                'margin-top' => 'm-t-0',
                                'font-header' => 't-rgl',
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
                  'column' => 'st-6-tb st-12-mb st-12 st-12-lp',
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
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
                  ],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [],
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
                                          'html' => 'Restitutionem petere<br>',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-12',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-blc-strk',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-m p-l-13 p-r-13',
                                            'margin-right' => 'm-r-12',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Button',
                                        'data' => [
                                          'html' => 'Ad auxilium scribere<br>',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-12',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-blc-strk',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-m p-l-13 p-r-13',
                                            'margin-right' => 'm-r-12',
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
  ['type' => 'site.FooterBottom2.2',],
];
