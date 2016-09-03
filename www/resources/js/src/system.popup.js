$(function(){
	$('#selectUsers').on('click',function(e){
		e.preventDefault();

		var data = [];

		$('input:checked').each(function(_,e){
			var $e = $(e),
				$tr = $e.parent().parent();

			data.push({
				'id': $tr.find('[data-type=id]').text(),
				'name': $tr.find('[data-type=name]').text(),
				'email': $tr.find('[data-type=email]').text(),
			});
		});

		window.opener.response(data);
	});
});