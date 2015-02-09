/*
 * WBS UI Splitter
 *
 * Copyright (c) 2010
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.position.js
 *	jquery.ui.widget.js
 */
$.fn.extend({
	isChildrenOf: function(parent) {
		var self = this, childrens = parent.find('*'), result = false;
		if (!$(self).not(parent).length){
			result = true;
		} else {
			childrens.each(function(){
				if (!$(self).not(this).length){
					result = true;
				}
			})
		}
		return result;
	}
});
$.widget("ui.waPopup", {
	options: {
		width: false,
		height: false,
		constraint: false,
		padding: 0,
		resizeable: false,
		shadow: true,
		padding: 10,
		content: $('<div/>'),
		my: "left top",
		at: "left bottom",
		parentCorners: "ui-corner-top",
		fitParent: false,
		parentShadow: true,
        toggledByParent:true,
		myCorners: "ui-corner-bottom",
		appendToBody: false,
		parent: false,
		selectedParent: false,
		offset: {x:0,y:0},
		absolute: false,
		emptyContent:false,
		closed: true,
		open: function(){return false},
		close: function(){return false},
		load: function(){return false}
	},
	_create: function() {
		var self = this, o = this.options;
		this.element
			.addClass("ui-widget")
			.addClass(o.myCorners)
			.addClass("ui-popup");
		if (!o.emptyContent) 
			this.element.addClass("ui-widget-content")
		
		if (!o.parent) o.parent = this.element.parent();

		if (o.padding > 0)
			this.element.css({
				'padding': o.padding
			})
			
		if (o.appendToBody){
			$('body').append(this.element);
		} else {
			o.parent.after(this.element);
		}
		o.my = o.my.split(" ");
		o.at = o.at.split(" ");
		self.setPosition();
		o.shadow_prefix = ['','']
		if (o.at[1] == "top") {
			o.shadow_prefix[0] = '-r';
		}
		if (o.my[1] == "bottom") {
			o.shadow_prefix[1] = '-t';
		}

		if (o.fitParent) {
			if (self.element.outerWidth() < o.parent.outerWidth()){
				self.element.width(o.parent.outerWidth())
			} else {
				o.parent.width(self.element.outerWidth())
			}
		}
		
		if (o.shadow) this.element.addClass("ui-wa-shadow"+o.shadow_prefix[0] );
		
        this.element.hide();
        o.parentWidth = o.parent.outerWidth();
        o.parent.mousedown(function(){
        	self.open(this);
        })
		this.isOpen = false;
        $(document).click(function(event){
			if (self.element.is(':visible') && !self.isOpen) {
				if (o.toggledByParent) {
					if (!$(event.target).isChildrenOf(o.parent)) {
						if (!$(event.target).isChildrenOf(self.element)){
								self.close();
						}
					}
				} else {
					if (!$(event.target).isChildrenOf(self.element)){
						self.close();
					}
				}
			}
			if (self.isOpen) self.isOpen = false;
		})
		o.load();
	},
	setPosition: function(el){
		var o = this.options,
			parentOffsetLeft = 0, parentOffsetTop = 0,
			myOffsetLeft = 0, myOffsetTop = 0,
			leftOffset = 0, topOffset = 0,
			parentOffset = o.parent.position();
		
			if (o.appendToBody && el) {
				parentOffset = $(el).offset();
			}

		if (o.absolute && parentOffset != null) {
			if (o.at[0] == "right") {
				parentOffsetLeft = parentOffset.left;
			} else {
				parentOffsetLeft = parentOffset.left;
			}
		
			if (o.at[1] == "top") {
				parentOffsetTop = parentOffset.top;
			} else {
				parentOffsetTop = parentOffset.top + o.parent.outerHeight();
			}
		}
		
		if (o.my[0] == "right") {
			myOffsetLeft = parentOffsetLeft - (this.element.outerWidth() - o.parent.outerWidth());
		} else {
			myOffsetLeft = parentOffsetLeft;
		}
	
		if (o.my[1] == "top") {
			myOffsetTop = parentOffsetTop;
		} else {
			myOffsetTop = parentOffsetTop - this.element.outerHeight();
		}
		if (o.absolute) {
			this.element.css('position', 'absolute');
		} else {
			this.element.css('position', 'relative');
		}
		if (o.my[0] != "right"){ 
		this.element.css({
			'left': myOffsetLeft + o.offset.x,
			'top': myOffsetTop + o.offset.y
		})
		} else {
			var right = 0;
			if (o.appendToBody){
				right = $('body').width() - (parentOffset.left + o.parent.width());
			}
			this.element.css({
				'right': right,
				'top': myOffsetTop + o.offset.y
			})
		}
		
		if (o.width) {
			this.element.width(o.width)
		}
	},
	open: function(el){
		this.isOpen = true;
		var o = this.options;
		o.selectedParent = $(el);
		this.setPosition(el);
		if (o.closed) {
			this.element.show();
			if (o.parentCorners) {
				o.parent.removeClass("ui-corner-all");
				o.parent.addClass(o.parentCorners);
			}
			if (o.parentShadow) o.parent.addClass("ui-wa-shadow"+o.shadow_prefix[1]);
			o.closed = false;
			o.open(el);
		} else {
			this.close();
		}
	},
	close: function(){
		var o = this.options;
		this.element.hide();
		if (o.parentCorners) {
			o.parent.addClass("ui-corner-all");
		}
		if (o.parentShadow) o.parent.removeClass("ui-wa-shadow"+o.shadow_prefix[1]);
		o.closed = true;
		o.close();
	}
});
