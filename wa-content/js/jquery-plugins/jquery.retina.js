/**
 * https://github.com/imulus/retinajs/blob/master/src/retina.js
 */
(function($) {
    $.fn.retina = function(options) {
        if ($.Retina.isRetina()) {
            $.Retina.opts = $.extend($.Retina.opts, options);
            return this.each(function() {
                if ($(this).is('img')) {
                    new RetinaImage(this);
                }
            });
        }
    }

    function Retina() {}
    $.Retina = Retina;
    $.Retina.opts = {
        // Ensure Content-Type is an image before trying to load @2x image
        // https://github.com/imulus/retinajs/pull/45)
        check_mime_type: true,
        // Resize high-resolution images to original image's pixel dimensions
        // https://github.com/imulus/retinajs/issues/8
        force_original_dimensions: true
    }

    $.Retina.isRetina = function() {
        var mediaQuery = '(-webkit-min-device-pixel-ratio: 1.5),' +
                      '(min--moz-device-pixel-ratio: 1.5),' +
                      '(-o-min-device-pixel-ratio: 3/2),' +
                      '(min-resolution: 1.5dppx)';

        if (window.devicePixelRatio > 1)
            return true;

        if (window.matchMedia && window.matchMedia(mediaQuery).matches)
            return true;

        return false;
    }

    function RetinaImagePath(path, at_2x_path) {
        this.path = path;
        if (typeof at_2x_path !== "undefined" && at_2x_path !== null) {
            this.at_2x_path = at_2x_path;
            this.perform_check = false;
        } else {
            this.at_2x_path = path.replace(/\.\w+$/, function(match) { return "@2x" + match; });
            this.perform_check = true;
        }
    }

    RetinaImagePath.confirmed_paths = [];

    RetinaImagePath.prototype.is_external = function() {
        return !!(this.path.match(/^(https?\:|\/\/)/i) && !this.path.match('//' + document.domain + '/') );
    }

    RetinaImagePath.prototype.check_2x_variant = function(callback) {
        var http, that = this;
        if (this.is_external()) {
            return callback(true);
        } else if (!this.perform_check && typeof this.at_2x_path !== "undefined" && this.at_2x_path !== null) {
            return callback(true);
        } else if (this.at_2x_path in RetinaImagePath.confirmed_paths) {
            return callback(true);
        } else {
            http = new XMLHttpRequest();
            http.open('HEAD', this.at_2x_path);
            http.onreadystatechange = function() {
                if (http.readyState != 4) {
                    return callback(false);
                }

                if (http.status >= 200 && http.status <= 399) {
                    if ($.Retina.opts.check_mime_type) {
                        var type = http.getResponseHeader('Content-Type');
                        if (type === null || !type.match(/^image/i)) {
                            return callback(false);
                        }
                    }

                    RetinaImagePath.confirmed_paths.push(that.at_2x_path);
                    return callback(true);
                } else {
                    return callback(false);
                }
            };
            http.send();
        }
    }

    function RetinaImage(el) {
        if (!/@2x\.\w+$/.test($(el).attr('src'))) {
            this.el = el;
            this.path = new RetinaImagePath($(el).attr('src'), $(el).attr('data-at2x'));
            var that = this;
            this.path.check_2x_variant(function(hasVariant) {
                if (hasVariant) that.swap();
            });
        }
    }

    $.RetinaImage = RetinaImage;

    RetinaImage.prototype.swap = function(path) {
        if (typeof path == 'undefined') path = this.path.at_2x_path;

        var that = this;
        function load() {
            if (! that.el.complete) {
                setTimeout(load, 5);
            } else {
                if ($.Retina.opts.force_original_dimensions) {
                    if (that.el.offsetWidth == 0 && that.el.offsetHeight == 0) {
                        that.el.setAttribute('width', that.el.naturalWidth);
                        that.el.setAttribute('height', that.el.naturalHeight);
                    } else {
                        that.el.setAttribute('width', that.el.offsetWidth);
                        that.el.setAttribute('height', that.el.offsetHeight);
                    }
                }

                var old_src = that.el.src;
                that.el.setAttribute('src', path);
                $(that.el).one('error', function() {
                    that.el.setAttribute('src', old_src);
                });
            }
        }
        load();
    }
})(jQuery);