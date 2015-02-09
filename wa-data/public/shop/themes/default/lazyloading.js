$(function() {
    
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
    
    // prevent previous launched lazy-loading
    win.lazyLoad('stop');
    
    // check need to initialize lazy-loading
    var next = current.next();
    if (next.length) {
        win.lazyLoad({
            container: '#product-list .product-list',
            load: function() {
                win.lazyLoad('sleep');

                var paging = $('.lazyloading-paging').hide();
                
                // determine actual current and next item for getting actual url
                var current = paging.find('li.selected');
                var next = current.next();
                var url = next.find('a').attr('href');
                if (!url) {
                    win.lazyLoad('stop');
                    return;
                }

                var product_list = $('#product-list .product-list');
                var loading = paging.parent().find('.loading').parent();
                if (!loading.length) {
                    loading = $('<div><i class="icon16 loading"></i>Loading...</div>').insertBefore(paging);
                }

                loading.show();
                $.get(url, function(html) {
                    var tmp = $('<div></div>').html(html);
                    if ($.Retina) {
                        tmp.find('#product-list .product-list img').retina();
                    }
                    product_list.append(tmp.find('#product-list .product-list').children());
                    var tmp_paging = tmp.find('.lazyloading-paging').hide();
                    paging.replaceWith(tmp_paging);
                    paging = tmp_paging;
                    
                    // check need to stop lazy-loading
                    var current = paging.find('li.selected');
                    var next = current.next();
                    if (next.length) {
                        win.lazyLoad('wake');
                    } else {
                        win.lazyLoad('stop');
                    }
                    
                    loading.hide();
                    tmp.remove();
                });
            }
        });
    }
});