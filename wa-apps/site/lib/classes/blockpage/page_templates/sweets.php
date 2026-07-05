<?php
$img_path = wa()->getAppStaticUrl('site').'img/blocks/page_templates/mini/sweets/';

return [
  [
    'type' => 'site.CustomHero',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-top' => 'p-t-30',
        ],
        'site-block-columns-wrapper' => [
          'flex-align' => 'y-c',
          'margin-top' => 'm-t-a',
        ],
      ],
      'inline_props' => [
        'site-block-columns' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'linear-gradient(#e800731f, #e800731f), center center / cover url('.$img_path.'sweet-store.jpg)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(#e800731f, #e800731f)',
                'name' => 'Self color',
                'css' => '#e800731f',
              ],
              [
                'type' => 'image',
                'value' => 'center center / cover url('.$img_path.'sweet-store.jpg)',
                'alignmentX' => 'center',
                'alignmentY' => 'center',
                'file_name' => 'sweet-store.jpg',
                'file_url' => $img_path.'sweet-store.jpg',
                'space' => 'cover',
                'name' => 'Image',
              ],
            ],
            'uuid' => 1,
          ],
        ],
        'site-block-columns-wrapper' => [
          'background' => [
            'type' => 'self_color',
            'value' => 'linear-gradient(180deg,  #FFFFFF00 0%,  #ffffff00 50%,  #ffffff 50%)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'linear-gradient(180deg,  #FFFFFF00 0%,  #ffffff00 50%,  #ffffff 50%)',
                'name' => 'Self color',
                'css' => 'gradient',
                'gradient' => [
                  'type' => 'linear-gradient',
                  'degree' => 180,
                  'stops' => [
                    [
                      'color' => '#FFFFFF00',
                      'stop' => '0',
                    ],
                    [
                      'color' => '#ffffff00',
                      'stop' => '50',
                    ],
                    [
                      'color' => '#ffffff',
                      'stop' => '50',
                    ],
                  ],
                ],
                'uuid' => 1,
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
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'flex-align-vertical' => 'a-c-c',
                    ],
                    'site-block-column-wrapper' => [
                      'column-max-width' => 'fx-9',
                      'flex-align' => 'y-c',
                      'margin-left' => 'm-l-a',
                      'margin-right' => 'm-r-a',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
                  ],
                  'inline_props' => [
                    'site-block-column-wrapper' => [],
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
                        'is_complex' => 'with_row',
                      ],
                      'children' => [
                        '' => [
                          [
                            'type' => 'site.Image',
                            'data' => [
                              'block_props' => [
                                'border-color' => [
                                  'name' => 'black and white',
                                  'value' => 'br-wh',
                                  'type' => 'palette',
                                ],
                                'border-width' => [
                                  'name' => _w('Width 2'),
                                  'value' => 'b-w-m',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'border-style' => [
                                  'value' => 'b-d-a',
                                  'type' => 'all',
                                ],
                                'border-radius' => 'b-r-r',
                              ],
                              'image' => [
                                'type' => 'address',
                                'url_text' => $img_path.'image-512.png',
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
    'type' => 'site.CustomFaq1',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'padding-bottom' => 'p-b-18',
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
                  'column' => 'st-12-tb st-12-mb st-6 st-7-lp',
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
                                    'html' => 'Lucia Dulce Atelier<br>',
                                    'tag' => 'h3',
                                    'block_props' => [
                                      'align' => 't-c',
                                      'font-header' => 't-hdn',
                                      'font-size' => [
                                        'name' => 'Size #3',
                                        'value' => 't-3',
                                        'unit' => 'px',
                                        'type' => 'library',
                                      ],
                                      'margin-bottom' => 'm-b-13',
                                      'margin-top' => 'm-t-12',
                                    ],
                                  ],
                                  'children' => [],
                                ],
                              ],
                            ],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Dulcia domestica et tortae ad mandatum
Pistoria recens pro festivitatibus et vesperis quietis.&nbsp;Sapores naturales et confectiones curate paratae
ad dies speciales, dona dulcia et momentos convivii.',
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
                                'margin-bottom' => 'm-b-16',
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Via delle Rose 12
Roma',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Paragraph',
                            'data' => [
                              'html' => 'Aperto quotidie 9:00–19:00',
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
                                'align' => 't-c',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'Catalogum videre<br>',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-blc-trnsp',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-l p-l-14 p-r-14',
                                'full-width' => 'f-w',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'Tortam ordinare<br>',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-blc-trnsp',
                                  'type' => 'palette',
                                ],
                                'button-size' => 'inp-l p-l-14 p-r-14',
                                'full-width' => 'f-w',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Button',
                            'data' => [
                              'html' => 'Testimonia clientium<br>',
                              'tag' => 'a',
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'button-style' => [
                                  'name' => 'Palette',
                                  'value' => 'btn-blc-trnsp',
                                  'type' => 'palette',
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
                                'padding-top' => 'p-t-16',
                                'padding-bottom' => 'p-b-16',
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
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-14',
                                            'picture-size' => 'i-l',
                                            'margin-right' => 'm-r-16',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-blc',
                                              'type' => 'palette',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto" viewBox="0 0 33 32" fill="var(--black)"><g clip-path="url(#clip0_10717_116298)"><path d="M16.9131 0.492188C20.8704 0.515741 24.6661 2.06705 27.5068 4.82227C30.3476 7.57755 32.0137 11.3244 32.1582 15.2793C32.2333 17.3038 31.9066 19.323 31.1973 21.2207C30.4879 23.1185 29.4101 24.8571 28.0254 26.3359C26.6405 27.8149 24.9761 29.0055 23.1289 29.8379C21.2819 30.6702 19.2879 31.1277 17.2627 31.1855H16.8242C14.5219 31.1862 12.2484 30.6693 10.1729 29.6729L2.13965 31.46H2.11719C2.10047 31.4599 2.08357 31.4562 2.06836 31.4492C2.05308 31.4422 2.03937 31.4316 2.02832 31.4189C2.01739 31.4063 2.00873 31.3919 2.00391 31.376C1.99906 31.3599 1.99781 31.3428 2 31.3262L3.35742 23.2041C2.07929 20.8656 1.43132 18.2349 1.47852 15.5703C1.52574 12.9058 2.26674 10.2995 3.62695 8.00781C4.98713 5.7162 6.92004 3.81771 9.23633 2.5C11.5528 1.18229 14.1728 0.490219 16.8379 0.492188H16.9131ZM10.8066 7.7002C10.6519 7.71881 10.5003 7.7605 10.3574 7.82422C10.167 7.90918 9.99589 8.03217 9.85352 8.18457C9.45116 8.59719 8.32581 9.5904 8.26074 11.6758C8.19581 13.7607 9.65239 15.8235 9.85645 16.1133C10.0602 16.4026 12.6426 20.9076 16.8955 22.7344C19.395 23.8112 20.4907 23.9961 21.2012 23.9961C21.494 23.9961 21.7152 23.9649 21.9463 23.9512C22.7256 23.903 24.4838 23.0026 24.8672 22.0234C25.2506 21.0442 25.2759 20.1878 25.1748 20.0166C25.0737 19.8456 24.7963 19.7219 24.3789 19.5029C23.9608 19.2836 21.9116 18.1882 21.5264 18.0342C21.3836 17.9677 21.2294 17.9276 21.0723 17.916C20.9701 17.9214 20.8711 17.9524 20.7832 18.0049C20.6952 18.0573 20.6208 18.1303 20.5674 18.2178C20.225 18.6441 19.4398 19.5694 19.1758 19.8369C19.1183 19.9031 19.0472 19.9569 18.9678 19.9941C18.8883 20.0312 18.8016 20.0511 18.7139 20.0527C18.5519 20.0456 18.3927 20.0029 18.249 19.9277C17.0078 19.4005 15.8757 18.6456 14.9121 17.7021C14.0118 16.8148 13.2479 15.7989 12.6455 14.6875C12.4127 14.256 12.6461 14.0331 12.8584 13.8311C13.0706 13.629 13.2976 13.3498 13.5166 13.1084C13.6964 12.9023 13.8464 12.6718 13.9619 12.4238C14.0216 12.3087 14.052 12.1804 14.0498 12.0508C14.0478 11.921 14.0137 11.7939 13.9502 11.6807C13.8492 11.4648 13.0947 9.34441 12.7402 8.49316C12.4528 7.76587 12.1101 7.741 11.8105 7.71875C11.5642 7.70164 11.2814 7.69313 10.999 7.68457H10.9629L10.8066 7.7002Z"></path></g><defs><clipPath id="clip0_10717_116298"><rect width="32" height="32" transform="translate(0.866699)"></rect></clipPath></defs></svg>',
                                            'fill' => 'removed',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-14',
                                            'picture-size' => 'i-l',
                                            'margin-right' => 'm-r-16',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-blc',
                                              'type' => 'palette',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto " viewBox="0 0 33 32" fill="var(--black)"><g clip-path="url(#clip0_10717_116287)"><path d="M16.6499 0C25.4865 0 32.6499 7.16344 32.6499 16C32.6499 24.8366 25.4865 32 16.6499 32C7.81335 32 0.649902 24.8366 0.649902 16C0.649902 7.16344 7.81335 0 16.6499 0ZM23.1919 9.63184C22.5901 9.64249 21.6661 9.96352 17.2231 11.8115C15.6668 12.4589 12.557 13.7991 7.89307 15.8311C7.13574 16.1322 6.73883 16.4268 6.70264 16.7148C6.6333 17.2679 7.42934 17.44 8.43115 17.7656C9.24801 18.0312 10.3471 18.3421 10.9185 18.3545C11.4366 18.3657 12.0153 18.1523 12.6538 17.7139C17.0118 14.7721 19.2616 13.285 19.4028 13.2529C19.5025 13.2303 19.6404 13.202 19.7339 13.2852C19.8271 13.3681 19.818 13.5246 19.8081 13.5674C19.7289 13.9051 15.6367 17.6282 15.3999 17.874C14.4996 18.8091 13.4756 19.3809 15.0552 20.4219C16.422 21.3226 17.2175 21.8973 18.6255 22.8203C19.5254 23.4102 20.2315 24.1099 21.1606 24.0244C21.588 23.9849 22.0292 23.5831 22.2534 22.3848C22.7835 19.5511 23.8261 13.4108 24.0669 10.8809C24.0879 10.6592 24.0609 10.3754 24.0396 10.251C24.0182 10.1265 23.9736 9.94958 23.812 9.81836C23.6203 9.66281 23.3243 9.6295 23.1919 9.63184Z"></path></g><defs><linearGradient id="paint0_linear_10717_116287" x1="1600.65" y1="0" x2="1600.65" y2="3176.27" gradientUnits="userSpaceOnUse"><stop stop-color="#2AABEE"></stop><stop offset="1" stop-color="#229ED9"></stop></linearGradient><clipPath id="clip0_10717_116287"><rect width="32" height="32" transform="translate(0.649902)"></rect></clipPath></defs></svg>',
                                            'fill' => 'removed',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-14',
                                            'picture-size' => 'i-l',
                                            'margin-right' => 'm-r-16',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'color' => [
                                              'name' => 'Palette',
                                              'value' => 'tx-blc',
                                              'type' => 'palette',
                                            ],
                                            'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto" viewBox="0 0 32 32" fill="var(--black)"><path d="M15.972 1.6022C19.8827 1.59471 20.3767 1.61213 21.9095 1.67837C23.4417 1.74463 24.4883 1.98607 25.4046 2.34048C26.3529 2.70563 27.1572 3.19671 27.9583 3.99576C28.7595 4.79496 29.2542 5.59695 29.6243 6.54361C29.9833 7.45896 30.2272 8.50166 30.3001 10.0368C30.373 11.5724 30.3903 12.0634 30.3977 15.9733C30.4052 19.8828 30.3897 20.3738 30.3226 21.9108C30.256 23.4423 30.0143 24.4901 29.6595 25.4059C29.2934 26.3543 28.8021 27.158 28.0042 27.9596C27.2062 28.7613 26.4039 29.2553 25.4573 29.6256C24.5421 29.9833 23.501 30.2286 21.9651 30.3014C20.4295 30.3743 19.9367 30.3916 16.0276 30.3991C12.119 30.4066 11.6254 30.391 10.0931 30.3239C8.56041 30.2568 7.51277 30.0147 6.59696 29.6608C5.64661 29.2939 4.84375 28.8041 4.04228 28.0055C3.24077 27.2069 2.74674 26.4043 2.37724 25.4577C2.01786 24.5428 1.77366 23.4971 1.70048 21.9655C1.62618 20.4293 1.60933 19.9384 1.60185 16.028C1.59436 12.1177 1.60936 11.6245 1.67704 10.0915C1.74474 8.55885 1.98472 7.51191 2.33915 6.59536C2.70549 5.64727 3.19707 4.84385 3.9954 4.04263C4.79359 3.24158 5.59584 2.74649 6.54228 2.37759C7.45687 2.01821 8.50355 1.77413 10.0354 1.70181C11.5713 1.62693 12.0614 1.60969 15.972 1.6022ZM15.9856 8.60611C11.9022 8.61432 8.59895 11.9321 8.60673 16.0153C8.61517 20.0987 11.9313 23.4019 16.014 23.3942C20.0967 23.3863 23.4024 20.0696 23.3948 15.986C23.3869 11.9023 20.069 8.5982 15.9856 8.60611ZM14.1546 11.569C15.0308 11.204 15.9957 11.1074 16.927 11.2907C17.8584 11.4741 18.7154 11.9294 19.388 12.5993C20.0603 13.2692 20.5193 14.1238 20.7063 15.0543C20.8932 15.9848 20.8001 16.9502 20.4388 17.8278C20.0772 18.7055 19.4633 19.457 18.6751 19.986C17.8869 20.5149 16.9592 20.7984 16.0101 20.8004C15.3799 20.8018 14.7549 20.679 14.1722 20.4391C13.5894 20.1991 13.0591 19.8457 12.6126 19.401C12.1662 18.9564 11.811 18.428 11.5686 17.8463C11.3263 17.2645 11.2017 16.6397 11.2005 16.0094C11.1986 15.0602 11.4784 14.1318 12.0042 13.3415C12.5301 12.5511 13.2782 11.934 14.1546 11.569ZM23.6683 6.57388C23.2101 6.57481 22.7709 6.75714 22.4476 7.08169C22.1242 7.40627 21.9429 7.84619 21.9436 8.30435C21.9443 8.646 22.0463 8.98056 22.2366 9.26431C22.4269 9.54779 22.6973 9.76891 23.013 9.89908C23.3289 10.0292 23.677 10.063 24.012 9.99576C24.347 9.92845 24.6546 9.76314 24.8958 9.52115C25.137 9.2791 25.3014 8.97062 25.3675 8.6354C25.4335 8.30029 25.3981 7.9527 25.2669 7.63736C25.1356 7.32205 24.9135 7.05226 24.6292 6.86294C24.3447 6.67366 24.0099 6.57325 23.6683 6.57388Z"></path></svg>',
                                            'fill' => 'removed',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-14',
                                            'picture-size' => 'i-l',
                                            'margin-right' => 'm-r-16',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="16" cy="16" r="16" fill="black"/><path d="M17.029 23.0534C9.73568 23.0534 5.57568 18.0534 5.40234 9.7334H9.05568C9.17568 15.8401 11.869 18.4267 14.0023 18.9601V9.7334H17.4423V15.0001C19.549 14.7734 21.7623 12.3734 22.509 9.7334H25.949C25.6676 11.1026 25.1068 12.399 24.3015 13.5415C23.4962 14.684 22.4639 15.6481 21.269 16.3734C22.6028 17.0361 23.7808 17.9742 24.7255 19.1257C25.6701 20.2771 26.3598 21.6158 26.749 23.0534H22.9623C22.6129 21.8048 21.9027 20.687 20.9208 19.8403C19.9388 18.9935 18.7288 18.4554 17.4423 18.2934V23.0534H17.029Z" fill="white"/></svg>',
                                          ],
                                        ],
                                        'children' => [],
                                      ],
                                      [
                                        'type' => 'site.Image',
                                        'data' => [
                                          'block_props' => [
                                            'margin-bottom' => 'm-b-14',
                                            'picture-size' => 'i-l',
                                          ],
                                          'image' => [
                                            'type' => 'svg',
                                            'svg_html' => '<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_3522_11884)"><circle cx="16" cy="16" r="16" fill="black"/><path fill-rule="evenodd" clip-rule="evenodd" d="M16.4633 25.4389C14.4221 25.4389 13.4734 25.1409 11.8245 23.9489C10.7816 25.2899 7.47881 26.3379 7.33478 24.5449C7.33478 23.199 7.03678 22.0616 6.69906 20.82C6.29677 19.2903 5.83984 17.5868 5.83984 15.1184C5.83984 9.2231 10.6773 4.78796 16.4087 4.78796C22.145 4.78796 26.6398 9.44163 26.6398 15.173C26.649 17.8831 25.5827 20.4861 23.6748 22.4107C21.7669 24.3354 19.1733 25.4245 16.4633 25.4389ZM16.5477 9.88365C13.7565 9.73962 11.5812 11.6716 11.0994 14.7012C10.7021 17.2093 11.4074 20.2638 12.0083 20.4227C12.2964 20.4922 13.0215 19.9062 13.4734 19.4542C14.2208 19.9705 15.091 20.2806 15.9965 20.3532C17.3867 20.42 18.7477 19.9388 19.787 19.0129C20.8262 18.0869 21.4607 16.7902 21.554 15.4015C21.6084 14.0099 21.1142 12.6525 20.178 11.6215C19.2417 10.5905 17.9381 9.96825 16.5477 9.88862V9.88365Z" fill="white"/></g><defs><clipPath id="clip0_3522_11884"><rect width="32" height="32" fill="white"/></clipPath></defs></svg>',
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
];
