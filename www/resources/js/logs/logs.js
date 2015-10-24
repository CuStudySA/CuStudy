$(function(){
	$.fn.extractLogData = function(){
		var $this = $(this);

		return [
			$this.children('.entryid').text().trim(),
			$this.find('.timestamp time').attr('datetime'),
			$this.children('.ip').attr('data-realip'),
			$this.find('.reftype').attr('data-realtype')
		];
	};

	$('tr').each(function(){
		var $row = $(this);
		$row.find('.expand-section').on('click',function(){
			var $this = $(this),
				title = 'Naplóbejegyzés részletek lekérése';

			if ($this.hasClass('expanded')) $this.removeClass('expanded').next().stop().slideUp();
			else {
				if ($this.next().length === 1)
					$this.addClass('expanded').next().stop().slideDown();
				else {
					var logdata = $row.extractLogData();
					$.ajax({
						method: "POST",
						url: '/logs/details/'+logdata[0],
						data: pushToken({}),
						success: function(data){
							if (typeof data === 'string') return console.log(data) === $(window).trigger('ajaxerror');

							if (typeof data.status == 'undefined'){
								var $dataDiv = $(document.createElement('div')).attr('class','expandable-section').css('display','none');
								$.each(data.details,function(i,el){
									el[0] = '<strong>'+el[0]+(/[\wáéíóöőúüű]$/.test(el[0]) ? ':' : '')+'</strong>';

									if (typeof el[1] === 'boolean')
										el[1] = '<span class="'+(el[1]?'zold':'piros')+'">'+(el[1]?'igen':'nem')+'</span>';

									$dataDiv.append('<p>'+el.join(' ')+'</p>');
								});

								$dataDiv.insertAfter($this).slideDown();
								$this.addClass('expanded');
							}
							else $.Dialog.fail(title,data.message);
						}
					});
				}
			}
		});
	});

	$('.dynt-el').on('click',function(){
		var ww = $(window).width();
		if (ww < 650){
			var $this = $(this),
				$td = $this.parent(),
				$tr = $td.parent(),
				$ip = $tr.children('.ip').clone();

			$ip.children('.self').html(function(){
				return ' ('+$(this).text()+')';
			});
			$ip = $ip.html().split('<br>');

			$.Dialog.info($tr.children('.entryid').text()+'. bejegyzés rejtett adatai','\
				<b>Időpont:</b> '+$td.children('time').html().trim().replace(/<br>/,' ')+'\
				<span class="modal-ip"><br>\
					<b>Kezdeményező:</b> '+$ip[0]+'<br>\
					<b>IP Cím:</b> '+$(document.createElement('div')).html($ip[1]).text()+'\
				</span>'
			);
		}
	});
});
