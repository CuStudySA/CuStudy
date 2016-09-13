$(function(){
	var $hWContent = $('section.homeworks');

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

	var $userdata = $('#sidebar').find('.userdata > :not(.avatar)');
	$w.on('resize',function(){
		if ($w.width()<650){
			let got = function(){
				$userdata.filter(':not(.marquee)').addClass('marquee').simplemarquee({
				    speed: 35,
				    cycles: Infinity,
				    space: 25,
				    handleHover: false,
				    delayBetweenCycles: 1000,
				    easing: 'ease-in-out',
				}).addClass('marquee');
			};
			if (typeof $.fn.simplemarquee === 'function')
				return got();

			$.ajax({
				url: '/resources/js/min/jquery.simplemarquee.js',
				dataType: "script",
				cache: true,
				success: got
			});
		}
	});
});
