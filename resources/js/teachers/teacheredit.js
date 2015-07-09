$(function(){
	$('.sendeditform').on('submit',function(e){
		e.preventDefault();

		var title = "Tanár adatainak szerkesztése";

		$.Dialog.wait(title);
		$.ajax({
			method: 'POST',
			data: $(this).serialize(),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status){
					$.Dialog.success(title,data.message);
					setTimeout(function(){
						window.location.href = '/teachers';
					},2500);
				}
				else $.Dialog.fail(title,data.message);
			}
		});
	});
});
