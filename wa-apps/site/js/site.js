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
						} else if (actionName == 'files') {
                            this.filesAction(hash.slice(i).join('/'));
							return;
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
	
	pagesAction: function (id) {
        if ($('#wa-page-container').length) {
            waLoadPage(id);
        } else {
            $('#s-save-panel input').replaceWith('<input id="wa-page-button" type="button" class="button green" value="' + $_('Save') + '">');
            $("#s-content").load('?module=pages', 'domain_id=' + this.domain + (id ? '&id=' + id : ''), function () {
// deprecated                $(".s-scrollable-part").scrollTop(0);
                $.wa.site.savePanel(true, 's-page-editor');
                $.wa.site.active($("#s-link-pages"));
                $('#wa-page-button').click(function () {
                    $("#wa-page-form").submit();
                });
            });
        }
    },

    newsiteAction: function (params) {
        var params = this.parseParams(params)
        $("#domain-name").val(params.domain || '');
        $('#addsite-dialog span.error').empty().hide();
        $("#addsite-dialog").waDialog({
            onSubmit: function () {
                var f = $(this);
                $.post(f.attr('action'), f.serialize(), function (response) {
                    if (response.status == 'ok') {
                        location.href = '?domain_id=' + response.data.id + '#/settings/';
                    } else if (response.status == 'fail') {
                        $("#addsite-dialog span.error").html(response.errors).show();
                    }
                }, "json");
                return false;
            }
        });
        if ($("#s-content .triple-padded .loading").length) {
            this.defaultAction();
        }
    },

    designAction: function (params) {
        if ($('#wa-design-container').length) {
            waDesignLoad(params === undefined ? '' : params);
            $.wa.site.savePanel(params && (params.indexOf('action=edit') != -1 || params.indexOf('file=') != -1));
            $('#wa-design-button').removeClass('yellow').addClass('green');
        } else {
            var p = this.parseParams(params);
            $('#s-save-panel input').replaceWith('<input id="wa-design-button" type="button" class="button green" value="' + $_('Save') + '">');
            $("#s-content").load('?module=design', 'domain_id=' + this.domain, function () {
// deprecated                $(".s-scrollable-part").scrollTop(0);
                $.wa.site.savePanel(params && (params.indexOf('action=edit') != -1 || params.indexOf('file=') != -1));
                $.wa.site.active($("#s-link-design"));
                $('#wa-design-button').click(function () {
                    $("#wa-design-form").submit();
                });
                waDesignLoad(params === undefined ? '' : params);
            });
        }
    },

    themesAction: function (params) {
        this.savePanel(false);
        if ($('#wa-design-container').length) {
            waDesignLoad();
        } else {
            $("#s-content").load('?module=design', 'domain_id=' + this.domain, function () {
// deprecated                $(".s-scrollable-part").scrollTop(0);
                $.wa.site.active($("#s-link-design"));
                waDesignLoad();
            });
        }
    },


    designAddAction: function (params) {
        this.designAction(params + '&file=');
    },


    filesPage: function (hash) {
        if (!hash) {
            hash = location.hash;
        }
        hash = hash.split('/');
        hash = hash[hash.length - 1];
        if (hash && hash.substr(0, 1) == '?') {
            hash = hash.substr(1).split('=');
            if (hash[0] == 'page') {
                return hash[1];
            }
        }
        return 1;
    },

	filesAction: function (load, path) {
		this.savePanel(false);
        var page = 1;
		if (load === true) {
			var params = path || this.filesPath();
		} else {
			var params = Array.prototype.join.call(arguments, '/');
			load = false;
		}

        if (params && (params.indexOf('?') != -1) && params.substr(-1) != '/') {
            var tmp = params.substr(params.indexOf('?') + 1);
            params = params.substr(0, params.indexOf('?'));
            tmp = tmp.split('=');
            if (tmp[0] == 'page') {
                page = tmp[1];
            }
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
			
			$.wa.site.filesList(params, page);
			$("#s-upload-path").val(params || '');
			$("#s-current-path").html('/' + (params || ''));
			$("#s-files-count").html('0');
			$("#s-files-grid input.all").removeAttr('checked');
		};
		if ($("#s-files-tree").length && !load) {
			loadFiles();
		} else {
			$("#s-content").load('?module=files', 'domain_id=' + this.domain, function () {
// deprecated                $(".s-scrollable-part").scrollTop(0);
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
	
	filesList: function (path, page) {
		if (!path) {
			path = this.filesPath();
		}
        if (!page) {
            page = this.filesPage();
        }
        var url = 'http://' + this.options.domain_url + '/wa-data/public/site/' + $.wa.site.filesPath();
		$.post("?module=files&action=list&page=" + page, {path: path}, function (response) {
			$("#s-files-grid tr.s-file").remove();
            $("div.s-pagination").empty();
			for (var i = 0; i < response.data.files.length; i++) {
                var r = response.data.files[i];
				var html = '<tr class="s-file"><td class="min-width"><input type="checkbox" value="' + r.file + '" /></td>' +
				'<td><ul class="menu-h dropdown clickable"><li>' + 
				'<a href="'+url+r.file+'"><i class="icon16 ' + r.type + '"></i> ' +
					r.file + ' <i class="icon10 darr no-overhanging s-file-actions"></i></a>' +
				'</li></ul></td>' + 
				'<td>' + r.datetime + '</td>' +
				'<td><span class="float-right">' + $.wa.site.getFileSize(r.size) + '</span></td></tr>';
				$("#s-files-grid").append(html);
			}
            if (response.data.pages > 1) {
                var html = '<ul class="menu-h">';
                for (var i = 1; i <= response.data.pages; i++) {
                    html += '<li' + (i == page ? ' class="selected"' : '') + '><a href="#/files/' + path + '?page=' + i + '">' + i + '</a></li>';
                }
                html += '</ul>';
                $("div.s-pagination").html(html).show();
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
		var url = this.options.domain_url + '/wa-data/public/site/' + $.wa.site.filesPath() + file;
		var menu = $('<ul class="menu-v width-icons" style="display:block"></ul>');
		if (file.substr(-4) != '.php' && file.substr(-6) != '.phtml' && file.substr(0,1) != '.') {
			menu.append('<li>' +
							'<i class="icon16 globe"></i>' + $_('File URL') + ': ' +
							'<a href="http://' + url + '" target="_blank" class="bold">' + url + '<i class="icon10 new-window"></i></a>' +
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
        $("#s-content").load('?module=settings&domain_id=' + this.domain, function () {
            $.wa.site.active($("#s-link-settings"));
        });
	},
	
	settingsRoutingAction: function () {
		this.settingsAction('routing');
	},
	
	blocksAction: function (params) {
        $('#s-save-panel input').replaceWith('<input id="s-editor-save-button" type="button" class="button green" value="' + $_('Save') + '">');
		$("#s-content").load('?module=blocks', params, function () {
// deprecated            $(".s-scrollable-part").scrollTop(0);
            waEditorAceInit({
                id: 'content',
                save_button: 's-editor-save-button'
            });
            $('#s-editor-save-button').click(function () {
                $("#site-form").submit();
            })
            $.wa.site.savePanel(true);
            $.wa.site.setHelper('app=');
			$.wa.site.active($("#s-link-blocks"));
		});
	},
	
	blocksAddAction: function () {
		this.blocksAction('id=');
	},


	
	active: function (el) {
		$(".sidebar a.selected").removeClass('selected');
		$("ul.s-links li.selected").removeClass('selected');
		if (el && el.length) {
			el.addClass('selected');
		}
	},
	
	routingAction: function (id) {
		this.savePanel(false);
		$("#s-content").load('?module=routing', 'domain_id=' + this.domain, function () {
            $.wa.site.active($("#s-link-routing"));
            $("tr#route-" + id + ' .s-route-settings').click();
		});
	},

    personalSettingsAction: function (hash) {
        var d = this.domain;
        this.savePanel(false);
        var f = function () {
            $("#s-personal-content").html('<i class="icon16 loading"></i>').load('?module=personal&action=settings&hash=' + (hash || ''), 'domain_id=' + d, function () {
                $('ul.s-personal-structure li.selected').removeClass('selected');
            });
        }

        if ($('#s-personal-content').length) {
            f();
        } else {
            this.personalAction(f);
        }
    },

    personalAppAction: function (app_id) {
        var d = this.domain;
        this.savePanel(false);
        var f = function () {
            $("#s-personal-content").html('<i class="icon16 loading"></i>').load('?module=personal&action=app&app_id=' + app_id, 'domain_id=' + d, function () {
                $('ul.s-personal-structure li.selected').removeClass('selected');
                $('#s-personal-app-' + app_id).addClass('selected');
                $('#s-app-frontend-link').html($('#s-personal-app-' + app_id).data('link')).attr('href', $('#s-personal-app-' + app_id).data('link'));
            });
        }
        if ($('#s-personal-content').length) {
            f();
        } else {
            this.personalAction(f);
        }
    },

    personalProfileAction: function () {
        var d = this.domain;
        this.savePanel(false);
        var f = function () {
            $("#s-personal-content").html('<i class="icon16 loading"></i>').load('?module=personal&action=profile', 'domain_id=' + d, function () {
                $('ul.s-personal-structure li.selected').removeClass('selected');
                $('#s-personal-profile-link').addClass('selected');
            });
        }
        if ($('#s-personal-content').length) {
            f();
        } else {
            this.personalAction(f);
        }
    },

    personalAction: function (callback) {
        this.savePanel(false);
        $("#s-content").load('?module=personal', 'domain_id=' + this.domain, function () {
            $.wa.site.active($("#s-link-personal"));
            if (callback) {
                callback();
            } else {
                if ($('#s-personal-settings-link').data('enabled')) {
                    $.wa.setHash($('ul.s-personal-structure a:first').attr('href'));
                } else {
                    $.wa.setHash('#/personal/settings/');
                }
            }
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
	
	savePanel: function (show, add_class) {
		if (show) {
			$("#s-save-panel").show();
// deprecated			$("#wa div.s-scrollable-part").removeClass('s-no-editor');
            $("#s-save-panel input").removeClass('yellow').addClass('green');
			$("#wa-editor-status").empty();
            if (add_class) {
                $("#s-save-panel .s-bottom-fixed-bar-content-offset").addClass(add_class);
            } else {
                $("#s-save-panel .s-bottom-fixed-bar-content-offset").attr('class', 's-bottom-fixed-bar-content-offset');
            }
		} else {
			$("#s-save-panel").hide();
// deprecated			$("#wa div.s-scrollable-part").addClass('s-no-editor');
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
        $.wa.site.newsiteAction();
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
        if ($('#wa-page-content').length) {
            $('#wa-page-content').waEditor('insert', el.text());
        } else {
            wa_editor.insert(el.text());
        }
		return false;
	});
	$("#wa-app > div.s-sidebar a, #wa-header a").live('click', function () {
		if ($("#s-save-panel").is(":visible") && $('#s-save-panel input:button').hasClass('yellow')) {
			return confirm($_("Unsaved changes will be lost if you leave this page now. Are you sure?"));
		}
	});

});
