(function ($) {
    // js controller
    $.dummy = {
        // init js controller
        init: function () {
            if (window.history && window.history.pushState) {
                window.onpopstate = function(event) {
                    event.stopPropagation();
                    $.dummy.dispatch(event.target.location.hash);
                }
            }
            $("#records-add-link").on('click', function (e) {
                e.preventDefault();
                $.dummy.recordsAdd();
            })
            this.dispatch();
        },
        // dispatch call method by hash
        dispatch: function (hash) {
            if (hash === undefined) {
                hash = location.hash.replace(/^[^#]*#\/*/, '');
            }
            if (hash) {
                // clear hash
                hash = hash.replace(/^.*#/, '');
                hash = hash.split('/').filter(String);

                if (hash[0]) {
                    let actionName = "";
                    let attrMarker = hash.length;
                    for (let i = 0; i < hash.length; i++) {
                        const h = hash[i];
                        if (i < 2) {
                            if (i === 0) {
                                actionName = h;
                            } else if (parseInt(h, 10) != h) {
                                actionName += h.charAt(0).toUpperCase() + h.slice(1);
                            } else {
                                attrMarker = i;
                                break;
                            }
                        } else {
                            attrMarker = i;
                            break;
                        }
                    }
                    const attr = hash.slice(attrMarker);
                    // call action if it exists
                    if (this[actionName + 'Action']) {
                        this.currentAction = actionName;
                        this.currentActionAttr = attr;
                        this[actionName + 'Action'](attr);
                    } else {
                        if (console) {
                            console.log('Invalid action name:', actionName + 'Action');
                        }
                    }
                } else {
                    // call default action
                    this.defaultAction();
                }
            } else {
                // call default action
                this.defaultAction();
            }
        },

        defaultAction: function () {
            $("#content").load('?action=records');
        },

        recordAction: function (params) {
            $.get('?action=record', {id: params[0]}, function (response) {
                if (response.status == 'ok') {
                    const html = '<div class="box contentbox"><h1>' + response.data.title + '</h1>' + response.data.content + '</div>';
                    $("#content").html(html);
                } else {
                    alert(response.errors);
                }
            }, 'json');
        },

        recordsAdd: function () {
            $.waDialog({
                $wrapper: $("#records-add").clone(),
                onOpen($dialog) {
                    $dialog.find('form').on('submit', function(e) {
                        e.preventDefault();
                        alert('Submit');
                    })
                }
            });
        }
    }
})(jQuery);