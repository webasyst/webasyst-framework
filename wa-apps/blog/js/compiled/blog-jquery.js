jQuery.JSON = {
    useHasOwn : ({}.hasOwnProperty ? true : false),
    pad : function(n) {
        return n < 10 ? "0" + n : n;
    },
    m : {
        "\b": '\\b',
        "\t": '\\t',
        "\n": '\\n',
        "\f": '\\f',
        "\r": '\\r',
        '"' : '\\"',
        "\\": '\\\\'
    },
    encodeString : function(s){
        if (/["\\\x00-\x1f]/.test(s)) {
            return '"' + s.replace(/([\x00-\x1f\\"])/g, function(a, b) {
                    var c = jQuery.JSON.m[b];
                    if(c){
                        return c;
                    }
                    c = b.charCodeAt();
                    return "\\u00" +
                    Math.floor(c / 16).toString(16) +
                    (c % 16).toString(16);
            }) + '"';
        }
        return '"' + s + '"';
    },
    encodeArray : function(o){
        var a = ["["], b, i, l = o.length, v;
        for (i = 0; i < l; i += 1) {
            v = o[i];
            switch (typeof v) {
            case "undefined":
            case "function":
            case "unknown":
                break;
            default:
                if (b) {
                    a.push(',');
                }
                a.push(v === null ? "null" : this.encode(v));
                b = true;
            }
        }
        a.push("]");
        return a.join("");
    },
    encodeDate : function(o){
        return '"' + o.getFullYear() + "-" +
        pad(o.getMonth() + 1) + "-" +
        pad(o.getDate()) + "T" +
        pad(o.getHours()) + ":" +
        pad(o.getMinutes()) + ":" +
        pad(o.getSeconds()) + '"';
    },
    encode : function(o){
        if(typeof o == "undefined" || o === null){
            return "null";
        }else if(o instanceof Array){
            return this.encodeArray(o);
        }else if(o instanceof Date){
            return this.encodeDate(o);
        }else if(typeof o == "string"){
            return this.encodeString(o);
        }else if(typeof o == "number"){
            return isFinite(o) ? String(o) : "null";
        }else if(typeof o == "boolean"){
            return String(o);
        }else {
            var self = this;
            var a = ["{"], b, i, v;
            for (i in o) {
                if(!this.useHasOwn || o.hasOwnProperty(i)) {
                    v = o[i];
                    switch (typeof v) {
                    case "undefined":
                    case "function":
                    case "unknown":
                        break;
                    default:
                        if(b){
                            a.push(',');
                        }
                        a.push(self.encode(i), ":",
                            v === null ? "null" : self.encode(v));
                        b = true;
                    }
                }
            }
            a.push("}");
            return a.join("");
        }
    },
    decode : function(json){
        return eval("(" + json + ')');
    }
};
;
/*
 * jQuery store - Plugin for persistent data storage using localStorage, userData (and window.name)
 * 
 * Authors: Rodney Rehm
 * Web: http://medialize.github.com/jQuery-store/
 * 
 * Licensed under the MIT License:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 */

/**********************************************************************************
 * INITIALIZE EXAMPLES:
 **********************************************************************************
 * 	// automatically detect best suited storage driver and use default serializers
 *	$.storage = new $.store();
 *	// optionally initialize with specific driver and or serializers
 *	$.storage = new $.store( [driver] [, serializers] );
 *		driver		can be the key (e.g. "windowName") or the driver-object itself
 *		serializers	can be a list of named serializers like $.store.serializers
 **********************************************************************************
 * USAGE EXAMPLES:
 **********************************************************************************
 *	$.storage.get( key );			// retrieves a value
 *	$.storage.set( key, value );	// saves a value
 *	$.storage.del( key );			// deletes a value
 *	$.storage.flush();				// deletes aall values
 **********************************************************************************
 */

(function($,undefined){

/**********************************************************************************
 * $.store base and convinience accessor
 **********************************************************************************/

$.store = function( driver, serializers )
{
	var that = this;
	
	if( typeof driver == 'string' )
	{
		if( $.store.drivers[ driver ] )
			this.driver = $.store.drivers[ driver ];
		else
			throw new Error( 'Unknown driver '+ driver );
	}
	else if( typeof driver == 'object' )
	{
		var invalidAPI = !$.isFunction( driver.init )
			|| !$.isFunction( driver.get )
			|| !$.isFunction( driver.set )
			|| !$.isFunction( driver.del )
			|| !$.isFunction( driver.flush );
			
		if( invalidAPI )
			throw new Error( 'The specified driver does not fulfill the API requirements' );
		
		this.driver = driver;
	}
	else
	{
		// detect and initialize storage driver
		$.each( $.store.drivers, function()
		{
			// skip unavailable drivers
			if( !$.isFunction( this.available ) || !this.available() )
				return true; // continue;
			
			that.driver = this;
			if( that.driver.init() === false )
			{
				that.driver = null;
				return true; // continue;
			}
			
			return false; // break;
		});
	}
	
	// use default serializers if not told otherwise
	if( !serializers )
		serializers = $.store.serializers;
	
	// intialize serializers
	this.serializers = {};
	$.each( serializers, function( key, serializer )
	{
		// skip invalid processors
		if( !$.isFunction( this.init ) )
			return true; // continue;
		
		that.serializers[ key ] = this;
		that.serializers[ key ].init( that.encoders, that.decoders );
	});
};


/**********************************************************************************
 * $.store API
 **********************************************************************************/

$.extend( $.store.prototype, {
	get: function( key )
	{
		var value = this.driver.get( key );
		return this.driver.encodes ? value : this.unserialize( value );
	},
	set: function( key, value )
	{
		this.driver.set( key, this.driver.encodes ? value : this.serialize( value ) );
	},
	del: function( key )
	{
		this.driver.del( key );
	},
	flush: function()
	{
		this.driver.flush();
	},
	driver : undefined,
	encoders : [],
	decoders : [],
	serialize: function( value )
	{
		var that = this;
		
		$.each( this.encoders, function()
		{
			var serializer = that.serializers[ this + "" ];
			if( !serializer || !serializer.encode )
				return true; // continue;
			try
			{
				value = serializer.encode( value );
			}
			catch( e ){}
		});

		return value;
	},
	unserialize: function( value )
	{
		var that = this;
		if( !value )
			return value;
		
		$.each( this.decoders, function()
		{
			var serializer = that.serializers[ this + "" ];
			if( !serializer || !serializer.decode )
				return true; // continue;

			value = serializer.decode( value );
		});

		return value;
	}
});


/**********************************************************************************
 * $.store drivers
 **********************************************************************************/

$.store.drivers = {
	// Firefox 3.5, Safari 4.0, Chrome 5, Opera 10.5, IE8
	'localStorage': {
		// see https://developer.mozilla.org/en/dom/storage#localStorage
		ident: "$.store.drivers.localStorage",
		scope: 'browser',
		available: function()
		{
			try
			{
				return !!window.localStorage;
			}
			catch(e)
			{
				// Firefox won't allow localStorage if cookies are disabled
				return false;
			}
		},
		init: $.noop,
		get: function( key )
		{
			return window.localStorage.getItem( key );
		},
		set: function( key, value )
		{
			window.localStorage.setItem( key, value );
		},
		del: function( key )
		{
			window.localStorage.removeItem( key );
		},
		flush: function()
		{
			window.localStorage.clear();
		}
	},
	
	// IE6, IE7
	'userData': {
		// see http://msdn.microsoft.com/en-us/library/ms531424.aspx
		ident: "$.store.drivers.userData",
		element: null,
		nodeName: 'userdatadriver',
		scope: 'browser',
		initialized: false,
		available: function()
		{
			try
			{
				return !!( document.documentElement && document.documentElement.addBehavior );
			}
			catch(e)
			{
				return false;
			}
		},
		init: function()
		{
			// $.store can only utilize one userData store at a time, thus avoid duplicate initialization
			if( this.initialized )
				return;
			
			try
			{
				// Create a non-existing element and append it to the root element (html)
				this.element = document.createElement( this.nodeName );
				document.documentElement.insertBefore( this.element, document.getElementsByTagName('title')[0] );
				// Apply userData behavior
				this.element.addBehavior( "#default#userData" );
				this.initialized = true;
			}
			catch( e )
			{
				return false; 
			}
		},
		get: function( key )
		{
			this.element.load( this.nodeName );
			return this.element.getAttribute( key );
		},
		set: function( key, value )
		{
			this.element.setAttribute( key, value );
			this.element.save( this.nodeName );
		},
		del: function( key )
		{
			this.element.removeAttribute( key );
			this.element.save( this.nodeName );
			
		},
		flush: function()
		{
			// flush by expiration
			this.element.expires = (new Date).toUTCString();
			this.element.save( this.nodeName );
		}
	},
	
	// most other browsers
	'windowName': {
		ident: "$.store.drivers.windowName",
		scope: 'window',
		cache: {},
		encodes: true,
		available: function()
		{
			return true;
		},
		init: function()
		{
			this.load();
		},
		save: function()
		{
			window.name = $.store.serializers.json.encode( this.cache );
		},
		load: function()
		{
			try
			{
				this.cache = $.store.serializers.json.decode( window.name + "" );
				if( typeof this.cache != "object" )
					this.cache = {};
			}
			catch(e)
			{
				this.cache = {};
				window.name = "{}";
			}
		},
		get: function( key )
		{
			return this.cache[ key ];
		},
		set: function( key, value )
		{
			this.cache[ key ] = value;
			this.save();
		},
		del: function( key )
		{
			try
			{
				delete this.cache[ key ];
			}
			catch(e)
			{
				this.cache[ key ] = undefined;
			}
			
			this.save();
		},
		flush: function()
		{
			window.name = "{}";
		}
	}
};

/**********************************************************************************
 * $.store serializers
 **********************************************************************************/

$.store.serializers = {
	
	'json': {
		ident: "$.store.serializers.json",
		init: function( encoders, decoders )
		{
			encoders.push( "json" );
			decoders.push( "json" );
		},
		encode: ((typeof(JSON) == 'object')?JSON.stringify:$.JSON.stringify),
		decode: ((typeof(JSON) == 'object')?JSON.parse:$.JSON.parse)
	},
	
	// TODO: html serializer
	// 'html' : {},
	
	'xml': {
		ident: "$.store.serializers.xml",
		init: function( encoders, decoders )
		{
			encoders.unshift( "xml" );
			decoders.push( "xml" );
		},
		
		// wouldn't be necessary if jQuery exposed this function
		isXML: function( value )
		{
			var documentElement = ( value ? value.ownerDocument || value : 0 ).documentElement;
			return documentElement ? documentElement.nodeName.toLowerCase() !== "html" : false;
		},

		// encodes a XML node to string (taken from $.jStorage, MIT License)
		encode: function( value )
		{
			if( !value || value._serialized || !this.isXML( value ) )
				return value;

			var _value = { _serialized: this.ident, value: value };
			
			try
			{
				// Mozilla, Webkit, Opera
				_value.value = new XMLSerializer().serializeToString( value );
				return _value;
			}
			catch(E1)
			{
				try
				{
					// Internet Explorer
					_value.value = value.xml;
					return _value;
				}
				catch(E2){}
			}
			
			return value;
		},
		
		// decodes a XML node from string (taken from $.jStorage, MIT License)
		decode: function( value )
		{
			if( !value || !value._serialized || value._serialized != this.ident )
				return value;

			var dom_parser = ( "DOMParser" in window && (new DOMParser()).parseFromString );
			if( !dom_parser && window.ActiveXObject )
			{
				dom_parser = function( _xmlString )
				{
					var xml_doc = new ActiveXObject( 'Microsoft.XMLDOM' );
					xml_doc.async = 'false';
					xml_doc.loadXML( _xmlString );
					return xml_doc;
				}
			}

			if( !dom_parser )
			{
				return undefined;
			}
			
			value.value = dom_parser.call(
				"DOMParser" in window && (new DOMParser()) || window, 
				value.value, 
				'text/xml'
			);
			
			return this.isXML( value.value ) ? value.value : undefined;
		}
	}
};

})(jQuery);;
(function(a){var r=a.fn.domManip,d="_tmplitem",q=/^[^<]*(<[\w\W]+>)[^>]*$|\{\{\! /,b={},f={},e,p={key:0,data:{}},h=0,c=0,l=[];function g(e,d,g,i){var c={data:i||(d?d.data:{}),_wrap:d?d._wrap:null,tmpl:null,parent:d||null,nodes:[],calls:u,nest:w,wrap:x,html:v,update:t};e&&a.extend(c,e,{nodes:[],parent:d});if(g){c.tmpl=g;c._ctnt=c._ctnt||c.tmpl(a,c);c.key=++h;(l.length?f:b)[h]=c}return c}a.each({appendTo:"append",prependTo:"prepend",insertBefore:"before",insertAfter:"after",replaceAll:"replaceWith"},function(f,d){a.fn[f]=function(n){var g=[],i=a(n),k,h,m,l,j=this.length===1&&this[0].parentNode;e=b||{};if(j&&j.nodeType===11&&j.childNodes.length===1&&i.length===1){i[d](this[0]);g=this}else{for(h=0,m=i.length;h<m;h++){c=h;k=(h>0?this.clone(true):this).get();a.fn[d].apply(a(i[h]),k);g=g.concat(k)}c=0;g=this.pushStack(g,f,i.selector)}l=e;e=null;a.tmpl.complete(l);return g}});a.fn.extend({tmpl:function(d,c,b){return a.tmpl(this[0],d,c,b)},tmplItem:function(){return a.tmplItem(this[0])},template:function(b){return a.template(b,this[0])},domManip:function(d,l,j){if(d[0]&&d[0].nodeType){var f=a.makeArray(arguments),g=d.length,i=0,h;while(i<g&&!(h=a.data(d[i++],"tmplItem")));if(g>1)f[0]=[a.makeArray(d)];if(h&&c)f[2]=function(b){a.tmpl.afterManip(this,b,j)};r.apply(this,f)}else r.apply(this,arguments);c=0;!e&&a.tmpl.complete(b);return this}});a.extend({tmpl:function(d,h,e,c){var j,k=!c;if(k){c=p;d=a.template[d]||a.template(null,d);f={}}else if(!d){d=c.tmpl;b[c.key]=c;c.nodes=[];c.wrapped&&n(c,c.wrapped);return a(i(c,null,c.tmpl(a,c)))}if(!d)return[];if(typeof h==="function")h=h.call(c||{});e&&e.wrapped&&n(e,e.wrapped);j=a.isArray(h)?a.map(h,function(a){return a?g(e,c,d,a):null}):[g(e,c,d,h)];return k?a(i(c,null,j)):j},tmplItem:function(b){var c;if(b instanceof a)b=b[0];while(b&&b.nodeType===1&&!(c=a.data(b,"tmplItem"))&&(b=b.parentNode));return c||p},template:function(c,b){if(b){if(typeof b==="string")b=o(b);else if(b instanceof a)b=b[0]||{};if(b.nodeType)b=a.data(b,"tmpl")||a.data(b,"tmpl",o(b.innerHTML));return typeof c==="string"?(a.template[c]=b):b}return c?typeof c!=="string"?a.template(null,c):a.template[c]||a.template(null,q.test(c)?c:a(c)):null},encode:function(a){return(""+a).split("<").join("&lt;").split(">").join("&gt;").split('"').join("&#34;").split("'").join("&#39;")}});a.extend(a.tmpl,{tag:{tmpl:{_default:{$2:"null"},open:"if($notnull_1){_=_.concat($item.nest($1,$2));}"},wrap:{_default:{$2:"null"},open:"$item.calls(_,$1,$2);_=[];",close:"call=$item.calls();_=call._.concat($item.wrap(call,_));"},each:{_default:{$2:"$index, $value"},open:"if($notnull_1){$.each($1a,function($2){with(this){",close:"}});}"},"if":{open:"if(($notnull_1) && $1a){",close:"}"},"else":{_default:{$1:"true"},open:"}else if(($notnull_1) && $1a){"},html:{open:"if($notnull_1){_.push($1a);}"},"=":{_default:{$1:"$data"},open:"if($notnull_1){_.push($.encode($1a));}"},"!":{open:""}},complete:function(){b={}},afterManip:function(f,b,d){var e=b.nodeType===11?a.makeArray(b.childNodes):b.nodeType===1?[b]:[];d.call(f,b);m(e);c++}});function i(e,g,f){var b,c=f?a.map(f,function(a){return typeof a==="string"?e.key?a.replace(/(<\w+)(?=[\s>])(?![^>]*_tmplitem)([^>]*)/g,"$1 "+d+'="'+e.key+'" $2'):a:i(a,e,a._ctnt)}):e;if(g)return c;c=c.join("");c.replace(/^\s*([^<\s][^<]*)?(<[\w\W]+>)([^>]*[^>\s])?\s*$/,function(f,c,e,d){b=a(e).get();m(b);if(c)b=j(c).concat(b);if(d)b=b.concat(j(d))});return b?b:j(c)}function j(c){var b=document.createElement("div");b.innerHTML=c;return a.makeArray(b.childNodes)}function o(b){return new Function("jQuery","$item","var $=jQuery,call,_=[],$data=$item.data;with($data){_.push('"+a.trim(b).replace(/([\\'])/g,"\\$1").replace(/[\r\t\n]/g," ").replace(/\$\{([^\}]*)\}/g,"{{= $1}}").replace(/\{\{(\/?)(\w+|.)(?:\(((?:[^\}]|\}(?!\}))*?)?\))?(?:\s+(.*?)?)?(\(((?:[^\}]|\}(?!\}))*?)\))?\s*\}\}/g,function(m,l,j,d,b,c,e){var i=a.tmpl.tag[j],h,f,g;if(!i)throw"Template command not found: "+j;h=i._default||[];if(c&&!/\w$/.test(b)){b+=c;c=""}if(b){b=k(b);e=e?","+k(e)+")":c?")":"";f=c?b.indexOf(".")>-1?b+c:"("+b+").call($item"+e:b;g=c?f:"(typeof("+b+")==='function'?("+b+").call($item):("+b+"))"}else g=f=h.$1||"null";d=k(d);return"');"+i[l?"close":"open"].split("$notnull_1").join(b?"typeof("+b+")!=='undefined' && ("+b+")!=null":"true").split("$1a").join(g).split("$1").join(f).split("$2").join(d?d.replace(/\s*([^\(]+)\s*(\((.*?)\))?/g,function(d,c,b,a){a=a?","+a+")":b?")":"";return a?"("+c+").call($item"+a:d}):h.$2||"")+"_.push('"})+"');}return _;")}function n(c,b){c._wrap=i(c,true,a.isArray(b)?b:[q.test(b)?b:a(b).html()]).join("")}function k(a){return a?a.replace(/\\'/g,"'").replace(/\\\\/g,"\\"):null}function s(b){var a=document.createElement("div");a.appendChild(b.cloneNode(true));return a.innerHTML}function m(o){var n="_"+c,k,j,l={},e,p,i;for(e=0,p=o.length;e<p;e++){if((k=o[e]).nodeType!==1)continue;j=k.getElementsByTagName("*");for(i=j.length-1;i>=0;i--)m(j[i]);m(k)}function m(j){var p,i=j,k,e,m;if(m=j.getAttribute(d)){while(i.parentNode&&(i=i.parentNode).nodeType===1&&!(p=i.getAttribute(d)));if(p!==m){i=i.parentNode?i.nodeType===11?0:i.getAttribute(d)||0:0;if(!(e=b[m])){e=f[m];e=g(e,b[i]||f[i],null,true);e.key=++h;b[h]=e}c&&o(m)}j.removeAttribute(d)}else if(c&&(e=a.data(j,"tmplItem"))){o(e.key);b[e.key]=e;i=a.data(j.parentNode,"tmplItem");i=i?i.key:0}if(e){k=e;while(k&&k.key!=i){k.nodes.push(j);k=k.parent}delete e._ctnt;delete e._wrap;a.data(j,"tmplItem",e)}function o(a){a=a+n;e=l[a]=l[a]||g(e,b[e.parent.key+n]||e.parent,null,true)}}}function u(a,d,c,b){if(!a)return l.pop();l.push({_:a,tmpl:d,item:this,data:c,options:b})}function w(d,c,b){return a.tmpl(a.template(d),c,b,this)}function x(b,d){var c=b.options||{};c.wrapped=d;return a.tmpl(a.template(b.tmpl),b.data,c,b.item)}function v(d,c){var b=this._wrap;return a.map(a(a.isArray(b)?b.join(""):b).filter(d||"*"),function(a){return c?a.innerText||a.textContent:a.outerHTML||s(a)})}function t(){var b=this.nodes;a.tmpl(null,null,null,this).insertBefore(b[0]);a(b).remove()}})(jQuery);
/*
 * iButton jQuery Plug-in
 *
 * Copyright 2011 Giva, Inc. (http://www.givainc.com/labs/) 
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 * 	http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Date: 2011-07-26
 * Rev:  1.0.03
 */
(function(E){E.iButton={version:"1.0.03",setDefaults:function(G){E.extend(F,G)}};E.fn.iButton=function(J){var K=typeof arguments[0]=="string"&&arguments[0];var I=K&&Array.prototype.slice.call(arguments,1)||arguments;var H=(this.length==0)?null:E.data(this[0],"iButton");if(H&&K&&this.length){if(K.toLowerCase()=="object"){return H}else{if(H[K]){var G;this.each(function(L){var M=E.data(this,"iButton")[K].apply(H,I);if(L==0&&M){if(!!M.jquery){G=E([]).add(M)}else{G=M;return false}}else{if(!!M&&!!M.jquery){G=G.add(M)}}});return G||this}else{return this}}}else{return this.each(function(){new C(this,J)})}};var A=0;E.browser.iphone=(navigator.userAgent.toLowerCase().indexOf("iphone")>-1);var C=function(N,I){var S=this,H=E(N),T=++A,K=false,U={},O={dragging:false,clicked:null},W={position:null,offset:null,time:null},I=E.extend({},F,I,(!!E.metadata?H.metadata():{})),Y=(I.labelOn==B&&I.labelOff==D),Z=":checkbox, :radio";if(!H.is(Z)){return H.find(Z).iButton(I)}else{if(E.data(H[0],"iButton")){return }}E.data(H[0],"iButton",S);if(I.resizeHandle=="auto"){I.resizeHandle=!Y}if(I.resizeContainer=="auto"){I.resizeContainer=!Y}this.toggle=function(b){var a=(arguments.length>0)?b:!H[0].checked;H.attr("checked",a).trigger("change")};this.disable=function(b){var a=(arguments.length>0)?b:!K;K=a;H.attr("disabled",a);V[a?"addClass":"removeClass"](I.classDisabled);if(E.isFunction(I.disable)){I.disable.apply(S,[K,H,I])}};this.repaint=function(){X()};this.destroy=function(){E([H[0],V[0]]).unbind(".iButton");E(document).unbind(".iButton_"+T);V.after(H).remove();E.data(H[0],"iButton",null);if(E.isFunction(I.destroy)){I.destroy.apply(S,[H,I])}};H.wrap('<div class="'+E.trim(I.classContainer+" "+I.className)+'" />').after('<div class="'+I.classHandle+'"><div class="'+I.classHandleRight+'"><div class="'+I.classHandleMiddle+'" /></div></div><div class="'+I.classLabelOff+'"><span><label>'+I.labelOff+'</label></span></div><div class="'+I.classLabelOn+'"><span><label>'+I.labelOn+'</label></span></div><div class="'+I.classPaddingLeft+'"></div><div class="'+I.classPaddingRight+'"></div>');var V=H.parent(),G=H.siblings("."+I.classHandle),P=H.siblings("."+I.classLabelOff),M=P.children("span"),J=H.siblings("."+I.classLabelOn),L=J.children("span");if(I.resizeHandle||I.resizeContainer){U.onspan=L.outerWidth();U.offspan=M.outerWidth()}if(I.resizeHandle){U.handle=Math.min(U.onspan,U.offspan);G.css("width",U.handle)}else{U.handle=G.width()}if(I.resizeContainer){U.container=(Math.max(U.onspan,U.offspan)+U.handle+20);V.css("width",U.container);P.css("width",U.container-5)}else{U.container=V.width()}var R=U.container-U.handle-6;var X=function(b){var c=H[0].checked,a=(c)?R:0,b=(arguments.length>0)?arguments[0]:true;if(b&&I.enableFx){G.stop().animate({left:a},I.duration,I.easing);J.stop().animate({width:a+4},I.duration,I.easing);L.stop().animate({marginLeft:a-R},I.duration,I.easing);M.stop().animate({marginRight:-a},I.duration,I.easing)}else{G.css("left",a);J.css("width",a+4);L.css("marginLeft",a-R);M.css("marginRight",-a)}};X(false);var Q=function(a){return a.pageX||((a.originalEvent.changedTouches)?a.originalEvent.changedTouches[0].pageX:0)};V.bind("mousedown.iButton touchstart.iButton",function(a){if(E(a.target).is(Z)||K||(!I.allowRadioUncheck&&H.is(":radio:checked"))){return }a.preventDefault();O.clicked=G;W.position=Q(a);W.offset=W.position-(parseInt(G.css("left"),10)||0);W.time=(new Date()).getTime();return false});if(I.enableDrag){E(document).bind("mousemove.iButton_"+T+" touchmove.iButton_"+T,function(c){if(O.clicked!=G){return }c.preventDefault();var a=Q(c);if(a!=W.offset){O.dragging=true;V.addClass(I.classHandleActive)}var b=Math.min(1,Math.max(0,(a-W.offset)/R));G.css("left",b*R);J.css("width",b*R+4);M.css("marginRight",-b*R);L.css("marginLeft",-(1-b)*R);return false})}E(document).bind("mouseup.iButton_"+T+" touchend.iButton_"+T,function(d){if(O.clicked!=G){return false}d.preventDefault();var f=true;if(!O.dragging||(((new Date()).getTime()-W.time)<I.clickOffset)){var b=H[0].checked;H.attr("checked",!b);if(E.isFunction(I.click)){I.click.apply(S,[!b,H,I])}}else{var a=Q(d);var c=(a-W.offset)/R;var b=(c>=0.5);if(H[0].checked==b){f=false}H.attr("checked",b)}V.removeClass(I.classHandleActive);O.clicked=null;O.dragging=null;if(f){H.trigger("change")}else{X()}return false});H.bind("change.iButton",function(){X();if(H.is(":radio")){var b=H[0];var a=E(b.form?b.form[b.name]:":radio[name="+b.name+"]");a.filter(":not(:checked)").iButton("repaint")}if(E.isFunction(I.change)){I.change.apply(S,[H,I])}}).bind("focus.iButton",function(){V.addClass(I.classFocus)}).bind("blur.iButton",function(){V.removeClass(I.classFocus)});if(E.isFunction(I.click)){H.bind("click.iButton",function(){I.click.apply(S,[H[0].checked,H,I])})}if(H.is(":disabled")){this.disable(true)}if(E.browser.msie){V.find("*").andSelf().attr("unselectable","on");H.bind("click.iButton",function(){H.triggerHandler("change.iButton")})}if(E.isFunction(I.init)){I.init.apply(S,[H,I])}};var F={duration:200,easing:"swing",labelOn:"ON",labelOff:"OFF",resizeHandle:"auto",resizeContainer:"auto",enableDrag:true,enableFx:true,allowRadioUncheck:false,clickOffset:120,className:"",classContainer:"ibutton-container",classDisabled:"ibutton-disabled",classFocus:"ibutton-focus",classLabelOn:"ibutton-label-on",classLabelOff:"ibutton-label-off",classHandle:"ibutton-handle",classHandleMiddle:"ibutton-handle-middle",classHandleRight:"ibutton-handle-right",classHandleActive:"ibutton-active-handle",classPaddingLeft:"ibutton-padding-left",classPaddingRight:"ibutton-padding-right",init:null,change:null,click:null,disable:null,destroy:null},B=F.labelOn,D=F.labelOff})(jQuery);;
if ($.ui) {
	$.wa = $.extend(true, $.wa, $.ui);
} else {
	$.wa = {};
}

$.wa = $.extend(true, $.wa, {
	data: {},
	get: function(key, defaultValue) {
		if (key == undefined) {
			return this.data;
		}
		return this.data[name] || defaultValue || null;
	},
	set: function(key, val) {
		if (key == undefined) {
			return this.data;
		}
		if (typeof(key) == 'object') {
			$.extend(this.data, key);
		} else {
			this.data[key] = value;
		}
		return this.data;
	},
	encodeHTML: function(html) {
		return html && (''+html).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
	},
	decodeHTML: function(html) {
		return html.replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>');
	},
	setHash: function(hash){
		if (!(hash instanceof String) && hash.toString) {
			hash = hash.toString();
		}
		hash = hash.replace(/\/\//g, "/");
		hash = hash.replace(/^.*#/, '');
		if (parent && !$.browser.msie) {
			parent.window.location.hash = hash;
		} else {
			location.hash = hash;
		}
		return true;
	},
	back: function (hash) {
		if (history.length > 2) {
			if (typeof(hash)=='number' && parseInt(hash) == hash) {
				history.go(-hash);
			} else {
				history.go(-1);
			}
		} else if ($.browser.msie && history.length > 0) {
			history.back();
		} else if (hash) {
			this.setHash(hash);
		}
		return false;
	},
	toggleHashParam: function(param){
		var hash = location.hash;
		if (hash.search(param) == -1){
			this.addToHash(param);
		} else {
			this.removeFromHash(param);
		}
	},
	addToHash: function(param){
		var hash = location.hash;
		if (hash.search(param) == -1){
			hash+='/'+param+'/';
		}
		this.setHash(hash);
	},
	removeFromHash: function(param){
		var hash = location.hash;
		if (hash.search(param) > -1){
			hash = hash.replace(param, "");
		}
		this.setHash(hash);
	},

	setTitle: function (title) {
		document.title = title;
	},
	array_search: function ( needle, haystack, strict ) {
		var strict = !!strict;

		for(var key in haystack){
			if( (strict && haystack[key] === needle) || (!strict && haystack[key] == needle) ){
				return key;
			}
		}
		return false;
	},

	/** Create dialog with given id (or use existing) and set it up according to properties.
		p = {
			content: // content for the dialog to show immediately. Default is a loading image.
			buttons: // html for button area. Defaut is a single 'cancel' link.

			url: ..., // if specified, content will be loaded from given url
			post: { // used with url; contains post parameters.
				var: value
			},
			onload: null // function to call when content is loaded (only when url is specified)
		}
	  */
	dialogCreate: function(id, p) {
		p = $.extend({
				content: '<h1>Loading... <i class="icon16 loading"></i></h1>',
				buttons: null,
				url: null,
				post: null,
				small: false,
				onload: null,
				oncancel: null
			}, p);

		p.content = $(p.content);
		if (!p.buttons) {
			p.buttons = $('<input type="submit" class="button gray" value="'+$_('Cancel')+'">').click(function() {
				if (p.oncancel) {
					p.oncancel.call(dialog[0]);
				}
				$.wa.dialogHide();
			});
		} else {
			p.buttons = $(p.buttons);
		}

		var dialog = $('#'+id);
		if (dialog.size() <= 0) {
			dialog = $(
				'<div class="dialog" id="'+id+'" style="display: none">'+
					'<div class="dialog-background"></div>'+
					'<div class="dialog-window">'+
						'<div class="dialog-content">'+
							'<div class="dialog-content-indent">'+
								// content goes here
							'</div>'+
						'</div>'+
						'<div class="dialog-buttons">'+
							'<div class="dialog-buttons-gradient">'+
								// buttons go here
							'</div>'+
						'</div>'+
					'</div>'+
				'</div>'
			).appendTo('body');
		}

		dialog.find('.dialog-buttons-gradient').empty().append(p.buttons);
		dialog.find('.dialog-content-indent').empty().append(p.content);
		dialog.show();

		if (p.small) {
			dialog.addClass('small');
		} else {
			dialog.removeClass('small');
		}

		if (p.url) {
			var f_callback = function (response) {
				dialog.find('.dialog-content-indent').html(response);
				$.wa.waCenterDialog(dialog);
				if (p.onload) {
					p.onload.call(dialog[0]);
				}
			};
			if (p.post) {
				$.post(p.url, p.post, f_callback);
			} else {
				$.get(p.url, f_callback);
			}
		}

		this.waCenterDialog(dialog);

		// close on escape key
		var onEsc = function(e) {
			if (!dialog.is(':visible')) {
				return;
			}

			if (e && e.keyCode == 27) { // escape
				if (p.oncancel && typeof p.oncancel == 'function') {
					p.oncancel.call(dialog[0]);
				}
				$.wa.dialogHide();
				return;
			}

			$(document).one('keyup', onEsc);
		};
		onEsc();
		$(document).one('hashchange', $.wa.dialogHide);
		return dialog;
	},

	/** Center the dialog initially or when its properties changed significantly
	  * (e.g. when .small class applied or removed) */
	waCenterDialog: function(dialog) {
		dialog = $(dialog);

		// Have to adjust width and height via JS because of min-width and min-height properties.
		var wdw = dialog.find('.dialog-window');

		var dw = wdw.outerWidth(true);
		var dh = wdw.outerHeight(true);

		var ww = $(window).width();
		var wh = $(window).height();

		var w = (ww-dw)/2 / ww;
		var h = (wh-dh)/2 / wh;

		wdw.css({
			'left': Math.round(w*100)+'%',
			'top': Math.round(h*100)+'%'
		});
	},

	/** Hide all dialogs */
	dialogHide: function() {
		$('.dialog').hide();
		return false;
	},

	/** Close all .dropdown menus */
	dropdownsClose: function() {
		var dd = $('.dropdown:not(.disabled)');
		dd.addClass('disabled');
		setTimeout(function() {
			dd.removeClass('disabled');
		}, 600);
	},

	/** Enable automatic close of .dropdowns when user clicks on item inside one. */
	dropdownsCloseEnable: function() {
		$('.dropdown:not(.disabled)').live('click', this.dropdownsClickHandler);
	},

	/** Disable automatic close of .dropdowns when user clicks on item inside one. */
	dropdownsCloseDisable: function() {
		$('.dropdown:not(.disabled)').die('click', this.dropdownsClickHandler);
	},

	/** Click handler used in dropdownsCloseDisable() and dropdownsCloseEnable(). */
	dropdownsClickHandler: function(e) {
		var self = $(this);
		if (self.hasClass('no-click-close')) {
			return;
		}
		self.addClass('disabled');
		setTimeout(function() {
			self.removeClass('disabled');
		}, 600);
	},

	 /** Set default value for an input field. If field becomes empty, it receives specified css class
		* and default value. On field focus, css class and value are removed. On blur, if field
		* is still empty, css class and value are restored. */
	defaultInputValue: function(input, defValue, cssClass) {
		if (!(input instanceof jQuery)) {
			input = $(input);
		}

		var onBlur = function() {
			var v = input.val();
			if (!v || v == defValue) {
				input.val(defValue);
				input.addClass(cssClass);
			}
		};
		onBlur();
		input.blur(onBlur);
		input.focus(function() {
			if (input.hasClass(cssClass)) {
				input.removeClass(cssClass);
				input.val('');
			}
		});
	},
	util: {
		formatFileSize: function(bytes) {
			var i = -1;
			do {
				bytes = bytes / 1024;
				i++;
			} while (bytes > 99);

			return Math.max(bytes, 0.01).toFixed(2) + ((i >=0)? (' ' + $_(['kB', 'MB', 'GB', 'TB', 'PB', 'EB'][i])):'');
		}
	}
});

$(document).ajaxError(function(e, xhr, settings, exception) {
	// Generic error page
	if (xhr.status !== 200 && xhr.responseText) {
		if (!$.wa.errorHandler || $.wa.errorHandler(xhr)) {
			if (xhr.responseText.indexOf('Exception') != -1) {
				$.wa.dialogCreate('ajax-error', {'content': "<div>" + xhr.responseText + '</div>'});
				return;
			}

			document.open("text/html");
			document.write(xhr.responseText); // !!! throws an "Access denied" exception in IE9
			document.close();
			$(window).one('hashchange', function() {
				window.location.reload();
			});
		}
	}
	// Session timeout, show login page
	else if (xhr.getResponseHeader('wa-session-expired')) {
		window.location.reload();
	}
	// Show an exception in development mode
	else if (typeof xhr.responseText !== 'undefined' && xhr.responseText.indexOf('Exception') != -1) {
		$.wa.dialogCreate('ajax-error', {'content': "<div>" + xhr.responseText + '</div>'});
	}
});

$.ajaxSetup({'cache': false});

$(document).ajaxSend(function (event, xhr, settings) {
	if (settings.type == 'POST') {
		var matches = document.cookie.match(new RegExp("(?:^|; )_csrf=([^;]*)"));
		var csrf = matches ? decodeURIComponent(matches[1]) : '';
		if (settings.data === null ) {
			settings.data = '';
		}
		if (typeof(settings.data) == 'string') {
			if (settings.data.indexOf('_csrf=') == -1) {
				settings.data += (settings.data.length > 0 ? '&' : '') + '_csrf=' + csrf;
			}
		} else if (typeof(settings.data) == 'object') {
			settings.data['_csrf'] = csrf;
		}
	}
});

if (!Array.prototype.indexOf)
{
	Array.prototype.indexOf = function(elt /*, from*/)
	{
	var len = this.length;

	var from = Number(arguments[1]) || 0;
	from = (from < 0)
		 ? Math.ceil(from)
		 : Math.floor(from);
	if (from < 0){from += len;}

	for (; from < len; from++)
	{
		if (from in this &&
			this[from] === elt) {
			return from;
		}
	}
	return -1;
	};
}

/** Localization */

// strings set up by apps
$.wa.locale = $.wa.locale || {};

/** One parameter: translate a string.
  * Two parameters, int and string: translate and get correct word form to use with number. */
$_ = function(p1, p2) {
	// Two parameters: number and string?
	if (p2) {
		if (!$.wa.locale[p2]) {
			if (console){
				console.log('Localization failed: '+p2); // !!!
			}
			return p2;
		}
		if (typeof $.wa.locale[p2] == 'string') {
			return $.wa.locale[p2];
		}

		var d = Math.floor(p1 / 10) % 10,
			e = p1 % 10;
		if (d == 1 || e > 4 || e == 0) {
			return $.wa.locale[p2][2];
		}
		if (e == 1) {
			return $.wa.locale[p2][0];
		}
		return $.wa.locale[p2][1];
	}

	// Just one parameter: a string
	if ($.wa.locale[p1]) {
		return typeof $.wa.locale[p1] == 'string' ? $.wa.locale[p1] : $.wa.locale[p1][0];
	}

	if (console){
		console.log('Localization failed: '+p1); // !!!
	}
	return p1;
};

// EOF;
jQuery.fn.waDialog = function (options) {
    options = jQuery.extend({
        loading_header: '',
        title: '',
        esc: true,
        buttons: null,
        url: null,
        url_reload: true,
        'class': null, // className is a synonym
        content: null,
        'width': 0,
        'height': 0,
        'min-width': 0,
        'min-height': 0,
        disableButtonsOnSubmit: false,
        onLoad: null,
        onCancel: null,
        onSubmit: null
    }, options || {});

    var d = $(this);

    var id = d.attr('id');
    if (id && !d.hasClass('dialog')) {
        d.removeAttr('id');
        if ($("#" + id).length) {
            if (options.url) {
                d = $("#" + id);
                if (!options.url_reload) {
                    options.url = null;
                }
            } else {
                $("#" + id).remove();
            }
        }
    }

    var cl = (options['class'] || options['className']) ? (options['class'] || options['className']) : (d.attr('class') || '');

    if (!d.hasClass('dialog')) {
        var content = $(this);
        var d = $('<div ' + (id ? 'id = "' + id + '"' : '') + ' class="dialog ' + cl + '" style="display: none">'+
                    '<div class="dialog-background"></div>'+
                    '<div class="dialog-window"></div>'+
              '</div>').appendTo('body');
        if (content.find('.dialog-content').length || content.find('.dialog-buttons').length) {
            $('.dialog-window', d).append(content.show());
            var dc = content.find('.dialog-content');
            if (dc.length) {
                var tmp = $('<div class="dialog-content-indent"></div>');
                dc.contents().appendTo(tmp);
                dc.append(tmp);
            }
            dc = content.find('.dialog-buttons');
            if (dc.length) {
                var tmp = $('<div class="dialog-buttons-gradient"></div>');
                dc.contents().appendTo(tmp);
                dc.append(tmp);
            }
        } else {
            $('.dialog-window', d).append(
                    (options.onSubmit ? '<form method="post" action="">' : '') +
                    '<div class="dialog-content">'+
                        '<div class="dialog-content-indent">'+
                            // content goes here
                        '</div>'+
                    '</div>'+
                    '<div class="dialog-buttons">'+
                        '<div class="dialog-buttons-gradient">'+
                            // buttons go here
                        '</div>'+
                    '</div>'+
                    (options.onSubmit ? '</form>' : '')
            );
            d.find('.dialog-content-indent').append(content.show());
        }
        if (options.buttons) {
            d.find('.dialog-buttons-gradient').empty().append(options.buttons);
        }
        if (options.url) {
            d.find('.dialog-content-indent').append('<h1>'+(options.loading_header || '')+'<i class="icon16 loading"></i></h1>');
        } else if (options.content) {
            d.find('.dialog-content-indent').append(options.content);
        }
        if (options.title) {
            d.find('.dialog-content-indent').prepend('<h1>' + options.title + '</h1>');
        }
    } else {
        if (options.content) {
            d.find('.dialog-content-indent').html(options.content);
            if (options.title) {
                d.find('.dialog-content-indent').prepend('<h1>' + options.title + '</h1>');
            }
        }
        if (options.buttons) {
            d.find('.dialog-buttons-gradient').empty().append(options.buttons);
        }
    }

    if (!d.find('.dialog-background').length) {
        d.prepend('<div class="dialog-background"> </div>');
    }

    d.bind('close', function () {
        if (options.onClose) {
            options.onClose.call($(this));
        }
        $(this).hide();
    });

    var css = ['width', 'height', 'min-width', 'min-height'];
    for (var k = 0; k < css.length; k++) {
        if (options[css[k]]) {
            if ((css[k] == 'height' && options[css[k]] < '300px') || (css[k] == 'width' && options[css[k]] < '400px')) {
                d.find('div.dialog-window').css('min-' + css[k], options[css[k]]);
            }
            d.find('div.dialog-window').css(css[k], options[css[k]]);
        }
    }

    if (options.disableButtonsOnSubmit) {
        d.find("input[type=submit]").removeAttr('disabled');
    }

    if (!d.parent().length) {
        d.appendTo('body');
    }


    d.show();

    if (options.url) {
        jQuery.get(options.url, function (response) {
            var el = $(response);
            if (el.find('.dialog-content').length || el.find('.dialog-buttons').length) {
                if (el.find('.dialog-content').length) {
                    d.find('.dialog-content-indent').empty().append(el.find('.dialog-content').contents());
                }
                if (el.find('.dialog-buttons').length) {
                    d.find('.dialog-buttons-gradient').empty().append(el.find('.dialog-buttons').contents());
                }
            } else {
                d.find('.dialog-content-indent').html(response);
            }
            d.trigger('wa-resize');
            if (options.onLoad) {
                options.onLoad.call(d.get(0));
            }
        });
    } else {
        if (options.onLoad) {
            options.onLoad.call(d.get(0));
        }
    }

    d.find('.dialog-buttons').delegate('.cancel', 'click', function (e) {
        e.stopPropagation();
        e.preventDefault();
        if (options.onCancel) {
            options.onCancel.call(d.get(0));
        }
        d.trigger('close');
        return false;
    });


    if (options.onSubmit) {
        d.find('form').unbind('submit').submit(function () {
            if (options.disableButtonsOnSubmit) {
                d.find("input[type=submit]").attr('disabled', 'disabled');
            }
            return options.onSubmit.apply(this, [d]);
        });
    }

    d.bind('wa-resize', function () {
        var el = jQuery(this).find('.dialog-window');
        var dw = el.width();
        var dh = el.height();

        jQuery("body").css('min-height', dh+'px');

        var ww = jQuery(window).width();
        var wh = jQuery(window).height()-60;

        //centralize dialog
        var w = (ww-dw)/2 / ww;
        var h = (wh-dh-60)/2 / wh; //60px is the height of .dialog-buttons div
        if (h < 0) h = 0;
        if (w < 0) w = 0;

        el.css({
            'left': Math.round(w*100)+'%',
            'top': Math.round(h*100)+'%'
        });
    }).trigger('wa-resize');

    if (options.esc) {
        d.bind('esc', function () {
            d.trigger('close');
        });
    }
    return d;
}

jQuery(window).resize(function () {
    jQuery(".dialog:visible").trigger('wa-resize');
});

jQuery(document).keyup(function(e) {
    //all dialogs should be closed when Escape is pressed
    if (e.keyCode == 27) {
        jQuery(".dialog:visible").trigger('esc');
    }
});;
$.storage = new $.store();
$.wa_blog_options = $.wa_blog_options ||{};
$.wa_blog = $.extend(true, $.wa_blog, {
	rights : {
		admin : false
	},
	common : {
		options : {},
		parent : null,
		init_stack : {},
		init : function(options) {
			var self = this;
			this.parent = $.wa_blog;
			this.options = $.extend(this.options, options);

			$(document).ready(function() {
				self.onDomReady(self.parent);
			});

		},
		onDomReady : function(blog) {
			blog = blog || $.wa_blog;
			$(window).scrollTop(0);
			for ( var i in blog) {
				if (i != 'common') {
					if (blog[i].init && (typeof (blog[i].init) == 'function')) {
						try {
							blog[i].init($.wa_blog_options[i]||{});
						} catch (e) {
							if (typeof (console) == 'object') {
								console.log(e);
							}
						}
					}
				}
			}
		},
		ajaxInit : function(blog) {
			blog = blog || $.wa_blog;
			var stack = [];
			$(window).scrollTop(0);
			for ( var i in blog) {
				try {
					if (i != 'common') {
						if (blog[i].ajaxInit && (typeof (blog[i].ajaxInit) == 'function')) {

							if (!this.init_stack[i]) {
								blog[i].ajaxInit();
								this.init_stack[i] = true;
								stack[i] = true;
							}

						}
					}
				} catch (e) {
					stack[i] = false;
					if (typeof (console) == 'object') {
						console.log(e);
					}
				}
			}
			return stack;
		},
		ajaxPurge : function(id) {
			if (this.init_stack[id]) {
				if ($.wa_blog[id]) {
					try {
						if ($.wa_blog[id].ajaxPurge && (typeof ($.wa_blog[id].ajaxPurge) == 'function')) {
							$.wa_blog[id].ajaxPurge();
						}
					} catch (e) {
						if (typeof (console) == 'object') {
							console.log(e);
						}
					}
					$.wa_blog[id] = {};
				}
				this.init_stack[id] = null;
			}
		},
		onContentUpdate : function(response, target) {
			var blog = this.parent;
			for ( var i in blog) {
				if (i != 'common') {
					if (blog[i].onContentUpdate
							&& (typeof (blog[i].onContentUpdate) == 'function')) {
						try {
							blog[i].onContentUpdate();
						} catch (e) {
							if (typeof (console) == 'object') {
								console.log(e);
							}
						}
					}
				}
			}
		}
	},
	plugins : {
	// placeholder for plugins js code
	},
	dialogs : {
		pull : {},
		init : function() {
			var self = this;
			$(".dialog-confirm").live('click', self.confirm);
			$(".js-confirm").live('click', self.jsConfirm);
		},
		close : function(id) {
			if ($.wa_blog.dialogs.pull[id]) {
				$.wa_blog.dialogs.pull[id].trigger('close');
			}
		},
		confirm : function() {
			var id = $(this).attr('id').replace(/-.*$/, '');
			$.wa_blog.dialogs.pull[id] = $("#" + id + "-dialog").waDialog({
				disableButtonsOnSubmit : true,
				onSubmit : function() {
					return false;
				}
			});
			return false;
		},
		jsConfirm : function() {
			var question = $(this).attr('title') || 'Are you sure?';
			if (!confirm(question)) {
				return false;
			}
		}

	},
	sidebar : {
		options : {
			key : 'blog/collapsible/'
		},
		init : function() {
			var self = this;
			$(".menu-collapsible .collapse-handler").each(function() {
				self.restore(this);
				$(this).click(function() {
					return self.toggle(this);
				});
			});
			if ($.wa_blog.rights.admin > 1) {
				$('#blogs').sortable({
					containment : 'parent',
					distance : 5,
					tolerance : 'pointer',
					stop : self.sortHandler
				});
			}
		},
		sortHandler : function(event, ui) {
			var url = "?module=blog&action=sort" + "&blog_id="
					+ $(ui.item).attr('id').replace('blog_li_item_', '') + "&sort="
					+ ($(ui.item).index() + 1);
			$.get(url, function(response) {
				if (response && response.status && response.status == "ok") {
				} else {
					return false;
				}
			}, "json");

		},
		toggle : function(Element) {
			var item = $(Element).find('.rarr');
			if (item.length) { // show
				this.show(Element);
			} else if (item = $(this).find('.darr')) {
				this.hide(Element);
			}
			return false;
		},
		show : function(Element) {
			Element = $(Element);
			var list = Element.parent().find('ul.collapsible');
			list.show();
			if (list.attr('id') == 'blog-drafts') {
				Element.find('.count').hide();
			}
			Element.find('.rarr').removeClass('rarr').addClass('darr');
			$.storage.set(this.options.key + list.attr('id'), null);
		},
		hide : function(Element) {
			Element = $(Element);
			var list = Element.parent().find('ul.collapsible');
			if (list.attr('id') == 'blog-drafts') {
				Element.find('.count').show();
			}
			list.hide();
			Element.find('.darr').removeClass('darr').addClass('rarr');
			$.storage.set(this.options.key + list.attr('id'), 2);

		},
		restore : function(Element) {
			var list = $(Element).parent().find('ul.collapsible');
			var id = list.attr('id');
			if (id) {
				try {
					if (parseInt($.storage.get(this.options.key + id)) == 2) {
						this.hide(Element);
					}
				} catch (e) {
					if (typeof (console) == 'object') {
						console.log(e);
					}
				}
			}
		}
	},
	helpers : {
		init : function() {
			this.compileTemplates();
		},
		compileTemplates : function() {
			var pattern = /<\\\/(\w+)/g;
			var replace = '</$1';

			$("script[type$='x-jquery-tmpl']").each(function() {
				var id = $(this).attr('id').replace(/-template-js$/, '');
				try {
					var template = $(this).html().replace(pattern, replace);
					$.template(id, template);
				} catch (e) {
					if (typeof (console) == 'object') {
						console.log(e);
					}
				}
			});
		}
	}

});

(function($, window, undefined) {
	$.wa_blog.common.init();
})(jQuery, this);
;
