// Pages

var GroupPage = ( function($) {

    GroupPage = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];

        // VARS
        that.group_id = options["group_id"];
        that.map_adapter = options["map_adapter"];
        that.latitude = options["latitude"];
        that.longitude = options["longitude"];
        that.can_manage = options["can_manage"];

        // DYNAMIC VARS
        that.map_is_render = false;
        that.map_is_shown = false;

        // INIT
        that.initClass();
    };

    GroupPage.prototype.initClass = function() {
        var that = this;
        //
        that.bindEvents();
        //
        if (that.can_manage) {
            //
            that.initEditableName();
            //
            that.initEditableDescription();
        }
        //
        that.initInfoBlock();
    };

    GroupPage.prototype.bindEvents = function() {
        var that = this;

        if (that.map_adapter && that.latitude && that.longitude) {
            that.$wrapper.on("click", ".js-open-map-link", function() {
                that.showMap();
            });
        }
    };

    GroupPage.prototype.showMap = function() {
        var that = this,
            $wrapper = that.$wrapper.find(".t-map-wrapper");

        if (that.map_is_shown) {
            $wrapper.slideUp(200);
        } else {
            if (!that.map_is_render) {
                // for height
                $wrapper.show();
                // init
                that.initMap();
                // save
                that.map_is_render = true;
            } else {
                $wrapper.slideDown(200);
            }
        }

        that.map_is_shown = !that.map_is_shown;
    };

    GroupPage.prototype.initMap = function() {
        var that = this;

        var $map = that.$wrapper.find("#t-location-map"),
            map = new TeamMap($map, that.map_adapter);

        map.render(that.latitude, that.longitude);
    };

    GroupPage.prototype.initEditableName = function() {
        var group = this,
            $name = group.$wrapper.find(".js-name-editable").first();

        if ($name.length) {
            new TeamEditable({
                $wrapper: $name,
                onSave: function( that ) {
                    var text = that.$field.val(),
                        do_save = ( text.length && that.text !== text );

                    if (do_save) {
                        var href = $.team.app_url + "?module=group&action=save",
                            data = {
                                "data[id]": group.group_id,
                                "data[name]": text
                            };

                        that.$field.attr("disabled", true);
                        var $loading = $('<i class="icon16 loading"></i>')
                            .css("margin", "0 0 0 4px")
                            .insertAfter( that.$field );

                        $.post(href, data, function() {
                            that.$field.attr("disabled", false);
                            $loading.remove();

                            that.text = text;
                            that.$wrapper.text( text );
                            that.toggle("hide");

                            $.team.sidebar.reload();
                        });

                    } else {
                        if (!text.length) {
                            that.$field.val( that.text );
                        }
                        that.toggle("hide");
                    }
                }
            });
        }
    };

    GroupPage.prototype.initEditableDescription = function() {
        var group = this,
            $name = group.$wrapper.find(".js-desc-editable").first();

        if ($name.length) {
            new TeamEditable({
                $wrapper: $name,
                onSave: function( that ) {
                    var text = that.$field.val(),
                        is_empty = ( !text.length );

                    if (that.text !== text) {
                        var href = $.team.app_url + "?module=group&action=save",
                            data = {
                                "data[id]": group.group_id,
                                "data[description]": text
                            };

                        that.$field.attr("disabled", true);
                        var $loading = $('<i class="icon16 loading"></i>')
                            .css("margin", "0 0 0 4px")
                            .insertAfter( that.$field );

                        $.post(href, data, function() {
                            that.$field.attr("disabled", false);
                            $loading.remove();

                            that.is_empty = is_empty;
                            that.text = text;
                            that.$wrapper.text( text );
                            that.toggle("hide");

                            if (is_empty) {
                                $.team.content.reload();
                            }
                        });

                    } else {
                        that.toggle("hide");
                    }
                }
            });
        }
    };

    GroupPage.prototype.initInfoBlock = function () {
        var that = this,
            $info_block = that.$wrapper.find(".t-info-notice-wrapper"),
            storage = new $.store(),
            key = "team/empty_group_notice_hide";

        if (storage.get(key)) {
            $info_block.hide();
        } else {
            $info_block.show();
        }

        $info_block.find(".t-info-notice-toggle").on("click", function () {
            storage.set(key, 1);
            $info_block.hide();
        });
    };

    return GroupPage;

})(jQuery);

