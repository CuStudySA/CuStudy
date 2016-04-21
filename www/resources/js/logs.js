$(function(){
	$('.js_getDetails').on('click',function(e){
		e.preventDefault();

		var title = 'Naplóbejegyzés információinak lekérése',
			$this = $(this),
			id = $this.attr('data-id'),
			$td = $this.parent(),
			requestInProgress = false;

		if ($td.find('.expandable-section').length){
			$this.toggleClass('typcn-plus typcn-minus');
			$td.find('.expandable-section').stop()[!$this.hasClass('typcn-minus') ? 'slideUp' : 'slideDown']();
		}
		else {
			if (requestInProgress) return false;
			requestInProgress = true;

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
					var $append = $.mk('div').attr('class','expandable-section').css('display','none'),
						$global = $.mk('div').attr('class','global').html("<h3>Alapvető adatok</h3>"),
						$sub = $.mk('div').attr('class','sub').html("<h3>További adatok</h3>"),
						subEmpty = true;

					$.each(data.global,function(i,e){
						$global.append("<p><strong>" + i + "</strong>: " + e + "</p>");
					});
					$.each(data.sub,function(i,e){
						subEmpty = false;
						$sub.append("<p><strong>" + i + "</strong>: " + e + "</p>");
					});

					if (subEmpty)
						$sub.css('display','none');

					$append.append($global,$sub).appendTo($td).slideDown();

					$.Dialog.close();
				}
			});
		}
	});
});
