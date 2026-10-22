<?php
return [
  [
    'type' => 'site.MenuT1',
  ],
  [
    'type' => 'site.CustomText3',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-21 p-t-18-tb p-t-12-mb',
          'padding-bottom' => 'p-b-12-mb p-b-21 p-b-18-tb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'padding-bottom' => 'p-b-0-mb',
          'padding-top' => 'p-t-0-mb',
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
                              'html' => 'Incipe textum emendare aut materiam novam addere.',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #5',
                                  'value' => 't-5',
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
    'type' => 'site.FooterBottom6.2',
    'replace_data' => [
      'block_props' => [
        'site-block-footer' => [
          'padding-bottom' => 'p-b-10',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-10',
          'background' => [
            'name' => 'grey shades',
            'type' => 'palette',
            'value' => 'bg-bw-7',
            'layers' => [
              [
                'name' => 'grey shades',
                'value' => 'bg-bw-7',
                'type' => 'palette',
              ],
            ],
          ],
          'margin-top' => 'm-t-a',
        ],
        'site-block-footer-wrapper' => [
          'flex-align-vertical' => 'x-c',
          'max-width' => 'cnt',
          'padding-bottom' => 'p-b-10',
          'padding-top' => 'p-t-10',
        ],
      ],
    ],
  ],
];
