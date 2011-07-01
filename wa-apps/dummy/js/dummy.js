(function ($) {
	// js controller
	$.dummy = {
		// init js controller
		init: function () {
			// if history exists
			if (typeof($.History) != "undefined") {
				$.History.bind(function (hash) {
					$.dummy.dispatch(hash);
				});
			}			
			$("#records-add-link").click(function () {
				$.dummy.recordsAdd();
				return false;
			})
			this.dispatch();
		},
		// dispatch call method by hash
		dispatch: function (hash) {
			if (hash === undefined) {
				hash = location.hash.replace(/^[^#]*#\/*/, '');
			}			
			if (hash) {
				// clear hash
				hash = hash.replace(/^.*#/, '');
				hash = hash.split('/');
				if (hash[0]) {				
					var actionName = "";
					var attrMarker = hash.length;
					for (var i = 0; i < hash.length; i++) {
						var h = hash[i];
						if (i < 2) {
							if (i === 0) {
								actionName = h;
							} else if (parseInt(h, 10) != h) {
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
					// call action if it exists
					if (this[actionName + 'Action']) {
						this.currentAction = actionName;
						this.currentActionAttr = attr;
						this[actionName + 'Action'](attr);
					} else {
						if (console) {
							console.log('Invalid action name:', actionName+'Action');
						}
					}
				} else {
					// call default action
					this.defaultAction();					
				}
			} else {
				// call default action
				this.defaultAction();
			}
		},
		
		defaultAction: function () {
			$("#content").load('?action=records');
		},
		
		recordAction: function (params) {
			$.get('?action=record', {id: params[0]}, function (response) {
				if (response.status == 'ok') {
					var html = '<div class="block">' +  
					'<h1 class="wa-page-heading">' + response.data.title + '</h1>' + 
					'</div><div class="block padded">' + response.data.content + '</div>';
					$("#content").html(html);
				} else {
					alert(response.errors);
				}
			}, 'json');
		},
	
		recordsAdd: function () {
			$("#records-add").waDialog({onSubmit: function () {
				alert('Submit');
				return false;
			}});
		}
	}
})(jQuery);