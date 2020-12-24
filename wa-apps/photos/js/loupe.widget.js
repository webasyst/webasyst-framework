/**
 * @version draft
 */

$.photos = $.photos || {};
$.photos.widget = $.photos.widget || {};
$.photos.widget.loupe = {
    options : {
        'animate' : true,
        'debug' : false
    },
    css : {
        'init' : {
            'width' : null,
            'max-width' : null,
            'margin-left' : 0,
            'margin-top' : 0,
            'height' : null
        }
    },
    drag : false,
    loaded : false,
    photo_data : null,
    thumb_data : null,
    container : null,
    helper : null,
    link : null,
    offset : {},
    status : 'thumb',
    image_container_width: 0,
    image_container_height: 0,
    image_width: 0,
    image_height: 0,

    init : function(options) {
        this.options = $.extend(this.options, options || {});
        var self = this;
        self.trace('init');
        $.photos.hooks_manager.bind('afterRenderImg', function(img, photo, proper_thumb) {
            self.trace('afterRenderImg');
            self.prepare(img, photo, proper_thumb);
        });
        $.photos.hooks_manager.bind('beforeLoadPhoto', function() {
            self.trace('beforeLoadPhoto');
            //self.stop();
        });
        $.photos.hooks_manager.bind('onAbortPrevLoading', function() {
            self.trace('onAbortPrevLoading');
            //self.stop();
        });
    },

    trace : function(message) {
        if (console && this.options.debug) {
            console.log(message);
        }
    },

    prepare : function(img, photo, proper_thumb) {

        var self = this;
        $('.p-one-photo a.next').die('click.loupe').live('click.loupe', function(e) {
            return self.clickNextHandler.apply(self, [this, e]);
        });
        this.trace('prepare, status='+this.status);
        this.photo_data = photo;
        this.container = img;

        let image_container = this.container.closest('.p-one-photo');
        this.image_container_width = image_container.width();
        this.image_container_height = image_container.height();
        this.image_width = this.container.width();
        this.image_height = this.container.height();

        this.thumb_data = {
                height : this.image_height,
                width : this.image_width,
                src : proper_thumb.url2x
        };

        if (photo.height >= this.image_container_height) {
            this.container.css({
                'width' : this.image_width + 'px',
                'height' : this.image_height + 'px',
            });
        }else{
            this.container.css({
                'width' : proper_thumb.size.width + 'px',
                'height' : proper_thumb.size.height + 'px',
            });
        }

        window.addEventListener("resize", function() {
            let image_container = self.container.closest('.p-one-photo');
            self.image_container_width = image_container.width();
            self.image_container_height = image_container.height();
            self.image_width = self.container.width();
            self.image_height = self.container.height();

            self.thumb_data = {
                height : self.image_height,
                width : self.image_width,
                src : proper_thumb.url2x
            };

            if (photo.height >= self.image_container_height) {
                self.container.css({
                    'width' : self.image_width + 'px',
                    'height' : self.image_height + 'px',
                });
            }else{
                self.container.css({
                    'width' : proper_thumb.size.width + 'px',
                    'height' : proper_thumb.size.height + 'px',
                });
            }
        });

        if (this.status != 'thumb') {
            this.stop();
        }

        this.loaded = false;
        this.reset();
    },

    reset : function() {
        var self = this;
        this.trace('reset, status='+this.status);
        this.link = $('#photo-loupe-link');
        if ($('div.photo-loupe-wrapper').length) {
            this.container.css({
                'width' : this.image_width + 'px',
                'max-width' : '',
                'height' : this.image_height + 'px',
                'margin-left' : '',
                'margin-top' : ''
            });
            this.container.unwrap();
        }

        this.status = 'thumb';
        this.link.find('.minimize').hide();
        if ((this.image_container_width && this.image_container_width < this.photo_data.width) || (this.image_container_height && this.image_container_height < this.photo_data.height) ) {
            this.link.find('.maximize').show();
            this.options.animate = (this.image_container_height && this.image_container_width) ? true : false;
        } else {
            this.link.find('.maximize').hide();
        }

        this.link.unbind('.loupe').bind('click.loupe', function(e) {
            return self.clickHandler.apply(self, [this, e]);
        }).show();

        window.addEventListener("resize", function() {
            if ((self.image_container_width && self.image_container_width < self.photo_data.width) || (self.image_container_height && self.image_container_height < self.photo_data.height) ) {
                self.link.find('.maximize').show();
                self.options.animate = (self.image_container_height && self.image_container_width) ? true : false;
            } else {
                self.link.find('.maximize').hide();
            }

            self.link.unbind('.loupe').bind('click.loupe', function(e) {
                return self.clickHandler.apply(self, [this, e]);
            }).show();
        })
    },

    clickHandler : function() {
        this.trace('clickHandler, status='+this.status);
        switch (this.status) {
            case 'loading': {
                this.container.stop();
            }
            case 'maximized' : {
                this.status = 'unloading';
                this.link.find('.minimize').hide();
                this.link.find('.maximize').show();
                this.decrease();
                break;
            }
            case 'thumb' : {
                this.status = 'loading';
                this.link.find('.minimize').hide();
                this.link.find('.maximize').hide();
                this.enlarge();
                break;
            }
        }
        return false;
    },

    clickNextHandler: function(element, e) {
        var res = (this.status == 'thumb') ? true: false;
        if(!res) {
            e.preventDefault();
        }
        this.trace('clickNextHandler '+res);
        return res;
    },

    interaction : function(element, e, node) {
        node = node.parents('body');
        switch (e.type) {
            case 'mouseup' : {
                if (this.drag) {
                    e.preventDefault();
                    this.drag = false;
                    $('div.photo-loupe-wrapper').css('cursor', 'auto');
                    $('body').css('cursor', '');
                    node.unbind(".loupe-move");
                }
                break;
            }
            case 'mousedown' :
            case 'click' : {
                this.clickNextHandler(element, e);
                if (!this.drag) {
                    $('div.photo-loupe-wrapper').css('cursor', 'move');
                    this.drag = true;
                    this.offset.mouseX = e.pageX;
                    this.offset.mouseY = e.pageY;
                    var self = this;

                    node.bind("mouseover.loupe-move mousemove.loupe-move", function(e) {
                        return self.watch.apply(self, [this, e]);
                    });
                }
                break;
            }
            case 'mouseleave' : {
                if (this.drag) {
                    e.preventDefault();
                    $('div.photo-loupe-wrapper').css('cursor', 'auto');
                    this.drag = false;
                    node.unbind(".loupe-move");
                }
                break;
            }
        }
    },

    enlarge : function() {
        this.drag = false;
        var self = this;
        this.trace('enlarge, status='+this.status);
        $('#photo').removeClass("ui-draggable").closest('.p-image').addClass('p-image-maximized');
        this.offset = this.container.offset();
        this.offset.x = Math
                .round((this.image_container_width - this.photo_data.width) / 2);
        this.offset.y = Math
                .round((this.image_container_height - this.photo_data.height) / 2);

        this.container.wrap('<div class="photo-loupe-wrapper" style="height:'
                + this.image_container_height + 'px;width:' + this.image_container_width
                + 'px;position: relative;"/>');
        window.addEventListener("resize", function() {
            this.offset = this.container.offset();
            this.offset.x = Math
                .round((this.image_container_width - this.photo_data.width) / 2);
            this.offset.y = Math
                .round((this.image_container_height - this.photo_data.height) / 2);

            this.container.wrap('<div class="photo-loupe-wrapper" style="height:'
                + this.image_container_height + 'px;width:' + this.image_container_width
                + 'px;position: relative;"/>');
        });
        this.container.removeAttr('width').removeAttr('height').css({
            'width' : this.image_width + 'px',
            'height' : this.image_height + 'px',
            'margin-left' : 0,
            'margin-top' : 0,
            'max-height' : this.photo_data.height + 'px',
            'max-width' : this.photo_data.width + 'px',
            'display' : 'inline-block'

        });
        this.options.animate = true
        if (this.options.animate) {
            this.container.animate({
                'width' : this.photo_data.width + 'px',
                'height' : this.photo_data.height + 'px',
                'margin-left' : this.offset.x + 'px',
                'margin-top' : this.offset.y + 'px',
                'max-height' : this.photo_data.height + 'px',
                'max-width' : this.photo_data.width + 'px'
            }, function() {
                self.enlargeComplete.apply(self, [this]);
            });
        } else {
            this.container.css({
                'width' : this.photo_data.width + 'px',
                'height' : this.photo_data.height + 'px',
                'margin-left' : this.offset.x + 'px',
                'margin-top' : this.offset.y + 'px',
                'max-width' : this.photo_data.width + 'px'
            });
            this.enlargeComplete();
        }
        this.link.find('.minimize').show();

        this.bind(this.container);

        if (!this.helper) {
            var search = $('#photo-loupe');
            if (search.length) {
                this.helper = search.first();
            } else {
                this.helper = $('<img id="photo-loupe"/>');
            }
            this.helper.load(function(e) {
                self.loaded = true;
            });
        }
        var src = '?module=photo&action=download&photo_id='
                + this.photo_data.id + '&attach='+(this.photo_data.edit_datetime||this.photo_data.upload_datetime);
        this.helper.attr('src', src);
    },
    enlargeComplete : function() {
        if (this.loaded) {
            this.trace('enlargeComplete, status='+this.status);
            this.helper.css({
                'width' : this.photo_data.width + 'px',
                'height' : this.photo_data.height + 'px',
                'margin-left' : this.offset.x + 'px',
                'margin-top' : this.offset.y + 'px',
                'max-width' : this.photo_data.width + 'px'
            }).show();
            this.container.before(this.helper).hide();
            this.container.unbind(".loupe .loupe-move");
            this.bind(this.helper);
            this.container.css({
                'width' : this.thumb_data.width + 'px',
                'max-width' : '',
                'height' : this.thumb_data.height + 'px',
                'margin-left' : '',
                'margin-top' : ''
            });

            this.link.find('.minimize').show();
            this.status = 'maximized';
        } else {
            var self = this;
            setTimeout(function() {
                return self.enlargeComplete.apply(self, [this]);
            }, 50);
        }
    },

    bind : function(node) {
        this.trace('bind, status='+this.status);
        var self = this;
        node.bind("mousedown.loupe", function(e) {
            return self.interaction.apply(self, [this, e, node]);
        });
        $(document).bind("mouseup.loupe", function(e) {
            return self.interaction.apply(self, [this, e, node]);
        });
    },

    stop: function() {
        this.trace('stop at status='+this.status);
        var status = this.status;
        this.status = 'stop';
        switch(status) {
            case 'loading':{
                this.container.stop();
                this.decrease(true);
                break;
            }
            case 'maximized':{
                this.decrease(true);
                break;
            }
            case 'unloading':{
                if(this.helper) {
                    this.helper.stop();
                }
                this.decreaseComplete(true);
                break;
            }
            case 'thumb':{
                break;
            }
        }
        if(this.container) {
            this.container.find('*').unbind('.loupe .loupe-move');
        }
        $(document).unbind('.loupe .loupe-move');
        var wrapper = $('div.photo-loupe-wrapper');
        $('#photo-loupe-link img:visible').hide();
        $('body').css('cursor', '');
        if (wrapper.length) {
            this.container.unwrap();
        };
        if(this.helper) {
            this.helper.remove();
            this.helper = null;
        }
        this.status = 'thumb';
        //stop animation
        //remove wrapper
        //hide img helper
        //restore size
    },

    decrease : function(fast) {
        this.trace('decrease, status='+this.status);
        this.loaded = false;

        var self = this;
        if (this.photo_data.height >= this.image_container_height) {
            var size = {
                'width' : this.image_width + 'px',
                'height' : this.image_height + 'px',
                'margin-left' : 0,
                'margin-top' : 0
            };
        }else{
            var size = {
                'width' : this.thumb_data.width + 'px',
                'height' : this.thumb_data.height + 'px',
                'margin-left' : 0,
                'margin-top' : 0
            };
        }

        if (fast || !this.options.animate) {
            this.helper.css(size);
            self.decreaseComplete();
        } else {
            this.helper.animate(size, function() {
                self.decreaseComplete();
            });
        }
        return false;
    },

    decreaseComplete : function(skip) {
        this.trace('decreaseComplete, status='+this.status);
        $('#photo').addClass("ui-draggable").closest('.p-image').removeClass('p-image-maximized');
        this.container.css(this.css.init).show();
        this.helper.hide();
        if(!skip) {
            this.reset();
        }
    },

    watch : function(element, e) {
        if (this.drag) {
            e.preventDefault();

            this.offset.x = Math.min(0, Math.max(this.image_width
                    - this.photo_data.width, Math.round(this.offset.x
                    - this.offset.mouseX + e.pageX)));
            this.offset.y = Math.min(0, Math.max(this.image_height
                    - this.photo_data.height, Math.round(this.offset.y
                    - this.offset.mouseY + e.pageY)));
            this.offset.mouseX = e.pageX;
            this.offset.mouseY = e.pageY;
            var item = (this.status == 'loading')
                    ? this.container
                    : this.helper;
            item.css({
                'margin-left' : this.offset.x + 'px',
                'margin-top' : this.offset.y + 'px'
            });
        }
    }
};
