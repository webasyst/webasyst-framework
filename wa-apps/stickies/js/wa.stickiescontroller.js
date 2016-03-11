(function($) {

    $.ui.ddmanager['drop'] = function(draggable, event) {
        var dropped = false;
        $.each($.ui.ddmanager.droppables[draggable.options.scope] || [], function() {
            if(!this.options) {
                return;
            }
            if (!this.options.disabled && this.visible && $.ui.intersect(draggable, this, this.options.tolerance)){dropped = this._drop.call(this, event);}

            if (!this.options.disabled && this.visible && this.accept.call(this.element[0],(draggable.currentItem || draggable.element))) {
                this.isout = 1; this.isover = 0;
                this._deactivate.call(this, event);
            }
        });
        return dropped;
    };

    $.wa.stickiescontroller = {
            debug: false,
            currentLayout: null,

            stickResizeTimer: {},
            stickResizeSign: {},
            stickFontSize: {},
            stickContentLength: {},
            stick:{},//zindex,x,y,etc
            maxZIndex: 0,
            stickZIndex: {},
            _border:{
                'height':18,
                'width':4
            },
            _margin:{
                'left':20,
                'top':20,
                'right':20,
                'bottom':20
            },
            _size:{
                'min_width':100,
                'min_height':100,

                'max_width':400,
                'max_height':400
            },

            options: {
                'default_background':''
            },

            init: function (options) {
                var self = this;
                if (typeof($.History) != "undefined"){

                    $.History.bind(function (hash) {
                        self.dispatch(hash);
                    });
                    var h = parent ? parent.window.location.hash : location.hash;
                    var sheet_id = Math.max(0,parseInt($.cookie('stickies.current_sheet')));
                    if (h.length < 2) {
                        if(sheet_id>0){
                            $.wa.setHash('#/sheet/'+sheet_id);
                        }else{
                            this.defaultAction();
                        }
                    } else {
                        $.wa.setHash(h);
                    }
                }

                if (default_background) {
                    self.options.default_background = default_background;
                }

                $("textarea.sticky-content").live('keyup change focus', function(event) {
                    var id = parseInt($(this).attr('id').match(/\d+$/));
                    if(id){
                        self.updateZIndex(id);
                        if(event.type!= 'focus'){

                            var timestamp = $(this).attr('timestamp');
                            if(!timestamp) {
                                $(this).attr('timestamp',event.timeStamp);
                                timestamp = event.timeStamp;
                            }

                            if ((event.type == 'change')||(event.timeStamp-timestamp)>10000) {//60 000 ms TODO make it option
                                self.updateFontSize(id, true);
                                self.stickyModifyAction({'content':$(this).val(),'id':id,'timestamp':event.timeStamp});
                                $(this).attr('timestamp',event.timeStamp);
                            } else if(self.stickContentLength[id]!=$(this).val().length) {
                                self.updateFontSize(id,false);
                                $('#sticky_status_'+id).removeClass('saved process').addClass('nosaved');
                            }
                        }
                    } else {
                        self.log('Invalid sticky id:',$(this).attr('id'));
                    }
                });

                $('.js-form-submit').live('submit', function() {
                    var hash = $(this).attr('action').replace(/^.*#\/?/, '');
                    self.dispatch(hash);
                    return false;
                });
                $('.js-form-submit input:reset').live('click', function() {
                    var hash = $(this).attr('src').replace(/^.*#\/?/, '');
                    self.dispatch(hash);
                    return false;
                });

                $(".sticky").live('click', function(event) {
                    var id = parseInt($(this).attr('id').match(/\d+$/));
                    if(id){
                        self.updateZIndex(id);
                    } else {
                        self.log('Invalid sticky id:',$(this).attr('id'));
                    }
                });

                $(".js-menu-item").live('click', function() {
                    var hash = $(this).attr('href').replace(/^.*#\/?/, '');
                    if($(this).hasClass('js-menu-no-proceed')){
                        self.dispatch(hash);
                    } else {
                        $.wa.setHash('#/'+hash);
                    }
                    return false;
                });

                $(".js-sheet-item").live('mouseover', function() {
                    if ($('.stickies-settings-form:visible').size() == 0
                        && $('#wa-app-stickies-sheets').sortable('option', 'disabled') ) {
                        $('#wa-app-stickies-sheets').sortable( "enable" );
                    }
                    return true;
                });

                $('div#stickies-sheets').droppable({
                    accept:'.sticky',
                    tolerance: 'pointer',
                    scope: 'sheet',
                    over: function(event, ui) {
                        var dragg = $(ui.draggable);
                        dragg.resizable( "destroy" ).find('div.sticky-inner').hide();
                        if (dragg.find('.clone-head').size() == 0) {
                            dragg.find('div.sticky-header').clone().addClass('clone-head').appendTo(ui.draggable);
                        }
                        else {
                            dragg.find('.clone-head').show();
                        }

                        if (!dragg.data('isOut')) {
                            var drag = dragg.data("draggable");
                            drag.helperProportions = {
                                width: dragg.find('.clone-head').width(),
                                height: dragg.find('.clone-head').height()
                            };
                            drag._setContainment();

                            dragg.data('isOver', true);
                        }

                    },
                    out: function(event, ui) {
                        var dragg = $(ui.draggable);
                        if (dragg.find('.clone-head').size() != 0) {
                            dragg.find('.clone-head').hide();

                            if (dragg.data('isOut')) {
                                var drag = dragg.data("draggable");
                                drag.helperProportions = {
                                    width: dragg.width(),
                                    height: dragg.height()
                                };
                                drag._setContainment();
                                dragg.data('isOver', null);
                            }
                        }

                        dragg.find('div.sticky-inner').show();
                        self.makeStickResizable(ui.draggable);
                    },
                    drop: function(event, ui)
                    {
                        return true;
                    }
                });

                $('body').click(function(e){
                    if ( $('.sticky').find(e.target).size() == 0 ) {
                        self.updateZIndexAllBlur();
                    }
                });

                $(window).unload( function () { self.checkChanges(); } );

                //prepare templates

                var pattern = /<\\\/(\w+)/g;
                var replace = '</$1';

                $("script[type$='x-jquery-tmpl']").each(function() {
                    var id = $(this).attr('id').replace(/-template-js$/, '');
                    try {
                        var template = $(this).html().replace(pattern, replace);
                        $.template(id, template);
                    } catch (e) {
                        if (typeof(console) == 'object') {
                            console.log(e);
                        }
                    }
                });

            },

            dispatch: function (hash) {
                if (hash) {
                    hash = hash.replace(/^.*#/, '');
                    hash = hash.split('/');
                    if (hash[0]) {
                        var actionName = "";
                        var attrMarker = hash.length;
                        for (var i in hash) {
                            var h = hash[i];
                            if (i < 2) {
                                if (i == 0){
                                    actionName = h;
                                } else if(h.match(/[a-z]+/i)) {
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
                        this.execute(actionName, attr);

                    } else {
                        this.defaultAction();
                    }
                } else {
                    this.defaultAction();
                }
            },

            execute: function (actionName, attr) {
                this.trace('execute', [actionName,attr]);
                if (this[actionName + 'Action']) {
                    this.currentAction = actionName;
                    this.currentActionAttr = attr;
                    this[actionName + 'Action'](attr);
                } else {
                    this.log('Invalid action name:', actionName+'Action');
                    $.wa.setHash('#');
                    this.dispatch('#');
                }
            },

            checkChanges: function() {
                var self = this;
                $('.sticky-status.nosaved').each( function (i) {
                    var id=parseInt($(this).attr('id').match(/\d+$/));
                    self.trace('force save', id);
                    $('#sticky_content_'+id).change();
                });
            },

            defaultAction: function () {
                this.sheetAction(-1);
            },

            sheetAddAction: function(params) {
                var self = this;
                this.sendRequest(
                    '?module=sheet&action=add',
                    null,
                    function (data) {
                        $.tmpl('sheet',{'sheet':data,'current_sheet_id':false}).insertBefore('#wa-app-stickies-sheets .top-padded');
                        self.makeSticksTransferable($('#sheet_item_'+data.id));
                        var sheet_id = data.id;
                        $.wa.setHash('#/sheet/'+sheet_id);
                    }
                );
            },

            sheetAction: function(params) {
                var url = '?module=sheet';
                var sheet_id = parseInt(params);
                var self = this;

                this.checkChanges();

                return this.sendRequest(
                    url,
                    {'sheet_id':sheet_id},
                    function (data) {
                        self.drawSheet(data);

                        var date = new Date();
                        date.setDate(date.getDate()+30);//30 days
                        if(!data.current_sheet_id&&data.default_sheet_id){
                            $.wa.setHash('#/sheet/'+data.default_sheet_id);
                            $.cookie('stickies.current_sheet',data.default_sheet_id,{'expires':date});
                        }else	if(data.current_sheet_id){
                            $.cookie('stickies.current_sheet',data.current_sheet_id,{'expires':date});
                        }
                        if (data.current_sheet){$( 'title' ).html ( (data.current_sheet.name.split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;") || "&lt;"+'no name'.translate()+"&gt;") + " &mdash; " + accountName );}

                        if (data.current_sheet_add) {
                            self.sheetEditAction(data.current_sheet_id);
                            $('.stickies-sidebar-scrolable').scrollTop(10000);
                        }
                    }
                );
            },
            sheetEditAction: function(params) {
                this.checkChanges();
                this.trace('sheetEditAction', params);
                $('#sheet_item_' + parseInt(params)+' .stickies-settings-form').toggle(0);

                if ($('#sheet_item_' + parseInt(params)+' .stickies-settings-form').is(':visible')) {
                    $('#sheet_item_' + parseInt(params)).parent().sortable( "disable" );
                }
                else {
                    $('#sheet_item_' + parseInt(params)).parent().sortable( "enable" );
                }
            },
            sheetBackgroundAction: function (params) {
                var id = parseInt(params[0]);
                var sheet_class = '';
                if(params[1]){
                    sheet_class  = params[1];
                }
                $('#stickies').attr('class',sheet_class);
                $('#background-vars-'+id+' li.selected').removeClass('selected');
                $('#background-vars-'+id+'-'+sheet_class).addClass('selected');

            },
            sheetSaveAction: function(params) {
                var id = parseInt(params);
                var request_data = $('#sheet_item__settings_'+id).serializeArray();
                request_data.push({'sheet_id':id,'name':'background_id','value': $('#stickies').attr('class')});
                this.sendRequest(
                    '?module=sheet&action=save',
                    request_data,
                    function (data) {
                        $('#sheet_item_' + parseInt(params)+' .stickies-settings-form').toggle(0);
                        if (data.name == '') {
                            data.name = '&lt;'+'no name'.translate()+'&gt;';
                        }
                        $('#sheet_item_name_'+id).html(data.name.split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;"));
                        $('title').html( data.name.split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;") + " &mdash; " + accountName );
                    }
                );

            },
            sheetSortAction: function(id, after_id ,sheet) {
                var self = this;
                this.sendRequest(
                    '?module=sheet&action=sort',
                    {
                        id: id,
                        after_id: after_id
                    },
                    function (data) {
                        self.trace('SortAction result', data);
                        if(data.error) {
                            self.error('Error occurred while sorting boards'.translate(), 'error');
                            sheet.sortable('cancel');
                        }
                    },
                    function (data) {
                        self.log('SortAction cancel', {'data':data,'before':self.sheetsOrder,'after':params});
                        sheet.sortable('cancel');
                        self.error('Error occurred while sorting boards'.translate(), 'error');
                        self.sheetAction(null);
                    }
                );
            },
            sheetDeleteAction: function(params) {
                //check stickies count
                if(
                        ($('#wa-app-stickies-stickies div.sticky').length == 0)
                        ||(confirm('Delete board with all stickies?'.translate())==true)
                    ){
                    var id = parseInt(params);
                    var self = this;
                    this.sendRequest(
                        '?module=sheet&action=delete',
                        {'sheet_id':id},
                        function (data) {
                            if(data.sheet_id){
                                self.sheetAction(-1);
                            }
                        }

                    );
                }
            },

            stickyModifyAction: function (param,callback) {
                var state_item = $('#sticky_'+param.id+' .sticky-status');
                state_item.removeClass('saved nosaved').addClass('process');
                if(param.content == undefined){
                    param.content = $('#sticky_'+param.id+' :input[name=content]').val();//.stick_content
                }
                if(param.font_size == undefined){
                    if ($('#sticky_'+param.id+' :input[name=content]').size() ){param.font_size = $('#sticky_'+param.id+' :input[name=content]').css('font-size').replace(/px/i, '');//.stick_content
}
                }
                this.sendRequest(
                        '?module=sticky&action=modify',
                        param,
                        function () {
                            state_item.removeClass('process nosaved').addClass('saved');
                            state_item.text('');
                            if(param.timestamp){
                                $('#sticky_'+param.id+' .stick_content').attr('timestamp',param.timestamp);
                            }

                            if(typeof(callback) == 'function'){
                                callback();
                            }

                        },
                        function () {
                            state_item.removeClass('process nosaved').addClass('nosaved');
                            var message = 'Saving error'.translate();
                            state_item.text(' '+message);
                        }
                );
            },

            stickyAddAction: function (param) {
                var self = this;
                this.sendRequest(
                    '?module=sticky&action=add',
                    {'sheet_id':param[0]},
                    function (data) {
                        self.drawStick(data);
                        self.makeStickDraggable("#sticky_"+data.id,{'width':data.size_width,'height':data.size_height});
                        $("#sticky_"+data.id).find('textarea').focus();
                    }
                );
            },

            stickySettingsAction: function (param) {
                var id = parseInt(param);
                if($('#sticky-settings-'+id).length) {
                    this.hideStickSettings(id);
                } else {
                    this.drawStickSettings(id);
                }
            },

            stickySizeAction: function (params) {
            },

            stickyColorAction: function (params) {
                var id = parseInt(params[0]);
                var color = params[1];
                var sticky_class = 'sticky';
                if(color){
                    sticky_class += ' sticky-'+color;
                }
                $('#color-vars-'+id+' li.selected').removeClass('selected');
                $('#color-vars-'+id+'-'+color).addClass('selected');
                $('#sticky_'+id).attr('class',sticky_class);

                this.hideStickSettings(id);
                this.stickyModifyAction({'id':id,'color':color});
            },

            stickyDeleteAction: function (param) {
                var self = this;
                var id = parseInt(param);
                var request_data = {
                    'id':id,
                    'sheet_id':$('#sticky_'+id+' :input[name=sheet_id]').val()
                };
                this.sendRequest(
                    '?module=sticky&action=delete',
                    request_data,
                     function (data) {
                        var id = parseInt(param[0]);
                        var sticky = $('.stick-position-'+id);
                        sticky.remove();
                        self.stick[id] = null;
                    }

                );
            },

            updateFontSize: function (id, allow_save, is_resize) {
                var mimics = [
                'paddingTop',
                'paddingRight',
                'paddingBottom',
                'paddingLeft',
                'fontSize',
                'fontFamily',
                'fontWeight'];

                var $textarea = $('#sticky_'+id+' .sticky-content:input[name=content]');
                var textareaContent = $textarea.val().replace(/&/g,'&amp;').replace(/  /g, '&nbsp;').replace(/<|>/g, '&gt;').replace(/\n/g, '<br />');
                if (textareaContent.length == 0) {
                    return;
                }

                $twin = $textarea.parent().find('div.twin');
                if ($twin.length == 0) {
                    $twin =	$('<div class="twin"/>').css({'position': 'absolute','word-wrap':'break-word', 'display': 'none', 'border': '1px solid #ccc'});
                    var i = mimics.length;
                    while(i--){
                        $twin.css(mimics[i].toString(),$textarea.css(mimics[i].toString()));
                    }
                    $twin.appendTo($textarea.parent());
                }
                $twin.css('width', $textarea.width());


                var twinContent = $twin.html().replace(/<br>/ig,'<br />');

                $twin.html(textareaContent+'&nbsp;');

                var h = $textarea.height(),
                    textarea_font = $textarea.css('font-size').replace(/px/i, '');

                if ( (!is_resize && (textareaContent+'&nbsp;' == twinContent))
                    || ($twin.height() < h && textarea_font >= 16)
                    || ($twin.height() > h && textarea_font <= 4)
                    ) {
                    return;
                }

                for (var font = 4; font < 16; font += 1 ) {
                    $twin.css('font-size',  font);
                    if ($twin.height() > h) {
                        font =  font - 1;
                        $twin.css('font-size', font);
                        break;
                    }
                }
//				this.log('Current font size '+id,font);
                $textarea.css('font-size', font);
                this.trace('updateFontSize', font);
                $textarea.css('lineHeight', $twin.css('lineHeight'));

                return;
            },

            updateZIndex: function(id){
                var container = $('#sticky_'+id);
                var z_index = this.stickZIndex[id]||Math.max(1,parseInt(container.css('z-index')));
                if(z_index<this.maxZIndex){
                    container.css('z-index',++this.maxZIndex);
                    this.stickZIndex[id] = this.maxZIndex;
                    $('div.sticky-inner.active').removeClass('active');
                    $('#sticky_'+id+' div.sticky-inner').addClass('active');
                }

            },
            updateZIndexAllBlur: function(){
                $('div.sticky-inner.active').removeClass('active');
                this.maxZIndex++;
            },

            drawSheet: function (data) {

                $('#wa-app-stickies-sheets').empty();
                $('#wa-app-stickies-stickies').empty();
                $('#wa-app-stickies-add').empty();

                if(data.current_sheet){
                    $('#stickies').attr('class',data.current_sheet.background_id||this.options.default_background);
                    $.tmpl('add-sticky',{'sheet_id':data.current_sheet_id}).appendTo('#wa-app-stickies-add');
                }
                this.sheetsOrder = new Array();
                this.maxZIndex = 0;
                this.stickFontSize = new Array();
                this.stickContentLength = new Array();
                this.stick = null;
                this.stick = new Array();
                for(sheet_id in data.sheets){
                    if (sheet_id != 'indexOf') {
                        this.sheetsOrder.push(parseInt(data.sheets[sheet_id].id));
                        $.tmpl('sheet',{'sheet':data.sheets[sheet_id],'current_sheet_id':data.current_sheet_id}).appendTo('#wa-app-stickies-sheets');
                    }
                }
                $.tmpl('sheet-add',{}).appendTo('#wa-app-stickies-sheets');


                var stickies_cache = {};
                for(id in data.stickies){
                    this.drawStick(data.stickies[id]);
                    stickies_cache[data.stickies[id].id] = data.stickies[id];
                }

                this.makeSheetsSortable("#wa-app-stickies-sheets");

                this.makeSticksTransferable($("#wa-app-stickies-sheets .js-sheet-item").not('.selected'));

                //make stick draggable
                var self = this;
                $('div.sticky').each(function(i){
                    var id = parseInt($(this).attr('id').match(/\d+$/));
                    self.makeStickDraggable("#sticky_"+id,{'width':$(this).width(),'height':$(this).height()});
                    if (!stickies_cache[id] || stickies_cache[id].font_size == 0) {
                        if (stickies_cache[id].content.length != 0){self.updateFontSize(id,true);}
                    }
                });
            },

            drawStick: function(data) {
                if (typeof data.id == 'undefined') {
                    return;
                }

                var stick_id = data.id;
                this.stickZIndex[stick_id] = this.maxZIndex;

                if (!$('.stick-containment').length) {
                    $contener1 = $('<div />').addClass('stick-containment').css({
                        'position' : 'absolute',
                        'display' : 'block',
                        'left' : '20px',
                        'top' : '20px',
                        'right' : '20px',
                        'bottom' : '20px'
//						'border' : '1px solid #ccc'
                    }).appendTo('#wa-app-stickies-stickies');
                }
                else {
                    $contener1 = $('.stick-containment');
                }

                $contener2 = $('<div />').addClass('stick-position-' + data['id']).css({
                    'position' : 'absolute',
                    'display' : 'block',
                    'left' : '0',
                    'top' : '0',
                    'right' : data['size_width'] + 'px',
                    'bottom' : data['size_height'] + 'px'
//					'border' : '1px solid #ccc'
                }).appendTo($contener1);

                data.position_left = data.position_left / 100;
                data.position_top = data.position_top / 100;
                $.tmpl('sticky',{'sticky':data,'zindex':++this.maxZIndex}).appendTo($contener2);

                return;
            },

            drawStickSettings: function(id) {
                var color = $('#sticky_'+id).attr('class').match(/sticky-(\w+)/);
                if(color) {
                    color = color[1];
                } else {
                    color = '';
                }
                var sticky =$('#sticky_'+id+' .sticky-content');
                var height =sticky.height();
                var width = sticky.width();
                var size = Math.max(height,width);
                $.tmpl('sticky-settings',{'sticky':{'id':id,'color':color,'size':size}}).appendTo('#sticky_'+id+' .sticky-inner');
                $('#sticky_'+id+' .sticky-content').hide('fast',function(){
                    $('#sticky_'+id+' .sticky-inner').addClass('sticky-back');
                    $('#sticky-settings-'+id).show().height(height).width(width);
                });
            },

            hideStickSettings: function(id)
            {
                var self = this;
                $('#sticky-settings-'+id).hide('fast',function(){
                    $('#sticky_'+id+' .sticky-inner').removeClass('sticky-back');
                    $('#sticky_'+id+' .sticky-content').show();
                    $('#sticky-settings-'+id).remove();
                    self.updateFontSize(id,false);
                });
            },

            makeSheetsSortable: function (selector) {
                var self = this;
                $(selector).sortable({
                    'containment':'#stickies-events',
                    'items':'li.js-sheet-item',
                    'axis':'y',
                    'distance':5,
                    'containment':'parent',
                    //'handle':'.wa-ui-helper-sortable',
                    'cursor':'move',
                    'start':function (event,ui) {
                    },
                    'update': function(event, ui) {
                        var id = parseInt($(ui.item).attr('id').match(/\d+$/));
                        var after_id = $(ui.item).prev().attr('id');
                        if (after_id === undefined) {
                            after_id = 0;
                        }
                        else {
                            after_id = parseInt(after_id.match(/\d+$/));
                        }
                        self.sheetSortAction(id, after_id, $(this));
                    }
                });
            },

            makeStickDraggable: function (selector,size) {
                var self = this;
                size = size||{'height':0,'width':0};
                $(selector).each(function(){
                    $(this).draggable( {
                        'start' : function(event,ui) {
                            var id = parseInt($(this).attr('id').match(/\d+$/));
                            $('#sticky_status_'+ id).removeClass('saved process').addClass('nosaved');
                            self.updateZIndex(id);


                        },
                        'stop' : function (event,ui){
                            //is exits draggable
                            if ($(ui.helper).data('draggable')) {
                                self.onStickDragableStop(event,ui,$(this));
                            }
                        },
                        scroll: false,
                        scope: 'sheet',
                        'containment':'.stick-containment',
                        'zindex': 10001,
                        'cusrsor':'move',
                        'handle':'div.sticky-header'
                    });
                });
                //and resizable
                self.makeStickResizable(selector);
            },

            makeStickResizable: function(selector){
                var self = this;
                $(selector).resizable({
                    maxHeight:	 this._size.max_height	+ this._border.height,
                    maxWidth:	 this._size.max_width	+ this._border.width,
                    minHeight:	 this._size.min_height	+ this._border.height,
                    minWidth:	 this._size.min_width	+ this._border.width,
                    ghost: true,
                    stop:function (event,ui){
                        var sticky_id = parseInt($(this).attr('id').match(/\d+$/));
                        self.onResizeHandler(sticky_id, ui.size.width, ui.size.height);
                    }
                });
            },

            makeSticksTransferable: function (selector) {
                var self = this;
                $(selector).droppable({
                    accept:'.sticky',
                    hoverClass: 'ui-state-active',
                    tolerance: 'pointer',
                    scope: 'sheet',
                    drop: function(event, ui) {
                        var sheet_id = parseInt($(this).attr('id').match(/\d+$/));
                        var id= parseInt(ui.draggable.attr('id').match(/\d+$/));
                        self.trace('drop', [id,event.target]);
                        self.onStickDrop(id, sheet_id);
                    }
                });
            },

            onStickDrop: function(id, sheet_id) {
                $('#sticky_'+id).draggable({ revert: true });
                this.stickyModifyAction(
                    {'id':id,'sheet_id':sheet_id},
                    function(data){
                        $('#sticky_'+id).draggable('destroy');
                        $('#sticky_'+id).remove();
                        var counter = $('#sheet_item_'+sheet_id+' .count');
                        counter.text(parseInt(counter.text())+1);
                    },
                    function(){
                        $('#sticky_'+id).draggable('option','disabled',false);
                    }
                );
            },

            onResizeHandler: function(id, width,height) {

                $('.stick-position-'+id).css({
                    'right' : width + 'px',
                    'bottom' : height + 'px'
                });

                var size	 = width;
                $('#size-vars-'+id+' li.selected').removeClass('selected');
                $('#size-vars-'+id+'-'+size).addClass('selected');
                $('#sticky_'+id).height(height).width(width);
                $('#sticky_'+id+' .sticky-content').height(height-this._border.height).width(width-this._border.width);
                $('#sticky-settings-'+id).height(height).width(width);

                var offset = this.stickCalculatePosition($('#sticky_'+id));
                var params = {
                        'drag':true,
                        'id':id,
                        'size_width': width,
                        'size_height': height,
                        'position_left' : offset.left_procent * 100,
                        'position_top' : offset.top_procent * 100
                };

                this.trace('resize',params);
                this.updateFontSize(id, false, true);

                this.updateZIndex(id);
                this.stickyModifyAction(params);
            },

            stickCalculatePosition: function(stick) {
                this.trace('stickCalculatePosition');
                var container = stick.parent(),
                    left_procent = 100 * parseInt(stick.css('left')) / parseInt(container.width()),
                    top_procent = 100 * parseInt(stick.css('top')) / parseInt(container.height());

                stick.css('left', left_procent+"%");
                stick.css('top', top_procent+"%");
                return {
                    left_procent: left_procent,
                    top_procent: top_procent
                };
            },

            onStickDragableStop: function(event, ui, item) {
                var id = parseInt(item.attr('id').match(/\d+$/));
                if (id){

                    var stick = $(item);
                    var offset = this.stickCalculatePosition(stick);

                    var params = {
                        'drag':true,
                        'id':id,
                        'position_left': offset.left_procent * 100,
                        'position_top': offset.top_procent * 100
                    };
                    this.stickyModifyAction(params);
                    return;

                } else {
                    this.log('Invalid sticky id:',item.attr('id'));
                }
            },

            sendRequest: function(url,request_data,success_handler,error_handler) {
                var self = this;
                return $.ajax({
                    'url':url,
                    'data':request_data||{},
                    'type':'POST',
                    'dataType': 'text',
                    'success': function (data, textStatus, XMLHttpRequest) {
                        try{
                            data = $.parseJSON(data);
                        }catch(e){
                            self.log('Invalid server JSON responce', e);
                            if(typeof(error_handler) == 'function'){
                                error_handler();
                            }
                            self.error('Invalid server responce'.translate()+'<br>'+e, 'error');
                        }
                        if(data){
                            switch(data.status){
                                case 'fail':{
                                    self.error(data.errors.error||data.errors, 'error');
                                    if(typeof(error_handler) == 'function'){
                                        error_handler(data);
                                    }
                                    break;
                                }
                                case 'ok':{
                                    if(typeof(success_handler) == 'function'){
                                        success_handler(data.data);
                                    }
                                    break;
                                }
                                default: {
                                    self.log('unknown status responce', data.status);
                                    if(typeof(error_handler) == 'function'){
                                        error_handler(data);
                                    }
                                    break;
                                }
                            }
                        }else{
                            self.log('empty responce', textStatus);
                            if(typeof(error_handler) == 'function'){
                                error_handler();
                            }
                            self.error('Empty server responce'.translate(), 'warning');
                        }

                    },
                    'error': function (XMLHttpRequest, textStatus, errorThrown) {
                        self.log('AJAX request error', textStatus);
                        if(typeof(error_handler) == 'function'){
                            error_handler();
                        }
                        self.error('AJAX request error'.translate(), 'warning');
                    }
                });
            },

            error: function (message,type) {
                var container = $('#wa-system-notice');
                if (container) {
                    //TODO use correct message box
                    var delay = 1500;
                    switch(type){
                        case 'error':{
                            delay = 6000;
                            message = '<i class="icon16 bug"></i>'+message;
                            break;
                        }
                        case 'warning':{
                            message = '<i class="icon16 no"></i>'+message;
                            break;
                        }
                    }
                    container.html(message);
                    container.slideDown().delay(delay).slideUp();

                } else {
                    alert(message);
                }

            },

            log: function (message, params) {
                if(console){
                    console.log(message,params);
                }
            },
            trace: function (message, params) {
                if(console && this.debug){
                    console.log(message,params);
                }
            }
    };
})(jQuery);
