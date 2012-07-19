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
            $(".s-scrollable-part").scrollTop(0);
            // set active link in sidebar
            $.wa.site.active($("#page-" + p.id));

            $("#s-save-panel .s-bottom-fixed-bar-content-offset").addClass('s-page-editor');

            var h = $("div.s-editor").height() - $("div.s-editor .s-gray-toolbar").height() - 132;
            if ($("#wa-app").height() < $("#wa").height() - $("#wa-header").height()) {
                h += $("#wa").height() - $("#wa-header").height() - $("#wa-app").height();
            }
            if (h < 300) {
                h = 300;
            }
            waEditorInit({'id': 'content', 'save_button': 's-editor-save-button', 'sortable': false, 'height': h});
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
                $(".s-scrollable-part").scrollTop(0);
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
		if (file.substr(-4) != '.php' && file.substr(-6) != '.phtml' && file.substr(0,1) != '.') {
			menu.append('<li>' +
							'<i class="icon16 globe"></i>' + $_('File URL') + ': ' +
							'<a href="' + url + '" target="_blank" class="bold">' + url + '<i class="icon10 new-window"></i></a>' +
						'</li>');
		}
		if (file.substr(-4) != '.php' && file.substr(-6) != '.phtml') {
			menu.append('<li>' + 
					'<a href="?module=files&action=download&path=' + 
						$.wa.site.filesPath() + '&file=' + file + '"><i class="icon16 download"></i>' + $_('Download') + 
					'</a></li>');
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
            $(".s-scrollable-part").scrollTop(0);
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
            $(".s-scrollable-part").scrollTop(0);
			$.wa.site.active($("#s-link-themes"));
		});
	},
	
	designAction: function (params) {
		var p = this.parseParams(params);
		$("#s-content").load('?module=design', params + '&domain_id=' + this.domain, function () {
            $(".s-scrollable-part").scrollTop(0);
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

        wa_editor = CodeMirror.fromTextArea(document.getElementById(id), {
            mode: t ? 'text/' + t : "text/html",
            tabMode: "indent",
            height: "dynamic",
            lineWrapping: true,
            onKeyEvent: function (editor, e) {
                var event = jQuery.Event(e);
                if (event.type == 'keydown') {
                    waEditorKeyCallback(false, {'save_button': 's-editor-save-button'})(e);
                } else if (event.type = 'keypress') {
                    waEditorKeyCallback(true, {'save_button': 's-editor-save-button'})(e);
                }
            }
        });
        $(".CodeMirror-scroll").css('min-height', h + 'px');

  		this.setHelper(helper || false);
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
			wa_editor.replaceSelection(el.text());
		}
		return false;
	});
	
	$("#wa-app > div.s-sidebar a, #wa-header a, #s-content > div.sidebar a").live('click', function () {
		if ($("#s-save-panel").is(":visible") && $('#s-editor-save-button').hasClass('yellow')) {
			return confirm($_("Unsaved changes will be lost if you leave this page now. Are you sure?"));
		}
	});	
	
	$("#site-form").live('submit', function () {
        waEditorUpdateSource({'id': 'content'});
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
            //$("#content").elrte()[0].elrte.selection.moveToBookmark($("#content").elrte()[0].elrte.selection.getBookmark());
        }
		return false;
	});
});