var GroupManage = ( function($) {

    GroupManage = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$groupUsersW = that.$wrapper.find(".t-users-list.is-used-list");
        that.$groupUsersHint = that.$wrapper.find(".t-empty-users-in-group");
        that.$otherUsersW = that.$wrapper.find(".t-users-list.is-unused-list");
        that.$otherUsersHint = that.$wrapper.find(".t-empty-users-outside-group");

        // VARS
        that.group_id = options["group_id"];
        that.hidden_class = "is-hidden";
        that.locales = options["locales"];

        // DYNAMIC VARS
        that.$sidebarLink = false;
        that.is_locked = false;
        that.xhr = false;
        that.group_count = that.$groupUsersW.find(".t-user-wrapper").length;
        that.other_count = that.$otherUsersW.find(".t-user-wrapper").length;

        // INIT
        that.initClass();
    };

    GroupManage.prototype.initClass = function() {
        var that = this;
        //
        that.bindEvents();
        //
        that.initAutoComplete();
        //
        that.initEditableName();
    };

    GroupManage.prototype.bindEvents = function() {
        var that = this;

        that.$wrapper.on("click", ".js-edit-group", function(event) {
            event.preventDefault();
            if (that.group_id && !that.is_locked) {
                that.showEditGroupDialog();
            }
        });

        that.$wrapper.on("click", ".js-delete-group", function(event) {
            event.preventDefault();
            if (that.group_id && !that.is_locked) {
                that.showDeleteDialog();
            }
        });

        that.$groupUsersW.on("click", ".js-move-user", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that.moveUser( $(this).closest(".t-user-wrapper"), false );
            }
        });

        that.$otherUsersW.on("click", ".js-move-user", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that.moveUser( $(this).closest(".t-user-wrapper"), true );
            }
        });
    };

    GroupManage.prototype.showEditGroupDialog = function() {
        var that = this,
            href = "?module=group&action=edit",
            data = {
                id: that.group_id
            };

        if (!that.is_locked) {
            that.is_locked = true;

            if (that.xhr) {
                that.xhr.abort();
                that.xhr = false;
            }

            that.xhr = $.get(href, data, function(response) {
                new TeamDialog({
                    html: response
                });
                that.is_locked = false;
            });
        }
    };

    GroupManage.prototype.showDeleteDialog = function() {
        var that = this,
            href = $.team.app_url + "?module=group&action=deleteConfirm",
            data = {
                id: that.group_id
            };

        if (!that.is_locked) {
            that.is_locked = true;

            if (that.xhr) {
                that.xhr.abort();
                that.xhr = false;
            }

            that.xhr = $.get(href, data, function(html) {
                new TeamDialog({
                    html: html
                });

                that.is_locked = false;

                that.$wrapper.trigger("close");
            });
        }
    };

    GroupManage.prototype.moveUser = function( $user, add ) {
        var that = this,
            data = {
                user_id: $user.data("user-id"),
                group_id: that.group_id
            },
            href;

        if (!that.is_locked) {

            // Save
            if (that.xhr) {
                that.xhr.abort();
                that.xhr = false;
            }

            if (add) {
                href = $.team.app_url + "?module=group&action=userAdd";
            } else {
                href = $.team.app_url + "?module=group&action=userRemove";
            }

            that.xhr = $.post(href, data, function(response) {
                if (response.status == "ok") {
                    // Render
                    if (add) {
                        that.$groupUsersW.append( $user );
                        that.group_count++;
                        that.other_count--;
                    } else {
                        that.$otherUsersW.prepend( $user );
                        that.group_count--;
                        that.other_count++;
                    }

                    if (that.group_count <= 0) {
                        that.$groupUsersW.addClass(that.hidden_class);
                        that.$groupUsersHint.removeClass(that.hidden_class);
                    } else {
                        that.$groupUsersW.removeClass(that.hidden_class);
                        that.$groupUsersHint.addClass(that.hidden_class);
                    }

                    if (that.other_count <= 0) {
                        that.$otherUsersW.addClass(that.hidden_class);
                        that.$otherUsersHint.removeClass(that.hidden_class);
                    } else {
                        that.$otherUsersW.removeClass(that.hidden_class);
                        that.$otherUsersHint.addClass(that.hidden_class);
                    }

                    that.setCount( that.group_count );
                }
                that.is_locked = false;
            });
        }

    };

    GroupManage.prototype.setCount = function( count ) {
        var that = this,
            href = $.team.app_url + 'group/' + that.group_id + "/";

        if (!that.$sidebarLink) {
            that.$sidebarLink = $.team.sidebar.$wrapper.find('a[href="' + href + '"] ');
        }

        if (that.$sidebarLink.length) {
            var $li = that.$sidebarLink.closest("li"),
                $counter;

            // Render
            $li.find(".indicator").remove();
            $counter = $li.find(".count");
            $counter.text(count);

            // Save
            $.team.sidebar.saveCount(href, count);
        }
    };

    GroupManage.prototype.initAutoComplete = function() {
        var that = this,
            $field = that.$wrapper.find(".t-autocomplete-wrapper .t-input"),
            $hint = false,
            timeout = 0;

        $field.autocomplete({
            source: getSource,
            minLength: 2,
            open: function() {
                removeHint();
            },
            focus: function() {
                return false;
            },
            select: function( event, ui ) {
                if (ui.item.id) {
                    addUser(ui.item.id);
                }
                $field.val("");
                return false;
            }
        });

        function removeHint() {
            clearTimeout(timeout);

            if ($hint.length) {
                $hint.remove();
                $hint = false;
            }
        }

        function addHint( locale ) {
            var time = 1000;

            $hint = $("<span class=\"t-hint\"><i class=\"icon16 yes\"></i>" + locale + "</span>");
            $field.after($hint);
            timeout = setTimeout( removeHint, time);
        }

        function getSource( request, response ) {
            var href = $.team.app_url + "?module=autocomplete&type=user",
                data = {
                    term: request.term
                };

            if (that.group_id) {
                data.group_id = that.group_id;
            }

            $.post(href, data, function(data) {
                response( data );
            }, "json");
        }

        function addUser( user_id ) {
            var $link = that.$otherUsersW.find(".t-user-wrapper[data-user-id=\"" + user_id + "\"]");
            if ($link.length) {
                $link.find(".js-move-user").click();
                addHint( that.locales["added"] );
            } else {
                addHint( that.locales["in_group"] );
            }
        }
    };

    GroupManage.prototype.initEditableName = function() {
        var group = this,
            $name = group.$wrapper.find(".js-name-editable").first();

        if ($name.length) {
            new TeamEditable({
                $wrapper: $name,
                onSave: function( that ) {
                    var text = that.$field.val(),
                        do_save = ( text.length && that.text !== text );

                    if (do_save) {
                        var href = $.team.app_url + "?module=group&action=save",
                            data = {
                                "data[id]": group.group_id,
                                "data[name]": text
                            };

                        that.$field.attr("disabled", true);
                        var $loading = $('<i class="icon16 loading"></i>')
                            .css("margin", "0 0 0 4px")
                            .insertAfter( that.$field );

                        $.post(href, data, function() {
                            that.$field.attr("disabled", false);
                            $loading.remove();

                            that.text = text;
                            that.$wrapper.text( text );
                            that.toggle("hide");

                            $.team.sidebar.reload();
                        });

                    } else {
                        if (!text.length) {
                            that.$field.val( that.text );
                        }
                        that.toggle("hide");
                    }
                }
            });
        }
    };

    return GroupManage;

})(jQuery);

