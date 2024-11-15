
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
        FontGroup: {
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
        TextColorGroup: {
            'type': 'color',
            'icon': 'fa-circle',
            'values': [
                {name: "Default", value: "#000"},
            ]
        },
        BackgroundColorGroup: {
            'type': 'background',
            'default_data_type': 'block_props',
            'icon': 'fa-circle',
            'values': [
                {name: "Not set", value: "", type: 'palette', css: "#FFFFFF", layer: 0, gradient: {type: 'linear-gradient', degree: '90', stops: [{color: "#FFFFFF00", stop: "0"}, {color: "#FFFFFF00", stop: "100"}]}}
            ],
            'palette': [
                {name: "1-1", value: "bg-tr", type: 'palette', css: "#FFFFFF00"},
                {name: "1-2", value: "bg-brn-1", type: 'palette', css: "#177EE5"}
            ]
        },
        BorderColorGroup: {
            'type': 'border-color',
            'default_data_type': 'block_props',
            'icon': 'fa-circle',
            'values': [
                {name: "1-1", value: "bd-tr-1", type: 'palette', css: "#0000001a"}
            ],
            'palette': [
                {name: "1-1", value: "bd-tr-1", type: 'palette', css: "#0000001a"},
                {name: "1-2", value: "bd-tr-2", type: 'palette', css: "#00000033"}
            ]
        },
        BorderWidthGroup: {
            'type': 'border-width',
            'default_data_type': 'block_props',
            'icon': 'fa-circle',
            'values': [
                {name: "Толщина 3", value: 'b-w-l ', unit: 'px', type: 'library'}
            ],
            'library': [
                {name: "Толщина 3", value: 'b-w-l ', unit: 'px', type: 'library'},
                {name: "Толщина 2", value: 'b-w-m ', unit: 'px', type: 'library'},
                {name: "Толщина 1", value: 'b-w-s ', unit: 'px', type: 'library'},

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
            'values': [
                {name: "Main",  value: "main-set", value_class: "bg-brn-1 t-wht b-r-r"},
                {name: "Secondary",  value: "second-set", value_class: "bg-tr t-brn-1 b-r-r bd-brn-1 b-w-s"},
            ]
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
                {name: "Content", value: 'hg', value_laptop: 'hg', value_tablet: 'hg', value_mobile: 'hg'},
                {name: "Fill", value: 'fl', value_laptop: 'fl-lp', value_tablet: 'fl-tb', value_mobile: 'fl-mb'},
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
        ColumnsAlignGroup: {
            'type': 'flex-align',
            'values': [
                {name: "Align-center", value: "y-c", icon: "fa-align-center"},
                {name: "Align-left", value: "y-l", icon: "fa-align-left"},
                {name: "Align-right",  value: "y-r", icon: "fa-align-right"},
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
                    {name: "0px", value: "p-t-0"},
                    {name: "32px", value: "p-t-20"},
                    {name: "28px", value: "p-t-19"},
                    {name: "24px", value: "p-t-18"},
                    {name: "20px", value: "p-t-17"},
                    {name: "18px", value: "p-t-16"},
                    {name: "16px", value: "p-t-14"},
                    {name: "14px", value: "p-t-13"},
                    {name: "12px", value: "p-t-12"},
                    {name: "11px", value: "p-t-11"},
                    {name: "10px", value: "p-t-10"},
                    {name: "9px", value: "p-t-9"},
                    {name: "8px", value: "p-t-8"},
                    {name: "7px", value: "p-t-7"},
                    {name: "6px", value: "p-t-6"},
                    {name: "5px", value: "p-t-5"},
                    {name: "4px", value: "p-t-4"},
                    {name: "3px", value: "p-t-3"},
                    {name: "2px", value: "p-t-2"},
                    {name: "1px", value: "p-t-1"},

                ]
            },
            {
                'type': 'padding-bottom',
                'icon': 'fa-arrow-down',
                'values': [
                    {name: "0px", value: "p-b-0"},
                    {name: "32px", value: "p-b-20"},
                    {name: "28px", value: "p-b-19"},
                    {name: "24px", value: "p-b-18"},
                    {name: "20px", value: "p-b-17"},
                    {name: "18px", value: "p-b-16"},
                    {name: "16px", value: "p-b-14"},
                    {name: "14px", value: "p-b-13"},
                    {name: "12px", value: "p-b-12"},
                    {name: "11px", value: "p-b-11"},
                    {name: "10px", value: "p-b-10"},
                    {name: "9px", value: "p-b-9"},
                    {name: "8px", value: "p-b-8"},
                    {name: "7px", value: "p-b-7"},
                    {name: "6px", value: "p-b-6"},
                    {name: "5px", value: "p-b-5"},
                    {name: "4px", value: "p-b-4"},
                    {name: "3px", value: "p-b-3"},
                    {name: "2px", value: "p-b-2"},
                    {name: "1px", value: "p-b-1"},

                ]
            }
        ],
        MarginGroup: [
            {
                'type': 'margin-top',
                'icon': 'fa-arrow-up',
                'values': [
                    {name: "0px", value: "m-t-0"},
                    {name: "32px", value: "m-t-20"},
                    {name: "28px", value: "m-t-19"},
                    {name: "24px", value: "m-t-18"},
                    {name: "20px", value: "m-t-17"},
                    {name: "18px", value: "m-t-16"},
                    {name: "16px", value: "m-t-14"},
                    {name: "14px", value: "m-t-13"},
                    {name: "12px", value: "m-t-12"},
                    {name: "11px", value: "m-t-11"},
                    {name: "10px", value: "m-t-10"},
                    {name: "9px", value: "m-t-9"},
                    {name: "8px", value: "m-t-8"},
                    {name: "7px", value: "m-t-7"},
                    {name: "6px", value: "m-t-6"},
                    {name: "5px", value: "m-t-5"},
                    {name: "4px", value: "m-t-4"},
                    {name: "3px", value: "m-t-3"},
                    {name: "2px", value: "m-t-2"},
                    {name: "1px", value: "m-t-1"},
                ]
            },
            {
                'type': 'margin-bottom',
                'icon': 'fa-arrow-down',
                'values': [
                    {name: "0px", value: "m-b-0"},
                    {name: "32px", value: "m-b-20"},
                    {name: "28px", value: "m-b-19"},
                    {name: "24px", value: "m-b-18"},
                    {name: "20px", value: "m-b-17"},
                    {name: "18px", value: "m-b-16"},
                    {name: "16px", value: "m-b-14"},
                    {name: "14px", value: "m-b-13"},
                    {name: "12px", value: "m-b-12"},
                    {name: "11px", value: "m-b-11"},
                    {name: "10px", value: "m-b-10"},
                    {name: "9px", value: "m-b-9"},
                    {name: "8px", value: "m-b-8"},
                    {name: "7px", value: "m-b-7"},
                    {name: "6px", value: "m-b-6"},
                    {name: "5px", value: "m-b-5"},
                    {name: "4px", value: "m-b-4"},
                    {name: "3px", value: "m-b-3"},
                    {name: "2px", value: "m-b-2"},
                    {name: "1px", value: "m-b-1"},
                ]
            },
            {
                'type': 'margin-left',
                'icon': 'fa-arrow-left',
                'values': [
                    {name: "0px", value: "m-l-0"},
                    {name: "32px", value: "m-l-20"},
                    {name: "28px", value: "m-l-19"},
                    {name: "24px", value: "m-l-18"},
                    {name: "20px", value: "m-l-17"},
                    {name: "18px", value: "m-l-16"},
                    {name: "16px", value: "m-l-14"},
                    {name: "14px", value: "m-l-13"},
                    {name: "12px", value: "m-l-12"},
                    {name: "11px", value: "m-l-11"},
                    {name: "10px", value: "m-l-10"},
                    {name: "9px", value: "m-l-9"},
                    {name: "8px", value: "m-l-8"},
                    {name: "7px", value: "m-l-7"},
                    {name: "6px", value: "m-l-6"},
                    {name: "5px", value: "m-l-5"},
                    {name: "4px", value: "m-l-4"},
                    {name: "3px", value: "m-l-3"},
                    {name: "2px", value: "m-l-2"},
                    {name: "1px", value: "m-l-1"},
                ]
            },
            {
                'type': 'margin-right',
                'icon': 'fa-arrow-right',
                'values': [
                    {name: "0px", value: "m-r-0"},
                    {name: "32px", value: "m-r-20"},
                    {name: "28px", value: "m-r-19"},
                    {name: "24px", value: "m-r-18"},
                    {name: "20px", value: "m-r-17"},
                    {name: "18px", value: "m-r-16"},
                    {name: "16px", value: "m-r-14"},
                    {name: "14px", value: "m-r-13"},
                    {name: "12px", value: "m-r-12"},
                    {name: "11px", value: "m-r-11"},
                    {name: "10px", value: "m-r-10"},
                    {name: "9px", value: "m-r-9"},
                    {name: "8px", value: "m-r-8"},
                    {name: "7px", value: "m-r-7"},
                    {name: "6px", value: "m-r-6"},
                    {name: "5px", value: "m-r-5"},
                    {name: "4px", value: "m-r-4"},
                    {name: "3px", value: "m-r-3"},
                    {name: "2px", value: "m-r-2"},
                    {name: "1px", value: "m-r-1"},
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
        link_dropdown_data: [
            {name: 'External link', value: 'external-link', semi_header: 'Url', placeholder: 'https://www.wikipedia.org/', new_window: true},
            {name: 'Internal link', value: 'internal-link', semi_header: 'Page', new_window: true},
            {name: 'Scroll to block', value: 'block-link', semi_header: 'Block'},
            {name: 'E-mail', value: 'email-link', semi_header: 'Email url', placeholder: 'info@gmail.com'},
            {name: 'Phone call', value: 'phone-link', semi_header: 'Phone number' , placeholder: '+12345678910'}
        ],
        link_block_data: [
                {name: 'Block1', value: '1', url: '#block1'},
                {name: 'Block2', value: '2', url: '#block2'},
                {name: 'Block3', value: '3', url: '#block3'}
        ],
        link_page_data: [
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

        }
    }

})(jQuery);

