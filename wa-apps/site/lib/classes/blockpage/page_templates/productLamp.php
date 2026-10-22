<?php
$img_path = wa()->getAppStaticUrl('site').'img/blocks/page_templates/product/lamp/';

return [
  [
    'type' => 'site.CustomHero2',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18',
          'padding-bottom' => 'p-b-18',
        ],
        'site-block-columns-wrapper' => [
          'padding-top' => 'p-t-12',
          'padding-bottom' => 'p-b-12',
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
        ],
      ],
      'inline_props' => [
        'site-block-columns' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'linear-gradient(#ede6c8, #ede6c8)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#ede6c8, #ede6c8)',
                'name' => 'Self color',
                'css' => '#ede6c8',
              ],
            ],
            'uuid' => 1,
          ],
          'min-height' => [
            'name' => 'Content',
            'value' => 'none',
            'type' => 'content',
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
                  'column' => 'st-6-lp st-12-mb st-12-tb st-6',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-10-mb',
                      'padding-top' => 'p-t-10-mb',
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
                            'type' => 'site.Image',
                            'data' => [
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path . 'yellow-bg-opt.jpg',
                              ],
                              'inline_props' => [],
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
                      'padding-bottom' => 'p-b-12 p-b-10-mb',
                      'padding-top' => 'p-t-12 p-t-0-mb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-bottom' => 'p-b-12',
                      'padding-top' => 'p-t-12',
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
                              'html' => '<font color="#592337"><span style="caret-color: rgb(89, 35, 55);"><b>KUPOL</b></span></font>',
                              'block_props' => [
                                'align' => 't-c',
                                'font-header' => 't-hdn',
                                'margin-bottom' => 'm-b-12',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'h1',
                              'inline_props' => [
                                'font-size' => [
                                  'type' => 'self_size',
                                  'value' => '70px',
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
                              'html' => '<i><font color="#592337">Vesper habet
locum suum.</font></i><br>',
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
                                'margin-bottom' => 'm-b-16',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-2">Lux calida angulum usitatum cubiculi in locum dilectum mutat.</font>',
                              'block_props' => [
                                'align' => 't-c',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-18',
                                'margin-top' => 'm-t-0',
                              ],
                              'tag' => 'h1',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-bottom' => 'p-b-4',
                                'padding-top' => 'p-t-4',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-ds n-wr-tb n-wr-lp',
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
                                          'html' => 'Kupol elige<br>',
                                          'block_props' => [
                                            'border-radius' => 'b-r-r',
                                            'button-size' => 'inp-l p-l-14 p-r-14',
                                            'button-style' => [
                                              'name' => 'Palette',
                                              'value' => 'btn-blc',
                                              'type' => 'palette',
                                            ],
                                            'margin-bottom' => 'm-b-12',
                                            'margin-right' => 'm-r-14',
                                          ],
                                          'tag' => 'a',
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '14 900 den.',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #4',
                                              'value' => 't-4',
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
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-b-opc-6">Bulbus inclusus est. Tantum lucernam accende.</font>',
                              'block_props' => [
                                'align' => 't-c',
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #7',
                                  'value' => 't-7',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-bottom' => 'm-b-16',
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
    'type' => 'site.CustomColumns.1',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-16-mb p-t-8',
          'padding-bottom' => 'p-b-16-mb p-b-8',
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
            'value' => 'linear-gradient(#592337, #592337)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#592337, #592337)',
                'name' => 'Self color',
                'css' => '#592337',
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
                  'column' => 'st-3 st-3-lp st-6-tb st-12-mb',
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
                  'wrapper_props' => [],
                  'inline_props' => [],
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
                                'padding-top' => 'p-t-8',
                                'padding-bottom' => 'p-b-8',
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
                                            'margin-bottom' => 'm-b-4',
                                            'picture-size' => 'i-m',
                                            'margin-right' => 'm-r-12',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'type' => 'self_color',
                                              'value' => '#ede6c8',
                                              'name' => 'Self color',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="#ede6c8">
<mask id="mask0_3780_3103" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="36" height="36">
<rect width="36" height="36"></rect>
</mask>
<g mask="url(#mask0_3780_3103)">
<path d="M21.1875 21.1875C22.0625 20.3125 22.5 19.25 22.5 18C22.5 16.75 22.0625 15.6875 21.1875 14.8125C20.3125 13.9375 19.25 13.5 18 13.5C16.75 13.5 15.6875 13.9375 14.8125 14.8125C13.9375 15.6875 13.5 16.75 13.5 18C13.5 19.25 13.9375 20.3125 14.8125 21.1875C15.6875 22.0625 16.75 22.5 18 22.5C19.25 22.5 20.3125 22.0625 21.1875 21.1875ZM12.6938 23.3063C11.2313 21.8438 10.5 20.075 10.5 18C10.5 15.925 11.2313 14.1563 12.6938 12.6938C14.1563 11.2313 15.925 10.5 18 10.5C20.075 10.5 21.8438 11.2313 23.3063 12.6938C24.7688 14.1563 25.5 15.925 25.5 18C25.5 20.075 24.7688 21.8438 23.3063 23.3063C21.8438 24.7688 20.075 25.5 18 25.5C15.925 25.5 14.1563 24.7688 12.6938 23.3063ZM7.5 19.5H1.5V16.5H7.5V19.5ZM34.5 19.5H28.5V16.5H34.5V19.5ZM16.5 7.5V1.5H19.5V7.5H16.5ZM16.5 34.5V28.5H19.5V34.5H16.5ZM9.6 11.625L5.8125 7.9875L7.95 5.775L11.55 9.525L9.6 11.625ZM28.05 30.225L24.4125 26.4375L26.4 24.375L30.1875 28.0125L28.05 30.225ZM24.375 9.6L28.0125 5.8125L30.225 7.95L26.475 11.55L24.375 9.6ZM5.775 28.05L9.5625 24.4125L11.625 26.4L7.9875 30.1875L5.775 28.05Z"></path>
</g>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="#ede6c8">Lux calida 2700 K</font>',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-2',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-3 st-3-lp st-6-tb st-12-mb',
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
                  'wrapper_props' => [],
                  'inline_props' => [],
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
                                'padding-top' => 'p-t-8',
                                'padding-bottom' => 'p-b-8',
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
                                            'margin-bottom' => 'm-b-4',
                                            'picture-size' => 'i-m',
                                            'margin-right' => 'm-r-12',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'type' => 'self_color',
                                              'value' => '#ede6c8',
                                              'name' => 'Self color',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="#ede6c8">
<mask id="mask0_3780_3203" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="36" height="36">
<rect width="36" height="36"></rect>
</mask>
<g mask="url(#mask0_3780_3203)">
<path d="M16.4922 31.5V22.5H19.4922V25.5H31.4922V28.5H19.4922V31.5H16.4922ZM4.49219 28.5V25.5H13.4922V28.5H4.49219ZM10.4922 22.5V19.5H4.49219V16.5H10.4922V13.5H13.4922V22.5H10.4922ZM16.4922 19.5V16.5H31.4922V19.5H16.4922ZM22.4922 13.5V4.5H25.4922V7.5H31.4922V10.5H25.4922V13.5H22.4922ZM4.49219 10.5V7.5H19.4922V10.5H4.49219Z"></path>
</g>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="#ede6c8">Claritas variabilis </font>',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-2',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-3 st-3-lp st-6-tb st-12-mb',
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
                    'flex-align' => 'y-c',
                  ],
                  'inline_props' => [],
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
                                'padding-top' => 'p-t-8',
                                'padding-bottom' => 'p-b-8',
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
                                            'margin-bottom' => 'm-b-4',
                                            'picture-size' => 'i-m',
                                            'margin-right' => 'm-r-12',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'type' => 'self_color',
                                              'value' => '#ede6c8',
                                              'name' => 'Self color',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="#ede6c8">
<mask id="mask0_3780_3209" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="37" height="36">
<rect width="36" height="36"></rect>
</mask>
<g mask="url(#mask0_3780_3209)">
<path d="M15.8813 32.1188C15.2938 31.5313 15 30.825 15 30H21C21 30.825 20.7063 31.5313 20.1188 32.1188C19.5313 32.7063 18.825 33 18 33C17.175 33 16.4688 32.7063 15.8813 32.1188ZM12 28.5V25.5H24V28.5H12ZM12.375 24C10.65 22.975 9.28125 21.6 8.26875 19.875C7.25625 18.15 6.75 16.275 6.75 14.25C6.75 11.125 7.84375 8.46875 10.0312 6.28125C12.2188 4.09375 14.875 3 18 3C21.125 3 23.7812 4.09375 25.9688 6.28125C28.1563 8.46875 29.25 11.125 29.25 14.25C29.25 16.275 28.7438 18.15 27.7313 19.875C26.7188 21.6 25.35 22.975 23.625 24H12.375ZM13.275 21H22.725C23.85 20.2 24.7187 19.2125 25.3312 18.0375C25.9437 16.8625 26.25 15.6 26.25 14.25C26.25 11.95 25.45 10 23.85 8.4C22.25 6.8 20.3 6 18 6C15.7 6 13.75 6.8 12.15 8.4C10.55 10 9.75 11.95 9.75 14.25C9.75 15.6 10.0562 16.8625 10.6687 18.0375C11.2812 19.2125 12.15 20.2 13.275 21Z"></path>
</g>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="#ede6c8">Bulbus E27 mutabilis&nbsp;</font>',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-2',
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
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-3 st-3-lp st-6-tb st-12-mb',
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
                  'wrapper_props' => [],
                  'inline_props' => [],
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
                                'padding-top' => 'p-t-8',
                                'padding-bottom' => 'p-b-8',
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
                                            'margin-bottom' => 'm-b-4',
                                            'picture-size' => 'i-m',
                                            'margin-right' => 'm-r-12',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'type' => 'self_color',
                                              'value' => '#ede6c8',
                                              'name' => 'Self color',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36" fill="#ede6c8">
<mask id="mask0_3780_3221" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="36" height="36">
<rect width="36" height="36"></rect>
</mask>
<g mask="url(#mask0_3780_3221)">
<path d="M16.425 23.325L24.9 14.85L22.7625 12.7125L16.425 19.05L13.275 15.9L11.1375 18.0375L16.425 23.325ZM18 33C14.525 32.125 11.6563 30.1313 9.39375 27.0188C7.13125 23.9063 6 20.45 6 16.65V7.5L18 3L30 7.5V16.65C30 20.45 28.8688 23.9063 26.6063 27.0188C24.3438 30.1313 21.475 32.125 18 33ZM18 29.85C20.6 29.025 22.75 27.375 24.45 24.9C26.15 22.425 27 19.675 27 16.65V9.5625L18 6.1875L9 9.5625V16.65C9 19.675 9.85 22.425 11.55 24.9C13.25 27.375 15.4 29.025 18 29.85Z"></path>
</g>
</svg>',
                                            'fill' => 'removed',
                                          ],
                                          'inline_props' => [],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="#ede6c8"> Praestatio 2 annorum&nbsp;</font>',
                                          'tag' => 'p',
                                          'block_props' => [
                                            'font-header' => 't-rgl',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
                                              'unit' => 'px',
                                              'type' => 'library',
                                            ],
                                            'margin-top' => 'm-t-2',
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
            ],
          ],
        ],
      ],
    ],
  ],
  [
    'type' => 'site.CustomColumns.1',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-21',
          'padding-bottom' => 'p-b-18',
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
            'value' => 'linear-gradient(#faf8f2, #faf8f2)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#faf8f2, #faf8f2)',
                'name' => 'Self color',
                'css' => '#faf8f2',
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
                  'column' => 'st-12-mb st-12-tb st-12 st-12-lp',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-18',
                      'padding-top' => 'p-t-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'column-max-width' => 'fx-6',
                      'margin-left' => 'm-l-a',
                      'margin-right' => 'm-r-a',
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
                      'data' => [],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font class="tx-b-opc-6" color="">Parvi ritus domestici</font>',
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
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="#592337"><b>Dies finiatur.
<br></b><i>Vesper iucundus duret.</i></font><br>',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<font class="tx-bw-2" color="">Lucem superiorem exstingue. Fruere libro dilecto, sermone tranquillo
et luce molli in loco ubi bene te habes.</font>',
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
                  'column' => 'st-12-mb st-12-tb st-12 st-12-lp',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-16',
                      'padding-top' => 'p-t-30 p-t-30-tb p-t-28-mb',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'border-radius' => 'b-r-l',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-top' => 'p-t-30 p-t-0-tb',
                    ],
                  ],
                  'wrapper_props' => [],
                  'inline_props' => [
                    'site-block-column' => [
                      'background' => [
                        'type' => 'self_color',
                        'value' => 'center center / cover url('.$img_path.'evening-opt.jpg)',
                        'name' => 'Self color',
                        'layers' => [
                          [
                            'space' => 'cover',
                            'alignmentX' => 'center',
                            'alignmentY' => 'center',
                            'type' => 'image',
                            'name' => 'Image',
                            'css' => '',
                            'file_name' => 'evening-opt.jpg',
                            'file_url' => $img_path.'evening-opt.jpg',
                            'value' => 'center center / cover url('.$img_path.'evening-opt.jpg)',
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
                      'data' => [],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-11',
                                'padding-bottom' => 'p-b-12',
                                'padding-left' => 'p-l-14',
                                'padding-right' => 'p-r-14',
                                'border-radius' => 'b-r-l',
                                'margin-top' => 'm-t-25',
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                              ],
                              'inline_props' => [
                                'background' => [
                                  'type' => 'self_color',
                                  'value' => 'linear-gradient(#19000bc4, #19000bc4)',
                                  'name' => 'Self color',
                                  'layers' => [
                                    [
                                      'type' => 'self_color',
                                      'value' => 'linear-gradient(#19000bc4, #19000bc4)',
                                      'name' => 'Self color',
                                      'css' => '#19000bc4',
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
                                          'html' => '<font color="" class="tx-wh">Locus tibi carissimus. Nunc luce apta illustratus.
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
                                            'align' => 't-l',
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
    'type' => 'site.CustomColumns.1',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-16-mb p-t-0',
          'padding-bottom' => 'p-b-16-mb',
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
            'value' => 'linear-gradient(#faf8f2, #faf8f2)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#faf8f2, #faf8f2)',
                'name' => 'Self color',
                'css' => '#faf8f2',
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
                  'column' => 'st-4 st-4-lp st-12-tb st-12-mb',
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
                  'wrapper_props' => [],
                  'inline_props' => [],
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
                              'html' => '<font color="" class="tx-b-opc-6">Tardius vivere</font>',
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
                              'html' => '<font color="#592337"><b> Iuxta librum </b></font>',
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
                              'html' => 'Lux deorsum directa angulum commodum ad legendum vespere creat.',
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
                  'column' => 'st-4 st-4-lp st-12-tb st-12-mb',
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
                  'wrapper_props' => [],
                  'inline_props' => [],
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
                              'html' => '<font color="" class="tx-b-opc-6">Una esse </font>',
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
                              'html' => '<font color="#592337"><b>Inter colloquia
</b></font>',
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
                              'html' => 'Claritatem minue: lux mitis cenam duorum hominum comitatur.

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
                  'column' => 'st-4 st-4-lp st-12-tb st-12-mb',
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
                  'wrapper_props' => [],
                  'inline_props' => [],
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
                              'html' => '<font color="" class="tx-b-opc-6">Respirare </font>',
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
                              'html' => '<font color="#592337"><b>Iuxta lectum </b></font>',
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
                              'html' => 'Ad paginas ultimas, poculum theae et quinque minuta tibi soli.',
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
    'type' => 'site.CustomColumns.1',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18 p-t-16-mb',
          'padding-bottom' => 'p-b-18 p-b-16-mb',
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
            'value' => 'linear-gradient(#faf8f2, #faf8f2)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#faf8f2, #faf8f2)',
                'name' => 'Self color',
                'css' => '#faf8f2',
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
                  'column' => 'st-6 st-6-lp st-12-mb st-12-tb',
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
                  'wrapper_props' => [],
                  'inline_props' => [],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Image',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-l',
                              ],
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path . 'dimmer-closeup-opt.jpg',
                              ],
                              'inline_props' => [],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b>Pulchra etiam desuper</b>. Umbraculum rotundum et superficies non nitens formam lucernae clare ostendunt.',
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
                  'column' => 'st-6 st-6-lp st-12-mb st-12-tb',
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
                  'wrapper_props' => [],
                  'inline_props' => [],
                ],
                'children' => [
                  '' => [
                    [
                      'type' => 'site.VerticalSequence',
                      'data' => [],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Image',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-l',
                              ],
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path . 'top-view-opt.jpg',
                              ],
                              'inline_props' => [],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => '<b>Claritas uno motu mutatur</b>. Regulatorem in fune verte: lucem auge ad legendum, minue ad quietum vesperum.',
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
    'type' => 'site.CustomColumns.1',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-18 p-t-16-mb',
          'padding-bottom' => 'p-b-18 p-b-16-mb',
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
            'value' => 'linear-gradient(#592337, #592337)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#592337, #592337)',
                'name' => 'Self color',
                'css' => '#592337',
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
                  'column' => 'st-6 st-6-lp st-12-mb st-12-tb',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-bottom' => 'p-b-12',
                      'padding-top' => 'p-t-12',
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'padding-right' => 'p-r-19 p-r-0-tb',
                    ],
                  ],
                  'wrapper_props' => [],
                  'inline_props' => [],
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
                              'html' => '<font color="" class="tx-w-opc-6">Pulchre constructa</font>',
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
                              'tag' => 'p',
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="#ede6c8"><b>Omnia cogitata. </b><br><i>Nihil supervacuum. </i></font>',
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
                          [
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-14',
                                'padding-bottom' => 'p-b-10',
                                'border-color' => [
                                  'name' => 'semi-transparent-white',
                                  'value' => 'br-w-opc-2',
                                  'type' => 'palette',
                                ],
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'value' => 'b-w-s',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'border-style' => [
                                  'value' => 'b-d-t',
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
                                          'html' => '<font color="" class="tx-bw-8">Lux sub umbraculo manet</font>',
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
                                          'html' => '<font color="" class="tx-bw-6">Umbraculum metallicum latum lucem in mensam dirigit. Superficies interior pallida lucem diffundere adiuvat. </font>',
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
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-14',
                                'padding-bottom' => 'p-b-10',
                                'border-color' => [
                                  'name' => 'semi-transparent-white',
                                  'value' => 'br-w-opc-2',
                                  'type' => 'palette',
                                ],
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'value' => 'b-w-s',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'border-style' => [
                                  'value' => 'b-d-t',
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
                                          'html' => '<font color="" class="tx-bw-8">Claritas pro animo tuo </font>',
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
                                          'html' => '<font color="" class="tx-bw-6">Regulatorem in fune verte: lux clarior ad legendum, mitior ad quietum vesperum. Nulla applicatio nec praeparatio necessaria est. </font>',
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
                            'type' => 'site.SubColumn',
                            'data' => [
                              'block_props' => [
                                'padding-top' => 'p-t-14',
                                'padding-bottom' => 'p-b-10',
                                'border-color' => [
                                  'name' => 'semi-transparent-white',
                                  'value' => 'br-w-opc-2',
                                  'type' => 'palette',
                                ],
                                'border-width' => [
                                  'name' => 'Width 1',
                                  'value' => 'b-w-s',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'border-style' => [
                                  'value' => 'b-d-t',
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
                                          'html' => '<font color="" class="tx-bw-8">Res dilecta diu manet  </font>',
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
                                          'html' => '<font class="tx-bw-6" color="#010203">Corpus metallicum et receptaculum ordinarium E27. Cum tempus venerit, bulbum ipse mutare potes. </font>',
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
                      'padding-bottom' => 'p-b-12',
                      'padding-top' => 'p-t-12',
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
                      'data' => [],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Image',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-10',
                                'border-radius-corners' => [
                                  'value' => ' b-r-u-bl b-r-u-br',
                                  'type' => 'separate',
                                ],
                                'border-radius' => 'b-r-r',
                                'margin-top' => 'm-t-16',
                              ],
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path . 'close-opt.jpg',
                              ],
                              'inline_props' => [],
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-w-opc-6">Forma quam contemplari libet</font>',
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
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-w-opc-6">Metallum vernice obductum</font>',
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
                                            'margin-left' => 'm-l-a m-l-0-mb',
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
          'padding-top' => 'p-t-20 p-t-18-mb',
          'padding-bottom' => 'p-b-20 p-b-12-mb',
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
                  'column' => 'st-12 st-12-tb st-12-mb st-12-lp',
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
                              'html' => '<font color="#592337"><b>Invenietur
</b><br><i>locus tuus</i></font>',
                              'tag' => 'h3',
                              'block_props' => [
                                'align' => 't-l',
                                'font-header' => 't-hdn',
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
                            'type' => 'site.Image',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'border-radius' => 'b-r-l',
                                'margin-top' => 'm-t-8',
                              ],
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path . 't_5d47e8a6f36a3e1f.jpg',
                              ],
                              'inline_props' => [],
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
                              'inline_props' => [
                                'border-width' => [
                                  'type' => 'self_size',
                                  'value' => '1px',
                                  'name' => 'Self size',
                                  'unit' => 'px',
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
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Altitudo</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
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
                                          'html' => '<font color="" class="tx-bw-1">42 cm
</font>',
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
                              'inline_props' => [
                                'border-width' => [
                                  'type' => 'self_size',
                                  'value' => '1px',
                                  'name' => 'Self size',
                                  'unit' => 'px',
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
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Diameter umbraculi
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
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
                                          'html' => '<font color="" class="tx-bw-1">30 cm
</font>',
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
                              'inline_props' => [
                                'border-width' => [
                                  'type' => 'self_size',
                                  'value' => '1px',
                                  'name' => 'Self size',
                                  'unit' => 'px',
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
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Diameter basis
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
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
                                          'html' => '<font color="" class="tx-bw-1">18 cm
</font>',
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
                              'inline_props' => [
                                'border-width' => [
                                  'type' => 'self_size',
                                  'value' => '1px',
                                  'name' => 'Self size',
                                  'unit' => 'px',
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
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Materia
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
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
                                          'html' => '<font color="" class="tx-bw-1">Metallum, superficies non nitens
</font>',
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
                                'border-color' => [
                                  'css' => '#0000001a',
                                  'name' => '1-1',
                                  'type' => 'palette',
                                  'value' => 'bd-tr-1',
                                ],
                                'border-style' => [
                                  'value' => ' b-d-b',
                                  'type' => 'separate',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
                              ],
                              'inline_props' => [
                                'border-width' => [
                                  'type' => 'self_size',
                                  'value' => '1px',
                                  'name' => 'Self size',
                                  'unit' => 'px',
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
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Lux
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
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
                                          'html' => '<font color="" class="tx-bw-1">2700 K, lux alba calida
</font>',
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
                                'border-color' => [
                                  'css' => '#0000001a',
                                  'name' => '1-1',
                                  'type' => 'palette',
                                  'value' => 'bd-tr-1',
                                ],
                                'border-style' => [
                                  'value' => ' b-d-b',
                                  'type' => 'separate',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
                              ],
                              'inline_props' => [
                                'border-width' => [
                                  'type' => 'self_size',
                                  'value' => '1px',
                                  'name' => 'Self size',
                                  'unit' => 'px',
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
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Bulbus
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
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
                                          'html' => '<font color="" class="tx-bw-1">1,3 kg</font>',
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
                                'border-color' => [
                                  'css' => '#0000001a',
                                  'name' => '1-1',
                                  'type' => 'palette',
                                  'value' => 'bd-tr-1',
                                ],
                                'border-style' => [
                                  'value' => ' b-d-b',
                                  'type' => 'separate',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
                              ],
                              'inline_props' => [
                                'border-width' => [
                                  'type' => 'self_size',
                                  'value' => '1px',
                                  'name' => 'Self size',
                                  'unit' => 'px',
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
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Lux
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
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
                                          'html' => '<font color="" class="tx-bw-1">Ex receptaculo electrico, 220–240 V
</font>',
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
                                'border-color' => [
                                  'css' => '#0000001a',
                                  'name' => '1-1',
                                  'type' => 'palette',
                                  'value' => 'bd-tr-1',
                                ],
                                'border-style' => [
                                  'value' => ' b-d-b',
                                  'type' => 'separate',
                                ],
                              ],
                              'wrapper_props' => [
                                'justify-align' => 'j-s',
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb n-wr-mb',
                              ],
                              'inline_props' => [
                                'border-width' => [
                                  'type' => 'self_size',
                                  'value' => '1px',
                                  'name' => 'Self size',
                                  'unit' => 'px',
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
                                  ],
                                  'children' => [
                                    '' => [
                                      [
                                        'type' => 'site.Paragraph',
                                        'data' => [
                                          'html' => '<font color="" class="tx-bw-4">Longitudo funis
</font>',
                                          'block_props' => [
                                            'align' => 't-l',
                                            'font-header' => 't-hdn',
                                            'font-size' => [
                                              'name' => 'Size #6',
                                              'value' => 't-6',
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
                                          'html' => '<font color="" class="tx-bw-1">1.8 m, integumentum textile</font>',
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
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Bulbus et regulator claritatis pretio includuntur.',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-16',
                                'margin-bottom' => 'm-b-12',
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '14 900 den.',
                              'tag' => 'h3',
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
                                'align' => 't-l',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'In cophinum adde<br>',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-bottom' => 'm-b-12',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-blc',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-l p-l-14 p-r-14',
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
            ],
          ],
        ],
      ],
    ],
  ],
];
