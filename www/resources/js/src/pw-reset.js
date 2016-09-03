$(function(){
	$('#pw-reset-form').on('submit',function(e){
		e.preventDefault();

		var data = $(this).serializeForm();
		$.Dialog.wait('Jelszóvisszaállítás', 'Új jelszó mentése');

		$.post('/pw-reset/reset', data, function(data){
			if (typeof data !== 'object'){
				console.log(data);
				$(window).trigger('ajaxerror');
				return false;
			}

			$.Dialog[data.status?'success':'fail'](false, data.message);
			if (!data.status) return;

			setTimeout(function(){
				window.location.href = '/';
			}, 2000)
		});
	})
});
