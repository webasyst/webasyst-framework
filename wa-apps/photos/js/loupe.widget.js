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
            'width'     : null,
            'max-width' : null,
            'height'    : null,
            'transform' : 'translate3d(0, 0, 0)'
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


    init : function(options) {
        const self = this;
        this.options = $.extend(this.options, options || {});
        self.trace('init');

        $.photos.hooks_manager.bind('afterRenderImg', function(img, photo, proper_thumb) {
            self.trace('afterRenderImg');
            self.prepare(img, photo, proper_thumb);
        });

        $.photos.hooks_manager.bind('beforeLoadPhoto', function() {
            self.trace('beforeLoadPhoto');
            self.stop();
        });

        $.photos.hooks_manager.bind('onAbortPrevLoading', function() {
            self.trace('onAbortPrevLoading');
            self.stop();
        });
    },

    trace : function(message) {
        if (console && this.options.debug) {
            console.log(message);
        }
    },

    prepare : function(img, photo, proper_thumb) {
        const self = this;

        $('.p-one-photo a.next').off('click.loupe').on('click.loupe', function(e) {
            return self.clickNextHandler.apply(self, [this, e]);
        });

        this.trace('prepare, status='+this.status);
        this.photo_data = photo;
        this.container = img;
        this.thumb_data = {
            height : proper_thumb.size.height,
            width : proper_thumb.size.width,
            src : proper_thumb.url
        };

        const image_container = self.container.closest('.p-one-photo');
        self.image_container_width = image_container.width();
        self.image_container_height = image_container.height();

        if (this.status !== 'thumb') {
            this.stop();
        }

        this.loaded = false;
        this.reset();
    },

    reset : function() {
        const self = this;

        this.trace('reset, status='+this.status);
        this.link = $('#photo-loupe-link');
        if ($('div.photo-loupe-wrapper').length) {
            this.container.css({
                'max-width' : '',
                'transform' : '',
            });
            this.container.unwrap();
        };

        this.status = 'thumb';
        this.link.addClass('hidden');

        if ((this.thumb_data?.width < this.photo_data.width) || (this.thumb_data?.height < this.photo_data.height) ) {
            this.link.removeClass('hidden');
            this.options.animate = (this.thumb_data.height && this.thumb_data.width);
        } else {
            this.link.addClass('hidden');
        }

        this.link.unbind('.loupe').bind('click.loupe', function(e) {
            return self.clickHandler.apply(self, [this, e]);
        });
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

                this.loader = $('<div class="spinner"></div>');
                this.loader.css({
                    'position': 'absolute',
                    'top': '50%',
                    'left': '50%',
                    'width': '200px',
                    'height': '200px',
                    'transform': 'translate(-50%, -50%)'
                });
                this.loader.insertBefore(this.container);
                $(this.container).hide();

                this.link.find('.minimize').hide();
                this.link.find('.maximize').hide();

                this.enlarge();
                break;
            }
        }
        return false;
    },

    clickNextHandler: function(element, e) {
        const res = (this.status == 'thumb') ? true: false;

        if(!res) {
            e.preventDefault();
        }

        this.trace('clickNextHandler '+res);
        return res;
    },

    interaction : function(element, e, node) {
        node = node.parents('body');
        switch (e.type) {
            case 'touchend' :
            case 'mouseup' : {
                if (this.drag) {
                    e.preventDefault();
                    this.drag = false;
                    node.unbind(".loupe-move");
                }
                break;
            }
            case 'touchstart' :
            case 'mousedown' : {
                this.clickNextHandler(element, e);

                if (this.drag) {
                    return;
                }

                const self = this;

                this.drag = true;

                if (e.type === 'touchstart') {
                    this.offset.mouseX = e.touches[0].pageX;
                    this.offset.mouseY = e.touches[0].pageY;
                } else {
                    this.offset.mouseX = e.pageX;
                    this.offset.mouseY = e.pageY;
                }

                node.bind("mouseover.loupe-move mousemove.loupe-move touchmove.loupe-move", function(e) {
                    return self.watch.apply(self, [this, e]);
                });

                break;
            }
            case 'touchleave' :
            case 'mouseleave' : {
                if (this.drag) {
                    e.preventDefault();
                    this.drag = false;
                    node.unbind(".loupe-move");
                }
                break;
            }
        }
    },

    enlarge : function() {
        const self = this;

        this.drag = false;
        this.trace('enlarge, status='+this.status);
        $('#photo').closest('.p-image').addClass('p-image-maximized');

        this.offset = this.container.offset();
        this.offset.x = (self.image_container_width > this.photo_data.width) ? '0' : Math.round((self.image_container_width - this.photo_data.width) / 2);
        this.offset.y = (self.image_container_height > this.photo_data.height) ? '0' : Math.round((self.image_container_height - this.photo_data.height) / 2);

        this.container.wrap('<div class="photo-loupe-wrapper"/>');
        $('.photo-loupe-wrapper').css({ 'height': self.image_container_height, 'width': self.image_container_width });

        this.container.css({
            'max-width' : this.photo_data.width + 'px',
            'transform' : 'translate3d(0, 0, 0)',
        });

        if (this.options.animate) {
            this.container.animate({
                'max-width' : this.photo_data.width + 'px',
                'transform' : `translate3d(${this.offset.x}px, ${this.offset.y}px, 0)`,
            }, function() {
                return self.enlargeComplete.apply(self, [this]);
            });
        } else {
            this.container.css({
                'max-width' : this.photo_data.width + 'px',
                'transform' : `translate3d(${this.offset.x}px, ${this.offset.y}px, 0)`,
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

        const src = '?module=photo&action=download&photo_id='
            + this.photo_data.id + '&attach='+(this.photo_data.edit_datetime||this.photo_data.upload_datetime);

        const loadImage = (url) => new Promise((resolve, reject) => {
            const img = new Image();
            img.addEventListener('load', () => {
                resolve(img);
            });
            img.addEventListener('error', reject);
            img.src = url;
        });

        loadImage(src)
          .then(img => {
              $(this.helper).addClass('fade-in');
              $(this.helper).on('animationend', function() {
                  self.loader.remove();
              });
              this.helper.attr('src', img.src);
          })
          .catch(err => console.error(err));
    },

    enlargeComplete : function() {
        if (!this.loaded) {
            const self = this;
            setTimeout(function() {
                return self.enlargeComplete.apply(self, [this]);
            }, 50);
            return;
        }

        this.trace('enlargeComplete, status='+this.status);
        this.helper.css({
            'max-width' : this.photo_data.width + 'px',
            'transform' : `translate3d(${this.offset.x}px, ${this.offset.y}px, 0)`,
        }).show();
        this.container.before(this.helper).hide();
        this.container.unbind(".loupe .loupe-move");
        this.bind(this.helper);
        this.container.css({
            'max-width' : '',
            'transform' : '',
        });

        this.link.find('.minimize').show();
        this.status = 'maximized';

        $('.p-image-nav').hide();
    },

    bind : function(node) {
        this.trace('bind, status='+this.status);
        var self = this;
        node.bind("mousedown.loupe touchstart.loupe", function(e) {
            return self.interaction.apply(self, [this, e, node]);
        });
        $(document).bind("mouseup.loupe touchend.loupe", function(e) {
            return self.interaction.apply(self, [this, e, node]);
        });
    },

    stop: function() {
        const self = this;
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
        const self = this;

        this.trace('decrease, status='+this.status);
        this.loaded = false;

        const size = {
            'transform' : 'translate3d(0, 0, 0)',
        };

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
        $('#photo').closest('.p-image').removeClass('p-image-maximized');

        this.link.find('.minimize').hide();
        this.link.find('.maximize').show();

        this.helper.hide();

        this.container.css(this.css.init).show();

        this.loader.remove();

        $('.p-image-nav').show();

        if(!skip) {
            this.reset();
        }
    },

    watch : function(element, e) {
        if (!this.drag) {
            return;
        }

        if (e.type === 'touchmove') {
            this.offset.x = Math.min(0, Math.max(this.image_container_width
              - this.photo_data.width, Math.round(this.offset.x
              - this.offset.mouseX + e.touches[0].pageX)));
            this.offset.y = Math.min(0, Math.max(this.image_container_height
              - this.photo_data.height, Math.round(this.offset.y
              - this.offset.mouseY + e.touches[0].pageY)));
        } else {
            this.offset.x = Math.min(0, Math.max(this.image_container_width
              - this.photo_data.width, Math.round(this.offset.x
              - this.offset.mouseX + e.pageX)));
            this.offset.y = Math.min(0, Math.max(this.image_container_height
              - this.photo_data.height, Math.round(this.offset.y
              - this.offset.mouseY + e.pageY)));
        }

        if (e.type === 'touchmove') {
            this.offset.mouseX = e.touches[0].pageX;
            this.offset.mouseY = e.touches[0].pageY;
        } else {
            this.offset.mouseX = e.pageX;
            this.offset.mouseY = e.pageY;
        }

        var item = (this.status == 'loading')
            ? this.container
            : this.helper;
        item.css({
            'transform' : `translate3d(${this.offset.x}px, ${this.offset.y}px, 0)`,
        });
    }
};
