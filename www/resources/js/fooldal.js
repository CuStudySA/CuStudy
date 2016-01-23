$(function(){
	// Amber flag start
	$.each($('link'),function(i,e){
		if ($(e).attr('href').indexOf('amber') != -1)
			$(e).remove();
	});
	$('main > p').remove();
	// Amber flag end

	var $hWContent = $('.hWContent');

	var makeMarkedDone = function(e){
		e.preventDefault();

		var $elem = $(e.currentTarget),
			id = $elem.attr('href').substring(1),
		    title = 'Házi feladat késznek jelölése';

		$.Dialog.wait(title);

		$.ajax({
			method: "POST",
			data: pushToken({'id': id}),
			url: '/homeworks/makeMarkedDone/mainPage',
			success: function(data){
				$hWContent.empty().append(data);
				$('.js_makeMarkedDone').click(makeMarkedDone);

				$.Dialog.close();
			}
		});
	};
	$('.js_makeMarkedDone').click(makeMarkedDone);
});