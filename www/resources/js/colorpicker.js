$(function(){
	$("#colorpicker").spectrum({
	    showInput: true,
	    showInitial: true,
	    preferredFormat: "hex",
	    change: function(color) {
	        $("#colorpicker").attr("value",color.toHexString());
	    }
	});
});
