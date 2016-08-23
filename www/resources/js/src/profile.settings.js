$(function(){
	$('.settingsForm').on('submit',function(e){
		e.preventDefault();

		var title = 'Felhasználói beállításaim módosítása',
		data = $(this).serialize();

		$.Dialog.wait(title);

		$.ajax({
			method: 'POST',
			url: '/profile/settings',
			data: data,
			success: function(data){
				if (typeof data !== 'object'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status)
					$.Dialog.success(title,data.message,true);

				else $.Dialog.fail(title,data.message);
			}
		});
	});
});
