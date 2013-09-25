(function($) {

// override elBorderSelect
$.fn.elBorderSelect = function(o) {

    var $self = this;
    var self  = this.eq(0);
    var opts  = $.extend({}, $.fn.elBorderSelect.defaults, o);
    var width = $('<input type="text" />')
        .attr({'name' : opts.name+'[width]', size : 3}).css('text-align', 'right')
        .change(function() { $self.change();});

    var color = $('<div />').css('position', 'relative')
        .elColorPicker({
            'class'         : 'el-colorpicker ui-icon ui-icon-pencil',
            name            : opts.name+'[color]',
            palettePosition : 'outer',
            change          : function() { $self.change(); }
        });


    var style = $('<select></select>').change(function() { $self.change();});
    var style_options = {
            ''       : 'none',
            solid    : 'solid',
            dashed   : 'dashed',
            dotted   : 'dotted',
            'double' : 'double',
            groove   : 'groove',
            ridge    : 'ridge',
            inset    : 'inset',
            outset   : 'outset'
    };
    for (var i in style_options) {
        style.append('<option value="' + i + '">' + style_options[i] + '</option>');
    }

    self.empty().addClass(opts['class']).attr('name', opts.name||'')
    .append(
        $('<table />').attr('cellspacing', 0).append(
            $('<tr />')
                .append($('<td />').append(width).append(' px'))
                .append($('<td />').append(style))
                .append($('<td />').append(color))
        )
    );

    function rgb2hex(str) {
        function hex(x)  {
            hexDigits = ["0", "1", "2", "3", "4", "5", "6", "7", "8","9", "a", "b", "c", "d", "e", "f"];
            return !x  ? "00" : hexDigits[(x - x % 16) / 16] + hexDigits[x% 16];
        }
        var rgb = (str||'').match(/\(([0-9]{1,3}),\s*([0-9]{1,3}),\s*([0-9]{1,3})\)/);
        return rgb ? "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]) : '';
    }

    function toPixels(num) {
        if (!num) {
            return num;
        }
        var m = num.match(/([0-9]+\.?[0-9]*)\s*(px|pt|em|%)/);
        if (m) {
            num  = m[1];
            unit = m[2];
        }
        if (num[0] == '.') {
            num = '0'+num;
        }
        num = parseFloat(num);

        if (isNaN(num)) {
            return '';
        }
        var base = parseInt($(document.body).css('font-size')) || 16;
        switch (unit) {
            case 'em': return parseInt(num*base);
            case 'pt': return parseInt(num*base/12);
            case '%' : return parseInt(num*base/100);
        }
        return num;
    }

    this.change = function() {
        opts.change && opts.change(this.val());
    };

    this.val = function(v) {
        var w, s, c, b, m;

        if (!v && v !== '') {
            w = parseInt(width.val());
            w = !isNaN(w) ? w+'px' : '';
            s = style.val();
            c = color.val();
            return {
                width : w,
                style : s,
                color : c,
                css   : $.trim(w+' '+s+' '+c)
            };
        } else {
            b = '';
            if (v.nodeName || v.css) {
                if (!v.css) {
                    v = $(v);
                }
                b = v.css('border');
                if ((b = v.css('border'))) {
                    w = s = c = b;
                } else {
                    w = v.css('border-width');
                    s = v.css('border-style');
                    c = v.css('border-color');
                }

            } else {
                w = v.width||'';
                s = v.style||'';
                c = v.color||'';
            }

            width.val(toPixels(w));
            m = s ? s.match(/(solid|dashed|dotted|double|groove|ridge|inset|outset)/i) :'';
            style.val(m ? m[1] : '');
            color.val(c.indexOf('#') === 0 ? c : rgb2hex(c));
            return this;
        }
    };

    this.val(opts.value);
    return this;
};

$.fn.elBorderSelect.defaults = {
    name      : 'el-borderselect',
    'class'   : 'el-borderselect',
    value     : {},
    change    : null
};

// wa panels
elRTE.prototype.options.panels.wa_style = ["bold", "italic", "underline", "strikethrough"];
elRTE.prototype.options.panels.wa_image = ["wa_image"];
elRTE.prototype.options.panels.wa_links = ["wa_link", "unlink", "youtube"];
elRTE.prototype.options.panels.wa_elements = ["wa_horizontalrule", "blockquote", "div", "stopfloat"];
elRTE.prototype.options.panels.wa_tables = ["wa_table", "wa_tableprops", "tablerm", "tbrowbefore", "tbrowafter", "tbrowrm", "tbcolbefore", "tbcolafter", "tbcolrm", "wa_tbcellprops", "tbcellsmerge", "tbcellsplit"];

elRTE.prototype.options.buttons['wa_link'] = elRTE.prototype.options.buttons['link'];
elRTE.prototype.ui.prototype.buttons.wa_link = function(rte, name) {
    this.constructor.prototype.constructor.call(this, rte, name);
    var self = this;
    this.img = false;

    function init() {
        self.src = {
            href   : $('<input type="text" />'),
            title  : $('<input type="text" />'),
            target : $('<select />')
                .append($('<option />').text(self.rte.i18n('In this window')).val(''))
                .append($('<option />').text(self.rte.i18n('In new window (_blank)')).val('_blank'))
        };
    }

    this.command = function() {
        var n = this.rte.selection.getNode(),
            sel, i, v, link, href, s;

        !this.src && init();

        function isLink(n) {
            return n.nodeName == 'A' && n.href;
        }

        this.link = this.rte.dom.selfOrParentLink(n);

        if (!this.link) {
            sel = $.browser.msie ? this.rte.selection.selected() : this.rte.selection.selected({wrap : false});
            if (sel.length) {
                for (i=0; i < sel.length; i++) {
                    if (isLink(sel[i])) {
                        this.link = sel[i];
                        break;
                    }
                };
                if (!this.link) {
                    this.link = this.rte.dom.parent(sel[0], isLink) || this.rte.dom.parent(sel[sel.length-1], isLink);
                }
            }
        }

        this.link = this.link ? $(this.link) : $(this.rte.doc.createElement('a'));
        this.img = n.nodeName == 'IMG' ? n : null;

        var d = $('<div id="elrte-wa_link" class="fields form"></div>');
        var values = {
            href: this.rte.i18n('URL'),
            title: this.rte.i18n('Title'),
            target: this.rte.i18n('Target')
        };
        for (var n in values) {
            var t = $('<div class="field"></div>').append($('<div class="name"></div>').html(values[n]));
            t.append($('<div class="value"></div>').append(self.src[n]));
            d.append(t);
        }
        d.waDialog({
            esc: true,
            width: '400px',
            height: '170px',
            className: 'wa-elrte-dialog',
            title : this.rte.i18n('Link'),
            buttons: '<input type="submit" class="button green" value="' + this.rte.i18n('OK') + '"> ' + this.rte.i18n('or') + ' <a href="#" class="inline-link cancel"><b><i>' + this.rte.i18n('cancel') + '</i></b></a>',
            onClose: function () {
                self.rte.browser.msie && self.rte.selection.restoreIERange();
                self.rte.selection.moveToBookmark(self.rte.selection.getBookmark());
            },
            onSubmit: function (d) {
                self.set();
                d.trigger('close');
                return false;
            }
        });

        link = this.link.get(0);
        this.src.href.val(this.rte.dom.attr(link, 'href'));
    };

    this.update = function() {
        var n = this.rte.selection.getNode();

        if (this.rte.dom.selfOrParentLink(n)) {
            this.domElem.removeClass('disabled').addClass('active');
        } else if (this.rte.dom.selectionHas(function(n) { return n.nodeName == 'A' && n.href; })) {
            this.domElem.removeClass('disabled').addClass('active');
        } else if (!this.rte.selection.collapsed() || (n && n.nodeName == 'IMG')) {
            this.domElem.removeClass('disabled active');
        } else {
            this.domElem.addClass('disabled').removeClass('active');
        }
    };

    this.set = function() {
        var href, fakeURL;
        this.rte.history.add();
        href = this.src.href.val();
        if (this.img && this.img.parentNode) {
            this.link = $(this.rte.dom.create('a')).attr('href', href);
            this.rte.dom.wrap(this.img, this.link[0]);
        } else if (!this.link[0].parentNode) {
            fakeURL = '#--el-editor---'+Math.random();
            this.rte.doc.execCommand('createLink', false, fakeURL);
            this.link = $('a[href="'+fakeURL+'"]', this.rte.doc);
            this.link.each(function() {
                var $this = $(this);

                // удаляем ссылки вокруг пустых элементов
                if (!$.trim($this.html()) && !$.trim($this.text())) {
                    $this.replaceWith($this.text()); //  сохраняем пробелы :)
                }
            });
        }

        this.src.href.val(href);
        for (var n in this.src) {
            var v = $.trim(this.src[n].val());
            if (v) {
                this.link.attr(n, v);
            } else {
                this.link.removeAttr(n);
            }
        };

        this.img && this.rte.selection.select(this.img);
        this.rte.ui.update(true);

    };

};
elRTE.prototype.options.buttons['wa_horizontalrule'] = elRTE.prototype.options.buttons['horizontalrule'];
elRTE.prototype.ui.prototype.buttons.wa_horizontalrule = function(rte, name) {
    this.constructor.prototype.constructor.call(this, rte, name);
    var self = this;
    this.src = {
        width   : $('<input type="text" />').attr({'name' : 'width', 'size' : 4}).css('text-align', 'right'),
        wunit   : $('<select />').attr('name', 'wunit')
                    .append($('<option />').val('%').text('%'))
                    .append($('<option />').val('px').text('px'))
                    .val('%'),
        height  : $('<input type="text" />').attr({'name' : 'height', 'size' : 4}).css('text-align', 'right'),
        bg      : $('<div />')
    };

    this.command = function() {
        if (!this.src.bg.is('.el-colorpicker')) {
            this.src.bg.elColorPicker({palettePosition : 'outer', 'class' : 'el-colorpicker ui-icon ui-icon-pencil'});
        }

        var n   = this.rte.selection.getEnd();
        this.hr = n.nodeName == 'HR' ? $(n) : $(rte.doc.createElement('hr')).css({width : '100%', height : '1px'});

        var _w  = this.hr.css('width') || this.hr.attr('width');
        this.src.width.val(parseInt(_w) || 100);
        this.src.wunit.val(_w.indexOf('px') != -1 ? 'px' : '%');

        this.src.height.val( this.rte.utils.toPixels(this.hr.css('height') || this.hr.attr('height')) || 1) ;

        this.src.bg.val(this.rte.utils.color2Hex(this.hr.css('background-color')));

        var d = $('<div id="elrte-wa_horizontalrule" class="fields form"></div>')
                .append($('<div class="field"><div class="name">' + this.rte.i18n('Width') + '</div></div>').append(
                    $('<div class="value"></div>').append(this.src.width).append(this.src.wunit)
                ))
                .append($('<div class="field"><div class="name">' + this.rte.i18n('Height') + '</div></div>').append(
                    $('<div class="value"></div>').append(this.src.height).append(' px')
                ))
                .append($('<div class="field"><div class="name">' + this.rte.i18n('Background') + '</div></div>').append(
                    $('<div class="value"></div>').append(this.src.bg)
                ));
        d.waDialog({
            esc: true,
            width: '400px',
            height: '170px',
            className: 'wa-elrte-dialog',
            title : this.rte.i18n('Horizontal rule'),
            buttons: '<input type="submit" class="button green" value="' + this.rte.i18n('OK') + '"> ' + this.rte.i18n('or') + ' <a href="#" class="inline-link cancel"><b><i>' + this.rte.i18n('cancel') + '</i></b></a>',
            onSubmit: function (d) {
                self.set();
                d.trigger('close');
                return false;
            }
        });
    };

    this.update = function() {
        this.domElem.removeClass('disabled');
        if (this.rte.selection.getEnd() && this.rte.selection.getEnd().nodeName == 'HR') {
            this.domElem.addClass('active');
        } else {
            this.domElem.removeClass('active');
        }
    };

    this.set = function() {
        this.rte.history.add();
        !this.hr.parentNode && this.rte.selection.insertNode(this.hr.get(0));

        this.hr.removeAttr('width')
            .removeAttr('height')
            .removeAttr('align')
            .attr('noshade', true)
            .css({
                width  : (parseInt(this.src.width.val()) || 100)+this.src.wunit.val(),
                height : parseInt(this.src.height.val()) || 1,
                'background-color' : this.src.bg.val()
            });
        this.rte.ui.update();
    };
};

elRTE.prototype.options.buttons['wa_table'] = elRTE.prototype.options.buttons['table'];
elRTE.prototype.ui.prototype.buttons.wa_table = function(rte, name) {
    this.constructor.prototype.constructor.call(this, rte, name);
    var self    = this;
    this.src    = null;
    this.labels = null;

    function init() {
        self.src = {
            rows    : $('<input type="text" />').attr('size', 5).val(2),
            cols    : $('<input type="text" />').attr('size', 5).val(2),
            width   : $('<input type="text" />').attr('size', 5),
            wunit   : $('<select />')
                        .append($('<option />').val('%').text('%'))
                        .append($('<option />').val('px').text('px')),
            height  : $('<input type="text" />').attr('size', 5),
            hunit   : $('<select />')
                        .append($('<option />').val('%').text('%'))
                        .append($('<option />').val('px').text('px')),
            align   : $('<select />')
                        .append($('<option />').val('').text(self.rte.i18n('Not set')))
                        .append($('<option />').val('left').text(self.rte.i18n('Left')))
                        .append($('<option />').val('center').text(self.rte.i18n('Center')))
                        .append($('<option />').val('right').text(self.rte.i18n('Right'))),
            spacing : $('<input type="text" />').attr('size', 5),
            padding : $('<input type="text" />').attr('size', 5),
            border  : $('<div />'),
            bg      : $('<div />'),
            bgimg   : $('<input type="text" />').css('width', '90%')
        };

        $.each(self.src, function(n, el) {
            el.attr('name', n);
            var t = el.get(0).nodeName;
            if (t == 'INPUT' && n != 'bgimg') {
                el.css(el.attr('size') ? {'text-align' : 'right'} : {width : '100%'});
            } else if (t == 'SELECT' && n!='wunit' && n!='hunit') {
                el.css('width', '100%');
            }
        });

        self.src.bgimg.change(function() {
            var t = $(this);
            t.val(self.rte.utils.absoluteURL(t.val()));
        });

    }

    this.command = function() {
        var n = this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^TABLE$/);

        if (this.name == 'table') {
            this.table = $(this.rte.doc.createElement('table'));
        } else {
            this.table = n ? $(n) : $(this.rte.doc.createElement('table'));
        }

        !this.src && init();
        this.src.border.elBorderSelect({styleHeight : 117});
        if (!this.src.bg.is('.el-colorpicker')) {
            this.src.bg.elColorPicker({palettePosition : 'outer', 'class' : 'el-colorpicker ui-icon ui-icon-pencil'});
        }

        if (this.table.parents().length) {
            this.src.rows.val('').attr('disabled', true);
            this.src.cols.val('').attr('disabled', true);
        } else {
            this.src.rows.val(2).removeAttr('disabled');
            this.src.cols.val(2).removeAttr('disabled');
        }

        var w = this.table.css('width') || this.table.attr('width');
        this.src.width.val(parseInt(w)||'');
        this.src.wunit.val(w.indexOf('px') != -1 ? 'px' : '%');

        var h = this.table.css('height') || this.table.attr('height');
        this.src.height.val(parseInt(h)||'');
        this.src.hunit.val(h && h.indexOf('px') != -1 ? 'px' : '%');

        var f = this.table.css('float');
        this.src.align.val('');
        if (f == 'left' || f == 'right') {
            this.src.align.val(f);
        } else {
            var ml = this.table.css('margin-left');
            var mr = this.table.css('margin-right');
            if (ml == 'auto' && mr == 'auto') {
                this.src.align.val('center');
            }
        }

        this.src.border.val(this.table);

        this.src.bg.val(this.table.css('background-color'));
        var bgimg = (this.table.css('background-image')||'').replace(/url\(([^\)]+)\)/i, "$1");
        this.src.bgimg.val(bgimg!='none' ? bgimg : '');

        var d = $('<table></table>');

        var rows = [[0, 1, 2]];
        rows[0][0] = $('<table />')
            .append($('<tr />').append('<td>'+this.rte.i18n('Rows')+'</td>').append($('<td />').append(this.src.rows)))
            .append($('<tr />').append('<td>'+this.rte.i18n('Columns')+'</td>').append($('<td />').append(this.src.cols)));
        rows[0][1] = $('<table />')
            .append($('<tr />').append('<td>'+this.rte.i18n('Width')+'</td>').append($('<td />').append(this.src.width).append(this.src.wunit)))
            .append($('<tr />').append('<td>'+this.rte.i18n('Height')+'</td>').append($('<td />').append(this.src.height).append(this.src.hunit)));
        rows[0][2] = $('<table />')
            .append($('<tr />').append('<td>'+this.rte.i18n('Spacing')+'</td>').append($('<td />').append(this.src.spacing.val(this.table.attr('cellspacing')||''))))
            .append($('<tr />').append('<td>'+this.rte.i18n('Padding')+'</td>').append($('<td />').append(this.src.padding.val(this.table.attr('cellpadding')||''))));
        rows.push([this.rte.i18n('Border'), this.src.border]);
        rows.push([this.rte.i18n('Alignment'),     this.src.align]);
        rows.push([this.rte.i18n('Background'),    $('<span />').append($('<span />').css({'float' : 'left', 'margin-right' : '3px'}).append(this.src.bg)).append(this.src.bgimg)]);
        for (var i = 0; i < rows.length; i++) {
            var tr = $('<tr></tr>');
            for (var j = 0; j < rows[i].length; j++) {
                tr.append($('<td></td>').append(rows[i][j]));
            }
            d.append(tr);
        }
        d.waDialog({
            esc: true,
            width: '530px',
            height: '300px',
            'class': 'wa-elrte-dialog',
            title : this.rte.i18n('Table'),
            buttons: '<input type="submit" class="button green" value="' + this.rte.i18n('OK') + '"> ' + this.rte.i18n('or') + ' <a href="#" class="inline-link cancel"><b><i>' + this.rte.i18n('cancel') + '</i></b></a>',
            onSubmit: function (d) {
                self.set();
                d.trigger('close');
                return false;
            }
        });

    };

    this.set = function() {

        if (!this.table.parents().length) {
            var r = parseInt(this.src.rows.val()) || 0;
            var c = parseInt(this.src.cols.val()) || 0;
            if (r<=0 || c<=0) {
                return;
            }
            this.rte.history.add();
            var b = $(this.rte.doc.createElement('tbody')).appendTo(this.table);

            for (var i=0; i < r; i++) {
                var tr = '<tr>';
                for (var j=0; j < c; j++) {
                    tr += '<td>&nbsp;</td>';
                }
                b.append(tr+'</tr>');
            };

        } else {
            this.table
                .removeAttr('width')
                .removeAttr('height')
                .removeAttr('border')
                .removeAttr('align')
                .removeAttr('bordercolor')
                .removeAttr('bgcolor')
                .removeAttr('cellspacing')
                .removeAttr('cellpadding')
                .removeAttr('style');
        }

        var spacing, padding;

        if ((spacing = parseInt(this.src.spacing.val())) && spacing>=0) {
            this.table.attr('cellspacing', spacing);
        }

        if ((padding = parseInt(this.src.padding.val())) && padding>=0) {
            this.table.attr('cellpadding', padding);
        }

        var
            w = parseInt(this.src.width.val()) || '',
            h = parseInt(this.src.height.val()) || '',
            i = $.trim(this.src.bgimg.val()),
            b = this.src.border.val(),
            f = this.src.align.val();
        this.table.css({
            width              : w ? w+this.src.wunit.val() : '',
            height             : h ? h+this.src.hunit.val() : '',
            border             : $.trim(b.width+' '+b.style+' '+b.color),
            'background-color' : this.src.bg.val(),
            'background-image' : i ? 'url('+i+')' : ''
        });
        if ((f=='left' || f=='right') && this.table.css('margin-left')!='auto'  && this.table.css('margin-right')!='auto') {
            this.table.css('float', f);
        }
        if (!this.table.attr('style')) {
            this.table.removeAttr('style');
        }
        if (!this.table.parents().length) {
            this.rte.selection.insertNode(this.table.get(0), true);
        }
        this.rte.ui.update();
    };

    this.update = function() {
        this.domElem.removeClass('disabled');
        if (this.name == 'tableprops' && !this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^TABLE$/)) {
            this.domElem.addClass('disabled').removeClass('active');
        }
    };

};

