<?php
$img_path = wa()->getAppStaticUrl('site') . 'img/blocks/page_templates/product/headphones/';

return [
  [
    'type' => 'site.CustomHero',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-20-tb p-t-16-mb p-t-25',
          'padding-bottom' => 'p-b-20-tb p-b-16-mb p-b-28',
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
        'site-block-columns' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'linear-gradient(90deg,  #000000d6 0%,  #00000000 100%), center center / cover url('.$img_path.'hero-bg.jpg), linear-gradient(#000000, #000000)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(90deg,  #000000d6 0%,  #00000000 100%)',
                'name' => 'Self color',
                'css' => 'gradient',
                'gradient' => [
                  'type' => 'linear-gradient',
                  'degree' => '90',
                  'stops' => [
                    [
                      'color' => '#000000d6',
                      'stop' => '0',
                    ],
                    [
                      'color' => '#00000000',
                      'stop' => '100',
                    ],
                  ],
                ],
              ],
              [
                'type' => 'image',
                'value' => 'center center / cover url('.$img_path.'hero-bg.jpg)',
                'alignmentX' => 'center',
                'alignmentY' => 'center',
                'file_name' => 'hero-bg.jpg',
                'file_url' => $img_path.'hero-bg.jpg',
                'space' => 'cover',
                'name' => 'Image',
                'uuid' => 2,
              ],
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#000000, #000000)',
                'name' => 'Self color',
                'css' => '#000000',
                'uuid' => 3,
              ],
            ],
            'uuid' => 3,
          ],
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
                  'column' => 'st-12-mb st-5 st-5-lp st-6-tb',
                  'indestructible' => false,
                  'block_props' => [
                    'site-block-column' => [
                      'padding-top' => 'p-t-12-tb',
                      'padding-bottom' => 'p-b-12-tb p-b-30',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'column-max-width' => 'fx-9',
                      'flex-align' => 'y-c',
                      'margin-left' => 'm-l-a',
                      'margin-right' => 'm-r-a',
                      'padding-top' => 'p-t-12-tb',
                      'padding-bottom' => 'p-b-12-tb',
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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-3">IN TUA UNDA &nbsp;</font><br>',
                              'tag' => 'h2',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #8',
                                  'value' => 't-8',
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
                              'html' => '<span class="tx-wh">Non solum
<br>audire.&nbsp;<font color="" class="tx-brn-b">Sentire</font><br></span>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-hdn',
                                'margin-top' => 'm-t-0',
                                'margin-bottom' => 'm-b-18',
                                'align' => 't-l',
                                'line-height' => 't-lh-xs',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                              ],
                              'inline_props' => [],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-wh">Bassus qui adfectum dat.
Vox quae prope sonat.
Idem carmen — cum novis singulis.</font>',
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
                                'margin-bottom' => 'm-b-18',
                                'align' => 't-l',
                                'margin-right' => 'm-r-19',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-8',
                                'padding-bottom' => 'p-b-8',
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
                                        'type' => 'site.Button',
                                        'data' => [
                                          'html' => 'Elige One<br>',
                                          'tag' => 'a',
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-10',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-wht',
                                              'type' => 'palette',
                                            ],
                                            'button-size' => 'inp-l p-l-14 p-r-14',
                                            'margin-right' => 'm-r-14',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-wh">19 900 den.</font>',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-8',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-8',
                                'padding-bottom' => 'p-b-8',
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
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-4',
                                            'margin-right' => 'm-r-8',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
<mask id="mask0_3789_3133" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="32" height="32">
<rect width="32" height="32" fill="#D9D9D9"/>
</mask>
<g mask="url(#mask0_3789_3133)">
<path d="M9.33333 23.9998V7.99984H12V23.9998H9.33333ZM14.6667 29.3332V2.6665H17.3333V29.3332H14.6667ZM4 18.6665V13.3332H6.66667V18.6665H4ZM20 23.9998V7.99984H22.6667V23.9998H20ZM25.3333 18.6665V13.3332H28V18.6665H25.3333Z" fill="#97DD56"/>
</g>
</svg>
',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-brn-b">40 мм
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
                                          'html' => '<font color="" class="tx-bw-5">sonitores dynamici
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
                                            'margin-top' => 'm-t-6',
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
                        ],
                      ],
                    ],
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
          'padding-top' => 'p-t-18',
          'padding-bottom' => 'p-b-18',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-12',
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
                  'column' => 'st-12-mb st-12 st-12-lp st-12-tb',
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
                              'html' => '<font color="" class="tx-bw-4">BONAE NON SOLUM IN CARMINE DILECTO

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
                              'html' => 'Ad diem tuum.
