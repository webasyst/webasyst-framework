/*
 * WBS UI Editor
 *
 * Copyright (c) 2010
 *
 * Depends:
 *	jquery.ui.core.js
 *	jquery.ui.widget.js
 */
$.widget("ui.waEditor", 	{
	options: {
		showToolbar: true,
		buttons: [
			[
				['bold','[`Bold`]'], 
				['italic','[`Italic`]'], 
				['underline','[`Underline`]'], 
				['strikethrough','[`Strikeout`]']
			],
			/*
			[
			 ['fontname','[`Font`]'], 
			 ['fontsize','[`Font size`]']
			], 
			[['undo'], ['redo']],*/
			[
				['forecolor','[`Font Color`]'], 
				['backcolor','[`Background Color`]']
			],
			[['left', '[`Left Justify`]'], ['center', '[`Center`]'], ['right', '[`Right Justify`]']],
			[['ol', '[`Numbered List`]'], ['ul', '[`Bullets`]']],
			/*[['indent'], ['outdent']],*/
			[['link', '[`Insert Link`]'], ['unlink', '[`Remove Link`]'], ['hr', '[`Horizontal Rule`]'] ]
			,
			[['removeFormat', '[`Remove Format`]']]/*,['showHTML']]*/
			,
			[['showHTML', '[`Show HTML`]']]
		],
		advancedButtons: [],
		useSimpleButtons:false,
		simpleBtns:[
	        [
	         ['bold','[`Bold`]'], 
	         ['italic','[`Italic`]'], 
	         ['underline','[`Underline`]']
	        ],
	        [
	         ['forecolor','[`Font color`]'], 
	         ['backcolor','[`Background color`]']
	        ],
	        [['left'], ['center'], ['right']],
	        [['ol'], ['ul']],
	        [['indent'], ['outdent']],
	        [['link'], ['unlink']]
	    ],
		fill: true,
		attachFiles: false,
		editable: true,
		withFrame: false,
		menuOffset: false,
		height: 300,
		name: ''
	},
	addButton: function(label, command, param, appendToToolbar) {
		var self = this, o = this.options;
		var button = $("<button/>"),
		menuOffset = {x:0,y:0};
		if (o.menuOffset) menuOffset = {x:0,y:25};
		switch(command){
			case 'fontname':
				var items = [
							  "Arial, Helvetica, sans-serif", 
							  "Arial Black, Gadget, sans-serif", 
							  "Courier New, Courier, monospace", 
							  "Garamond, serif", 
							  "Georgia, serif",
							  "Impact, Charcoal, sans-serif", 
							  "Lucida Sans Unicode, Lucida Grande, sans-serif", 
							  "MS Sans Serif, Geneva, sans-serif",
							  "Times New Roman, serif",
							  "Verdana, Geneva, sans-serif"
							], fonts = [];

				if ($.browser.opera) {
					items = [
							  "Arial", 
							  "Courier,monospace", 
							  "Garamond", 
							  "Impact", 
							  "Tahoma,sans-serif",
							  "Times,serif",
							  "Verdana"
							]
				}

				for (key in items){
					fonts.push({
						name: "<span style='font-family:"+items[key]+"; font-weight:normal'>"+items[key]+"</span>",
						callback: function(){
							var font = $('span',this).css('font-family');
							self.doc.execCommand('fontname', false, font) 
							self.textarea.val(self.editablefield.html());
						}
					})
				}
				button.data('onLoad', function($this) {
					$this.waMenu({
						items: fonts,
						width:270,
						offset: menuOffset
					});
				});	
			  break;
			case 'fontsize':
				var items = [ "xx-small",
							  "x-small",
							  "small",
							  "medium",
							  "large",
							  "x-large", 
							  "xx-large" ], 
					fonts = [];
				for (key in items){
					fonts.push({
						name: "<span style='font-size:"+items[key]+"; font-weight:normal'>"+items[key]+"</span>",
						callback: function(){
							var font = $('span',this).text();
							self.doc.execCommand('fontsize', false, parseInt($.wa.array_search(font, items))+1,10)
							self.textarea.val(self.editablefield.html());
						}
					})
				}
				button.data('onLoad', function($this) {
					$this.wrap('<div style="position: relative;float:left;"></div>');
					$this.waMenu({
						items: fonts,
						width:170,
						offset: menuOffset
					});
				});
			  break;

			case 'backcolor':
			case 'forecolor':
				button.data('onLoad', function($this) {
					$this.wrap('<div style="position: relative;float:left;"></div>');
					var $colorPicker = $("<div/>");
					$colorPicker.waColorpicker({
						callback: function(){
							if (command == 'backcolor' && !$.browser.msie){
								command = 'hilitecolor';
							}
							self.doc.execCommand(command, false, '#'+$(this).attr('rel'));
							$colorPicker.waPopup('close');
							self.textarea.val(self.editablefield.html());
							return false;
						}
					})
					$colorPicker.waPopup({
						padding:3,
				        parent: $this,
				        absolute: true
				    })
				});	
			  break;
			case 'link':
				button.data('onLoad', function($this) {
					$this.wrap('<div style="position: relative;float:left;"></div>');
					var $linkDiv = $("<div/>"), 
						$input = $("<input value='http://' class='disable-selection-on-focus' style='width:150px' />"),
						$btn = $("<button>OK</button>").click(function(){
							self.doc.execCommand("createlink", false, $input.val());
							$linkDiv.waPopup('close');
							self.textarea.val(self.editablefield.html());
							return false;
						});
					$linkDiv.append('[`Enter link address:`]').append($input).append($btn);
					$linkDiv.waPopup({
						padding:3,
						width: 200,
				        parent: $this,
				        absolute: true
				    })
				});	
				button.data('clickEvent', function(button) {
					return false;
				})
				command = 'link';
			  break;
			case 'showHTML':
				button.data('clickEvent', function(button) {
					var visible = false;
					if (!self.options.withFrame){
						visible = self.editablefield.css('display')
					} else {
						visible = self.frame.css('display')
					}
					if (visible != 'none'){
						self.toolbar.find('button').button("disable");
						$(button).button("enable");
						self.showTextarea();
					} else {
						$('button',self.toolbar).button("enable");
						self.showEditableField();
					}
					return false;
				});	
			  break;
			case 'hr':
				command = 'inserthorizontalrule';
			break;
			case 'left':
				command = 'justifyleft';
			  break;
			case 'center':
				command = 'justifycenter';
			  break;
			case 'right':
				command = 'justifyright';
			  break;
			case 'ol':	
				button.data('clickEvent', function(button) {
					if ($.browser.webkit){
						var tempspan = '<span>&nbsp;</span>';
						self.editablefield.prepend(tempspan);
					}
					self.doc.execCommand("insertorderedlist", false, '');
					if ($.browser.webkit){
						self.editablefield.html((self.editablefield.html().replace(tempspan, "")));
					}
				});	
			  break;
			case 'ul':
				button.data('clickEvent', function(button) {
					if ($.browser.webkit){
						var tempspan = '<span>&nbsp;</span>';
						self.editablefield.prepend(tempspan);
					}
					self.doc.execCommand("insertunorderedlist", false, '');
					if ($.browser.webkit){
						self.editablefield.html((self.editablefield.html().replace(tempspan, "")));
					}
				});	
			  break;
			case 'img':		
				button.data('onLoad', function($this) {
					$this.wrap('<div style="position: relative;float:left;"></div>');
					var $linkDiv = $("<div/>"), 
						$template = $("<input type='file' name='files[]' />"),
						$input = $template.clone();
					$input.change(function(){
						
						var 
						$oldInput = $input.clone(),
						$deleteBtn = $("<button>X</button>").button().click(
							function(){
								$parent.remove();
								$linkDiv.waPopup('open');
							}
						),
						$parent=$("<div/>").append($oldInput).append($deleteBtn);
						
						$linkDiv.prepend($parent);
						$input.val('');
					})
					$linkDiv.append($input);
					$linkDiv.waPopup({
						padding:3,
						width: 200,
				        parent: $this,
				        absolute: true,
						offset: menuOffset
				    })
				});	
				button.data('clickEvent', function(button) {
					return false;
				})
				//param = 'http://www.google.ru/images/nav_logo.png';
				command = 'insertimage';
			  break;
			case 'vars':
				var items = o.vars;
				var vars = [];
				for (key in items) {
					vars.push({
						name: items[key][0] + " " + items[key][1],
						value: items[key][0],
						callback: function(){
							var html = $(this).attr('rel');
							if ($.browser.msie){	
								var rangeRef = document.selection.createRange();
								rangeRef.pasteHTML(html);
							} else {
								self.doc.execCommand("InsertHTML", false, html);
							}
						}
					})
				}
				button.data('onLoad', function($this) {
					$this.waMenu({
						items: vars,
						width: 250,
						offset: menuOffset
					});
				});
			  break;
			case 'hello':	
				param = "<b>Hello, world!</b>";
				button.data('clickEvent', function(button) {
					if ($.browser.msie){	
						var rangeRef = document.selection.createRange();
						rangeRef.pasteHTML('<b>Hello world!</b>');
					} else {
						self.doc.execCommand("InsertHTML", false, $(button).data('param'));
					}
				});	
			  break;
			default:
			  break;
		}
		button.append(label);
		button.data('command', command);
		button.data('param', param);
		button.button({
			icons:{
				primary: 'ui-icon-editor-'+command
			},
			text: false
		});

		
		button.mousedown(function(event) {
			var $this = $(this);
			if ($.browser.msie) {
				self.editablefield.focus();
			}
			if (typeof($this.data('clickEvent'))=='function') {
				$this.data('clickEvent')($this);
			} else {
				var command = $this.data('command');
				var param = $this.data('param');
				self.doc.execCommand(command, false, param)
				self.textarea.val(self.editablefield.html());
            }
		});

		button.click(function(){return false});
		if (appendToToolbar) {
			this.cleardiv.remove();
			this.toolbar.append(button);
			this.toolbar.append(cleardiv);
			return button;
		}

		return button;
	},
	_create: function() {
		var self = this, o = this.options,
			previousHtml = this.element.html();
		if (o.useSimpleButtons) o.buttons = o.simpleBtns;
		
		if (o.advancedButtons) {
			o.buttons = o.buttons.concat(o.advancedButtons);
		}
		self.frame = $('<iframe border="0"></iframe>');
		self.doc = false;
		if (!o.withFrame) self.doc = document;
		this.element.empty()
		this.element.addClass("wa-resizable ui-editor ui-widget ui-widget-content ui-helper-clearfix ui-corner-all");
		this.toolbar  = $('<div/>')
			.addClass('ui-editor-toolbar ui-widget-header ui-corner-top');

		this.editablefield = $('<div/>')
			.addClass('ui-editor-editablefield');
		if (o.editable) {
			this.editablefield.attr("contenteditable", true);
			if (!o.withFrame) if ($.browser.mozilla) this.editablefield.append('<span></span><p><br/></p>');
		}

		this.textarea = $('<textarea></textarea>')
		.addClass('ui-editor-textarea')
		.css({
			position: 'relative',
			top: '-1px'
		})
		.hide();
		if (this.element.attr('name')) this.textarea.attr('name', this.element.attr('name'));
		if (o.name) this.textarea.attr('name', o.name);

		if (!o.withFrame){
			self.editablefield.blur(function(){
				self.setTextareaContent()
			})
		}
		for (var buttonsetIndex in o.buttons) {
			var buttonset = $("<div/>");
			for (var buttonIndex in o.buttons[buttonsetIndex]) {
				var buttonData = o.buttons[buttonsetIndex][buttonIndex];
				var command = buttonData[0];
				if (buttonData[1]) {
					var label = buttonData[1];
				} else {
					var label = command;
				}
				if (buttonData[2]) {
					var param = buttonData[2];
				} else {
					var param = '';
				}
				var button = this.addButton(label, command, param);
				buttonset.append(button);
				button.focus(function(e){
					e.preventDefault();
					self.frame.focus();
				})
			}
			buttonset.buttonset();
			this.toolbar.append(buttonset);
		}
		this.cleardiv = $('<div style="clear: both"/>');
		this.toolbar.append(this.cleardiv);
		
		this.element.parent().append(this.textarea);
		if (o.showToolbar){ 
			this.element.append(this.toolbar);
			$('button', this.toolbar).each(function(){
					var button = $(this);
					if (typeof(button.data('onLoad'))=='function') {
						button.data('onLoad')(button);
					}
			})
		}
		if (o.withFrame){
			this.element.append(self.frame);
			self.frame.width('100%');
			if ($.browser.mozilla){
				var timeout = 1000;
			} else {
				var timeout = 200;
			}
	        setTimeout( function() {
					self.doc = self.frame[0].contentWindow.document;
		            self.doc.designMode = 'On';
		            var $body = $('body',self.doc);
		            $body.css({
		            	'margin': 0,
		            	'padding':'10px',
		            	'overflow':'auto',
						'background':'white'
		            });
		            var thtml = self.editablefield.html();
		            $body.attr('class', self.editablefield.attr('class'));
		            self.editablefield = $body;
		            if (thtml!='') {
			            self.setContent(thtml);
		            } else {
			            self.setContent(previousHtml);
		            }
					$('body').click(function(e){
						if (!self.textarea.is(":visible")){
							self.setTextareaContent()
						}
					})
					$('a',$body).live('click',function(){
		    			self.showLinkEditDiv($(this))
					})
					self.frame.focus();
					self.frame.height(o.height);
	        }, timeout ); 
		} else {
			this.element.append(this.editablefield);
            self.setContent(previousHtml);
    		$('.ui-editor-editablefield a',this.element).live('click',function(){
    			self.showLinkEditDiv($(this))
    		})
		}
		if (o.attachFiles){
			var 
			$attach_wrapper = $("<div class='ui-editor-attachments'/>")
			$attach_link = $('<a href="javascript:void(0)" id="attach_link" class="attach_link">[`Attach file`]</a>'),
			$attach_div = $("<div id='attach_div' class='attach_div'/>"),
			$input = $("<input type='file' name='files[]' />");

			$attach_wrapper.append($attach_link);
			this.element.append($attach_wrapper)
			$attach_link.after($attach_div.append($input));
			$input.css({'height': '1.4em','opacity':0, 'position':'relative', 'left':'-105px'});
			$attach_div.css({
				'opacity':0,
				'position':'relative',
				'top': '-1.3em',
				'width': 100,
				'height':  '1.4em',
				'overflow': 'hidden'
			})
			$attach_div.hover(function(){
				$attach_link.css('text-decoration','underline')
			},function(){
				$attach_link.css('text-decoration','none')
			})
			$input.change(function(){
				var 
				$attach_div = self.element.find('#attach_div'),
				$input = $attach_div.find('input'),
				//$oldInput = $input.clone().val($input.val()),
				$deleteBtn = $("<button>[`Delete file`]</button>").button({text: false, icons: {
	                primary: 'ui-icon-circle-close'
	            }}).click(
					function(){
						$parent.remove();
					}
				),
				$parent = $("<div class='attached-file'/>").append($input).append("<span>"+$input.val()+"</span>").append($deleteBtn);
				$input.hide();
				self.element.find('#attach_link').before($parent);
				$attach_div.append($input.clone(true).val('').show());
			})
		}
		this.element.append(this.textarea);
		this.resize();
	},
	focus: function(){
		this.frame.focus();
	},
	showLinkEditDiv: function($link){
		var self = this, o = this.options;
		var $linkDiv = $("<div/>"), 
			$input = $("<input value='"+$link.attr('href')+"' class='disable-selection-on-focus' style='width:250px' />"),
			$btn = $("<button>OK</button>").button().click(function(){
				$link.attr('href', $input.val());
				$linkDiv.waPopup('close');
				self.textarea.val(self.editablefield.html());
				return false;
			});
		$linkDiv.append('[`Edit link`]').append($input).append($btn);
		$linkDiv.waPopup({
			padding:3,
			width: 300,
	        parent: self.toolbar,
	        offset: {x: $link.offset().left, y: $link.offset().top+$link.height()},
	        parentShadow:false,
	        parentCorners:false,
	        toggledByParent:false,
	        absolute: true,
	        close: function(){
	        	$linkDiv.remove();
	        }
	    })
	    $linkDiv.waPopup('open');
	},
	mozillaHacks: function(data){
		var self = this, o = this.options;
		if (!o.withFrame){
			var search = data.search('<span></span><p>');
			if ($.browser.mozilla && search != 0) {
				if (data.length > 0){
					data = '<span></span><p>'+data+'</p>';
				} else {
					data = '<span></span><p><br/></p>';
				}
			}
		}
		return data;
	},
	loadContent: function(params){
		var self = this, o = this.options;
		//self.element.hide();
		params.uid = new Date().getTime();
		self.setContent('<div class="request-loading"><img src="img/ajax-loader32.gif"/><span>[`Loading`]&hellip;</span></div>');
		if (params.ajax){
			$.wa.currentUrl = params.url;
			if (!$.wa.get('load-uid')){
				$.wa.set('load-uid',params.uid);
				$.get(params.url, function(data) {
					if ($.wa.get('load-uid') == params.uid){
						self.element.show();
						//data = self.mozillaHacks(data);
						if ( $(".grid-tbody tr").length ){
							self.setContent(data);
						}
						self.resize();
						$.wa.set('load-uid',0);
				        if (typeof(params.callback) == 'function') params.callback();
					}
				})
			}
		}
	},
	setContent: function(data){
		var self = this, o = this.options;
		//data = self.mozillaHacks(data);
		if ($.browser.msie){
			if (typeof(self.frame[0].contentWindow) != "unknown"){
				setTimeout(function(){$(self.frame[0].contentWindow.document.body).html(data)},100);
			} else {
				self.editablefield.html(data);
			}
		} else {
			self.editablefield.html(data);
		}

		self.textarea.val(data);
	},
	getContent: function(){
		this.setTextareaContent();
		return this.textarea.val();
	},
	setEditableFieldContent: function(){
		var self = this;
		if (this.options.withFrame){
			self.frame[0].contentWindow.document.body.innerHTML = this.textarea.val();
		} else {
			this.editablefield.html(this.textarea.val());
		}
	},
	setTextareaContent: function(){
		var self = this;
		if (this.options.withFrame){
			if (self.frame[0].contentWindow != null)
			this.textarea.val(self.frame[0].contentWindow.document.body.innerHTML);
		} else {
			this.textarea.val(this.editablefield.html());
		}
		//this.textarea.val(this.editablefield.html());
	},
	showTextarea: function(){
		var self = this, o = this.options;
		this.setTextareaContent();
		//this.textarea.val(this.editablefield.html());
		if (!o.withFrame){
			this.editablefield.hide();
		} else {
			self.frame.hide();
		}
		this.textarea.show();

		//this.textarea.width(this.toolbar.width());
		if (!o.withFrame) {
			this.textarea.height(this.editablefield.height());
		} else {
			this.textarea.height(self.frame.height());
		}
		this.textarea.css("width","99%");
	},
	showEditableField: function(){
		var self = this, o = this.options;
			this.textarea.hide();
			if (!this.options.withFrame){
				this.editablefield.show();
			} else {
				self.frame.show();
			}
			this.setEditableFieldContent()
			//this.editablefield.html(this.textarea.val());
	},
	toggleToolbar: function(param){
		var self = this, o = this.options;
		if ($('.ui-editor-toolbar', this.element).length == 0){
			this.options.showToolbar = true;
			this.element.prepend(this.toolbar);
		} else {
			this.options.showToolbar = false;
			this.toolbar.detach();
		}
	},
	resize: function() {
		var self = this, o = this.options;
		if (o.fill) {
			var tH = 0;
			if (o.showToolbar) tH = this.toolbar.height();
			if (this.element.attr('id') == 'textarea') {
				tH = 40;
					$('.ticket-toolbar-top').css('top', this.element.offset().top+tH)
				if ($('#leftPanel').is(':visible')) {
					$('.ticket-toolbar-top .backlink').css('margin-left', $('#leftPanel').width())
				} else {
					$('.ticket-toolbar-top .backlink').css('margin-left', 0)
				}
			}
			
			this.editablefield.height(this.element.parent().height() - tH);

			this.textarea.height(this.editablefield.height());
			/*if ($('.ticket-sidebar', this.editablefield).length)
				$('.ticket-sidebar').height($(window).height() - $('.ticket-sidebar').offset().top)*/
		} else {
			this.editablefield.height(o.height);
			this.textarea.height(o.height);
		}
	    //$('#ticket').height($('.ui-editor-editablefield').height() - $(".ticket-toolbar").outerHeight() - $(".ticket-tabs").outerHeight());
	},
	destroy: function() {
		this.textarea.remove();
		this.editablefield.remove();
		//this.toolbar.remove();
		
		this.element
			.removeClass("ui-editor"
				+ " ui-widget-content"
				+ " ui-corner-all")
			.removeData("editor");

		return this;
	},
	empty: function() {
		this.textarea.empty();
		this.editablefield.empty();
	}
});
