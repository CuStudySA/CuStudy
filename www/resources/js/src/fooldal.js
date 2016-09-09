$(function(){
	var $hWContent = $('.hWContent');

	var makeMarkedDone = function(e){
		e.preventDefault();

		var $elem = $(e.currentTarget),
			id = $elem.attr('href').substring(1),
		    title = 'Házi feladat késznek jelölése';

		$.Dialog.wait(title);

		$.ajax({
			method: "POST",
			data: {'id': id},
			url: '/homeworks/makeMarkedDone/mainPage',
			dataType: 'html',
			success: function(data){
				$hWContent.empty().append(data);
				$('.js_makeMarkedDone').click(makeMarkedDone);

				$.Dialog.close();
			}
		});
	};
	$('.js_makeMarkedDone').click(makeMarkedDone);
});
