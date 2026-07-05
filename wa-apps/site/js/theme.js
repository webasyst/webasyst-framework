(function ($) {
    $.theme = {
        init: function () {
            if (typeof($.History) != 'undefined') {
                $.History.bind(() => {
                    const hash = window.location.hash;
                    if (hash.startsWith('#/themes')) {
                        this.dispatch();
                    }
                });
            } else {
                console.error('$.History not found.')
            }
        },

        dispatch: function (hash) {
            if (hash === undefined) {
                hash = window.location.hash;
            }
            hash = hash.replace(/(^[^#]*#\/*|\/$)/g, ''); /* fix syntax highlight*/
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
                        $.site.log('$.theme.dispatch',[actionName + 'Action',attr]);
                        this[actionName + 'Action'].apply(this, attr);
                    } else {
                        $.site.log('Invalid action name:', actionName+'Action');
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
            this.themesAction();
        },

        themesAction: function(params) {
            if (params) {
                // load single theme
                if ($('#wa-design-container').length) {
                    waDesignLoad();
                } else {
                    $(document).one('wa_design_inited', () => {
                        waDesignLoad(params);
                    });
                }
            }
        }
    }
})(jQuery);
