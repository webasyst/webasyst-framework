<?php

return [
  [
    'type' => 'site.CustomBanner3',
    'data' => [
      'block_props' => [
        'site-block-columns' => [
          'color' => 'f-w',
          'padding-bottom' => 'p-b-14 p-b-12-mb',
          'padding-left' => 'p-l-blc',
          'padding-right' => 'p-r-blc',
          'padding-top' => 'p-t-14 p-t-12-mb',
        ],
        'site-block-columns-wrapper' => [
          'border-radius' => 'b-r-l',
          'flex-align' => 'y-c',
          'max-width' => 'cnt',
          'padding-top' => 'p-t-0',
          'padding-bottom' => 'p-b-0',
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
        'site-block-columns-wrapper' => [],
        'site-block-columns' => [
          'min-height' => [
            'name' => 'Browser height',
            'value' => '100vh',
            'type' => 'browser',
          ],
          'background' => [
            'type' => 'self_color',
            'value' => 'radial-gradient(circle,  #000000 0%,  #1a191e 100%)',
            'name' => 'Self color',
            'layers' => [
              [
                'type' => 'self_color',
                'value' => 'radial-gradient(circle,  #000000 0%,  #1a191e 100%)',
                'name' => 'Self color',
                'css' => 'gradient',
                'gradient' => [
                  'type' => 'radial-gradient',
                  'degree' => '90',
                  'stops' => [
                    [
                      'color' => '#000000',
                      'stop' => '0',
                    ],
                    [
                      'color' => '#1a191e',
                      'stop' => '100',
                    ],
                  ],
                ],
              ],
            ],
            'uuid' => 3,
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
                  'column' => 'st-12-mb st-12-tb st-12-lp st-12',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'flex-align-vertical' => 'a-c-s',
                      'margin-bottom' => 'm-b-a',
                      'padding-top' => 'p-t-0',
                      'padding-bottom' => 'p-b-0',
                      'margin-top' => 'm-t-0',
                    ],
                    'site-block-column-wrapper' => [
                      'flex-align' => 'y-c',
                      'margin-left' => 'm-l-a',
                      'margin-right' => 'm-r-a',
                      'padding-top' => 'p-t-0',
                      'padding-bottom' => 'p-b-0',
                      'margin-top' => 'm-t-0',
                      'margin-bottom' => 'm-b-0',
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
                            'type' => 'site.Row',
                            'data' => [
                              'block_props' => [
                                'padding-bottom' => 'p-b-6-lp p-b-6',
                                'padding-top' => 'p-t-8-lp p-t-0',
                                'margin-top' => 'm-t-0-mb',
                              ],
                              'wrapper_props' => [
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb',
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
                                        'type' => 'site.Row',
                                        'data' => [
                                          'block_props' => [
                                            'padding-top' => 'p-t-10 p-t-0-mb',
                                            'padding-bottom' => 'p-b-0',
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
                                                        'margin-bottom' => 'm-b-14',
                                                        'picture-size' => 'i-xl',
                                                        'margin-right' => 'm-r-10',
                                                        'margin-left' => 'm-l-0-mb',
                                                      ],
                                                      'image' => [
                                                        'type' => 'svg',
                                                        'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="64" viewBox="0 0 64 64" fill-rule="evenodd" fill="var(--white)">
        <path d="M32,47 C40.2842712,47 47,40.2842712 47,32 C47,23.7157288 40.2842712,17 32,17 C23.7157288,17 17,23.7157288 17,32 C17,40.2842712 23.7157288,47 32,47 Z M32,50 C41.9411255,50 50,41.9411255 50,32 C50,22.0588745 41.9411255,14 32,14 C22.0588745,14 14,22.0588745 14,32 C14,41.9411255 22.0588745,50 32,50 Z"></path>
        <path d="M32,61 C15.9837423,61 3,48.0162577 3,32 C3,15.9837423 15.9837423,3 32,3 C48.0162577,3 61,15.9837423 61,32 C61,48.0162577 48.0162577,61 32,61 Z M32,52 C20.954305,52 12,43.045695 12,32 C12,20.954305 20.954305,12 32,12 C43.045695,12 52,20.954305 52,32 C52,43.045695 43.045695,52 32,52 Z"></path>
        </svg>',
                                                      ],
                                                    ],
                                                    'children' => [],
                                                  ],
                                                  [
                                                    'type' => 'site.SubColumn',
                                                    'data' => [
                                                      'block_props' => [
                                                        'padding-bottom' => 'p-b-8',
                                                        'margin-right' => 'm-r-a',
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
                                                                  'html' => '<font color="" class="tx-wh">Nomen Societatis</font>',
                                                                  'tag' => 'p',
                                                                  'block_props' => [
                                                                    'font-header' => 't-hdn',
                                                                    'font-size' => [
                                                                      'name' => 'Size #6',
                                                                      'value' => 't-6',
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
                                                                  'html' => '<font color="" class="tx-wh">In pagina interretiali opus technicum fit</font>',
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
                                                    'type' => 'site.Button',
                                                    'data' => [
                                                      'html' => '+1 234 56-78-10',
                                                      'tag' => 'a',
                                                      'block_props' => [
                                                        'margin-bottom' => 'm-b-12',
                                                        'button-style' => [
                                                          'name' => 'Palette',
                                                          'value' => 'btn-wht-trnsp',
                                                          'type' => 'palette',
                                                        ],
                                                        'button-size' => 'inp-m p-l-13 p-r-13',
                                                      ],
                                                      'link_props' => [
                                                        'href' => 'tel:+12345678910',
                                                        'data-value' => 'phone-link',
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
                'type' => 'site.Column',
                'data' => [
                  'elements' => [
                    'main' => 'site-block-column',
                    'wrapper' => 'site-block-column-wrapper',
                  ],
                  'column' => 'st-12-lp st-12-tb st-12-mb st-12',
                  'block_props' => [
                    'site-block-column' => [
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'padding-top' => 'p-t-21 p-t-18-mb',
                      'padding-bottom' => 'p-b-22 p-b-18-mb',
                      'border-radius' => 'b-r-l',
                      'background' => [
                        'name' => 'semi-transparent-white',
                        'value' => 'bg-w-opc-3',
                        'type' => 'palette',
                        'uuid' => 2,
                        'layers' => [
                          [
                            'name' => 'semi-transparent-white',
                            'value' => 'bg-w-opc-3',
                            'type' => 'palette',
                            'uuid' => 2,
                          ],
                        ],
                      ],
                    ],
                    'site-block-column-wrapper' => [
                      'column-max-width' => 'fx-7',
                      'flex-align' => 'y-c',
                      'margin-left' => 'm-l-a',
                      'margin-right' => 'm-r-a',
                    ],
                  ],
                  'wrapper_props' => [
                    'flex-align' => 'y-c',
                  ],
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
                            'type' => 'site.Image',
                            'data' => [
                              'block_props' => [
                                'margin-bottom' => 'm-b-14',
                                'picture-size' => 'i-xxl',
                              ],
                              'image' => [
                                'type' => 'svg',
                                'color' => [
                                  'name' => 'Palette',
                                  'value' => 'tx-w-opc-7',
                                  'type' => 'palette',
                                ],
                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="96" height="96" viewBox="0 0 96 96" fill="var(--w-opc-7)">
<path d="M71.6199 21.4646C71.6748 20.6045 71.725 19.7381 71.839 18.8869C71.9474 18.0783 72.0293 17.6272 72.7293 17.1284C73.3948 16.6542 74.1184 16.217 74.8118 15.7826L79.0151 13.1831C79.7932 12.6968 80.5684 12.207 81.3479 11.7158C82.1047 11.2517 83.4853 9.90876 84.3921 10.777C85.3742 11.7173 86.2981 12.7191 87.2721 13.6696C87.4969 13.8888 87.8249 14.215 88.0024 14.4749C88.4028 15.0628 87.7837 15.9718 87.4804 16.4534C87.1902 16.9137 86.9541 17.3089 86.6961 17.7399C86.0293 18.8685 85.3562 19.9933 84.6766 21.1142L82.4725 24.7913C82.0304 25.5254 81.5415 26.5534 80.8718 27.1282C80.6872 27.222 80.2707 27.2998 80.0824 27.2709C79.2716 27.1463 78.3203 27.0349 77.5346 26.8454C76.9023 27.1773 75.3618 28.8536 74.7891 29.4289L67.8662 36.4606C67.8662 36.4606 65.2816 39.0736 64.9536 39.3952L55.8589 48.5222C55.4476 48.9378 55.0547 49.3491 54.6179 49.7414C54.2378 50.1541 53.3689 50.0666 52.9704 49.6733C51.6593 48.3675 50.2617 47.0195 49.0903 45.5916C48.2127 44.5218 50.1893 42.9474 50.8694 42.2691L65.9002 27.2594L69.51 23.6435C70.1485 23.0069 71.0827 22.1596 71.6199 21.4646Z"></path>
<path d="M48.839 66.8121L34.1597 81.4907L31.3215 84.3423C30.0489 85.6178 29.17 86.5644 27.5346 87.3946C25.8088 88.2706 24.3816 88.4732 22.503 88.503C19.0451 88.556 16.4119 87.2124 13.977 84.8497C10.434 81.5102 8.91297 75.4128 11.5778 71.1186C12.3107 69.9377 14.0304 68.5515 14.9453 67.4302C15.224 67.089 15.8404 66.5712 16.1857 66.2129C21.3139 60.9674 26.4802 55.7589 31.6839 50.5886C31.9507 50.3227 32.4159 49.8692 32.7244 49.6932C33.9662 48.9851 34.5813 50.4069 35.6339 50.4883C35.8231 50.503 36.0562 50.3397 36.1859 50.1958C36.6704 49.6577 36.2443 48.996 36.2096 48.3727C36.1838 47.9074 36.4926 47.5265 36.8139 47.2046C37.4255 46.5922 38.0388 45.9859 38.6588 45.3758C39.2755 44.7903 39.8401 44.1215 40.5207 43.6103C41.1544 43.1345 41.947 43.2768 42.5188 43.7707C43.1066 44.2786 43.6451 44.8563 44.1904 45.4071L47.0108 48.2326L52.5912 53.7815C53.2444 54.4251 53.9075 55.0494 54.572 55.6813C55.1361 56.218 55.1158 56.6472 55.1077 57.3865C55.0254 58.6488 54.3751 59.7752 53.3106 60.4629C52.7938 60.8993 52.2041 61.2419 51.5689 61.4753C50.2825 61.9561 48.1291 61.9793 48.7765 64.0235C48.8754 64.3368 49.0098 64.6506 49.1286 64.9578C49.2905 65.6289 49.3847 66.2721 48.839 66.8121ZM18.4493 77.144C18.8759 77.0073 19.0874 76.9013 19.4187 76.5785C20.0715 75.9424 20.7024 75.2665 21.3463 74.6205L26.5816 69.3475L34.7554 61.1719C35.0878 60.8378 35.4132 60.5098 35.7543 60.1837C36.2821 59.6787 36.9983 59.1789 37.3355 58.5215C37.8657 57.4878 36.8736 56.0949 35.7591 56.0902C34.9484 56.2582 34.5721 56.6771 34.0017 57.2436C33.3558 57.8854 32.7306 58.548 32.0785 59.1831L25.2409 65.9507L20.1157 71.0731C19.0622 72.1272 17.8496 73.2328 16.9726 74.433C16.1521 75.5562 17.0487 77.0693 18.4493 77.144ZM23.4091 80.7903C23.4975 81.7094 24.0316 82.0511 24.8345 82.3356C25.1116 82.2849 25.5174 82.2045 25.7219 82.0128C26.5123 81.2735 27.3121 80.4542 28.0774 79.6885L39.6817 68.081C40.6918 67.0682 41.7205 66.0586 42.7246 65.0335C44.1583 63.5696 42.129 61.7006 40.7824 62.7456C40.2909 63.1271 39.6491 63.8502 39.1889 64.3164C38.3006 65.2157 37.4075 66.1102 36.5094 67L28.9815 74.5315C27.5292 75.993 25.7457 77.8715 24.252 79.2365C23.6973 79.8016 23.4281 79.971 23.4091 80.7903Z"></path>
<path d="M28.1631 38.1182C26.6114 38.6262 24.9791 38.8424 23.3486 38.7556C21.3039 38.6588 19.3119 38.198 17.4728 37.2829C11.1123 34.1177 6.67254 27.2465 7.01894 20.0632C7.07503 18.9001 7.05946 16.8563 8.59003 16.7222C9.71462 16.6237 10.4244 17.8936 11.1733 18.53C13.1013 20.4204 14.9765 22.3748 16.9321 24.2358C17.7031 24.9017 18.3275 25.979 19.4455 26.0197C23.3264 26.1608 26.8298 23.0591 27.0896 19.1644C27.2477 16.7939 25.8432 15.8504 24.3488 14.3492L20.4797 10.4562C19.8344 9.80136 18.2426 8.5604 18.3451 7.58887C18.3813 7.23111 18.5658 6.90482 18.8536 6.68929C20.1626 5.71601 24.4949 5.97249 26.1314 6.26276C30.5081 7.03914 34.6315 9.48265 37.2075 13.121C39.8356 16.839 40.8653 21.4546 40.0666 25.937C43.3377 29.3378 46.8238 32.6025 50.1349 35.9686C50.8212 36.6667 50.993 37.3249 50.2736 38.0829C49.0397 39.3605 47.6964 40.602 46.4686 41.882C45.6461 42.7395 44.8344 42.3085 44.0576 41.7378C43.6531 41.4406 43.0372 41.1017 42.5637 40.9372L42.4918 40.9126C41.3555 40.6439 40.5154 40.5727 39.3932 40.9927C38.9214 41.2078 38.5101 41.4892 38.1195 41.8291C37.4129 42.4438 35.9835 44.2249 35.1985 44.3998C34.9859 44.4472 34.762 44.44 34.5658 44.3392C33.9154 44.0056 30.4119 40.2833 29.5734 39.4297C29.1629 39.0117 28.7049 38.4382 28.2033 38.1408C28.1901 38.133 28.1765 38.1257 28.1631 38.1182Z"></path>
<path d="M61.0819 70.3787C61.018 70.337 60.5926 69.9068 60.5182 69.8306C59.8022 69.0975 59.123 68.3265 58.4121 67.5881C57.7637 66.917 57.1105 66.2497 56.4527 65.5871C54.0862 63.2078 54.3153 63.2291 56.4967 60.8906C56.8162 60.5475 57.111 60.1821 57.3789 59.7973C58.4225 58.3036 58.724 56.295 57.9487 54.6048C57.3145 53.2205 56.5871 52.4556 57.9506 51.2355C59.1623 50.1512 60.2494 48.7999 61.5415 47.798C61.6612 47.7047 62.1071 47.6527 62.2718 47.6271C62.5321 47.7199 62.7934 47.8496 62.9898 48.0445C63.7977 48.8463 64.5909 49.6727 65.3936 50.4787L71.4782 56.6556C72.7713 57.9718 74.0686 59.191 75.3635 60.4736L81.9351 67.0666C82.4988 67.6606 83.7961 68.8566 84.25 69.4207C84.6636 69.7776 85.0262 70.2499 85.4001 70.5869C87.3146 72.313 88.7657 74.1631 88.9588 76.8296C89.0572 78.1903 88.9947 79.3135 88.6033 80.6538C88.3094 81.3997 87.8844 82.4022 87.3553 83.0132C85.1663 85.6371 82.9461 86.3854 79.6004 86.2661C75.2641 86.1113 73.4353 83.1708 70.7072 80.3736L66.1542 75.6388C64.8053 74.2497 63.5412 73.0045 62.2325 71.5595C61.8312 71.1653 61.4539 70.8013 61.0819 70.3787ZM76.0204 77.3725C76.3238 79.1937 77.8928 80.5331 79.7391 80.5464C81.5849 80.5601 83.1737 79.2439 83.5031 77.4274C83.833 75.6109 82.8088 73.82 81.0756 73.1839C79.3424 72.5478 77.4029 73.2497 76.479 74.8484C76.0379 75.6109 75.8761 76.5035 76.0204 77.3725Z"></path>
</svg>',
                                'fill' => 'removed',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-wh">Pagina interretialis renovatur. Nobis coniungi pergitur</font>',
                              'tag' => 'h2',
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
                                'align' => 't-c',
                                'line-height' => 't-lh-xs',
                              ],
                            ],
                            'children' => [],
                          ],
                          [
                            'type' => 'site.Heading',
                            'data' => [
                              'html' => '<font color="" class="tx-bw-5">Dum pagina tempore non praebetur, nobis directo scribere, ad societates sociales transire vel mercatum electronicum iubere potes.
</font>',
                              'tag' => 'h3',
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
                            'type' => 'site.Hr',
                            'data' => [
                              'tag' => 'hr',
                              'block_props' => [
                                'margin-top' => 'm-t-10',
                                'margin-bottom' => 'm-b-10',
                                'border-color' => [
                                  'name' => 'semi-transparent-white',
                                  'value' => 'br-w-opc-2',
                                  'type' => 'palette',
                                ],
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
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb',
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
                                                    'type' => 'site.SubColumn',
                                                    'data' => [
                                                      'block_props' => [
                                                        'padding-top' => 'p-t-8',
                                                        'padding-bottom' => 'p-b-8',
                                                        'full-width' => 'f-w',
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
                                                                  'html' => '<font color="" class="tx-w-opc-6">Inscriptio Tabernae</font>',
                                                                  'tag' => 'h3',
                                                                  'block_props' => [
                                                                    'font-header' => 't-hdn',
                                                                    'font-size' => [
                                                                      'name' => 'Size #7',
                                                                      'value' => 't-7',
                                                                      'unit' => 'px',
                                                                      'type' => 'library',
                                                                    ],
                                                                    'margin-top' => 'm-t-0',
                                                                    'margin-bottom' => 'm-b-9',
                                                                    'align' => 't-c',
                                                                  ],
                                                                ],
                                                                'children' => [],
                                                              ],
                                                              [
                                                                'type' => 'site.Heading',
                                                                'data' => [
                                                                  'html' => '<font color="" class="tx-wh">Roma, Via Augusta 10&nbsp;</font><br>',
                                                                  'tag' => 'h3',
                                                                  'block_props' => [
                                                                    'font-header' => 't-hdn',
                                                                    'font-size' => [
                                                                      'name' => 'Size #6',
                                                                      'value' => 't-6',
                                                                      'unit' => 'px',
                                                                      'type' => 'library',
                                                                    ],
                                                                    'margin-top' => 'm-t-0',
                                                                    'margin-bottom' => 'm-b-0',
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
                                                    'type' => 'site.SubColumn',
                                                    'data' => [
                                                      'block_props' => [
                                                        'padding-top' => 'p-t-8',
                                                        'padding-bottom' => 'p-b-8',
                                                        'full-width' => 'f-w',
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
                                                                  'html' => '<font color="" class="tx-w-opc-6">Telephonum</font>',
                                                                  'tag' => 'h3',
                                                                  'block_props' => [
                                                                    'font-header' => 't-hdn',
                                                                    'font-size' => [
                                                                      'name' => 'Size #7',
                                                                      'value' => 't-7',
                                                                      'unit' => 'px',
                                                                      'type' => 'library',
                                                                    ],
                                                                    'margin-top' => 'm-t-0',
                                                                    'margin-bottom' => 'm-b-9',
                                                                    'align' => 't-c',
                                                                  ],
                                                                ],
                                                                'children' => [],
                                                              ],
                                                              [
                                                                'type' => 'site.Heading',
                                                                'data' => [
                                                                  'html' => '<font color="#ffffff"><span style="caret-color: rgb(255, 255, 255);">+1 234 56-78-90</span></font>',
                                                                  'tag' => 'h3',
                                                                  'block_props' => [
                                                                    'font-header' => 't-hdn',
                                                                    'font-size' => [
                                                                      'name' => 'Size #6',
                                                                      'value' => 't-6',
                                                                      'unit' => 'px',
                                                                      'type' => 'library',
                                                                    ],
                                                                    'margin-top' => 'm-t-0',
                                                                    'margin-bottom' => 'm-b-0',
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
                                                    'type' => 'site.SubColumn',
                                                    'data' => [
                                                      'block_props' => [
                                                        'padding-top' => 'p-t-8',
                                                        'padding-bottom' => 'p-b-8',
                                                        'full-width' => 'f-w',
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
                                                                  'html' => '<font color="" class="tx-w-opc-6">Email</font>',
                                                                  'tag' => 'h3',
                                                                  'block_props' => [
                                                                    'font-header' => 't-hdn',
                                                                    'font-size' => [
                                                                      'name' => 'Size #7',
                                                                      'value' => 't-7',
                                                                      'unit' => 'px',
                                                                      'type' => 'library',
                                                                    ],
                                                                    'margin-top' => 'm-t-0',
                                                                    'margin-bottom' => 'm-b-9',
                                                                    'align' => 't-c',
                                                                  ],
                                                                ],
                                                                'children' => [],
                                                              ],
                                                              [
                                                                'type' => 'site.Heading',
                                                                'data' => [
                                                                  'html' => '<font color="" class="tx-wh">epistula@tabellario.romano</font><br>',
                                                                  'tag' => 'h3',
                                                                  'block_props' => [
                                                                    'font-header' => 't-hdn',
                                                                    'font-size' => [
                                                                      'name' => 'Size #6',
                                                                      'value' => 't-6',
                                                                      'unit' => 'px',
                                                                      'type' => 'library',
                                                                    ],
                                                                    'margin-top' => 'm-t-0',
                                                                    'margin-bottom' => 'm-b-0',
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
                                'flex-wrap' => 'n-wr-ds n-wr-lp n-wr-tb',
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
                                                    'type' => 'site.SubColumn',
                                                    'data' => [
                                                      'block_props' => [
                                                        'padding-top' => 'p-t-8',
                                                        'padding-bottom' => 'p-b-8',
                                                        'full-width' => 'f-w',
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
                                                                  'html' => '<span style="caret-color: rgba(255, 255, 255, 0.25); color: rgba(255, 255, 255, 0.25);">Nos in Socialibus</span>',
                                                                  'tag' => 'h3',
                                                                  'block_props' => [
                                                                    'font-header' => 't-hdn',
                                                                    'font-size' => [
                                                                      'name' => 'Size #7',
                                                                      'value' => 't-7',
                                                                      'unit' => 'px',
                                                                      'type' => 'library',
                                                                    ],
                                                                    'margin-top' => 'm-t-0',
                                                                    'margin-bottom' => 'm-b-10',
                                                                    'align' => 't-c',
                                                                  ],
                                                                ],
                                                                'children' => [],
                                                              ],
                                                              [
                                                                'type' => 'site.Row',
                                                                'data' => [
                                                                  'block_props' => [
                                                                    'padding-top' => 'p-t-0',
                                                                    'padding-bottom' => 'p-b-8',
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
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-11',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="var(--white)">
<g clip-path="url(#clip0_611_7268)">
<path d="M16.001 13C17.6577 13.0001 19.001 14.3432 19.001 16C19.001 17.6566 17.6577 18.9999 16.001 19C14.3441 19 13.001 17.6567 13.001 16C13.001 14.3431 14.3441 13 16.001 13Z"></path>
<path fill-rule="evenodd" clip-rule="evenodd" d="M15.1934 8.62207C15.433 8.6217 15.7007 8.62207 16.001 8.62207C18.4039 8.62207 18.6888 8.6307 19.6377 8.67383C20.5151 8.71395 20.9914 8.86023 21.3086 8.9834C21.7286 9.14648 22.0281 9.34142 22.3428 9.65625C22.6577 9.9712 22.8531 10.2715 23.0166 10.6914C23.1398 11.0083 23.2862 11.4848 23.3262 12.3623C23.3693 13.3111 23.3789 13.5962 23.3789 15.998C23.3789 18.3998 23.3693 18.685 23.3262 19.6338C23.286 20.5112 23.1398 20.9878 23.0166 21.3047C22.8535 21.7245 22.6577 22.0232 22.3428 22.3379C22.0278 22.6529 21.7287 22.8477 21.3086 23.0107C20.9918 23.1344 20.5151 23.2812 19.6377 23.3213C18.689 23.3644 18.4039 23.374 16.001 23.374C13.5979 23.374 13.3128 23.3644 12.3643 23.3213C11.4869 23.2808 11.0108 23.1339 10.6934 23.0107C10.2733 22.8477 9.97314 22.6528 9.6582 22.3379C9.34327 22.023 9.14785 21.7238 8.98438 21.3037C8.86119 20.9869 8.71475 20.5103 8.6748 19.6328C8.63169 18.6842 8.62305 18.3991 8.62305 15.9961C8.62305 13.5929 8.63169 13.3089 8.6748 12.3604C8.71492 11.4831 8.86121 11.0067 8.98438 10.6895C9.14751 10.2695 9.3432 9.9693 9.6582 9.6543C9.97318 9.33933 10.2733 9.14396 10.6934 8.98047C11.0106 8.85675 11.4871 8.71021 12.3643 8.66992C13.1943 8.63243 13.5162 8.62199 15.1934 8.62012V8.62207ZM16.001 11.3779C13.4486 11.3779 11.3789 13.4476 11.3789 16C11.3789 18.5524 13.4486 20.6211 16.001 20.6211C18.5534 20.621 20.6221 18.5523 20.6221 16C20.6221 13.4477 18.5534 11.378 16.001 11.3779ZM20.8047 10.1162C20.2086 10.1164 19.7246 10.5999 19.7246 11.1963C19.7248 11.7922 20.2088 12.2762 20.8047 12.2764C21.4009 12.2764 21.8846 11.7923 21.8848 11.1963C21.8848 10.6 21.401 10.1162 20.8047 10.1162Z"></path>
<path fill-rule="evenodd" clip-rule="evenodd" d="M15.998 0C24.8346 0 31.998 7.16344 31.998 16C31.998 24.8366 24.8346 32 15.998 32C7.16149 32 -0.00195312 24.8366 -0.00195312 16C-0.00195312 7.16344 7.16149 0 15.998 0ZM15.999 7C13.5566 7.00001 13.2496 7.01102 12.29 7.05469C11.3319 7.09856 10.6778 7.2501 10.1055 7.47266C9.51353 7.70254 9.01099 8.01031 8.51074 8.51074C8.01035 9.01086 7.70322 9.51295 7.47266 10.1045C7.24953 10.677 7.09683 11.3314 7.05371 12.2891C7.01078 13.2488 7 13.556 7 16C7 18.4442 7.01119 18.7501 7.05469 19.71C7.09875 20.6681 7.25028 21.3223 7.47266 21.8945C7.70271 22.4865 8.01034 22.989 8.51074 23.4893C9.01069 23.9897 9.51304 24.2975 10.1045 24.5273C10.6771 24.7499 11.3312 24.9015 12.2891 24.9453C13.2491 24.989 13.5559 25 16 25C18.4442 25 18.75 24.989 19.71 24.9453C20.6681 24.9015 21.3229 24.7499 21.8955 24.5273C22.4871 24.2975 22.9884 23.9896 23.4883 23.4893C23.989 22.989 24.2967 22.4863 24.5273 21.8945C24.7485 21.3221 24.9003 20.6677 24.9453 19.71C24.9884 18.7502 25 18.444 25 16C25 13.5558 24.9884 13.249 24.9453 12.2891C24.9003 11.331 24.7485 10.6768 24.5273 10.1045C24.2968 9.51265 23.9889 9.01095 23.4883 8.51074C22.9877 8.01012 22.487 7.70235 21.8945 7.47266C21.3209 7.25019 20.6667 7.09857 19.709 7.05469C18.7491 7.011 18.4432 7 15.999 7Z"></path>
</g>
<defs>
<clipPath id="clip0_611_7268">
<rect width="32" height="32"></rect>
</clipPath>
</defs>
</svg>',
                                                                                'fill' => 'removed',
                                                                              ],
                                                                            ],
                                                                            'children' => [],
                                                                          ],
                                                                          [
                                                                            'type' => 'site.Image',
                                                                            'data' => [
                                                                              'block_props' => [
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-11',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto" viewBox="0 0 33 32" fill="var(--white)"><g clip-path="url(#clip0_10717_116298)"><path d="M16.9131 0.492188C20.8704 0.515741 24.6661 2.06705 27.5068 4.82227C30.3476 7.57755 32.0137 11.3244 32.1582 15.2793C32.2333 17.3038 31.9066 19.323 31.1973 21.2207C30.4879 23.1185 29.4101 24.8571 28.0254 26.3359C26.6405 27.8149 24.9761 29.0055 23.1289 29.8379C21.2819 30.6702 19.2879 31.1277 17.2627 31.1855H16.8242C14.5219 31.1862 12.2484 30.6693 10.1729 29.6729L2.13965 31.46H2.11719C2.10047 31.4599 2.08357 31.4562 2.06836 31.4492C2.05308 31.4422 2.03937 31.4316 2.02832 31.4189C2.01739 31.4063 2.00873 31.3919 2.00391 31.376C1.99906 31.3599 1.99781 31.3428 2 31.3262L3.35742 23.2041C2.07929 20.8656 1.43132 18.2349 1.47852 15.5703C1.52574 12.9058 2.26674 10.2995 3.62695 8.00781C4.98713 5.7162 6.92004 3.81771 9.23633 2.5C11.5528 1.18229 14.1728 0.490219 16.8379 0.492188H16.9131ZM10.8066 7.7002C10.6519 7.71881 10.5003 7.7605 10.3574 7.82422C10.167 7.90918 9.99589 8.03217 9.85352 8.18457C9.45116 8.59719 8.32581 9.5904 8.26074 11.6758C8.19581 13.7607 9.65239 15.8235 9.85645 16.1133C10.0602 16.4026 12.6426 20.9076 16.8955 22.7344C19.395 23.8112 20.4907 23.9961 21.2012 23.9961C21.494 23.9961 21.7152 23.9649 21.9463 23.9512C22.7256 23.903 24.4838 23.0026 24.8672 22.0234C25.2506 21.0442 25.2759 20.1878 25.1748 20.0166C25.0737 19.8456 24.7963 19.7219 24.3789 19.5029C23.9608 19.2836 21.9116 18.1882 21.5264 18.0342C21.3836 17.9677 21.2294 17.9276 21.0723 17.916C20.9701 17.9214 20.8711 17.9524 20.7832 18.0049C20.6952 18.0573 20.6208 18.1303 20.5674 18.2178C20.225 18.6441 19.4398 19.5694 19.1758 19.8369C19.1183 19.9031 19.0472 19.9569 18.9678 19.9941C18.8883 20.0312 18.8016 20.0511 18.7139 20.0527C18.5519 20.0456 18.3927 20.0029 18.249 19.9277C17.0078 19.4005 15.8757 18.6456 14.9121 17.7021C14.0118 16.8148 13.2479 15.7989 12.6455 14.6875C12.4127 14.256 12.6461 14.0331 12.8584 13.8311C13.0706 13.629 13.2976 13.3498 13.5166 13.1084C13.6964 12.9023 13.8464 12.6718 13.9619 12.4238C14.0216 12.3087 14.052 12.1804 14.0498 12.0508C14.0478 11.921 14.0137 11.7939 13.9502 11.6807C13.8492 11.4648 13.0947 9.34441 12.7402 8.49316C12.4528 7.76587 12.1101 7.741 11.8105 7.71875C11.5642 7.70164 11.2814 7.69313 10.999 7.68457H10.9629L10.8066 7.7002Z"></path></g><defs><clipPath id="clip0_10717_116298"><rect width="32" height="32" transform="translate(0.866699)"></rect></clipPath></defs></svg>',
                                                                                'fill' => 'removed',
                                                                              ],
                                                                            ],
                                                                            'children' => [],
                                                                          ],
                                                                          [
                                                                            'type' => 'site.Image',
                                                                            'data' => [
                                                                              'block_props' => [
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-11',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="auto " viewBox="0 0 33 32" fill="var(--white)"><g clip-path="url(#clip0_10717_116287)"><path d="M16.6499 0C25.4865 0 32.6499 7.16344 32.6499 16C32.6499 24.8366 25.4865 32 16.6499 32C7.81335 32 0.649902 24.8366 0.649902 16C0.649902 7.16344 7.81335 0 16.6499 0ZM23.1919 9.63184C22.5901 9.64249 21.6661 9.96352 17.2231 11.8115C15.6668 12.4589 12.557 13.7991 7.89307 15.8311C7.13574 16.1322 6.73883 16.4268 6.70264 16.7148C6.6333 17.2679 7.42934 17.44 8.43115 17.7656C9.24801 18.0312 10.3471 18.3421 10.9185 18.3545C11.4366 18.3657 12.0153 18.1523 12.6538 17.7139C17.0118 14.7721 19.2616 13.285 19.4028 13.2529C19.5025 13.2303 19.6404 13.202 19.7339 13.2852C19.8271 13.3681 19.818 13.5246 19.8081 13.5674C19.7289 13.9051 15.6367 17.6282 15.3999 17.874C14.4996 18.8091 13.4756 19.3809 15.0552 20.4219C16.422 21.3226 17.2175 21.8973 18.6255 22.8203C19.5254 23.4102 20.2315 24.1099 21.1606 24.0244C21.588 23.9849 22.0292 23.5831 22.2534 22.3848C22.7835 19.5511 23.8261 13.4108 24.0669 10.8809C24.0879 10.6592 24.0609 10.3754 24.0396 10.251C24.0182 10.1265 23.9736 9.94958 23.812 9.81836C23.6203 9.66281 23.3243 9.6295 23.1919 9.63184Z"></path></g><defs><linearGradient id="paint0_linear_10717_116287" x1="1600.65" y1="0" x2="1600.65" y2="3176.27" gradientUnits="userSpaceOnUse"><stop stop-color="#2AABEE"></stop><stop offset="1" stop-color="#229ED9"></stop></linearGradient><clipPath id="clip0_10717_116287"><rect width="32" height="32" transform="translate(0.649902)"></rect></clipPath></defs></svg>',
                                                                                'fill' => 'removed',
                                                                              ],
                                                                            ],
                                                                            'children' => [],
                                                                          ],
                                                                          [
                                                                            'type' => 'site.Image',
                                                                            'data' => [
                                                                              'block_props' => [
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-11',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="var(--white)">
<g clip-path="url(#clip0_611_7196)">
<path fill-rule="evenodd" clip-rule="evenodd" d="M16 0C24.8366 0 32 7.16344 32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0ZM5.40234 9.7334C5.57568 18.0534 9.73598 23.0537 17.0293 23.0537H17.4424V18.293C18.7288 18.455 19.9389 18.9931 20.9209 19.8398C21.9028 20.6865 22.6125 21.8051 22.9619 23.0537H26.749C26.3598 21.6162 25.6701 20.2773 24.7256 19.126C23.7809 17.9745 22.6024 17.0357 21.2686 16.373C22.4635 15.6477 23.4965 14.6835 24.3018 13.541C25.1069 12.3986 25.6679 11.1024 25.9492 9.7334H22.5088C21.7621 12.3733 19.549 14.7732 17.4424 15V9.7334H14.002V18.96C11.8687 18.4264 9.17566 15.8398 9.05566 9.7334H5.40234Z"></path>
</g>
<defs>
<clipPath id="clip0_611_7196">
<rect width="32" height="32"></rect>
</clipPath>
</defs>
</svg>',
                                                                                'fill' => 'removed',
                                                                              ],
                                                                            ],
                                                                            'children' => [],
                                                                          ],
                                                                          [
                                                                            'type' => 'site.Image',
                                                                            'data' => [
                                                                              'block_props' => [
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-0',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="var(--white)">
<g clip-path="url(#clip0_611_7202)">
<path d="M16.5479 9.88379V9.88867C17.938 9.96833 19.2415 10.5903 20.1777 11.6211C21.1139 12.6521 21.6081 14.0098 21.5537 15.4014C21.4604 16.79 20.8262 18.0867 19.7871 19.0127C18.7478 19.9386 17.3863 20.4203 15.9961 20.3535C15.0908 20.2809 14.2209 19.9703 13.4736 19.4541C13.022 19.9058 12.2972 20.4916 12.0088 20.4229C11.4079 20.264 10.7023 17.2093 11.0996 14.7012C11.5814 11.6716 13.7567 9.73976 16.5479 9.88379Z"></path>
<path fill-rule="evenodd" clip-rule="evenodd" d="M16 0C24.8366 0 32 7.16344 32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0ZM16.4092 4.78809C10.6779 4.78809 5.83997 9.22298 5.83984 15.1182C5.83984 17.5866 6.29693 19.2906 6.69922 20.8203C7.0369 22.0618 7.33496 23.1991 7.33496 24.5449C7.47902 26.3377 10.781 25.29 11.8242 23.9492C13.473 25.1411 14.422 25.4384 16.4629 25.4385C19.1728 25.4241 21.7669 24.3357 23.6748 22.4111C25.5827 20.4865 26.6488 17.883 26.6396 15.1729C26.6396 9.44171 22.1452 4.78834 16.4092 4.78809Z"></path>
</g>
<defs>
<clipPath id="clip0_611_7202">
<rect width="32" height="32"></rect>
</clipPath>
</defs>
</svg>',
                                                                                'fill' => 'removed',
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
                                                    'type' => 'site.SubColumn',
                                                    'data' => [
                                                      'block_props' => [
                                                        'padding-top' => 'p-t-8',
                                                        'padding-bottom' => 'p-b-8',
                                                        'full-width' => 'f-w',
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
                                                                  'html' => '<span style="caret-color: rgba(255, 255, 255, 0.25); color: rgba(255, 255, 255, 0.25);">Fora Venalia</span>',
                                                                  'tag' => 'h3',
                                                                  'block_props' => [
                                                                    'font-header' => 't-hdn',
                                                                    'font-size' => [
                                                                      'name' => 'Size #7',
                                                                      'value' => 't-7',
                                                                      'unit' => 'px',
                                                                      'type' => 'library',
                                                                    ],
                                                                    'margin-top' => 'm-t-0',
                                                                    'margin-bottom' => 'm-b-10',
                                                                    'align' => 't-c',
                                                                  ],
                                                                ],
                                                                'children' => [],
                                                              ],
                                                              [
                                                                'type' => 'site.Row',
                                                                'data' => [
                                                                  'block_props' => [
                                                                    'padding-top' => 'p-t-0',
                                                                    'padding-bottom' => 'p-b-8',
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
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-11',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="var(--white)">
<g clip-path="url(#clip0_611_7212)">
<path d="M17.3564 14.3926C17.3723 15.2709 16.9675 16.1146 16.249 16.6201C15.804 16.9332 15.3183 17.1582 14.8408 17.1582C13.2595 17.158 13.304 15.7277 13.6689 15.0488C14.0105 14.4135 14.9131 13.2877 17.335 13.2109L17.3564 14.3926Z"></path>
<path fill-rule="evenodd" clip-rule="evenodd" d="M16 0C24.8366 0 32 7.16344 32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0ZM4.72656 20.7646C4.43601 20.6218 4.17417 21.0064 4.41406 21.2236C6.11839 22.7639 9.61043 25.1278 14.9854 25.1279C20.3805 25.1279 23.4992 23.3087 24.9512 22.1328C25.1958 21.9343 24.9811 21.5452 24.6836 21.6494C22.8533 22.2902 19.217 23.3281 14.8711 23.3281C10.6182 23.328 6.77038 21.7714 4.72656 20.7646ZM26.8525 20.0791C24.7475 19.4466 23.3383 20.1701 22.7715 20.5898C22.671 20.665 22.7233 20.8245 22.8486 20.8242C23.7127 20.8178 25.845 20.8318 26.0176 21.123C26.1665 21.3775 25.6977 23.2014 25.4668 24.0518C25.4322 24.1795 25.5898 24.2709 25.6836 24.1777C27.0353 22.8304 27.1847 21.2095 27.1797 20.5186C27.1782 20.3166 27.0459 20.1373 26.8525 20.0791ZM15.7568 5.31836C11.6584 5.31865 10.3909 7.50344 9.99902 8.85449C9.84379 9.39064 10.2094 9.93663 10.7646 9.99414L12.3535 10.1582C12.7271 10.1965 13.0858 9.99799 13.248 9.65918C13.556 9.01509 14.0929 8.05591 15.749 8.05566C16.3908 8.05566 17.2938 8.32666 17.2939 9.12012C17.2939 10.2556 17.292 10.8024 17.292 10.8174C17.2963 11.0577 17.1027 11.2549 16.8623 11.2549H15.3096C14.1373 11.255 9.68555 11.8566 9.68555 16.3086C9.68561 20.7593 14.4069 20.2486 14.7236 20.1748C15.3089 20.0376 16.4053 19.6375 17.4355 18.6553C17.6086 18.4902 17.8847 18.5046 18.0352 18.6904L18.8682 19.7197C19.1916 20.1198 19.7865 20.162 20.1631 19.8115L21.5303 18.5381C21.8451 18.2448 21.9046 17.7675 21.6709 17.4062L20.9902 16.3535C20.7804 16.0292 20.668 15.6509 20.668 15.2646V10.8428C20.668 9.07773 20.7952 5.31836 15.7568 5.31836Z"></path>
</g>
<defs>
<clipPath id="clip0_611_7212">
<rect width="32" height="32"></rect>
</clipPath>
</defs>
</svg>',
                                                                                'fill' => 'removed',
                                                                              ],
                                                                            ],
                                                                            'children' => [],
                                                                          ],
                                                                          [
                                                                            'type' => 'site.Image',
                                                                            'data' => [
                                                                              'block_props' => [
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-11',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="var(--white)">
<g clip-path="url(#clip0_611_7221)">
<path d="M21.3164 16.7373C21.3162 17.2882 20.994 18.6503 19.1006 18.6504C18.0657 18.6504 17.6193 18.1053 17.6191 17.4736C17.6191 16.3243 19.1169 16.3174 21.3164 16.3174V16.7373Z"></path>
<path d="M12.7246 13.7568C14.0377 13.7568 14.8721 14.7827 14.8721 16.1592C14.8719 17.6345 13.9084 18.6006 12.7344 18.6006C11.3333 18.6006 10.5773 17.4487 10.5771 16.1729C10.5771 14.9837 11.2546 13.7569 12.7246 13.7568Z"></path>
<path d="M5.7832 13.7129C6.9421 13.7129 7.73233 14.4601 7.73242 15.5791H3.73633C3.73644 14.39 4.76747 13.7129 5.7832 13.7129Z"></path>
<path fill-rule="evenodd" clip-rule="evenodd" d="M16 0C24.8366 0 32 7.16344 32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0ZM9.17871 15.7344C9.15789 14.5355 8.41866 12.7383 5.83398 12.7383C3.89746 12.7398 2.28718 13.6028 2.28711 16.2109C2.28711 18.2756 3.37176 19.5762 5.88379 19.5762C8.8404 19.5762 9.03027 17.5254 9.03027 17.5254H7.60059C7.60059 17.5254 7.29171 18.6299 5.79688 18.6299C4.58019 18.6298 3.70508 17.7633 3.70508 16.5508H9.18164V18.2656C9.18164 18.7226 9.15039 19.3662 9.15039 19.3662H10.5166C10.5166 19.3662 10.5664 18.9046 10.5664 18.4834C10.5664 18.4834 11.2409 19.5957 13.0771 19.5957C14.6435 19.5957 15.8025 18.6695 16.1865 17.2422C16.1796 17.3165 16.1738 17.3938 16.1738 17.4736C16.174 18.8595 17.2755 19.6133 18.7646 19.6133C20.7935 19.6133 21.4473 18.4336 21.4473 18.4336C21.4473 18.903 21.4814 19.3662 21.4814 19.3662H22.7686C22.7686 19.3662 22.7188 18.7919 22.7188 18.4258V15.2568C22.7187 14.8317 22.6529 14.4779 22.5332 14.1807L25.1445 19.3369L23.917 21.7861H25.4648L29.7158 13.0039H28.2373L25.9072 17.9326L23.5674 13.001H21.9385L22.3555 13.8242C21.8049 12.9573 20.7166 12.7374 19.7217 12.7373C16.8105 12.7373 16.624 14.4166 16.624 14.6836H18.0723C18.0723 14.6836 18.1483 13.7031 19.6211 13.7031C20.5776 13.7032 21.3184 14.1645 21.3184 15.0508V15.3672H19.6211C17.7383 15.3672 16.5978 15.8501 16.2715 16.8301C16.3045 16.6127 16.3232 16.3869 16.3232 16.1543C16.3231 14.2702 15.117 12.7559 13.0801 12.7559C11.1811 12.7559 10.5846 13.8308 10.5791 13.8408V10.2139H9.17871V15.7344Z"></path>
</g>
<defs>
<clipPath id="clip0_611_7221">
<rect width="32" height="32"></rect>
</clipPath>
</defs>
</svg>',
                                                                                'fill' => 'removed',
                                                                              ],
                                                                            ],
                                                                            'children' => [],
                                                                          ],
                                                                          [
                                                                            'type' => 'site.Image',
                                                                            'data' => [
                                                                              'block_props' => [
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-11',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="var(--white)">
<g clip-path="url(#clip0_611_7228)">
<path d="M20.1709 15.3301C19.8507 17.4612 19.3174 22.3627 24.0137 23.8545C26.4494 24.5945 28.7792 23.644 30.916 21.7979C28.5937 27.7682 22.7915 32 16 32C10.353 32 5.38958 29.0743 2.54199 24.6562L8.2168 17.1416L7.14941 24.707L13.0195 26.7324L20.1709 15.3301Z"></path>
<path d="M16 0C23.6519 0 30.0472 5.37187 31.625 12.5498C29.3634 15.9279 27.1986 18.2983 26.041 17.9941C24.0135 17.4609 25.8279 10.9613 27.002 6.80566V6.69922L20.4912 4.46191L12.6992 17.1416L13.7666 10.2158L8.11035 8.29785L0.21582 18.627C0.0746247 17.7722 0 16.8948 0 16C0 7.16344 7.16344 0 16 0Z"></path>
</g>
<defs>
<clipPath id="clip0_611_7228">
<rect width="32" height="32"></rect>
</clipPath>
</defs>
</svg>',
                                                                                'fill' => 'removed',
                                                                              ],
                                                                            ],
                                                                            'children' => [],
                                                                          ],
                                                                          [
                                                                            'type' => 'site.Image',
                                                                            'data' => [
                                                                              'block_props' => [
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-11',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="var(--white)">
<g clip-path="url(#clip0_611_7242)">
<path d="M23.2324 14.6816C24.7482 14.6819 25.8838 15.8647 25.8838 17.3115C25.8838 18.7589 24.6749 19.9512 23.2178 19.9521C22.8717 19.9529 22.5289 19.8868 22.209 19.7549C21.888 19.6226 21.5961 19.428 21.3506 19.1826C21.1053 18.9371 20.9106 18.6452 20.7783 18.3242C20.6461 18.0034 20.5789 17.6595 20.5801 17.3125C20.5801 15.8175 21.7154 14.6816 23.2324 14.6816Z"></path>
<path fill-rule="evenodd" clip-rule="evenodd" d="M16 0C24.8366 0 32 7.16344 32 16C32 24.8366 24.8366 32 16 32C7.16344 32 0 24.8366 0 16C0 7.16344 7.16344 0 16 0ZM18.4609 17.3281C18.4609 19.958 20.6008 22.0721 23.2207 22.0723C23.8479 22.0742 24.4697 21.9527 25.0498 21.7139C25.6299 21.475 26.1575 21.1237 26.6016 20.6807C27.0458 20.2376 27.3985 19.7105 27.6387 19.1309C27.8789 18.5513 28.0023 17.9301 28.002 17.3027C28.002 14.6339 25.8842 12.5578 23.2324 12.5576C22.2867 12.556 21.3619 12.8393 20.5801 13.3711V8.68555H18.4609V17.3281ZM7.40918 21.7607H8.89355L10.9385 16.4775L12.9932 21.7607H14.4775L17.875 12.8906H15.5771L13.627 17.9355L11.6885 12.8906H10.2041L8.25391 17.9355L6.30469 12.8906H4.00195L7.40918 21.7607Z"></path>
</g>
<defs>
<clipPath id="clip0_611_7242">
<rect width="32" height="32"></rect>
</clipPath>
</defs>
</svg>',
                                                                                'fill' => 'removed',
                                                                              ],
                                                                            ],
                                                                            'children' => [],
                                                                          ],
                                                                          [
                                                                            'type' => 'site.Image',
                                                                            'data' => [
                                                                              'block_props' => [
                                                                                'margin-bottom' => 'm-b-8',
                                                                                'margin-right' => 'm-r-0',
                                                                                'picture-size' => 'i-l',
                                                                              ],
                                                                              'image' => [
                                                                                'type' => 'svg',
                                                                                'color' => [
                                                                                  'name' => 'Palette',
                                                                                  'value' => 'tx-wh',
                                                                                  'type' => 'palette',
                                                                                ],
                                                                                'svg_html' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32" fill="var(--white)">
<g clip-path="url(#clip0_611_7248)">
<path fill-rule="evenodd" clip-rule="evenodd" d="M16.0042 -0.000213623C22.0541 -0.000123289 27.3189 3.35846 30.0383 8.31229C29.7536 8.52821 29.6319 8.91416 29.7278 9.27225L30.4915 12.1248L24.9143 9.67752C24.7198 9.59231 24.5148 9.77031 24.5696 9.97635L26.1477 15.866C26.2566 16.2718 26.6261 16.5695 27.0422 16.5476C27.6226 16.517 27.9939 15.9671 27.8508 15.4334L27.0803 12.5574L31.9504 14.6941C31.9852 15.1248 32.0041 15.5602 32.0042 15.9998C32.0042 18.377 31.4849 20.6328 30.5549 22.6609L24.0403 15.6531C24.3286 14.993 24.4095 14.2953 24.2288 13.6209C23.6833 11.5852 20.9766 10.5414 18.1829 11.2898C15.3892 12.0384 13.567 14.296 14.1125 16.3318C14.3175 17.0965 14.8275 17.7206 15.5325 18.1629L14.0354 31.8787C8.62635 31.2149 4.05303 27.8524 1.70239 23.1785C2.19789 23.3191 2.72371 23.3581 3.24927 23.2732C5.57483 22.8975 6.94153 20.4496 6.09302 18.1785C5.4649 16.4979 3.73571 15.4651 2.01489 15.743C1.22275 15.871 0.541739 16.2394 0.0217285 16.7596C0.00995856 16.5078 0.00415039 16.2545 0.00415039 15.9998C0.00426584 7.16333 7.16767 -0.000213623 16.0042 -0.000213623ZM12.5061 13.0027L7.21509 14.4207C6.6924 14.5607 6.41037 15.154 6.66626 15.6756C6.85405 16.0581 7.30091 16.2344 7.7063 16.1258L10.0598 15.4949L7.71216 21.3855C7.63516 21.5788 7.81007 21.7804 8.00903 21.7273L13.7561 20.1873C14.1612 20.0786 14.4593 19.703 14.4309 19.2781C14.3919 18.6984 13.8511 18.3255 13.3284 18.4656L10.4602 19.2342L12.8059 13.3474C12.8837 13.1522 12.707 12.9489 12.5061 13.0027Z"></path>
<path d="M1.8606 17.6258C3.06394 17.1284 4.40834 17.931 4.59399 19.2576C4.72035 20.1619 4.22357 21.0523 3.40356 21.3914C2.20026 21.8889 0.855755 21.0863 0.670166 19.7596C0.543669 18.8554 1.0405 17.9649 1.8606 17.6258Z"></path>
<path d="M18.6448 13.0105C20.5747 12.4937 22.2947 13.1452 22.5432 14.073C22.7919 15.0008 21.6276 16.4251 19.6975 16.9422C17.7676 17.4593 16.0477 16.8075 15.7991 15.8797C15.5509 14.9519 16.715 13.5277 18.6448 13.0105Z"></path>
</g>
<defs>
<clipPath id="clip0_611_7248">
<rect width="32" height="32"></rect>
</clipPath>
</defs>
</svg>',
                                                                                'fill' => 'removed',
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
                      'padding-left' => 'p-l-clm',
                      'padding-right' => 'p-r-clm',
                      'margin-bottom' => 'm-b-a',
                      'padding-top' => 'p-t-12',
                      'padding-bottom' => 'p-b-12',
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
                              'html' => '© 2026 Nomen Societatis',
                              'tag' => 'p',
                              'block_props' => [
                                'font-header' => 't-rgl',
                                'font-size' => [
                                  'name' => 'Size #6',
                                  'value' => 't-6',
                                  'unit' => 'px',
                                  'type' => 'library',
                                ],
                                'margin-top' => 'm-t-10',
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
];
