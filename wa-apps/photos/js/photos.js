(function ($) {
    $.storage = new $.store();
    $.photos = {
        namespace: '.photos',
        load_from_hash: 0,
        hash: '',
        raw_hash: '',
        options: {},
        shift_next: false,      // signal that shift to next or prev photo
        anchor: '',             // we use hash (#) for RIA interface therefore browser-anchor mechanism doesn't work. Make own
        album: null,
        total_count: null,          // count of all VISIBLE photos in current photo-list (this counter implys taking into account RIGHTS)
        photos_per_page:null,
        list_template:'template-photo-thumbs',           //template id
        photo_list_string:{},
        init: function (options) {
            if (typeof($.History) != "undefined") {
                $.History.bind(function () {
                    $.photos.dispatch();
                });

                $.History.unbind = function (state, handler) {
                    if (handler) {
                        if ($.History.handlers.specific[state]) {
                            $.each($.History.handlers.specific[state], function (i, h) {
                                if (h === handler) {
                                    $.History.handlers.specific[state].splice(i, 1);
                                    return false;
                                }
                            });
                        }
                    } else {
                        // We have a generic handler
                        handler = state;
                        $.each($.History.handlers.generic, function (i, h) {
                            if (h === handler) {
                                $.History.handlers.generic.splice(i, 1);
                                return false;
                            }
                        });
                    }
                };

                $.History.one = function(state, handler) {
                    if (!handler) {
                        handler = state;
                        state = null;
                    }
                    var h = function() {
                        handler.call(this);
                        $.History.unbind.apply($.History, state ? [state, h] : [h]);
                    };
                    $.History.bind.apply($.History, state ? [state, h] : [h]);
                };
            }
            $.wa.errorHandler = function (xhr) {
                $.storage.del('photos/hash');
                if (xhr.status === 403) {
                    $("#content").html('<div class="content left'+$.photos_sidebar.width+'px"><div class="block double-padded">' + xhr.responseText + '</div></div>');
                    return false;
                } else {
                    if ($.photos.load_from_hash) {
                        $.wa.setHash('#/');
                        return false;
                    }
                }
                return true;
            }
            this.options = options;
            var hash = window.location.hash || $.storage.get('photos/hash');
            if (hash && hash != window.location.hash) {
                this.load_from_hash = 2;
                $.wa.setHash('#/' + hash);
            } else {
                this.dispatch();
            }

            this.container = $('#content');
            this.containerWidth = this.container.width();
            this.containerHeight = this.container.height();

            $(document).on('close', function (event) {
                $(event.target).hide();
            })
        },

        dispatch: function (hash) {
            if ($.photos.ignore_dispatch) {
                $.photos.ignore_dispatch--;
                return;
            }

            $.photos.hash = '';
            if (hash == undefined) {
                hash = window.location.hash;
            }
            hash = hash.replace(/^[^#]*#\/*/, ''); /* fix syntax highlight*/
            $.photos.raw_hash = hash;
            if (hash) {
                hash = hash.split('/');
                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    for (var i = 0; i < hash.length; i++) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else if (actionName == 'tag' || actionName == 'search' || actionName == 'plugins' || actionName == 'pages' || actionName == 'app') {
                                attrMarker = i;
                                break;
                            } else if (parseInt(h, 10) != h && h.indexOf('=') == -1) {
                                actionName += h.substr(0,1).toUpperCase() + h.substr(1);
                            } else {
                                attrMarker = i;
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }
                    var attr = hash.slice(attrMarker);
                    $.photos.hash = '/' + hash.slice(0, 2).join('/');
                    if (~$.photos.hash.indexOf('photo')) {
                        $.photos.hash = '';
                    }
                    this.beforeAnyAction(actionName, attr);
                    if (this[actionName + 'Action']) {
                        if (this.load_from_hash) {
                            this.load_from_hash--;
                        }
                        this[actionName + 'Action'].apply(this, attr);

                        // save last page to return to by default later
                        $.storage.set('photos/hash', $.photos.hash);
                    } else {
                        $.storage.del('photos/hash');
                        console && console.log('Invalid action name:', actionName+'Action');
                    }
                } else {
                    this.beforeAnyAction();
                    this.defaultAction();
                }
            } else {
                this.beforeAnyAction();
                this.defaultAction();
            }
        },

        ignore_dispatch: 0,

        forceHash: function(hash) {
            if (hash != window.location.hash) {
                $.photos.ignore_dispatch = 1;
                window.location.hash = hash;
            }
        },

        menu: {
            stack:{
                'list':[],
                'photo':[]
            },
            extend_stack: {
                'list':[],
                'photo':[]
            },
            register:function(type, selector, options) {
                if(this.stack[type]) {
                    this.stack[type].push(new ToolbarMenu(selector, options));
                }
            },
            get: function(type, selector) {
                if (this.stack[type] && $.isArray(this.stack[type])) {
                    for (var i = 0; i < this.stack[type].length; i++) {
                        if (this.stack[type][i].is(selector)) {
                            return this.stack[type][i];
                        }
                    }
                }
                return null;
            },
            init: function(type) {
                var extra_action = '';
                if (this.stack[type]){
                    while(extra_action = this.extend_stack[type].shift()) {
                        for (var id = 0; id < this.stack[type].length; id++) {
                            if(this.stack[type][id].is(extra_action.selector)) {
                                for(var action in extra_action.actions) {
                                    this.stack[type][id].setAction(action, extra_action.actions[action]);
                                }
                                break;
                            }
                        }
                    }
                    for (var id = 0; id < this.stack[type].length; id++) {
                        this.stack[type][id].init();
                    }
                }
            },
            enable: function(type, selector, actions) {
                selector = selector || '*';
                if (this.stack[type]){
                    for (var id = 0; id < this.stack[type].length; id++) {
                        var item = this.stack[type][id];
                        if(!selector || item.is(selector)){
                            item.enable(actions);
                        }
                    }
                }
            },
            disable: function(type, selector, actions) {
                selector = selector || '*';
                if (this.stack[type]){
                    for (var id = 0; id < this.stack[type].length; id++) {
                        var item = this.stack[type][id];
                        if(!selector || item.is(selector)){
                            item.disable(actions);
                        }
                    }
                }
            },
            extend: function(type, selector, actions) {
                if (this.stack[type]){
                    this.extend_stack[type].push({'selector':selector,'actions':actions});
                }
                return this;
            }
        },

        setOption: function(name, value) {
            this.options[name] = value;
        },

        getOption: function(name) {
            return this.options[name];
        },

        beforeAnyAction: function() {},

        initClearance: function() {
            $.photos.removeHeaderToolbar();
            $.photos.toggleFullScreen();
            $.photos.highlightSidebarItem();
            $.photos.hotkey_manager.unset();
            $.photos.unsetLazyLoad();
            $.photos.photo_stream_cache.clear();
            $.photos.photo_stack_cache.clear();
            delete $.photos.photo_stream_cache.hash;
        },

        defaultAction: function () {
            if (window.location.hash || !$('#album-list ul li.dr').length) {
                $.storage.set('photos/hash', 'photos/');
                this.photosAction();
            } else {
                $.storage.set('photos/hash', 'albums/');
                this.albumsAction();
            }
        },

        photosAction: function () {
            $.photos.loadDispatch(arguments, function() {
                $.photos.load("?module=photo&action=list", $.photos.onLoadPhotoList);
            });
        },

        albumsAction: function () {
            $.photos.loadDispatch(arguments, function() {
                $.photos.load("?action=albums");
            });
        },

        albumAction: function (id) {
            $.photos.loadDispatch(arguments, function() {
                $.photos.load("?module=album&action=photos&id=" + id, $.photos.onLoadPhotoList);
            });
        },

        searchAction: function (q) {
            $.photos.loadDispatch(arguments, function() {
                $.photos.load("?module=search&action=photos", {q: q}, $.photos.onLoadPhotoList);
            });
        },

        tagAction: function (name) {
            $.photos.loadDispatch(arguments, function() {
                $.photos.load("?module=tag&action=photos&tag=" + name, $.photos.onLoadPhotoList);
            });
        },

        appAction: function (app_id) {
            console.log('unused method');
            $.photos.loadDispatch(arguments, function() {
                $.photos.load("?module=photo&action=list&app_id=" + app_id, $.photos.onLoadPhotoList);
            });
        },

        settingsAction: function() {
            $.photos.initClearance();
            $.photos.load('?module=settings', $.photos.onLoadSettings);
        },

        pagesAction: function(id) {
            if ($('#wa-page-container').length) {
                waLoadPage(id);
            } else {
                $.photos.initClearance();
                $.photos.load('?module=pages', $.photos.onLoadPages, '');
            }
        },

        designAction: function(params) {
            $.photos.initClearance();
            if (params) {
                if ($('#wa-design-container').length) {
                    $.photos.setTitle($_('Themes'));
                    $.photos.scrollTop();
                    waDesignLoad();
                } else {
                    $.photos.load('?module=design', function () {
                        waDesignLoad(params);
                        $.photos.setTitle($_('Themes'));
                        $.photos.scrollTop();
                    });
                }
            } else {
                $.photos.load('?module=design', function () {
                    waDesignLoad('');
                    $.photos.setTitle($_('Design'));
                    $.photos.scrollTop();
                });
            }
        },

        designThemesAction: function(params) {
            $.photos.initClearance();
            if ($('#wa-design-container').length) {
                $.photos.setTitle($_('Themes'));
                $.photos.scrollTop();
                waDesignLoad();
            } else {
                $.photos.load('?module=design', function () {
                    waDesignLoad();
                    $.photos.setTitle($_('Themes'));
                    $.photos.scrollTop();
                }, '');
            }
        },

        pluginsAction: function(params) {
            $.photos.initClearance();
            $('#js-app-sidebar li.selected').removeClass('selected');
            $('#js-app-sidebar #sidebar-plugins').addClass('selected');
            if (!$('#wa-plugins-container').length) {
                $.photos.load("?module=plugins");
            } else {
                $.plugins.dispatch('#/plugins/' + params);
            }
        },

        photoAction: function (id) {
            $.photos.loadPhoto(id);
        },

        loadDispatch: function(delegated_arguments, loadCollectionCallback) { // delegated_arguments is an instance of Arguments
            var args = Array.prototype.slice.call(delegated_arguments, 1);
            for (var i = 0; i < args.length; i++) {
                if (args[i] === 'photo' && i < args.length - 1) {
                    this.loadPhoto(args[i + 1]);
                    return;
                }
            }
            if (typeof loadCollectionCallback == "function") {
                loadCollectionCallback(delegated_arguments[0]);
            }
            $.photos.initClearance();
        },

        onLoadSettings: function() {
            $.photos.setTitle($_('Settings'));
            $.photos.scrollTop();
        },

        onLoadPlugins: function f(id) {
            $.photos.setTitle($_('Plugins'));
            $('#plugins-settings-form').submit(function() {
                $('#plugins-settings-form-status').fadeIn(400);
                $('#plugins-settings-iframe').one('load', function() {
                    var r = $.parseJSON($(this).contents().find('body').html());
                    $('#plugins-settings-form-status').fadeOut(200, function () {
                        if (r.status == 'ok') {
                            $.photos.loadPluginSettings(id, f);
                        } else {
                            $("#plugins-settings-form-status").html(r.errors ? r.errors : r).css('color', 'red');
                            $("#plugins-settings-form-status").fadeIn("slow");
                        }
                    });
                });
            });
            $.photos.scrollTop();
        },

        onLoadPages: function() {
            $.photos.setTitle($_('Pages'));
            $.photos.scrollTop();
        },

        renderPhotoList: function() {
            var target = $("#photo-list");
            target.empty();

            $.photos.renderPhotoListChunk(target, 0);
            if ($.photos.photo_stream_cache.length() && (!$.photos.total_count || $.photos.total_count>$.photos.photo_stream_cache.length())) {
                $.photos.setLazyLoad();
            }
        },

        removeHeaderToolbar(){
            const $toolbar =$('#p-toolbar.rendered');
            if ($toolbar.length) {
                $toolbar.closest('#wa-header').removeClass('has-toolbar').end().remove();
            }
        },

        renderPhotoListChunk: function(target, offset, options, callback) {
            var chunk  = $.photos.options.photo_list_render_chunk || 10;
            var length = $.photos.photo_stream_cache.length();

            options = options || {};
            if(!options.hide_name) {
                //XXX use event instead or etc
                options.hide_name = $.storage.get('photos/list/hide_name',false);
            }

            var chunk = $.photos.photo_stream_cache.slice(offset, offset += chunk);
            var tmpl_prams = {
                photos: chunk,
                hash: $.photos.hash,
                last_login_time: $.photos.options.last_login_time,
                options:options,
                selected: !!$('#selector-menu').find('[data-action=select-photos]').data('checked'),
                view: ($.photos.list_template || '').replace('template-photo-', '')
            };
            target.append(tmpl($.photos.list_template, tmpl_prams));

            $('#content').trigger('photos_list_chunk_render', [tmpl_prams]);

            if(offset < length) {
                setTimeout(function() {
                    $.photos.renderPhotoListChunk(target, offset, options, callback);
                }, 100);
            } else {
                if (typeof callback == 'function') {
                    callback();
                }
                var string = options.string || $.photos.photo_list_string || false ;
                if (string) {
                    $('.lazyloading-wrapper').html(tmpl('template-photo-counter',{
                        count: length,
                        total_count: $.photos.total_count,
                        string: string
                    }));
                    $.photos.photo_list_string = string;
                }

            }
        },

        selectPhotoListView: function(view) {
            view = view.replace(/-view$/,'');
            $.photos.list_template = 'template-photo-'+view;
            var album = $.photos.getAlbum();
            if(album) {
                if(view == 'thumbs') {
                    $.storage.del('photos/list/view/'+album.id);
                } else {
                    $.storage.set('photos/list/view/'+album.id, view);
                }
            }
            //menu
            $.photos.menu.enable('list','.' + view + '-view-menu');
            $.photos.menu.disable('list',':not(.' + view + '-view-menu)');
            //$.photos.menu.disable('list',false,'select-photos');
            //list class
            var container = $('#photo-list');
            container.removeClass(container.attr('class'));
            switch(view) {
                case 'thumbs':{
                    container.addClass('thumbs li300px');
                    break;
                }
                case 'descriptions':{
                    container.addClass('p-descriptions');
                    break;
                }
            }
            //control
            $('#js-photos-view-toggle .selected').removeClass('selected');
            $('#js-photos-view-toggle [data-action="'+view+'-view"]').addClass('selected');
            $('.js-toolbar-dropdown').toggleClass('is-descriptions', view === 'descriptions')

        },

        onLoadPhotoList: function() {
            var album = $.photos.getAlbum();
            var view = null;
            if (album) {
                view = $.storage.get('photos/list/view/'+album.id);
                if((view != 'thumbs') && (view != 'descriptions')){
                    view = 'thumbs';
                }
                var li = $('#album-list li[rel=' + album.id + ']');
                let $count_first = li.find('.count:first');
                $count_first.text(album.count);
                if (album.status == 0) {
                    $count_first.prepend('<span class="hint"><i class="fas fa-lock"></i></span>&nbsp;')
                }
                li.find('.count-new:first').text('');
                $('#album-list-container').trigger('uncollapse_section', li);

                // Album cover selection controller
                if (album.edit_rights) {
                    $('#photo-list, .js-photo-list').on('click', '.make-key-photo-link', function() {
                        $('#photo-list .key-photo').removeClass('key-photo');
                        let $li = $(this).closest('li').addClass('key-photo');
                        album.key_photo_id = $li.data('photo-id');
                        $.post('?module=album&action=keyPhoto', { album_id: album.id, photo_id: album.key_photo_id });
                    });
                }
            }
            if(!view) {
                view = 'thumbs';
            }
            if (!$('#template-photo-'+view).length) {
                $("#content").empty();
                $.wa.setHash('#/');
                return;
            }
            $.photos.list_template = 'template-photo-'+view;

            $.photos.renderPhotoList();

            $.photos.setTitle($('#photo-list-name').text());
            $.photos.menu.init('list');
            $.photos.selectPhotoListView(view);

            const $toggle = $("#js-photos-view-toggle");

            $toggle.waToggle({
                change: function(event, target, toggle) {
                    let new_view = $(target).attr('data-action');
                    if(new_view !== view) {
                        view = new_view.replace(/-view$/,'');
                        $.photos.list_template = 'template-photo-' + view;
                        $.photos.renderPhotoList();
                        $.photos.selectPhotoListView(view);
                    }
                }
            });

            $('#photo-list').on('click', '.js-description div, .js-description-editable', function() {
                var self = $(this),
                    height = $(this).height(),
                    placeholder = $_('add description');

                $(this).inlineEditable({
                    inputType: 'textarea',
                    makeReadableBy: ['esc'],
                    updateBy: ['ctrl+enter'],
                    placeholder: placeholder,
                    placeholderClass: 'gray',
                    minSize: {
                        height: 40
                    },
                    html: false,
                    allowEmpty: true,
                    beforeMakeEditable: function(input) {
                        var self = $(this),
                            full_text = self.parent().find('.full-description:first').html(),
                            size = self.css('font-size'),
                            line_height = self.css('line-height');

                        input.css({
                            'font-size': size,
                            'line-height': line_height,
                            'max-width': '100%'
                        });
                        self.html(full_text);

                        var width = Math.max(self.parents('li:first').find('img').width(), self.width());
                        input.width(width);

                        var button_id = this.id + '-button',
                            button = $('#' + button_id);
                        if (!button.length) {
                            input.after('<input class="button smallest" type="button" id="' + button_id + '" value="' + $_('Save') + '"> <em class="hint" id="' + this.id + '-hint">Ctrl+Enter</em>');
                            $('#' + button_id).on('click', function() {
                                self.trigger('readable');
                            });
                        }
                        $('#'+this.id+'-hint').show();
                        button.show();
                    },
                    afterBackReadable: function(input, data) {
                        var button_id = this.id + '-button',
                            button = $('#' + button_id),
                            self = $(this),
                            value = $(input).val(),
                            href = self.parents('li:first').find('.p-image').attr('data-href'),
                            match = /(\d+)[\/]*$/.exec(href),
                            id = null;

                        button.hide();
                        $('#'+this.id+'-hint').hide();
                        if (value) {
                            // self.text(value.truncate(255));
                            self.parent().find('.js-full-description:first').text(value).html();
                        } else {
                            self.parent().find('.js-full-description:first').html('');
                        }

                        if (match && data.changed) {
                            id = match[1];
                            $.photos.saveField({
                                id: id,
                                type: 'photo',
                                name: 'description',
                                value: value,
                                fn: function() {}
                            });
                        }
                    },
                    hold: function() {
                        return !this.hasClass('editable');
                    }
                }).trigger('editable');

            });

            var album_name = $('#photo-list-name');
            if (album_name.hasClass('editable')) {
                album_name.inlineEditable({
                    minSize: {
                        width: 350
                    },
                    maxSize: {
                        width: 600
                    },
                    size: {
                        height: 30
                    },
                    afterBackReadable: function(input, data) {
                        if (!data.changed) {
                            return false;
                        }
                        var value = $(input).val(),
                            album = $.photos.getAlbum();
                        if (album) {
                            $.photos.saveField({
                                id: album.id,
                                type: 'album',
                                name: 'name',
                                value: value,
                                fn: function(r) {
                                    if (r.status == 'ok') {
                                        var album = r.data.album;

                                        // Update album name in sidebar
                                        $('#album-list li[rel='+album.id+'] > a > .album-name').html(album.name);

                                        // update album-list in upload-form
                                        $('#p-upload-step2 select[name=album_id] option[value='+album.id+']').html(album.name);

                                        // Update page title
                                        $.photos.setTitle(album.not_escaped_name);
                                    }
                                }
                            });
                        }
                    },
                    hold: function() {
                        return !this.hasClass('editable');
                    }
                });
            }
            var album_note = $('#photo-album-note');
            if (album_note.hasClass('editable')) {
                album_note.inlineEditable({
                    placeholder: '(' + $_('subtitle') + ')',
                    placeholderClass: 'gray',
                    minSize: {
                        width: 150
                    },
                    maxSize: {
                        width: 250
                    },
                    size: {
                        height: 22
                    },
                    afterBackReadable: function(input, data) {
                        if (!data.changed) {
                            return false;
                        }
                        var value = $(input).val(),
                            album = $.photos.getAlbum();
                        if (album) {
                            $.photos.saveField({
                                id: album.id,
                                type: 'album',
                                name: 'note',
                                value: value
                            });
                        }
                    },
                    hold: function() {
                        return !this.hasClass('editable');
                    }
                });
            }
            $('.js-toolbar-dropdown-button').on('recount', function() {
                let cnt = $('#photo-list > li.selected').length,
                    $count = $(this).find('.js-count');

                if (cnt > 0) {
                    $count.text(cnt).show();
                    //$.photos.menu.enable('list',false,'select-photos');
                } else {
                    $count.hide();
                    //$.photos.menu.disable('list',false,'select-photos');
                }
            });

            $('#p-album-settings').click(function() {
                var album = $.photos.getAlbum(),
                    album_id = album ? album.id : 0,

                    showDialog = function () {
                        $.waDialog({
                            html: $("#album-settings-dialog"),
                            onOpen($dialog, dialog) {
                                let $form = $dialog.find('form');

                                $form.find('.js-privacy-settings-link').on('click', function () {
                                    $(window).resize()
                                });

                                $form.on('submit', function (e) {
                                    e.preventDefault();
                                    $dialog.trigger('change_loading_status', true);
                                    $.post($form.attr('action'), $form.serializeArray(), function(r) {
                                        if (r.status == 'ok') {
                                            let total_count = r.data.total_count,
                                                status = r.data.status,
                                                groups = r.data.groups,
                                                count = r.data.count,
                                                offset = count;

                                            function process(data) {
                                                $.post('?module=album&action=savePhotosAccess', data, function() {
                                                    data.offset += count;
                                                    if (data.offset <= total_count) {
                                                        process(data);
                                                    } else {
                                                        $dialog.trigger('change_loading_status', false);
                                                        location.reload();
                                                    }
                                                });
                                            }
                                            if (count < total_count) {
                                                process({
                                                    id: album_id,
                                                    status: status,
                                                    groups: groups,
                                                    count: count,
                                                    offset: offset
                                                });
                                            } else {
                                                $dialog.trigger('change_loading_status', false);
                                                location.reload();
                                            }
                                        } else if (r.status == 'fail') {
                                            $dialog.trigger('change_loading_status', false);
                                            let errors = r.errors;
                                            for (let name in errors) {
                                                if (errors.hasOwnProperty(name)) {
                                                    $dialog
                                                        .find('input[name=' + name + ']')
                                                        .addClass('error')
                                                        .parent()
                                                        .find('.errormsg')
                                                        .text(errors[name]);
                                                }
                                            }
                                        }
                                    }, 'json');
                                });
                            }
                        });
                    };

                    //album-settings-dialog
                    var d = $('#album-settings-dialog-acceptor');
                    if (!d.length) {
                        d = $("<div id='album-settings-dialog-acceptor'></div>");
                        $("body").append(d);
                    }
                    d.load('?module=dialog&action=albumSettings&id=' + album_id, showDialog);
                    return false;
            });

            // fix prevent browser-action
            $('#photo-list').find('.p-description textarea, .p-photo-details textarea, .p-photo-details input').on('select', function() {
                return false;
            });

            $.photos.hotkey_manager.set('rate');
            // if we go from some photo back to collection do not scroll top
            if ($.photos.hash != $.photos.photo_stream_cache.hash) {
                $.photos.scrollTop();
            }

            $('#content').trigger('photos_list_load');

            const $highlighted = $('#photo-list').find('li.highlighted');
            if ($highlighted.length) {
                $('.i-product-review-widget-wrappper').show();
            } else {
                $('.i-product-review-widget-wrappper').hide();
            }
        },

        loadPhoto: function (id) {
            // check case when jumping from photo-page of one collection
            // to photo-page of another collection by passing the going to page of collection (photo-list)
            if ($.photos.photo_stream_cache.hash !== $.photos.hash) {
                $.photos.loadNewPhoto(id);
                return;
            }

            const stream = $.photos.photo_stream_cache.getById(id);
            if (!stream || stream.stack_count > 0) {
                // navigation through stack
                const stack = $.photos.photo_stack_cache.getById(id);
                if (stack) {
                    this.setTitle(stack.name);
                    $.photos.loadPhotoInStack(stack);
                    return;
                }
            }

            if (stream) {
                $.photos.setTitle(stream.name);
                $.photos.loadPhotoCompletly(stream);
            }
        },

        loadPhotoInStack: function f(photo) {
            $.photos.abortPrevLoading();
            /**
             * @hook
             */
            $.photos.hooks_manager.trigger('beforeLoadPhoto', photo);
            $.photos.photo_stack_cache.setCurrent(photo);
            var proper_thumb = $.photos._chooseProperThumb(photo);
            let $photo = $('#photo');

            $photo.smallSize = (photo.width < this.containerWidth && photo.height < this.containerHeight);

            $photo.proper_thumb = {
                url: proper_thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                url2x: proper_thumb.url2x + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : '')
            }

            replaceImg(
                $photo,
                proper_thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                null
            );

            if ((photo.thumb_big.size.width < photo.width) || (photo.thumb_big.size.height < photo.height) ) {
                $photo.addClass('contain');
            } else {
                $photo.removeClass('contain');
            }

            $photo.on('load', function() {
                $(this).addClass('fade-in');
                $(this).on('animationend', function() {
                    $(this).removeClass('fade-in');
                });
            });

            $.photos.setNextPhotoLink();
            var xhr = $.post('?module=photo&action=load', { id: photo.id, in_stack: 1 },
                function(r) {
                    var data = r.data,
                        photo = data.photo;


                    photo = $.photos.photo_stack_cache.updateById(photo.id, photo);
                    data.photo = photo;

                    $.photos.updateViewChildPhoto(data);

                    const isFirst = () => data.photo_stream.photos[0].id === photo.id
                    const isLast = () => data.photo_stream.photos[data.photo_stream.photos.length - 1].id === photo.id
                    $.photos.hooks_manager.trigger('afterLoadPhoto', { first: isFirst(), last: isLast() });
                    delete f.xhr;
                },
                'json'
            );
            f.xhr = xhr;
        },

        updateViewChildPhoto: function(data) {
            var author = data.author,
                albums = data.albums,
                exif = data.exif,
                photo = data.photo;

            $.photos.renderPhotoImg(photo);

            $('#photo-author').html(tmpl('template-photo-author', {
                photo: photo,
                author: author
            }));

            $.photos.updatePhotoOriginalBlock(photo);

            if(exif.length) {
                $('#photo-exif').html(tmpl('template-photo-exif', {
                    exif: exif
                }));
                $.photos.renderMap(exif.GPSLatitude, exif.GPSLongitude, photo.name);
            }

            $('#photo-albums').html(tmpl('template-photo-albums', {
                albums: albums
            }));

            $.photos.initPhotoContentControlWidget({
                frontend_link_template: data.frontend_link_template,
                photo: photo
            });

            $.photos.updatePhotoName(photo.name, photo.edit_rights);
            $.photos.initPhotoStackWidgetActiveItem(photo.index);
        },

        loadNewPhoto: function method(id) {
            $.photos.initClearance();
            $.photos.widget.loupe.init();

            method.xhr_map = method.xhr_map || {};
            if (method.xhr_map[id]) {
                method.xhr_map[id].abort();
            }

            method.xhr_map[id] = $.post('?module=photo&action=load', { id: id, hash: $.photos.hash },
                function(r) {

                    method.xhr_map[id] = null;

                    var data = r.data,
                        photo = data.photo;

                    $.photos.renderViewPhoto(data);

                    // preloading next photo
                    const next_photo = $.photos.photo_stream_cache.getNext(photo);
                    if (next_photo) {
                        $.photos.preloadPhoto(next_photo);
                    }

                    // preloading prev photo
                    const prev_photo = $.photos.photo_stream_cache.getPrev(photo);
                    if (prev_photo) {
                        $.photos.preloadPhotoPrev(prev_photo);
                    }

                    if (data.album) {
                        $.photos.setAlbum(data.album);
                    }

                    $.photos.updateViewPhoto(data, false);

                    const isFirst = () => data.photo_stream.photos[0].id === id;
                    const isLast = () => data.photo_stream.photos[data.photo_stream.photos.length - 1].id === id;
                    $.photos.hooks_manager.trigger('afterLoadPhoto', { first: isFirst(), last: isLast() });
                },
            'json');
        },

        renderViewPhoto: function(data) {
            var photo = data.photo,
                author = data.author,
                exif = data.exif,
                stack = data.stack,
                albums = data.albums,
                album = data.album,
                hooks = data.hooks,
                frontend_link_template = data.frontend_link_template,
                photo_stream = data.photo_stream,
                in_collection = photo_stream.in_collection;

            // get alive next/prev action if comes from menu
            $.photos.photo_stream_cache.set(photo_stream.photos);

            let not_in_dynamic_album = false;

            if (album && !in_collection) {
                if (album.type == Album.TYPE_STATIC && !stack) {
                    $.photos.goToHash('album/' + album.id + '/');
                    return;
                } else {
                    not_in_dynamic_album = true;
                }
            }

            $.photos.setTitle(photo.name_not_escaped);
            $('#content').html(tmpl('template-p-block'));

            $.photos.renderPhotoBlock({
                photo,
                hash: $.photos.hash,
                author,
                exif,
                albums,
                hooks,
                frontend_link_template: frontend_link_template
            });

            $.photos.initPhotoToolbar({
                photo,
                stack,
                hash: $.photos.hash
            });

            $.photos.initPhotoWidgets({
                photo,
                stack,
                exif,
                photo_stream,
                hash: $.photos.hash
            });

            if ($.photos.anchor) {
                $.photos.goToAnchor($.photos.anchor);
                $.photos.anchor = '';
            } else {
                $.photos.scrollTop();
            }

            $('#p-warning-not-in-album').hide();
            if (not_in_dynamic_album) {
                $('#p-warning-not-in-album').show();
                $.photos.setNextPhotoLink($.photos.photo_stream_cache.getCurrent());
            } else {
                $.photos.setNextPhotoLink();
            }

        },

        loadPhotoCompletly: function(photo) {
            $.photos.photo_stream_cache.setCurrent(photo);
            // make shift inside photo-stream
            $.photos.updatePhotoStreamWidget({
                shift: photo.id,
                fn: function() {
                    $.photos._loadPhotoCompletly(photo);
                }
            });
        },

        _loadPhotoCompletly: function f(photo) {
            $.photos.abortPrevLoading();
            /**
             * @hook
             */
            $.photos.hooks_manager.trigger('beforeLoadPhoto', photo);

            $('#p-warning-not-in-album').hide();

            $.photos.setNextPhotoLink();
            $.photos.updatePhotoName(photo.name);
            $.photos.updatePhotoDescription(photo.description);
            $.photos.updatePhotoRate(photo.rate);

            $('#photo-frontend-url').html(photo.url);
            // clean stack-stream
            $('#stack-stream').hide();
            // clean prev hooks results
            $('#photo-hook-bottom').html('');
            var is_preloaded = $.photos.isPhotoPreloaded(photo);
            var is_prevPreloaded = $.photos.isPrevPhotoPreloaded(photo);

            if (is_preloaded || is_prevPreloaded) {
                $.photos.renderPhotoImg(photo, is_preloaded, is_prevPreloaded);
            } else {
                var proper_thumb = $.photos._chooseProperThumb(photo),
                    $photo = $('#photo');

                $photo.smallSize = (photo.width < this.containerWidth && photo.height < this.containerHeight);

                $photo.proper_thumb = {
                    url: proper_thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                    url2x: proper_thumb.url2x + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : '')
                }

                replaceImg(
                    $photo,
                    proper_thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                    function(){
                        if ((photo.thumb_big.size.width < photo.width) || (photo.thumb_big.size.height < photo.height) ) {
                            $photo.addClass('contain');
                        } else {
                            $photo.removeClass('contain');
                        }

                        const is_prevInStack = $.photos.photo_stack_cache.getPrev(photo);
                        const is_nextInStack = $.photos.photo_stack_cache.getNext(photo);
                        if (is_prevInStack || is_nextInStack) {
                            $photo.on('load', function() {
                                $(this).addClass('fade-in');
                                $(this).on('animationend', function() {
                                    $(this).removeClass('fade-in');
                                });
                            });
                        }
                    }
                );
            }
            $.photos.updatePhotoTags(photo.tags);

            // preload next photo
            const next_photo = $.photos.photo_stream_cache.getNext();
            if (next_photo) {
                $.photos.preloadPhoto(next_photo);
            }

            const prev_photo = $.photos.photo_stream_cache.getPrev();
            if (prev_photo) {
                $.photos.preloadPhotoPrev(prev_photo);
            }

            // load addition info about photo
            var xhr = $.post('?module=photo&action=load', { id: photo.id, hash: $.photos.hash },
                function (r) {
                    if (r.status == 'ok') {
                        var data = r.data,
                            album = data.album,
                            photo = data.photo,
                            photo_stream = data.photo_stream,
                            in_collection = photo_stream.in_collection;

                        var not_in_dynamic_album = false;
                        if (album && !in_collection) {
                            if (album.type == Album.TYPE_STATIC) {
                                $.photos.goToHash('album/' + album.id + '/');
                                return;
                            } else {
                                not_in_dynamic_album = true;
                            }
                        }

                        if (not_in_dynamic_album) {
                            $('#p-warning-not-in-album').show();
                            $.photos.setNextPhotoLink($.photos.photo_stream_cache.getCurrent());
                        } else {
                            $.photos.setNextPhotoLink();
                        }

                        photo = $.photos.photo_stream_cache.updateById(photo.id, photo);
                        data.photo = photo;
                        $.photos.updateViewPhoto(data, is_preloaded);

                        /**
                         * @hook
                         */
                        const isFirst = () => data.photo_stream.photos[0].id === photo.id
                        const isLast = () => data.photo_stream.photos[data.photo_stream.photos.length - 1].id === photo.id
                        $.photos.hooks_manager.trigger('afterLoadPhoto', { first: isFirst(), last: isLast() });
                        delete f.xhr;
                    }
                },
                'json'
            );
            f.xhr = xhr;
        },

        updateViewPhoto: function(data, is_preloaded) {
            var author = data.author,
                albums = data.albums,
                exif = data.exif,
                frontend_link_template = data.frontend_link_template,
                hooks = data.hooks,
                stack = data.stack,
                photo = data.photo;

            if (!is_preloaded) {
                $.photos.renderPhotoImg(photo);
            }

            // update toolbars menus
            $.photos.updateViewPhotoMenu((photo.parent_id != 0 || photo.stack_count > 0), photo.edit_rights);

            $.photos.updatePhotoName(photo.name, photo.edit_rights);
            $.photos.updatePhotoDescription(photo.description, photo.edit_rights);
            $.photos.updatePhotoRate(photo.rate, photo.edit_rights);
            $.photos.updatePhotoTags(photo.tags);

            // update photo's author info
            $('#photo-author').html(tmpl('template-photo-author', {
                photo: photo,
                author: author
            }));

            $.photos.updatePhotoOriginalBlock(photo);

            // update-exif
            $('#photo-exif').html(tmpl('template-photo-exif', {
                exif: exif
            }));
            $.photos.renderMap(exif.GPSLatitude, exif.GPSLongitude, photo.name);
            // update albums
            $('#photo-albums').html(tmpl('template-photo-albums', {
                albums: albums
            }));

            $.photos.initPhotoContentControlWidget({
                frontend_link_template: frontend_link_template,
                photo: photo
            });

            // update stack if it is necessary
            if (stack) {
                // show and init photo-stack widget
                $('#stack-stream').html(tmpl('template-photo-stack', {
                    stack: stack,
                    photo_id: photo.id,
                    hash: $.photos.hash
                })).show();
                $.photos.photo_stack_cache.set(stack);
                $.photos.initPhotoStackWidget();
            } else {
                // clear previous stack
                $.photos.photo_stack_cache.clear();
            }

            // insert hooks
            var html_hooks = '';
            for (var plugin in hooks.backend_photo) {
                if (hooks.backend_photo[plugin].bottom) {
                    html_hooks += hooks.backend_photo[plugin].bottom;
                }
            }
            $('#photo-hook-bottom').html(html_hooks);

            html_hooks = '';
            for (var plugin in hooks.backend_photo) {
                if (hooks.backend_photo[plugin].after_rate) {
                    html_hooks += hooks.backend_photo[plugin].after_rate;
                }
            }
            if (html_hooks) {
                $('#photo-rate').nextAll().remove();
                $('#photo-rate').after(html_hooks);
            }

            // go by anchor
            if ($.photos.anchor) {
                $.photos.goToAnchor($.photos.anchor);
                $.photos.anchor = '';
            }
        },

        renderPhotoImg: function(photo, preload, prevPreload) {
            let proper_thumb = $.photos._chooseProperThumb(photo),
                $photo = $('#photo');

            $photo.smallSize = (photo.width < this.containerWidth && photo.height < this.containerHeight);

            $photo.proper_thumb = {
                url: proper_thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                url2x: proper_thumb.url2x + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : '')
            }

            replaceImg(
                $photo,
                proper_thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                function() {
                    if ((photo.thumb_big.size.width < photo.width) || (photo.thumb_big.size.height < photo.height) ) {
                        $photo.addClass('contain');
                    } else {
                        $photo.removeClass('contain');
                    }

                    if (preload || prevPreload) {
                        $photo.on('load', function() {
                            $(this).addClass('fade-in');
                            $(this).on('animationend', function() {
                                $(this).removeClass('fade-in');
                            });
                        });
                    }

                    $.photos.hooks_manager.trigger('afterRenderImg', this, photo, proper_thumb);
                }
            );
        },

        updateViewPhotoMenu: function(stack, edit) {
            // update toolbars menus
            if (stack) {
                $.photos.menu.enable('photo','#photo-organize-menu','unstack');
            } else {
                $.photos.menu.disable('photo','#photo-organize-menu','unstack');
            }
            if (edit) {
                $.photos.menu.enable('photo','#photo-organize-menu');
                $.photos.menu.enable('photo','#edit-menu');
                // $('#photo-tags').parent().show();
            } else {
                $.photos.menu.disable('photo','#photo-organize-menu');
                $.photos.menu.disable('photo','#edit-menu');
                // $('#photo-tags').parent().hide();
            }
        },

        preloadPhoto: function(photo) {
            var preload_photo_img = $('#preload-photo');
            preload_photo_img.attr('data-photo-id', '');

            preload_photo_img.smallSize = (photo.width < this.containerWidth && photo.height < this.containerHeight);

            replaceImg(
                preload_photo_img,
                photo.thumb_big.url,
                function() {
                    preload_photo_img.attr('data-photo-id', photo.id);
                }
            );
        },

        preloadPhotoPrev: function(photo) {
            var preload_photo_img = $('#preload-photo-prev');
            preload_photo_img.attr('data-photo-id', '');

            preload_photo_img.smallSize = (photo.width < this.containerWidth && photo.height < this.containerHeight);

            replaceImg(
              preload_photo_img,
              photo.thumb_big.url,
              function() {
                  preload_photo_img.attr('data-photo-id', photo.id);
              }
            );
        },

        isPhotoPreloaded: function(photo) {
            return $('#preload-photo').attr('data-photo-id') == photo.id;
        },

        isPrevPhotoPreloaded: function(photo) {
            return $('#preload-photo-prev').attr('data-photo-id') == photo.id;
        },

        abortPrevLoading: function() {
            if (typeof $.photos._loadPhotoCompletly.xhr === 'object' && typeof $.photos._loadPhotoCompletly.xhr.abort === 'function')
            {
                $.photos._loadPhotoCompletly.xhr.abort();
                /**
                 * @hook
                 */
                $.photos.hooks_manager.trigger('onAbortPrevLoading');
            }
            if (typeof $.photos.loadPhotoInStack.xhr === 'object' && typeof $.photos.loadPhotoInStack.xhr.abort === 'function')
            {
                $.photos.loadPhotoInStack.xhr.abort();
                /**
                 * @hook
                 */
                $.photos.hooks_manager.trigger('onAbortPrevLoading');
            }
        },

        updateThumbRate: function(li, rate) {
            let rate_item = li.find('.p-details .p-rate');
            rate = Math.round(rate * 2) / 2;
            if (!rate) {
                rate_item.hide();
            } else {

                let stars = rate_item
                    .find('svg')
                    .removeClass('fa-star-half text-yellow')
                    .addClass('text-light-gray');

                stars.each(function(i) {
                    i += 1;
                    if (i > rate) {
                        if (i - rate == 0.5) {
                            $(this).addClass('fa-star-half').removeClass('text-light-gray').addClass('text-yellow');
                        }
                    } else {
                        $(this).removeClass('fa-star-half').removeClass('text-light-gray').addClass('text-yellow');
                    }
                    $(this).attr('data-rate-value', i)
                });

                rate_item.show();
            }
        },

        showManageAccessDialog: function(params, onSubmit) {
            const showDialog = function () {
                $.waDialog({
                    $wrapper: $("#manage-access-dialog"),
                    onOpen($dialog, dialog) {
                        let $form = $dialog.find('form')
                        $form.find('.js-privacy-settings-link').on('click', function () {
                            $(window).resize()
                        });
                        $form.on('submit', function (e) {
                            e.preventDefault();
                            onSubmit($dialog, dialog)
                        });
                    }
                });
            };
            //manage-access-dialog
            let d = $('#manage-access-dialog-acceptor'),
                url = '?module=dialog&action=manageAccess';

            if (!d.length) {
                d = $("<div id='manage-access-dialog-acceptor'></div>");
                $("body").append(d);
            }

            if (params) {
                if ($.isArray(params)) {
                    for (let i = 0, n = params.length; i < n; ++i) {
                        let param = params[i];
                        url += '&' + param.name + '=' + param.value;
                    }
                } else {
                    url += '&' + params;
                }
            }
            d.load(url, showDialog);
        },

        initPhotoNameWidget: function(edit_status) {
            edit_status = typeof edit_status === 'undefined' ? false : edit_status;
            $('#photo-name').inlineEditable({
                placeholder: $('#photo-name').text() ? null : $_('Edit title...'),
                placeholderClass: 'gray',
                html: false,
                afterBackReadable: function(input, e) {
                    if (!e.changed) {
                        return;
                    }
                    var value = $(input).val();
                    if (!value.length) {
                        return;
                    }
                    $.photos.saveField({
                        id: $.photos.getPhotoId(),
                        type: 'photo',
                        name: 'name',
                        value: value.replace(/&/g, '&amp;').replace(/\s/g,' ').replace(/</g, '&lt;').replace(/>/g, '&gt;'),
                        fn: function(r) {
                            $('#photo-name').inlineEditable('setOption', {
                                placeholder: null
                            });
                            if (r.status == 'ok') {
                                $.photos.setTitle(r.data.value);
                            }
                        }
                    });
                },
                minSize: {
                    width: 350
                },
                maxSize: {
                    width: 600
                },
                size: {
                    height: 30
                },
                hold: function() {
                    return !this.hasClass('editable');
                }
            });
            $.photos.updatePhotoName(edit_status);
        },

        initPhotoDescriptionWidget: function(edit_status) {
            edit_status = typeof edit_status === 'undefined' ? false : edit_status;
            $('#photo-description').inlineEditable({
                placeholder: $_('add description'),
                placeholderClass: 'gray',
                makeReadableBy: ['esc'],
                updateBy: ['ctrl+enter'],
                html: false,
                allowEmpty: true,
                beforeMakeEditable: function(input) {
                    var button = $('#photo-description-save');
                    if (!button.length) {
                        var widget = $(this);
                        input.after('<input type="button" id="photo-description-save" class="button smallest" value="' + $_('Save') + '"> <em class="hint" id="' + this.id + '-hint">Ctrl+Enter</em>');
                        button = $('#photo-description-save');
                        button.click(function() {
                            widget.trigger('readable');
                        });
                    }
                    $('#'+this.id+'-hint').show();
                    button.show().prev('br').show();
                },
                afterBackReadable: function(input, status) {
                    $('#photo-description-save').hide().prev('br').hide();
                    $('#'+this.id+'-hint').hide();
                    if (!status.changed) {
                        return false;
                    };
                    var value = $(input).val();
                    // when parent in stack save this value for all photos in stack
                    $.photos.saveField({
                        id: $.photos.photo_stream_cache.getCurrent().id,
                        type: 'photo',
                        name: 'description',
                        value: $(input).val().replace(/&/g, '&amp;').replace(/\s/g,' ').replace(/</g, '&lt;').replace(/>/g, '&gt;'),
                        fn: function() {}
                    });
                },
                minSize: {
                    height: 50,
                    width: 600
                },
                size: {
                    width: $('#photo').width()
                },
                inputType: 'textarea',
                editLink: '#photo-description-edit-link',
                hold: function() {
                    return !this.hasClass('editable');
                }
            });
            $.photos.updatePhotoDescription(edit_status);
        },

        initPhotoRateWidget: function(edit_status) {
            edit_status = typeof edit_status === 'undefined' ? false : edit_status;
            var update = function(rate) {
                // when parent in stack save this value for all photos in stacks
                $.photos.saveField($.photos.photo_stream_cache.getCurrent().id, 'rate', rate,
                    function (r) {
                        if (r.status == 'ok' && r.data.count) {
                            $('#rated-count').text(r.data.count > 0 ? r.data.count : '');
                        }
                    }
                );
            };
            var photo_rate = $('#photo-rate');
            photo_rate.rateWidget({
                onUpdate: update,
                hold: function() {
                    return this.hasClass('hold');
                }
            });
            $.photos.updatePhotoRate(edit_status);
        },

        initPhotoStackWidget: function(data) {
            if (typeof data === 'object' && data) {
                $('#stack-stream').html(tmpl('template-photo-stack', data)).show();
            }
            //template-photo-stack
            var href = $('#stack-stream li.dr:first a').attr('href');
            var match = /(\d+)[\/]*$/.exec(href);
            if (match) {
                parent_id = match[1];
            }
            $('#stack-stream').data('parent_id', parent_id);
            $("#stack-stream").sortable({
                distance: 5,
                helper: 'clone',
                items: 'li.dr',
                opacity: 0.75,
                tolerance: 'pointer',
                onEnd: function (ui) {
                    var li = $(ui.item);
                    var id = parseInt(li.attr('data-photo-id'));
                    var next = li.next(),
                        before_id = null;
                    if (next.length) {
                        before_id = parseInt(next.attr('data-photo-id'));
                    }
                    $.post('?module=stack&action=photoMove',
                        {
                            id: id,
                            before_id: before_id
                        },
                        function (response) {
                            if (response.status !== 'ok') {
                                return;
                            }

                            if (!response.data.stack) {
                                return;
                            }

                            const stack = response.data.stack;
                            $.photos.photo_stack_cache.set(stack).setCurrent(response.data.stack[0]);

                            const oldPhoto = $.photos.photo_stream_cache.getById(parent_id);
                            const newPhoto = $.photos.photo_stack_cache.getAll()[0];

                            $.photos.photo_stream_cache.replace(oldPhoto, newPhoto);
                        },
                    "json");
                }
            });
            if ($('#stack-stream').data('inited')) {
                return false;
            }

            $('#stack-stream').data('inited', true);
        },

        initPhotoStackWidgetActiveItem: function(id) {
            $(document).find('#stack-stream li.dr').removeClass('selected').siblings(`li:nth-child(${id + 1})`).addClass('selected');
        },

        initPhotoTagsWidget: function(tags) {
            var tags_input = $('#photo-tags'),
                default_text = $_('add a tag');

            if (typeof tags !== 'undefined') {
                if (typeof tags === 'object' && tags) {
                    tags = $.photos.joinObject(tags, ',');
                }
                tags_input.importTags(tags);
            }

            tags_input.data('current_value', tags_input.val());

            const pop_tags = $('#photos-photo-popular-tags');
            if (!pop_tags.data('inited')) {
                pop_tags.off('click.photos', 'a').
                on('click.photos', 'a', function() {
                    var name = $(this).text();
                    var tags_input = $('#photo-tags')
                    tags_input.removeTag(name);
                    tags_input.addTag(name);
                }).data('inited', true);
            }

            var onUserChange = function () {
                if (tags_input.data('current_value') === tags_input.val()) {
                    return;
                }
                tags_input.data('current_value', tags_input.val());

                $('#photo-save-tags-status').html('<p class="state-success"><i class="fas fa-check-circle custom-mr-4"></i>'+$_('Saving')+'</p>').fadeIn('slow');
                $.photos.assignTags({
                    photo_id: $.photos.getPhotoId(),
                    tags: tags_input.val(),
                    fn: function() {
                        $('#photo-save-tags-status').html('<p class="state-success"><i class="fas fa-check-circle custom-mr-4"></i>'+$_('Saved')+'</p>').fadeOut('slow');
                    },
                    onDeniedExist: function() {
                        alert($_("You don't have sufficient access rights"));
                    }
                });
            };

            try {
                tags_input.tagsInput({
                    autocomplete_url: '?module=tag&action=list',
                    width: '100%',
                    defaultText: default_text,
                    onChange: function () {
                        // this event calls every time list of tags is changed, event by importTags, so don't use it for ajax!
                    },
                    onAddTag: function () {
                        onUserChange();
                    },
                    onRemoveTag: function () {
                        onUserChange();
                    }
                });
            } catch (e) {
                ;
            }
        },

        initPhotoContentControlWidget: function(data) {
            if (typeof data === 'object' && data) {
                $('#photo-content-control').html(tmpl('template-photo-content-control', data));
                edit_rights = data.photo.edit_rights;
            } else {
                edit_rights = data;
            }
            edit_rights = typeof edit_rights === 'undefined' ? false : edit_rights;
            var frontend_url = $('#photo-frontend-url'),
                new_window = frontend_url.parents('li:first').find('i.new-window'),
                span = $('#photo-frontend-link').nextAll('span.slash:first');
            if (frontend_url.length) {
                frontend_url.inlineEditable({
                    inputType: 'input',
                    editLink: '#photo-frontend-url-edit-link',
                    editOnItself: false,
                    minSize: {
                        width: 100
                    },
                    beforeMakeEditable: function(input) {
                        var self = $(this),
                            text = self.text().replace(/\/$/, '');
                        self.text(text);
                        span.show();
                        new_window.hide();
                        input.removeClass('error');
                        $('#photo-content-control').find('.errormsg').text('').hide();
                    },
                    beforeBackReadable: function(input, data) {
                        var self = $(this),
                            text = self.text() + '/',
                            photo_id = $.photos.getPhotoId(),
                            value = $(input).val();

                        function restoreReadableView() {
                            self.text(text);
                            span.hide();
                            new_window.show();
                            $(input).removeClass('state-error');
                            $('#photo-content-control').find('.errormsg').text('').hide();
                        }
                        if (!data.changed) {
                            restoreReadableView();
                            return;
                        }
                        $.photos.saveField(photo_id, 'url', value, function(r) {
                            if (r.status == 'ok') {
                                restoreReadableView();
                                frontend_url.trigger('readable', true);
                                let $photo_frontend_link = $('#photo-frontend-link'),
                                    http = $photo_frontend_link.text().trim() + '/';
                                $photo_frontend_link.attr('href', http);
                            } else if (r.status == 'fail') {
                                $(input).addClass('state-error');
                                $('#photo-content-control').find('.errormsg').text(r.errors.url).show();
                            }
                        });
                        return false;
                    },
                    hold: function() {
                        return this.hasClass('hold');
                    }
                });
            }
            $.photos.updatePhotoFrontendUrl(edit_rights);
        },

        renderMap: function(lat, lng, title) {
            const that = this;
            const map_options = that.options.map_options || {};
            let render = function () {};    // map renderer
            const $photoMap = $('#photo-map');

            if (!lat || !lng) {
                $photoMap.hide();
                return;
            }

            if (map_options.type === 'google') {
                render = function() {

                    window.initPhotosGoogleMap = function () {

                        if (!(window.google)) {
                            $photoMap.hide();
                            return;
                        }

                        $photoMap.show();
                        var latLng = new google.maps.LatLng(lat, lng),
                            options = {
                                zoom: 11,
                                center: latLng,
                                mapTypeId: google.maps.MapTypeId.ROADMAP,
                                disableDefaultUI: true,
                                zoomControlOptions: {
                                    position: google.maps.ControlPosition.TOP_LEFT,
                                    style: google.maps.ZoomControlStyle.SMALL
                                }
                            };
                        var map = new google.maps.Map($('#photo-map').get(0), options);
                        var marker = new google.maps.Marker({
                            position: latLng,
                            map: map,
                            title: title
                        });
                    };

                    if (!(window.google)) {
                        var script_url = 'https://maps.googleapis.com/maps/api/js?sensor=false&key=:KEY&lang=:LOCALE&callback=initPhotosGoogleMap';
                        script_url = script_url.replace(':KEY', map_options.key || '').replace(':LOCALE', map_options.locale || '');
                        $.getScript(script_url);
                    } else {
                        initPhotosGoogleMap();
                    }

                };
            } else if (map_options.type === 'yandex') {
                render = function() {
                    var initYandexMap = function() {

                        if (!(window.ymaps)) {
                            $photoMap.hide();
                            return;
                        }

                        ymaps.ready(function () {
                            $photoMap.show();
                            var coords = [lat, lng];
                            var map = new ymaps.Map('photo-map', {
                                center: coords,
                                zoom: 11,
                                controls: [
                                    'zoomControl',
                                    'fullscreenControl'
                                ]
                            });
                            map.setCenter(coords);
                            map.geoObjects.add(new ymaps.Placemark(coords, {balloonContent: ''}));
                        });
                    };

                    if (!(window.ymaps)) {
                        var script_url = 'https://api-maps.yandex.ru/2.1/?';

                        script_url += ('lang=' + (map_options.locale || 'ru_RU'));

                        if (map_options.key) {
                            script_url += ('&apikey=' + map_options.key);
                        }

                        $.getScript(script_url, initYandexMap);
                    } else {
                        initYandexMap();
                    }
                };
            } else {
                render = function() {
                    $photoMap.hide();
                }
            }

            render();
        },

        joinObject: function(obj, glue) {
            var str = '';
            var i = 0;
            glue = glue || '';
            for (var k in obj) {
                if (!obj.hasOwnProperty(k)) {
                    continue;
                }
                if (i++ > 0) {
                   str += glue;
                }
                str += obj[k];
            }
            return str;
        },

        initPhotoStreamWidget: function(data) {
            var photos = data.photos;

            photos = [null].concat(photos, [null]);
            $('#photo-stream').html(tmpl('template-photo-stream', {
                photos: photos,
                current_photo: data.current_photo || null,
                hash: $.photos.hash
            }));
            var max_availabe_rest_count = 15,
                photo_stream = 'ul',
                duration = 400,
                is_end = false,
                is_start = false;

            $('#photo-stream ul.photostream:first').photoStreamSlider({
                backwardLink: '#photo-stream .p-rewind',
                forwardLink: '#photo-stream .p-ff',
                photoStream: photo_stream,
                duration: duration,
                onForward: function f() {
                    if (is_end) {
                        return;
                    }
                    var self = this,
                        list = self.find(photo_stream).find('li:not(.dummy)'),
                        visible_list = list.filter('.visible'),
                        last = list.filter(':last'),
                        last_visible = visible_list.filter(':last'),
                        next = last_visible.nextAll(),
                        next_count = next.length;
                    if (next_count < max_availabe_rest_count) {
                        if (typeof f.xhr == 'object') {
                            return;
                        }
                        var photo_id = last.attr('data-photo-id');
                        f.xhr = $.post('?module=photo&action=loadList',
                            {
                                id: photo_id,
                                direction: 1,
                                hash: $.photos.hash
                            },
                            function(r) {
                                if (r.status == 'ok') {
                                    var photos = r.data.photos;
                                    if (photos.length > 0) {
                                        $.photos.photo_stream_cache.append(photos);
                                    } else {
                                        is_end = true;
                                        return;
                                    }
                                    photos.push(null);  // one dummy item at the end
                                    self.trigger('append', tmpl('template-photo-stream-list', {
                                        photos: photos
                                    }));
                                }
                                delete f.xhr;
                            },
                        'json');
                    }
                },
                onBackward: function f() {
                    if (is_start) {
                        return;
                    }
                    var self = this,
                        list = self.find(photo_stream).find('li:not(.dummy)'),
                        visible_list = list.filter('.visible'),
                        first = list.filter(':first'),
                        first_visible = visible_list.filter(':first'),
                        prev = first_visible.prevAll(),
                        prev_count = prev.length;
                    if (prev_count < max_availabe_rest_count) {
                        if (typeof f.xhr == 'object') {
                            return;
                        }
                        var photo_id = first.attr('data-photo-id');
                        f.xhr = $.post('?module=photo&action=loadList',
                            {
                                id: photo_id,
                                direction: -1,
                                hash: $.photos.hash
                            },
                            function(r) {
                                if (r.status == 'ok') {
                                    var photos = r.data.photos;
                                    if (photos.length > 0) {
                                        $.photos.photo_stream_cache.prepend(photos);
                                    } else {
                                        is_start = true;
                                        return;
                                    }
                                    photos.unshift(null);  // one dummy item at the start
                                    self.trigger('prepend', tmpl('template-photo-stream-list', {
                                        photos: photos
                                    }));
                                }
                                delete f.xhr;
                            },
                        'json');
                    }
                }
            });
        },

        updatePhotoStreamWidget: function(data) {
            // update current photo
            var photo_stream = $('#photo-stream ul.photostream:first');
            if (data.current_photo) {
                var li = photo_stream.find('li.selected'),
                    current_photo = data.current_photo;
                li.attr('data-photo-id', current_photo.id);
                li.find('a').attr('href', '#/photo/'+current_photo.id);
                li.find('img').attr('src', current_photo.thumb_crop.url);
            }
            // make shift in photo-stream
            if (data.shift) {
                photo_stream.find('li.selected').removeClass('selected');
                var fn = data.fn || function() {};
                photo_stream.trigger('home', [fn, !$.photos.shift_next]);
                $.photos.shift_next = false;
            }
        },

        updatePhotoName: function(name, edit_status) {
            if (typeof name === 'boolean' || typeof name === 'undefined') {
                edit_status = name;
                name = null;
            }
            var photo_name = $('#photo-name');
            if (name !== null) {
                photo_name.html(name);
            }
            if (edit_status) {
                photo_name.addClass('editable');
            } else {
                photo_name.removeClass('editable');
            }
        },

        updatePhotoDescription: function(description, edit_status) {
            if (typeof description === 'boolean' || typeof description === 'undefined') {
                edit_status = description;
                description = $('#photo-description').html();
            }
            var photo_description = $('#photo-description'),
                placeholder = null,
                placeholder_text = $_('add description');

            if (edit_status) {
                photo_description.addClass('editable');
                //placeholder = description && description !== placeholder_text ? null : placeholder_text;
                placeholder = placeholder_text;
                $('#photo-description-edit-link').show();
            } else {
                placeholder = null;
                description = description !== placeholder_text ? description : '';
                photo_description.removeClass('editable');
                $('#photo-description-edit-link').hide();
            }
            photo_description.inlineEditable('setOption', {
                placeholder: placeholder
            }).html(description).trigger('placeholder', !!placeholder);
        },

        updatePhotoRate: function(rate, edit_status) {
            if (typeof rate === 'boolean' || typeof rate === 'undefined') {
                edit_status = rate;
                rate = null;
            }
            var photo_rate = $('#photo-rate');
            if (rate !== null) {
                photo_rate.rateWidget('setOption', 'rate', rate);
            }
            if (edit_status) {
                photo_rate.removeClass('hold').show();
            } else {
                photo_rate.addClass('hold');
                rate = photo_rate.rateWidget('getOption', 'rate');
                if (!rate) {
                    photo_rate.hide();
                }
            }
        },

        updatePhotoTags: function(tags) {
            const tags_input = $('.js-photos-tags');

            tags_input.data('current_value', tags_input.val());
            if (typeof tags !== 'undefined') {
                if (typeof tags === 'object' && tags) {
                    tags = $.photos.joinObject(tags, ',');
                }
                //#
                tags_input.importTags(tags);
            }
        },

        updatePhotoFrontendUrl: function(edit_status) {
            var frontend_url = $('#photo-frontend-url');
            if (edit_status) {
                frontend_url.removeClass('hold');
                $('#photo-frontend-url-edit-link').show();
            } else {
                frontend_url.addClass('hold');
                $('#photo-frontend-url-edit-link').hide();
            }
        },

        updatePhotoOriginalBlock: function(photo) {
            $('#photo-original').html(tmpl('template-photo-original', {
                photo: photo
            }));
        },

        initPhotoToolbar: function(data) {
            let $toolbar = $('#p-toolbar');
            $toolbar.html(tmpl('template-photo-toolbar', data)).addClass('rendered');
            $toolbar.closest('#wa-header').addClass('has-toolbar');
            $.photos.menu.init('photo');

            $('.js-toolbar-close').on('click', function() {
                $(this).closest('#wa-header').removeClass('has-toolbar').find('#p-toolbar').remove();
            });

            $(document).on('keyup', event => {
                const key = event.which || event.keyCode || 0;
                if (key === 27) {
                    let $toolbar = $('#wa-header').find('#p-toolbar');
                    const isDialogOpened = $('.dialog-opened').length;
                    if ($toolbar.length && !isDialogOpened) {
                        let $toolbar_close = $('.js-toolbar-close'),
                            hash = $toolbar_close.attr('href');

                        $toolbar_close.trigger('click');
                        $.wa.setHash(hash)
                    }
                }
            })

            $.photos.menu.init('photo');

            $.photos.updateViewPhotoMenu(($.isArray(data.stack) && data.stack.length), data.photo.edit_rights);

            $(".js-p-toolbar-dropdown").waDropdown({
                hover: true,
                update_title: false,
                items: ".menu > li > a",
            });
        },

        renderPhotoBlock: function(data) {
            var photo = data.photo,
                proper_thumb = $.photos._chooseProperThumb(photo);

            const newData = data;
            newData.smallSize = (photo.width < this.containerWidth && photo.height < this.containerHeight);

            $("#p-block").html(tmpl('template-photo', newData)).addClass('rendered');
            $("#p-info-area").html(tmpl('template-info-area', data)).addClass('rendered');

            $('#p-toolbar').prependTo($('#wa-header-content-area'));
            let $photo = $('#photo');
            $photo.smallSize = newData.smallSize;

            $photo.proper_thumb = {
                url: proper_thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                url2x: proper_thumb.url2x + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : '')
            }

            // first time open photo from gallery
            replaceImg($photo,
                proper_thumb.url + (photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : ''),
                function() {
                    if ((photo.thumb_big.size.width < photo.width) || (photo.thumb_big.size.height < photo.height) ) {
                        $photo.addClass('contain');
                    } else {
                        $photo.removeClass('contain');
                    }

                    $.photos.hooks_manager.trigger('afterRenderImg', this, photo, proper_thumb);
                }
            );
        },

        initPhotoWidgets: function(data) {
            var photo = data.photo,
                exif = data.exif,
                stack = data.stack,
                photo_stream = data.photo_stream,
                hash = $.photos.hash;

            $.photos.initPhotoNameWidget(photo.edit_rights);
            $.photos.initPhotoDescriptionWidget(photo.edit_rights);
            $.photos.initPhotoRateWidget(photo.edit_rights);
            $.photos.initPhotoTagsWidget(photo.tags);
            $.photos.initPhotoContentControlWidget(photo.edit_rights);
            $.photos.hotkey_manager.set();

            // map
            $.photos.renderMap(exif.GPSLatitude, exif.GPSLongitude, photo.name);
            // stack
            if ($.isArray(stack) && stack.length) {
                $.photos.photo_stack_cache.set(stack).setCurrentById(photo.id);
                $.photos.initPhotoStackWidget({
                    stack: stack,
                    photo_id: photo.id,
                    hash: $.photos.hash
                });
            }

            var stream = $.photos.photo_stream_cache;
            stream.hash = hash;
            stream.set(photo_stream.photos).setCurrentById(photo_stream.current_photo_id);
            // visually update photo-stream
            $.photos.initPhotoStreamWidget({
                photos: photo_stream.photos,
                current_photo: stream.getCurrent()
            });

            // Go to prev or next photo when user clicks on an arrow over the photo
            $('#p-block .p-one-photo > .p-image-nav .p-image-nav-item').on('click', function() {
                if (this.classList.contains('p-rewind')) {
                    var prev = $.photos.photo_stream_cache.getPrev();

                    if (prev) {
                        $.photos.goToHash($.photos.getHashByPhotoId(prev.id), false);
                        $.photos.shift_next = true;
                    }
                } else {
                    var next = $.photos.photo_stream_cache.getNext();
                    if (next) {
                        $.photos.goToHash($.photos.getHashByPhotoId(next.id), false);
                        $.photos.shift_next = true;
                    }
                }
            });
        },

        _chooseProperThumb: function(photo) {
            return photo.thumb_big;
        },

        setNextPhotoLink: function(next) {
            // set a-link to next
            var a = $('#photo').parents('a:first');
            next = next ? next : $.photos.photo_stream_cache.getNext();
            if (next) {
                a.attr('title', $_('Next →'));
                a.attr('href', '#' + $.photos.hash + '/photo/' + next.id + '/');
            } else {
                a.attr('title', '');
                a.attr('href', 'javascript:void(0);');
            }
        },

        setTitle: function(title) {
            if (title) {
                document.title = title + $.photos.options.title_suffix;
            }
        },

        ignore_scrolltop: false,

        scrollTop: function() {
            if ($.photos.ignore_scrolltop) {
                $.photos.ignore_scrolltop = false;
            } else {
                $(document).scrollTop(0);
            }
        },

        load: function (url, data, callback, wrapper) {
            let target = $('#content');

            if (typeof data == 'function') {
                wrapper = callback||null;
            }

            if(wrapper) {
                target.empty().append(wrapper);
                target = target.find(':last-child');
            }

            if (typeof data == 'function') {
                target.load(url, data);
            } else {
                target.load(url, data, callback);
            }
        },

        setCover: function(is_full = false) {
            if (is_full) {
                $('#full-cover').show();
            }else{
                $('#cover').show();
            }
        },

        unsetCover: function(is_full = false) {
            if (is_full) {
                $('#full-cover').hide();
            }else{
                $('#cover').hide();
            }
        },

        saveField: function(id, name, value, fn) {
            var type = 'photo';
            if (arguments.length == 1) {
                var options = id;
                options = $.extend({
                    type: type,
                    fn: function() {}
                }, options);
                var id = options.id,
                    name = options.name,
                    value = options.value,
                    fn = options.fn;
                type = options.type;
            }
            if (!~['photo', 'album'].indexOf(type)) {
                throw "Unknown type";
            }
            if (!$.isArray(id)) {
                id = [{name: 'id[]', value: parseInt(id)}];
            }
            var data = [].concat(id, {
                name: 'name',
                value: name
            }, {
                name: 'value',
                value: value
            });
            $.post('?module=' + type + '&action=saveField', data,
                function(r) {
                    if (r.status == 'ok') {
                        var info = {};
                        info[name] = value;
                        if (r.data.parent_id) {
                            id = r.data.parent_id;
                        }
                        $.photos._updateStreamCache(id, info);
                        if (typeof fn == 'function') {
                            fn(r);
                        }
                    } else if (r.status == 'fail') {
                        if (typeof fn == 'function') {
                            fn(r);
                        }
                    }
                },
            'json');
        },

        saveFields: function(data,success, fail) {
            var type = 'photo'
            $.post('?module=' + type + '&action=saveFields', {'data':data},
                function(response) {
                    if (response.status == 'ok') {
                        for(var i in response.data.update) {
                            var id = response.data.update[i]['id'];
                            if (!$.isArray(id)) {
                                id = [id];
                            }
                            var info = response.data.update[i]['data'];
                            for (var i = 0, n = id.length; i < n; ++i) {
                                var updated_info = $.photos.photo_stream_cache.updateById(id[i], info);
                            }
                        }
                    }
                   //callback
                },
            'json');
        },

        _updateStreamCache: function(photo_ids, info) {
            if (!$.isArray(photo_ids)) {
                photo_ids = [{value: photo_ids}];
            }
            for (var i = 0, n = photo_ids.length; i < n; ++i) {
                $.photos.photo_stream_cache.updateById(photo_ids[i].value, info);
            }
        },

        restoreOriginal: function (element) {
            if (element.id === 'restore-original') {
                if (confirm($_('This will reset all changes you applied to the image after upload and will restore the original image. Are you sure?'))) {
                    $.photos.setCover(true);
                    let waLoading = $.waLoading(),
                        $wrapper = $("body"),
                        locked_class = "is-locked",
                        id = $.photos.getPhotoId();

                    waLoading.show();
                    waLoading.animate(10000, 95, false);
                    $wrapper.addClass(locked_class);
                    $.post('?module=photo&action=restore', {id: id}, function (r) {
                        if (r.status == 'ok') {
                            var photo = r.data.photo;
                            if (photo.parent_id == 0) {
                                photo = $.photos.photo_stream_cache.updateById(id, photo);
                            } else {
                                photo = $.photos.photo_stack_cache.updateById(id, photo);
                            }
                            $.photos.updatePhotoOriginalBlock(photo);
                            $.photos.updatePhotoImgs(photo, function () {
                                $.photos.unsetCover(true);
                            });
                            waLoading.done();
                            $wrapper.removeClass(locked_class);
                        }
                    },'json')
                        .error(function(xhr) {
                            $.photos.showServerError(xhr.responseText);
                            waLoading.abort();
                            $wrapper.removeClass(locked_class);
                    });
                }
            }
        },

        rotate: function(id, direction, fn) {
            fn = fn || function() {};

            var waLoading = $.waLoading();

            var $wrapper = $("body"),
                locked_class = "is-locked";

            waLoading.show();
            waLoading.animate(10000, 95, false);
            $wrapper.addClass(locked_class);

            $.post('?module=photo&action=rotate', { id: id, direction: direction },
                function(r) {
                    if (r.status !== 'ok') {
                        return;
                    }

                    var photo = r.data.photo;
                    if (photo.parent_id == 0) {
                        photo = $.photos.photo_stream_cache.updateById(id, photo);
                    } else {
                        photo = $.photos.photo_stack_cache.updateById(id, photo);
                    }
                    $.photos.updatePhotoOriginalBlock(photo);
                    $.photos.updatePhotoImgs(photo, fn);

                    waLoading.done();
                    $wrapper.removeClass(locked_class);
                },
            'json').error(function(xhr) {
                $.photos.showServerError(xhr.responseText);
                if (typeof fn == 'function') {
                    fn();
                }
                waLoading.abort();
                $wrapper.removeClass(locked_class);
            });
        },

        updatePhotoImgs: function(photo, fn) {
            var proper_thumb = $.photos._chooseProperThumb(photo),
                salt = photo.edit_datetime ? '?' + Date.parseISO(photo.edit_datetime) : '',
                $photo = $('#photo');

            $photo.smallSize = (photo.width < this.containerWidth && photo.height < this.containerHeight);

            $photo.proper_thumb = {
                url: proper_thumb.url + salt,
                url2x: proper_thumb.url2x + salt
            }

            replaceImg(
                $photo,
                proper_thumb.url + salt,
                function() {
                    $('#photo-stream .selected img, #stack-stream .selected img').each(function() {
                        var self = $(this);
                        if (self.hasClass('thumb')) {
                            self.attr('src', photo.thumb.url + salt);
                        } else if(photo.thumb_crop !== undefined) {
                            self.attr('src', photo.thumb_crop.url + salt);
                        }
                    });
                    if (typeof fn == 'function') {
                        fn();
                    }
                    /**
                     * @hook
                     */
                    $.photos.hooks_manager.trigger('afterRenderImg', this, photo, proper_thumb);
                }
            );
        },

        deletePhotos: function(photo_id, fn) {
            var original_photo_id = photo_id;

            $.post('?module=photo&action=resolution', $.isArray(photo_id) ? photo_id : { photo_id: photo_id },
                function(r) {
                    if (r.status == 'ok') {
                        var photo_id = r.data.photo_id,
                            data = [],
                            denied_photo_id = [];

                        // serializeArray
                        for (var i = 0, n = photo_id.length; i < n; ++i) {
                            data.push({
                                name: 'photo_id[]', value: photo_id[i]
                            });
                        }
                        $.photos.massPost({
                            photo_id: data,
                            url: '?module=photo&action=delete',
                            dataType: 'json',
                            data: { photos_length: $.isArray(original_photo_id) ? original_photo_id.length : 1 },
                            success: function(r, data) {
                                if (r.status == 'ok') {
                                    denied_photo_id = denied_photo_id.concat(r.data.denied_photo_id);
                                    for (var i = 0, n = denied_photo_id.length; i < n; ++i) {
                                        data.push({
                                            name: 'denied_photo_id[]', value: denied_photo_id[i]
                                        });
                                    }
                                }
                            },
                            fullSuccess: function(r) {
                                var hash;
                                if (r.status == 'ok') {
                                    if (!$.isArray(original_photo_id)) { // one photo page
                                        if (denied_photo_id.length) {
                                            alert($_("You don't have sufficient access rights"));
                                        }
                                        if (r.data.parent_id) {
                                            hash = $.photos.getHashByPhotoId(r.data.parent_id);
                                            $.photos.photo_stack_cache.deleteById(photo_id);
                                        } else {
                                            var stream = $.photos.photo_stream_cache,
                                                next = stream.getNext(original_photo_id);
                                            if (next) {
                                                hash = $.photos.getHashByPhotoId(next.id);
                                            } else {
                                                hash = stream.hash;
                                            }
                                            stream.deleteById(photo_id);
                                        }

                                        $.wa.setHash(hash);
                                        fn && fn(r);
                                    } else { // photo list page
                                        if (r.data.alert_msg) {
                                            alert(r.data.alert_msg);
                                        }

                                        var removed_photo_ids = {};
                                        $.each(photo_id, function(i, id) {
                                            removed_photo_ids[id] = id;
                                        });
                                        if (r.data.denied_photo_id && r.data.denied_photo_id.length) {
                                            $.each(r.data.denied_photo_id, function(i, id) {
                                                delete removed_photo_ids[id];
                                            });
                                        }
                                        removed_photo_ids = $.map(removed_photo_ids, function(i, id) {
                                            $.photos.photo_stream_cache.deleteById(photo_id[i]);
                                            return id;
                                        });

                                        $('#photo-list > li.selected').trigger('select', false);

                                        if (removed_photo_ids.length) {
                                            $.photos.makeDeleteAnimation(removed_photo_ids, function() {
                                                fn && fn(r);
                                            });
                                        } else {
                                            fn && fn(r);
                                        }
                                    }
                                } else {
                                    fn && fn(r);
                                }
                            }
                        });
                    }
                },
            'json');
        },

        deleteAllAlbums: function(album_ids, del_photos, fn) {
            if (!album_ids.length) {
                return fn && fn();
            }

            // Delete first album from the list, then delete the rest of albums
            var a_id = album_ids.shift();
            $.photos.deleteAlbum(a_id, del_photos, function() {
                $.photos.deleteAllAlbums(album_ids, del_photos, fn);
            });
        },

        deleteAlbum: function(album_id, del_photos, fn) {
            if (del_photos) {
                $.photos._deleteAlbumWithPhotos(album_id, fn);
            } else {
                $.post('?module=album&action=delete', { album_id: album_id },
                    function(r) {
                        if (r.status == 'ok') {
                            $.photos.onDeleteAlbum(album_id);
                            $.photos.goToHash('');
                            if (typeof fn == 'function') {
                                fn();
                            }
                        }
                    },
                'json');
            }
        },

        _deleteAlbumWithPhotos: function(album_id, fn) {
            $.post('?module=photo&action=resolution', { album_id: album_id },
                function(r) {
                    if (r.status == 'ok') {
                        var photo_id = r.data.photo_id,
                            data = [];

                        // serializeArray
                        for (var i = 0, n = photo_id.length; i < n; ++i) {
                            data.push({
                                name: 'photo_id[]', value: photo_id[i]
                            });
                        }

                        if (data.length) {
                            var chunk_count = 10,
                                params = {
                                    url: '?module=photo&action=delete',
                                    photo_id: data,
                                    dataType: 'json',
                                    fullSuccess: function(r) {
                                        if (r.status == 'ok') {
                                            $.photos.deleteAlbum(album_id, 0, fn);
                                        } else if(console) {
                                            console.log('Error while delete album');
                                        }
                                    }
                                };
                            $.photos.massPost(params);
                        } else {
                            $.photos.deleteAlbum(album_id, 0, fn);
                        }
                    }
                },
            'json');
        },

        /**
         * Serialize data in format of jQuery.serializeArray function
         */
        serializeData: function(data, name) {
            if (typeof data === 'object' && !$.isArray(data) && typeof name === 'undefined') {
                var result = [];
                for (name in data) {
                    if (data.hasOwnProperty(name)) {
                        result.push({
                            name: name,
                            value: data[name]
                        });
                    }
                }
                return result;
            }
            if (!$.isArray(data)) {
                data = [data];
            }
            for (var i = 0, n = data.length, item = data[0]; i < n; item = data[++i]) {
                if (typeof item != 'object') {
                    item = {
                        name: name,
                        value: item
                    };
                }
                data[i] = item;
            }
            return data;
        },

        saveAccess: function(options) {
            let photo_id = options.photo_id || [],
                data = options.data || [],
                fn = options.fn,
                original_photo_id = photo_id,
                onDeniedExist = options.onDeniedExist || function(msg) {
                    alert(msg);
                },
                resolution = typeof options.resolution === 'boolean' ? options.resolution : true;

            if (typeof data === 'object' && !$.isArray(data)) {
                data = $.photos.serializeData(data);
            }

            data.push({
                name: 'photos_length',
                value: original_photo_id.length
            });

            function _saveAccess(photo_id) {
                let post_data = [],
                    denied_photo_id = [],
                    allowed_photo_id = [];

                // serializeArray
                for (let i = 0, n = photo_id.length; i < n; ++i) {
                    post_data.push({
                        name: 'photo_id[]', value: photo_id[i]
                    });
                }

                $.photos.massPost({
                    photo_id: post_data,
                    url: '?module=photo&action=manageAccessSave',
                    dataType: 'json',
                    data: data,
                    success: function(r, data) {
                        if (r.status == 'ok') {
                            denied_photo_id = denied_photo_id.concat(r.data.denied_photo_id);
                            for (let i = 0, n = denied_photo_id.length; i < n; ++i) {
                                data.push({
                                    name: 'denied_photo_id[]', value: denied_photo_id[i]
                                });
                            }
                            allowed_photo_id = r.data.allowed_photo_id;
                            for (let i = 0, n = allowed_photo_id.length; i < n; ++i) {
                                data.push({
                                    name: 'allowed_photo_id[]', value: allowed_photo_id[i]
                                });
                            }
                        }
                    },
                    fullSuccess: function(r) {
                        if (r.status == 'ok' && r.data.alert_msg) {
                            onDeniedExist(r.data.alert_msg);
                        }
                        if (typeof fn === 'function') {
                            fn(r, allowed_photo_id);
                        }
                    }
                });
            }

            if (resolution) {
                $.post('?module=photo&action=resolution', $.isArray(photo_id) ? photo_id : { photo_id: photo_id },
                    function(r) {
                        if (r.status == 'ok') {
                            _saveAccess(r.data.photo_id);
                        }
                    },
                'json');
            } else {
                _saveAccess($.isArray(photo_id) ? photo_id : [photo_id]);
            }
        },

        addToAlbums: function(options) {
            var photo_id = options.photo_id || [],
                album_id = options.album_id || [],
                copy = options.copy == undefined ? 1 : options.copy,  // true is default value
                fn = options.fn,
                onDeniedExist = options.onDeniedExist || function(msg) {
                    alert(msg);
                };

            photo_id = $.photos.serializeData(photo_id, 'photo_id[]');
            album_id = $.photos.serializeData(album_id, 'album_id[]');

            var data = photo_id.concat(album_id);
            data.push({
                name: 'copy',
                value: +copy
            });
            $.post('?module=photo&action=addToAlbums', data, function (r) {
                if (r.status == 'ok') {
                    var old_albums = r.data.old_albums || [],
                        albums = [].concat(r.data.albums || [], old_albums),
                        denied_album_id = r.data.denied_album_id;
                    for (var i = 0, n = albums.length; i < n; ++i) {
                        var album = albums[i];
                        $('#album-list li[rel=' + album.id + ']')
                            .find('.count:first').text(album.count).end()
                            .find('.count-new:first').text(album.count_new > 0 ? '+' + album.count_new : '');
                    }
                    if (r.data.alert_msg) {
                        onDeniedExist(r.data.alert_msg);
                    }
                }
                if (typeof fn == 'function') {
                    fn(r);
                }
            }, 'json');
        },

        assignTags: function(options) {
            var photo_id = options.photo_id || [],
                tags = options.tags || [],
                delete_tags = options.delete_tags || [],
                fn = options.fn,
                onDeniedExist = options.onDeniedExist || function(msg) {
                    alert(msg);
                },
                data = [];
            if (!$.isArray(photo_id)) {
                data = data.concat([{
                    name: 'photo_id[]',
                    value: photo_id
                }, {
                    name: 'one_photo',
                    value: 1
                }]);
            } else {
                data = photo_id;
            }
            if (!$.isArray(tags) || !tags.length) {
                data.push({
                    name: 'tags',
                    value: tags
                });
                if (delete_tags.length) {
                    data = data.concat(delete_tags);
                }
            } else {
                data = data.concat(tags);
            }
            $.post('?module=photo&action=assignTags', data,
                function(r) {
                    if (r.status == 'ok') {
                        var cloud = r.data.cloud,
                            tag_cloud_block = $('#tag-cloud-block');

                        if (($.isArray(cloud) && !cloud.length) || !cloud) {
                            tag_cloud_block.hide();
                        } else {
                            $('#tag-cloud').html(tmpl('template-tag-cloud', {
                                cloud: cloud
                            }));
                            tag_cloud_block.show();
                        }

                        // update stream cache
                        var photo_tags = r.data.tags || {},
                            photo_id,
                            tags;

                        for (photo_id in photo_tags) {
                            if (photo_tags.hasOwnProperty(photo_id)) {
                                tags = photo_tags[photo_id];
                                $.photos._updateStreamCache(photo_id, {
                                    tags: tags
                                });
                            }
                        }
                        // new cloud tag needed in the album-create-dialog, so remove, because waDialog made cache of it
                        $('#album-create-dialog').remove();
                        if (r.data.alert_msg) {
                            onDeniedExist(r.data.alert_msg);
                        }
                    }
                    if (typeof fn == 'function') {
                        fn(r);
                    }
                },
            'json');
        },

        makeStack: function(photo_ids, fn) {
            var parent_id = photo_ids.shift(),
                original_photo_ids = photo_ids;

            $.post('?module=photo&action=resolution', photo_ids, function(r) {
                if (r.status == 'ok') {
                    var photo_ids = r.data.photo_id,
                        data = [],
                        denied_photo_ids = [];

                    for (var i = 0, n = photo_ids.length; i < n; ++i) {
                        if (photo_ids[i] != parent_id.value) {
                            data.push({
                                name: 'photo_id[]', value: photo_ids[i]
                            });
                        }
                    }

                    $.photos.massPost({
                        photo_id: data,
                        data: { parent_id: parent_id.value, photos_length: original_photo_ids.length },
                        url: '?module=stack&action=make',
                        dataType: 'json',
                        success: function(r, data) {
                            if (r.status == 'ok') {
                                if (r.status == 'ok') {
                                    denied_photo_ids = denied_photo_ids.concat(r.data.denied_photo_ids);
                                    for (var i = 0, n = denied_photo_ids.length; i < n; ++i) {
                                        data.push({
                                            name: 'denied_photo_id[]', value: denied_photo_ids[i]
                                        });
                                    }
                                }
                            }
                        },
                        fullSuccess: function(r) {
                            if (r.status == 'ok') {
                                if (r.data.alert_msg) {
                                    alert(r.data.alert_msg);
                                }
                                for (var i = 0, n = original_photo_ids.length; i < n; ++i) {
                                    $.photos.photo_stream_cache.deleteById(original_photo_ids[i].value);
                                }
                                $.photos.photo_stream_cache.updateById(r.data.parent_id, r.data.photo);
                                $('#photo-list > li.selected').trigger('select', false);
                                $.photos.makeStackAnimation([parent_id].concat(original_photo_ids));
                                //$.photos.goToHash($.photos.getHashByPhotoId(parent_id.value));
                            }
                        }
                    });
                }
            }, 'json');
        },

        makeStackAnimation: function(photo_ids, done) {
            var parent_id = photo_ids[0].value;
            var parent = $('li[data-photo-id="'+parent_id+'"]');
            var parent_offset = parent.offset();
            var duration = 300;
            var win = $(window);

            // scroll to parent
            if (parent_offset.top < win.scrollTop() || parent_offset.top > win.scrollTop() + win.height()) {
                $("html, body").animate({
                    scrollTop: parent_offset.top
                }, duration);
            }

            var deferreds = [];
            for (var i = 1; i < photo_ids.length; i++) {
                var photo_id = photo_ids[i].value;
                var photo = $('li[data-photo-id="'+photo_id+'"]');
                var photo_offset = photo.offset();
                var photo_clone = photo.clone().css({
                    'z-index': 10,
                    position: 'absolute',
                    top: photo_offset.top,
                    left: photo_offset.left
                }).insertAfter(photo);
                photo.css({
                    opacity: 0
                });
                deferreds.push(
                    photo.hide(duration).promise()
                );
                $.photos.photo_stream_cache.deleteById(photo_id);
                deferreds.push(photo_clone.animate({
                    top: parent_offset.top,
                    left: parent_offset.left
                }, duration).promise().done(function() {
                    $(this).remove();
                }));

            }
            $.when.apply($, deferreds).done(function() {
                var is_last = parent.hasClass('last');
                parent.replaceWith(tmpl($.photos.list_template, {
                    photos: [$.photos.photo_stream_cache.getById(parent_id)],
                    hash: $.photos.hash,
                    last_login_time: $.photos.options.last_login_time,
                    options: {}
                }));
                if (!is_last) {
                    $('li[data-photo-id="'+parent_id+'"]').removeClass('last');
                }
                $('li[data-photo-id="'+parent_id+'"]').addClass('highlighted');
                if (typeof done === 'function') {
                    done();
                }
            });
        },

        makeDeleteAnimation: function(photo_ids, done) {
            var ids = {};
            $.each(photo_ids, function(i, id) {
                ids[id] = true;
            });
            var $lis = $('#photo-list').children().filter(function() {
                return !!ids[$(this).data('photo-id')];
            }).animate({ width: 0 }, function() {
                $lis.remove();
                done && done();
            });
        },

        highlightSidebarItem: function() {
            var href = decodeURIComponent($.photos.hash);

            const $app_sidebar = $('#js-app-sidebar');

            var link = $app_sidebar.find('a[href="#'+(href||'/')+'"]');

            $app_sidebar.find('li.selected').removeClass('selected');

            if (!link.length) {
                link = $app_sidebar.find('a[href="#'+(`${href}/`||'/')+'"]');
            }
            if (!link.length) {
                link = $app_sidebar.find('a[href^="#'+(href||'/')+'"]');
            }
            if (link.length) {
                link.parents('li:first').addClass('selected');
            }
        },

        toggleFullScreen: function() {
            if ($.storage.get('photos/maximize_photo')) {
                $('#wa-app').addClass('p-full-screen');
            } else {
                $('#wa-app').removeClass('p-full-screen');
            }
        },

        hotkey_manager: (function() {
            function arrowsHandlerDown(e) {
                const target_type = e.target.type;
                const code = e.keyCode;
                const isDialogOpened = $('.dialog-opened').length;

                if ( arrowsHandlerDown.hold ||
                     target_type == 'text' || target_type == 'textarea' ||
                     (code != 37 && code != 39) || isDialogOpened
                   )
                {
                    return;
                }
                if (code == 39) { // right arrow
                    var next = $.photos.photo_stream_cache.getNext();
                    if (next) {
                        $.photos.goToHash($.photos.getHashByPhotoId(next.id), false);
                        $.photos.shift_next = true;
                    }
                    arrowsHandlerDown.hold = true;
                }
                if (code == 37) { // left arrow
                    var prev = $.photos.photo_stream_cache.getPrev();
                    if (prev) {
                        $.photos.goToHash($.photos.getHashByPhotoId(prev.id), false);
                        $.photos.shift_next = true;
                    }
                    arrowsHandlerDown.hold = true;
                }
            }
            function arrowsHandlerUp(e) {
                arrowsHandlerDown.hold = false;
            }
            function hotkeyToRate(key) {
                switch (key) {
                    case 48:
                    case 96:
                        key = 0;
                        break;
                    case 49:
                    case 97:
                        key = 1;
                        break;
                    case 50:
                    case 98:
                        key = 2;
                        break;
                    case 51:
                    case 99:
                        key = 3;
                        break;
                    case 52:
                    case 100:
                        key = 4;
                        break;
                    case 53:
                    case 101:
                        key = 5;
                        break;
                    default:
                        key = null;
                        break;
                }
                return key;
            }
            function rateHandlerDown(e) {
                var target_type = e.target.type,
                    code = e.keyCode;
                if ( rateHandlerDown.hold ||
                     target_type == 'text' || target_type == 'textarea'
                   )
                {
                    return;
                }
                var rate = hotkeyToRate(code);
                if (typeof rate !== 'number') {
                    return;
                }
                rateHandlerDown.hold = true;
                // TODO: kick dublicate code
                if ($('#photo').length) {
                    // when parent in stack save this value for all photos in stacks
                    var photo = $.photos.photo_stream_cache.getCurrent();
                    if (!photo.edit_rights) {
                        return;
                    }
                    $.photos.saveField(photo.id, 'rate', rate,
                        function (r) {
                            if (r.status == 'ok' && r.data.count) {
                                $('#rated-count').text(r.data.count > 0 ? r.data.count : '');
                                $('#photo-rate').rateWidget('setOption', 'rate', rate);
                            }
                        }
                    );
                } else {
                    var photo_id = $('input[name^=photo_id]').map(function() {
                        return this.checked ? { name: 'id[]', value: this.value } : null;
                    }).toArray();
                    if (!photo_id.length) {
                        return;
                    }
                    $.photos.saveField({
                        id: photo_id,
                        name: 'rate',
                        value: rate,
                        fn: function(r) {
                            if (r.status == 'ok') {
                                var allowed_photo_id = r.data.allowed_photo_id && !$.isArray(r.data.allowed_photo_id) ?
                                        r.data.allowed_photo_id :
                                        {};
                                $('#photo-list > li.selected').each(function() {
                                    var self = $(this);
                                    if (allowed_photo_id[self.attr('data-photo-id')]) {
                                        $.photos.updateThumbRate(self, rate);
                                    }
                                }).find('input:first').trigger('select', false);
                                if (r.data.count) {
                                    $('#rated-count').text(r.data.count > 0 ? r.data.count : '');
                                }
                                if (r.data.alert_msg) {
                                    alert(r.data.alert_msg);
                                }
                            }
                        }
                    });
                }
            };
            function rateHandlerUp() {
                rateHandlerDown.hold = false;
            }
            return {
                set: function(type) {
                    if (typeof type === 'undefined') {
                        $(document).bind('keydown', arrowsHandlerDown).bind('keyup', arrowsHandlerUp).
                            bind('keydown', rateHandlerDown).bind('keyup', rateHandlerUp);
                    } else if (type === 'rate') {
                        $(document).bind('keydown', rateHandlerDown).bind('keyup', rateHandlerUp);
                    }
                },
                unset: function() {
                    $(document).unbind('keydown', arrowsHandlerDown).unbind('keyup', arrowsHandlerUp).
                        unbind('keyup', rateHandlerDown).unbind('keyup', rateHandlerUp);
                }
            };
        })(),

        getHashByPhotoId: function(id) {
            return $.photos.hash + '/photo/' + id;
        },

        setLazyLoad: function() {
            var offset = null;
            $(window).lazyLoad({
                container: '#photo-list',
                load: function() {
                    offset = offset || $('#photo-list > li').length;
                    $(window).lazyLoad('sleep');
                    $(".lazyloading-wrapper .lazyloading-progress").show();
                    $(".lazyloading-wrapper .lazyloading-link").hide();
                    $.post(
                        '?module=photo&action=loadList',
                        { offset : offset, hash: decodeURI($.photos.hash) },
                        function (r) {
                            // if hash has changed already than ignore
                            if (r.data.hash != decodeURI($.photos.hash)) {
                                return;
                            }
                            var target = $("#photo-list");
                            if (!r.data.photos.length) {

                                $(".lazyloading-wrapper .lazyloading-progress").hide();
                                $(".lazyloading-wrapper .lazyloading-link").hide();
                                $(window).lazyLoad('stop');
                                return;
                            }
                            var list_offset = $.photos.photo_stream_cache.length();
                            $.photos.photo_stream_cache.append(r.data.photos);
                            target.find("li.last").removeClass('last');
                            $.photos.renderPhotoListChunk(target, list_offset, {string: r.data.string}, function(){
                                if(!$.photos.total_count || ($.photos.total_count>$.photos.photo_stream_cache.length())) {
                                    $(window).lazyLoad('wake');
                                } else {
                                    $(".lazyloading-wrapper .lazyloading-progress").hide();
                                    $(".lazyloading-wrapper .lazyloading-link").hide();
                                    $(window).lazyLoad('stop');
                                }
                            });
                            offset += r.data.photos.length;
                        },
                        "json"
                    );
                }
            });
            $('.lazyloading-wrapper a.lazyloading-link').off('click.lazyloading').on('click.lazyloading',function(){
                $(window).lazyLoad('force');
                return false;
            });
        },

        unsetLazyLoad: function() {
            if ($(window).lazyLoad) {
                $(window).lazyLoad('stop');
            }
            $('.lazyloading-wrapper a.lazyloading-link').off('click.lazyloading')
        },

        getAlbum: function() {
            this.album = this.album || null;
            return this.album;
        },

        setAlbum: function(album) {
            this.album = album;
        },

        unsetAlbum: function() {
            this.album = null;
        },

        /**
         * Get id of current rendered photo
         */
        getPhotoId: function() {
            var match = /photo\/(\d+)[\/]*$/.exec(location.href);
            if (match) {
                return parseInt(match[1]);
            }
            return null;
        },

        isCurrentPhotoStack: function() {
            var photo_id = this.getPhotoId();
            if (!photo_id) {
                return false;
            }
            var photo = this.photo_stream_cache.getById(photo_id);
            return photo && photo.stack_count > 0;
        },

        goToHash: function(hash, reload) {
            reload = typeof reload === 'undefined' ? true : reload;
            hash = hash.replace(/^\/*/, '').replace(/\/*\s*$/, '');
            var cur_hash = location.hash.replace(/^[^#]*#\/*/, '').replace(/\/*\s*$/, '');
            if (cur_hash == hash && reload) {
                $.photos.ignore_scrolltop = true;
                $.photos.dispatch();
            } else {
                location.hash = hash ? '/'+hash+'/' : '#/';
            }
        },

        goToAnchor: function(anchor_name) {
            var anchor = $('a[name='+anchor_name+']');
            if (anchor.length) {
                $(document).scrollTop(anchor.position()['top']);
            }
        },

        photo_stream_cache: new PhotoStream(),

        // visual corrections after creating new album
        onCreateAlbum: function(album, parent_id) {
            // update album-list in left sidebar
            var html = tmpl('template-album-list-item', album);
            var album_list = $('#album-list');

            var ul;
            if (!parent_id) {
                ul = album_list.find('ul:first');
                if (!ul.length) {
                    album_list.find('.p-empty-album-list').hide();
                    ul = album_list.prepend(
                            '<ul class="menu"><li class="drag-newposition"></li></ul>'
                    ).find('ul:first');
                }
                album_list.find('ul:first').prepend(html);
            } else {
                var li = album_list.find('li[rel='+parent_id+']');
                ul = li.find('ul:first');
                if (!ul.length) {
                    li.append('<ul class="menu"></ul>');
                    ul = li.find('ul:first');
                    li.find('.count:first').after('<i class="icon16 darr overhanging collapse-handler" id="album-'+parent_id+'-handler"></i>');
                }
                ul.prepend(html);
            }

            album_list.find('.new-item').removeClass('new-item').mouseover();

            // update album-list in upload-form
            if(!album.type) {
                var select = $('#p-upload-step2 select[name=album_id]');
                if (!parent_id) {
                    select.find('optgroup').prepend('<option value="'+album.id+'">'+album.name+'</option>');
                } else {
                    var item = select.find('option[value='+parent_id+']');
                    var text = item.text();
                    var prefix = text.replace(/[^-]/g, '') + '-';
                    item.after('<option value="'+album.id+'">'+prefix+' '+album.name+'</option>');
                }
            }
        },

        // visual corrections after deleting album
        onDeleteAlbum: function(album_id) {

            // remove from sidebar-tree
            var album_list = $('#album-list'),
                li = album_list.find('li[rel='+album_id+']'),
                subtree = li.find('ul:first'),
                ul_wrapper = li.parents('ul:first'),
                children;

            // make children to jump one level up
            li.hide();
            if (subtree.length) {
                li.prev('.drag-newposition:first').remove();
                li.next('.drag-newposition:first').remove();

                children = subtree.children('li');
                children.hide().insertAfter(li);
                subtree.remove();
                li.remove();
                children.show();
            } else {
                li.next('.drag-newposition:first').remove().end().remove();
            }

            // if parent now has not children than delete collcapse-handler icon and ul wrapper
            if (ul_wrapper.length) {
                var items = ul_wrapper.find('li:not(.drag-newposition)');
                // parent has not children
                if (!items.length) {
                    var parent = ul_wrapper.parent('li');
                    if (parent.length) {
                        parent.find('i.collapse-handler').remove();
                    }
                    ul_wrapper.remove();
                }
            }

            if (!album_list.find('ul:first').length) {
                album_list.find('.p-empty-album-list').show();
            }

            // remove from album-list in upload-form
            var upload_form_select = $('#p-upload-step2 select[name=album_id]');
            upload_form_select.find('option[value='+album_id+']').remove();
        },

        /**
         * @deprecated
         * @desc make right toolbar fixed for available even in scrolling
         */
        fixRightToolbar: function() {
            $.photos.fixRightToolbar._handler = function handler() {
                var toolbar = $('#p-toolbar');
                if (!toolbar.length) {
                    return;
                }
                if (toolbar.hasClass('rendered')) {
                    toolbar.removeClass('p-fixed');
                    return;
                }
                if (!handler.top) {
                    handler.top = toolbar.children(':first').position()['top'];
                }
                if ($(this).scrollTop() > handler.top) {
                    toolbar.addClass('p-fixed');
                } else {
                    toolbar.removeClass('p-fixed');
                }
            };
            // check right now (maybe document is scrolled)
            $.photos.fixRightToolbar._handler.call(document);
            // bind handler
            $(document).bind('scroll' , $.photos.fixRightToolbar._handler);
        },

        photo_stack_cache: new PhotoStream(),

        isSelectedAnyPhoto: function() {
            return !!$('#photo-list > li.selected:first').length;
        },

        getRatingHtml: function(rating, size, show_when_zero) {
            size = size || 10;
            rating = Math.round(rating * 2) / 2;
            if (!rating && !show_when_zero) {
                return '';
            }
            var html = '';
            for (var i = 1; i <= 5; i += 1) {
                html += `<i class="fas fa-star`;
                if (i > rating) {
                    if (i - rating == 0.5) {
                        html += '-half';
                    } else {
                        html += ' text-light-gray';
                    }
                }else{
                    html += ' text-yellow';
                }
                html += `" data-rate-value="${i}"></i>`;
            }
            return html;
        },

        uploadDialog: function() {
            $.get('?module=upload').done(function (response) {
                $.waDialog({
                    html: response,
                    onOpen($_dialog, dialog_instance) {
                        $.photos.onUploadDialog($_dialog, dialog_instance)
                    }
                });
            });
        },

        dialogClearSteps: function() {
            const that = $(this);
            that.find('#p-upload-step2').hide();
            that.find('#p-upload-step2-buttons').hide();
            that.find('#p-upload-step3').hide();
            that.find('#p-upload-step3-buttons').hide();
            that.find('#p-upload-step1').show();
            that.find('#p-upload-step1-buttons').show();
        },

        onUploadDialog: function ($dialog, dialog_instance) {
            $dialog.find('.files').empty();

            let hash = $.storage.get('photos/hash'),
                select = $dialog.find('#p-upload-step2 select[name=album_id]');

            if (!hash || !~hash.indexOf('album')) {
                select.find('option:first').attr('selected', true);
            } else {
                let album_id = parseInt(hash.replace(/\/?album\//, '')),
                    option = select.find('optgroup option[value='+album_id+']');

                if(option.length) {
                    option.attr('selected', true);
                } else {
                    select.find('option:first').attr('selected', true);
                }
            }

            let $form = $dialog.find('form');
            $form.on('submit', function (e) {
                e.preventDefault();
                $(this).find('#p-start-upload-button').click();
            });

            /* Update upload dialog position */
            $(document).on('fileuploadsend fileuploaddone', function () {
                dialog_instance.resize();
            })
        },

        massPost: function(params) {
            let chunk_count = params.chunk_count || 25,
                chunk_photo_ids,
                data = [];

            // serializeArray if needed
            if ($.isArray(params.data)) {
                data = data.concat(params.data);
            } else if (typeof params.data == 'object') {
                for (let k in params.data) {
                    data.push({
                        name: k,
                        value: params.data[k]
                    });
                }
            }
            params.type = 'post';

            let count = parseInt(params.count) || 0,
                photo_id = params.photo_id;
            if (params.photo_id) {
                photo_id = params.photo_id;
                if (!$.isArray(photo_id)) {
                    data.push({
                        name: 'photo_id',
                        value: photo_id
                    });
                    params.data = data;
                    params.success = params.fullSuccess;
                    $.ajax(params);
                    return;
                } else {
                    // make copy
                    photo_id = [].concat(photo_id);
                }
                count = photo_id.length;
            }
            if (typeof params.success == 'function') {
                let fn = params.success;
                params.success = function(r) {
                    fn(r, data);
                    process(r);
                };
            } else {
                params.success = process;
            }

            function process(r) {
                if (count) {
                    if (photo_id) {
                        chunk_photo_ids = photo_id.splice(0, chunk_count);
                        params.data = data.concat(chunk_photo_ids);
                    } else {
                        params.data = data;
                    }
                    count -= chunk_count;
                    count = count > 0 ? count : 0;
                    $.ajax(params);
                } else {
                    if (typeof params.fullSuccess == 'function') {
                        params.fullSuccess(r);
                    }
                }
            }
            process();
        },

        confirmDialog: function(options) {
            let d = $('#confirm-dialog');
            if (!d.length) {
                d = $('<div id="confirm-dialog"></div>');
                $("body").append(d);
            }
            if (options.url) {
                d.load(options.url, function() {
                    let opt = $.extend({}, options);
                    delete opt.url;
                    let self = $(this),
                        onOpen = opt.onOpen,
                        onSubmit = opt.onSubmit,
                        attr = opt.attr || null;

                    opt.onOpen = function($dialog, dialog) {
                        if (attr) {
                            for (let key in attr) {
                                if (attr.hasOwnProperty(key)) {
                                    $dialog.attr(key, attr[key]);
                                }
                            }
                        }
                        $dialog.find('input[type=submit]').focus();
                        if (typeof onOpen === 'function') {
                            onOpen($dialog, dialog);
                        }
                        if (typeof onSubmit === 'function') {
                            let $form = $dialog.find('form');
                            $form.on('submit', function (e) {
                                e.preventDefault();
                                onSubmit($dialog, dialog);
                            });
                        }
                    };
                    delete opt.onSubmit;
                    opt.$wrapper = self.find('div:first');
                    $.waDialog(opt);
                });
            }
        },

        showServerError: function(text) {
            if (text) {
                if (console) {
                    console.log('Server error occurred' + ":\n" + text); // show message in console
                }
                alert(text);
            } else if (console) {
                console.log('Server error occurred'); // show message in console
            }
        },

        // Drag-and-drop to sort sub-albums above photo list in a single album,
        // or root albums in Albums page
        initAlbumThumbsDragAndDrop: function($ul, parent_id) {
            $ul.sortable({
                delay: 200,
                delayOnTouchOnly: true,
                animation: 150,
                forceFallback: true,
                ghostClass:'album-list-ghost',
                chosenClass:'album-list-chosen',
                dragClass:'album-list-drag',
                onEnd: function(event) {
                    let $item = $(event.item);
                    /* хак для предотвращения срабатывания клика по элементу после его перетаскивания*/
                    let $link = $item.find('[onclick]'),
                        href = $link.attr('onclick');
                    $link.attr('onclick', 'javascript:void(0);');
                    setTimeout(() => $link.attr('onclick', href),500)

                    $.post("?module=album&action=move", {
                        id: $item.data('album-id'),
                        before_id: $item.next().data('album-id') || 0,
                        parent_id: parent_id
                    });
                }
            });
        },

        initPhotoThumbsDragAndDrop: function($ul, album_id) {

            $ul.sortable({
                delay: 200,
                delayOnTouchOnly: true,
                animation: 150,
                forceFallback: true,
                ghostClass:'photo-list-ghost',
                chosenClass:'photo-list-chosen',
                dragClass:'photo-list-drag',
                onEnd(event) {
                    let $item = $(event.item),
                        photo_id = [],
                        before_id = $item.next().data('photo-id') || 0;

                    /* хак для предотвращения срабатывания клика по элементу после его перетаскивания*/
                    let $link = $item.find('[onclick]'),
                        href = $link.attr('onclick');
                    $link.attr('onclick', 'javascript:void(0);');
                    setTimeout(() => $link.attr('onclick', href),500)

                    photo_id.push($item.data('photo-id'));

                    $.post('?module=album&action=photoMove', {
                        photo_id,
                        album_id,
                        before_id
                    }, function(r) {
                        if (r.status == 'ok') {
                            $.photos.photo_stream_cache.move(photo_id, before_id);
                        }
                    }, 'json');
                },
            });
        },

        hooks_manager: {
            handlers: {},
            /**
             * bind handler to hook (or handlers to hooks by hash-table). Function is polymorphous.
             *
             * @param string hook_name. Name of hook (search in files for tag: '@hook'
             * @param function handler
             * @param string name. Need for available to unbind. If omitted generate random name
             * @returns {*} name
             *
             * ...or in case of hash-table hooks to handlers correspongins
             * @param object map
             *
             */
            bind: function(hook_name, handler, name) {
                if (arguments.length == 1 && arguments[0] && typeof arguments[0] === 'object') {
                    var bind_map = arguments[0];
                    for (var hook_name in bind_map) {
                        if (bind_map.hasOwnProperty(hook_name)) {
                            this.bind(hook_name, bind_map[hook_name]);
                        }
                    }
                    return;
                }
                name = name || ('' + Math.random()).slice(2);
                this.handlers[hook_name] = this.handlers[hook_name] || {};
                this.handlers[hook_name][name] = handler;
                handler.handler_name = name;
                return name;
            },
            /**
             * Unbind handler from hook_name by name (or by handler itself in case of hanlder is not anonymous function)
             * @param string hook_name
             * @param string|function item. Name of handler
             */
            unbind: function(hook_name, item) {
                var name = item;
                if (typeof item === 'undefined') {
                    this.handlers[hook_name] = {};
                    return;
                }
                if (typeof item === 'function') {
                    name = item.handler_name;
                }
                if (typeof this.handlers[hook_name][name] === 'function') {
                    delete this.handlers[hook_name][name];
                }
            },
            /**
             * Trigger hook
             * @param hook_name
             *
             * @param argument
             * @param argument
             * ... (unlimited list of params)
             * This arguments will be available in correspond hook's handlers
             */
            trigger: function(hook_name/*argument, argument...*/) {
                var hook_handlers = this.handlers[hook_name];
                if (hook_handlers && typeof hook_handlers === 'object') {
                    var args = Array.prototype.slice.call(arguments, 1);
                    for (var name in hook_handlers) {
                        if (hook_handlers.hasOwnProperty(name) && typeof hook_handlers[name] === 'function') {
                            hook_handlers[name].apply(null, args);
                        }
                    }
                }
            }
        }
    };
})(jQuery);

$(function () {

    $('.dialog').off().on('change_loading_status', function(e, status) {
        var status = status || false,
            self = $(this),
            submit_input = self.find('input[type=submit]');

        if (status) {
            submit_input.attr('disabled', true).after('<i class="fas fa-spin fa-spinner loading"></i>');
        } else {
            submit_input.attr('disabled', false).next('i').remove();
        }
    });

    /**
     * Selection photos in photo-list section
     */

    // Prevent text selection in photo list while 'shift' key is held.
    (function() {
        var prevent_selection = false;
        $(document).on('selectstart', '#photo-list', function() {
            return !prevent_selection;
        });
        $(document).keydown(function(e) {
            if (e.keyCode == 16) {
                prevent_selection = true;
            }
        }).keyup(function(e) {
            if (e.keyCode == 16) {
                prevent_selection = false;
            }
        });
    })();

    // handler of triggerable 'select' event
    $(document).on('select', '#photo-list > li', function(e, selected, need_count) {
        selected = selected !== undefined ? selected : true;
        need_count = need_count !== undefined ? need_count : true;
        if (selected) {
            $(this).addClass('selected').find('input:first').attr('checked', true).prop('checked', true);
        } else {
            var select_all_photos = $('#selector-menu').find('[data-action="select-photos"]');
            if (select_all_photos.data('checked')) {
                select_all_photos.data('checked', false).
                        find('.unchecked').show().end().
                        find('.checked').hide();
            }
            $(this).removeClass('selected').find('input:first').attr('checked', false).prop('checked', false);
        }
        if (need_count) {
            $('.js-toolbar-dropdown-button').trigger('recount');
        }
    });


    // Shift+click on an image selects all between this one and previous one clicked
    (function() {
        var $last_li_checked = null;
        var $last_li_unchecked = null;
        $('#content').on('click', '#photo-list > li .p-details input:checkbox, #photo-list > li .p-details label, #photo-list > li .p-details .wa-checkbox', function(e) {
            var $li = $(this).closest('li');
            var $checkbox = $li.find('.p-details input:checkbox');
            var new_status;
            if ($checkbox.is(e.target)) {
                new_status = $checkbox.prop('checked');
            } else {
                new_status = !$checkbox.prop('checked');
                $checkbox.prop('checked', new_status).change();
            }

            if (new_status) {
                if (e.shiftKey && $last_li_checked) {
                    setCheckedBetween($last_li_checked, $li, true);
                }
                $last_li_checked = $li;
                $last_li_unchecked = null;
            } else {
                if (e.shiftKey && $last_li_unchecked) {
                    setCheckedBetween($last_li_unchecked, $li, false);
                }
                $last_li_checked = null;
                $last_li_unchecked = $li;
            }
            // Active Dropdown toolbar
            const $toolbar_dropdown = $('.js-toolbar-dropdown'),
                $counter = $('.js-toolbar-dropdown-button').find('.js-count'),
                $album_control = $toolbar_dropdown.parent(),
                selected_images_count = $('#photo-list :checkbox[name="photo_id[]"]:checked').length;

            $album_control.toggleClass('is-fixed', !!selected_images_count);
            if(selected_images_count) {
                $counter.text(selected_images_count).show()
            }else{
                $counter.empty().hide()
            }

        });
        function setCheckedBetween($from, $to, status) {
            if (!$from || !$to || !$from[0] || !$to[0] || $from.is($to[0])) {
                return;
            }

            var is_between = false;
            $to.parent().children().each(function(i, el) {
                if (!is_between) {
                    if ($from.is(el) || $to.is(el)) {
                        is_between = true;
                    }
                } else {
                    if ($from.is(el) || $to.is(el)) {
                        return false;
                    }
                    var $checkbox = $(el).find('.p-details input:checkbox');
                    if ($checkbox.prop('checked') != status) {
                        $checkbox.prop('checked', status).change();
                    }
                }
            });
        }
    })();

    // Highlight photos when selected
    $('#content')
        .off($.photos.namespace + '-photo-list-select-checkbox')
        .on('change' + $.photos.namespace + '-photo-list-select-checkbox',
            ':checkbox[name="photo_id[]"]',
            function () {
                if (this.checked) {
                    $(this).closest('li').addClass('selected');
                } else {
                    $(this).closest('li').removeClass('selected');
                }
                $('.js-toolbar-dropdown-button').trigger('recount');
            }
        );

    // Hide arrows over the photo if there are nowhere to go in its direction
    $.photos.hooks_manager.bind('afterLoadPhoto', function(status) {
        $('#p-block .p-one-photo > .p-image-nav .p-rewind').show();
        $('#p-block .p-one-photo > .p-image-nav .p-ff').show();

        if (status.first) {
            $('#p-block .p-one-photo > .p-image-nav .p-rewind').hide();
        }

        if (status.last) {
            $('#p-block .p-one-photo > .p-image-nav .p-ff').hide();
        }
    });

    $.photos_dragndrop.init();
});

/**
 * Toolbar menu constructor. Interface for convenient manipulations
 *
 * @param string selector
 * @param object options for active menu plugin-widget
 * @returns object interface for manipulations
 */
function ToolbarMenu(selector, options)
{
    var ul = null;
    return {
        init: function() {
            ul = $(selector);
            if (ul.length) {
                ul.activeMenu(options);
            }
            return this;
        },
        enable: function(items) {
            if (!items) {
                ul.parents('section:first').show();
                ul.activeMenu('fire');
            } else {
                ul.activeMenu('enable', items);
            }
            return this;
        },
        disable: function(items) {
            if (!items) {
                ul.parents('section:first').hide();
            } else {
                ul.activeMenu('disable', items);
            }
            return this;
        },
        setAction: function(name, action) {
            options[name] = action;
            if (ul) {
                ul.activeMenu('setOption', name, action);
            }
            return this;
        },
        getAction: function(name) {
            return options[name];
        },
        is: function(expr) {
            return ul? ul.is(expr) : $(selector).is(expr);
        }
    };
}
