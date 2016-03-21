$(function(){
	var extConnHash = /(^|#)(?:_(?:=_)?)?$/;
	if (extConnHash.test(window.location.hash))
		window.history.replaceState({},'',window.location.href.replace(extConnHash,''));

	$('#sidebar').find('.avatar').children('img').on('error',function(){
		this.src = '/resources/img/user.svg';
	});

	$('#logout').on('click',function(e){
		e.preventDefault();

		var title = 'Kilépés a rendszerből';
		$.Dialog.confirm(title,'Biztosan ki szeretnél jelentkezni?',['Kijelentkezek','Belépve maradok'],function(sure){
			if (!sure) return;

			$.Dialog.wait(title);

			$.ajax({
				method: "POST",
				url: "/logout",
				data: pushToken({}),
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}
					if (data.status){
						$.Dialog.success(title,'Sikeresen kijelentkezett, átirányítjuk...'); // TODO üzenet visszaadása PHP-val
						window.location.href = '/';
					}
					else $.Dialog.fail(title,'Kijelentkezés nem sikerült, próbálja meg később, vagy törölje a böngésző sütijeit!');
				}
			})
		});
	});

	$('#exit').on('click',function(e){
		e.preventDefault();

		var title = 'Kilépés az osztályból';
		$.Dialog.wait(title);

		$.ajax({
			method: "POST",
			url: "/logout/exit",
			data: pushToken({}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					$.Dialog.success(title,'Sikeresen kilépett az osztályból, átirányítjuk...');
					setTimeout(function(){
							window.location.href = '/';
					},1500)
				}
				else $.Dialog.fail(title,'Kijelentkezés nem sikerült, próbálja meg később, vagy törölje a böngésző sütijeit!');
			}
		});
	});

	// Szerepkör-választás előkészítése
	var isOpenedBefore = false,
		$text = $('<p>Kérem válasszon az elérhető szerepkörök közül:</p>\
				<form id="js_form">\
				</form>');

	$('.avatar').children('.sessionswitch').on('click',function(e){
		var $listElement = $('<label style="text-align:left"><input type="radio" name="role" required> <b class="intezmeny"></b>: <span class="szerep"></span></label>');
		var availableRoles = {},
			title = "Szerepkör-választás",
			run = function(){
				$.Dialog.request(title,$text,'js_form','Szerepkör kiválasztása',function(){
					$('#js_form').on('submit',function(e){
						e.preventDefault();

						$.Dialog.wait(title,'Módosítások alkalmazása a munkameneten...');

						$.ajax({
							method: "POST",
							url: "/fooldal/roles/set",
							data: $('#js_form').serializeForm(),
							success: function(data){
								if (typeof data === 'string'){
									console.log(data);
									$(window).trigger('ajaxerror');
									return false;
								}
								if (data.status){
									$.Dialog.success(title,data.message);
									setTimeout(function(){
										window.location.href = '/';
									},1000);
								}
								else {
									$.Dialog.fail(title,data.message);
								}
							}
						});
					});
				});
			};

		if (!isOpenedBefore){
			isOpenedBefore = true;
			$.Dialog.wait(title,'Az elérhető szerepkörök lekérdezése folyamatban...');

			$.ajax({
				method: "POST",
				url: "/fooldal/roles/get",
				data: pushToken({}),
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}
					if (data.status){
						availableRoles = data.roles;

						var i = 0;
						for (i = 0; i < availableRoles.length; i++){
							var $input = $listElement.clone(),
								elem = availableRoles[i];

							$input.find('input').attr('value',elem.entryId);
							$input.find('.intezmeny').text(elem.intezmeny);
							$input.find('.szerep').text(elem.szerep);

							if (elem.active)
								$input.find('input[type=radio]').attr({disabled:true});

							$text.filter('form').append($input);
						}

						run();
					}
					else $.Dialog.fail(title,'Nem tudtuk lekérdezni az elérhető szerepkörök listáját! A szerepkör-választás pillanatnyilag nem lehetséges!');
				}
			});
		}
		else run();
	});
});