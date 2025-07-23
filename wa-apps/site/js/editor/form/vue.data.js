
( function($) {
    $.form_storage = $.form_storage || {};
    $.form_storage.data = {
        FontHeaderGroup: {
            'type': 'font-header',
            'values': [
                {name: "Heading", value: "t-hdn"},
                {name: "Basic text", value: "t-rgl"},
            ],
        },
        /*FontGroup: {
            'type': 'font',
            'values': [
                {name: "Size #1", value: "t-1"},
                {name: "Size #2", value: "t-2"},
                {name: "Size #3", value: "t-3"},
                {name: "Size #4", value: "t-4"},
                {name: "Size #5", value: "t-5"},
                {name: "Size #6", value: "t-6"},
                {name: "Size #7", value: "t-7"},
                {name: "Size #8", value: "t-8"},
            ],
        },*/
        FontGroup: {
            'type': 'font-size',
            'default_data_type': 'block_props',
            'icon': 'fa-font',
            'values': [
                {name: "Size #3", value: "t-3", unit: 'px', type: 'library'},
            ],
            'library': [
                {name: "Size #1", value: "t-1", unit: 'px', type: 'library'},
                {name: "Size #2", value: "t-2", unit: 'px', type: 'library'},
                {name: "Size #3", value: "t-3", unit: 'px', type: 'library'},
                {name: "Size #4", value: "t-4", unit: 'px', type: 'library'},
                {name: "Size #5", value: "t-5", unit: 'px', type: 'library'},
                {name: "Size #6", value: "t-6", unit: 'px', type: 'library'},
                {name: "Size #7", value: "t-7", unit: 'px', type: 'library'},
                {name: "Size #8", value: "t-8", unit: 'px', type: 'library'},
            ],
        },
        FontStyleGroup: {
            'type': 'font-style',
            'values': [
                {name: "Bold", value: "bold", 'icon': 'fa-bold'},
                {name: "Italic", value: "italic", 'icon': 'fa-italic'},
                {name: "Underline", value: "underline", 'icon': 'fa-underline'},
                {name: "Strikethrough", value: "strikethrough",  'icon': 'fa-strikethrough'},
                {name: "Add link", value: "link",  'icon': 'fa-link'},
                {name: "Variables", value: "variables",  'icon': 'fa-dollar-sign'}
            ]
        },
        LineHeightGroup: {
            'type': 'line-height',
            'icon': 'fa-text-height',
            'values': [
                {name: "Default", value: ""},
                {name: "xl", value: "t-lh-xl"},
                {name: "l", value: "t-lh-l"},
                {name: "m", value: "t-lh-m"},
                {name: "s", value: "t-lh-s"},
                {name: "xs", value: "t-lh-xs"},
            ]
        },
        LineHeightGroup: {
            'type': 'line-height',
            'icon': 'fa-text-height',
            'values': [
                {name: "Default", value: ""},
                {name: "xl", value: "t-lh-xl"},
                {name: "l", value: "t-lh-l"},
                {name: "m", value: "t-lh-m"},
                {name: "s", value: "t-lh-s"},
                {name: "xs", value: "t-lh-xs"},
            ]
        },
        PictureSizeGroup: {
            'type': 'picture-size',
            'icon': 'fa-text-height',
            'values': [
                {name: "Default", value: "removeData"},
                {name: "xxl", value: "i-xxl"},
                {name: "xl", value: "i-xl"},
                {name: "l", value: "i-l"},
                {name: "m", value: "i-m"},
                {name: "s", value: "i-s"},
                {name: "xs", value: "i-xs"},
                {name: "xxs", value: "i-xxs"},
                {name: "xxxs", value: "i-xxxs"},
            ]
        },
        TextColorGroup: {
            'type': 'color',
            'icon': 'fa-circle',
            'values': [
                {name: "Default", value: "#000"},
            ],
            'palette': {
                'black and white': [ "tx-blc", "tx-wh"],
                'grey shades': ["tx-bw-1", "tx-bw-2", "tx-bw-3", "tx-bw-4", "tx-bw-5", "tx-bw-6", "tx-bw-7", "tx-bw-8"],
                'semi-transparent-black': ["tx-b-opc-1", "tx-b-opc-2", "tx-b-opc-3", "tx-b-opc-4", "tx-b-opc-5", "tx-b-opc-6", "tx-b-opc-7", "tx-b-opc-8"],
                'semi-transparent-white': ["tx-w-opc-1", "tx-w-opc-2", "tx-w-opc-3", "tx-w-opc-4", "tx-w-opc-5", "tx-w-opc-6", "tx-w-opc-7", "tx-w-opc-8"],
                'scheme': {
                    'monochrome': ["tx-brn-a", "tx-brn-a-1", "tx-brn-a-2", "tx-brn-a-8", "tx-brn-a-9"],
                    'complementary': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-b', 'tx-brn-b-1', 'tx-brn-b-2', 'tx-brn-b-8', 'tx-brn-b-9'],
                    'triadic': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-c', 'tx-brn-c-1', 'tx-brn-c-2', 'tx-brn-c-8', 'tx-brn-c-9', 'tx-brn-d', 'tx-brn-d-1', 'tx-brn-d-2', 'tx-brn-d-8', 'tx-brn-d-9'],
                    'tetradic': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-e', 'tx-brn-e-1', 'tx-brn-e-2', 'tx-brn-e-8', 'tx-brn-e-9'],
                    'separate-complementary': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-g', 'tx-brn-g-1', 'tx-brn-g-2', 'tx-brn-g-8', 'tx-brn-g-9', 'tx-brn-h', 'tx-brn-h-1', 'tx-brn-h-2', 'tx-brn-h-8', 'tx-brn-h-9'],
                    'analog': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-j', 'tx-brn-j-1', 'tx-brn-j-2', 'tx-brn-j-8', 'tx-brn-j-9', 'tx-brn-k', 'tx-brn-k-1', 'tx-brn-k-2', 'tx-brn-k-8', 'tx-brn-k-9'],
                }
            }
        },
        SvgColorGroup: {
            'type': 'svgColor',
            'icon': 'fa-circle',
            'values': [
                {name: "Default", value: "#000", type: 'self_color'},
            ],
            'palette': {
                'black and white': [ "tx-blc", "tx-wh"],
                'grey shades': ["tx-bw-1", "tx-bw-2", "tx-bw-3", "tx-bw-4", "tx-bw-5", "tx-bw-6", "tx-bw-7", "tx-bw-8"],
                'semi-transparent-black': ["tx-b-opc-1", "tx-b-opc-2", "tx-b-opc-3", "tx-b-opc-4", "tx-b-opc-5", "tx-b-opc-6", "tx-b-opc-7", "tx-b-opc-8"],
                'semi-transparent-white': ["tx-w-opc-1", "tx-w-opc-2", "tx-w-opc-3", "tx-w-opc-4", "tx-w-opc-5", "tx-w-opc-6", "tx-w-opc-7", "tx-w-opc-8"],
                'scheme': {
                    'monochrome': ["tx-brn-a", "tx-brn-a-1", "tx-brn-a-2", "tx-brn-a-8", "tx-brn-a-9"],
                    'complementary': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-b', 'tx-brn-b-1', 'tx-brn-b-2', 'tx-brn-b-8', 'tx-brn-b-9'],
                    'triadic': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-c', 'tx-brn-c-1', 'tx-brn-c-2', 'tx-brn-c-8', 'tx-brn-c-9', 'tx-brn-d', 'tx-brn-d-1', 'tx-brn-d-2', 'tx-brn-d-8', 'tx-brn-d-9'],
                    'tetradic': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-e', 'tx-brn-e-1', 'tx-brn-e-2', 'tx-brn-e-8', 'tx-brn-e-9'],
                    'separate-complementary': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-g', 'tx-brn-g-1', 'tx-brn-g-2', 'tx-brn-g-8', 'tx-brn-g-9', 'tx-brn-h', 'tx-brn-h-1', 'tx-brn-h-2', 'tx-brn-h-8', 'tx-brn-h-9'],
                    'analog': ['tx-brn-a', 'tx-brn-a-1', 'tx-brn-a-2', 'tx-brn-a-8', 'tx-brn-a-9', 'tx-brn-j', 'tx-brn-j-1', 'tx-brn-j-2', 'tx-brn-j-8', 'tx-brn-j-9', 'tx-brn-k', 'tx-brn-k-1', 'tx-brn-k-2', 'tx-brn-k-8', 'tx-brn-k-9'],
                }
            }
        },
        BackgroundColorGroup: {
            'type': 'background',
            'default_data_type': 'block_props',
            'default_prop': {name: "grey shades", value: "bg-bw-6", type: 'palette'},
            'icon': 'fa-circle',
            'values': [
                {name: "Not set", value: "", type: 'palette', css: "#FFFFFF", layer: 0, gradient: {type: 'linear-gradient', degree: '90', stops: [{color: "#FFFFFF00", stop: "0"}, {color: "#FFFFFF00", stop: "100"}]}}
            ],
            'palette': {
                'black and white': [ "bg-blc", "bg-wh"],
                'grey shades': ["bg-bw-1", "bg-bw-2", "bg-bw-3", "bg-bw-4", "bg-bw-5", "bg-bw-6", "bg-bw-7", "bg-bw-8"],
                'semi-transparent-black': ["bg-b-opc-1", "bg-b-opc-2", "bg-b-opc-3", "bg-b-opc-4", "bg-b-opc-5", "bg-b-opc-6", "bg-b-opc-7", "bg-b-opc-8"],
                'semi-transparent-white': ["bg-w-opc-1", "bg-w-opc-2", "bg-w-opc-3", "bg-w-opc-4", "bg-w-opc-5", "bg-w-opc-6", "bg-w-opc-7", "bg-w-opc-8"],
                'scheme': {
                    'monochromatic': ["bg-brn-a", "bg-brn-a-1", "bg-brn-a-2", "bg-brn-a-8", "bg-brn-a-9"],
                    'complementary': ['bg-brn-a', 'bg-brn-a-1', 'bg-brn-a-2', 'bg-brn-a-8', 'bg-brn-a-9', 'bg-brn-b', 'bg-brn-b-1', 'bg-brn-b-2', 'bg-brn-b-8', 'bg-brn-b-9'],
                    'triadic': ['bg-brn-a', 'bg-brn-a-1', 'bg-brn-a-2', 'bg-brn-a-8', 'bg-brn-a-9', 'bg-brn-c', 'bg-brn-c-1', 'bg-brn-c-2', 'bg-brn-c-8', 'bg-brn-c-9', 'bg-brn-d', 'bg-brn-d-1', 'bg-brn-d-2', 'bg-brn-d-8', 'bg-brn-d-9'],
                    'tetradic': ['bg-brn-a', 'bg-brn-a-1', 'bg-brn-a-2', 'bg-brn-a-8', 'bg-brn-a-9', 'bg-brn-e', 'bg-brn-e-1', 'bg-brn-e-2', 'bg-brn-e-8', 'bg-brn-e-9'],
                    'separate-complementary': ['bg-brn-a', 'bg-brn-a-1', 'bg-brn-a-2', 'bg-brn-a-8', 'bg-brn-a-9', 'bg-brn-g', 'bg-brn-g-1', 'bg-brn-g-2', 'bg-brn-g-8', 'bg-brn-g-9', 'bg-brn-h', 'bg-brn-h-1', 'bg-brn-h-2', 'bg-brn-h-8', 'bg-brn-h-9'],
                    'analogous': ['bg-brn-a', 'bg-brn-a-1', 'bg-brn-a-2', 'bg-brn-a-8', 'bg-brn-a-9', 'bg-brn-j', 'bg-brn-j-1', 'bg-brn-j-2', 'bg-brn-j-8', 'bg-brn-j-9', 'bg-brn-k', 'bg-brn-k-1', 'bg-brn-k-2', 'bg-brn-k-8', 'bg-brn-k-9'],
                }
            }
        },
        BorderColorGroup: {
            'type': 'border-color',
            'default_data_type': 'block_props',
            'default_prop': {name: "grey shades", value: "bg-bw-6", type: 'palette'},
            'icon': 'fa-circle',
            'values': [
                {name: "1-1", value: "bd-tr-1", type: 'palette', css: "#0000001a"}
            ],
            'palette': {
                'black and white': [ "br-blc", "br-wh"],
                'grey shades': ["br-bw-1", "br-bw-2", "br-bw-3", "br-bw-4", "br-bw-5", "br-bw-6", "br-bw-7", "br-bw-8"],
                'semi-transparent-black': ["br-b-opc-1", "br-b-opc-2", "br-b-opc-3", "br-b-opc-4", "br-b-opc-5", "br-b-opc-6", "br-b-opc-7", "br-b-opc-8"],
                'semi-transparent-white': ["br-w-opc-1", "br-w-opc-2", "br-w-opc-3", "br-w-opc-4", "br-w-opc-5", "br-w-opc-6", "br-w-opc-7", "br-w-opc-8"],
                'scheme': {
                    'monochrome': ["br-brn-a", "br-brn-a-1", "br-brn-a-2", "br-brn-a-8", "br-brn-a-9"],
                    'complementary': ['br-brn-a', 'br-brn-a-1', 'br-brn-a-2', 'br-brn-a-8', 'br-brn-a-9', 'br-brn-b', 'br-brn-b-1', 'br-brn-b-2', 'br-brn-b-8', 'br-brn-b-9'],
                    'triadic': ['br-brn-a', 'br-brn-a-1', 'br-brn-a-2', 'br-brn-a-8', 'br-brn-a-9', 'br-brn-c', 'br-brn-c-1', 'br-brn-c-2', 'br-brn-c-8', 'br-brn-c-9', 'br-brn-d', 'br-brn-d-1', 'br-brn-d-2', 'br-brn-d-8', 'br-brn-d-9'],
                    'tetradic': ['br-brn-a', 'br-brn-a-1', 'br-brn-a-2', 'br-brn-a-8', 'br-brn-a-9', 'br-brn-e', 'br-brn-e-1', 'br-brn-e-2', 'br-brn-e-8', 'br-brn-e-9'],
                    'separate-complementary': ['br-brn-a', 'br-brn-a-1', 'br-brn-a-2', 'br-brn-a-8', 'br-brn-a-9', 'br-brn-g', 'br-brn-g-1', 'br-brn-g-2', 'br-brn-g-8', 'br-brn-g-9', 'br-brn-h', 'br-brn-h-1', 'br-brn-h-2', 'br-brn-h-8', 'br-brn-h-9'],
                    'analog': ['br-brn-a', 'br-brn-a-1', 'br-brn-a-2', 'br-brn-a-8', 'br-brn-a-9', 'br-brn-j', 'br-brn-j-1', 'br-brn-j-2', 'br-brn-j-8', 'br-brn-j-9', 'br-brn-k', 'br-brn-k-1', 'br-brn-k-2', 'br-brn-k-8', 'br-brn-k-9'],
                }
            }
        },
        BorderWidthGroup: {
            'type': 'border-width',
            'default_data_type': 'block_props',
            'icon': 'fa-circle',
            'values': [
                {name: "Толщина 3", value: 'b-w-l ', unit: 'px', type: 'library'}
            ],
            'library': [
                {name: "Толщина 3", value: 'b-w-l', unit: 'px', type: 'library'},
                {name: "Толщина 2", value: 'b-w-m', unit: 'px', type: 'library'},
                {name: "Толщина 1", value: 'b-w-s', unit: 'px', type: 'library'},
            ]
        },
        BorderStyleGroup: {
            'type': 'border-style',
            'default_data_type': 'block_props',
            'icon': 'fa-circle',
            'values': [
                {value: 'b-d-a', type: "all"}
            ],
            'separate': [
                {name: "Border top",  value: "b-d-t", icon: "fa-arrow-up"},
                {name: "Border right", value: "b-d-r", icon: "fa-arrow-right"},
                {name: "Border bottom",  value: "b-d-b", icon: "fa-arrow-down"},
                {name: "Border left", value: "b-d-l", icon: "fa-arrow-left"},
            ],
        },
        BorderRadiusGroup: {
            'type': 'border-radius',
            'values': [
                {name: "Without radius",  value: ""},
                {name: "Radius S",  value: "b-r-s"},
                {name: "Radius M", value: "b-r-m"},
                {name: "Radius L", value: "b-r-l"},
                {name: "Full radius", value: "b-r-r"},
            ],
        },
        BorderRadiusCornersGroup: {
            'type': 'border-radius-corners',
            'values': [
                {value: '', type: "all"}
            ],
            'separate': [
                {name: "Top-left",  value: "b-r-u-tl", icon: "fa-angle-up", angle: -45},
                {name: "Top-right", value: "b-r-u-tr", icon: "fa-angle-up", angle: 45},
                {name: "Bottom-left",  value: "b-r-u-bl", icon: "fa-angle-up", angle: -135},
                {name: "Bottom-right", value: "b-r-u-br", icon: "fa-angle-up", angle: 135},
            ],
        },
        ShadowsGroup: {
            'type': 'box-shadow',
            'default_data_type': 'block_props',
            'icon': 'fa-circle',
            'values': [
                //{name: "Shadow 3", value: "sh-3", type: 'palette', css: "#00000005"}
                {name: "Not set", value: "", type: 'palette', css: "#000000", offset: { "xaxis": 0, "yaxis": 0, "blur": 0, "spread": 0 }, units: { "xaxis": 'px', "yaxis": 'px', "blur": 'px', "spread": 'px' }}
            ],
            'palette': [
                {name: "Shadow 1", value: "sh-1", type: 'palette', css: "#00000005"},
                {name: "Shadow 2", value: "sh-2", type: 'palette', css: "#00000005"},
                {name: "Shadow 3", value: "sh-3", type: 'palette', css: "#00000005"},
                {name: "Shadow 4", value: "sh-4", type: 'palette', css: "#00000005"}
            ]
        },
        textShadowsGroup: {
            'type': 'text-shadow',
            'default_data_type': 'block_props',
            'icon': 'fa-circle',
            'values': [
                //{name: "Shadow 3", value: "sh-3", type: 'palette', css: "#00000005"}
                {name: "Not set", value: "", type: 'palette', css: "#000000", offset: { "xaxis": 0, "yaxis": 0, "blur": 0 }, units: { "xaxis": 'px', "yaxis": 'px', "blur": 'px' }}
            ],
            'palette': [
                {name: "Shadow 1", value: "sh-txt-1", type: 'palette', css: "#00000005"},
                {name: "Shadow 2", value: "sh-txt-2", type: 'palette', css: "#00000005"},
                {name: "Shadow 3", value: "sh-txt-3", type: 'palette', css: "#00000005"},
                {name: "Shadow 4", value: "sh-txt-4", type: 'palette', css: "#00000005"}
            ]
        },
        ButtonSizeGroup: {
            'type': 'button-size',
            'values': [
                {name: "XS",  value: "inp-xs p-l-11 p-r-11", value_class: "inp-xs p-l-11 p-r-11"},
                {name: "S",  value: "inp-s p-l-12 p-r-12", value_class: "inp-s p-l-12 p-r-12"},
                {name: "M",  value: "inp-m p-l-13 p-r-13", value_class: "inp-m p-l-13 p-r-13"},
                {name: "L",  value: "inp-l p-l-14 p-r-14", value_class: "inp-l p-l-14 p-r-14"},
                {name: "XL",  value: "inp-xl p-l-15 p-r-15", value_class: "inp-xl p-l-15 p-r-15"},
            ]
        },
        ButtonStyleGroup: {
            'type': 'button-style',
            'default_data_type': 'block_props',
            'default_prop': {name: "scheme", value: "btn-a", type: 'palette'},
            'icon': 'fa-circle',
            'values': [
                {name: "complementary", scheme: 'complementary', value: "btn-a", type: 'palette', css: "#0000001a"}
            ],
            /*'values': [
                {name: "Main",  value: "main-set", value_class: "bg-brn-1 t-wht b-r-r"},
                {name: "Secondary",  value: "second-set", value_class: "bg-tr t-brn-1 b-r-r bd-brn-1 b-w-s"},
            ],*/
            'palette': {
                'black colors': ["btn-blc", "btn-blc-strk", "btn-blc-trnsp", "btn-blc-lnk"],
                'white colors': ["btn-wht", "btn-wht-strk", "btn-wht-trnsp", "btn-wht-lnk"],
                'scheme': {
                    'monochrome': ["btn-a", "btn-a-strk", "btn-a-trnsp", "btn-a-wht", "btn-a-lnk"],
                    'complementary': ["btn-a", "btn-a-strk", "btn-a-trnsp", "btn-a-wht", "btn-a-lnk", 'btn-b', 'btn-b-strk', 'btn-b-trnsp', 'btn-b-wht', "btn-b-lnk"],
                    'triadic': ["btn-a",'btn-a-strk', 'btn-a-trnsp', 'btn-a-wht', "btn-a-lnk", 'btn-c', 'btn-c-strk', 'btn-c-trnsp', 'btn-c-wht', "btn-c-lnk", 'btn-d', 'btn-d-strk', 'btn-d-trnsp', 'btn-d-wht', "btn-d-lnk"],
                    'tetradic': ["btn-a",'btn-a-strk', 'btn-a-trnsp', 'btn-a-wht', "btn-a-lnk", 'btn-e', 'btn-e-strk', 'btn-e-trnsp', 'btn-e-wht', "btn-e-lnk"],
                    'separate-complementary': ["btn-a",'btn-a-strk', 'btn-a-trnsp', 'btn-a-wht', "btn-a-lnk", 'btn-g', 'btn-g-strk', 'btn-g-trnsp', 'btn-g-wht', "btn-g-lnk", 'btn-h', 'btn-h-strk', 'btn-h-trnsp', 'btn-h-wht', "btn-h-lnk"],
                    'analog': ["btn-a",'btn-a-strk', 'btn-a-trnsp', 'btn-a-wht', "btn-a-lnk", 'btn-j', 'btn-j-strk', 'btn-j-trnsp', 'btn-j-wht', "btn-j-lnk", 'btn-k', 'btn-k-strk', 'btn-k-trnsp', 'btn-k-wht', "btn-k-lnk"],
                }
            }
        },
        AlignGroup: {
            'type': 'align',
            'values': [
                {name: "Align-left", value: "t-l", icon: "fa-align-left"},
                {name: "Align-center", value: "t-c", icon: "fa-align-center"},
                {name: "Align-justify", value: "t-j", icon: "fa-align-justify"},
                {name: "Align-right",  value: "t-r", icon: "fa-align-right"},
            ]
        },
        ColumnsGroup: {
            'type': 'column-width',
            'icons': {desktop: "fa-desktop", laptop: 'fa-laptop', tablet: 'fa-tablet-alt', mobile: 'fa-mobile-alt'},
            'values': [
                {name: "12/12 (100%)", value: 'st-12', value_laptop: 'st-12-lp', value_tablet: 'st-12-tb', value_mobile: 'st-12-mb'},
                {name: "11/12 (91.7%)", value: 'st-11', value_laptop: 'st-11-lp', value_tablet: 'st-11-tb', value_mobile: 'st-11-mb'},
                {name: "10/12 (83.3%)", value: 'st-10', value_laptop: 'st-10-lp', value_tablet: 'st-10-tb', value_mobile: 'st-10-mb'},
                {name: "9/12 (75%)", value: 'st-9', value_laptop: 'st-9-lp', value_tablet: 'st-9-tb', value_mobile: 'st-9-mb'},
                {name: "8/12 (66.7%)", value: 'st-8', value_laptop: 'st-8-lp', value_tablet: 'st-8-tb', value_mobile: 'st-8-mb'},
                {name: "7/12 (58.3%)", value: 'st-7', value_laptop: 'st-7-lp', value_tablet: 'st-7-tb', value_mobile: 'st-7-mb'},
                {name: "6/12 (50%)", value: 'st-6', value_laptop: 'st-6-lp', value_tablet: 'st-6-tb', value_mobile: 'st-6-mb'},
                {name: "5/12 (41.7%)", value: 'st-5', value_laptop: 'st-5-lp', value_tablet: 'st-5-tb', value_mobile: 'st-5-mb'},
                {name: "4/12 (33.3%)", value: 'st-4', value_laptop: 'st-4-lp', value_tablet: 'st-4-tb', value_mobile: 'st-4-mb'},
                {name: "3/12 (25%)", value: 'st-3', value_laptop: 'st-3-lp', value_tablet: 'st-3-tb', value_mobile: 'st-3-mb'},
                {name: "2/12 (16.7%)", value: 'st-2', value_laptop: 'st-2-lp', value_tablet: 'st-2-tb', value_mobile: 'st-2-mb'},
                {name: "1/12 (8.3%)", value: 'st-1', value_laptop: 'st-1-lp', value_tablet: 'st-1-tb', value_mobile: 'st-1-mb'},
                {name: "0/12 (0%)", value: 'st-0', value_laptop: 'st-0-lp', value_tablet: 'st-0-tb', value_mobile: 'st-0-mb'},
                /*{name: "Content", value: 'hg', value_laptop: 'hg-lp', value_tablet: 'hg-tb', value_mobile: 'hg-mb'},
                {name: "Fill", value: 'fl', value_laptop: 'fl-lp', value_tablet: 'fl-tb', value_mobile: 'fl-mb'},*/
            ]
        },
        ColumnWidthGroup: {
            'type': 'column-max-width',
            'icons': {desktop: "fa-desktop", laptop: 'fa-laptop', tablet: 'fa-tablet-alt', mobile: 'fa-mobile-alt'},
            'values': [
                {name: "Default", value: 'removeData', value_laptop: 'removeData', value_tablet: 'removeData', value_mobile: 'removeData'},
                {name: "12/12", value: 'fx-12', value_laptop: 'fx-12-lp', value_tablet: 'fx-12-tb', value_mobile: 'fx-12-mb'},
                {name: "11/12", value: 'fx-11', value_laptop: 'fx-11-lp', value_tablet: 'fx-11-tb', value_mobile: 'fx-11-mb'},
                {name: "10/12", value: 'fx-10', value_laptop: 'fx-10-lp', value_tablet: 'fx-10-tb', value_mobile: 'fx-10-mb'},
                {name: "9/12", value: 'fx-9', value_laptop: 'fx-9-lp', value_tablet: 'fx-9-tb', value_mobile: 'fx-9-mb'},
                {name: "8/12", value: 'fx-8', value_laptop: 'fx-8-lp', value_tablet: 'fx-8-tb', value_mobile: 'fx-8-mb'},
                {name: "7/12", value: 'fx-7', value_laptop: 'fx-7-lp', value_tablet: 'fx-7-tb', value_mobile: 'fx-7-mb'},
                {name: "6/12", value: 'fx-6', value_laptop: 'fx-6-lp', value_tablet: 'fx-6-tb', value_mobile: 'fx-6-mb'},
                {name: "5/12", value: 'fx-5', value_laptop: 'fx-5-lp', value_tablet: 'fx-5-tb', value_mobile: 'fx-5-mb'},
                {name: "4/12", value: 'fx-4', value_laptop: 'fx-4-lp', value_tablet: 'fx-4-tb', value_mobile: 'fx-4-mb'},
                {name: "3/12", value: 'fx-3', value_laptop: 'fx-3-lp', value_tablet: 'fx-3-tb', value_mobile: 'fx-3-mb'},
                {name: "2/12", value: 'fx-2', value_laptop: 'fx-2-lp', value_tablet: 'fx-2-tb', value_mobile: 'fx-2-mb'},
                {name: "1/12", value: 'fx-1', value_laptop: 'fx-1-lp', value_tablet: 'fx-1-tb', value_mobile: 'fx-1-mb'},
                {name: "0/12", value: 'fx-0', value_laptop: 'fx-0-lp', value_tablet: 'fx-0-tb', value_mobile: 'fx-0-mb'},
            ]
        },
        RowsWrapGroup: {
            'type': 'flex-wrap',
            'icons': {desktop: "fa-desktop", laptop: 'fa-laptop', tablet: 'fa-tablet-alt', mobile: 'fa-mobile-alt'},
            'values': [
                {name: "nowrap", value: 'n-wr-ds', value_laptop: 'n-wr-lp', value_tablet: 'n-wr-tb', value_mobile: 'n-wr-mb'},
            ]
        },
        RowsAttrsVisibilityGroup: {
            'type': 'attrs-visibility',
            'values': [
                {name: "SKU code", value: 1, key: 'sku' },
                {name: "Price", value: 1, key: 'price' },
            ]
        },
        MenuDecorationGroup: {
            'type': 'scroll-fix',
            'values': [
                {name: "Fix it when scrolling", value: "psn-stk", key: "activeStk"},
                {name: "Against the background of the next block", value: "psn-abs-tl f-w", key: "activeAbs"},
                {name: "Both", value: "psn-fxd-tl f-w", key: "activeBoth"},
            ]
        },
        MaxWidthToggleGroup: {
            'type': 'max-width',
            'values': [
                {name: "Width is limited", value: "cnt"},
            ]
        },
        FullWidthToggleGroup: {
            'type': 'full-width',
            'values': [
                {name: "Occupy the entire available width", value: "f-w"},
            ]
        },
        ColumnsAlignGroup: {
            'type': 'flex-align',
            'values': [
                {name: "Align-left", value: "y-l", icon: "fa-align-left"},
                {name: "Align-center", value: "y-c", icon: "fa-align-center"},
                {name: "Align-right",  value: "y-r", icon: "fa-align-right"},
            ]
        },
        ProductInfoGroup: {
            'type': 'info_type',
            'values': [
                {name: "Name", value: "name", icon: "fa-align-center"},
                {name: "Summary", value: "summary", icon: "fa-align-left"},
                {name: "Description", value: "description", icon: "fa-align-left"},
                {name: "Price", value: "price", icon: "fa-align-left"},
                {name: "Old price", value: "compare_price", icon: "fa-align-left"},
                {name: "Stock", value: "stock", icon: "fa-align-left"},
            ]
        },
        ProductPictureGroup: {
            'type': 'picture_type',
            'values': [
                {name: "Big", value: "url_big", icon: "fa-align-center"},
                {name: "Crop", value: "url_crop", icon: "fa-align-left"},
                {name: "Thumb", value: "url_thumb", icon: "fa-align-left"},
            ]
        },
        ProductSkuElementLayoutGroup: {
            'type': 'element_layout',
            'values': [
                {name: "product_widget_element_layout_line", value: "line"},
                {name: "product_widget_element_layout_sku_above", value: "sku_above"},
            ]
        },
        ColumnsAlignVerticalGroup: {
            'type': 'flex-align-vertical',
            'values': [
                {name: "Align-top", value: "x-t", icon: "fa-align-top"},
                {name: "Align-center", value: "x-c", icon: "fa-align-center"},
                {name: "Align-bottom",  value: "x-b", icon: "fa-align-down"},
            ]
        },
        ColumnAlignVerticalGroup: {
            'type': 'flex-align-vertical',
            'values': [
                {name: "Align-top", value: "a-c-s", icon: "fa-align-top"},
                {name: "Align-center", value: "a-c-c", icon: "fa-align-center"},
                {name: "Align-bottom",  value: "a-c-e", icon: "fa-align-down"},
            ]
        },
        RowsAlignGroup: {
            'type': 'justify-align',
            'values': [
                {name: "Align-center", value: "y-j-cnt", icon: "fa-align-center"},
                {name: "Align-left", value: "j-s", icon: "fa-align-left"},
                {name: "Align-right",  value: "j-end", icon: "fa-align-right"},
            ]
        },
        VisibilityGroup: {
            'type': 'visibility',
            'values': [
                {name: "Mobile visibility",  value: "d-n-mb", icon: "fa-mobile-alt"},
                {name: "Tablet visibility",  value: "d-n-tb", icon: "fa-tablet-alt"},
                {name: "Laptop visibility", value: "d-n-lp", icon: "fa-laptop"},
                {name: "Desktop visibility", value: "d-n-ds", icon: "fa-desktop"},
            ]
        },
        ImageUploadGroup: {
            'type': 'image-upload',
        },
        PaddingGroup: [
            {
                'type': 'padding-top',
                'icon': 'fa-arrow-up',
                'values': [
                    {name: "0px", value: "p-t-0", value_laptop: "p-t-0-lp", value_tablet: "p-t-0-tb", value_mobile: "p-t-0-mb"},
                    {name: "1px", value: "p-t-1", value_laptop: "p-t-1-lp", value_tablet: "p-t-1-tb", value_mobile: "p-t-1-mb"},
                    {name: "2px", value: "p-t-2", value_laptop: "p-t-2-lp", value_tablet: "p-t-2-tb", value_mobile: "p-t-2-mb"},
                    {name: "3px", value: "p-t-3", value_laptop: "p-t-3-lp", value_tablet: "p-t-3-tb", value_mobile: "p-t-3-mb"},
                    {name: "4px", value: "p-t-4", value_laptop: "p-t-4-lp", value_tablet: "p-t-4-tb", value_mobile: "p-t-4-mb"},
                    {name: "5px", value: "p-t-5", value_laptop: "p-t-5-lp", value_tablet: "p-t-5-tb", value_mobile: "p-t-5-mb"},
                    {name: "6px", value: "p-t-6", value_laptop: "p-t-6-lp", value_tablet: "p-t-6-tb", value_mobile: "p-t-6-mb"},
                    {name: "7px", value: "p-t-7", value_laptop: "p-t-7-lp", value_tablet: "p-t-7-tb", value_mobile: "p-t-7-mb"},
                    {name: "8px", value: "p-t-8", value_laptop: "p-t-8-lp", value_tablet: "p-t-8-tb", value_mobile: "p-t-8-mb"},
                    {name: "10px", value: "p-t-9", value_laptop: "p-t-9-lp", value_tablet: "p-t-9-tb", value_mobile: "p-t-9-mb"},
                    {name: "12px", value: "p-t-10", value_laptop: "p-t-10-lp", value_tablet: "p-t-10-tb", value_mobile: "p-t-10-mb"},
                    {name: "14px", value: "p-t-11", value_laptop: "p-t-11-lp", value_tablet: "p-t-11-tb", value_mobile: "p-t-11-mb"},
                    {name: "16px", value: "p-t-12", value_laptop: "p-t-12-lp", value_tablet: "p-t-12-tb", value_mobile: "p-t-12-mb"},
                    {name: "18px", value: "p-t-13", value_laptop: "p-t-13-lp", value_tablet: "p-t-13-tb", value_mobile: "p-t-13-mb"},
                    {name: "20px", value: "p-t-14", value_laptop: "p-t-14-lp", value_tablet: "p-t-14-tb", value_mobile: "p-t-14-mb"},
                    {name: "22px", value: "p-t-15", value_laptop: "p-t-15-lp", value_tablet: "p-t-15-tb", value_mobile: "p-t-15-mb"},
                    {name: "24px", value: "p-t-16", value_laptop: "p-t-16-lp", value_tablet: "p-t-16-tb", value_mobile: "p-t-16-mb"},
                    {name: "28px", value: "p-t-17", value_laptop: "p-t-17-lp", value_tablet: "p-t-17-tb", value_mobile: "p-t-17-mb"},
                    {name: "32px", value: "p-t-18", value_laptop: "p-t-18-lp", value_tablet: "p-t-18-tb", value_mobile: "p-t-18-mb"},
                    {name: "36px", value: "p-t-19", value_laptop: "p-t-19-lp", value_tablet: "p-t-19-tb", value_mobile: "p-t-19-mb"},
                    {name: "40px", value: "p-t-20", value_laptop: "p-t-20-lp", value_tablet: "p-t-20-tb", value_mobile: "p-t-20-mb"},
                    {name: "48px", value: "p-t-21", value_laptop: "p-t-21-lp", value_tablet: "p-t-21-tb", value_mobile: "p-t-21-mb"},
                    {name: "56px", value: "p-t-22", value_laptop: "p-t-22-lp", value_tablet: "p-t-22-tb", value_mobile: "p-t-22-mb"},
                    {name: "64px", value: "p-t-23", value_laptop: "p-t-23-lp", value_tablet: "p-t-23-tb", value_mobile: "p-t-23-mb"},
                    {name: "80px", value: "p-t-24", value_laptop: "p-t-24-lp", value_tablet: "p-t-24-tb", value_mobile: "p-t-24-mb"},
                    {name: "96px", value: "p-t-25", value_laptop: "p-t-25-lp", value_tablet: "p-t-25-tb", value_mobile: "p-t-25-mb"},
                    {name: "112px", value: "p-t-26", value_laptop: "p-t-26-lp", value_tablet: "p-t-26-tb", value_mobile: "p-t-26-mb"},
                    {name: "128px", value: "p-t-27", value_laptop: "p-t-27-lp", value_tablet: "p-t-27-tb", value_mobile: "p-t-27-mb"},
                    {name: "144px", value: "p-t-28", value_laptop: "p-t-28-lp", value_tablet: "p-t-28-tb", value_mobile: "p-t-28-mb"},
                    {name: "168px", value: "p-t-29", value_laptop: "p-t-29-lp", value_tablet: "p-t-29-tb", value_mobile: "p-t-29-mb"},
                    {name: "192px", value: "p-t-30", value_laptop: "p-t-30-lp", value_tablet: "p-t-30-tb", value_mobile: "p-t-30-mb"},
                ]
            },
            {
                'type': 'padding-bottom',
                'icon': 'fa-arrow-down',
                'values': [
                    {name: "0px", value: "p-b-0", value_laptop: "p-b-0-lp", value_tablet: "p-b-0-tb", value_mobile: "p-b-0-mb"},
                    {name: "1px", value: "p-b-1", value_laptop: "p-b-1-lp", value_tablet: "p-b-1-tb", value_mobile: "p-b-1-mb"},
                    {name: "2px", value: "p-b-2", value_laptop: "p-b-2-lp", value_tablet: "p-b-2-tb", value_mobile: "p-b-2-mb"},
                    {name: "3px", value: "p-b-3", value_laptop: "p-b-3-lp", value_tablet: "p-b-3-tb", value_mobile: "p-b-3-mb"},
                    {name: "4px", value: "p-b-4", value_laptop: "p-b-4-lp", value_tablet: "p-b-4-tb", value_mobile: "p-b-4-mb"},
                    {name: "5px", value: "p-b-5", value_laptop: "p-b-5-lp", value_tablet: "p-b-5-tb", value_mobile: "p-b-5-mb"},
                    {name: "6px", value: "p-b-6", value_laptop: "p-b-6-lp", value_tablet: "p-b-6-tb", value_mobile: "p-b-6-mb"},
                    {name: "7px", value: "p-b-7", value_laptop: "p-b-7-lp", value_tablet: "p-b-7-tb", value_mobile: "p-b-7-mb"},
                    {name: "8px", value: "p-b-8", value_laptop: "p-b-8-lp", value_tablet: "p-b-8-tb", value_mobile: "p-b-8-mb"},
                    {name: "10px", value: "p-b-9", value_laptop: "p-b-9-lp", value_tablet: "p-b-9-tb", value_mobile: "p-b-9-mb"},
                    {name: "12px", value: "p-b-10", value_laptop: "p-b-10-lp", value_tablet: "p-b-10-tb", value_mobile: "p-b-10-mb"},
                    {name: "14px", value: "p-b-11", value_laptop: "p-b-11-lp", value_tablet: "p-b-11-tb", value_mobile: "p-b-11-mb"},
                    {name: "16px", value: "p-b-12", value_laptop: "p-b-12-lp", value_tablet: "p-b-12-tb", value_mobile: "p-b-12-mb"},
                    {name: "18px", value: "p-b-13", value_laptop: "p-b-13-lp", value_tablet: "p-b-13-tb", value_mobile: "p-b-13-mb"},
                    {name: "20px", value: "p-b-14", value_laptop: "p-b-14-lp", value_tablet: "p-b-14-tb", value_mobile: "p-b-14-mb"},
                    {name: "22px", value: "p-b-15", value_laptop: "p-b-15-lp", value_tablet: "p-b-15-tb", value_mobile: "p-b-15-mb"},
                    {name: "24px", value: "p-b-16", value_laptop: "p-b-16-lp", value_tablet: "p-b-16-tb", value_mobile: "p-b-16-mb"},
                    {name: "28px", value: "p-b-17", value_laptop: "p-b-17-lp", value_tablet: "p-b-17-tb", value_mobile: "p-b-17-mb"},
                    {name: "32px", value: "p-b-18", value_laptop: "p-b-18-lp", value_tablet: "p-b-18-tb", value_mobile: "p-b-18-mb"},
                    {name: "36px", value: "p-b-19", value_laptop: "p-b-19-lp", value_tablet: "p-b-19-tb", value_mobile: "p-b-19-mb"},
                    {name: "40px", value: "p-b-20", value_laptop: "p-b-20-lp", value_tablet: "p-b-20-tb", value_mobile: "p-b-20-mb"},
                    {name: "48px", value: "p-b-21", value_laptop: "p-b-21-lp", value_tablet: "p-b-21-tb", value_mobile: "p-b-21-mb"},
                    {name: "56px", value: "p-b-22", value_laptop: "p-b-22-lp", value_tablet: "p-b-22-tb", value_mobile: "p-b-22-mb"},
                    {name: "64px", value: "p-b-23", value_laptop: "p-b-23-lp", value_tablet: "p-b-23-tb", value_mobile: "p-b-23-mb"},
                    {name: "80px", value: "p-b-24", value_laptop: "p-b-24-lp", value_tablet: "p-b-24-tb", value_mobile: "p-b-24-mb"},
                    {name: "96px", value: "p-b-25", value_laptop: "p-b-25-lp", value_tablet: "p-b-25-tb", value_mobile: "p-b-25-mb"},
                    {name: "112px", value: "p-b-26", value_laptop: "p-b-26-lp", value_tablet: "p-b-26-tb", value_mobile: "p-b-26-mb"},
                    {name: "128px", value: "p-b-27", value_laptop: "p-b-27-lp", value_tablet: "p-b-27-tb", value_mobile: "p-b-27-mb"},
                    {name: "144px", value: "p-b-28", value_laptop: "p-b-28-lp", value_tablet: "p-b-28-tb", value_mobile: "p-b-28-mb"},
                    {name: "168px", value: "p-b-29", value_laptop: "p-b-29-lp", value_tablet: "p-b-29-tb", value_mobile: "p-b-29-mb"},
                    {name: "192px", value: "p-b-30", value_laptop: "p-b-30-lp", value_tablet: "p-b-30-tb", value_mobile: "p-b-30-mb"},
                ]
            },
            {
                'type': 'padding-left',
                'icon': 'fa-arrow-left',
                'values': [
                    {name: "Default for columns", value: "p-l-clm"},
                    {name: "Default for blocks", value: "p-l-blc"},
                    {name: "Default for blocks + columns", value: "p-l-blc-clm"},
                    {name: "0px", value: "p-l-0", value_laptop: "p-l-0-lp", value_tablet: "p-l-0-tb", value_mobile: "p-l-0-mb"},
                    {name: "1px", value: "p-l-1", value_laptop: "p-l-1-lp", value_tablet: "p-l-1-tb", value_mobile: "p-l-1-mb"},
                    {name: "2px", value: "p-l-2", value_laptop: "p-l-2-lp", value_tablet: "p-l-2-tb", value_mobile: "p-l-2-mb"},
                    {name: "3px", value: "p-l-3", value_laptop: "p-l-3-lp", value_tablet: "p-l-3-tb", value_mobile: "p-l-3-mb"},
                    {name: "4px", value: "p-l-4", value_laptop: "p-l-4-lp", value_tablet: "p-l-4-tb", value_mobile: "p-l-4-mb"},
                    {name: "5px", value: "p-l-5", value_laptop: "p-l-5-lp", value_tablet: "p-l-5-tb", value_mobile: "p-l-5-mb"},
                    {name: "6px", value: "p-l-6", value_laptop: "p-l-6-lp", value_tablet: "p-l-6-tb", value_mobile: "p-l-6-mb"},
                    {name: "7px", value: "p-l-7", value_laptop: "p-l-7-lp", value_tablet: "p-l-7-tb", value_mobile: "p-l-7-mb"},
                    {name: "8px", value: "p-l-8", value_laptop: "p-l-8-lp", value_tablet: "p-l-8-tb", value_mobile: "p-l-8-mb"},
                    {name: "10px", value: "p-l-9", value_laptop: "p-l-9-lp", value_tablet: "p-l-9-tb", value_mobile: "p-l-9-mb"},
                    {name: "12px", value: "p-l-10", value_laptop: "p-l-10-lp", value_tablet: "p-l-10-tb", value_mobile: "p-l-10-mb"},
                    {name: "14px", value: "p-l-11", value_laptop: "p-l-11-lp", value_tablet: "p-l-11-tb", value_mobile: "p-l-11-mb"},
                    {name: "16px", value: "p-l-12", value_laptop: "p-l-12-lp", value_tablet: "p-l-12-tb", value_mobile: "p-l-12-mb"},
                    {name: "18px", value: "p-l-13", value_laptop: "p-l-13-lp", value_tablet: "p-l-13-tb", value_mobile: "p-l-13-mb"},
                    {name: "20px", value: "p-l-14", value_laptop: "p-l-14-lp", value_tablet: "p-l-14-tb", value_mobile: "p-l-14-mb"},
                    {name: "22px", value: "p-l-15", value_laptop: "p-l-15-lp", value_tablet: "p-l-15-tb", value_mobile: "p-l-15-mb"},
                    {name: "24px", value: "p-l-16", value_laptop: "p-l-16-lp", value_tablet: "p-l-16-tb", value_mobile: "p-l-16-mb"},
                    {name: "28px", value: "p-l-17", value_laptop: "p-l-17-lp", value_tablet: "p-l-17-tb", value_mobile: "p-l-17-mb"},
                    {name: "32px", value: "p-l-18", value_laptop: "p-l-18-lp", value_tablet: "p-l-18-tb", value_mobile: "p-l-18-mb"},
                    {name: "36px", value: "p-l-19", value_laptop: "p-l-19-lp", value_tablet: "p-l-19-tb", value_mobile: "p-l-19-mb"},
                ]
            },
            {
                'type': 'padding-right',
                'icon': 'fa-arrow-right',
                'values': [
                    {name: "Default for columns", value: "p-r-clm", variable: 1},
                    {name: "Default for blocks", value: "p-r-blc", variable: 1},
                    {name: "Default for blocks + columns", value: "p-r-blc-clm", variable: 1},
                    {name: "0px", value: "p-r-0", value_laptop: "p-r-0-lp", value_tablet: "p-r-0-tb", value_mobile: "p-r-0-mb"},
                    {name: "1px", value: "p-r-1", value_laptop: "p-r-1-lp", value_tablet: "p-r-1-tb", value_mobile: "p-r-1-mb"},
                    {name: "2px", value: "p-r-2", value_laptop: "p-r-2-lp", value_tablet: "p-r-2-tb", value_mobile: "p-r-2-mb"},
                    {name: "3px", value: "p-r-3", value_laptop: "p-r-3-lp", value_tablet: "p-r-3-tb", value_mobile: "p-r-3-mb"},
                    {name: "4px", value: "p-r-4", value_laptop: "p-r-4-lp", value_tablet: "p-r-4-tb", value_mobile: "p-r-4-mb"},
                    {name: "5px", value: "p-r-5", value_laptop: "p-r-5-lp", value_tablet: "p-r-5-tb", value_mobile: "p-r-5-mb"},
                    {name: "6px", value: "p-r-6", value_laptop: "p-r-6-lp", value_tablet: "p-r-6-tb", value_mobile: "p-r-6-mb"},
                    {name: "7px", value: "p-r-7", value_laptop: "p-r-7-lp", value_tablet: "p-r-7-tb", value_mobile: "p-r-7-mb"},
                    {name: "8px", value: "p-r-8", value_laptop: "p-r-8-lp", value_tablet: "p-r-8-tb", value_mobile: "p-r-8-mb"},
                    {name: "10px", value: "p-r-9", value_laptop: "p-r-9-lp", value_tablet: "p-r-9-tb", value_mobile: "p-r-9-mb"},
                    {name: "12px", value: "p-r-10", value_laptop: "p-r-10-lp", value_tablet: "p-r-10-tb", value_mobile: "p-r-10-mb"},
                    {name: "14px", value: "p-r-11", value_laptop: "p-r-11-lp", value_tablet: "p-r-11-tb", value_mobile: "p-r-11-mb"},
                    {name: "16px", value: "p-r-12", value_laptop: "p-r-12-lp", value_tablet: "p-r-12-tb", value_mobile: "p-r-12-mb"},
                    {name: "18px", value: "p-r-13", value_laptop: "p-r-13-lp", value_tablet: "p-r-13-tb", value_mobile: "p-r-13-mb"},
                    {name: "20px", value: "p-r-14", value_laptop: "p-r-14-lp", value_tablet: "p-r-14-tb", value_mobile: "p-r-14-mb"},
                    {name: "22px", value: "p-r-15", value_laptop: "p-r-15-lp", value_tablet: "p-r-15-tb", value_mobile: "p-r-15-mb"},
                    {name: "24px", value: "p-r-16", value_laptop: "p-r-16-lp", value_tablet: "p-r-16-tb", value_mobile: "p-r-16-mb"},
                    {name: "28px", value: "p-r-17", value_laptop: "p-r-17-lp", value_tablet: "p-r-17-tb", value_mobile: "p-r-17-mb"},
                    {name: "32px", value: "p-r-18", value_laptop: "p-r-18-lp", value_tablet: "p-r-18-tb", value_mobile: "p-r-18-mb"},
                    {name: "36px", value: "p-r-19", value_laptop: "p-r-19-lp", value_tablet: "p-r-19-tb", value_mobile: "p-r-19-mb"},
                ]
            }
        ],
        MarginGroup: [
            {
                'type': 'margin-top',
                'icon': 'fa-arrow-up',
                'values': [
                    {name: "0px", value: "m-t-0", value_laptop: "m-t-0-lp", value_tablet: "m-t-0-tb", value_mobile: "m-t-0-mb"},
                    {name: "1px", value: "m-t-1", value_laptop: "m-t-1-lp", value_tablet: "m-t-1-tb", value_mobile: "m-t-1-mb"},
                    {name: "2px", value: "m-t-2", value_laptop: "m-t-2-lp", value_tablet: "m-t-2-tb", value_mobile: "m-t-2-mb"},
                    {name: "3px", value: "m-t-3", value_laptop: "m-t-3-lp", value_tablet: "m-t-3-tb", value_mobile: "m-t-3-mb"},
                    {name: "4px", value: "m-t-4", value_laptop: "m-t-4-lp", value_tablet: "m-t-4-tb", value_mobile: "m-t-4-mb"},
                    {name: "5px", value: "m-t-5", value_laptop: "m-t-5-lp", value_tablet: "m-t-5-tb", value_mobile: "m-t-5-mb"},
                    {name: "6px", value: "m-t-6", value_laptop: "m-t-6-lp", value_tablet: "m-t-6-tb", value_mobile: "m-t-6-mb"},
                    {name: "7px", value: "m-t-7", value_laptop: "m-t-7-lp", value_tablet: "m-t-7-tb", value_mobile: "m-t-7-mb"},
                    {name: "8px", value: "m-t-8", value_laptop: "m-t-8-lp", value_tablet: "m-t-8-tb", value_mobile: "m-t-8-mb"},
                    {name: "10px", value: "m-t-9", value_laptop: "m-t-9-lp", value_tablet: "m-t-9-tb", value_mobile: "m-t-9-mb"},
                    {name: "12px", value: "m-t-10", value_laptop: "m-t-10-lp", value_tablet: "m-t-10-tb", value_mobile: "m-t-10-mb"},
                    {name: "14px", value: "m-t-11", value_laptop: "m-t-11-lp", value_tablet: "m-t-11-tb", value_mobile: "m-t-11-mb"},
                    {name: "16px", value: "m-t-12", value_laptop: "m-t-12-lp", value_tablet: "m-t-12-tb", value_mobile: "m-t-12-mb"},
                    {name: "18px", value: "m-t-13", value_laptop: "m-t-13-lp", value_tablet: "m-t-13-tb", value_mobile: "m-t-13-mb"},
                    {name: "20px", value: "m-t-14", value_laptop: "m-t-14-lp", value_tablet: "m-t-14-tb", value_mobile: "m-t-14-mb"},
                    {name: "22px", value: "m-t-15", value_laptop: "m-t-15-lp", value_tablet: "m-t-15-tb", value_mobile: "m-t-15-mb"},
                    {name: "24px", value: "m-t-16", value_laptop: "m-t-16-lp", value_tablet: "m-t-16-tb", value_mobile: "m-t-16-mb"},
                    {name: "28px", value: "m-t-17", value_laptop: "m-t-17-lp", value_tablet: "m-t-17-tb", value_mobile: "m-t-17-mb"},
                    {name: "32px", value: "m-t-18", value_laptop: "m-t-18-lp", value_tablet: "m-t-18-tb", value_mobile: "m-t-18-mb"},
                    {name: "36px", value: "m-t-19", value_laptop: "m-t-19-lp", value_tablet: "m-t-19-tb", value_mobile: "m-t-19-mb"},
                    {name: "40px", value: "m-t-20", value_laptop: "m-t-20-lp", value_tablet: "m-t-20-tb", value_mobile: "m-t-20-mb"},
                    {name: "48px", value: "m-t-21", value_laptop: "m-t-21-lp", value_tablet: "m-t-21-tb", value_mobile: "m-t-21-mb"},
                    {name: "56px", value: "m-t-22", value_laptop: "m-t-22-lp", value_tablet: "m-t-22-tb", value_mobile: "m-t-22-mb"},
                    {name: "64px", value: "m-t-23", value_laptop: "m-t-23-lp", value_tablet: "m-t-23-tb", value_mobile: "m-t-23-mb"},
                    {name: "80px", value: "m-t-24", value_laptop: "m-t-24-lp", value_tablet: "m-t-24-tb", value_mobile: "m-t-24-mb"},
                    {name: "96px", value: "m-t-25", value_laptop: "m-t-25-lp", value_tablet: "m-t-25-tb", value_mobile: "m-t-25-mb"},
                    {name: "112px", value: "m-t-26", value_laptop: "m-t-26-lp", value_tablet: "m-t-26-tb", value_mobile: "m-t-26-mb"},
                    {name: "128px", value: "m-t-27", value_laptop: "m-t-27-lp", value_tablet: "m-t-27-tb", value_mobile: "m-t-27-mb"},
                    {name: "144px", value: "m-t-28", value_laptop: "m-t-28-lp", value_tablet: "m-t-28-tb", value_mobile: "m-t-28-mb"},
                    {name: "168px", value: "m-t-29", value_laptop: "m-t-29-lp", value_tablet: "m-t-29-tb", value_mobile: "m-t-29-mb"},
                    {name: "192px", value: "m-t-30", value_laptop: "m-t-30-lp", value_tablet: "m-t-30-tb", value_mobile: "m-t-30-mb"},
                    {name: "∞ (auto)", value: "m-t-a", value_laptop: "m-t-a-lp", value_tablet: "m-t-a-tb", value_mobile: "m-t-a-mb"},
                ]
            },
            {
                'type': 'margin-bottom',
                'icon': 'fa-arrow-down',
                'values': [
                    {name: "0px", value: "m-b-0", value_laptop: "m-b-0-lp", value_tablet: "m-b-0-tb", value_mobile: "m-b-0-mb"},
                    {name: "1px", value: "m-b-1", value_laptop: "m-b-1-lp", value_tablet: "m-b-1-tb", value_mobile: "m-b-1-mb"},
                    {name: "2px", value: "m-b-2", value_laptop: "m-b-2-lp", value_tablet: "m-b-2-tb", value_mobile: "m-b-2-mb"},
                    {name: "3px", value: "m-b-3", value_laptop: "m-b-3-lp", value_tablet: "m-b-3-tb", value_mobile: "m-b-3-mb"},
                    {name: "4px", value: "m-b-4", value_laptop: "m-b-4-lp", value_tablet: "m-b-4-tb", value_mobile: "m-b-4-mb"},
                    {name: "5px", value: "m-b-5", value_laptop: "m-b-5-lp", value_tablet: "m-b-5-tb", value_mobile: "m-b-5-mb"},
                    {name: "6px", value: "m-b-6", value_laptop: "m-b-6-lp", value_tablet: "m-b-6-tb", value_mobile: "m-b-6-mb"},
                    {name: "7px", value: "m-b-7", value_laptop: "m-b-7-lp", value_tablet: "m-b-7-tb", value_mobile: "m-b-7-mb"},
                    {name: "8px", value: "m-b-8", value_laptop: "m-b-8-lp", value_tablet: "m-b-8-tb", value_mobile: "m-b-8-mb"},
                    {name: "10px", value: "m-b-9", value_laptop: "m-b-9-lp", value_tablet: "m-b-9-tb", value_mobile: "m-b-9-mb"},
                    {name: "12px", value: "m-b-10", value_laptop: "m-b-10-lp", value_tablet: "m-b-10-tb", value_mobile: "m-b-10-mb"},
                    {name: "14px", value: "m-b-11", value_laptop: "m-b-11-lp", value_tablet: "m-b-11-tb", value_mobile: "m-b-11-mb"},
                    {name: "16px", value: "m-b-12", value_laptop: "m-b-12-lp", value_tablet: "m-b-12-tb", value_mobile: "m-b-12-mb"},
                    {name: "18px", value: "m-b-13", value_laptop: "m-b-13-lp", value_tablet: "m-b-13-tb", value_mobile: "m-b-13-mb"},
                    {name: "20px", value: "m-b-14", value_laptop: "m-b-14-lp", value_tablet: "m-b-14-tb", value_mobile: "m-b-14-mb"},
                    {name: "22px", value: "m-b-15", value_laptop: "m-b-15-lp", value_tablet: "m-b-15-tb", value_mobile: "m-b-15-mb"},
                    {name: "24px", value: "m-b-16", value_laptop: "m-b-16-lp", value_tablet: "m-b-16-tb", value_mobile: "m-b-16-mb"},
                    {name: "28px", value: "m-b-17", value_laptop: "m-b-17-lp", value_tablet: "m-b-17-tb", value_mobile: "m-b-17-mb"},
                    {name: "32px", value: "m-b-18", value_laptop: "m-b-18-lp", value_tablet: "m-b-18-tb", value_mobile: "m-b-18-mb"},
                    {name: "36px", value: "m-b-19", value_laptop: "m-b-19-lp", value_tablet: "m-b-19-tb", value_mobile: "m-b-19-mb"},
                    {name: "40px", value: "m-b-20", value_laptop: "m-b-20-lp", value_tablet: "m-b-20-tb", value_mobile: "m-b-20-mb"},
                    {name: "48px", value: "m-b-21", value_laptop: "m-b-21-lp", value_tablet: "m-b-21-tb", value_mobile: "m-b-21-mb"},
                    {name: "56px", value: "m-b-22", value_laptop: "m-b-22-lp", value_tablet: "m-b-22-tb", value_mobile: "m-b-22-mb"},
                    {name: "64px", value: "m-b-23", value_laptop: "m-b-23-lp", value_tablet: "m-b-23-tb", value_mobile: "m-b-23-mb"},
                    {name: "80px", value: "m-b-24", value_laptop: "m-b-24-lp", value_tablet: "m-b-24-tb", value_mobile: "m-b-24-mb"},
                    {name: "96px", value: "m-b-25", value_laptop: "m-b-25-lp", value_tablet: "m-b-25-tb", value_mobile: "m-b-25-mb"},
                    {name: "112px", value: "m-b-26", value_laptop: "m-b-26-lp", value_tablet: "m-b-26-tb", value_mobile: "m-b-26-mb"},
                    {name: "128px", value: "m-b-27", value_laptop: "m-b-27-lp", value_tablet: "m-b-27-tb", value_mobile: "m-b-27-mb"},
                    {name: "144px", value: "m-b-28", value_laptop: "m-b-28-lp", value_tablet: "m-b-28-tb", value_mobile: "m-b-28-mb"},
                    {name: "168px", value: "m-b-29", value_laptop: "m-b-29-lp", value_tablet: "m-b-29-tb", value_mobile: "m-b-29-mb"},
                    {name: "192px", value: "m-b-30", value_laptop: "m-b-30-lp", value_tablet: "m-b-30-tb", value_mobile: "m-b-30-mb"},
                    {name: "∞ (auto)", value: "m-b-a", value_laptop: "m-b-a-lp", value_tablet: "m-b-a-tb", value_mobile: "m-b-a-mb"},
                ]
            },
            {
                'type': 'margin-left',
                'icon': 'fa-arrow-left',
                'values': [
                    {name: "0px", value: "m-l-0", value_laptop: "m-l-0-lp", value_tablet: "m-l-0-tb", value_mobile: "m-l-0-mb"},
                    {name: "1px", value: "m-l-1", value_laptop: "m-l-1-lp", value_tablet: "m-l-1-tb", value_mobile: "m-l-1-mb"},
                    {name: "2px", value: "m-l-2", value_laptop: "m-l-2-lp", value_tablet: "m-l-2-tb", value_mobile: "m-l-2-mb"},
                    {name: "3px", value: "m-l-3", value_laptop: "m-l-3-lp", value_tablet: "m-l-3-tb", value_mobile: "m-l-3-mb"},
                    {name: "4px", value: "m-l-4", value_laptop: "m-l-4-lp", value_tablet: "m-l-4-tb", value_mobile: "m-l-4-mb"},
                    {name: "5px", value: "m-l-5", value_laptop: "m-l-5-lp", value_tablet: "m-l-5-tb", value_mobile: "m-l-5-mb"},
                    {name: "6px", value: "m-l-6", value_laptop: "m-l-6-lp", value_tablet: "m-l-6-tb", value_mobile: "m-l-6-mb"},
                    {name: "7px", value: "m-l-7", value_laptop: "m-l-7-lp", value_tablet: "m-l-7-tb", value_mobile: "m-l-7-mb"},
                    {name: "8px", value: "m-l-8", value_laptop: "m-l-8-lp", value_tablet: "m-l-8-tb", value_mobile: "m-l-8-mb"},
                    {name: "10px", value: "m-l-9", value_laptop: "m-l-9-lp", value_tablet: "m-l-9-tb", value_mobile: "m-l-9-mb"},
                    {name: "12px", value: "m-l-10", value_laptop: "m-l-10-lp", value_tablet: "m-l-10-tb", value_mobile: "m-l-10-mb"},
                    {name: "14px", value: "m-l-11", value_laptop: "m-l-11-lp", value_tablet: "m-l-11-tb", value_mobile: "m-l-11-mb"},
                    {name: "16px", value: "m-l-12", value_laptop: "m-l-12-lp", value_tablet: "m-l-12-tb", value_mobile: "m-l-12-mb"},
                    {name: "18px", value: "m-l-13", value_laptop: "m-l-13-lp", value_tablet: "m-l-13-tb", value_mobile: "m-l-13-mb"},
                    {name: "20px", value: "m-l-14", value_laptop: "m-l-14-lp", value_tablet: "m-l-14-tb", value_mobile: "m-l-14-mb"},
                    {name: "22px", value: "m-l-15", value_laptop: "m-l-15-lp", value_tablet: "m-l-15-tb", value_mobile: "m-l-15-mb"},
                    {name: "24px", value: "m-l-16", value_laptop: "m-l-16-lp", value_tablet: "m-l-16-tb", value_mobile: "m-l-16-mb"},
                    {name: "28px", value: "m-l-17", value_laptop: "m-l-17-lp", value_tablet: "m-l-17-tb", value_mobile: "m-l-17-mb"},
                    {name: "32px", value: "m-l-18", value_laptop: "m-l-18-lp", value_tablet: "m-l-18-tb", value_mobile: "m-l-18-mb"},
                    {name: "36px", value: "m-l-19", value_laptop: "m-l-19-lp", value_tablet: "m-l-19-tb", value_mobile: "m-l-19-mb"},
                    {name: "∞ (auto)", value: "m-l-a", value_laptop: "m-l-a-lp", value_tablet: "m-l-a-tb", value_mobile: "m-l-a-mb"},
                ]
            },
            {
                'type': 'margin-right',
                'icon': 'fa-arrow-right',
                'values': [
                    {name: "0px", value: "m-r-0", value_laptop: "m-r-0-lp", value_tablet: "m-r-0-tb", value_mobile: "m-r-0-mb"},
                    {name: "1px", value: "m-r-1", value_laptop: "m-r-1-lp", value_tablet: "m-r-1-tb", value_mobile: "m-r-1-mb"},
                    {name: "2px", value: "m-r-2", value_laptop: "m-r-2-lp", value_tablet: "m-r-2-tb", value_mobile: "m-r-2-mb"},
                    {name: "3px", value: "m-r-3", value_laptop: "m-r-3-lp", value_tablet: "m-r-3-tb", value_mobile: "m-r-3-mb"},
                    {name: "4px", value: "m-r-4", value_laptop: "m-r-4-lp", value_tablet: "m-r-4-tb", value_mobile: "m-r-4-mb"},
                    {name: "5px", value: "m-r-5", value_laptop: "m-r-5-lp", value_tablet: "m-r-5-tb", value_mobile: "m-r-5-mb"},
                    {name: "6px", value: "m-r-6", value_laptop: "m-r-6-lp", value_tablet: "m-r-6-tb", value_mobile: "m-r-6-mb"},
                    {name: "7px", value: "m-r-7", value_laptop: "m-r-7-lp", value_tablet: "m-r-7-tb", value_mobile: "m-r-7-mb"},
                    {name: "8px", value: "m-r-8", value_laptop: "m-r-8-lp", value_tablet: "m-r-8-tb", value_mobile: "m-r-8-mb"},
                    {name: "10px", value: "m-r-9", value_laptop: "m-r-9-lp", value_tablet: "m-r-9-tb", value_mobile: "m-r-9-mb"},
                    {name: "12px", value: "m-r-10", value_laptop: "m-r-10-lp", value_tablet: "m-r-10-tb", value_mobile: "m-r-10-mb"},
                    {name: "14px", value: "m-r-11", value_laptop: "m-r-11-lp", value_tablet: "m-r-11-tb", value_mobile: "m-r-11-mb"},
                    {name: "16px", value: "m-r-12", value_laptop: "m-r-12-lp", value_tablet: "m-r-12-tb", value_mobile: "m-r-12-mb"},
                    {name: "18px", value: "m-r-13", value_laptop: "m-r-13-lp", value_tablet: "m-r-13-tb", value_mobile: "m-r-13-mb"},
                    {name: "20px", value: "m-r-14", value_laptop: "m-r-14-lp", value_tablet: "m-r-14-tb", value_mobile: "m-r-14-mb"},
                    {name: "22px", value: "m-r-15", value_laptop: "m-r-15-lp", value_tablet: "m-r-15-tb", value_mobile: "m-r-15-mb"},
                    {name: "24px", value: "m-r-16", value_laptop: "m-r-16-lp", value_tablet: "m-r-16-tb", value_mobile: "m-r-16-mb"},
                    {name: "28px", value: "m-r-17", value_laptop: "m-r-17-lp", value_tablet: "m-r-17-tb", value_mobile: "m-r-17-mb"},
                    {name: "32px", value: "m-r-18", value_laptop: "m-r-18-lp", value_tablet: "m-r-18-tb", value_mobile: "m-r-18-mb"},
                    {name: "36px", value: "m-r-19", value_laptop: "m-r-19-lp", value_tablet: "m-r-19-tb", value_mobile: "m-r-19-mb"},
                    {name: "∞ (auto)", value: "m-r-a", value_laptop: "m-r-a-lp", value_tablet: "m-r-a-tb", value_mobile: "m-r-a-mb"},
                ]
            },
        ],
        TagsGroup: {
            'type': 'tag',
            'values': [
                {name: "<H1>", value: "h1"},
                {name: "<H2>", value: "h2"},
                {name: "<H3>", value: "h3"},
                {name: "<H4>", value: "h4"},
                {name: "<H5>", value: "h5"},
                {name: "<H6>", value: "h6"},
                {name: "<p>", value: "p"},
            ]
        },
        HeightGroup: {
            'type': 'min-height',
            'values': [
                {name: "Content", value: "none", type: 'content'},
                {name: "Custom", value: "300px", unit: 'px', type: 'custom'},
                {name: "Browser height", value: "100vh", type: 'browser'},
                {name: "Fill parent", value: "100%", type: 'parent'},
            ]
        },
        ListStyleGroup: {
            'type': 'tag',
            'values': [
                {name: "Unordered", value: "ul"},
                {name: "Ordered", value: "ol"},
            ]
        },
        link_dropdown_data: [
            {name: 'Internal link', value: 'internal-link', semi_header: 'Page', placeholder_internal: 'page-address', new_window: true, 'no_follow': true},
            {name: 'External link', value: 'external-link', semi_header: 'Url', placeholder: 'https://www.wikipedia.org/', new_window: true, 'no_follow': true},
            {name: 'Scroll to block', value: 'block-link', semi_header: 'Block', placeholder_internal: '#unique-name',},
            {name: 'E-mail', value: 'email-link', semi_header: 'Email url', placeholder: 'info@gmail.com'},
            {name: 'Phone call', value: 'phone-link', semi_header: 'Phone number' , placeholder: '+12345678910'}
        ],
        link_block_data: [
                {name: 'Manually', value: null, url: ''},
                {name: 'Block1', value: '1', url: '#block1'},
                {name: 'Block2', value: '2', url: '#block2'},
                {name: 'Block3', value: '3', url: '#block3'}
        ],
        link_page_data: [
                {name: 'Manually', value: null, url: ''},
                {name: 'Главная', value: '1', url: '/'},
                {name: 'ЛК', value: '2', url: '/lk'},
                {name: 'About-us', value: '3', url: '/about-us'},
        ],
        color_toggle_data: [
            {name: 'Palette', value: 'palette', icon: 'fa-palette'},
            {name: 'Self color', value: 'self_color', icon: 'fa-eye-dropper'},
        ],
        'border-color_toggle_data': [
            {name: 'Palette', value: 'palette', icon: 'fa-palette'},
            {name: 'Self color', value: 'self_color', icon: 'fa-eye-dropper'},
        ],
        background_toggle_data: [
            {name: 'Palette', value: 'palette', icon: 'fa-palette'},
            {name: 'Self color', value: 'self_color', icon: 'fa-eye-dropper'},
            {name: 'Image', value: 'image', icon: 'fa-image'},
            {name: 'Video', value: 'video', icon: 'fa-video'},
        ],
        border_size_toggle_data: [
            {name: 'Library', value: 'library'},
            {name: 'Self size', value: 'self_size'},
        ],
        shadow_toggle_data: [
            {name: 'Library', value: 'palette'},
            {name: 'Manually', value: 'self_color'},
        ],
        border_style_toggle_data: [
            {name: 'All sides', value: 'all'},
            {name: 'Separate', value: 'separate'},
        ],
        image_upload_toggle_data: [
            {name: 'Upload', value: 'upload'},
            {name: 'SVG', value: 'svg'},
            {name: 'Address', value: 'address'},
        ],
        video_upload_toggle_data: [
            {name: 'Code', value: 'code'},
            {name: 'Upload', value: 'upload'}
        ],
        border_button_data: {
            edited: {name: 'Remove border', value: 'empty', icon: 'fa-trash-alt'},
            empty: {name: 'Add border', value: 'edited', icon: 'fa-plus'},
        },
        border_unit_data: [
            {name: 'px', value: 'px'},
            {name: 'rem', value: 'rem'},
            {name: 'em', value: 'em'},
        ],
        gradient_type_data: [
            {name: 'Linear', value: 'linear-gradient'},
            {name: 'Radial', value: 'radial-gradient'},
        ],

        'media_icons_data': {desktop: "fa-desktop", laptop: 'fa-laptop', tablet: 'fa-tablet-alt', mobile: 'fa-mobile-alt'},
        'media_sizes_data': ['lp','tb', 'mb'],
        'image_toggle_data': {
            'space': [
                {name: 'Fit', value: 'contain no-repeat', icon: 'fa-compress-alt', key: 'space'},
                {name: 'Fill', value: 'cover', icon: 'fa-expand-alt', key: 'space'},
                {name: 'Repeat', value: 'contain', icon: 'fa-chess-board', key: 'space'},
            ],
            'alignmentX': [
                {name: 'Left', value: 'left', icon: 'fa-long-arrow-alt-left', key: 'alignmentX'},
                {name: 'Center', value: 'center', icon: 'fa-arrows-alt-h', key: 'alignmentX'},
                {name: 'Right', value: 'right', icon: 'fa-long-arrow-alt-right', key: 'alignmentX'},
            ],
            'alignmentY': [
                {name: 'Top', value: 'top', icon: 'fa-long-arrow-alt-up', key: 'alignmentY'},
                {name: 'Center', value: 'center', icon: 'fa-arrows-alt-v', key: 'alignmentY'},
                {name: 'Bottom', value: 'bottom', icon: 'fa-long-arrow-alt-down', key: 'alignmentY'},
            ],

        },
        'colors_variables_data':  {
            'bg-brn-a': '--brn-a',
            'tx-brn-a': '--brn-a',
            'br-brn-a': '--brn-a',
            'bg-brn-a-8': '--brn-a-8',
            'tx-brn-a-8': '--brn-a-8',
            'br-brn-a-8': '--brn-a-8',
            'bg-brn-a-9': '--brn-a-9',
            'tx-brn-a-9': '--brn-a-9',
            'br-brn-a-9': '--brn-a-9',
            'bg-brn-a-1': '--brn-a-1',
            'tx-brn-a-1': '--brn-a-1',
            'br-brn-a-1': '--brn-a-1',
            'bg-brn-a-2': '--brn-a-2',
            'tx-brn-a-2': '--brn-a-2',
            'br-brn-a-2': '--brn-a-2',
            'bg-brn-b': '--brn-b',
            'tx-brn-b': '--brn-b',
            'br-brn-b': '--brn-b',
            'bg-brn-b-8': '--brn-b-8',
            'tx-brn-b-8': '--brn-b-8',
            'br-brn-b-8': '--brn-b-8',
            'bg-brn-b-9': '--brn-b-9',
            'tx-brn-b-9': '--brn-b-9',
            'br-brn-b-9': '--brn-b-9',
            'bg-brn-b-1': '--brn-b-1',
            'tx-brn-b-1': '--brn-b-1',
            'br-brn-b-1': '--brn-b-1',
            'bg-brn-b-2': '--brn-b-2',
            'tx-brn-b-2': '--brn-b-2',
            'br-brn-b-2': '--brn-b-2',
            'bg-brn-c': '--brn-c',
            'tx-brn-c': '--brn-c',
            'br-brn-c': '--brn-c',
            'bg-brn-c-8': '--brn-c-8',
            'tx-brn-c-8': '--brn-c-8',
            'br-brn-c-8': '--brn-c-8',
            'bg-brn-c-9': '--brn-c-9',
            'tx-brn-c-9': '--brn-c-9',
            'br-brn-c-9': '--brn-c-9',
            'bg-brn-c-1': '--brn-c-1',
            'tx-brn-c-1': '--brn-c-1',
            'br-brn-c-1': '--brn-c-1',
            'bg-brn-c-2': '--brn-c-2',
            'tx-brn-c-2': '--brn-c-2',
            'br-brn-c-2': '--brn-c-2',
            'bg-brn-d': '--brn-d',
            'tx-brn-d': '--brn-d',
            'br-brn-d': '--brn-d',
            'bg-brn-d-8': '--brn-d-8',
            'tx-brn-d-8': '--brn-d-8',
            'br-brn-d-8': '--brn-d-8',
            'bg-brn-d-9': '--brn-d-9',
            'tx-brn-d-9': '--brn-d-9',
            'br-brn-d-9': '--brn-d-9',
            'bg-brn-d-1': '--brn-d-1',
            'tx-brn-d-1': '--brn-d-1',
            'br-brn-d-1': '--brn-d-1',
            'bg-brn-d-2': '--brn-d-2',
            'tx-brn-d-2': '--brn-d-2',
            'br-brn-d-2': '--brn-d-2',
            'bg-brn-e': '--brn-e',
            'tx-brn-e': '--brn-e',
            'br-brn-e': '--brn-e',
            'bg-brn-e-8': '--brn-e-8',
            'tx-brn-e-8': '--brn-e-8',
            'br-brn-e-8': '--brn-e-8',
            'bg-brn-e-9': '--brn-e-9',
            'tx-brn-e-9': '--brn-e-9',
            'br-brn-e-9': '--brn-e-9',
            'bg-brn-e-1': '--brn-e-1',
            'tx-brn-e-1': '--brn-e-1',
            'br-brn-e-1': '--brn-e-1',
            'bg-brn-e-2': '--brn-e-2',
            'tx-brn-e-2': '--brn-e-2',
            'br-brn-e-2': '--brn-e-2',
            'bg-brn-g': '--brn-g',
            'tx-brn-g': '--brn-g',
            'br-brn-g': '--brn-g',
            'bg-brn-g-8': '--brn-g-8',
            'tx-brn-g-8': '--brn-g-8',
            'br-brn-g-8': '--brn-g-8',
            'bg-brn-g-9': '--brn-g-9',
            'tx-brn-g-9': '--brn-g-9',
            'br-brn-g-9': '--brn-g-9',
            'bg-brn-g-1': '--brn-g-1',
            'tx-brn-g-1': '--brn-g-1',
            'br-brn-g-1': '--brn-g-1',
            'bg-brn-g-2': '--brn-g-2',
            'tx-brn-g-2': '--brn-g-2',
            'br-brn-g-2': '--brn-g-2',
            'bg-brn-h': '--brn-h',
            'tx-brn-h': '--brn-h',
            'br-brn-h': '--brn-h',
            'bg-brn-h-8': '--brn-h-8',
            'tx-brn-h-8': '--brn-h-8',
            'br-brn-h-8': '--brn-h-8',
            'bg-brn-h-9': '--brn-h-9',
            'tx-brn-h-9': '--brn-h-9',
            'br-brn-h-9': '--brn-h-9',
            'bg-brn-h-1': '--brn-h-1',
            'tx-brn-h-1': '--brn-h-1',
            'br-brn-h-1': '--brn-h-1',
            'bg-brn-h-2': '--brn-h-2',
            'tx-brn-h-2': '--brn-h-2',
            'br-brn-h-2': '--brn-h-2',
            'bg-brn-j': '--brn-j',
            'tx-brn-j': '--brn-j',
            'br-brn-j': '--brn-j',
            'bg-brn-j-8': '--brn-j-8',
            'tx-brn-j-8': '--brn-j-8',
            'br-brn-j-8': '--brn-j-8',
            'bg-brn-j-9': '--brn-j-9',
            'tx-brn-j-9': '--brn-j-9',
            'br-brn-j-9': '--brn-j-9',
            'bg-brn-j-1': '--brn-j-1',
            'tx-brn-j-1': '--brn-j-1',
            'br-brn-j-1': '--brn-j-1',
            'bg-brn-j-2': '--brn-j-2',
            'tx-brn-j-2': '--brn-j-2',
            'br-brn-j-2': '--brn-j-2',
            'bg-brn-k': '--brn-k',
            'tx-brn-k': '--brn-k',
            'br-brn-k': '--brn-k',
            'bg-brn-k-8': '--brn-k-8',
            'tx-brn-k-8': '--brn-k-8',
            'br-brn-k-8': '--brn-k-8',
            'bg-brn-k-9': '--brn-k-9',
            'tx-brn-k-9': '--brn-k-9',
            'br-brn-k-9': '--brn-k-9',
            'bg-brn-k-1': '--brn-k-1',
            'tx-brn-k-1': '--brn-k-1',
            'br-brn-k-1': '--brn-k-1',
            'bg-brn-k-2': '--brn-k-2',
            'tx-brn-k-2': '--brn-k-2',
            'br-brn-k-2': '--brn-k-2',
            'bg-blc': '--black',
            'tx-blc': '--black',
            'br-blc': '--black',
            'bg-wh': '--white',
            'tx-wh': '--white',
            'br-wh': '--white',
            'bg-bw-1': '--bw-1',
            'tx-bw-1': '--bw-1',
            'br-bw-1': '--bw-1',
            'bg-bw-2': '--bw-2',
            'tx-bw-2': '--bw-2',
            'br-bw-2': '--bw-2',
            'bg-bw-3': '--bw-3',
            'tx-bw-3': '--bw-3',
            'br-bw-3': '--bw-3',
            'bg-bw-4': '--bw-4',
            'tx-bw-4': '--bw-4',
            'br-bw-4': '--bw-4',
            'bg-bw-5': '--bw-5',
            'tx-bw-5': '--bw-5',
            'br-bw-5': '--bw-5',
            'bg-bw-6': '--bw-6',
            'tx-bw-6': '--bw-6',
            'br-bw-6': '--bw-6',
            'bg-bw-7': '--bw-7',
            'tx-bw-7': '--bw-7',
            'br-bw-7': '--bw-7',
            'bg-bw-8': '--bw-8',
            'tx-bw-8': '--bw-8',
            'br-bw-8': '--bw-8',
            'bg-b-opc-1': '--b-opc-1',
            'tx-b-opc-1': '--b-opc-1',
            'br-b-opc-1': '--b-opc-1',
            'bg-b-opc-2': '--b-opc-2',
            'tx-b-opc-2': '--b-opc-2',
            'br-b-opc-2': '--b-opc-2',
            'bg-b-opc-3': '--b-opc-3',
            'tx-b-opc-3': '--b-opc-3',
            'br-b-opc-3': '--b-opc-3',
            'bg-b-opc-4': '--b-opc-4',
            'tx-b-opc-4': '--b-opc-4',
            'br-b-opc-4': '--b-opc-4',
            'bg-b-opc-5': '--b-opc-5',
            'tx-b-opc-5': '--b-opc-5',
            'br-b-opc-5': '--b-opc-5',
            'bg-b-opc-6': '--b-opc-6',
            'tx-b-opc-6': '--b-opc-6',
            'br-b-opc-6': '--b-opc-6',
            'bg-b-opc-7': '--b-opc-7',
            'tx-b-opc-7': '--b-opc-7',
            'br-b-opc-7': '--b-opc-7',
            'bg-b-opc-8': '--b-opc-8',
            'tx-b-opc-8': '--b-opc-8',
            'br-b-opc-8': '--b-opc-8',
            'bg-w-opc-1': '--w-opc-1',
            'tx-w-opc-1': '--w-opc-1',
            'br-w-opc-1': '--w-opc-1',
            'bg-w-opc-2': '--w-opc-2',
            'tx-w-opc-2': '--w-opc-2',
            'br-w-opc-2': '--w-opc-2',
            'bg-w-opc-3': '--w-opc-3',
            'tx-w-opc-3': '--w-opc-3',
            'br-w-opc-3': '--w-opc-3',
            'bg-w-opc-4': '--w-opc-4',
            'tx-w-opc-4': '--w-opc-4',
            'br-w-opc-4': '--w-opc-4',
            'bg-w-opc-5': '--w-opc-5',
            'tx-w-opc-5': '--w-opc-5',
            'br-w-opc-5': '--w-opc-5',
            'bg-w-opc-6': '--w-opc-6',
            'tx-w-opc-6': '--w-opc-6',
            'br-w-opc-6': '--w-opc-6',
            'bg-w-opc-7': '--w-opc-7',
            'tx-w-opc-7': '--w-opc-7',
            'br-w-opc-7': '--w-opc-7',
            'bg-w-opc-8': '--w-opc-8',
            'tx-w-opc-8': '--w-opc-8',
            'br-w-opc-8': '--w-opc-8',
            },
            'ai_prompt_data': {
                'site.Paragraph': {
                    facility: 'site_text', response_type: 'content', fields: {emotion: 'positive', text_length: 'short', locale: 'ru_RU', audience_gender: ''}
                    },
                'site.Heading': {
                    facility: 'site_text', response_type: 'content', fields: {emotion: 'positive', text_length: 'minimal', locale: 'ru_RU', audience_gender: ''}
                    },
                'site.List': {
                    facility: 'site_list', response_type: 'list', fields: {emotion: 'positive', locale: $.site.lang, audience_gender: ''}
                },
                'site.CustomCode': {
                    facility: 'site_html', response_type: 'content', fields: {locale: 'ru_RU'}
                },
            },
    }

})(jQuery);
