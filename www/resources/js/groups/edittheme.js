var title = 'Csoportkategória módosítása';

$('#dataform').on('submit',function(e){
	e.preventDefault();

	$.Dialog.wait(title);
	$.ajax({
		method: 'POST',
		data: $(this).serializeForm(),
		success: function(data){
			if (typeof data === 'string') return console.log(data) === $(window).trigger('ajaxerror');

			if (data.status)
				$.Dialog.success(title,data.message,true);

			else {
				$.Dialog.fail(title,data.message,true);
			}
		}
	});
});
