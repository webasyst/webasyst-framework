( function($) {

    var initialize = function() {
        // init Lazy Load
        lazyLoad();

        //
        bindEvents();
    };

    var bindEvents = function() {

        // Click on slide menu link
        $(".waSlideMenu-menu a").on("click", function() {
            var $link = $(this),
                is_collapsible = $link.parent().hasClass('collapsible'),
                is_back = $link.parent().hasClass('waSlideMenu-back');

            if ( !is_collapsible && !is_back ) {
                window.location.href = $(this).attr('href');
            }
        });
    };

    var wookmark = function() {
        var list = $("#photo-list.view-thumbs");

        if (list.length) {

            var $handler = $(".photo-item", list);

            function applyLayout() {
                if ($handler.wookmarkInstance) {
                    $handler.wookmarkInstance.clear();
                }

                $handler = $(".photo-item", list)
                    .addClass('wookmark')
                    .show();

                $handler.wookmark();
            }

            list.bind('append_photo_list', function() {
                $(".photo-item:not(.wookmark)", list).hide();
                list.waitForImages(applyLayout);
            });

            applyLayout();

            list.waitForImages( function() {
                applyLayout();
            });
        }
    };

    var lazyLoad = function() {
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
    };

    $(document).ready( function() {
        initialize();
    });

})(jQuery);

// Hack for Photo page
var renderBreadCrumbs = function() {

    var getAlbumsData = function( $albums ) {
        var albumArray = [];

        $albums.find("a").each( function() {
            var $link = $(this),
                href = $link.attr("href"),
                name = $link.text();

            albumArray.push( { href: href, name: name } );

        });

        return(albumArray);
    };

    var getItemsArray = function( albumsData ) {
        var html = [];

        for (var i = 0; i < albumsData.length ; i++) {
            html.push( $('<div class="nav-item back-nav-item"><a href="'+ albumsData[i].href +'">' + albumsData[i].name + '</a></div>') );
        }

        return html;
    };

    var $wrapper = $(".content-nav-wrapper"),
        wrapper_is_exist = ( $wrapper.length ),
        $albums = $("#photo-albums"),
        albums_is_exist = ( $albums.length );

    if (wrapper_is_exist && albums_is_exist) {
        var $nav_items = getItemsArray( getAlbumsData($albums)),
            $nav_list = $wrapper.find(".nav-list");

        if ($nav_items.length) {
            // Remove transparent Style
            $wrapper.removeClass("transparent");

            // Remove First Back link
            $wrapper.find(".nav-item").remove();

            // Render new nav items
            for (var i = 0; i < $nav_items.length; i++) {
                $nav_list.append( $nav_items[i] );
            }

        }
    }
};