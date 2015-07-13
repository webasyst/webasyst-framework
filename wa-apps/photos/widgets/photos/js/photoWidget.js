var PhotoWidget;

( function($) {

    var storage = {
        invisibleClass: "is-invisible",
        animation_time: 1000,
        loading_time: 1000
    };

    var getImageHTML = function() {
        return $("<a class=\"image-wrapper\" href=\"\"></a>");
    };

    var getNewImage = function(that) {
        var new_photo_index = parseInt( Math.random() * (that.photos.length - 1)),
            new_photo = that.photos[new_photo_index],
            $newPhoto = getImageHTML();

        $newPhoto.css({
            "background-image":"url(" + new_photo['image_href'] + ")"
        });

        $newPhoto.attr("href", new_photo['link_href']);

        return $newPhoto;
    };

    var getIntervalTime = function() {
        return ( 2 + parseInt( Math.random() * 5 ) ) * 1000
    };

    PhotoWidget = function(options) {
        var that = this;

        // DOM
        that.widget = options.widget;
        that.$wrapper = that.widget.$widget.find(".photo-widget-wrapper");

        // Vars
        that.photos = options.photos;
        that.play = true;

        // Init
        that.initPhotos();
    };

    PhotoWidget.prototype.initPhotos = function() {
        var that = this,
            $photo_wrapper = that.$wrapper.find(".photo-item-wrapper");

        $photo_wrapper.each( function() {
            var $wrapper = $(this);

            that.refreshPhoto($wrapper);
        });
    };

    PhotoWidget.prototype.refreshPhoto = function($wrapper) {
        if ($wrapper.length) {
            var that = this,
                $newPhoto = getNewImage(that),
                $currentPhoto = $wrapper.find(".image-wrapper");

            // Render
            $wrapper.prepend($newPhoto);

            // Show Photo
            if (that.play) {
                setTimeout( function() {
                    $currentPhoto.addClass(storage.invisibleClass);

                    // Show Photo
                    if (that.play) {
                        setTimeout(function () {
                            $currentPhoto.remove();

                            if (that.play) {
                                setTimeout(function () {
                                    that.refreshPhoto($wrapper);
                                }, getIntervalTime());
                            }

                        }, storage.animation_time);
                    }

                }, storage.loading_time);
            }
        }
    };

    PhotoWidget.prototype.stopRefresh = function() {
        var that = this;

        that.play = false;
    }

})(jQuery);