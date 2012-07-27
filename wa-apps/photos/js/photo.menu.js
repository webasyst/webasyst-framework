/**
 *
 */
(function($) {
    $.photos.menu.register('photo', '#photo-organize-menu', {
        addToAlbumAction: function() {
            $('<div id="choose-albums-photo"></div>').waDialog({
                url: '?module=dialog&action=albums&id=' + $.photos.getPhotoId(),
                className: 'width600px height400px',
                onSubmit: function (d) {
                    var photo_id = $.photos.photo_stream_cache.getCurrent().id;
                    $.photos.addToAlbums({
                        photo_id: photo_id,
                        album_id: $(this).serializeArray(),
                        copy: 0,
                        fn: function(r) {
                            if (r.status == 'ok') {
                                var old_albums = r.data.old_albums || [],
                                    album = $.photos.getAlbum(),
                                    albums = r.data.albums;
                                if (album) {
                                    for (var i = 0, n = old_albums.length; i < n; ++i) {
                                        // if now we are inside the one of old albums
                                        if (album.id == old_albums[i].id) {
                                            if (!albums.length) {
                                                $.photos.goToHash('/photo/'+photo_id);
                                            } else {
                                                $.photos.goToHash('/album/'+albums[0].id+'/photo/'+photo_id);
                                            }
                                            d.trigger('close');
                                            return;
                                        }
                                    }
                                }
                                $('#photo-albums').html(tmpl('template-photo-albums', {
                                    albums: [].concat(old_albums, albums)
                                }));
                                d.trigger('close');
                            }
                        }
                    });
                    return false;
                }
            });
        },
        deletePhotoAction: function() {
            $.photos.confirmDialog({
                url: '?module=dialog&action=confirmDeletePhoto&id=' + $.photos.getPhotoId(),
                onSubmit: function(d) {
                    d.trigger('close');
                    $.photos.setCover();
                    $.photos.deletePhotos($.photos.getPhotoId(), function() {
                        $.photos.unsetCover();
                    });
                    return false;
                }
            });
        },
        unstackAction: function() {
            $.photos.confirmDialog({
                url: '?module=dialog&action=confirmUnstack&cnt=' + $.photos.photo_stack_cache.length(),
                onSubmit: function(d) {
                    d.trigger('close');
                    var id = $.photos.photo_stream_cache.getCurrent().id;
                    $.post('?module=stack&action=unmake&id=' + id, {}, function(response) {
                        $.photos.goToHash($.photos.hash);
                    }, 'json');
                    return false;
                }
            });
        },
        manageAccessAction: function() {
            var photo_id = $.photos.photo_stream_cache.getCurrent().id;
            $.photos.showManageAccessDialog(
                'photo_id='+photo_id,
                function(d) {
                    var f = $(this),
                        data = f.serializeArray();

                    data.push({
                        name: 'one_photo',
                        value: 1
                    });
                    $.photos.saveAccess({
                        photo_id: $.photos.photo_stream_cache.getCurrent().id,
                        data: data,
                        fn: function(r) {
                            var photo = r.data.photo;
                            if (photo) {
                                $.photos.photo_stream_cache.updateById(photo.id, photo);
                                // update content control panel
                                $.photos.initPhotoContentControlWidget({
                                    frontend_link_template: r.data.frontend_link_template,
                                    photo:photo
                                });
                            }
                            d.trigger('close');
                        },
                        onDeniedExist: function() {
                            alert($_("You don't have sufficient access rights"));
                            }
                        });
                        return false;
                    }
                );
                return false;
            }
        }
    );

    $.photos.menu.register('photo', '#edit-menu', {
        beforeAnyAction: function() {
            $.photos.setCover();
        },
        rotateLeftAction: function() {
            $.photos.rotate($.photos.getPhotoId(), 'left', function() {
                $.photos.unsetCover();
            });
        },
        rotateRightAction: function() {
            $.photos.rotate($.photos.getPhotoId(), 'right', function() {
                $.photos.unsetCover();
            });
        },

        onInit: function() {
            $(window).resize($.photos.centralizeLoadingIcon);
        }
    });

    $.photos.menu.register('photo', '#share-menu', {
        embedAction: function() {
            var d = $('#embed-photo-dialog'),
                photo_id = $.photos.getPhotoId(),
                hash = $.photos.hash,
                size = $.storage.get('photos/embed_size'),
                dialog_url = '?module=dialog&action=embedPhoto&photo_id='+photo_id+'&hash='+hash;

            if (size) {
                dialog_url += '&size='+size;
            }
            if (!d.length) {
                d = $('<div id="embed-photo-dialog"></div>');
                $("body").append(d);
            }
            d.load(dialog_url, function() {
                d.find('div:first').waDialog({
                    onLoad: function() {
                        var select = d.find('select[name=size]');

                        select.val(size);
                        select.change(function() {
                            var size = $(this).val(),
                                contexts = d.data('contexts'),
                                context = contexts[size];

                            d.find('textarea[name=html]').val(context.html);
                            d.find('input[name=url]').val(context.url);
                            $.storage.set('photos/embed_size', size);
                        });

                        d.find('input[name=url], textarea[name=html], input[name=link]').click(function() {
                            var selection = $(this).getSelection();
                            if (!selection.length) {
                                $(this).select();
                            }
                        });
                        d.find('input[name=link]').focus().select();
                    },
                    onSubmit: function() {
                        return false;
                    }
                });
            });
            return false;
        },
        blogPostAction: function() {
          var form = $('#blog-post-form');
          var photo_id = $.photos.getPhotoId();
          var photo = $.photos.photo_stream_cache.getById(photo_id);
          if (!photo) {
              photo = $.photos.photo_stack_cache.getById(photo_id);
          }
          if(photo) {
              form.find('[name="title"]:input').val(photo.name);
              var content = '<p>'+(photo.description ? photo.description + '<br>': '') + '<img src="'+photo.thumb_big.url+'" alt="'+photo.name+'.'+photo.ext+'"></p>';
              form.find('[name="text"]:input').val(content);
                  form.submit();
              }
          return false;
        }

    });
})(jQuery);