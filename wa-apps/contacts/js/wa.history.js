$.wa.history = {
    data: null,
    updateHistory: function(historyData) {
        this.data = historyData;
        var searchUl = $('#wa-search-history').empty();
        var creationUl = $('#wa-creation-history').empty();
        var currentHash = $.wa.controller.cleanHash(location.hash);
        for(var i = 0; i < historyData.length; i++) {
            var h = historyData[i];
            h.hash = $.wa.controller.cleanHash(h.hash);
            var li = $('<li rel="'+h.id+'">'+
                            (h.cnt >= 0 ? '<span class="count">'+h.cnt+'</span>' : '')+
                            '<a href="'+h.hash+'"><i class="icon16 '+h.type+'"></i></a>'+
                        '</li>');

            if (h.type == 'search' || h.type == 'import') {
                li.addClass('wa-h-type-search');
            }

            li.children('a').append($('<b>').text(h.name));

            if (h.type == 'import') {
                creationUl.append(li);
            } else if (h.type == 'add') {
                li.find('.icon16').removeClass(h.type).addClass('userpic20').css('background-image', 'url('+h.icon+')');
                creationUl.append(li);
            } else if (h.type == 'search') {
                searchUl.append(li);
            }
        }

        var lists = [searchUl, creationUl];
        for(var l = 0; l < lists.length; l++) {
            var ul = lists[l];
            if (ul.children().size() > 0) {
                ul.parents('.block.wrapper').show();
            } else {
                ul.parents('.block.wrapper').hide();
            }
        }
        $.wa.controller.highlightSidebar();
    },
    clear: function(type) {
        if (!type || type == 'search') {
            $('#wa-search-history').parents('.block.wrapper').hide();
            $('#wa-search-history').empty();
            type = '&ctype='+type
        } else if (type && type == 'creation') {
            $('#wa-creation-history').parents('.block.wrapper').hide();
            $('#wa-creation-history').empty();
            type = '&ctype[]=import&ctype[]=add';
        } else {
            type = '';
        }
        $.get('?module=contacts&action=history&clear=1'+type);
        return false;
    }
};

// EOF