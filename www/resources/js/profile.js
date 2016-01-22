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

var $connSel = $('#connect_s');
$('#connect').on('click',function(e){
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

$('.disconnect').on('click',function(e){
	e.preventDefault();

	var title = 'Fiókok leválasztása';
	$.Dialog.confirm(title,'A kiválasztott szolgáltatóhoz kapcsolódó fiókot leválasztja a CuStudy fiókjáról, így az nem lesz látható a listában és nem lehet bejelentkezéshez használni. A művelet nem visszavonható! Folytatja?',['Leválasztás','Mégse'],
	function(sure){
		if (!sure) return;

		$.Dialog.wait(title);

		$.ajax({
			method: 'POST',
			url: '/profile/unlink',
			data: pushToken({id: $(e.currentTarget).closest('.conn').attr('data-id')}),
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
	$.Dialog.confirm(title,'A kiválaszott szolgáltatóval annak újra aktiválásáig nem tud majd bejelentkezni. Folytatja?',['Deaktiválás','Mégse'],
	function(sure){
		if (!sure) return;

		$.Dialog.wait(title);

		$.ajax({
			method: 'POST',
			url: '/profile/deactivate',
			data: pushToken({id: $(e.currentTarget).closest('.conn').attr('data-id')}),
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
	$.Dialog.confirm(title,'A kiválaszott szolgáltató aktiválásával újra be tud majd jelentkezni vele. Folytatja?',['Aktiválása','Mégse'],
	function(sure){
		if (!sure) return;

		$.ajax({
			method: 'POST',
			url: '/profile/activate',
			data: {id: $(e.currentTarget).closest('.conn').attr('data-id')},
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