// Dialogs

var GroupEditDialog = ( function($) {

    GroupEditDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$block = that.$wrapper.find(".t-dialog-block");
        that.$form = that.$block.find("form");
        that.$iconToggle = that.$block.find(".t-icon-toggle");
        that.$addressToggle = that.$block.find('.t-address-toggle');
        that.$mapToggle = that.$block.find('.t-map-toggle');
        that.$submitButton = that.$form.find("input[type=\"submit\"]");

        // VARS
        that.selected_class = "is-selected";
        that.hidden_class = "is-hidden";
        that.has_error_class = "error";
        that.locales = options["locales"];
        that.dialog = that.$wrapper.data("teamDialog");

        // DYNAMIC VARS
        that.$activeType = that.$block.find(".t-type-toggle ." + that.selected_class);
        that.$activeIcon = that.$iconToggle.find("." + that.selected_class);
        that.is_locked = false;
        that.save_timeout = 0;
        // for map
        that.is_map_loading = false;

        // INIT
        that.bindEvents();
        that.teamMap = that.initMap( ( options["map_type"] || "google") );
    };

    GroupEditDialog.prototype.bindEvents = function() {
        var that = this;

        that.$block.on("click", ".t-type-toggle .t-toggle-item", function(event) {
            event.stopPropagation();
            that.setType( $(this) );
        });

        that.$iconToggle.on("click", ".t-icon-item", function(event) {
            event.preventDefault();
            that.setIcon( $(this) );
        });

        that.$form.on("submit", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that.$form.find('.js-submit-loading').remove();
                that.$submitButton.parent().append("<i class='icon16 loading js-submit-loading'></i>");
                that.save();
            }
        });

        // Remove errors hints
        var $fields = that.$form.find("input, textarea");
        $fields.on("mousedown", function() {
            var $field = $(this),
                has_error = $field.hasClass( that.has_error_class );

            that.$form.find('.js-submit-loading').remove();
            if (has_error) {
                $field
                    .removeClass(that.has_error_class)
                    .closest(".value")
                    .find(".t-error").remove();
            }
        });
    };

    GroupEditDialog.prototype.setType = function( $label ) {
        var that = this,
            type = $label.data("type");

        if ($label.hasClass(that.selected_class)) {
            return false;
        }

        if (that.$activeType.length) {
            that.$activeType.removeClass(that.selected_class);
        }

        if (type == "group") {
            that.$iconToggle.removeClass(that.hidden_class);
            that.$addressToggle.addClass(that.hidden_class);
            that.$mapToggle.addClass(that.hidden_class);
        } else {
            that.$iconToggle.addClass(that.hidden_class);
            that.$addressToggle.removeClass(that.hidden_class);
            that.$mapToggle.removeClass(that.hidden_class);
        }

        $label.addClass(that.selected_class);
        that.$activeType = $label;
        // resize
        that.dialog.resize();
    };

    GroupEditDialog.prototype.setIcon = function( $icon ) {
        var that = this,
            icon_class = $icon.data("icon-class");

        if ($icon.hasClass(that.selected_class)) {
            return false;
        }

        if (that.$activeIcon.length) {
            that.$activeIcon.removeClass(that.selected_class);
        }

        that.$form.find("input[name=\"data[icon]\"]")
            .val(icon_class)
            .trigger("change");

        $icon.addClass(that.selected_class);
        that.$activeIcon = $icon;
    };

    GroupEditDialog.prototype.save = function(try_num) {
        var that = this,
            href = "?module=group&action=save",
            data;

        try_num = try_num || 0;

        if (!that.is_locked) {
            that.is_locked = true;
            data = prepareData( that.$form.serializeArray() );

            if (data) {
                if (that.is_map_loading && try_num < 5) {
                    that.is_locked = false;
                    that.save_timeout = setTimeout( function() {
                        if ($.contains(document, that.$wrapper[0])) {
                            that.save(try_num + 1);
                        }
                    }, 1000);
                    return false;
                }

                var post = function () {
                    $.post(href, data, function(response) {
                        if (response.status == "ok") {
                            var content_uri = $.team.app_url + "group/" + response.data.id + "/manage/";
                            $.team.content.load( content_uri );
                            $.team.sidebar.reload();
                            that.is_locked = false;
                        }
                    });
                };

                var address = data['data[location][address]'],
                    lat = data['data[location][latitude]'],
                    lng = data['data[location][longitude]'];
                if (address && (!lat || !lng) && that.teamMap) {
                    that.teamMap.geocode(
                        address,
                        function (lat, lng) {
                            data['data[location][latitude]'] = lat;
                            data['data[location][longitude]'] = lng;
                            post();
                        },
                        function () {
                            post();
                        }
                    );
                    return;
                }

                post();



            } else {
                that.is_locked = false;
            }
        }

        function prepareData(data) {
            var result = {},
                errors = [];

            $.each(data, function(index, item) {
                result[item.name] = item.value;
            });

            if (!$.trim(result["data[name]"]).length) {
                errors.push({
                    field: "data[name]",
                    locale: "empty"
                });
            }

            if (errors.length) {
                showErrors(errors);
                return false;
            }

            return result;

            function showErrors( errors ) {
                // Remove old errors
                that.$form.find(".t-error").remove();

                // Display new errors
                $.each(errors, function(index, item) {
                    var $field = that.$form.find("[name='" + item.field + "']");
                    if ($field.length) {
                        $field
                            .addClass(that.has_error_class)
                            .after('<span class="t-error">' + that.locales[item.locale] + '</span>')
                    }
                });
            }
        }
    };

    GroupEditDialog.prototype.initMap = function( adapter ) {
        var that = this,
            $block = that.$block,
            $address = that.$addressToggle.find('.f-location-edit-address-input'),
            $longtitude = that.$addressToggle.find('.t-location-longitude-input'),
            $latitude = that.$addressToggle.find('.t-location-latitude-input'),
            $map = that.$mapToggle.find('.t-location-edit-map'),
            $hint = that.$addressToggle.find(".t-map-hint"),
            timeout = 0,
            teamMap;

        // init sizes otherwise map will not be shown
        if ($address.length > 0) {
            var width = that.$form.find(".value").first().width();
            $map.width(width);
            $map.height(width / 1.618);
        }

        // init map
        teamMap = new TeamMap($map, adapter);

        // first show on map
        var lng = $longtitude.val();
        var lat = $latitude.val();
        if (lng && lat) {
            openMap({
                lat: lat,
                lng: lng
            });
        }

        // bind events
        $address.on("change", function() {
            var address = $(this).val();
            if (address.length) {
                openMap(address);
            } else {
                $longtitude.val("");
                $latitude.val("");
                $map.hide();
            }
        });

        $address.on("keyup", function() {
            clearTimeout(timeout);
            var address = $(this).val();
            if (address.length) {
                that.is_map_loading = true;
                timeout = setTimeout( function() {
                    openMap(address);
                }, 1000);
            } else {
                $longtitude.val("");
                $latitude.val("");
                $map.hide();
            }
        });

        //
        function openMap(query) {
            clearTimeout(timeout);

            var is_address = $.type(query) === "string";
            if (is_address) {
                that.is_map_loading = true;
                teamMap.geocode(query, function(lat, lng) {
                    $latitude.val(lat);
                    $longtitude.val(lng);
                    $hint.hide();
                    openMap(lat, lng);
                    that.is_map_loading = false;
                }, function () {
                    $map.hide();
                    // correct top of dialog
                    $longtitude.val("");
                    $latitude.val("");
                    $hint.show();
                    that.is_map_loading = false;
                });
            } else {
                openMap(query.lat, query.lng);
            }

            function openMap(lat, lng) {
                if ($map.is(':hidden')) {
                    $map.show();
                    // correct top of dialog
                    $block.data('top', $block.offset().top);
                    $block.css('top', $block.data('top') - $map.height() / 2)
                }
                teamMap.render(lat, lng);
                $latitude.val(lat);
                $longtitude.val(lng);
            }
        }

        return teamMap;
    };

    return GroupEditDialog;

})(jQuery);

