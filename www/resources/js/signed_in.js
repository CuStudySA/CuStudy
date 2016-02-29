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

	// Szerepkör-választás előkészítése
	var isOpenedBefore = false,
		$text = $('<form id="js_form"><p>Kérem válasszon az elérhető szerepkörök közül:</p></form>');

	$('.avatar').children('.sessionswitch').on('click',function(e){
		var $listElement = $('<label style="text-align:left"><input type="radio" name="role" required> <b class="intezmeny"></b>: <span class="szerep"></span></label>');
		var title = "Szerepkör-választás",
			run = function(){
				$.Dialog.request(title,$text,'js_form','Szerepkör kiválasztása',function($urlap){
					$urlap.on('submit',function(e){
						e.preventDefault();

						var data = $urlap.serializeForm();
						$.Dialog.wait(false, 'Módosítások alkalmazása a munkameneten');

						$.ajax({
							method: "POST",
							url: "/fooldal/roles/set",
							data: data,
							success: function(data){
								if (typeof data === 'string'){
									console.log(data);
									$(window).trigger('ajaxerror');
									return false;
								}

								if (!data.status) return $.Dialog.fail(false, data.message);

								$.Dialog.success(false, data.message);
								setTimeout(function(){
									window.location.href = '/';
								},1000);
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

					if (!data.status)
						return $.Dialog.fail(false, 'Nem tudtuk lekérdezni az elérhető szerepkörök listáját! A szerepkör-választás nem lehetséges!');

					$.each(data.roles,function(_,elem){
						var $input = $listElement.clone();

						$input.find('input').attr('value',elem.entryId).attr('disabled', elem.active == true);
						$input.find('.intezmeny').text(elem.intezmeny);
						$input.find('.szerep').text(elem.szerep);

						$text.append($input);
					});

					run();
				}
			});
		}
		else run();
	});
});
