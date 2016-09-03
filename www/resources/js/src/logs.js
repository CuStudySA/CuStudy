$(function(){
	$('#logs').on('click','.dynt-el',function(){
		let ww = $w.width();
		if (ww >= 650)
			return true;

		let $this = $(this),
			$td = $this.parent(),
			$tr = $td.parent(),
			$ip = $tr.children('.ip');

		if ($ip.children('a').length){
			$ip = $ip.clone(true,true);
			$ip.children('.self').html(function(){
				return $(this).text();
			});
		}
		let $split = $ip.contents(),
			$span = $.mk('span').attr('class','modal-ip').append(
				'<br><b>Kezdeményező:</b> ',
				$split.eq(0)
			);
		if ($split.length > 1)
			$span.append(`<br><b>IP cím:</b> ${$split.get(2).textContent}`);

		$.Dialog.info(`${$tr.children('.entryid').text()}. bejegyzés rejtett adatai`,
			$.mk('div').append(
				`<b>Időpont:</b> ${$td.children('time').html().trim().replace(/<br>/,' ')}`,
				$span
			)
		);
	});
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
				data: {'id': id},
				success: function(data){
					if (typeof data !== 'object'){
						console.log(data);
						$.Dialog.fail(title,'A naplóbejegyzéshez tartozó adatok lekérése nem lehetséges, mert a szerver válasza érvénytelen volt!');
						return false;
					}

					if (!data.status)
						return $.Dialog.fail(title,data.message);

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
