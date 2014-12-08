$(function() {
    
    $.photos.publicgalleryInitRateWidget = function() {

        var url = $('#your-rate').parent().data('vote-url');
        var update = function(rate) {
            $.post(url, {
                photo_id: [$.photos.photo_stream_cache.getCurrent().id],
                rate: rate
            }, function(r) {
                if (r.status != 'ok') {
                    if (!r.errors) {
                        if (console) {
                            console.log(r);
                        }
                    } else {
                        $('#photo-rate-error').show().html(r.errors[0]);
                        return;
                    }
                }
                $('#photo-rate').rateWidget('setOption', 'rate', r.data.photos[0].rate);
                $('#photo-rate-votes-count').text(r.data.photos[0].votes_count_text);
                if (parseInt(r.data.you_voted, 10)) {
                    $('#photo-rate-votes-count').attr('data-you-voted', 1);
                    $('#clear-photo-rate').show();
                } else {
                    $('#photo-rate-votes-count').attr('data-you-voted', 0);
                    $('#clear-photo-rate').hide();
                }
                $.photos.photo_stream_cache.updateById(r.data.photos[0].id, r.data.photos[0]);
            }, 'json');
        };

        if ($('#photo-rate').length) {

            $('#photo-rate').rateWidget({
                onUpdate: update,
                hold: function() {
                    return true;
                    //return parseInt($('#photo-rate-votes-count').attr('data-you-voted'), 10);
                },
                withClearAction: false,
                alwaysUpdate: true
            });

            $('#your-rate').rateWidget({
                onUpdate: update,
                hold: function() {
                    return parseInt($('#photo-rate-votes-count').attr('data-you-voted'), 10);
                },
                withClearAction: false,
                alwaysUpdate: true
            });

            $('#clear-photo-rate').click(function() {
                update(0);
                $('#your-rate').rateWidget('setOption', 'rate', 0);
            });
            var voted = parseInt($('#photo-rate-votes-count').attr('data-you-voted'), 10);
            if (voted) {
                $('#clear-photo-rate').show();
            }
        }
    };
    
    $.photos.publicgalleryInitRateWidget();
    
});