elRTE.prototype.options.buttons['wa_tableprops'] = elRTE.prototype.options.buttons['tableprops'];
elRTE.prototype.ui.prototype.buttons.wa_tableprops = elRTE.prototype.ui.prototype.buttons.wa_table;


elRTE.prototype.options.buttons['wa_tbcellprops'] = elRTE.prototype.options.buttons['tbcellprops'];
elRTE.prototype.ui.prototype.buttons.wa_tbcellprops = function(rte, name) {
    this.constructor.prototype.constructor.call(this, rte, name);
    var self = this;
    this.src = null;
    this.labels = null;

    function init() {
        self.labels = {
            main    : 'Properies',
            adv     : 'Advanced',
            events  : 'Events',
            id      : 'ID',
            'class' : 'Css class',
            style   : 'Css style',
            dir     : 'Script direction',
            lang    : 'Language'
        }

        self.src = {
                type    : $('<select />').css('width', '100%')
                    .append($('<option />').val('td').text(self.rte.i18n('Data')))
                    .append($('<option />').val('th').text(self.rte.i18n('Header'))),
                width   : $('<input type="text" />').attr('size', 4),
                wunit   : $('<select />')
                    .append($('<option />').val('%').text('%'))
                    .append($('<option />').val('px').text('px')),
                height  : $('<input type="text" />').attr('size', 4),
                hunit   : $('<select />')
                    .append($('<option />').val('%').text('%'))
                    .append($('<option />').val('px').text('px')),
                align   : $('<select />').css('width', '100%')
                    .append($('<option />').val('').text(self.rte.i18n('Not set')))
                    .append($('<option />').val('left').text(self.rte.i18n('Left')))
                    .append($('<option />').val('center').text(self.rte.i18n('Center')))
                    .append($('<option />').val('right').text(self.rte.i18n('Right')))
                    .append($('<option />').val('justify').text(self.rte.i18n('Justify'))),
                border  : $('<div />'),
                padding  : $('<div />'),
                bg      : $('<div />'),
                bgimg   : $('<input type="text" />').css('width', '90%'),
                apply   : $('<select />').css('width', '100%')
                    .append($('<option />').val('').text(self.rte.i18n('Current cell')))
                    .append($('<option />').val('row').text(self.rte.i18n('All cells in row')))
                    .append($('<option />').val('column').text(self.rte.i18n('All cells in column')))
                    .append($('<option />').val('table').text(self.rte.i18n('All cells in table')))
        }

        $.each(self.src, function(n, el) {
                if (el.attr('type') == 'text' && !el.attr('size') && n!='bgimg') {
                    el.css('width', '100%')
                }
        });

    }

    this.command = function() {
        !this.src && init();
        this.cell = this.rte.dom.selfOrParent(this.rte.selection.getNode(), /^(TD|TH)$/);
        if (!this.cell) {
            return;
        }
        this.src.type.val(this.cell.nodeName.toLowerCase());
        this.cell = $(this.cell);
        this.src.border.elBorderSelect({styleHeight : 117, value : this.cell});
        this.src.bg.elColorPicker({palettePosition : 'outer', 'class' : 'el-colorpicker ui-icon ui-icon-pencil'});
        this.src.padding.elPaddingInput({ value : this.cell});

        var w = this.cell.css('width') || this.cell.attr('width');
        this.src.width.val(parseInt(w)||'');
        this.src.wunit.val(w.indexOf('px') != -1 ? 'px' : '%');

        var h = this.cell.css('height') || this.cell.attr('height');
        this.src.height.val(parseInt(h)||'');
        this.src.hunit.val(h.indexOf('px') != -1 ? 'px' : '%');

        this.src.align.val(this.cell.attr('align') || this.cell.css('text-align'));
        this.src.bg.val(this.cell.css('background-color'));
        var bgimg = this.cell.css('background-image');
        this.src.bgimg.val(bgimg && bgimg!='none' ? bgimg.replace(/url\(([^\)]+)\)/i, "$1") : '');
        this.src.apply.val('');

        var d = $('<table></table>');
        d.append($('<tr />').append('<td>'+this.rte.i18n('Width')+'</td>').append($('<td />').append($('<span />').append(this.src.width).append(this.src.wunit))));
        d.append($('<tr />').append('<td>'+this.rte.i18n('Height')+'</td>').append($('<td />').append($('<span />').append(this.src.height).append(this.src.hunit))));
        d.append($('<tr />').append('<td>'+this.rte.i18n('Table cell type')+'</td>').append($('<td />').append(this.src.type)));
        d.append($('<tr />').append('<td>'+this.rte.i18n('Border')+'</td>').append($('<td />').append(this.src.border)));
        d.append($('<tr />').append('<td>'+this.rte.i18n('Align')+'</td>').append($('<td />').append(this.src.align)));
        d.append($('<tr />').append('<td>'+this.rte.i18n('Paddings')+'</td>').append($('<td />').append(this.src.padding)));
        d.append($('<tr />').append('<td>'+this.rte.i18n('Background')+'</td>').append($('<td />').append($('<span />').append($('<span />').css({'float' : 'left', 'margin-right' : '3px'}).append(this.src.bg)).append(this.src.bgimg))));
        d.append($('<tr />').append('<td>'+this.rte.i18n('Apply to')+'</td>').append($('<td />').append(this.src.apply)));
        d.waDialog({
            esc: true,
            width: '530px',
            height: '300px',
            'class': 'wa-elrte-dialog',
            title : this.rte.i18n('Table cell properties'),
            buttons: '<input type="submit" class="button green" value="' + this.rte.i18n('OK') + '"> ' + this.rte.i18n('or') + ' <a href="#" class="inline-link cancel"><b><i>' + this.rte.i18n('cancel') + '</i></b></a>',
            onSubmit: function (d) {
                self.set();
                d.trigger('close');
                return false;
            }
        });
    }

    this.set = function() {
        // $(t).remove();
        var target = this.cell,
            apply  = this.src.apply.val();
        switch (this.src.apply.val()) {
            case 'row':
                target = this.cell.parent('tr').children('td,th');
                break;

            case 'column':
                target = $(this.rte.dom.tableColumn(this.cell.get(0)));
                break;

            case 'table':
                target = this.cell.parents('table').find('td,th');
                break;
        }

        target.removeAttr('width')
            .removeAttr('height')
            .removeAttr('border')
            .removeAttr('align')
            .removeAttr('bordercolor')
            .removeAttr('bgcolor');

        var t = this.src.type.val();
        var w = parseInt(this.src.width.val()) || '';
        var h = parseInt(this.src.height.val()) || '';
        var i = $.trim(this.src.bgimg.val());
        var b = this.src.border.val();
        var css = {
            'width'            : w ? w+this.src.wunit.val() : '',
            'height'           : h ? h+this.src.hunit.val() : '',
            'background-color' : this.src.bg.val(),
            'background-image' : i ? 'url('+i+')' : '',
            'border'           : $.trim(b.width+' '+b.style+' '+b.color),
            'text-align'       : this.src.align.val() || ''
        };
        var p = this.src.padding.val();
        if (p.css) {
            css.padding = p.css;
        } else {
            css['padding-top']    = p.top;
            css['padding-right']  = p.right;
            css['padding-bottom'] = p.bottom;
            css['padding-left']   = p.left;
        }

        target = target.get();

        $.each(target, function() {
            var type = this.nodeName.toLowerCase();
            var $this = $(this);
            if (type != t) {

                var attr = {}
                for (var i in self.src.adv) {
                    var v = $this.attr(i)
                    if (v) {
                        attr[i] = v.toString();
                    }
                }
                for (var i in self.src.events) {
                    var v = $this.attr(i)
                    if (v) {
                        attr[i] = v.toString();
                    }
                }
                var colspan = $this.attr('colspan')||1;
                var rowspan = $this.attr('rowspan')||1;
                if (colspan>1) {
                    attr.colspan = colspan;
                }
                if (rowspan>1) {
                    attr.rowspan = rowspan;
                }

                $this.replaceWith($('<'+t+' />').html($this.html()).attr(attr).css(css) );

            } else {
                $this.css(css);
            }
        });

        this.rte.ui.update();
    }

    this.update = function() {
        if (this.rte.dom.parent(this.rte.selection.getNode(), /^TABLE$/)) {
            this.domElem.removeClass('disabled');
        } else {
            this.domElem.addClass('disabled');
        }
    }

}

