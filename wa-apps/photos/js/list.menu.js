/**
 *
 */
(function($) {
    $.photos.menu.register('list','#share-menu', {
        embedAction: function() {
            let $wrapper = $('#embed-photo-list-dialog'),
                size = $.storage.get('photos/embed_size'),
                photo_list = $('#photo-list > li.selected'),
                // accumulate photo ids to comma-separated string
                photo_ids = photo_list.map(function() {
                    let photo_id = $(this).attr('data-photo-id'),
                        photo = $.photos.photo_stream_cache.getById(photo_id),
                        hash = photo_id;
                    if (photo.status <= 0) {
                        hash += ':' + photo.hash;
                    }
                    return hash;
                }).toArray().join(','),
                album = $.photos.getAlbum(),
                hash = $.photos.hash,
                context_parameters = {
                    photo_ids: photo_ids,
                    hash: hash,
                    size: size
                },
                dialog_url;

            if (album && album.status <= 0) {
                hash = hash.replace(/\/*$/, '')+':'+album.hash + '/';
                context_parameters.hash = hash;
            }

            dialog_url = '?module=dialog&action=embedPhotoList&photo_ids='+photo_ids+'&hash='+hash;

            if (size) {
                dialog_url += '&size='+size;
            }

            if (!$wrapper.length) {
                $wrapper = $('<div id="embed-photo-list-dialog"></div>');
                $("body").append($wrapper);
            }

            $wrapper.load(dialog_url, function() {
                $.waDialog({
                    $wrapper: $wrapper.find('div:first'),
                    onOpen($dialog, dialog) {
                        let select = $dialog.find('select[name=size]');
                        let $list_html_with_descriptions = $('#embed-photo-list-html-with-descriptions');
                        let $list_html = $('#embed-photo-list-html');

                        size && select.val(size);
                        select.change(function() {
                            let size = $(this).val();
                            context_parameters.size = size;
                            loadEmbedListContext(context_parameters);
                            $.storage.set('photos/embed_size', size);
                        });

                        $dialog.find('input[name=description]').on('click', function() {
                            if (this.checked) {
                                $list_html_with_descriptions.show().attr('disabled', false);
                                $list_html.hide().attr('disabled', true);
                            } else {
                                $list_html.show().attr('disabled', false);
                                $list_html_with_descriptions.hide().attr('disabled', true);
                            }
                        });

                        $dialog.find('input[name=link], textarea[name=urls], textarea[name=html], textarea[name=smarty_code]').on('click', function() {
                            let that = $(this),
                                selection = that.getSelection();

                            if (!selection.length) {
                                that.select();
                            }
                        });

                        $dialog.find('input[name=link]').focus().select();

                        $dialog.find('.switcher').activeMenu({
                            selectedPhotosAction: function(action) {
                                context_parameters.size = $dialog.find('select[name=size]').val();
                                context_parameters.photo_ids = photo_ids;
                                loadEmbedListContext(context_parameters);
                            },
                            allListPhotosAction: function(action) {
                                context_parameters.size = $dialog.find('select[name=size]').val();
                                context_parameters.photo_ids = '';
                                loadEmbedListContext(context_parameters);
                            }
                        }).find('li').click(function() {
                            $(this)
                                .parent()
                                .find('.selected')
                                .removeClass('selected')
                                .end()
                                .addClass('selected');
                        });

                        function loadEmbedListContext(data) {
                            let cached_data = loadEmbedListContext.data,
                                send_post = false;

                            for (let name in cached_data) {
                                if (cached_data.hasOwnProperty(name)) {
                                    if (cached_data[name] !== data[name]) {
                                        send_post = true;
                                        break;
                                    }
                                }
                            }

                            loadEmbedListContext.data = $.extend({}, data);

                            if (!send_post) {
                                return;
                            }

                            $dialog.find('.dialog-header').find('.loading').parent().show();

                            $.post("?module=photo&action=embedList",
                                data,
                                function (response) {
                                    if (response.status == 'ok') {
                                        let context = response.data.context;
                                        $dialog.find('input[name=link]').val(context.link);
                                        $dialog.find('a.link').attr('href', context.link);
                                        $dialog.find('textarea[name=urls]').val(context.urls);
                                        $list_html.val(context.html);
                                        $list_html_with_descriptions.val(context.html_with_descriptions);
                                        $dialog.find('textarea[name=smarty_code]').val(context.smarty_code);
                                        $dialog.find('h1 span:first').text('('+context.count+')');
                                        if (context.all_public) {
                                            $dialog.find('.exclamation-message').hide();
                                        } else {
                                            $dialog.find('.exclamation-message').show();
                                        }

                                        // Domains for domain selector
                                        context.domains = context.domains || {};
                                        $domain_selector.children().each(function() {
                                            let $option = $(this),
                                                domain = $option.attr('value');

                                            $option.data('frontend-url', (context.domains[domain] || {}).frontend_url || '');
                                        });

                                        saveContextData();
                                        updateDomainInFields();
                                    }
                                    $dialog.find('.dialog-header').find('.loading').parent().hide();
                                },
                                "json");
                        }

                        loadEmbedListContext.data = loadEmbedListContext.data || $.extend({}, context_parameters);

                        let $domain_selector = $dialog.find('select[name=domain]');
                        if ($domain_selector.length) {
                            saveContextData();
                            updateDomainInFields();
                            $domain_selector.change(updateDomainInFields);
                        }

                        function saveContextData() {
                            $.each(['textarea[name=urls]', '#embed-photo-list-html', '#embed-photo-list-html-with-descriptions'], function(i, selector) {
                                let $el = $(selector);
                                $el.data('context_data', $el.val());
                            });
                        }

                        function updateDomainInFields() {
                            if (!$domain_selector.length) {
                                return false;
                            }

                            $.each(['textarea[name=urls]', '#embed-photo-list-html', '#embed-photo-list-html-with-descriptions'], function(i, selector) {
                                let $el = $(selector);
                                $el.val($el.data('context_data').split($domain_selector.data('original-domain')).join($domain_selector.val()));
                            });

                            let $selectted_option = $domain_selector.children(':selected');
                            if ($selectted_option.data('frontend-url')) {
                                $dialog.find('input[name=link]').val($selectted_option.data('frontend-url')).closest('.field').slideDown();
                                $dialog.find('a.link').attr('href', $selectted_option.data('frontend-url'));
                            } else {
                                $dialog.find('input[name=link]').closest('.field').slideUp();
                            }
                        }
                    }
                });
            });

            return false;
        },
        beforeAnyAction: function(action) {
            if (!$.photos.isSelectedAnyPhoto() && action != 'blog-post' && action != 'embed') {
                alert($_('Please select at least one photo'));
                return false;
            }
        },
        blogPostAction: function() {
            let form = $('#blog-post-form');
            let photo_ids = [...document.querySelectorAll('#photo-list > li.selected')].map(el => el.getAttribute('data-photo-id'));

            if (!photo_ids.length) {
                alert($_('Please select at least one photo'));
                return;
            }

            let counter =[0,0];
            for(let i=0;i<photo_ids.length;i++) {
                let photo = $.photos.photo_stream_cache.getById(photo_ids[i]);
                if (!photo) {
                    photo = $.photos.photo_stack_cache.getById(photo_ids[i]);
                }
                if(photo) {
                    if(photo.hash) {
                        photo_ids[i] += ':'+photo.hash;
                    }
                    ++counter[photo.status];
                }
            }

            let content = '';
            if(true) {
                let album = $.photos.getAlbum(),
                    hash = $.photos.hash,
                    context_parameters = {
                        photo_ids: photo_ids.join(','),
                        hash: hash,
                        size: obligatory_size //XXX
                    };

                if (album && album.status <= 0) {
                    hash = hash.replace(/\/*$/, '')+':'+album.hash + '/';
                    context_parameters.hash = hash;
                }
                context_parameters.hash = null;
                $('#photo-blog-dialog :submit').attr('disabled',true);

                $.post("?module=photo&action=embedList",
                    context_parameters,
                    function (r) {
                        if (r.status == 'ok') {
                            let context = r.data.context;
                            form.find('[name="title"]:input').val($('#photo-list-name').text());
                            form.find('[name="text"]:input').val(blog_smarty_enabled?context.smarty_code:context.html_with_descriptions);
                            if(counter[0]) {
                                $('#photo-blog-dialog :submit').attr('disabled',false);
                            } else {
                                form.submit();
                            }
                        }
                    },
                    "json");
            } else {
                form.find('[name="title"]:input').val($('#photo-list-name').text());
                let id;
                if(blog_smarty_enabled) {
                    let photos_hash = ''+(photo_ids.length?("/id/"+photo_ids.join(',')):window.location.hash.replace(/.*#/,'').replace(/\/$/,''));
                    content = "\n"+
                        "{if $wa->photos}\n"+
                        "{$photos_size='big'}\n"+
                        "\t{$photos = $wa->photos->photos('"+photos_hash+"', $photos_size)}\n"+
                        "\t{foreach $photos as $photo}\n"+
                        "\t\t<p>{if $photo.description}{$photo.description}<br>{/if}\n"+
                        "\t\t<img src='{$photo[\"thumb_`$photos_size`\"]['url']}' alt='{$photo.name}.{$photo.ext}'></p>\n"+
                        "\t{/foreach}\n" +
                        "{/if}\n";

                } else {
                    if(!photo_ids.length) {
                        photo_ids = $('#photo-list > li').map(function() {
                            return $(this).attr('data-photo-id');
                        }).toArray();
                    }
                    while(id = photo_ids.shift()) {
                        let photo = $.photos.photo_stream_cache.getById(id);
                        if (!photo) {
                            photo = $.photos.photo_stack_cache.getById(id);
                        }
                        if(photo) {
                            content += '<p>'+(photo.description ? photo.description + '<br>\n': '\n') +
                                '    <img src="'+photo.thumb_big.url+'" alt="'+photo.name+'.'+photo.ext+'" width="'+photo.thumb_big.size.width+'" height="'+photo.thumb_big.size.height+'">\n</p>\n';
                        }
                    }
                }
            }

            if(counter[0]) {
                let $wrapper = $('#photo-blog-dialog').clone();
                $.waDialog({
                    $wrapper,
                    onOpen($dialog) {
                        let $submit = $dialog.find('[type="submit"]'),
                            notice = $dialog.find('p'),
                            count = [counter[0],photo_ids.length];

                        notice.html(notice.html().replace(/(%d)/g,() => count.shift()));

                        $submit.on('click', function (e) {
                            e.preventDefault();
                            form.submit();
                        });
                        //$('#photo-blog-dialog :submit').attr('disabled',true);

                        //count photos
                    }
                });
            } else {
                //form.submit();
            }

            return false;
        },
        onFire: function() {
            $('.js-toolbar-dropdown-button').trigger('recount');
        }
    });

    $.photos.menu.register('list','#organize-menu', {
        addToAlbumAction: function() {
            const $wrapper = $("#choose-albums");
            const showDialog = function() {
                $.waDialog({
                    $wrapper: $("#choose-albums"),
                    onOpen($dialog, dialog) {
                        $dialog.find('h3:first span:first').text('(' + $('#photo-list > li.selected').length + ')');
                        let $submit = $dialog.find('[type="submit"]'),
                            $form = $dialog.find('form');

                        $submit.on('click', function (e) {
                            e.preventDefault();
                            let photo_id = $('input[name^=photo_id]').serializeArray(),
                                album_id = $form.serializeArray();

                            if (!album_id.length) {
                                alert($_('Please select at least one album'));
                                return false;
                            }

                            if (!photo_id.length) {
                                dialog.close();
                                return false;
                            }

                            $dialog.trigger('change_loading_status', true);

                            $.photos.addToAlbums({
                                photo_id: photo_id,
                                album_id: album_id,
                                copy: 1,
                                fn: function() {
                                    $('#photo-list > li.selected').trigger('select', false);
                                    $dialog.trigger('change_loading_status', false);
                                    dialog.close();
                                }
                            });
                        });
                    }
                });
            };

            // no cache dialog
            if ($wrapper.length) {
                $wrapper.parent().remove();
            }

            let p = $('<div></div>').appendTo('body');
            p.load('?module=dialog&action=albums', showDialog);
        },
        assignTagsAction: function() {
            let default_text = $_('add a tag'),
                $d = $('#photo-list-tags-dialog'),
                $wrapper = $d.clone(),
                $template;

            $.waDialog({
                $wrapper,
                onOpen($dialog, dialog) {
                    let $tags_control = $dialog.find('#photos-list-tags'),
                        photo_ids = [],
                        tags = {},
                        photo_stream = $.photos.photo_stream_cache.getAll(),
                        $tags_remove_list = $dialog.find("#photos-tags-remove-list"),
                        $tags_remove = $dialog.find("#photo-tags-remove"),
                        $form = $dialog.find('form'),
                        $checked_photos = $('input[name^=photo_id]:checked'),
                        $popular_tags = $dialog.find('#photos-popular-tags');

                    $template = $d.detach();

                    if(!$dialog.find('.tagsinput').length) {
                        $.fn.tagsInput.bind($tags_control)
                        $tags_control.tagsInput({
                            autocomplete_url: '?module=tag&action=list',
                            height: 200,
                            width: '100%',
                            defaultText: default_text
                        });
                    }

                    $tags_control.importTags('');

                    $dialog.find('.js-selected-counter').text('(' + $checked_photos.length + ')');

                    $checked_photos.each(function () {
                        photo_ids.push($(this).val());
                    });

                    // union tags
                    for (let i = 0, n = $.photos.photo_stream_cache.length(); i < n; i++) {
                        let p = photo_stream[i];

                        if (photo_ids.some((id) => id == p.id)) {
                            let p_tags = p.tags;
                            if ($.isEmptyObject(p_tags)) {
                                continue;
                            }
                            if ($.isEmptyObject(tags)) {
                                tags = p_tags;
                            } else {
                                for (let tag_id in p_tags) {
                                    if (p_tags.hasOwnProperty(tag_id)) {
                                        tags[tag_id] = p_tags[tag_id];
                                    }
                                }
                            }
                        }
                    }

                    $tags_remove_list.empty();

                    if (!jQuery.isEmptyObject(tags)) {
                        $tags_remove.show();
                        for (let tag_id in tags) {
                            $tags_remove_list
                                .append($('<label></label>')
                                .text(tags[tag_id])
                                .prepend('<input name="delete_tags[]" value="' + tag_id + '" type="checkbox"> '))
                                .append('<br>');
                        }
                    } else {
                        $tags_remove.hide();
                    }

                    $popular_tags.off('click.photos', 'a').on('click.photos', 'a', function () {
                        let name = $(this).text();
                        $tags_control.removeTag(name);
                        $tags_control.addTag(name);
                    });

                    let $input = $dialog.find('#photos-list-tags_tag');
                    if ($input.length && ($input.val() != default_text)) {
                        let e = jQuery.Event("keypress",{
                            which:13
                        });
                        $input.trigger(e);
                    }

                    $form.on('submit', function (e) {
                        e.preventDefault();

                        let photo_id = $checked_photos.serializeArray(),
                            tags = $tags_control.val(),
                            delete_tags = $tags_remove_list.find('input[name^=delete_tags]:checked').serializeArray();

                        if (!tags.length && !delete_tags.length) {
                            alert($_('Please select at least one tag'));
                            return false;
                        }

                        if (!photo_id.length) {
                            dialog.close();
                            return false;
                        }

                        $dialog.trigger('change_loading_status', true);
                        $.photos.assignTags({
                            photo_id,
                            tags,
                            delete_tags,
                            fn: function(response) {
                                if (response.status == 'ok') {
                                    let photo_tags = response.data.tags,
                                        $photo_list = $('#photo-list > li.selected'),
                                        html;

                                    for (let id in photo_tags) {
                                        if (photo_tags.hasOwnProperty(id)) {
                                            html = tmpl('template-photo-list-photo-tags', {
                                                tags: photo_tags[id]
                                            });
                                            $photo_list.filter('[data-photo-id='+id+']:first').find('.tags').replaceWith(html);
                                        }
                                    }

                                    $photo_list.trigger('select', false);
                                    $dialog.trigger('change_loading_status', false);
                                    dialog.close();
                                }
                            }
                        });
                    });
                },
                onClose() {
                    $('body').append($template);
                }
            });
        },
        deleteFromAlbumAction: function() {
            let photo_id = $('input[name^=photo_id]').serializeArray();
            if (photo_id.length) {
                let album_id = $.photos.getAlbum().id;
                $.post('?module=photo&action=deleteFromAlbum&id=' + album_id, photo_id, function() {
                    $.photos.dispatch();
                }, 'json');
            }
        },
        deletePhotosAction: function() {
            let photo_id = $('input[name^=photo_id]').serializeArray();
            if (photo_id.length) {
                $.photos.confirmDialog({
                    url: '?module=dialog&action=confirmDeletePhotos&cnt=' + photo_id.length,
                    onSubmit: function(d, d_instance) {
                        d.trigger('change_loading_status', true);
                        $.photos.deletePhotos(photo_id, function() {
                            $.photos.unsetCover();
                            d.trigger('change_loading_status', false);
                            $('#photo-list > li.selected').trigger('select', false);
                            d_instance.close()
                        });
                        return false;
                    }
                });
            }
        },
        setRateAction: function() {
            $.photos.confirmDialog({
                url: '?module=dialog&action=rates',
                attr: {
                    id: 'set-rate'
                },
                onOpen($dialog, dialog) {
                    $dialog.find('.p-rate-photo-counter').text('('+$('input[name^=photo_id]:checked').length+')');
                    const $rate = $dialog.find('#photos-rate');

                    $rate.rateWidget({
                        withClearAction: false,
                        onUpdate() {
                            let rate = $rate.rateWidget('getOption', 'rate'),
                                photo_id = $('input[name^=photo_id]').map(function() {
                                    return this.checked ? { name: 'id[]', value: this.value } : null;
                                }).toArray();

                            if (!photo_id.length) {
                                dialog.close();
                                return false;
                            }

                            $.photos.saveField({
                                id: photo_id,
                                name: 'rate',
                                value: rate,
                                fn: function(response) {
                                    if (response.status == 'ok') {
                                        let response_data = response.data,
                                            allowed_photo_id = response_data.allowed_photo_id && !$.isArray(response_data.allowed_photo_id) ?
                                            response_data.allowed_photo_id :
                                            {};

                                        $('#photo-list > li.selected')
                                            .each(function () {
                                                let self = $(this);
                                                if (allowed_photo_id[self.attr('data-photo-id')]) {
                                                    $.photos.updateThumbRate(self, rate);
                                                }
                                            })
                                            .find('input:first').trigger('select', false);

                                        if (response_data.count) {
                                            $('#rated-count').text(response_data.count > 0 ? response_data.count : '');
                                        }

                                        if (response_data.alert_msg) {
                                            alert(response_data.alert_msg);
                                        }

                                        dialog.close();
                                    }
                                }
                            });

                            return false;
                        }
                    });
                }
            });



            // let dialog = $.waDialog({
            //     html: $('<div id="set-rate"></div>'),
            //     url: '?module=dialog&action=rates',
            //     className: 'width300px height200px',
            //     onOpen: function(d) {
            //         dialog.find('.p-rate-photo-counter').text('('+$('input[name^=photo_id]:checked').length+')');
            //         $('#photos-rate', d).rateWidget({
            //             withClearAction: false,
            //             onUpdate: function (rate) {
            //                 var d = dialog;
            //                 var rate = $('#photos-rate', d).rateWidget('getOption', 'rate');
            //                 var photo_id = $('input[name^=photo_id]').map(function() {
            //                     return this.checked ? { name: 'id[]', value: this.value } : null;
            //                 }).toArray();
            //                 if (!photo_id.length) {
            //                     d.trigger('close');
            //                     return false;
            //                 }
            //                 $.photos.saveField({
            //                     id: photo_id,
            //                     name: 'rate',
            //                     value: rate,
            //                     fn: function(r) {
            //                         if (r.status == 'ok') {
            //                             var allowed_photo_id = r.data.allowed_photo_id && !$.isArray(r.data.allowed_photo_id) ?
            //                                     r.data.allowed_photo_id :
            //                                     {};
            //                             $('#photo-list > li.selected').each(function() {
            //                                 var self = $(this);
            //                                 if (allowed_photo_id[self.attr('data-photo-id')]) {
            //                                     $.photos.updateThumbRate(self, rate);
            //                                 }
            //                             }).find('input:first').trigger('select', false);
            //                             if (r.data.count) {
            //                                 $('#rated-count').text(r.data.count > 0 ? r.data.count : '');
            //                             }
            //                             if (r.data.alert_msg) {
            //                                 alert(r.data.alert_msg);
            //                             }
            //                             d.trigger('close');
            //                         }
            //                     }
            //                 });
            //                 return false;
            //             }
            //     });
            //  }
            // });
        },
        makeStackAction: function() {
            var photo_id = $('input[name^=photo_id]').serializeArray();
            if (photo_id.length < 2) {
                alert($_('Please select at least two photos'));
                return false;

            }
            $.photos.makeStack(photo_id);
            return false;
        },
        manageAccessAction: function() {
            let photo_id = $('input[name^=photo_id]').serializeArray();
            $.photos.showManageAccessDialog(
                photo_id,
                function($dialog, dialog) {
                    let data = $dialog.serializeArray(),
                        status = $dialog.find('input[name=status]:checked').val();

                    if (!photo_id.length) {
                        dialog.close();
                    }

                    $dialog.trigger('change_loading_status', true);

                    let $photo_list = $('#photo-list > li.selected');

                    $.photos.saveAccess({
                        photo_id: photo_id,
                        data: data,
                        fn: function(r, allowed_photo_id) {
                            for (let i = 0, n = allowed_photo_id.length; i < n; ++i) {
                                let photo_id = allowed_photo_id[i],
                                    corner_top = $photo_list.filter('[data-photo-id='+photo_id+']:first').find('.p-image-corner.top.left');
                                // update icon in top-left corner
                                corner_top.find('.fa-lock').remove();
                                if (status <= 0) {
                                    corner_top.append('<i class="fas fa-lock p-private-photo" title="' + $_('Private photo') + '"></i>');
                                }
                            }
                            $.photos._updateStreamCache(allowed_photo_id, {
                                status: status
                            });
                            $photo_list.trigger('select', false);
                            $dialog.trigger('change_loading_status', false);
                            dialog.close();
                        }
                    });
                    return false;
                }
            );
            return false;
        },
        beforeAnyAction: function(name) {
            if (name != 'make-stack') {
                if (!$.photos.isSelectedAnyPhoto()) {
                    alert($_('Please select at least one photo'));
                    return false;
                }
            }
        },
        onFire: function() {
            $('.js-toolbar-dropdown-button').trigger('recount');
        }

    });

    $.photos.menu.register('list','#save-menu', {

        saveDescriptionAction: function() {

            var data = {},matches,id,field;
            $('#photo-list.p-descriptions :text,#photo-list.p-descriptions textarea').each(function(){
                if ( (this.defaultValue != this.value) && (matches = $(this).attr('name').match(/^photo\[(\d+)\]\[(\w+)\]$/)) ){
                    id = matches[1];
                    field = matches[2];
                    var cached = $.photos.photo_stream_cache.getById(id);
                    if(!cached || (this.value != cached[field])) {
                        if(!data[id]) {
                            data[id] = {'id':id};
                        }
                        data[id][field] = this.value;
                    }
                }
            });
            $.photos.saveFields(data);

            //TODO check response and update default values for inputs
            $('#photo-list.p-descriptions :text.highlighted,#photo-list.p-descriptions textarea.highlighted').each(function(){
                $(this).removeClass('highlighted');
            });
            var $counter = $('.js-toolbar-dropdown-button > .js-count');
            if($counter.length) {
                var count = 0;
                $counter.text(count);
                if(!count) {
                    $('#save-menu-block input.button').removeClass('yellow');
                    $counter.hide();
                }
            }
            return false;
        },
        hideNameAction: function(item, e) {
            var checkbox = item.find(':checkbox'),
                checked = checkbox.prop('checked'),
                $li = $('#photo-list > li');

            if(e.target.tagName != 'INPUT') {
                checked = !checked;
            }

            if (checked) {
                $li.find(':text[name$="\[name\]"]').hide();
            } else {
                $li.find(':text[name$="\[name\]"]').show();
            }
            $li.find('textarea[name$="\[description\]"]')
                .css('height',`${!!checked ? '+=46' : '-=46'}`)
                .toggleClass('js-small', !checked)
                .toggleClass('js-big', !!checked);

            setTimeout(function(){checkbox.prop('checked',checked);},50);
            $.storage.set('photos/list/hide_name',checked);
        },
        onFire: function() {
            var $counter = $('.js-toolbar-dropdown-button > .js-count');
            if($counter.length) {
                $counter.text('0');
                $('#save-menu-block input.button').removeClass('yellow');
                $counter.hide();
            }
        },
        onInit: function(container) {
            container.find('[data-action="hide-name"] :checkbox').prop('checked', $.storage.get('photos/list/hide_name',false));
            var handler = function(){
                var changed = [],matches;
                $('#photo-list.p-descriptions :text,#photo-list.p-descriptions textarea').each(function(){
                    if ( (this.defaultValue != this.value) && (matches = $(this).attr('name').match(/^photo\[(\d+)\]\[(\w+)\]$/)) ){
                        var id = matches[1];
                        if(changed.indexOf(id) < 0) {
                            var cached = $.photos.photo_stream_cache.getById(id);
                            if(!cached || (this.value != cached[matches[2]])) {
                                $(this).addClass('highlighted');
                                changed.push(id);
                            } else if ($(this).hasClass('highlighted')) {
                                $(this).removeClass('highlighted');
                            }
                        }

                    } else if ($(this).hasClass('highlighted')) {
                        $(this).removeClass('highlighted');
                    }
                });
                var $counter = $('.js-toolbar-dropdown-button > .js-count');
                var count = changed.length;
                if($counter.length) {
                    $counter.text(count);
                }
                if(!count) {
                    $('#save-menu-block input.button').removeClass('yellow');
                    $counter.hide();
                } else {
                    $('#save-menu-block input.button').addClass('yellow');
                    $counter.show();
                }
            };
            $('#p-content').on('change.photos-save-menu', '#photo-list.p-descriptions :text, #photo-list.p-descriptions textarea', handler);
            $('#p-content').on('keyup.photos-save-menu', '#photo-list.p-descriptions :text, #photo-list.p-descriptions textarea', handler);
            //change data handler
        }
    });

    $.photos.menu.register('list','#selector-menu', {

        selectPhotosAction: function(item) {
            var $counter = $('.js-toolbar-dropdown-button > .js-count');

            if (!$.photos.total_count) {
                const alertNoItems = $(`<div class="alert-fixed-box"><span class="alert warning">${item[0].dataset.errorMsg}</span></div>`);
                $('body').append(alertNoItems);
                setTimeout(() => {
                    alertNoItems.remove();
                }, 2000)
                return;
            }

            if (!item.data('checked')) {
                item.data('checked', true);
                item.find('.checked').show().end().
                        find('.unchecked').hide();
                $counter.text($.photos.total_count).show();
            } else {
                item.data('checked', false);
                item.find('.unchecked').show().end().
                        find('.checked').hide();
                $counter.text('').hide();
            }
            $('#photo-list > li').trigger('select', [!!item.data('checked'), false]);
        }
    });

    $('#p-sidebar a, #wa-header a, #js-photos-view-toggle button, #photo-list div.p-image a').on('click', function (e) {
        if ($('#save-menu-block').is(':visible') && $('#save-menu-block input.button').hasClass('yellow')) {
            if (!confirm($_("Unsaved changes will be lost if you leave this page now. Are you sure?"))) {
                e.preventDefault();
                return false;
            }
        }
    });
})(jQuery);
