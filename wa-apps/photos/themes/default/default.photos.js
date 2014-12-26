$(function() {

    var list = $('#photo-list.view-thumbs');
    if (list.length) {
        list.css({
            position: 'relative'
        });
        var handler = $('li', list);
        var options = {
            align: 'center',
            autoResize: true,
            comparator: null,
            container: list,
            direction: 'left',
            ignoreInactiveItems: true,
            itemWidth: '220',
            fillEmptySpace: false,
            flexibleWidth: true,
            offset: 0,
            outerOffset: 0,
            possibleFilters: [],
            resizeDelay: 50,
            verticalOffset: undefined
        };

        function applyLayout() {
            if (handler.wookmarkInstance) {
                handler.wookmarkInstance.clear();
            }
            handler = $('li', list).addClass('wookmark').fadeIn('slow');
            handler.wookmark(options);
        }

        list.bind('append_photo_list', function() {
            $('li:not(.wookmark)', list).hide();
            list.waitForImages(applyLayout);
        });

        // A hack to make LIs have height before document.ready
        list.find('li').each(function() {
           var $li = $(this);
           var $img = $li.find('img.photo_img');
           var height = ($img[0].style.height || '0').replace('px', '');
           height && $li.height(height);
        });

        //setTimeout(applyLayout, 0);
        applyLayout();
        list.waitForImages(function() {
            list.find('li').css('height', '');
            applyLayout();
        });
    }

    $('.waSlideMenu-menu a').click(function(){

        if ( !$(this).parent().hasClass('collapsible') && !$(this).parent().hasClass('waSlideMenu-back') )
        {
            // that was an end node click in waSlideMenu, so do forst redirect here (Photos app Default theme specific)
            window.location.href = $(this).attr('href');
        }
    });

    $('.slidemenu').on('afterLoadDone.waSlideMenu', function () {
        $('img').retina();
    });

    if ($.fn.lazyLoad) {
        var paging = $('.lazyloading-paging');
        if (!paging.length) {
            return;
        }
        // check need to initialize lazy-loading
        var current = paging.find('li.selected');
        if (current.children('a').text() != '1') {
            return;
        }
        paging.hide();
        var win = $(window);

        var times = parseInt(paging.data('times'), 10);
        var link_text = paging.data('linkText') || 'Load more';

        // prevent previous launched lazy-loading
        win.lazyLoad('stop');

        // check need to initialize lazy-loading
        var next = current.next();
        if (next.length) {
            win.lazyLoad({
                container: '#photo-list',
                load: function() {
                    win.lazyLoad('sleep');
                    var paging = $('.lazyloading-paging').hide();

                    var loading = paging.parent().find('.loading').parent();

                    // determine actual current and next item for getting actual url
                    var current = paging.find('li.selected');
                    var next = current.next();
                    var url = next.find('a').attr('href');
                    if (!url) {
                        loading.hide();
                        $('.lazyloading-load-more').hide();
                        win.lazyLoad('stop');
                        return;
                    }

                    var photo_list = $('#photo-list');
                    if (!loading.length) {
                        loading = $('<div><i class="icon16 loading"></i>Loading...</div>').insertBefore(paging); // !!! localization?..
                    }

                    loading.show();
                    $.get(url, function(html) {
                        var tmp = $('<div></div>').html(html);
                        if ($.Retina) {
                            tmp.find('#photo-list img').retina();
                        }
                        photo_list.append(tmp.find('#photo-list').children());
                        var tmp_paging = tmp.find('.lazyloading-paging').hide();
                        paging.replaceWith(tmp_paging);
                        paging = tmp_paging;

                        times -= 1;

                        // check need to stop lazy-loading
                        var current = paging.find('li.selected');
                        var next = current.next();
                        if (next.length && next.find('a').attr('href')) {
                            if (!isNaN(times) && times <= 0) {
                                win.lazyLoad('sleep');
                                if (!$('.lazyloading-load-more').length) {
                                    $('<a href="#" class="lazyloading-load-more">' + link_text + '</a>').insertAfter(paging)
                                        .click(function() {
                                            loading.show();
                                            times = 1;      // one more time
                                            win.lazyLoad('wake');
                                            win.lazyLoad('force');
                                            return false;
                                        });
                                }
                            } else {
                                win.lazyLoad('wake');
                            }
                        } else {
                            $('.lazyloading-load-more').hide();
                            win.lazyLoad('stop');
                        }

                        loading.hide();
                        tmp.remove();

                        photo_list.trigger('append_photo_list');
                    });
                }
            });
        }
    }

});