elRTE.prototype.options.buttons['wa_image'] = elRTE.prototype.options.buttons['image'];
elRTE.prototype.ui.prototype.buttons.wa_image = function(rte, name) {
    this.constructor.prototype.constructor.call(this, rte, name);
    var self = this,
        rte  = self.rte,
        proportion = 0,
        width = 0,
        height = 0,
        bookmarks = null,
        dialog_row = function(name, value) {
            var el = $('<div class="field"></div>')
                    .append($('<div class="name"></div>').html(name));
            if (value instanceof Array) {
                for (var i = 0; i < value.length; i++) {
                    el.append($('<div class="value"></div>').append(value[i]));
                }
            } else {
                el.append($('<div class="value"></div>').append(value));
            }
            return el;
        },
        reset = function(nosrc) {
            $.each(self.src, function(n, el) {
                if (n == 'src' && nosrc) {
                    return;
                }
                el.val('');
            });
        },
        values = function(img) {
            $.each(self.src, function(n, el) {
                var val, w, c, s, border;

                if (n == 'width') {
                    val = img.width();
                } else if (n == 'height') {
                    val = img.height();
                } else if (n == 'border') {
                    val = '';
                    border = img.css('border') || rte.utils.parseStyle(img.attr('style')).border || '';

                    if (border) {
                        w = border.match(/(\d(px|em|%))/);
                        c = border.match(/(#[a-z0-9]+)/);
                        val = {
                            width : w ? w[1] : border,
                            style : border,
                            color : rte.utils.color2Hex(c ? c[1] : border)
                        };
                    }
                } else if (n == 'align') {
                    val = img.css('float');

                    if (val != 'left' && val != 'right') {
                        val = img.css('vertical-align');
                    }
                 }else {
                    val = img.attr(n)||'';
                }

                el.val(val);
            });
        };

    this.img     = null;

    this.init = function() {
        this.src = {
            src    : $('<input type="text" />'),
            file   : $('<input name="file" type="file" />'),
            title  : $('<input type="text" />'),
            alt    : $('<input type="text" />'),
            width  : $('<input type="text" />').css('min-width', '50px').css('text-align', 'right'),
            height : $('<input type="text" />').css('min-width', '50px').css('text-align', 'right'),
            align  : $('<select />')
                        .append($('<option />').val('').text(this.rte.i18n('Not set', 'dialogs')))
                        .append($('<option />').val('left'       ).text(this.rte.i18n('Left')))
                        .append($('<option />').val('right'      ).text(this.rte.i18n('Right')))
                        .append($('<option />').val('top'        ).text(this.rte.i18n('Top')))
                        .append($('<option />').val('text-top'   ).text(this.rte.i18n('Text top')))
                        .append($('<option />').val('middle'     ).text(this.rte.i18n('middle')))
                        .append($('<option />').val('baseline'   ).text(this.rte.i18n('Baseline')))
                        .append($('<option />').val('bottom'     ).text(this.rte.i18n('Bottom')))
                        .append($('<option />').val('text-bottom').text(this.rte.i18n('Text bottom'))),
            border : $('<div />').elBorderSelect({name : 'border'})
        };
    };

    this.command = function() {
        !this.src && this.init();

        var img;
        reset();
        img = rte.selection.getEnd();

        this.img = img.nodeName == 'IMG' && !$(img).is('.elrte-protected')
            ? $(img)
            : $('<img/>');

        bookmarks = rte.selection.getBookmark();
        var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)"));
        var csrf = matches ? decodeURIComponent(matches[1]) : '';

        this.d = $('<div id="elrte-wa_image" class="fields form"></div>')
        .append(dialog_row(this.rte.i18n('Image'),[
                $("<label></label>")
                    .append('<input type="radio" name="source" value="url" checked /> ' + this.rte.i18n('URL') + ' ')
                    .append(this.src.src),
                $("<label></label>")
                    .append('<input type="radio" name="source" value="file" /> ' + this.rte.i18n('Upload') + ' ')
                    .append(this.src.file)
                    .append('<br /><span class="hint">' + ($_ ? $_('Image will be uploaded into') : this.rte.i18n('Image will be uploaded into')) + ' '+(rte.options.wa_image_upload_path?rte.options.wa_image_upload_path:'/wa-data/public/site/img/')+'</span>')]
                ))
        .append(dialog_row(this.rte.i18n('Title'), this.src.title))
        .append(dialog_row(this.rte.i18n('Alt text'), this.src.alt))
        .append(dialog_row(this.rte.i18n('Size'),
                $('<span />').append(this.src.width).append(' x ')
                             .append(this.src.height).append(' px')))
        .append(dialog_row(this.rte.i18n('Alignment'), this.src.align))
        .append(dialog_row(this.rte.i18n('Border'), this.src.border))
        .append('<input type="hidden" name="_csrf" value="' + csrf + '"/>')
        .waDialog({
            esc: true,
            width: '600px',
            height: '300px',
            'class': 'wa-elrte-dialog',
            title : this.rte.i18n('Image'),
            buttons: '<input type="submit" class="button green" value="' + this.rte.i18n('OK') + '"> ' + this.rte.i18n('or') + ' <a href="#" class="inline-link cancel"><b><i>' + this.rte.i18n('cancel') + '</i></b></a>',
            onClose: function () {
                self.bookmarks && self.rte.selection.moveToBookmark(self.bookmarks);
            },
            disableButtonsOnSubmit: true,
            onSubmit: function (d) {
                if (self.src.file.parents('div.value').find('input[name=source]:checked').val() == 'file') {
                    var f = self.src.src.parents('form');
                    var iframe = $('<iframe style="display:none" name="wa_image_upload_' + Math.random() + '"></iframe>');
                    iframe.insertAfter(f);
                    f.attr('enctype', 'multipart/form-data');
                    f.attr('target', iframe.attr('name'));
                    f.attr('action', rte.options.wa_image_upload);
                    iframe.one('load', function () {
                        var response = $.parseJSON($(this).contents().find('body').html());
                        if (response.status == 'ok') {
                            self.src.src.val(response.data);
                            self.src.height.val('');
                            self.src.width.val('');
                            self.set();
                            d.trigger('close');
                        } else if (response.status == 'fail') {
                            d.find("input[type=submit]").removeAttr('disabled');
                            alert(response.errors);
                        } else {
                            d.find("input[type=submit]").removeAttr('disabled');
                            alert('Unknown error');
                        }
                        $(this).remove();
                    });
                } else {
                    self.set();
                    d.trigger('close');
                    return false;
                }
            }
        });

        if (this.img.attr('src')) {
            values(this.img);
            proportion   = (this.img.width()/this.img.height()).toFixed(2);
            width        = parseInt(this.img.width());
            height       = parseInt(this.img.height());
        }
    };

    this.set = function() {
        var src = this.src.src.val(),
            link;

        this.rte.history.add();
        bookmarks && rte.selection.moveToBookmark(bookmarks);

        if (!src) {
            link = rte.dom.selfOrParentLink(this.img[0]);
            link && link.remove();
            return this.img.remove();
        }

        !this.img[0].parentNode && (this.img = $(this.rte.doc.createElement('img')));

        if (this.img.attr('src') != src) {
            this.img.attr('src', src);
            this.img.removeAttr('data-src');
        }

        $.each(this.src, function(name, el) {
            if (name == 'file') {
                return;
            }
            var val = el.val();

            switch (name) {
                case 'width':
                    self.img.css('width', val).attr('width', val);
                    break;
                case 'height':
                    self.img.css('height', val).attr('height', val);
                    break;
                case 'align':
                    self.img.css(val == 'left' || val == 'right' ? 'float' : 'vertical-align', val);
                    break;
                case 'border':
                    if (!val.width) {
                        val = '';
                    } else {
                        val = 'border:'+val.css+';'+$.trim((self.img.attr('style')||'').replace(/border\-[^;]+;?/ig, ''));
                        name = 'style';
                        self.img.attr('style', val);
                        return;
                    }

                    break;
                case 'src':
                    return;
                default:
                    val ? self.img.attr(name, val) : self.img.removeAttr(name);
            }
        });

        !this.img[0].parentNode && rte.selection.insertNode(this.img[0]);
        this.rte.ui.update();
    };

    this.update = function() {
        this.domElem.removeClass('disabled');
        var n = this.rte.selection.getEnd(),
            $n = $(n);
        if (n && n.nodeName == 'IMG' && !$n.hasClass('elrte-protected')) {
            this.domElem.addClass('active');
        } else {
            this.domElem.removeClass('active');
        }
    };
};
elRTE.prototype.options.buttons.youtube = 'Insert YouTube video';
elRTE.prototype.ui.prototype.buttons.youtube = function(rte, name) {
    this.constructor.prototype.constructor.call(this, rte, name);

    this.youtube_url = $('<input type="text" />').attr('name', 'youtube_url').attr('size', '40');
    this.youtube_w = $('<input type="text" />').attr('name', 'youtube_w').attr('size', '12').val("560");
    this.youtube_h = $('<input type="text" />').attr('name', 'youtube_h').attr('size', '12').val("315");
    //antoinek: needs to be commented out to prevent the button to be active in fullscreen mode
    //this.active  = true;
    var self = this;

    this.command = function() {

        var d = $('<div id="elrte-hm_youtube" class="fields form"></div>')
            .append($('<div class="field"><div class="name">'+this.rte.i18n('Youtube URL')+'</div></div>').append(
                $('<div class="value"></div>').append(this.youtube_url)
            ))
            .append($('<div class="field"><div class="name">'+this.rte.i18n('Width')+'</div></div>').append(
                $('<div class="value"></div>').append(this.youtube_w).append(' px')
            ))
            .append($('<div class="field"><div class="name">'+this.rte.i18n('Height')+'</div></div>').append(
                $('<div class="value"></div>').append(this.youtube_h).append(' px')
            ));

        d.waDialog({
            esc: true,
            width: '400px',
            height: '170px',
            className: 'wa-elrte-dialog',
            title : this.rte.i18n('Insert YouTube video'),
            buttons: '<input type="submit" class="button green" value="' + this.rte.i18n('OK') + '"> ' + this.rte.i18n('or') + ' <a href="#" class="inline-link cancel"><b><i>' + this.rte.i18n('cancel') + '</i></b></a>',
            onSubmit: function (d) {
                self.set($("input[name=youtube_url]").val(), $("input[name=youtube_w]").val(),$("input[name=youtube_h]").val());
                d.trigger('close');
                return false;
            }
        });
    }

    this.update = function() {
        this.domElem.removeClass('disabled active');
    }

    this.set = function(url, w, h) {
        var getTubeID = function(url, gkey) {
            var returned = null;
            if (url.indexOf("?") != -1) {
                var list = url.split("?")[1].split("&"),
                    gets = [];

                for (var ind in list) {
                    var kv = list[ind].split("=");
                    if (kv.length>0)
                        gets[kv[0]] = kv[1];
                }
                returned = gets;

                if (typeof gkey != "undefined")
                    if (typeof gets[gkey] != "undefined")
                        returned = gets[gkey];
            }

            return returned;
        }

        var toinsert = '<iframe width="'+w+'" height="'+h+'" src="http://www.youtube.com/embed/'+getTubeID(url, "v")+'?wmode=transparent" frameborder="0" allowfullscreen></iframe>';
        this.rte.history.add();
        this.rte.selection.insertHtml(toinsert);
    }
}
})(jQuery);
