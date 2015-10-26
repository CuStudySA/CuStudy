$('#dataform').on('submit',function(e){
	e.preventDefault();

	var title = "Felhasználói adataim módosítása";

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
				$('.name').text($('[name=realname]').val());
				$('.email').text($('[name=email]').val());
			}
			else $.Dialog.fail(title,data.message);
		}
	});
});

if($('#connect_s').children().length == 0)
	$('#connect_s').append("<option value='#'>(nincs elérhető szolg.)</option>");

$('#connect').on('click',function(e){
	e.preventDefault();

	if ($('#connect_s').find(':selected').attr('value') == '#') return $.Dialog.fail('Fiókok összekapcsolása','Fiókjának összekapcsolásához először válasszon ki egy szolgáltatót!');
	$.Dialog.confirm('Fiókok összekapcsolása','A fiókjának összekapcsolásához át kell irányítanunk Önt a szolgáltatójának weboldalára.<br>A sikeres azonosítás után a rendszer visszairányítja. Folytatja?',['Tovább a szolgáltatóhoz','Visszalépés'],
	function(sure){
		if (!sure) return;
		window.location.href = '/profile/connect/' + $('#connect_s').children().filter(':selected').eq(0).attr('value');
	});
});

$('.disconnect').on('click',function(e){
	e.preventDefault();

	var title = 'Fiókok leválasztása';
	$.Dialog.confirm(title,'Arra készül, hogy a kiválasztott szolgáltatóhoz kapcsolódó fiókot leválasztja a CuStudy fiókjáról. A művelet nem visszavonható! Folytatja?',['Fiók leválasztása','Visszalépés'],
	function(sure){
		if (!sure) return;

		$.ajax({
			method: 'POST',
			url: '/profile/unlink',
			data: pushToken({'id': $(e.currentTarget).attr('href').substring(1)}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status){
					$.Dialog.success(title,data.message);
					setTimeout(function(){
						window.location.href = '/profile';
					},2500);
				}
				else $.Dialog.fail(title,data.message);
			}
		});
	});
});

$('.deactivate').on('click',function(e){
	e.preventDefault();

	var title = 'Fiókkapcsolat deaktiválása';
	$.Dialog.confirm(title,'Arra készül, hogy deaktiválja a kiválaszott fiókkapcsolatot. Fiókja nem kerül leválasztásra, de az újbóli aktiválásig nem tud a kiválaszott szolgáltató segítségével bejelentkezni. Folytatja?',['Fiók deaktiválása','Visszalépés'],
	function(sure){
		if (!sure) return;

		$.ajax({
			method: 'POST',
			url: '/profile/deactivate',
			data: pushToken({'id': $(e.currentTarget).attr('href').substring(1)}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status){
					$.Dialog.success(title,data.message);
					setTimeout(function(){
						window.location.href = '/profile';
					},2500);
				}
				else $.Dialog.fail(title,data.message);
			}
		});
	});
});

$('.activate').on('click',function(e){
	e.preventDefault();

	var title = 'Fiókkapcsolat aktiválása';
	$.Dialog.confirm(title,'Arra készül, hogy aktiválja a kiválaszott fiókkapcsolatot, így a kiválasztott fiókkal újra be tud jelentkezni. Folytatja?',['Fiók aktiválása','Visszalépés'],
	function(sure){
		if (!sure) return;

		$.ajax({
			method: 'POST',
			url: '/profile/activate',
			data: pushToken({'id': $(e.currentTarget).attr('href').substring(1)}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status){
					$.Dialog.success(title,data.message);
					setTimeout(function(){
						window.location.href = '/profile';
					},2500);
				}
				else $.Dialog.fail(title,data.message);
			}
		});
	});
});
