$(function(){
	$("#colorpicker").spectrum({
	    /*color: "#000000",*/
	    change: function(color) {
	        $("#colorpicker").attr("value",color.toHexString());
	    }
	});
});