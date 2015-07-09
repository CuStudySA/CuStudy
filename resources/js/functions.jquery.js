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