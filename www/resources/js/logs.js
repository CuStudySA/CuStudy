$(function(){
	$('.js_getDetails').on('click',function(e){
		e.preventDefault();

		var title = 'Naplóbejegyzés információinak lekérése',
			id = $(e.currentTarget).attr('data-id'),
			$td = $(e.currentTarget).parent(),
			$this = $(e.currentTarget);

		if ($td.find('.expandable-section').length == 1){
			$this.toggleClass('typcn-plus typcn-minus');
			$td.find('.expandable-section')[!$this.hasClass('typcn-minus') ? 'hide' : 'show']();
		}
		else {
			$.Dialog.wait(title);

			$.ajax({
				method: "POST",
				url: '/logs/getDetails',
				data: pushToken({'id': id}),
				success: function(data){
					if (typeof data !== 'object'){
						console.log(data);
						$.Dialog.fail(title,'A naplóbejegyzéshez tartozó adatok lekérése nem lehetséges, mert a szerver válasza érvénytelen volt!');
						return false;
					}

					$this.toggleClass('typcn-plus typcn-minus');
					var $append = $('<div class="expandable-section"><div class="global"><h3>Alapvető adatok</h3></div><div class="sub"><h3>További adatok</h3></div></div>');
					$.each(data.global,function(i,e){
						$append.find('.global').append("<p><strong>" + i + "</strong>: " + e + "</p>");
					});
					$.each(data.sub,function(i,e){
						$append.find('.sub').append("<p><strong>" + i + "</strong>: " + e + "</p>");
					});

					$(e.currentTarget).parent().append($append);

					$.Dialog.close();
				}
			});
		}
	});
});