var GroupDeleteDialog = ( function($) {

    GroupDeleteDialog = function(options) {
        var that = this;

        // DOM
        that.$wrapper = options["$wrapper"];
        that.$block = that.$wrapper.find(".t-dialog-block");

        // VARS
        that.api_enabled = ( window.history && window.history.replaceState );
        that.group_id = options["group_id"];

        // DYNAMIC VARS
        that.is_locked = false;

        // INIT
        that.initClass();
    };

    GroupDeleteDialog.prototype.initClass = function() {
        var that = this;

        that.$block.on("click", ".js-delete-event", function(event) {
            event.preventDefault();
            if (!that.is_locked) {
                that['delete']();
            }
        });
    };

    GroupDeleteDialog.prototype['delete'] = function() {
        var that = this,
            href = "?module=group&action=delete",
            data = {
                id: that.group_id
            };

        that.is_locked = true;

        $.post(href, data, function(response) {
            if (response.status == "ok") {
                that.$wrapper.trigger("close");

                if (that.api_enabled) {
                    history.state.content_uri = $.team.app_url;
                    history.replaceState({
                        reload: true,
                        content_uri: $.team.app_url
                    }, "", $.team.app_url);

                    $.team.sidebar.reload();
                    $.team.content.reload();
                } else {
                    location.href = $.team.app_url;
                }

            }
        }, "json");
    };

    return  GroupDeleteDialog;

})(jQuery);