Totum integrum<br>',
                              'tag' => 'h2',
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
                                'align' => 't-l',
                                'line-height' => 't-lh-xs',
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
                  'column' => 'st-6-tb st-12-mb st-4 st-4-lp',
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
                              'image' => [
                                'type' => 'svg',
                                'color' => [
                                  'name' => 'Palette',
                                  'value' => 'tx-brn-b',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                ],
                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="var(--brn-b)">
<mask id="mask0_3796_3184" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="48" height="48">
<rect width="48" height="48"></rect>
</mask>
<g mask="url(#mask0_3796_3184)">
<path d="M30.55 31.85L28.4 29.7L34.1 24.05L28.4 18.4L30.55 16.25L36.2 21.95L41.85 16.25L44 18.4L38.3 24.05L44 29.7L41.85 31.85L36.2 26.15L30.55 31.85ZM6 30V18H14L24 8V40L14 30H6ZM21 15.6L15.35 21H9V27H15.35L21 32.45V15.6Z"></path>
</g>
</svg>',
                                'fill' => 'removed',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                              ],
                              'inline_props' => [],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Urbs fit quietior&nbsp;<br>',
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
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Deminutio strepitus activa sonum circumstantem mitigat, cum concentrari vis.',
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
                  'column' => 'st-6-tb st-12-mb st-4 st-4-lp',
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
                              'image' => [
                                'type' => 'svg',
                                'color' => [
                                  'name' => 'Palette',
                                  'value' => 'tx-brn-b',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                ],
                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="var(--brn-b)">
<mask id="mask0_3796_3226" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="48" height="48">
<rect width="48" height="48"></rect>
</mask>
<g mask="url(#mask0_3796_3226)">
<path d="M6.22578 39.825C4.77578 38.375 4.05078 36.6 4.05078 34.5C4.05078 32.4 4.77578 30.625 6.22578 29.175C7.67578 27.725 9.45078 27 11.5508 27C12.4841 27 13.3258 27.1333 14.0758 27.4C14.8258 27.6667 15.4841 28.0333 16.0508 28.5V10L40.0008 6V30.5C40.0008 32.6 39.2758 34.375 37.8258 35.825C36.3758 37.275 34.6008 38 32.5008 38C30.4008 38 28.6258 37.275 27.1758 35.825C25.7258 34.375 25.0008 32.6 25.0008 30.5C25.0008 28.4 25.7258 26.625 27.1758 25.175C28.6258 23.725 30.4008 23 32.5008 23C33.4341 23 34.2758 23.1333 35.0258 23.4C35.7758 23.6667 36.4341 24.0333 37.0008 24.5V15.3L19.0508 18.3V34.5C19.0508 36.6 18.3258 38.375 16.8758 39.825C15.4258 41.275 13.6508 42 11.5508 42C9.45078 42 7.67578 41.275 6.22578 39.825Z"></path>
</g>
</svg>',
                                'fill' => 'removed',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                              ],
                              'inline_props' => [],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Mundus — uno pressu',
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
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Modus perspicuitatis adiuvat sermones et sonos circum audire, in auriculis manens.',
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
                  'column' => 'st-6-tb st-12-mb st-4 st-4-lp',
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
                              'image' => [
                                'type' => 'svg',
                                'color' => [
                                  'name' => 'Palette',
                                  'value' => 'tx-brn-b',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                ],
                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="var(--brn-b)">
<mask id="mask0_3790_3264" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="48" height="48">
<rect width="48" height="48"></rect>
</mask>
<g mask="url(#mask0_3790_3264)">
<path d="M7 40C6.2 40 5.5 39.7 4.9 39.1C4.3 38.5 4 37.8 4 37V11C4 10.2 4.3 9.5 4.9 8.9C5.5 8.3 6.2 8 7 8H41C41.8 8 42.5 8.3 43.1 8.9C43.7 9.5 44 10.2 44 11H7V37H13.85V40H7ZM41 37V17H31.05V37H41ZM30.3 40C29.7 40 29.175 39.775 28.725 39.325C28.275 38.875 28.05 38.35 28.05 37.75V16.25C28.05 15.65 28.275 15.125 28.725 14.675C29.175 14.225 29.7 14 30.3 14H41.75C42.35 14 42.875 14.225 43.325 14.675C43.775 15.125 44 15.65 44 16.25V37.75C44 38.35 43.775 38.875 43.325 39.325C42.875 39.775 42.35 40 41.75 40H30.3ZM36.05 22C36.45 22 36.8 21.85 37.1 21.55C37.4 21.25 37.55 20.9 37.55 20.5C37.55 20.1 37.4 19.75 37.1 19.45C36.8 19.15 36.442 19 36.026 19C35.642 19 35.3 19.15 35 19.45C34.7 19.75 34.55 20.108 34.55 20.524C34.55 20.908 34.7 21.25 35 21.55C35.3 21.85 35.65 22 36.05 22ZM17.95 40L17 36.7C16.4 36.2 15.925 35.5877 15.575 34.863C15.225 34.1383 15.05 33.3507 15.05 32.5C15.05 31.6493 15.225 30.8617 15.575 30.137C15.925 29.4123 16.4 28.8 17 28.3L17.95 25H23.15L24.1 28.3C24.7 28.8 25.175 29.4123 25.525 30.137C25.875 30.8617 26.05 31.6493 26.05 32.5C26.05 33.3507 25.875 34.1383 25.525 34.863C25.175 35.5877 24.7 36.2 24.1 36.7L23.15 40H17.95ZM20.5735 36C21.5912 36 22.425 35.6603 23.075 34.981C23.725 34.3017 24.05 33.4767 24.05 32.506C24.05 31.5353 23.7 30.7083 23 30.025C22.3 29.3417 21.4833 29 20.55 29C19.6167 29 18.8 29.3295 18.1 29.9885C17.4 30.6472 17.05 31.4805 17.05 32.4885C17.05 33.4962 17.3828 34.3333 18.0485 35C18.7138 35.6667 19.5555 36 20.5735 36Z"></path>
</g>
</svg>',
                                'fill' => 'removed',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                              ],
                              'inline_props' => [],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Computatrum et telephonum simul ',
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
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Coniunge duo dispositiva simul. Transi ab opere ad indicem carminum dilectum.',
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
                  'column' => 'st-6-tb st-12-mb st-4 st-4-lp',
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
                              'image' => [
                                'type' => 'svg',
                                'color' => [
                                  'name' => 'Palette',
                                  'value' => 'tx-brn-b',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                ],
                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="var(--brn-b)">
<mask id="mask0_3790_3270" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="48" height="48">
<rect width="48" height="48"></rect>
</mask>
<g mask="url(#mask0_3790_3270)">
<path d="M6.5 36C5.25 36 4.1875 35.5625 3.3125 34.6875C2.4375 33.8125 2 32.75 2 31.5V16.5C2 15.25 2.4375 14.1875 3.3125 13.3125C4.1875 12.4375 5.25 12 6.5 12H37.5C38.75 12 39.8125 12.4375 40.6875 13.3125C41.5625 14.1875 42 15.25 42 16.5V31.5C42 32.75 41.5625 33.8125 40.6875 34.6875C39.8125 35.5625 38.75 36 37.5 36H6.5ZM6.5 33H37.5C37.925 33 38.2813 32.8562 38.569 32.5685C38.8563 32.2812 39 31.925 39 31.5V16.5C39 16.075 38.8563 15.7187 38.569 15.431C38.2813 15.1437 37.925 15 37.5 15H6.5C6.075 15 5.71883 15.1437 5.4315 15.431C5.14383 15.7187 5 16.075 5 16.5V31.5C5 31.925 5.14383 32.2812 5.4315 32.5685C5.71883 32.8562 6.075 33 6.5 33ZM43.5 28.65V19.35H44.5C44.9 19.35 45.25 19.5 45.55 19.8C45.85 20.1 46 20.45 46 20.85V27.15C46 27.55 45.85 27.9 45.55 28.2C45.25 28.5 44.9 28.65 44.5 28.65H43.5Z"></path>
<path d="M7.80078 16.5V31.5H10.8008V16.5H7.80078Z"></path>
<path d="M12.8008 16.5V31.5H15.8008V16.5H12.8008Z"></path>
<path d="M17.8008 16.5V31.5H20.8008V16.5H17.8008Z"></path>
<path d="M22.8008 16.5V31.5H25.8008V16.5H22.8008Z"></path>
<path d="M27.8008 16.5V31.5H30.8008V16.5H27.8008Z"></path>
<path d="M32.8008 16.5V31.5H35.8008V16.5H32.8008Z"></path>
</g>
</svg>',
                                'fill' => 'removed',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                              ],
                              'inline_props' => [],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Usque ad 40 horas musicae',
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
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Et usque ad 30 horas cum strepitus deminutione. Sufficit itineri, operi et consiliis vespertinis.',
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
                  'column' => 'st-6-tb st-12-mb st-4 st-4-lp',
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
                              'image' => [
                                'type' => 'svg',
                                'color' => [
                                  'name' => 'Palette',
                                  'value' => 'tx-brn-b',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                ],
                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="var(--brn-b)">
<mask id="mask0_3790_3148" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="48" height="48">
<rect width="48" height="48"></rect>
</mask>
<g mask="url(#mask0_3790_3148)">
<path d="M19.65 39.75L33.6 23H24.6L26.4 8.7L13.75 27H21.45L19.65 39.75ZM16 44L18 30H8L26 4H30L28 20H40L20 44H16Z"></path>
</g>
</svg>',
                                'fill' => 'removed',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                              ],
                              'inline_props' => [],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Quinque horae in decem minutis',
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
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Brevis oneratio ante exitum — et musica iterum tecum est. Portus USB-C.',
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
                  'column' => 'st-6-tb st-12-mb st-4 st-4-lp',
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
                              'image' => [
                                'type' => 'svg',
                                'color' => [
                                  'name' => 'Palette',
                                  'value' => 'tx-brn-b',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                ],
                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="var(--brn-b)">
<mask id="mask0_3790_3258" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="48" height="48">
<rect width="48" height="48"></rect>
</mask>
<g mask="url(#mask0_3790_3258)">
<path d="M10.8503 39H37.1503L34.0003 17H14.0003L10.8503 39ZM24.0003 14C24.8503 14 25.5628 13.7125 26.1378 13.1375C26.7128 12.5625 27.0003 11.85 27.0003 11C27.0003 10.15 26.7128 9.4375 26.1378 8.8625C25.5628 8.2875 24.8503 8 24.0003 8C23.1503 8 22.4378 8.2875 21.8628 8.8625C21.2878 9.4375 21.0003 10.15 21.0003 11C21.0003 11.85 21.2878 12.5625 21.8628 13.1375C22.4378 13.7125 23.1503 14 24.0003 14ZM29.2003 14H34.0003C34.7706 14 35.4381 14.2417 36.0028 14.725C36.5678 15.2083 36.9003 15.8333 37.0003 16.6L40.1003 38.6C40.2336 39.5 40.0066 40.2917 39.4193 40.975C38.8316 41.6583 38.0753 42 37.1503 42H10.8503C9.92527 42 9.1691 41.6583 8.58177 40.975C7.9941 40.2917 7.76693 39.5 7.90027 38.6L11.0003 16.6C11.1003 15.8333 11.4328 15.2083 11.9978 14.725C12.5624 14.2417 13.2299 14 14.0003 14H18.8003C18.5336 13.5333 18.3336 13.0572 18.2003 12.5715C18.0669 12.0862 18.0003 11.5623 18.0003 11C18.0003 9.33333 18.5836 7.91667 19.7503 6.75C20.9169 5.58333 22.3336 5 24.0003 5C25.6669 5 27.0836 5.58333 28.2503 6.75C29.4169 7.91667 30.0003 9.33333 30.0003 11C30.0003 11.5667 29.9336 12.0917 29.8003 12.575C29.6669 13.0583 29.4669 13.5333 29.2003 14Z"></path>
</g>
</svg>',
                                'fill' => 'removed',
                              ],
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                              ],
                              'inline_props' => [],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => 'Tantum 248 grammata ',
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
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Structura levis et pulvini molles — pro longis albumis magnisque itineribus.',
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
    'type' => 'site.CustomHero4',
    'data' => [
      'block_props' => [
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
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
                  'column' => 'st-6 st-6-lp st-12-mb st-12-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-s',
                      'padding-bottom' => 'p-b-30',
                      'padding-top' => 'p-t-30',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-l',
                  ],
                  'inline_props' => [
                    'site-block-column' => [
                      'background' => [
                        'type' => 'self_color',
                        'value' => 'center top / cover url('.$img_path.'park.jpg)',
                        'name' => 'Self color',
                        'layers' => [
                          [
                            'alignmentX' => 'center',
                            'alignmentY' => 'top',
                            'css' => '',
                            'file_name' => 'park.jpg',
                            'file_url' => $img_path.'park.jpg',
                            'name' => 'Image',
                            'space' => 'cover',
                            'type' => 'image',
                            'value' => 'center top / cover url('.$img_path.'park.jpg)',
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
                      'flex-align-vertical' => 'a-c-s',
                      'padding-bottom' => 'p-b-16-mb p-b-27 p-b-23-tb',
                      'padding-top' => 'p-t-16-mb p-t-24 p-t-23-tb',
                      'background' => [
                        'name' => 'complementary',
                        'value' => 'bg-brn-b-2',
                        'type' => 'palette',
                        'scheme' => 'complementary',
                        'uuid' => 1,
                        'layers' => [
                          [
                            'name' => 'complementary',
                            'value' => 'bg-brn-b-2',
                            'type' => 'palette',
                            'scheme' => 'complementary',
                          ],
                        ],
                      ],
                    ],
                    'site-block-column-wrapper' => [
                      'column-max-width' => 'fx-6',
                      'flex-align' => 'y-c',
                      'margin-left' => 'm-l-a-tb m-l-0-mb',
                      'margin-right' => 'm-r-a-tb m-r-0-mb',
                      'padding-bottom' => 'p-b-14',
                      'padding-left' => 'p-l-blc-clm',
                      'padding-right' => 'p-r-blc-clm',
                      'padding-top' => 'p-t-14',
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
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-4">TUA PARVA PAUSA</font>',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #7',
                                  'value' => 't-7',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-8',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'h2',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-blc">In medio
urbis.
<br>In tuo mundo</font>',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-hdn',
                                'font-size' => [
                                  'name' => 'Size #2',
                                  'value' => 't-2',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                                'margin-top' => 'm-t-0',
                                'line-height' => 't-lh-xs',
                              ],
                              'tag' => 'h2',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-2">Prima coffea. Iter notum. Pauca minuta ante congressum. Accende dilectum — et tempus tibi inveni.
</font>',
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
                              'tag' => 'h2',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-bottom' => 'p-b-12',
                                'padding-top' => 'p-t-8',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-lp n-wr-tb n-wr-mb',
                                'justify-align' => 'j-s',
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
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-brn-b-8',
                                              'type' => 'palette',
                                              'scheme' => 'complementary',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48" fill="var(--brn-b-8)">
<mask id="mask0_3796_3202" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="48" height="48">
<rect width="48" height="48"></rect>
</mask>
<g mask="url(#mask0_3796_3202)">
<path d="M37.1492 16.45C36.2826 17.3167 35.3159 17.9583 34.2492 18.375C33.1826 18.7917 32.0826 19 30.9492 19C29.8159 19 28.7159 18.7917 27.6492 18.375C26.5826 17.9583 25.6159 17.3167 24.7492 16.45L20.9992 12.7C20.4326 12.1333 19.8076 11.7167 19.1242 11.45C18.4409 11.1833 17.7326 11.05 16.9992 11.05C16.2659 11.05 15.5576 11.1833 14.8742 11.45C14.1909 11.7167 13.5659 12.1333 12.9992 12.7L9.59922 16.1L7.44922 13.95L10.8492 10.55C11.7159 9.68333 12.6826 9.04167 13.7492 8.625C14.8159 8.20833 15.8992 8 16.9992 8C18.0992 8 19.1742 8.20833 20.2242 8.625C21.2742 9.04167 22.2326 9.68333 23.0992 10.55L26.8492 14.3C27.4159 14.8667 28.0409 15.2917 28.7242 15.575C29.4076 15.8583 30.1326 16 30.8992 16C31.6659 16 32.3992 15.8583 33.0992 15.575C33.7992 15.2917 34.4326 14.8667 34.9992 14.3L38.3992 10.9L40.5492 13.05L37.1492 16.45ZM37.1492 26.95C36.2826 27.8167 35.3159 28.4583 34.2492 28.875C33.1826 29.2917 32.0826 29.5 30.9492 29.5C29.8159 29.5 28.7159 29.2917 27.6492 28.875C26.5826 28.4583 25.6159 27.8167 24.7492 26.95L20.9992 23.2C20.4326 22.6333 19.8076 22.2167 19.1242 21.95C18.4409 21.6833 17.7326 21.55 16.9992 21.55C16.2659 21.55 15.5576 21.6833 14.8742 21.95C14.1909 22.2167 13.5659 22.6333 12.9992 23.2L9.59922 26.6L7.44922 24.5L10.8492 21.05C11.7159 20.1833 12.6826 19.5417 13.7492 19.125C14.8159 18.7083 15.8992 18.5 16.9992 18.5C18.0992 18.5 19.1742 18.7083 20.2242 19.125C21.2742 19.5417 22.2326 20.1833 23.0992 21.05L26.8492 24.8C27.4159 25.3667 28.0492 25.7917 28.7492 26.075C29.4492 26.3583 30.1826 26.5 30.9492 26.5C31.7159 26.5 32.4409 26.3583 33.1242 26.075C33.8076 25.7917 34.4326 25.3667 34.9992 24.8L38.3992 21.4L40.5492 23.55L37.1492 26.95ZM37.0992 37.45C36.2326 38.3167 35.2742 38.9583 34.2242 39.375C33.1742 39.7917 32.0826 40 30.9492 40C29.8159 40 28.7159 39.7917 27.6492 39.375C26.5826 38.9583 25.6159 38.3167 24.7492 37.45L20.9492 33.7C20.3826 33.1333 19.7576 32.7167 19.0742 32.45C18.3909 32.1833 17.6826 32.05 16.9492 32.05C16.2159 32.05 15.5076 32.1833 14.8242 32.45C14.1409 32.7167 13.5159 33.1333 12.9492 33.7L9.54922 37.1L7.44922 35L10.8492 31.55C11.7159 30.6833 12.6742 30.0417 13.7242 29.625C14.7742 29.2083 15.8492 29 16.9492 29C18.0492 29 19.1326 29.2083 20.1992 29.625C21.2659 30.0417 22.2326 30.6833 23.0992 31.55L26.8492 35.3C27.4159 35.8667 28.0492 36.2917 28.7492 36.575C29.4492 36.8583 30.1826 37 30.9492 37C31.7159 37 32.4409 36.8583 33.1242 36.575C33.8076 36.2917 34.4326 35.8667 34.9992 35.3L38.3992 31.9L40.4992 34.05L37.0992 37.45Z"></path>
</g>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                          'block_props' => [
                                            'margin-right' => 'm-r-12 m-r-10-mb',
                                            'margin-top' => 'm-t-0-mb',
                                            'picture-size' => 'i-xxl',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.SubColumn',
                                        'data' => [
                                          'block_props' => [
                                            'padding-bottom' => 'p-b-8',
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
                                                      'html' => '<font color="" class="tx-blc">Tres modi pro animo</font>',
                                                      'block_props' => [
                                                        'align' => 't-l',
                                                        'font-header' => 't-hdn',
                                                        'font-size' => [
                                                          'name' => 'Size #6',
                                                          'value' => 't-6',
                                                          'unit' => 'px',
                                                          'type' => 'library',
                                                        ],
                                                        'margin-bottom' => 'm-b-5',
                                                        'margin-top' => 'm-t-0',
                                                      ],
                                                      'tag' => 'p',
                                                    ],
                                                    'children' => [],
                                                  ],
                                                  [
                                                    'type' => 'site.Heading',
                                                    'data' => [
                                                      'html' => '<font color="" class="tx-bw-4">Deminutio strepitus · Perspicuitas · Communis</font>',
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
                                                      'tag' => 'h1',
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
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'Cape tuum One<br>',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-blc',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-l p-l-14 p-r-14',
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
    'type' => 'site.CustomAdvantages3Cols2',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18 p-t-16-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-12',
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
                  'column' => 'st-12-mb st-12 st-12-lp st-12-tb',
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
                              'html' => '<font color="" class="tx-bw-4">PROPIUS AD NUMEROS

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
                                'margin-bottom' => 'm-b-0',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-1">Omnia quae magni sunt</font>',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
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
                      'padding-left' => 'p-l-16',
                      'border-color' => [
                        'name' => 'complementary',
                        'value' => 'br-brn-b',
                        'type' => 'palette',
                        'scheme' => 'complementary',
                      ],
                      'border-width' => [
                        'name' => 'Width 1',
                        'value' => 'b-w-s',
                        'unit' => 'px',
                        'type' => 'library',
                      ],
                      'border-style' => [
                        'value' => 'b-d-l',
                        'type' => 'separate',
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
                              'html' => '<font color="" class="tx-bw-4">MUSICAE UNO ONERE</font>',
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
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-8',
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
                                          'html' => '40',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'margin-top' => 'm-t-0',
                                            'align' => 't-l',
                                            'line-height' => 't-lh-xs',
                                            'margin-right' => 'm-r-12',
                                            'margin-bottom' => 'm-b-0',
                                          ],
                                          'inline_props' => [
                                            'font-size' => [
                                              'type' => 'self_size',
                                              'value' => '60px',
                                              'name' => 'Self size',
                                              'unit' => 'px',
                                            ],
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Heading',
                                        'data' => [
                                          'html' => 'horae',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-18',
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
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-4 st-4-lp st-12-mb st-4-tb',
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
                      'padding-left' => 'p-l-16',
                      'border-color' => [
                        'name' => 'complementary',
                        'value' => 'br-brn-b',
                        'type' => 'palette',
                        'scheme' => 'complementary',
                      ],
                      'border-width' => [
                        'name' => 'Width 1',
                        'value' => 'b-w-s',
                        'unit' => 'px',
                        'type' => 'library',
                      ],
                      'border-style' => [
                        'value' => 'b-d-l',
                        'type' => 'separate',
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
                              'html' => '<font color="" class="tx-bw-4">PONDUS AURICULARUM</font>',
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
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-8',
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
                                          'html' => '248',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'margin-top' => 'm-t-0',
                                            'align' => 't-l',
                                            'line-height' => 't-lh-xs',
                                            'margin-right' => 'm-r-12',
                                            'margin-bottom' => 'm-b-0',
                                          ],
                                          'inline_props' => [
                                            'font-size' => [
                                              'type' => 'self_size',
                                              'value' => '60px',
                                              'name' => 'Self size',
                                              'unit' => 'px',
                                            ],
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Heading',
                                        'data' => [
                                          'html' => 'gamma',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-18',
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
              [
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-4 st-4-lp st-12-mb st-4-tb',
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
                      'padding-left' => 'p-l-16',
                      'border-color' => [
                        'name' => 'complementary',
                        'value' => 'br-brn-b',
                        'type' => 'palette',
                        'scheme' => 'complementary',
                      ],
                      'border-width' => [
                        'name' => 'Width 1',
                        'value' => 'b-w-s',
                        'unit' => 'px',
                        'type' => 'library',
                      ],
                      'border-style' => [
                        'value' => 'b-d-l',
                        'type' => 'separate',
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
                              'html' => '<font color="" class="tx-bw-4">SIMUL</font>',
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
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-8',
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
                                          'html' => '2',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'margin-top' => 'm-t-0',
                                            'align' => 't-l',
                                            'line-height' => 't-lh-xs',
                                            'margin-right' => 'm-r-12',
                                            'margin-bottom' => 'm-b-0',
                                          ],
                                          'inline_props' => [
                                            'font-size' => [
                                              'type' => 'self_size',
                                              'value' => '60px',
                                              'name' => 'Self size',
                                              'unit' => 'px',
                                            ],
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Heading',
                                        'data' => [
                                          'html' => 'dispositiva',
                                          'tag' => 'h3',
                                          'block_props' => [
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #5',
                                              'value' => 't-5',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-18',
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
    'type' => 'site.CustomText4',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-12-mb',
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
                  'column' => 'st-6 st-6-lp st-6-tb st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-14',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-14',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
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
                                'padding-bottom' => 'p-b-4',
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'type' => 'library',
                                  'unit' => 'px',
                                  'value' => 'b-w-s',
                                ],
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'type' => 'palette',
                                  'value' => 'br-b-opc-2',
                                ],
                                'border-style' => [
                                  'type' => 'separate',
                                  'value' => 'b-d-b',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Forma</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-top' => 'm-t-0',
                                            'margin-right' => 'm-r-a',
                                          ],
                                          'tag' => 'p',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-1">Plenae magnitudinis, sine filo
</font>',
                                          'block_props' => [
                                            'align' => 't-r',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-left' => 'm-l-12',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-4',
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'type' => 'library',
                                  'unit' => 'px',
                                  'value' => 'b-w-s',
                                ],
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'type' => 'palette',
                                  'value' => 'br-b-opc-2',
                                ],
                                'border-style' => [
                                  'type' => 'separate',
                                  'value' => 'b-d-b',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Sonitores</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-top' => 'm-t-0',
                                            'margin-right' => 'm-r-a',
                                          ],
                                          'tag' => 'p',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-1">Dynamici, 40 mm
</font>',
                                          'block_props' => [
                                            'align' => 't-r',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-left' => 'm-l-12',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-4',
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'type' => 'library',
                                  'unit' => 'px',
                                  'value' => 'b-w-s',
                                ],
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'type' => 'palette',
                                  'value' => 'br-b-opc-2',
                                ],
                                'border-style' => [
                                  'type' => 'separate',
                                  'value' => 'b-d-b',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Nexus</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-top' => 'm-t-0',
                                            'margin-right' => 'm-r-a',
                                          ],
                                          'tag' => 'p',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-1">Bluetooth 5.3, duo dispositiva
</font>',
                                          'block_props' => [
                                            'align' => 't-r',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-left' => 'm-l-12',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-4',
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'type' => 'library',
                                  'unit' => 'px',
                                  'value' => 'b-w-s',
                                ],
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'type' => 'palette',
                                  'value' => 'br-b-opc-2',
                                ],
                                'border-style' => [
                                  'type' => 'separate',
                                  'value' => 'b-d-b',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Modi</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-top' => 'm-t-0',
                                            'margin-right' => 'm-r-a',
                                          ],
                                          'tag' => 'p',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-1">Deminutio strepitus, perspicuitas, communis
</font>',
                                          'block_props' => [
                                            'align' => 't-r',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-left' => 'm-l-12',
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
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-6 st-6-lp st-6-tb st-12-mb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-14',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-14',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
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
                                'padding-bottom' => 'p-b-4',
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'type' => 'library',
                                  'unit' => 'px',
                                  'value' => 'b-w-s',
                                ],
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'type' => 'palette',
                                  'value' => 'br-b-opc-2',
                                ],
                                'border-style' => [
                                  'type' => 'separate',
                                  'value' => 'b-d-b',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Autonomia
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-top' => 'm-t-0',
                                            'margin-right' => 'm-r-a',
                                          ],
                                          'tag' => 'p',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-1">Usque ad 40 horas</font>',
                                          'block_props' => [
                                            'align' => 't-r',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-left' => 'm-l-12',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-4',
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'type' => 'library',
                                  'unit' => 'px',
                                  'value' => 'b-w-s',
                                ],
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'type' => 'palette',
                                  'value' => 'br-b-opc-2',
                                ],
                                'border-style' => [
                                  'type' => 'separate',
                                  'value' => 'b-d-b',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Oneratio </font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-top' => 'm-t-0',
                                            'margin-right' => 'm-r-a',
                                          ],
                                          'tag' => 'p',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-1">USB-C
</font>',
                                          'block_props' => [
                                            'align' => 't-r',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-left' => 'm-l-12',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-4',
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'type' => 'library',
                                  'unit' => 'px',
                                  'value' => 'b-w-s',
                                ],
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'type' => 'palette',
                                  'value' => 'br-b-opc-2',
                                ],
                                'border-style' => [
                                  'type' => 'separate',
                                  'value' => 'b-d-b',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Inclusa
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-top' => 'm-t-0',
                                            'margin-right' => 'm-r-a',
                                          ],
                                          'tag' => 'p',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-1">60 min</font>',
                                          'block_props' => [
                                            'align' => 't-r',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-left' => 'm-l-12',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-10',
                                'padding-bottom' => 'p-b-4',
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'type' => 'library',
                                  'unit' => 'px',
                                  'value' => 'b-w-s',
                                ],
                                'border-color' => [
                                  'name' => 'semi-transparent-black',
                                  'type' => 'palette',
                                  'value' => 'br-b-opc-2',
                                ],
                                'border-style' => [
                                  'type' => 'separate',
                                  'value' => 'b-d-b',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Oneratio celeris
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-top' => 'm-t-0',
                                            'margin-right' => 'm-r-a',
                                          ],
                                          'tag' => 'p',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-1">10 minuta — usque ad 5 horas musicae</font>',
                                          'block_props' => [
                                            'align' => 't-r',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-8',
                                            'margin-left' => 'm-l-12',
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
            ],
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomHero2',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18 p-t-16-mb',
          'padding-bottom' => 'p-b-18 p-b-16-mb',
          'background' => [
            'name' => 'black and white',
            'value' => 'bg-blc',
            'type' => 'palette',
            'uuid' => 2,
            'layers' => [
              [
                'name' => 'black and white',
                'value' => 'bg-blc',
                'type' => 'palette',
                'uuid' => 2,
              ],
            ],
          ],
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-12',
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
                  'column' => 'st-6-lp st-12-mb st-12-tb st-6',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-10-mb p-b-12',
                      'padding-top' => 'p-t-10-mb p-t-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-right' => 'p-r-19 p-r-0-mb p-r-0-tb',
                    ],
                  ],
                  'wrapper_props' => [],
                  'inline_props' => [
                    'site-block-column' => [],
                    'site-block-column-wrapper' => [],
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
                              'html' => '<font color="" class="tx-bw-3">RESTAT PREMERE PLAY

</font>',
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
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-wh">Tuus dies.&nbsp;</font>',
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
                                'align' => 't-l',
                                'margin-bottom' => 'm-b-0',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-brn-b">Tua musica.</font>',
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
                                'margin-bottom' => 'm-b-18',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Image',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                                'border-radius' => 'b-r-l',
                                'margin-right' => 'm-r-19 m-r-0-tb',
                                'picture-size' => 'custom',
                                'margin-top' => 'm-t-a',
                              ],
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path.'detail-opt.jpg',
                              ],
                              'inline_props' => [
                                '--width' => [
                                  'unit' => 'px',
                                  'raw_value' => '380',
                                  'value' => '380px',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-6-lp st-12-mb st-12-tb st-6',
                  'block_props' => [
                    'site-block-column' => [
                      'flex-align-vertical' => 'a-c-c',
                      'padding-bottom' => 'p-b-10-mb p-b-12',
                      'padding-top' => 'p-t-0-mb p-t-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-18 p-b-16-mb',
                      'padding-top' => 'p-t-18 p-t-16-mb',
                      'padding-left' => 'p-l-19 p-l-16-mb',
                      'padding-right' => 'p-r-19 p-r-16-mb',
                      'border-radius' => 'b-r-l',
                    ],
                  ],
                  'wrapper_props' => [],
                  'inline_props' => [
                    'site-block-column-wrapper' => [
                      'background' => [
                        'type' => 'self_color',
                        'value' => 'linear-gradient(#1b1b1b, #1b1b1b)',
                        'name' => 'Self color',
                        'layers' => [
                          [
                            'type' => 'self_color',
                            'value' => 'linear-gradient(#1b1b1b, #1b1b1b)',
                            'name' => 'Self color',
                            'css' => '#1b1b1b',
                            'uuid' => 1,
                          ],
                        ],
                        'uuid' => 1,
                      ],
                    ],
                    'site-block-column' => [
                      'min-height' => [
                        'name' => 'Content',
                        'value' => 'none',
                        'type' => 'content',
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
                                'margin-bottom' => 'm-b-8',
                                'margin-right' => 'm-r-14-lp m-r-0-mb m-r-16',
                                'padding-bottom' => 'p-b-4',
                                'padding-top' => 'p-t-4',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
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
                                          'html' => '<font color="" class="tx-bw-3">AURICULAE SINE FILO
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #8',
                                              'value' => 't-8',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-4',
                                            'margin-right' => 'm-r-a',
                                            'margin-top' => 'm-t-0',
                                          ],
                                          'tag' => 'h1',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-0',
                                            'margin-right' => 'm-r-8',
                                            'margin-top' => 'm-t-4',
                                            'picture-size' => 'i-xxs',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
<circle cx="7" cy="7.5" r="6" fill="#D9D9D9"/>
<circle cx="7" cy="7.5" r="5.5" stroke="black" stroke-opacity="0.1"/>
</svg>
',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Heading',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Graphitum
</font>',
                                          'block_props' => [
                                            'align' => 't-r',
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #7',
                                              'value' => 't-7',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-bottom' => 'm-b-4',
                                            'margin-top' => 'm-t-0',
                                          ],
                                          'tag' => 'h1',
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
                              'html' => '<span class="tx-bw-6"><font class="tx-wh" color="">QUIETIUS One
</font></span>',
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
                              'tag' => 'h1',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font class="tx-wh">19 900 den.</font>',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #1',
                                  'value' => 't-1',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-12',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'h1',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'Adde in corbem<br>',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-bottom' => 'm-b-18',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-b',
                                  'type' => 'palette',
                                  'scheme' => 'complementary',
                                ],
                                'button-size' => 'inp-l p-l-14 p-r-14',
                                'full-width' => 'f-w',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-8',
                                'margin-right' => 'm-r-14-lp m-r-0-mb m-r-16',
                                'padding-bottom' => 'p-b-4',
                                'padding-top' => 'p-t-4',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
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
                                            'margin-bottom' => 'm-b-14',
                                            'margin-right' => 'm-r-10',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-brn-b',
                                              'type' => 'palette',
                                              'scheme' => 'complementary',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="var(--brn-b)">
<mask id="mask0_3808_3193" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="28" height="28">
<rect width="28" height="28"></rect>
</mask>
<g mask="url(#mask0_3808_3193)">
<path d="M5.83333 9.33333V22.1667H22.1667V9.33333H18.6667V18.6667L14 16.3333L9.33333 18.6667V9.33333H5.83333ZM5.83333 24.5C5.19167 24.5 4.64236 24.2715 4.18542 23.8146C3.72847 23.3576 3.5 22.8083 3.5 22.1667V7.6125C3.5 7.34028 3.54375 7.07778 3.63125 6.825C3.71875 6.57222 3.85 6.33889 4.025 6.125L5.48333 4.34583C5.69722 4.07361 5.96458 3.86458 6.28542 3.71875C6.60625 3.57292 6.94167 3.5 7.29167 3.5H20.7083C21.0583 3.5 21.3937 3.57292 21.7146 3.71875C22.0354 3.86458 22.3028 4.07361 22.5167 4.34583L23.975 6.125C24.15 6.33889 24.2813 6.57222 24.3688 6.825C24.4563 7.07778 24.5 7.34028 24.5 7.6125V22.1667C24.5 22.8083 24.2715 23.3576 23.8146 23.8146C23.3576 24.2715 22.8083 24.5 22.1667 24.5H5.83333ZM6.3 7H21.7L20.7083 5.83333H7.29167L6.3 7ZM11.6667 9.33333V14.875L14 13.7083L16.3333 14.875V9.33333H11.6667Z"></path>
</g>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.SubColumn',
                                        'data' => [
                                          'block_props' => [
                                            'padding-top' => 'p-t-0',
                                            'padding-bottom' => 'p-b-10',
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
                                                      'html' => '<font color="" class="tx-bw-6">Vectura — 490 den.</font>',
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
                                                  [
                                                    'type' => 'site.Paragraph',
                                                    'data' => [
                                                      'html' => '<font color="" class="tx-bw-4">Pretium statim videtur, ante ordinationem.
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
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-8',
                                'margin-right' => 'm-r-14-lp m-r-0-mb m-r-16',
                                'padding-bottom' => 'p-b-4',
                                'padding-top' => 'p-t-4',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
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
                                            'margin-bottom' => 'm-b-14',
                                            'margin-right' => 'm-r-10',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-brn-b',
                                              'type' => 'palette',
                                              'scheme' => 'complementary',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="var(--brn-b)">
<mask id="mask0_3808_3099" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="28" height="28">
<rect width="28" height="28"></rect>
</mask>
<g mask="url(#mask0_3808_3099)">
<path d="M12.7724 18.1413L19.3641 11.5497L17.7016 9.88717L12.7724 14.8163L10.3224 12.3663L8.6599 14.0288L12.7724 18.1413ZM13.9974 25.6663C11.2946 24.9858 9.06337 23.4351 7.30365 21.0143C5.54392 18.5934 4.66406 15.9052 4.66406 12.9497V5.83301L13.9974 2.33301L23.3307 5.83301V12.9497C23.3307 15.9052 22.4509 18.5934 20.6911 21.0143C18.9314 23.4351 16.7002 24.9858 13.9974 25.6663ZM13.9974 23.2163C16.0196 22.5747 17.6918 21.2913 19.0141 19.3663C20.3363 17.4413 20.9974 15.3025 20.9974 12.9497V7.43717L13.9974 4.81217L6.9974 7.43717V12.9497C6.9974 15.3025 7.65851 17.4413 8.98073 19.3663C10.303 21.2913 11.9752 22.5747 13.9974 23.2163Z"></path>
</g>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.SubColumn',
                                        'data' => [
                                          'block_props' => [
                                            'padding-top' => 'p-t-0',
                                            'padding-bottom' => 'p-b-10',
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
                                                      'html' => '<font color="" class="tx-bw-6">Garantia — 2 anni
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
                                                        'margin-bottom' => 'm-b-0',
                                                        'align' => 't-l',
                                                      ],
                                                    ],
                                                    'children' => [],
                                                  ],
                                                  [
                                                    'type' => 'site.Paragraph',
                                                    'data' => [
                                                      'html' => '<font color="" class="tx-bw-4">Auxilium de quaestionibus operis auricularum.
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
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-8',
                                'margin-right' => 'm-r-14-lp m-r-0-mb m-r-16',
                                'padding-bottom' => 'p-b-4',
                                'padding-top' => 'p-t-4',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp n-wr-mb',
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
                                            'margin-bottom' => 'm-b-14',
                                            'margin-right' => 'm-r-10',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-brn-b',
                                              'type' => 'palette',
                                              'scheme' => 'complementary',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" fill="var(--brn-b)">
<mask id="mask0_3808_3205" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="28" height="28">
<rect width="28" height="28"></rect>
</mask>
<g mask="url(#mask0_3808_3205)">
<path d="M9.12682 11.6664L12.5102 15.0498C12.7435 15.2831 12.8553 15.5553 12.8456 15.8664C12.8359 16.1775 12.724 16.4498 12.5102 16.6831C12.2768 16.9164 12.0046 17.038 11.6935 17.0477C11.3824 17.0574 11.1102 16.9456 10.8768 16.7123L5.48099 11.3164C5.36432 11.1998 5.28168 11.0734 5.23307 10.9373C5.18446 10.8012 5.16016 10.6553 5.16016 10.4998C5.16016 10.3442 5.18446 10.1984 5.23307 10.0623C5.28168 9.92616 5.36432 9.79977 5.48099 9.68311L10.8185 4.34561C11.0518 4.11227 11.3289 3.99561 11.6497 3.99561C11.9706 3.99561 12.2477 4.11227 12.481 4.34561C12.7143 4.57894 12.831 4.85116 12.831 5.16227C12.831 5.47338 12.7143 5.74561 12.481 5.97894L9.12682 9.33311H19.831C20.4727 9.33311 21.022 9.56158 21.4789 10.0185C21.9359 10.4755 22.1643 11.0248 22.1643 11.6664V22.1664C22.1643 22.497 22.0525 22.7741 21.8289 22.9977C21.6053 23.2213 21.3282 23.3331 20.9977 23.3331C20.6671 23.3331 20.39 23.2213 20.1664 22.9977C19.9428 22.7741 19.831 22.497 19.831 22.1664V11.6664H9.12682Z"></path>
</g>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.SubColumn',
                                        'data' => [
                                          'block_props' => [
                                            'padding-top' => 'p-t-0',
                                            'padding-bottom' => 'p-b-10',
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
                                                      'html' => '<font color="" class="tx-bw-6">30 dies ad restitutionem
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
                                                        'margin-bottom' => 'm-b-0',
                                                        'align' => 't-l',
                                                      ],
                                                    ],
                                                    'children' => [],
                                                  ],
                                                  [
                                                    'type' => 'site.Paragraph',
                                                    'data' => [
                                                      'html' => '<font color="" class="tx-bw-4">Pro merce sine vestigiis usus, cum plena instructione.
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
            ],
          ],
        ],
      ],
    ],
  ],
];
