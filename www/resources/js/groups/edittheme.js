var title = 'Csoportkategória módosítása';

$('#dataform').on('submit',function(e){
	e.preventDefault();

	var data = $(this).serializeForm();
	$.Dialog.wait(title);

	$.ajax({
		method: 'POST',
		data: data,
		success: function(data){
			if (typeof data === 'string') return console.log(data) === $(window).trigger('ajaxerror');

			if (data.status)
				$.Dialog.success(title,data.message,true);
			else $.Dialog.fail(title,data.message);
		}
	});
});
