$(function(){
	$('#pw-reset-form').on('submit',function(e){
		e.preventDefault();

		var title = 'Jelszóvisszaállítás';

		$.Dialog.wait(title, 'Új jelszó mentése');

		$.post('/pw-reset', $(this).serializeForm(), function(data){
			if (typeof data !== 'object'){
				console.log(data);
				$(window).trigger('ajaxerror');
				return false;
			}

			$.Dialog[data.status?'success':'fail'](title, data.message);
			if (!data.status) return;

			setTimeout(function(){
				window.location.href = '/';
			}, 2000)
		});
	})
});
