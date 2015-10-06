$(function(){
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

	function getToken(){
		var token = getCookie('JSSESSID');
		if (typeof token == 'undefined') return '';

		return token;
	}

	window.pushToken = function(data){
		var token = getCookie('JSSESSID');
		if (typeof token == 'undefined') return data;

		data['JSSESSID'] = token;

		return data;
	}
});
