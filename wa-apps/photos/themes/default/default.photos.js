$(function() {
    
    var list = $('#photo-list.view-thumbs');
    if (list.length) {
        var handler = $('li', list);
        var options = {
            align: 'center',
            autoResize: true,
            comparator: null,
            container: list,
            direction: 'left',
            ignoreInactiveItems: true,
            itemWidth: '250',
            fillEmptySpace: false,
            flexibleWidth: true,
            offset: 22,
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
            list.css({
                position: 'relative'
            });
            handler.wookmark(options);
        }

        list.bind('append_photo_list', function() {
            $('li:not(.wookmark)', list).hide();
            list.waitForImages(applyLayout);
        });

        list.waitForImages(applyLayout);
    }
    
    $('.dropdown-sidebar .waSlideMenu-menu a').click(function(){
        if ( !$(this).parent().hasClass('collapsible') && !$(this).parent().hasClass('waSlideMenu-back') )
            $('#page-content').addClass('page-content'); // hack for single photo -> album navigation
    });
    
});