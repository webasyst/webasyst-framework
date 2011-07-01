var dragged = false;
$.widget("ui.waColorpicker", {
	options: {
		colors: [
		 ['FFFFFF'],['CCCCCC'],['C0C0C0'],['999999'],['666666'],['333333'],['000000'],
		 ['FFCCCC'],['FF6666'],['FF0000'],['CC0000'],['990000'],['660000'],['330000'],
		 ['FFCC99'],['FF9966'],['FF9900'],['FF6600'],['CC6600'],['993300'],['663300'],
		 ['FFFF99'],['FFFF66'],['FFCC66'],['FFCC33'],['CC9933'],['996633'],['663333'],
		 ['FFFFCC'],['FFFF33'],['FFFF00'],['FFCC00'],['999900'],['666600'],['333300'],
		 ['99FF99'],['66FF99'],['33FF33'],['33CC00'],['009900'],['006600'],['003300'],
		 ['99FFFF'],['33FFFF'],['66CCCC'],['00CCCC'],['339999'],['336666'],['003333'],
		 ['CCFFFF'],['66FFFF'],['33CCFF'],['3366FF'],['3333FF'],['000099'],['000066'],
		 ['CCCCFF'],['9999FF'],['6666CC'],['6633FF'],['6600CC'],['333399'],['330099'],
		 ['FFCCFF'],['FF99FF'],['CC66CC'],['CC33CC'],['993399'],['663366'],['330033']
		],
		elementsInRow: 7,
		callback: function(){
		return false;
		}
	},
	_create: function() {
		var self = this, o = this.options;
		this.wrapper = $('<div/>');
		for (id in o.colors) {
			var curColor = o.colors[id],
			$color = $("<div/>")
				.addClass("ui-colorpicker-item ui-widget-content ui-corner-all")
				.css('background', '#'+curColor)
				.attr('rel', curColor)
			$color.hover(function(){$(this).addClass('ui-wa-shadow-light')},
						 function(){$(this).removeClass('ui-wa-shadow-light')})
			$color.mousedown(o.callback)
			this.wrapper.append($color);
		}
		this.wrapper.append("<div style='clear:both'></div>");

		this.wrapper.width(o.elementsInRow * 18);
		this.element.append(this.wrapper);
	}
});
