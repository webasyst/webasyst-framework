/**
 * @version draft
 */

$.photos = $.photos || {};
$.photos.widget = $.photos.widget || {};
$.photos.widget.loupe = {
    settings : {},
    css: {
        'init':{
            'width' : null,
            'max-width' : null,
            'margin-left':0,
            'margin-top':0,
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
    mode:'move',
    animation:true,

    init : function(photo) {
        this.container = $('#photo');
        if (photo) {
            this.photo_data = photo;

            if(this.status != 'thumb') {
                this.stop(true);
            }
            this.thumb_data = {
                height : this.container.height(),
                width : this.container.width(),
                src : this.container.attr('src')
            };

            //this.css.init.width = this.thumb_data.width + 'px';
            //this.css.init.height = this.thumb_data.height + 'px';
            this.loaded = false;
        }
        this.link = $('#photo-loupe-link');
        this.refresh();


        this.status = 'thumb';
        this.link.find('.minimize').hide();
        if(this.thumb_data.width < this.photo_data.width) {
            this.link.find('.maximize').show();
        } else {
            this.link.find('.maximize').hide();
        }

        var self = this;
        this.link.unbind('.loupe').bind('click.loupe', function(e) {
            return self.clickHandler.apply(self, [this, e]);
        }).show();
    },


    refresh: function() {
        if ($('div.photo-loupe-wrapper').length) {
            this.container.unwrap();
        };
    },

    clickHandler : function() {
        switch (this.status) {
            case 'maximized' : {
                this.status = 'unloading';
                this.link.find('.minimize').hide();
                this.link.find('.maximize').show();
                this.stop();
                break;
            }
            case 'thumb' : {
                this.status = 'loading';
                this.link.find('.minimize').hide();
                this.link.find('.maximize').hide();
                this.start();
                break;
            }
        }
        return false;
    },

    interaction : function(element, e, node) {
        if(this.mode == 'move') {
            node = node.parents('body');
        }
        switch (e.type) {
            case 'mouseup' : {
                if (this.drag) {
                    e.preventDefault();
                    this.drag = false;
                    this.container.parent().css('cursor', 'auto');
                    node.unbind(".loupe-move");
                }
                break;
            }
            case 'mousedown' :
            case 'click' : {
                e.preventDefault();
                if (!this.drag) {
                    this.container.parent().css('cursor', 'move');
                    this.drag = true;
                    this.offset.mouseX = e.pageX,
                    this.offset.mouseY = e.pageY,
                    $('.p-one-photo a.next').live('click.loupe', function() {
                        return false;
                    });
                    var self = this;

                    node.bind("mouseover.loupe-move mousemove.loupe-move", function(e) {
                        return self.watch.apply(self, [this, e]);
                    });
                }
                break;
            }
            case 'mouseleave' : {
                if (this.drag/* && (this.mode != 'move')*/) {
                    e.preventDefault();
                    this.container.parent().css('cursor', 'auto');
                    this.drag = false;
                    node.unbind(".loupe-move");
                }
                break;
            }
        }
    },

    start : function() {
        this.drag = false;
        var self = this;
        $('#photo').removeClass("ui-draggable");
        this.offset = this.container.offset();
        this.offset.x = Math.round((this.thumb_data.width-this.photo_data.width)/2);
        this.offset.y = Math.round((this.thumb_data.height-this.photo_data.height)/2);
        this.container.wrap('<div class="photo-loupe-wrapper" style="height:'
                + this.thumb_data.height + 'px;width:' + this.thumb_data.width
                + 'px;position: relative;"/>');
        this.container.css({
            'width' : this.thumb_data.width + 'px',
            'height' : this.thumb_data.height + 'px',
            'margin-left':0,
            'margin-top':0,
            'max-width':this.thumb_data.width + 'px'

        }).removeAttr('width').removeAttr('height');
        if(this.animate) {
            this.container.delay(10).animate({
                'width' : this.photo_data.width + 'px',
                'height' : this.photo_data.height + 'px',
                'margin-left':this.offset.x+'px',
                'margin-top':this.offset.y+'px',
                'max-width' : this.photo_data.width + 'px'
            }, function() {
                return self.replace.apply(self, [this]);
            });
        } else {
            this.container.css({
                'width' : this.photo_data.width + 'px',
                'height' : this.photo_data.height + 'px',
                'margin-left':this.offset.x+'px',
                'margin-top':this.offset.y+'px',
                'max-width' : this.photo_data.width + 'px'
            });
            this.replace();
        }

        this.bind(this.container);

        if (!this.helper) {
            var search =  $('#photo-loupe');
            if (search.length) {
                this.helper = search.first();
            } else {
                this.helper = $('<img id="photo-loupe"/>');
            }
            this.helper.load(function(e) {
                self.loaded = true;
            });
        }
        this.helper.attr('src', '?module=photo&action=download&photo_id='
                + this.photo_data.id + '&attach=1');
    },

    bind : function(node) {
        var self = this;
        node.bind("mousedown.loupe",
                function(e) {
                    return self.interaction.apply(self, [this, e, node]);
        });
        $(document).bind("mouseup.loupe",
                function(e) {
                    return self.interaction.apply(self, [this, e, node]);
        });
    },

    stop : function(fast) {
        this.loaded = false;
        var self = this;
        var size = {
            'width' : this.thumb_data.width + 'px',
            'height' : this.thumb_data.height + 'px',
            'margin-left':0,
            'margin-top':0
        };
        if(fast || !this.animation) {
            this.helper.css(size);
            self.afterStop();
        } else {
            this.helper.animate(size, function() {self.afterStop();});
        }
        $('.p-one-photo a.next').die('.loupe');
        $('#photo').addClass("ui-draggable");
        return false;
    },
    afterStop: function() {
        this.container.css(this.css.init).attr({'width':this.thumb_data.width,'height':this.thumb_data.height}).show();
        this.helper.hide();
        this.init();
    },

    watch : function(element, e) {
        if (this.drag) {
            e.preventDefault();
            switch (this.mode) {
                case 'window' : {
                    this.offset.x = -Math
                            .round((e.pageX - this.offset.left)
                                    * (this.photo_data.width - this.thumb_data.width)
                                    / this.thumb_data.width);
                    this.offset.y = -Math.round((e.pageY - this.offset.top)
                            * (this.photo_data.height - this.thumb_data.height)
                            / this.thumb_data.height);
                    break;
                }
                case 'map' : {
                    this.offset.x = Math
                            .round((e.pageX - this.offset.left - this.thumb_data.width)
                                    * (this.photo_data.width - this.thumb_data.width)
                                    / this.thumb_data.width);
                    this.offset.y = Math
                            .round((e.pageY - this.offset.top - this.thumb_data.height)
                                    * (this.photo_data.height - this.thumb_data.height)
                                    / this.thumb_data.height);
                    break;
                }
                case 'move' : {
                    this.offset.x = Math.min(0,Math.max(this.thumb_data.width-this.photo_data.width,Math.round(this.offset.x - this.offset.mouseX+e.pageX)));
                    this.offset.y = Math.min(0,Math.max(this.thumb_data.height-this.photo_data.height,Math.round(this.offset.y - this.offset.mouseY+e.pageY)));
                    this.offset.mouseX = e.pageX;
                    this.offset.mouseY = e.pageY;
                    break;
                }
            }
            var item = (this.status=='loading') ? this.container : this.helper;
            item.css({
                'margin-left' : this.offset.x + 'px',
                'margin-top' : this.offset.y + 'px'
            });
        }
    },
    replace : function() {
        if (this.loaded) {
            this.helper.css({
                'width' : this.photo_data.width + 'px',
                'height' : this.photo_data.height + 'px',
                'margin-left':this.offset.x+'px',
                'margin-top':this.offset.y+'px',
                'max-width' : this.photo_data.width + 'px'
            }).show();
            this.container.before(this.helper).hide();
            this.bind(this.helper);
            this.container.unbind(".loupe .loupe-move");
            this.container.css({
                'width' : '',
                'max-width' : '',
                'height' : ''
            }).attr('width','').attr('height','');

            this.link.find('.minimize').show();
            this.status = 'maximized';
        } else {
            var self = this;
            setTimeout(function() {
                return self.replace.apply(self, [this]);
            }, 50);
        }
    }
};
