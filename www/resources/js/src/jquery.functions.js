(function($){
	// Hibák kiíratása
	window.displayError = function(title, selector){
		if (typeof(selector) === 'undefined') selector = 'error';
		function parse(val) {
		    var result = "Not found",
		        tmp = [];
		    location.search
		    //.replace ( "?", "" )
		    // this is better, there might be a question mark inside
		    .substr(1)
		        .split("&")
		        .forEach(function (item) {
		        tmp = item.split("=");
		        if (tmp[0] === val) result = decodeURIComponent(tmp[1]);
		    });
		    return result;
		}

		function urldecode(str) {
			return decodeURIComponent((str+'').replace(/\+/g, '%20'));
		}

		var error = urldecode(parse(selector));
		if (error != 'Not found')
			$.Dialog.fail(title,error);
	};

	// Gyakori elemek globalizálása
	$.extend(window, {
		$w: $(window),
		$d: $(document),
		$body: $('body'),
	});

	// document.createElement shortcut
	var mk = function(){ return document.createElement.apply(document,arguments) };
	window.mk = function(){return mk.apply(window,arguments)};

	// $(document.createElement) shortcut
	$.mk = function(){ return $(document.createElement.apply(document,arguments)) };

	// Common key codes for easy reference
	window.Key = {
		Enter: 13,
		Space: 32,
		LeftArrow: 37,
		RightArrow: 39,
		Tab: 9,
	};
	$.isKey = function(Key, e){
		return e.keyCode == Key;
	};

	// Checks if a variable is a function and if yes, runs it
	// If no, returns default value (undefined or value of def)
	$.callCallback = function(func, params, def){
		if (typeof params !== 'object' || !$.isArray(params)){
			def = params;
			params = [];
		}
		if (typeof func !== 'function')
			return def;

		return func.apply(window, params);
	};

	// Make first character in string uppercase
	$.capitalize = function(str){
		return str.length === 1 ? str.toUpperCase() : str[0].toUpperCase()+str.substring(1);
	};

	// Array.includes (ES7) polyfill
	if (typeof Array.prototype.includes !== 'function')
		Array.prototype.includes = function(elem){ return this.indexOf(elem) !== -1 };

	function getCookie(name) {
		var value = "; " + document.cookie;
		var parts = value.split("; " + name + "=");
		if (parts.length == 2) return parts.pop().split(";").shift();
	}

	$.fn.checkInputs = function(){
		 var $elem = $(this),
		     $ch = $elem.find('input'),
		     toReturn = ' ';

		 $ch.each(function(_,item){
		    var $item = $(item),
		        pattern = $item.attr('pattern');

		    if (typeof pattern !== undefined){
		        var regEx = new RegExp(pattern);

		        if (regEx.test($item.val()) == false) {
		            toReturn = $item.attr('name');
		            return false;
		        }
		    }
		 });

		 return toReturn;
	};

	$.fn.sortChildren = function(selector, reverse){
	    var $elem = $(this),
	        $ch = $elem.children();
	    $ch.sort(function(a,b){
	        return reverse ? $(b).find(selector).text().localeCompare($(a).find(selector).text()) : $(a).find(selector).text().localeCompare($(b).find(selector).text());
	    }).appendTo($elem);
	    return $elem;
	};

	$.fn.serializeForm = function(){
		var tempdata = $(this).serializeArray(), data = {};
		$.each(tempdata,function(i,el){
			data[el.name] = el.value;
		});

		var token = getCookie('JSSESSID');
		if (typeof token == 'undefined') return data;

		data['JSSESSID'] = token;

		return data;
	};

	$.rangeLimit = function(input,overflow){
		var min, max, paramCount = 2;
		switch (arguments.length-paramCount){
			case 1:
				min = 0;
				max = arguments[paramCount];
				break;
			case 2:
				min = arguments[paramCount];
				max = arguments[paramCount+1];
				break;
			default:
				throw new Error('Invalid number of parameters for $.rangeLimit');
		}
		if (overflow){
			if (input > max)
				input = min;
			else if (input < min)
				input = max;
		}
		return Math.min(max, Math.max(min, input));
	};

	$.fn.toggleHtml = function(contentArray){
		this.html(contentArray[$.rangeLimit(contentArray.indexOf(this.html())+1, true, contentArray.length-1)]);
	};

	var CSRF_COOKIE_NAME = 'JSSESSID';
	window.getToken = function(){
		var token = getCookie(CSRF_COOKIE_NAME);
		if (typeof token == 'undefined') return '';

		return token;
	};
	$.ajaxPrefilter(function(event, origEvent){
		if ((origEvent.type||event.type).toUpperCase() !== 'POST')
			return;

		var t = getToken();
		if (typeof event.data === "undefined")
			event.data = "";
		if (typeof event.data === "string"){
			var r = event.data.length > 0 ? event.data.split("&") : [];
			r.push(CSRF_COOKIE_NAME + '=' + t);
			event.data = r.join("&");
		}
		else if (event.data instanceof FormData)
			event.data.append(CSRF_COOKIE_NAME, t);
		else event.data[CSRF_COOKIE_NAME] = t;
	});
	$.ajaxSetup({
		dataType: "json",
		error: function(_,name){
			if (name === 'abort')
				return;
			$.Dialog.fail(undefined, "Ismeretlen AJAX hiba");
		},
		statusCode: {
			401: function(){
				$.Dialog.fail(undefined, "Oldalak közötti kéréshamisítást érzékelt rendszerünk");
			},
			404: function(){
				$.Dialog.fail(undefined, "A kért oldal nem létezik");
			},
			500: function(){
				$.Dialog.fail(false, 'A kérés egy belső szerverhiba miatt nem sikerült.');
			},
		},
	});

	$body.on('scroll',function(){
		if (this.className.indexOf('dialog-open') !== -1)
			return false;
	});

	window.getUserSetting = function(key){
		var keys = key.split('.');
		return userSettings[keys[0]][keys[1]];
	};
})(jQuery);

$(function(){
	displayError('Meghívás','invitationErr');
});
