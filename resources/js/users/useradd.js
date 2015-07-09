$(function(){
	$('#useradd').on('submit',function(e){
		e.preventDefault();

		var title = "Felhasználó hozzáadása";

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
						window.location.href = '/users';
					},2500);
				}
				else $.Dialog.fail(title,data.message);
			}
		});
	});
});