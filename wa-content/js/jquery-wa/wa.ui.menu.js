/*
 * WBS UI Splitter
 *
 * Copyright (c) 2010
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.popup.js
 *	jquery.ui.widget.js
 */

$.widget("ui.waMenu", {
	options: {
		selectName: '',
		source: 'json',
		width: false,
		offset: {x:0,y:0},
		stateful: false,
		direction: 'down',
		onSelect: function(){return false}
	},
	_createItem: function(html, value){
		if (!value) value = '';
		var $wrapper = $("<div class='ui-menu-item ui-widget-content' rel='" + value + "'></div>")
		if ($(html).length){
			$wrapper.append( $(html).html( $(html).text().replace(/\s/g,"&nbsp;") ) )
		} else {
			$wrapper.append( html.replace(/\s/g,"&nbsp;") )
		}
		return $wrapper;
	},
	_setWidth: function(){
		var self = this, o = self.options, $options = $('.ui-menu-item', self.content);
		if (!o.width){
			if (self.header.width() >= self.content.width()){
				self.content.width(self.header.width())
			} else {
				self.content.css('width','auto');
			}
		} else {
			self.content.css('width',o.width);
		}

		$options.each(function(){
			if ($(this).outerWidth(true) > self.content.width()){
				self.content.width($(this).outerWidth(true));
			}
		})
	
	},
	_create: function() {
		var self = this, o = this.options;

		this.menu = $("<div style='position:relative;float:left;'/>");
		this.header = $("<div />");
		this.content = $("<div/>");
		
		this.clone = this.element.clone();
		
		this.menu.addClass("ui-widget");
		
		if(o.direction == "down") {
			var l1 = "ui-corner-left", l2 = "ui-corner-tl", l3 = "ui-corner-left-deleted",
				r1 = "ui-corner-right", r2 = "ui-corner-tr", r3 = "ui-corner-right-deleted",
				lr1 = "ui-corner-all", lr2 = "ui-corner-top", lr3 = "ui-corner-all-deleted";
		} else {
			var l1 = "ui-corner-left", l2 = "ui-corner-bl", l3 = "ui-corner-left-deleted",
				r1 = "ui-corner-right", r2 = "ui-corner-br", r3 = "ui-corner-right-deleted",
				lr1 = "ui-corner-all", lr2 = "ui-corner-bottom", lr3 = "ui-corner-all-deleted";
		}
		
		if (o.source == 'json'){
			var items = [], $selected = false;
			for (var key in o.items){
				var $currentItem = $(self._createItem( o.items[key].name, o.items[key].value ));
				self.content.append($currentItem);
				$currentItem.mousedown(
					o.items[key].callback
				);
				$currentItem.mousedown(
						o.onSelect
				);
			}
			this.header.append(this.clone.addClass('ui-menu-header ui-corner-all'));
			this.menu.append(this.header);
			this.menu.append(this.content);
			this.element.replaceWith(this.menu);
			
			$('.ui-menu-item', this.content).mousedown(
				function(){
					if (o.stateful) {
						$('.ui-menu-item', this.content).removeClass('ui-state-active');
						$(this).addClass('ui-state-active');
					}
					self.content.waPopup('close');
				}
			);
		}
		
		$('.ui-menu-item', this.content).hover(
			function(){
				$(this).addClass('ui-state-default')
			},
			function(){
				$(this).removeClass('ui-state-default')
			}
		)
		this.header.css('z-index','5000');
		this.header.click(function(e){
			e.preventDefault();
		})
		
		if (o.direction =="up") {
			var my = "left bottom",
				at = "left top";
		} else {
			var my = "left top",
				at = "left bottom";
		}
		this.content.waPopup({
			padding: 0,
			parent: this.header,
			parentCorners: "ui-corner-top",
			emptyContent:true,
			absolute: true,
			offset: o.offset,
			my: my,
			at: at,
			open: function(){
			if (this.parent.children().length > 1){
				if (this.parent.children(':first').hasClass(l1))
					this.parent.children(':first').removeClass(l1).addClass(l2).addClass(l3);
	
				if (this.parent.children(':last').hasClass(r1))
					this.parent.children(':last').removeClass(r1).addClass(r2).addClass(r3);
			} else {
				this.parent.children().removeClass(lr1).addClass(lr2).addClass(lr3);
			}
			self._setWidth();
			},
			close: function(){
				if (this.parent.children().length > 1){
					if (this.parent.children(':first').hasClass(l3))
						this.parent.children(':first').addClass(l1).removeClass(l3);
					if (this.parent.children(':last').hasClass(r3))
						this.parent.children(':last').addClass(r1).removeClass(r3);
				} else {
					this.parent.children().addClass(lr1).removeClass(lr3);
				}
			}
		})
		self._setWidth();
		//this.content.width(this.header.outerWidth())
		
	}
});
