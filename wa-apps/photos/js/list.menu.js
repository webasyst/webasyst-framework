/**
 *
 */
(function($) {
    $.photos.menu.register('list','#organize-menu', {
        addToAlbumAction: function() {
            var d = $("#choose-albums");
            var showDialog = function() {
                $('#choose-albums').waDialog({
                    onLoad: function() {
                        $(this).find('h1:first span:first').text('(' + $('#photo-list li.selected').length + ')');
                    },
                    onSubmit: function (d) {
                        var photo_id = $('input[name^=photo_id]').serializeArray(),
                            album_id = $(this).serializeArray();
                        if (!album_id.length) {
                            alert($_('Please select at least one album'));
                            return false;
                        }
                        if (!photo_id.length) {
                            d.trigger('close');
                            return false;
                        }
                        d.trigger('change_loading_status', true);
                        $.photos.addToAlbums({
                            photo_id: photo_id,
                            album_id: album_id,
                            copy: 1,
                            fn: function() {
                                $('#photo-list li.selected').trigger('select', false);
                                d.trigger('change_loading_status', false).trigger('close');
                            }
                        });
                        return false;
                    }
                });
            };
            
            // no cache dialog
            if (d.length) {
                d.parent().remove();
            }
            
            var p = $('<div></div>').appendTo('body');
            p.load('?module=dialog&action=albums', showDialog);
        },
        assignTagsAction: function() {
            var default_text = $_('add a tag');
            $('#photo-list-tags-dialog').waDialog({
                onLoad: function() {
                    var tags_control = $('#photo-list-tags-dialog #photos-list-tags');
                    if(!$('#photo-list-tags-dialog .tagsinput').length) {
                        tags_control.tagsInput({
                            autocomplete_url: '?module=tag&action=list',
                            height: 200,
                            width: '100%',
                            defaultText: default_text
                        });
                    }
                    tags_control.importTags('');
                    $('#photo-list-tags-dialog .js-selected-counter').text('(' + $('input[name^=photo_id]:checked').length + ')');
                    var photo_ids = [];
                    $('input[name^=photo_id]:checked').each(function () {
                        photo_ids.push($(this).val());
                    });
                    var tags = {},
                        photo_stream = $.photos.photo_stream_cache.getAll();
                    // union tags
                    for (var i = 0, n = $.photos.photo_stream_cache.length(); i < n; i++) {
                        var p = photo_stream[i];
                        if ($.inArray(p.id, photo_ids) != -1) {
                            var p_tags = p.tags;
                            if ($.isEmptyObject(p_tags)) {
                                continue;
                            }
                            if ($.isEmptyObject(tags)) {
                                tags = p_tags;
                            } else {
                                for (var tag_id in p_tags) {
                                    if (p_tags.hasOwnProperty(tag_id)) {
                                        tags[tag_id] = p_tags[tag_id];
                                    }
                                }
                            }
                        }
                    }
                    $("#photos-tags-remove-list").empty();
                    if (!jQuery.isEmptyObject(tags)) {
                        $("#photo-tags-remove").show();
                        for (var tag_id in tags) {
                            $("#photos-tags-remove-list").append($('<label></label>').text(tags[tag_id]).prepend('<input name="delete_tags[]" value="' + tag_id + '" type="checkbox"> ')).append('<br>');
                        }
                    } else {
                        $("#photo-tags-remove").hide();
                    }
                    $("#photo-list-tags-dialog .dialog-window").height($("#photo-list-tags-dialog .dialog-content-indent").outerHeight());
                    
                    $('#photos-popular-tags').off('click.photos', 'a').
                            on('click.photos', 'a', function() {
                                var name = $(this).text();
                                tags_control.removeTag(name);
                                tags_control.addTag(name);
                            });
                },
                onSubmit: function (d) {
                    var input = $('#photos-list-tags_tag');
                    if (input.length && (input.val() != default_text)) {
                        var e = jQuery.Event("keypress",{
                            which:13
                        });
                        input.trigger(e);
                    }
                    var photo_id = $('input[name^=photo_id]:checked').serializeArray(),
                        tags = $('#photo-list-tags-dialog #photos-list-tags').val(),
                        delete_tags = $('#photos-tags-remove-list input[name^=delete_tags]:checked').serializeArray();

                    if (!tags.length && !delete_tags.length) {
                        alert($_('Please select at least one tag'));
                        return false;
                    }
                    if (!photo_id.length) {
                        d.trigger('close');
                        return false;
                    }
                    d.trigger('change_loading_status', true);
                    $.photos.assignTags({
                        photo_id: photo_id,
                        tags: tags,
                        delete_tags: delete_tags,
                        fn: function(r) {
                            if (r.status == 'ok') {
                                var photo_tags = r.data.tags,
                                    photo_list = $('#photo-list li.selected'),
                                    html;
                                for (var id in photo_tags) {
                                    if (photo_tags.hasOwnProperty(id)) {
                                        html = tmpl('template-photo-list-photo-tags', {
                                            tags: photo_tags[id]
                                        });
                                        photo_list.filter('[data-photo-id='+id+']:first').find('.tags>span').html(html);
                                    }
                                }
                                photo_list.trigger('select', false);
                                d.trigger('change_loading_status', false).trigger('close');
                            }
                        }
                    });

                    return false;
                }
            });
        },
        deleteFromAlbumAction: function() {
            var photo_id = $('input[name^=photo_id]').serializeArray();
            if (photo_id.length) {
                var album_id = $.photos.getAlbum().id;
                $.post('?module=photo&action=deleteFromAlbum&id=' + album_id, photo_id, function() {
                    $.photos.dispatch();
                }, 'json');
            }
        },
        deletePhotosAction: function() {
            var photo_id = $('input[name^=photo_id]').serializeArray();
            if (photo_id.length) {
                $.photos.confirmDialog({
                    url: '?module=dialog&action=confirmDeletePhotos&cnt=' + photo_id.length,
                    onSubmit: function(d) {
                        d.trigger('change_loading_status', true);
                        $.photos.deletePhotos(photo_id, function() {
                            $.photos.unsetCover();
                            d.trigger('change_loading_status', false).trigger('close');
                            $('#photo-list li.selected').trigger('select', false);
                        });
                        return false;
                    }
                });
            }
        },
        setRateAction: function() {
            var dialog = $('<div id="set-rate"></div>').waDialog({
                url: '?module=dialog&action=rates',
                className: 'width300px height200px',
                onLoad: function(d) {
                    dialog.find('.p-rate-photo-counter').text('('+$('input[name^=photo_id]:checked').length+')');
                    $('#photos-rate', d).rateWidget({
                        withClearAction: false,
                        onUpdate: function (rate) {
                            var d = dialog;
                            var rate = $('#photos-rate', d).rateWidget('getOption', 'rate');
                            var photo_id = $('input[name^=photo_id]').map(function() {
                                return this.checked ? { name: 'id[]', value: this.value } : null;
                            }).toArray();
                            if (!photo_id.length) {
                                d.trigger('close');
                                return false;
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
                                        $('#photo-list li.selected').each(function() {
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
                                        d.trigger('close');
                                    }
                                }
                            });
                            return false;
                        }
                });
             }
            });
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
            var photo_id = $('input[name^=photo_id]').serializeArray();
            $.photos.showManageAccessDialog(
                photo_id,
                function(d) {
                    var f = $(this),
                        data = f.serializeArray(),
                        status = f.find('input[name=status]:checked').val();

                    if (!photo_id.length) {
                        d.trigger('close');
                        return false;
                    }
                    d.trigger('change_loading_status', true);
                    $.photos.saveAccess({
                        photo_id: photo_id,
                        data: data,
                        fn: function(r, allowed_photo_id) {
                            var photo_list = $('#photo-list li.selected');
                            for (var i = 0, n = allowed_photo_id.length; i < n; ++i) {
                                var photo_id = allowed_photo_id[i],
                                    corner_top = photo_list.filter('[data-photo-id='+photo_id+']:first').find('.p-image-corner.top.left');
                                // update icon in top-left corner
                                corner_top.find('.lock-bw').remove();
                                if (status <= 0) {
                                    corner_top.append('<i class="icon16 lock-bw p-private-photo" title="' + $_('Private photo') + '"></i>');
                                }
                            }
                            $.photos._updateStreamCache(allowed_photo_id, {
                                status: status
                            });
                            $('#photo-list li.selected').trigger('select', false);
                            d.trigger('change_loading_status', false).trigger('close');
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
            $('#organize-menu').trigger('recount');
        }

    });
    $.photos.menu.register('list','#selector-menu', {

        selectPhotosAction: function(item) {
            var counter = $('#share-menu-block, #organize-menu-block').find('.count');
            if (!item.data('checked')) {
                item.data('checked', true);
                item.find('.checked').show().end().
                        find('.unchecked').hide();
                counter.text($.photos.total_count).show();
            } else {
                item.data('checked', false);
                item.find('.unchecked').show().end().
                        find('.checked').hide();
                counter.text('').hide();
            }
            $('#photo-list li').trigger('select', [!!item.data('checked'), false]);
        }
    });

    $.photos.menu.register('list','#share-menu', {
        embedAction: function() {
            var d = $('#embed-photo-list-dialog'),
                size = $.storage.get('photos/embed_size'),
                photo_list = $('#photo-list li.selected'),

                // accumulate photo ids to comma-separated string
                photo_ids = photo_list.map(function() {
                    var photo_id = $(this).attr('data-photo-id'),
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
            var dialog_url = '?module=dialog&action=embedPhotoList&photo_ids='+photo_ids+'&hash='+hash;
            if (size) {
                dialog_url += '&size='+size;
            }
            if (!d.length) {
                d = $('<div id="embed-photo-list-dialog"></div>');
                $("body").append(d);
            }
            d.load(dialog_url, function() {
                d.find('div:first').waDialog({
                    onLoad: function() {
                        var select = d.find('select[name=size]');
                        select.val(size);
                        select.change(function() {
                            var size = $(this).val();
                            context_parameters.size = size;
                            loadEmbedListContext(context_parameters);
                            $.storage.set('photos/embed_size', size);
                        });

                        d.find('input[name=description]').click(function() {
                            if (this.checked) {
                                $('#embed-photo-list-html-with-descriptions').show().attr('disabled', false);
                                $('#embed-photo-list-html').hide().attr('disabled', true);
                            } else {
                                $('#embed-photo-list-html').show().attr('disabled', false);
                                $('#embed-photo-list-html-with-descriptions').hide().attr('disabled', true);
                            }
                        });

                        d.find('input[name=link], textarea[name=urls], textarea[name=html], textarea[name=smarty_code]').click(function() {
                            var selection = $(this).getSelection();
                            if (!selection.length) {
                                $(this).select();
                            }
                        });
                        d.find('input[name=link]').focus().select();

                        d.find('.switcher').activeMenu({
                            selectedPhotosAction: function(action) {
                                context_parameters.size = d.find('select[name=size]').val();
                                context_parameters.photo_ids = photo_ids;
                                loadEmbedListContext(context_parameters);
                            },
                            allListPhotosAction: function(action) {
                                context_parameters.size = d.find('select[name=size]').val();
                                context_parameters.photo_ids = '';
                                loadEmbedListContext(context_parameters);
                            }
                        }).find('li').click(function() {
                            $(this).parent().find('.selected').removeClass('selected').end().end().addClass('selected');
                        });
                        function loadEmbedListContext(data) {
                            var cached_data = loadEmbedListContext.data,
                                send_post = false;
                            for (var name in cached_data) {
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
                            d.find('h1').find('.loading').parent().show();
                            $.post("?module=photo&action=embedList",
                                data,
                                function (r) {
                                    if (r.status == 'ok') {
                                        var context = r.data.context;
                                        d.find('input[name=link]').val(context.link);
                                        d.find('a.link').attr('href', context.link);
                                        d.find('textarea[name=urls]').val(context.urls);
                                        d.find('#embed-photo-list-html').val(context.html);
                                        d.find('#embed-photo-list-html-with-descriptions').val(context.html_with_descriptions);
                                        d.find('textarea[name=smarty_code]').val(context.smarty_code);
                                        d.find('h1 span:first').text('('+context.count+')');
                                        if (context.all_public) {
                                            d.find('.exclamation-message').hide();
                                        } else {
                                            d.find('.exclamation-message').show();
                                        }
                                    }
                                    d.find('h1').find('.loading').parent().hide();
                                },
                            "json");
                        }
                        loadEmbedListContext.data = loadEmbedListContext.data || $.extend({}, context_parameters);
                    },
                    onSubmit: function() {
                        return false;
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
          var form = $('#blog-post-form');
          var photo_ids = $('#photo-list li.selected').map(function() {
              return $(this).attr('data-photo-id');
          }).toArray();
          var counter =[0,0];
          for(var i=0;i<photo_ids.length;i++) {
              var photo = $.photos.photo_stream_cache.getById(photo_ids[i]);
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

          var content = '';
          if(true) {
              var album = $.photos.getAlbum(),
              hash = $.photos.hash,
              context_parameters = {
                      photo_ids: photo_ids.join(','),
                      hash: hash,
                      size:obligatory_size//XXX
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
                          var context = r.data.context;
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
              var id;
              if(blog_smarty_enabled) {
                  var photos_hash = ''+(photo_ids.length?("/id/"+photo_ids.join(',')):window.location.hash.replace(/.*#/,'').replace(/\/$/,''));
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
                      photo_ids = $('#photo-list li').map(function() {
                          return $(this).attr('data-photo-id');
                      }).toArray();
                  }
                  while(id = photo_ids.shift()) {
                      var photo = $.photos.photo_stream_cache.getById(id);
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
              $('#photo-blog-dialog').waDialog({
              'onLoad':function(){
                  $('#photo-blog-dialog :submit').attr('disabled',true);
                  var notice = $('#photo-blog-dialog p');
                  var count = [counter[0],photo_ids.length];
                  notice.html(notice.html().replace(/(%d)/g,function(){return count.shift();}));
                  //count photos
              },
              'onSubmit':function(){
                  form.submit();
                  return false;
              }
          });
          } else {
              //form.submit();
          }

          return false;
        },
        onFire: function() {
            $('#share-menu').trigger('recount');
        }
    });


    $('#p-sidebar a, #wa-header a, a.album-view, #photo-list div.p-image a').live('click', function (e) {
        if ($('#save-menu-block').is(':visible') && $('#save-menu-block input.button').hasClass('yellow')) {
            if (!confirm($_("Unsaved changes will be lost if you leave this page now. Are you sure?"))) {
                e.preventDefault();
                return false;
            }
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
            var counter = $('#save-menu-block .count.indicator');
            if(counter.length) {
                var count = 0;
                counter.text(count);
                if(!count) {
                    $('#save-menu-block input.button').removeClass('yellow').addClass('green');
                    counter.hide();
                }
            }
            return false;
        },
        hideNameAction: function(item, e) {
            var checkbox = item.find(':checkbox');
            var checked = checkbox.prop('checked');
            if(e.target.tagName != 'INPUT') {
                checked = !checked;
            }
            if (checked) {
                $('#photo-list li :text[name$="\[name\]"]').hide();
                $('#photo-list li textarea[name$="\[description\]"].js-small').css('height','+=27').removeClass('js-small').addClass('js-big');
            } else {
                $('#photo-list li :text[name$="\[name\]"]').show();
                $('#photo-list li textarea[name$="\[description\]"].js-big').css('height','-=27').removeClass('js-big').addClass('js-small');
            }
            setTimeout(function(){checkbox.attr('checked',checked);},50);
            $.storage.set('photos/list/hide_name',checked);
        },
        onFire: function() {
            var counter = $('#save-menu-block .count.indicator');
            if(counter.length) {
                counter.text('0');
                $('#save-menu-block input.button').removeClass('yellow').addClass('green');
                counter.hide();
            }
        },
        onInit: function(container) {
            container.find('[data-action="hide-name"] :checkbox').prop('checked', $.storage.get('photos/list/hide_name',false));
            $('#photo-list.p-descriptions :text,#photo-list.p-descriptions textarea').live('change, keyup',function(){
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
                var counter = $('#save-menu-block .count.indicator');
                var count = changed.length;
                if(counter.length) {
                    counter.text(count);
                }
                if(!count) {
                    $('#save-menu-block input.button').removeClass('yellow').addClass('green');
                    counter.hide();
                } else {
                    $('#save-menu-block input.button').removeClass('green').addClass('yellow');
                    counter.show();
                }
            });
            //change data handler
        }
    });
})(jQuery);