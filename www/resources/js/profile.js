$('#dataform').on('submit',function(e){
	e.preventDefault();

	var title = "Felhasználói adataim módosítása";

	$.Dialog.wait(title);

	$.ajax({
		method: 'POST',
		url: '/profile/edit',
		data: $(this).serializeForm(),
		success: function(data){
			if (typeof data === 'string'){
				console.log(data);
				$(window).trigger('ajaxerror');
				return false;
			}

			if (data.status){
				$.Dialog.success(title,data.message,true);
				$('.name').text($('[name=name]').val());
				$('.email').text($('[name=email]').val());
			}
			else $.Dialog.fail(title,data.message);
		}
	});
	$('[name=oldpassword], [name=password], [name=verpasswd]').val('');
});

var $connSel = $('#connect_s'),
	$connBtn = $('#connect');
$connBtn.on('click',function(e){
	e.preventDefault();

	var title = 'Fiókok összekapcsolása',
		provider = $connSel.val();

	if (!provider)
		return $.Dialog.fail('Fiókok összekapcsolása','Fiókjának összekapcsolásához először válasszon ki egy szolgáltatót!');

	$.Dialog.confirm(title,'A fiókjának összekapcsolásához át kell irányítanunk Önt a szolgáltatójának weboldalára.<br>A sikeres azonosítás után a rendszer visszairányítja. Folytatja?',['Tovább a szolgáltatóhoz','Visszalépés'],
	function(sure){
		if (!sure) return;
		$.Dialog.wait(title,'Átirányítjuk...');
		window.location.href = '/profile/connect/' + provider;
	});
});

(function rebind(){
	'use setrict';

	$('.disconnect').off('click').on('click',function(e){
		e.preventDefault();

		var title = 'Fiókok leválasztása',
			$thisConn = $(this).closest('.conn-wrap'),
			$provIcon = $thisConn.find('.logo'),
			provDispName = $provIcon.attr('title'),
			provShortName = $thisConn.attr('data-prov');
		$.Dialog.confirm(title,'A kiválasztott szolgáltatóhoz kapcsolódó fiókot leválasztja a CuStudy fiókjáról, így az nem lesz látható a listában és nem lehet bejelentkezéshez használni. A művelet nem visszavonható! Folytatja?',['Leválasztás','Mégse'],
		function(sure){
			if (!sure) return;

			$.Dialog.wait(title);

			$.ajax({
				method: 'POST',
				url: '/profile/unlink',
				data: pushToken({ id: $thisConn.attr('data-id') }),
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}

					if (!data.status) return $.Dialog.fail(title,data.message);
					$.Dialog.close();

					$thisConn.remove();
					$connSel
						.append($.mk('option').attr('value', provShortName).text(provDispName))
						.closest('.conn-wrap').show();
					$connBtn.attr('disabled', false);
				}
			});
		});
	});

	$('.activeToggle').off('click').on('click',function(e){
		e.preventDefault();

		var whatDo = (this.className.indexOf('typcn-tick') !== -1 ? '' : 'de')+'activate',
			whatDoReadable = $.capitalize(whatDo.replace(/ate$/,'').replace('c','k')+'álás'),
			title = 'Fiókkapcsolat '+whatDoReadable.toLowerCase()+'a',
			text = whatDo === 'deactivate'
				? 'A kiválaszott szolgáltatóval annak újra aktiválásáig nem tud majd bejelentkezni. Folytatja?'
				: 'A kiválaszott szolgáltató aktiválásával újra be tud majd jelentkezni vele. Folytatja?',
			$button = $(this),
			$thisConn = $(this).closest('.conn-wrap');

		$.Dialog.confirm(title,text,[whatDoReadable,'Mégse'],
			function(sure){
				if (!sure) return;

				$.Dialog.wait(title);

				$.ajax({
					method: 'POST',
					url: '/profile/'+whatDo,
					data: pushToken({ id: $thisConn.attr('data-id') }),
					success: function(data){
						if (typeof data === 'string'){
							console.log(data);
							$(window).trigger('ajaxerror');
							return false;
						}

						if (!data.status) return $.Dialog.fail(title,data.message);
						$.Dialog.close();

						$button.toggleClass('typcn-power typcn-tick').toggleHtml(['Aktiválás','Deaktiválás']);
						$button.parent().prev().html(function(){
							return this.innerHTML.replace(/^(Ina|A)/,function(m){
								return m === 'A' ? 'Ina' : 'A';
							});
						});
					}
				});
			});
	});

	$('.makepicture').off('click').on('click',function(e){
		e.preventDefault();

		var title = 'Profilkép szolgáltató megváltoztatása',
			$connWrap = $(this).closest('.conn-wrap'),
			providerDisk = $connWrap.find('.logo').attr('title'),
			provider = $connWrap.attr('data-prov');
		$.Dialog.confirm(title, 'A profilkép szolgáltató módosításával megváltoztathatod, hogy melyik kép jelenjen meg az oldalon a neved mellett.<br>Átváltasz a(z) '+providerDisk+' szolgáltatónál használt profilképre?', ['Váltás','Maradjon a régi'], function(sure){
			if (!sure) return;

			$.Dialog.wait(title);

			$.post('/profile/setavatarprovider',pushToken({ provider: provider }),function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (!data.status) return $.Dialog.fail(title,data.message);

				var isGravatar = typeof provider === 'undefined',
					$connParent = $connWrap.parent(),
					$gravatarConn = $connParent.children().last();
				$connParent.children('[data-id]').remove();
				$(data.connwraps).insertBefore($gravatarConn);
				rebind();

				$gravatarConn
					.find('.status').text(isGravatar ? 'Jelenlegi profilkép' : 'Nincs használatban')
					.next().children('.makepicture').attr('disabled', isGravatar);
				$('#sidebar').find('.avatar img').attr('src', data.picture);
				$.Dialog.close();
			});
		});
	});
})();
