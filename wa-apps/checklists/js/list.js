/*
 * Script for list items page.
 */
(function(){
	/** Update unchecked list items count in sidebar */
	var updateCount = function() {
		$('#cnt'+$.cl.list_id).text($('#c-checklist :checkbox:not(:checked)').length);
	};

	/** Insert item into ul#c-checklist */
	var insertItem = function(item, where) {

		//
		// `li.item`s in `ul#c-checklist` are sorted by `rel` attribute.
		// when a new `li.item` is inserted, all `rel`s of next items are increased by 1.
		// Initial `rel` is equal to the `sort` parameter of an item.
		//

		where = where || 'top';
		var li =	'<li rel="'+item.sort+'" class="item'+(item.done ? ' c-done' : '')+'">'+
						'<label>'+
							'<input type="checkbox" name="'+item.id+'" class="c-item-checkbox"'+(item.done ? ' checked="true"' : '')+'> '+
							'<span class="c-item-name">'+item.name+'</span>'+
						'</label>'+
						(item.when ? '<span class="hint c-completed-when">'+item.when+'</span>' : '')+
						(item.who ? '<span class="hint c-completed-by">'+item.who+'</span>' : '')+
						' <a href="#" class="c-edit-item" title="'+$.cl.loc.edit+'"><i class="icon10 edit"></i></a>'+
						' <a href="#" class="c-delete-item" title="'+$.cl.loc.del+'"><i class="icon10 no"></i></a>'+
					'</li>';

		// li will be inserted inserted before this item
		var insertBefore = null;
		$('#c-checklist li.item').each(function() {
			var self = $(this);
			var sort = parseInt(self.attr('rel'));
			if (sort >= item.sort) {
				if (!insertBefore) {
					insertBefore = self;
				}
				self.attr('rel', sort+1);
			}
		});

		if (insertBefore) {
			insertBefore.before(li);
		} else {
			$('#c-checklist').append(li);
		}
		$('#c-checklist').sortable('refresh');
	};

	// List items
	var items = $.cl.items;
	for(i = 0; i < items.length; i++) {
		insertItem(items[i], 'bottom');
	};
	updateCount();

	// show start over form when there's no unchecked items
	var maybeStartOver = function() {
		if ($('#c-checklist :checkbox:not(:checked)').size() > 0 || $('#c-checklist :checkbox').size() <= 0) {
			$('#c-start-over-form').hide();
		} else {
			$('#c-start-over-form').show();
		}
	};
	maybeStartOver();

	// Start over button resets all checkboxes
	$('#c-start-over-form :submit').click(function() {
		var btn = $(this);
		if (btn.attr('disabled')) {
			return false;
		}
		btn.attr('disabled', true);
		btn.parent().append('<i class="icon16 loading">');

		$.post('?module=json&action=startover', {
			id: $.cl.list_id
		}, function(items) {
			items = items.data;
			$('#c-checklist .item').remove();
			for(i = 0; i < items.length; i++) {
				insertItem(items[i], 'bottom');
			};
			updateCount();
			maybeStartOver();
			btn.attr('disabled', false);
			btn.siblings('.loading').remove();
		}, 'json');
		return false;
	});

	// turns edit mode on and off
	// edit mode = form to add items
	var toggleEditMode = function(action) {
		if (!$.cl.can_edit) {
			return false;
		}
		action = action || 'toggle';
		var form = $('#c-add-form');
		var clist = $('#c-checklist');
		if (action === 'off' || (action === 'toggle' && form.is(':visible'))) {
			$('#c-add-form').hide();
			clist.removeClass('c-edit-mode');
		} else {
			clist.addClass('c-edit-mode');
			$('#c-add-form').show().find(':text').focus();
		}
	};
	$('#add').click(function() {
		toggleEditMode();
		return false;
	});

	// Warn user when there's more than 255 symbols are in name or icon field
	var warn = function(input) {
		var msg = input.siblings('.max255');
		if (input.val().length > 255 && !msg.length) {
			input.parent().append('<em class="hint max255">'+$.cl.loc.max+'</em>');
		} else if (input.val().length <= 255 && msg.length) {
			msg.remove();
		}
	}

	// add list item when user clicks button or presses enter in input
	var addItem = function() {
		if (!$.cl.can_edit) {
			return false;
		}
		var btn = $('#c-add-form :submit');
		if (btn.attr('disabled')) {
			return false;
		}

		var input = $('#c-add-form :text');
		if (!input.val()) {
			return false;
		}

		btn.attr('disabled', true);
		btn.parent().append('<i class="icon16 loading">');

		$.post('?module=json&action=itemsave', {
			list_id: $.cl.list_id,
			name: input.val()
		}, function(item) {
			item = item.data;
			insertItem(item);
			btn.attr('disabled', false);
			btn.siblings('.loading').remove();
			updateCount();
			maybeStartOver();
		}, 'json');

		input.val('').focus();
		return false;
	};
	$('#c-add-form :submit').click(addItem);
	$('#c-add-form :text').keyup(function (e) {
		if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
			return addItem();
		}
		warn($(this));
	});

	// Mark items as done when user checks them
	$(':checkbox', $('#c-checklist')[0]).die('change').live('change', function() {
		var cbox = $(this);
		var li = cbox.parents('#c-checklist li.item');
		cbox.after('<i class="icon16 loading">').attr('disabled', true);

		var data = {
			id: cbox.attr('name'),
		};
		if (cbox.is(':checked')) {
			data.check = 1;
			if(!li.prevAll('.c-done').length) {
				var sort = li.nextAll('.c-done').first().attr('rel');
				if(!sort) {
					sort = $('#c-checklist li.item').last().attr('rel');
					if (sort) {
						sort++;
					}
				}
				if (sort) {
					data.sort = sort
				}
			}
		} else {
			data.uncheck = 1;
			data.sort = 0;
		}

		$.post('?module=json&action=itemsave', data, function(item) {
			item = item.data;
			cbox.parents('#c-checklist li').remove();
			insertItem(item);
			updateCount();
			maybeStartOver();
		}, 'json');
	});

	// edit items
	$('.c-edit-item', $('#c-checklist')[0]).die('click').live('click', function() {
		if (!$.cl.can_edit) {
			return false;
		}
		var li = $(this).parents('#c-checklist li');
		var newValue = prompt($.cl.loc.prompt, li.find('.c-item-name').text());
		if (!newValue) {
			return false;
		}

		li.find('.c-item-name').text(newValue);
		$.post('?module=json&action=itemsave', {
				id: li.find(':checkbox').attr('name'),
				name: newValue
			}, function(item) {
				item = item.data;
				li.remove();
				insertItem(item);
			}, 'json'
		);
		return false;
	});

	// delete items
	$('.c-delete-item', $('#c-checklist')[0]).die('click').live('click', function() {
		if (!$.cl.can_edit) {
			return false;
		}
		var li = $(this).parents('#c-checklist li');
		$.post('?module=json&action=deleteitem', {
			id: li.find(':checkbox').attr('name')
		});
		li.remove();
		updateCount();
		maybeStartOver();
		return false;
	});

	// highlight list in sidebar
	$('#cnt'+$.cl.list_id).parent().addClass('selected').siblings('.selected').removeClass('selected');

	// make list sortable
	if ($.cl.can_edit) {
		$('#c-checklist').sortable({
			opacity: 0.75,
			handle: 'span.c-item-name',
			distance: 5,
			stop: function(e, ui) {
				var li = ui.item;
				var data = {
					id: li.find(':checkbox').attr('name'),
				};
				if (li.next().length) {
					data.sort = li.next().attr('rel');
				} else if (li.prev().length) {
					data.sort = 1 + li.prev().attr('rel');
				} else {
					return; // no reason to sort list if there's only one item
				}

				$.post('?module=json&action=itemsave', data, function(item) {
					item = item.data;
					li.remove();
					insertItem(item);
				}, 'json');
			}
		});

		// if there are no items then show form right away
		if (!items.length) {
			toggleEditMode('on');
		}
	}

	// delete list button
	$('#deletelist').click(function() {
		if (!$.cl.can_edit) {
			return false;
		}

		if (!confirm($.cl.loc.delconfirm.replace('%s', $.trim($('#name span').text())))) {
			return false;
		}
		$(this).find('.icon16').removeClass('delete').addClass('loading');
		$.post('?module=json&action=deletelist', {id: $.cl.list_id}, function() {
			window.location.search = '';
		});
		return false;
	});

	// !!! experimental hotkeys
	var current = null;
	$(document).keyup(function(e) {
		if (!e || !e.which) {
			return true;
		}

		switch(e.which) {
			// up and down keys focus checkboxes
			case 40: // down
				if (current && current[0] && (!document.activeElement || current[0] === document.activeElement)) {
					// find checkbox below current
					var n = current.parent().parent().next().find(':checkbox').focus();
					if (n[0]) {
						current = n;
					}
				} else {
					current = $('#c-checklist :checkbox').first().focus();
				}
				return false;
			case 38: // up
				if (current && current[0] && (!document.activeElement || current[0] === document.activeElement)) {
					// find checkbox above current
					var n = current.parent().parent().prev().find(':checkbox').focus();
					if (n[0]) {
						current = n;
					}
				} else {
					current = $('#c-checklist :checkbox').last().focus();
				}
				return false;
			// Alt + A focuses input to add new items
			case 65: // A
				if (e.altKey) {
					toggleEditMode('on');
					return false;
				}
				return true;
			// Escape hides new item form
			case 27: // Escape
				toggleEditMode('off');
				return false;
		}
	});
})();