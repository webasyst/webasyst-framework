(function ($) {
    const routing = {
        init: function () {
            if (typeof($.History) != "undefined") {
                $.History.bind(function () {
                    routing.dispatch();
                });
            }
            $.wa.errorHandler = function (xhr) {
                if ((xhr.status === 403) || (xhr.status === 404) ) {
                    $("#content").html('<div class="content left200px"><div class="block double-padded">' + xhr.responseText + '</div></div>');
                    return false;
                }
                return true;
            };
            var hash = window.location.hash;
            if (hash === '#/' || !hash) {
                this.dispatch();
            } else {
                $.wa.setHash(hash);
            }
        },

        dispatch: function (hash) {
            if (hash === undefined) {
                hash = window.location.hash;
            }
            hash = hash.replace(/(^[^#]*#\/*|\/$)/g, ''); /* fix syntax highlight*/
            var original_hash = this.hash
            this.hash = hash;
            if (hash) {
                hash = hash.split('/');
                if (hash[0]) {
                    var actionName = "";
                    var attrMarker = hash.length;
                    for (var i = 0; i < hash.length; i++) {
                        var h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else if (parseInt(h, 10) != h && h.indexOf('=') == -1) {
                                actionName += h.substr(0,1).toUpperCase() + h.substr(1);
                            } else {
                                attrMarker = i;
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }
                    var attr = hash.slice(attrMarker);
                    this.preExecute(actionName, attr);
                    if (typeof(this[actionName + 'Action']) == 'function') {
                        this[actionName + 'Action'].apply(this, attr);
                    }
                } else {
                    this.preExecute();
                    this.defaultAction();
                }
            } else {
                this.preExecute();
                this.defaultAction();
            }
        },

        preExecute: function () {

        },

        defaultAction: function () {
            this.designThemesAction();
        },

        pagesAction: function (id) {
            if ($('#wa-page-container').length) {
                waLoadPage(id);
            } else {
                routing.load('?module=pages');
            }
        },

        designAction: function(params) {
            if (params) {
                if ($('#wa-design-container').length) {
                    waDesignLoad();
                } else {
                    routing.load('?module=design', function () {
                        waDesignLoad(params);
                    });
                }
            } else {
                routing.load('?module=design', function () {
                    waDesignLoad('');
                });
            }
        },

        designPagesAction: function () {
            this.pagesAction();
        },

        designThemesAction: function (params) {
            if ($('#wa-design-container').length) {
                waDesignLoad();
            } else {
                routing.load('?module=design', function () {
                    waDesignLoad();
                });
            }
        },

        load: function (url, data, callback, wrapper) {
            let target = $('#content');

            if (typeof data == 'function') {
                wrapper = callback||null;
            }

            if(wrapper) {
                target.empty().append(wrapper);
                target = target.find(':last-child');
            }

            if (typeof data == 'function') {
                target.load(url, data);
            } else {
                target.load(url, data, callback);
            }
        },

    }

    routing.init();
})(jQuery);
