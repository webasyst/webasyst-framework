(function ($) {
$.storage = new $.store();

$.wa.errorHandler = function (xhr) {
	$.storage.del('site/' + $.wa.site.domain + '/hash');
	if (xhr.status == 404) {
		$.wa.setHash('#/');
		return false;
	}
	return true;
};

$.wa.site = {
	options: [],
	domain: 0,
	helper: '',
	init: function (options) {
		if (typeof($.History) != "undefined") {
			$.History.bind(function () {
				$.wa.site.dispatch();
			});
		}
		this.domain = options.domain;
		this.options = options;
		var hash = window.location.hash;
		if (hash === '#/' || !hash) {
			hash = $.storage.get('site/' + this.domain + '/hash');
			if (hash && hash != null) {
				$.wa.setHash('#/' + hash);
			} else {
				this.dispatch();
			}
		} else {
			$.wa.setHash(hash);
		}
	},
	
	setHelper: function (helper) {
		if (helper === true) {
			return false;
		}
		if (helper) {
			this.helper = helper;
			$("#s-save-panel div.s-dropdown").show();
		} else {
			this.helper = '';
			$("#s-save-panel div.s-dropdown").hide();
		}
	},
	
	dispatch: function (hash) {
		if (hash == undefined) {
			hash = window.location.hash;
		}
		hash = hash.replace(/^[^#]*#\/*/, ''); /* fix sintax highlight*/
		if (hash) {
			hash = hash.split('/');
			if (hash[0]) {
				var actionName = "";
				var attrMarker = hash.length;
				for (var i = 0; i < hash.length; i++) {
					var h = hash[i];
					if (i < 2) {
						if (i === 0) {
							actionName = h;
						} else if ((actionName == 'files')) {
							attrMarker = i;
							break;							
						} else if (parseInt(h, 10) != h && h.indexOf('=') == -1) {
							actionName += h.substr(0,1).toUpperCase() + h.substr(1);
						} else {
							attrMarker = i;
							break;
						}
					} else {
						attrMarker = i;
						break;
					}
				}
				var attr = hash.slice(attrMarker);

				if (this[actionName + 'Action']) {
					this[actionName + 'Action'].apply(this, attr);
					// save last page to return to by default later
					$.storage.set('site/' + this.domain + '/hash', hash.join('/'));					
				} else {
					if (console) {
						console.log('Invalid action name:', actionName+'Action');
					}
				}
			} else {
				this.defaultAction();
			}
		} else {
			this.defaultAction();
		}			
	},
			
	defaultAction: function () {
		var hash = $("div.s-sidebar ul.s-links a:first").attr('href');
		$.wa.setHash(hash);
	},
	
	pagesAction: function (params) {
		var p = this.parseParams(params);
		$("#s-content").load('?module=pages', params + '&domain_id=' + this.domain, function () {
			// set active link in sidebar
			$.wa.site.active($("#page-" + p.id));
			$("#s-save-panel .s-bottom-fixed-bar-content-offset").addClass('s-page-editor');			
			
			// init elrte editor
			elRTE.prototype.beforeSave = function () {};
			elRTE.prototype.options.toolbars.siteToolbar = ['wa_style', 'alignment', 'colors', 'format', 'indent', 'lists', 'wa_image', 'wa_links', 'wa_elements', 'wa_tables', 'direction'];
		  	var h = $("div.s-editor.s-white").height() - $("div.s-editor.s-white .s-grey-toolbar").height() - 120;
		  	if ($("div.s-scrollable-part").height() > $("div.s-scrollable-content").height()) {
		  		h += $("div.s-scrollable-part").height() - $("div.s-scrollable-content").height() - 5;
		  	} 
		  	if (h < 300) {
		  		h = 300;
		  	}							
			$("#content").elrte({
				height: h,
				cssfiles: [wa_url + "wa-content/css/wa/wa-1.0.css"],
				toolbar: 'siteToolbar',
				lang: wa_lang,
				wa_image_upload: '?module=files&action=uploadimage',
				width: "100%"
			});				
			var f = $("#content").elrte()[0].elrte.filter.source; 
			$("#content").elrte()[0].elrte.filter.source = function (html) {
				var html = f.call($("#content").elrte()[0].elrte.filter, html);
				html = html.replace(/%7B\$wa_url%7D/, '{$wa_url}');
				html = html.replace(/{[a-z$][^}]*}/gi, function (match, offset, full) {
					var i =	full.indexOf('</script', offset + match.length);
					var j = full.indexOf('<script', offset + match.length);
					if (i == -1 || (j != -1 && j < i)) {
						match = match.replace(/&gt;/g, '>');
						match = match.replace(/&lt;/g, '<');
						match = match.replace(/&amp;/g, '&');
						match = match.replace(/&quot;/g, '"');
					}
					return match;
				});
				return html;
			};
			$('.el-rte iframe').contents()
			.keydown($.wa.site.editorKeyCallback())
			.keypress($.wa.site.editorKeyCallback(true))
			.keyup(function(e) {
				//all dialogs should be closed when Escape is pressed
				if (e.keyCode == 27) {
					jQuery(".dialog:visible").trigger('esc');
				} 
			});
			$('.el-rte .toolbar li').click(function () {
				$('#s-editor-save-button').removeClass('green').addClass('yellow');
			});
			// bind click handlers to buttons
			$("#wysiwyg").click(function () {
				$.storage.set('site/editor', 'wysiwyg');
				$("ul.s-wysiwyg-html-toggle li.selected").removeClass('selected');
				$(this).parent().addClass('selected');
				$("div.CodeMirror-wrapping").hide();
				$("#content").elrte('val', document.editor.getCode());
				$('.el-rte iframe').contents().find('img[src*="$wa_url"]').each(function () {
					var s = decodeURIComponent($(this).attr('src'));
					$(this).attr('data-src', s);
					$(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
				});
				$(".el-rte").show();
				$('.el-rte iframe').contents().find('body').focus();
				return false;
			});		
			
			$("#html").click(function () {
				$.storage.set('site/editor', 'html');
				$("ul.s-wysiwyg-html-toggle li.selected").removeClass('selected');
				$(this).parent().addClass('selected');		
				$('.el-rte iframe').contents().find("img[data-src!='']").each(function () {
					$(this).attr('src', $(this).attr('data-src'));
				});
				document.editor.setCode($("#content").elrte('val'));
				$(".el-rte").hide();
				$("div.CodeMirror-wrapping").show();
				return false;
			});			

			// show active editor
			if ($.storage.get('site/editor') == 'wysiwyg') {
				$("ul.s-wysiwyg-html-toggle li.selected").removeClass('selected');
				$("#wysiwyg").parent().addClass('selected');
				$('.el-rte iframe').contents().find('img[src*="$wa_url"]').each(function () {
					var s = decodeURIComponent($(this).attr('src'));
					$(this).attr('data-src', s);
					$(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
				});
				$("div.CodeMirror-wrapping").hide();
			} else {
				$(".el-rte").hide();
			}			
			
			// other
		   $("div.s-page-app-url input[type=radio]").click(function () {
			   $("div.s-page-app-url.bold").removeClass('bold');
			   $("div.s-page-app-url input[type=text]").attr('disabled', 'disabled');
			   $(this).parents('div.s-page-app-url').addClass('bold').find('input[type=text]').removeAttr('disabled').focus();
		   });
		
		    $("div.s-page-app-url input[type=text]").keyup(function () {
		    	$("div.s-page-app-url input[type=text]").not(this).val($(this).val());	
		    });
		    
		    var iButtonInit = function () {
                $("#s-page-v").iButton({
                    labelOn: "", 
                    labelOff: "",
                    classContainer: 'ibutton-container mini'
                });
            };
			if ($("#s-page-settings").is(":visible")) {
				setTimeout(iButtonInit, 200);
			} else {
				$("#s-page-settings-toggle").one('click', function () {
					setTimeout(iButtonInit, 100);
				});
			}
            var status_check = function(item){
                if ($(item).is(':checked')) {
                    $('#s-page-v-open-label').addClass('s-gray');
                    $('#s-page-v-private-label').removeClass('s-gray');
                }
                else {
                    $('#s-page-v-open-label').removeClass('s-gray');
                    $('#s-page-v-private-label').addClass('s-gray');
                }
            };
            status_check($('#s-page-v'));
            $('#s-page-v').change(function(){
            	$('#s-editor-save-button').removeClass('green').addClass('yellow');
                status_check(this);
            });
		});
	},
	
	removePageCallback: function (page_id) {
		return function (response) {
			var li = $("#page-" + page_id);
			if (li.prev().length > 0) {
				var hash = li.prev().addClass('selected').find('a').attr('hash');
			} else if (li.next().length > 0) {
				var hash = li.next().addClass('selected').find('a').attr('hash');
			} else {
				var hash = "/pages/add/";
			}
			$.wa.setHash(hash);
			li.remove();
		};		
	},
	
	pagesAddAction: function () {
		this.pagesAction('id=');
	},
	
	filesAction: function (load, path) {
		this.savePanel(false);
		if (load === true) {
			var params = path || this.filesPath();
		} else {
			var params = Array.prototype.join.call(arguments, '/');
			load = false;
		}
		//s-files-tree
		var loadFiles =  function () {
			$.wa.site.active($("#s-link-files"));
			$("#s-files-tree li.selected").removeClass('selected');
			if (!params) {
				$("#s-folder-actions-li").hide();
				$("a.s-baseurl").addClass('selected');
			} else {
				$("a.s-baseurl").removeClass('selected');
				$("#s-folder-actions-li").show();
				var a = $("#s-files-tree a[href='#/files/" + params + "']");
				a.parent().addClass('selected');
				var p = a.parent();
				while (p.length) {
					var i = p.find('> i.overhanging');
					if (i.hasClass('rarr')) {
						i.click();
					}
					p = p.parent('ul').parent('li');
				}				
			}
			
			$.wa.site.filesList(params);
			$("#s-upload-path").val(params || '');
			$("#s-current-path").html('/' + (params || ''));
			$("#s-files-count").html('0');
			$("#s-files-grid input.all").removeAttr('checked');
		};
		if ($("#s-files-tree").length && !load) {
			loadFiles();
		} else {
			$("#s-content").load('?module=files', 'domain_id=' + this.domain, function () {
				$("#s-files-tree i.overhanging").click(function () {
					var i = $(this);
					if (i.hasClass('rarr')) {
						i.removeClass('rarr').addClass('darr').parent().children('ul').show();
					} else {
						i.removeClass('darr').addClass('rarr').parent().children('ul').hide();
					}
				});		
				if (load === true && path) {
					$.wa.setHash('#/files/' + path);
				} else {
					loadFiles();
				}
			});
		}
	},
	
	filesList: function (path) {
		if (!path) {
			path = this.filesPath();
		}
		$.post("?module=files&action=list", {path: path}, function (response) {
			$("#s-files-grid tr.s-file").remove();
			for (var i = 0; i < response.data.length; i++) {
				var html = '<tr class="s-file"><td class="min-width"><input type="checkbox" value="' + response.data[i].file + '" /></td>' + 
				'<td><ul class="menu-h dropdown clickable"><li>' + 
				'<a href="#"><i class="icon16 ' + response.data[i].type + '"></i> ' + 
					response.data[i].file + ' <i class="icon10 darr no-overhanging s-file-actions"></i></a>' +
				'</li></ul></td>' + 
				'<td>' + response.data[i].datetime + '</td>' + 
				'<td><span class="float-right">' + $.wa.site.getFileSize(response.data[i].size) + '</span></td></tr>';
				$("#s-files-grid").append(html);
			}
		}, "json");		
	},
	
	getFileSize: function (size) {
		if (size < 1024) {
			return size + ' B';
		} else if (size < 1024 * 1024) {
			return Math.round(size/1024) + ' KB';
		} else if (size < 1024 * 1024 * 1024) {
			return Math.round(size/(1024 * 1024)) + ' MB';
		} else {
			return Math.round(size/(1024 * 1024 * 1024)) + ' GB';
		}
	},
	
	checkFileType: function (type) {
		return type == 'image' || type == 'text' || type == 'script-css' || type == 'script-js';
	},
	
	getFileMenu: function (file) {
		var url = $("#s-domain").attr('href') + 'wa-data/public/site/' + $.wa.site.filesPath() + file;
		var menu = $('<ul class="menu-v width-icons" style="display:block"></ul>');
		if (file.substr(-4) != '.php') {
			menu.append('<li>' +
							'<i class="icon16 globe"></i>' + $_('File URL') + ': ' +
							'<a href="' + url + '" target="_blank" class="bold">' + url + '<i class="icon10 new-window"></i></a>' +
						'</li>' +
						'<li><a href="?module=files&action=download&path=' + $.wa.site.filesPath() + '&file=' + file + '"><i class="icon16 download"></i>' + $_('Download') + '</a></li>'
			);
		}
		menu.append($('<li></li>').append('<a href="#"><i class="icon16 edit"></i>' + $_('Rename') + '</a>').click(function () {
			$("#s-rename-dialog").waDialog({ 
				disableButtonsOnSubmit: true,
				onLoad: function () {
					$("#s-name").val(file).focus().select();
					$(this).find('span').html($.wa.site.filesPath(true));
				},
				onSubmit: function () {
					var name = $("#s-name").val();
					$.post('?module=files&action=rename', { path: $.wa.site.filesPath(), name: name, file: file}, function (response) {
						if (response.status == 'ok') {
							$.wa.site.filesList();
							$("#s-rename-dialog").hide();
						} else if (response.status == 'fail') {
							alert(response.errors);
							$("#s-rename-dialog input[type=submit]").removeAttr('disabled');
						}
					}, "json");
					return false;
				}
			});
			return false;
		}));
		menu.append($('<li></li>').append('<a href="#"><i class="icon16 move"></i>' + $_('Move to folder') + '</a>').click(function () {
			$("#s-move-dialog select").html($.wa.site.filesPathOptions($("#s-files-tree > ul.s-folderlist"), ''));
			$("#s-move-dialog-files").html('<input type="hidden" name="file" value="' + file + '" />');
			$("#s-move-dialog input[name=path]").val($.wa.site.filesPath());
			$("#s-move-dialog h1 span").empty();
			$("#s-move-dialog").waDialog({ 
				disableButtonsOnSubmit: true,
				onSubmit: function () {
					$.post('?module=files&action=move', $("#s-move-dialog form").serialize() , function (response) {
						if (response.status == 'ok') {
							$("#s-move-dialog").hide();
							$.wa.site.filesList();
						} else if (response.status == 'fail') {
							alert(response.errors);
							$("#s-move-dialog input[type=submit]").removeAttr('disabled');
						}
					}, "json");
					return false;
				}
			});			
			return false;
		}));
		menu.append($('<li></li>').append('<a href="#"><i class="icon16 delete"></i>' + $_('Delete') + '</a>').click(function () {
			$("#s-delete-dialog").waDialog({
				content: '<h1>' + $_('Delete file') + '</h1><p>' + $_('File') + ' <b>' + file + '</b> ' + $_('will be deleted without the ability to recover.') + '</p>',
				disableButtonsOnSubmit: true,
				onSubmit: function () {
					$.post('?module=files&action=delete', {path: $.wa.site.filesPath(), file: file}, function (response) {
						if (response.status == 'ok') {
							$.wa.site.filesList();
							$("#s-delete-dialog").hide();
						} else if (response.status == 'fail') {
							alert(response.errors);
							$("#s-delete-dialog input[type=submit]").removeAttr('disabled');
						}
					}, "json");
				}
			});
			return false;
		}));
		return menu;
	},
	
	settingsAction: function (tab) {
		this.savePanel(false);
		if ($("#s-settings-tabs").length) {
			if (tab) {
				var a = $("#s-settings-tabs a[href='#" + tab + "']");
			} else {
				var a = $("#s-settings-tabs a:last");
			}
			if (!a.parent().hasClass('selected')) {
				a.click();
			}			
			$.wa.site.active(tab != 'routing' ? $("a#s-settings-link") : false);
		} else {
			$("#s-content").load('?module=settings&domain_id=' + this.domain, function () {
				if (tab) {
					$("#s-settings-tabs a[href='#" + tab + "']").click();
				} else {
					$("#s-settings-tabs a:last").click();
				}
				$.wa.site.active(tab != 'routing' ? $("a#s-settings-link") : false);
			});
		}
	},
	
	settingsRoutingAction: function () {
		this.settingsAction('routing');
	},
	
	snippetsAction: function (params) {
		$("#s-content").load('?module=snippets', params, function () {
			$.wa.site.initEditor('content', 'app=');
			$.wa.site.active($("#s-link-snippets"));
		});
	},
	
	snippetsAddAction: function () {
		this.snippetsAction('id=');
	},
	
	themesAction: function (params) {
		this.savePanel(false);
		$("#s-content").load('?module=themes',params, function () {
			$.wa.site.active($("#s-link-themes"));
		});
	},
	
	designAction: function (params) {
		var p = this.parseParams(params);
		$("#s-content").load('?module=design', params + '&domain_id=' + this.domain, function () {
			$.wa.site.initEditor('content', true);
			$.wa.site.active($("#design-" + p.route));
			$("ul.s-theme li.selected").removeClass('selected');
			if (p['file'] === '') {
				$("#theme-" + p['theme'] + " li.add-file").addClass('selected');
			} else if (p['file']) {
				$("#theme-" + p['theme'] + " li[data-file='" + p['file'] + "']").addClass('selected');
			} else if (p['theme']) {
				$("#theme-" + p['theme'] + " li:first").addClass('selected');
			} else {
				if ($("ul.s-theme:first li[data-file='index.html']").length > 0) {
					$("ul.s-theme:first li[data-file='index.html']").addClass('selected');
				} else {
					$("ul.s-theme:first li:first").addClass('selected');
				}
			}
		});		
	},
	
	active: function (el) {
		$(".sidebar a.selected").removeClass('selected');
		$("ul.s-links li.selected").removeClass('selected');
		if (el && el.length) {
			el.addClass('selected');
		}
	},
	
	routingAction: function (params) {
		this.savePanel(false);
		$("#s-content").load('?module=routing', params + '&domain_id=' + this.domain, function () {
			
		});
	},
	
	parseParams: function (params) {
		if (!params) return {};
		var p = params.split('&');
		var result = {};
		for (i = 0; i < p.length; i++) {
			var t = p[i].split('=');
			result[t[0]] = t.length > 1 ? t[1] : '';
		}
		return result;
	},
	
	designAddAction: function (params) {
		this.designAction(params + '&file=');
	},
	
	initEditor: function (id, helper) {
		if (!$("#" + id).length) {
			return false;
		}
		var t = $("#" + id).attr('data-type');
  		this.savePanel(true);
  		var h = $("div.s-editor.s-white").height() - $("div.s-editor.s-white .s-grey-toolbar").height() - 50;
  		if (h < 300) {
  			h = 300;
  		}
  		
  		document.editor = CodeMirror.fromTextArea(id, {
  			minHeight: h, 
  		    height: "dynamic",
  		    parserfile: t == 'css' ? 'parsecss.js' : 
  		    			t == 'js' ? ["tokenizejavascript.js", "parsejavascript.js"] : 
  		    			["parsexml.js", "parsecss.js", "tokenizejavascript.js", "parsejavascript.js", "parsehtmlmixed.js"],
  		    stylesheet: t == 'css' ? 
  		    			this.options['wa_url'] + "wa-content/js/codemirror/1/css/csscolors.css" :
  		    			t == 'js' ? this.options['wa_url'] + "wa-content/js/codemirror/1/css/jscolors.css" :
  		    			[this.options['wa_url'] + "wa-content/js/codemirror/1/css/xmlcolors.css", 
  		    			 this.options['wa_url'] + "wa-content/js/codemirror/1/css/jscolors.css", 
  		    			 this.options['wa_url'] + "wa-content/js/codemirror/1/css/csscolors.css"],
  		    path: this.options['wa_url'] + "wa-content/js/codemirror/1/js/",
  		    initCallback: function (editor) {
                editor.frame.contentWindow.document.addEventListener('keydown', $.wa.site.editorKeyCallback(), false);
                editor.frame.contentWindow.document.addEventListener('keypress', $.wa.site.editorKeyCallback(true), false);
            }
  		}); 	
  		
  		this.setHelper(helper || false);
	},
	editor_key: false,
	editorKeyCallback: function (press) {
		if (press) {
			return function (e) {
				if (!$('#s-editor-save-button').length) {
					return;
				}
		    	if (e.ctrlKey && e.which == 115 && !$.wa.site.editor_key) {
	    			$('#s-editor-save-button').click();
	    			e.preventDefault();
		    	}
			}			
		} else {
			return function (e) {
				$.wa.site.editor_key = false;
				if (!$('#s-editor-save-button').length) {
					return;
				}				
		    	if (e.ctrlKey && e.which == 83) {
	    			$.wa.site.editor_key = true;
	    			$('#s-editor-save-button').click();
	    			e.preventDefault();
		    	}
		    	if (e.metaKey) {
		    		return;
		    	}
		    	if ((e.which < 33 || e.which > 40) && 
		    		(e.which > 27 || e.which == 8 || e.which == 13) && 
		    		(e.which < 112 || e.which > 124) && 
		    		(!e.ctrlKey || e.which != 67)
		    		) {
		    		$('#s-editor-save-button').removeClass('green').addClass('yellow');
		    	}
			}
		}
	},
	
	savePanel: function (show) {
		if (show) {
			$("#s-save-panel").show();
			$("#s-save-panel .s-bottom-fixed-bar-content-offset").removeClass('s-page-editor');
			$("#wa div.s-scrollable-part").removeClass('s-no-editor');
			$('#s-editor-save-button').removeClass('yellow').addClass('green');
			$("#process-message").empty();
		} else {
			$("#s-save-panel").hide();
			$("#wa div.s-scrollable-part").addClass('s-no-editor');
		}
	},
	
	getTreeHTML: function (data, cl, hash) {
		var hash = hash || '';
		var html = '<ul' + (cl ? '' : ' style="display:none"') + ' class="menu-v with-icons' + (cl ? ' ' + cl : '') + '">';
		var id = '';
		for (var i = 0; i < data.length; i++) {
			id = typeof(data[i]) == 'string' ? data[i] : data[i]['id']; 
			html += '<li>';
			if (typeof(data[i]) != 'string') {
				html += '<i class="icon16 rarr overhanging"></i>';
			}
			html += '<a href="#/files/' + hash + id + '/"><i class="icon16 folder"></i><b>' + id + '</b></a>';			
			if (typeof(data[i]) != 'string') {
				html +=  this.getTreeHTML(data[i]['childs'], false, hash + id + '/');
			}
			html += '</li>';
		}
		html += '</ul>';
		return html;
	},
	
	filesPath: function (full) {
		var prefix = full ? 'wa-data/public/site/' : '';
		if ($("#s-files-tree li.selected").length) {
			return prefix + $("#s-files-tree li.selected a").attr('href').substr(8);
		}
		return prefix;
	},
		
	filesPathOptions: function (el, prefix, is_folder) {
		var prefix = prefix || '';
		var result = '';
		if (prefix == '') {
			result = '<option value="">wa-data/public/site</a>';
			prefix = '&nbsp;&nbsp;&nbsp;'
		}
		var is_folder = is_folder || false;
		el.children('li').each(function () {
			if ((is_folder && $(this).find('> ul > li.selected').length) || 
				(!is_folder && $(this).hasClass('selected'))) {
				var selected = true;
			} else {
				var selected = false;
			}

			var a = $(this).children('a');
			result += '<option ' + (selected ? 'selected="selected"' : '') + ' value="' + a.attr('href').substr(8)  + '">' + prefix + a.children('b').html() + '</option>';
			if ($(this).children('ul').length && (!is_folder || !$(this).hasClass('selected'))) {
				result += $.wa.site.filesPathOptions($(this).children('ul'), prefix + '&nbsp;&nbsp;&nbsp;', is_folder);
			}
		});
		return result;
	}
};
})(jQuery);

$(function () {
	$(".s-add-new-site").live('click', function () {
		$("#addsite-dialog").waDialog({
			onSubmit: function () {
				var f = $(this);
				$.post(f.attr('action'), f.serialize(), function (response) {
					if (response.status == 'ok') {
						location.href = '?domain_id=' + response.data.id + '#/settings/';
					}
				}, "json");
				return false;
			}
		});
		return false;
	});
	
	$("div.s-sidebar h5.heading").click(function () {
		var h = $(this);
		if (h.find('i').hasClass('darr')) {
			h.find('i').removeClass('darr').addClass('rarr');
			h.next('ul').hide();
			$.storage.set('site/' + $.wa.site.domain + '/' + h.attr('data-hash'), 1);
		} else {
			h.find('i').removeClass('rarr').addClass('darr');
			h.next('ul').show();
			$.storage.del('site/' + $.wa.site.domain + '/' + h.attr('data-hash'));
		}
	});
	
	$("div.s-sidebar h5.heading").each(function () {
		if ($.storage.get('site/' + $.wa.site.domain + '/' + $(this).attr('data-hash'))) {
			$(this).click();
		}
	});
	
	$("#s-pages").sortable({
		distance: 5,
		helper: 'clone',	
		items: 'li',
		opacity: 0.75,
		tolerance: 'pointer',
		stop: function (event, ui) {
			var li = $(ui.item);
			var id = li.attr('id').replace(/page-/, '');
			var pos = li.prevAll('li').size() + 1;
			$.post("?module=pages&action=sort&domain_id=" + $.wa.site.domain, { id: id, pos: pos}, function () {
			}, "json");
		}
	});	

	$('#s-page-settings-toggle').live('click', function(){
		$('#s-page-settings').toggle();
		return false;
	});	
	
	$("#s-editor-save-button").click(function () {
		$("#site-form").submit();
	});
	
	$("#s-domains-menu h1").click(function (e) {
		var menu = $('#s-domains-menu ul.menu-v');
		if (menu.is(":visible")) {
			menu.hide();
			return false;
		}
		menu.show();
		$(document).one('click', function (e) {
			menu.hide();
		});
		return false;
	});	
	
	$("#s-helper-link").click(function () {
		if ($("#s-helper").is(":visible")) {
			$("#s-helper").hide();
			return false;
		}
		$("#s-helper").load('?module=helper', $.wa.site.helper, function () {
			$(this).show();
			var f = function (e) {
				if ($(e.target).attr('id') == 's-helper' || $(e.target).parents('#s-helper').length) {
					$(document).one('click', f);
				} else {
					$("#s-helper").hide();
				}
			};
			$(document).one('click', f);
		});
		return false;
	});
	$("#s-helper div.fields a.inline-link").live('click', function () {
		var el = $(this).find('i');
		if (el.children('b').length) {
			el = el.children('b');
		}
		if ($(".el-rte").length && $(".el-rte").is(':visible')) {
			try {
				$("#content").elrte()[0].elrte.selection.insertHtml(el.text());
			} catch (e) {}
		} else {
			document.editor.replaceSelection(el.text());
		}
		return false;
	});
	
	$("#wa-app > div.s-sidebar a, #wa-header a, #s-content > div.sidebar a").live('click', function () {
		if ($("#s-save-panel").is(":visible") && $('#s-editor-save-button').hasClass('yellow')) {
			return confirm($_("Unsaved changes will be lost if you leave this page now. Are you sure?"));
		}
	});	
	
	$("#site-form").live('submit', function () {		
		if ($(".el-rte").length && $(".el-rte").is(':visible')) {
			$('.el-rte iframe').contents().find("img[data-src!='']").each(function () {
				$(this).attr('src', $(this).attr('data-src'));
			});
			$("#content").val($("#content").elrte('val'));
			$("#content").elrte('val', $("#content").val());
			$('.el-rte iframe').contents().find('img[src*="$wa_url"]').each(function () {
				var s = decodeURIComponent($(this).attr('src'));
				$(this).attr('data-src', s);
				$(this).attr('src', s.replace(/\{\$wa_url\}/, wa_url));
			});			
		} else if (document.editor) {
			$("#content").val(document.editor.getCode());
		}
		var form = $(this);
		$("#process-message").html("<i class='icon16 loading'></i> " + $_('Saving...')).fadeIn("slow");
		$.post(form.attr('action'), form.serialize(), function (response) {
			if (response.status == 'ok') {
				$(".error").removeClass('error');
				$("#process-message").html('<i class="icon16 yes"></i>' + $_('Saved')).fadeOut('slow');
				$('#s-editor-save-button').removeClass('yellow').removeClass('red').addClass('green');
				form.trigger('response', [response]);
			} else if (response.status == 'fail') {
				if ($.isArray(response.errors)) {
					var e = response.errors[0];
					$(response.errors[1]).addClass('error');
				} else {
					var e = response.errors;
				} 
				$("#process-message").html('<b style="color:red">' + (e ? e : $_('An error occurred while saving')) + '</b>');
				$('#s-editor-save-button').removeClass('yellow').removeClass('green').addClass('red');
			}
		}, "json");
		// restore focus to editors
        if ($(".el-rte").length && $(".el-rte").is(':visible')) {
            try {
                $("#content").elrte()[0].elrte.selection.moveToBookmark($("#content").elrte()[0].elrte.selection.getBookmark());
            } catch (e) {}
        } else {
        	document.editor.focus();
        }
		return false;
	});	
	
  	$(document).keydown($.wa.site.editorKeyCallback());	
});
