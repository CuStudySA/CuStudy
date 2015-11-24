$(function(){
	$('#js_hideShowFilter').on('click',function(){
		var $btn = $(this),
			$filterForm = $('#filterFormContainer');

		$btn.toggleClass('typcn-arrow-up-thick typcn-arrow-down-thick hide show');

		$filterForm[$btn.hasClass('hide') ? 'show' : 'hide']();
		$btn.text($btn.hasClass('hide') ? 'Szűrőpanel összecsukása' : 'Szűrőpanel kinyitása');

		$btn.blur();
	});

	$('#filterForm').on('submit',function(e){
		e.preventDefault();

		$.Dialog.wait();

		$.ajax({
			method: "POST",
			url: "/system/users/filter",
			data: $('#filterForm').serializeForm(),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					$('#resultContainer').empty().append(data.html);
					$.Dialog.close();
					$('.js_filterUsers').blur();
				}

				else $.Dialog.fail(title,data.message);
			}
		});
	});
});