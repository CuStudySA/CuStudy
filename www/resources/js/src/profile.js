$(function(){
	displayError();

	$('#dataform').on('submit',function(e){
		e.preventDefault();

		var title = "Felhasználói adataim módosítása",
			data = $(this).serializeForm();

		$.Dialog.wait(title);

		$.ajax({
			method: 'POST',
			url: '/profile/edit',
			data: data,
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

		var provider = $connSel.val();

		if (!provider)
			return $.Dialog.fail('Fiókok összekapcsolása','Fiókjának összekapcsolásához először válasszon ki egy szolgáltatót!');

		$.Dialog.confirm('Fiókok összekapcsolása','A fiókjának összekapcsolásához át kell irányítanunk Önt a szolgáltatójának weboldalára.<br>A sikeres azonosítás után a rendszer visszairányítja. Folytatja?',['Tovább a szolgáltatóhoz','Visszalépés'],
		function(sure){
			if (!sure) return;
			$.Dialog.wait(false, 'Átirányítjuk');
			window.location.href = '/profile/connect/' + provider;
		});
	});

	(function rebind(){
		'use strict';

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
					data: { id: $thisConn.attr('data-id') },
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
						data: { id: $thisConn.attr('data-id') },
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

				$.post('/profile/setavatarprovider',{ provider: provider },function(data){
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

		// Szerepkörök
		var e_changeDefault = function(e){
			e.preventDefault();

			var id = $(e.currentTarget).attr('data-id'),
				title = 'Szerepkör alapértelmezetté tétele';

			if (typeof id === 'undefined')
				throw new Error('Nincs megadva "data-id" paraméter!');

			$.Dialog.wait();

			$.ajax({
				method: 'POST',
				url: '/profile/roles/changeDefault',
				data: {'id': id},
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}

					if (data.status){
						$('.js_changeDefault').prop('disabled',false);
						$(e.currentTarget).prop('disabled',true);

						$.Dialog.close();
					}
					else $.Dialog.fail(title,data.message);
				}
			});
		};
		$('.js_changeDefault').on('click',e_changeDefault);

		var e_eject = function(e){
			e.preventDefault();

			var title = 'Szerepkör leválasztása';

			if ($(e.currentTarget).parent().find('.js_changeDefault').prop('disabled'))
				return $.Dialog.fail(title,'Az alapértelemeztt szerepkör leválasztása nem lehetséges! A leválasztás előtt jelöljön ki egy másik szerepkört alapértelmezettként!');

			var id = $(e.currentTarget).attr('data-id'),
				$dialog = $("<p>Arra készül, hogy leválasztja a kiválasztott szerepkört a fiókjáról. Ez azt jelenti, hogy egy osztálybeli szerepkör leválasztása esetén nem lesz képes a továbbiakban hozzáférni az osztályhoz!<br>\
							Ez a művelet nem visszavonható, így kérem, erősítse meg szándékát a jelszava begépelésével!</p>\
							<form id='js_form'>\
								<p><strong>Jelenlegi jelszava:</strong> <input type='password' name='password' required placeholder='Jelenlegi jelszava\
								'></p>\
								<input type='hidden' name='id'>\
							</form>");

			if (typeof id === 'undefined')
				throw new Error('Nincs megadva "data-id" paraméter!');

			$dialog.find('input[type=hidden]').attr('value',id);

			$.Dialog.request(title,$dialog,'js_form','Leválasztás',function($urlap){
				$urlap.on('submit',function(ev){
					ev.preventDefault();

					var data = $urlap.serializeForm();
					$.Dialog.wait();

					$.ajax({
						method: 'POST',
						url: '/profile/roles/eject',
						data: data,
						success: function(data){
							if (typeof data === 'string'){
								console.log(data);
								$(window).trigger('ajaxerror');
								return false;
							}

							if (data.status){
								if (data.reload == 1)
									return window.location.reload();

								$('.conn-wrap[data-id=' + id +']').remove();

								$.Dialog.close();
							}
							else $.Dialog.fail(title,data.message);
						}
					});
				});
			});
		};
		$('.js_eject').on('click',e_eject);
	})();

	// 2fa
	let $2fasection = $('#twofactor');
	$2fasection.on('click','#enable_2fa',function(e){
		e.preventDefault();

		let resumetext = ['Kétlépcsős azonosítás beállítása megszakítva', 'A beálltást bármikor újrakezdheted a Profilom menüpontban.'];
		$.Dialog.confirm('Kétlépcsős azonosítás beállítása - Megerősítés','Biztos vagy benne, hogy megkezded a kétlépcsős azonosítás beállítását?',function(sure){
			if (!sure) return;

			$.Dialog.close(function(){
				let $2FAStep1 = $.mk('div').attr({id:'twofa-step1','class':'twofa-step'}).append(
					`<h3>1. lépés <span class="faded">&rsaquo; 2. lépés &rsaquo; 3. lépés</span></h3>
					<p>Szerezd be a platformodhoz készült kétlépcsős azonostó alkalmazást, és nyisd meg azt.<br>Alább listázunk pár gyakran használt alkalmazást:</p>
					<ul>
						<li>Android: <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Google Authenticator</a></li>
						<li>iOS: <a href="https://itunes.apple.com/en/app/google-authenticator/id388497605?mt=8">Google Authenticator</a></li>
						<li>Windows Phone: <a href="https://www.microsoft.com/en-us/store/p/authenticator/9wzdncrfj3rj">Microsoft Authenticator</a></li>
					</ul>`
				);
				$.Dialog.confirm('Kétlépcsős azonosítás beállítása - 1. lépés',$2FAStep1,['Folytatás','Beállítás elvetése'],function(sure){
					if (!sure)
						return $.Dialog.apply($.Dialog, resumetext);

					$.Dialog.wait(false, 'Kommunikáció a szerverrel, kis türelmet');

					$.post('/profile/2fa?a=enable&step=2',function(data){
						if (typeof data === 'string'){
							console.log(data);
							$(window).trigger('ajaxerror');
							return false;
						}

						if (!data.status)
							return $.Dialog.fail(false, data.message);

						let secret = data.secret;

						$.Dialog.close(function(){
							let $2FAStep2 = $.mk('div').attr({id:'twofa-step2','class':'twofa-step'}).append(
								`<h3><span class="color-green">1. lépés</span> &rsaquo; 2. lépés <span class="faded">&rsaquo; 3. lépés</span></h3>
								<p>Az alkalmazásban keresd meg az új bejegyzés hozzáadása menüpontot, majd írd be az alábbi kódot:</p>
								<div class="twofa-code">${secret}</div>
								<p>Amennyiben az alkalmazásod támogatja, beszkennelheted a telefon kamerája segítségével az alábbi QR kódot is:</p>
								<div class="twofa-img">
									<img src="${data.qr}">
								</div>`
							);
							$.Dialog.confirm('Kétlépcsős azonosítás beállítása - 2. lépés',$2FAStep2,['Folytatás','Beállítás elvetése'],function(sure){
								if (!sure)
									return $.Dialog.info.apply($.Dialog, resumetext);

								$.Dialog.close(function(){
									let $2FAStep3 = $.mk('form').attr({id:'twofa-step3','class':'twofa-step'}).append(
										`<h3><span class="color-green">1. lépés &rsaquo; 2. lépés</span> &rsaquo; 3. lépés</h3>
										<p>A párosítás befejezéséhez add meg az alkalmazás által generált kódot:</p>
										<label>
											<input type="text" name="code" minlength="6" maxlength="6" pattern="^\\d{6}$" title="6 számjegyből álló kód" required autocomplete="off">
										</label>
										<p>A Mégse gombbal még megszakíthatod a beállítást, de ha az űrlapot elküldöd, a fiókodban engedélyezésre kerül a kétlépcsős autentikáció. Kapni fogsz 10 tartalék kódot, amit biztonságos helyen érdemes tárolnod (lehetőleg ne a számítógépen). Ezekkel tudsz hozzáférni a fiókodhoz, ha a hordoható eszközöd nincs kézél vagy ellopják.</p>`
									);
									$.Dialog.request('Kétlépcsős azonosítás beállítása - 3. lépés',$2FAStep3,'twofa-step3','Befejezés',function($form){
										$form.on('submit',function(e){
											e.preventDefault();

											var data = $form.serializeForm();
											data.secret = secret;
											$.Dialog.wait(false, 'Beállítás véglegesítése');

											$.post('/profile/2fa?a=enable&step=3',data,function(data){
												if (typeof data === 'string'){
													console.log(data);
													$(window).trigger('ajaxerror');
													return false;
												}

												if (!data.status)
													return $.Dialog.fail(false, data.message);

												$.Dialog.close(function(){
													if (data.twofactor_html)
														$('#twofactor').html(data.twofactor_html);
													$.Dialog.success('Kétlépcsős azonosítás beállítva',data.message,true);
												});
											});
										});
									});
								});
							});
						});
					});
				});
			});
		});
	});
	$2fasection.on('click','#disable_2fa',function(e){
		e.preventDefault();

		$.Dialog.confirm('Kétlépcsős azonosítás kikapcsolása','Biztos vagy benne, hogy kikapcsolod a kétlépcsős azonosítást?',function(sure){
			if (!sure) return;

			$.Dialog.wait(false, 'Kommunikáció a szerverrel, kis türelmet');

			$.post('/profile/2fa?a=disable',function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (!data.status)
					return $.Dialog.fail(false, data.message);

				if (data.twofactor_html)
					$('#twofactor').html(data.twofactor_html);
				$.Dialog.success(false, data.message, true);
			});
		});
	});
	$2fasection.on('click','#2fa_backupcodes',function(e){
		e.preventDefault();

		$.Dialog.wait(false, 'Kommunikáció a szerverrel, kis türelmet');

		$.post('/profile/2fa?a=codes',function(data){
			if (typeof data === 'string'){
				console.log(data);
				$(window).trigger('ajaxerror');
				return false;
			}

			let title = 'Kétlépcsős azonosítás - Tartalék kódok';
			if (!data.status)
				return $.Dialog.fail(title, data.message);

			(function showcodes(data){
				$.Dialog.info(
					title,
					`Ha elveszted a hordozható eszközöd, ezekkel be tudsz lépni a fiókodba az alkalmazás által generált kód helyett.
					${data.codes_html}
					<p>Ha bármilyen okból meg szeretnéd újítani ezeket a kódokat (elfogytak, rossz kezekbe kerültek) kattints az alábbi gombra:</p><button id="2fa_backupregen" class="btn typcn typcn-refresh">Kódok megújítása</button></p>`,
					function(){
						$('#2fa_backupregen').on('click',function(e){
							e.preventDefault();

							$.Dialog.close(function(){
								$.Dialog.confirm('Tartalék kódok megújítása','Biztosan le szeretnéd cserélni a mostani tartalék kódokat?',function(sure){
									if (!sure) return;

									$.Dialog.wait(false, 'Kommunikáció a szerverrel, kis türelmet');

									$.post('/profile/2fa?a=codes&regen',function(data){
										if (typeof data === 'string'){
											console.log(data);
											$(window).trigger('ajaxerror');
											return false;
										}

										if (!data.status)
											return $.Dialog.fail(false, data.message);

										$.Dialog.success(false, 'Kódok sikeresen újragenerálva');
										showcodes(data);
									})
								});
							});
						});
					}
				);
			})(data);
		});
	});
});
