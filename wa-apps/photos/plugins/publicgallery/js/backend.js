$(function() {
    var container = $('#content');
    
    var udpateCounterer = function(values) {
        $('#sidebar-publicgallery-plugin-declined').find('.count').text(values.declined || 0);
        $('#sidebar-publicgallery-plugin-awaiting').find('.count').text(values.awaiting || 0);
    };
    
    container.off('click.publicgallery', 'a.moderation').
            on('click.publicgallery', 'a.moderation', function() {
                var self = $(this);
                var action = self.data('action');
                if (action === 'approve' || action === 'decline') {
                    var count = $.photos.photo_stream_cache.length() - 1;
                    var total_count = $.photos.total_count - 1;
                    var photo_id = self.closest('li').data('photo-id');
                    $.post('?plugin=publicgallery&module=backend&action=moderation', {
                        id: photo_id,
                        moderation: action,
                        count: count,
                        total_count: total_count
                    }, function(r) {
                        if (r.status == 'ok') {
/*
                            self.parent().find('.moderation').show().
                                    filter('[data-action="'+action+'"]').hide();
*/
                            var update = function() {
                                $.photos.photo_stream_cache.deleteById(photo_id);
                                $('.lazyloading-wrapper').html(tmpl('template-photo-counter',{
                                    count: count,
                                    total_count: total_count,
                                    string: r.data.string
                                }));
                                $.photos.photo_list_string = r.data.string;
                                $.photos.total_count -= 1;
                            };
                            if (action === 'decline') {
                                self.parent().find('.moderation.decline').css('background','red').css('color','white');
                                setTimeout(function(){
                                    self.closest('li').animate({
                                        width: 'toggle'
                                    }, 300, function() {
                                        $(this).remove();
                                        udpateCounterer(r.data.counters);
                                    });
                                },200);
                                update();
                            } else {
                                self.parent().find('.moderation.approve').css('background','green').css('color','white');
                                setTimeout(function(){
                                    self.closest('li').animate({
                                        width: 'toggle'
                                    }, 300, function() {
                                        $(this).remove();
                                        udpateCounterer(r.data.counters);
                                    });
                                },200);
                                update();
                            }
                        }
                    }, 'json');
                }
                return false;
            }
        );

    // extend default method
    var updateViewPhotoMenu = $.photos.updateViewPhotoMenu;
    if (typeof updateViewPhotoMenu === 'function') {
        $.photos.updateViewPhotoMenu = function() {
            updateViewPhotoMenu.apply(this, arguments);
            $.photos.hooks_manager.bind('afterLoadPhoto', function(photo) {
                var toolbar = $('#p-toolbar');
                if (photo.source === "publicgallery") {
                    
                    var renderModerationMenu = function(photo) {
                        toolbar.find('.moderation').show();
                        if (photo.moderation === '1') {
                            toolbar.find('.moderation[data-action="approve"]').hide();
                        } else if (photo.moderation === '-1') {
                            toolbar.find('.moderation[data-action="decline"]').hide();
                        }
                    };
                    
                    renderModerationMenu(photo);
                    
                    toolbar.find('.moderation a').unbind('click.publicgallery').
                            one('click.publicgallery', function() {
                                var self = $(this);
                                var action = self.closest('li').data('action');
                                var photo = $.photos.photo_stream_cache.getCurrent();
                                if (action === 'approve' || action === 'decline') {
                                    $.post('?plugin=publicgallery&module=backend&action=moderation', {
                                        id: photo.id,
                                        moderation: action
                                    }, function(r) {
                                        if (r.status == 'ok') {
                                            photo = r.data.photo;
                                            renderModerationMenu(photo);
                                            $.photos.photo_stream_cache.updateById(photo);
                                            $.photos.initPhotoContentControlWidget({
                                                frontend_link_template: frontend_link_template,
                                                photo: photo
                                            });
                                        }
                                    }, 'json');
                                }
                                return false;
                            }
                        );
                } else {
                    toolbar.find('.moderation').hide();
                }
            });
        };
    }

    var onLoadPhotoList = $.photos.onLoadPhotoList;
    $.photos.onLoadPhotoList = function() {
        if ($.photos.hash === '/search/moderation=0') {
            var counter = $('#sidebar-publicgallery-plugin-awaiting').find('.count');
            counter.text($.photos.total_count);
            if ($.photos.total_count) {
                counter.addClass('indicator');
            } else {
                counter.removeClass('indicator');
            }
        }
        if ($.photos.hash === '/search/moderation=-1') {
            $('#sidebar-publicgallery-plugin-declined').find('.count').text($.photos.total_count);
        }
        onLoadPhotoList.apply(this, arguments);
    };
        
    var onVoteOnePhoto = function(r) {
            if (r.status != 'ok') {
                if (console) {
                    console.log(r);
                }
            }
            $('#rated-count').text(r.data.count > 0 ? r.data.count : '');
            $('#photo-rate').rateWidget('setOption', 'rate', r.data.photos[0].rate);
            $('#photo-rate-votes-count').html('<u>' + r.data.photos[0].votes_count_text + '</u>');
            if (parseInt(r.data.you_voted, 10)) {
                $('#photo-rate-votes-count').attr('data-you-voted', 1);
                $('#clear-photo-rate').show();
                $('#p-your-rate-wrapper').show();
            } else {
                $('#photo-rate-votes-count').attr('data-you-voted', 0);
                $('#clear-photo-rate').hide();
                $('#p-your-rate-wrapper').hide();
            }
            
            $.photos.photo_stream_cache.updateById(r.data.photos[0].id, r.data.photos[0]);
    };
    
    var onVoteFewPhotos = function(r) {
            if (r.status != 'ok') {
                if (console) {
                    console.log(r);
                }
            }
            var selected = $('#photo-list li.selected');
            var photos = r.data.photos;
            for (var i = 0; i < photos.length; i++) {
                var item = selected.filter('[data-photo-id="'+photos[i].id+'"]');
                $.photos.updateThumbRate(item, photos[i].rate);
                $.photos.photo_stream_cache.updateById(photos[i].id, photos[i]);
            }
            selected.find('input:first').trigger('select', false);
            $('#rated-count').text(r.data.count > 0 ? r.data.count : '');
            $('#set-rate').trigger('close');
    };
    
    $.photos.initPhotoRateWidget = function(edit_status) {
        edit_status = typeof edit_status === 'undefined' ? false : edit_status;
        var update = function(rate) {
            $.photos.saveField($.photos.getPhotoId(), 'rate', rate);
            $('#your-rate').rateWidget('setOption', 'rate', rate);
        };
        $('#photo-rate').rateWidget({
            onUpdate: update,
            hold: function() {
                return parseInt($('#photo-rate-votes-count').attr('data-you-voted'), 10);
            },
            withClearAction: false,
            alwaysUpdate: true
        });
        $.photos.updatePhotoRate(edit_status);
        //$.photos.publicgalleryInitYourRate();
    };
    
    $.photos.publicgalleryInitYourRate = function() {
        $('#your-rate').rateWidget({
            onUpdate: function(){},
            hold: function() {
                return true;
            },
            withClearAction: false,
            alwaysUpdate: true
        });
        $('#clear-photo-rate').click(function() {
            $.photos.saveField($.photos.getPhotoId(), 'rate', 0);
            $('#your-rate').rateWidget('setOption', 'rate', 0);
        });
        var voted = parseInt($('#photo-rate-votes-count').attr('data-you-voted'), 10);
        if (voted) {
            $('#clear-photo-rate').show();
            $('#p-your-rate-wrapper').show();
        } else {
            $('#clear-photo-rate').hide();
            $('#p-your-rate-wrapper').hide();
        }
                
        $('#photo-rate-votes-count').click(function() {
            $('<div id="photo-rates-distribution"></div>').waDialog({
                url: '?plugin=publicgallery&module=backend&action=ratesDistribution&photo_id=' + $.photos.getPhotoId(),
                className: 'width600px height500px'
            });
        });
    };
    
    var saveField = $.photos.saveField;
    $.photos.saveField = function(id, name, value, fn) {
        if (name !== 'rate') {
            saveField.apply(this, arguments);
        } else {
            if (arguments.length === 1) {
                var params = arguments[0];
                id = params.id;
                name = params.name;
                value = params.value;
            }
            var photo_id = [];
            var one_photo = null;
            if ($.isArray(id)) {
                for (var i = 0; i < id.length; i++) {
                    photo_id.push(id[i].value);
                }
                one_photo = false;
            } else {
                photo_id.push(id);
                one_photo = true;
            }
            $.post('?plugin=publicgallery&module=vote', {
                photo_id: photo_id,
                rate: value
            }, function(r) {
                    if (one_photo) {
                        onVoteOnePhoto(r);
                    } else {
                        onVoteFewPhotos(r);
                    }
              }, 'json');
        }
    };
});