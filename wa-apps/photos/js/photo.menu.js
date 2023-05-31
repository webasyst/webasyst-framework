/**
 *
 */
(function($) {
    $.photos.menu.register('photo', '#photo-organize-menu', {
        addToAlbumAction: function() {
            $.photos.confirmDialog({
                url: '?module=dialog&action=albums&id=' + $.photos.getPhotoId(),
                onOpen($dialog, dialog) {
                    const $dialogForm = dialog.$block.find('form');

                    $dialogForm.on('submit', function(event) {
                        event.preventDefault();

                        const photo_id = $.photos.photo_stream_cache.getCurrent().id;
                        $.photos.addToAlbums({
                            photo_id: photo_id,
                            album_id: $dialog.find('form').serializeArray(),
                            copy: 0,
                            fn: function (r) {
                                if (r.status !== 'ok') {
                                    return;
                                }

                                const old_albums = r.data.old_albums || [];
                                const album = $.photos.getAlbum();
                                const albums = r.data.albums;

                                if (album) {
                                    for (let i = 0, n = old_albums.length; i < n; ++i) {
                                        // if now we are inside the one of old albums
                                        if (album.id == old_albums[i].id) {
                                            if (!albums.length) {
                                                $.photos.goToHash('/photo/' + photo_id);
                                            } else {
                                                $.photos.goToHash('/album/' + albums[0].id + '/photo/' + photo_id);
                                            }

                                            dialog.close();
                                            return;
                                        }
                                    }
                                }

                                $('#photo-albums').html(tmpl('template-photo-albums', {albums}));
                            }
                        });

                        dialog.close();
                    });
                }
            });
        },
        deletePhotoAction: function() {
            $.photos.confirmDialog({
                url: '?module=dialog&action=confirmDeletePhoto&id=' + $.photos.getPhotoId(),
                onOpen($dialog, dialog) {
                    const $dialogForm = dialog.$block.find('form');

                    $dialogForm.on('submit', function(event) {
                        event.preventDefault();

                        $.photos.setCover();
                        $.photos.deletePhotos($.photos.getPhotoId(), function() {
                            $.photos.unsetCover();
                        });

                        dialog.close();
                    });
                }
            });
        },
        unstackAction: function() {
            $.photos.confirmDialog({
                url: '?module=dialog&action=confirmUnstack&cnt=' + $.photos.photo_stack_cache.length(),
                onOpen($dialog, dialog) {
                    const $dialogForm = dialog.$block.find('form');

                    $dialogForm.on('submit', function(event) {
                        event.preventDefault();

                        const id = $.photos.photo_stream_cache.getCurrent().id;

                        $.post('?module=stack&action=unmake&id=' + id, {}, function(response) {
                            $.photos.goToHash($.photos.hash);
                        }, 'json');

                        dialog.close();
                    });
                }
            });
        },
        manageAccessAction: function() {
            var photo_id = $.photos.photo_stream_cache.getCurrent().id;
            $.photos.showManageAccessDialog(
                'photo_id='+photo_id,
                function($dialog, dialog) {
                    let data = $dialog.find('form').serializeArray();

                    data.push({
                        name: 'one_photo',
                        value: 1
                    });
                    $.photos.saveAccess({
                        photo_id: $.photos.photo_stream_cache.getCurrent().id,
                        data,
                        fn: function(r) {
                            var photo = r.data.photo,
                                stack = r.data.stack;

                            if (photo) {
                                photo = $.photos.photo_stream_cache.updateById(photo.id, photo);
                                // update content control panel
                                $.photos.initPhotoContentControlWidget({
                                    frontend_link_template: r.data.frontend_link_template,
                                    photo
                                });
                                $.photos.updatePhotoImgs(photo);
                            } else if (stack && $.isArray(stack) && stack.length) {
                                var stack_cache = $.photos.photo_stack_cache,
                                    current = stack_cache.getCurrent();
                                for (var i = 0, n = stack.length; i < n; ++i) {
                                    stack_cache.updateById(stack[i].id, stack[i]);
                                }
                                $.photos.initPhotoContentControlWidget({
                                    frontend_link_template: r.data.frontend_link_template,
                                    photo: current
                                });
                                $.photos.updatePhotoImgs(current);
                            }
                            dialog.close();
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
            $.photos.setCover(true);
        },
        rotateLeftAction: function() {
            $.photos.rotate($.photos.getPhotoId(), 'left', function() {
                $.photos.unsetCover(true);
            });
        },
        rotateRightAction: function() {
            $.photos.rotate($.photos.getPhotoId(), 'right', function() {
                $.photos.unsetCover(true);
            });
        },

        onInit: function() {
        }
    });

    $.photos.menu.register('photo', '#share-menu', {
        embedAction: function() {
            var d = $('#embed-photo-dialog-wrapper'),
                photo_id = $.photos.getPhotoId(),
                hash = $.photos.hash,
                size = $.storage.get('photos/embed_size'),
                dialog_url = '?module=dialog&action=embedPhoto&photo_id='+photo_id+'&hash='+hash;

            if (size) {
                dialog_url += '&size='+size;
            }
            if (!d.length) {
                d = $('<div id="embed-photo-dialog-wrapper"></div>');
                $("body").append(d);
            }
            d.load(dialog_url, function () {
                $.waDialog({
                    $wrapper: $('#embed-photo-dialog'),
                    onOpen($dialog) {
                        let $select = $dialog.find('select[name=size]');

                        $select.val(size);
                        $select.change(function() {
                            let size = $(this).val(),
                                contexts = $dialog.data('contexts'),
                                context = contexts[size];

                            $dialog.find('textarea[name=html]').val(context.html);
                            $dialog.find('input[name=url]').val(context.url);
                            $.storage.set('photos/embed_size', size);
                            saveContextData();
                            updateDomainInFields();
                        });

                        let $domain_selector = $dialog.find('select[name=domain]');
                        if ($domain_selector.length) {
                            saveContextData();
                            updateDomainInFields();
                            $domain_selector.change(updateDomainInFields);
                        }
                        function saveContextData() {
                            if ($domain_selector.length) {
                                $.each(['textarea[name=html]', 'input[name=url]'], function(i, selector) {
                                    let $el = $(selector);
                                    $el.data('context_data', $el.val());
                                });
                            }
                        }
                        function updateDomainInFields() {
                            if (!$domain_selector.length) {
                                return false;
                            }

                            $.each(['textarea[name=html]', 'input[name=url]'], function(i, selector) {
                                let $el = $(selector);
                                $el.val($el.data('context_data').split($domain_selector.data('original-domain')).join($domain_selector.val()));
                            });

                            let $selected_option = $domain_selector.children(':selected');
                            if ($selected_option.data('frontend-url')) {
                                $dialog.find('input[name=link]').val($selected_option.data('frontend-url')).closest('.field').slideDown();
                                $dialog.find('a.link').attr('href', $selected_option.data('frontend-url'));
                            } else {
                                $dialog.find('input[name=link]').closest('.field').slideUp();
                            }
                        }

                        $dialog.find('input[name=url], textarea[name=html], input[name=link]').on('click', function() {
                            var selection = $(this).getSelection();
                            if (!selection.length) {
                                $(this).select();
                            }
                        });
                        $dialog.find('input[name=link]').focus().select();
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
              if(true) {
                  var id = photo.id;
                  if(photo.hash){
                      id += ':'+photo.hash;
                  }
                  var context_parameters = {
                          photo_ids: id,
                          hash: null,
                          size:obligatory_size//XXX
                      };


                  $('#photo-blog-dialog :submit').attr('disabled',true);

                  $.post("?module=photo&action=embedList",
                      context_parameters,
                      function (r) {
                          if (r.status == 'ok') {
                              var context = r.data.context;
                              form.find('[name="title"]:input').val(photo.name);
                              var content = (blog_smarty_enabled && false)?context.smarty_code:context.html_with_descriptions;
                              form.find('[name="text"]:input').val(content.replace(/^<p>(.*)<\/p>$/mi,'$1'));
                              if(!parseInt(photo.status)) {
                                  $('#photo-blog-dialog :submit').attr('disabled',false);
                              } else {
                                  form.submit();
                              }
                          }
                      },
                  "json");
              } else {

                  form.find('[name="title"]:input').val(photo.name);
                  var content = '<p>'+(photo.description ? photo.description + '<br>': '') + '<img src="'+photo.thumb_big.url+'" alt="'+photo.name+'.'+photo.ext+'"></p>';
                  form.find('[name="text"]:input').val(content);
              }

              if(!parseInt(photo.status)) {
                  $('#photo-blog-dialog').waDialog({
                      'onLoad':function(){
                          $('#photo-blog-dialog :submit').attr('disabled',true);
                          var notice = $('#photo-blog-dialog p');
                          var count = [1,1];
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
          }
          return false;
        }

    });

})(jQuery);
