$(function(){
	var $formTempl = $("<form id='js_form'>\
							<p>Felhasználónév: <input type='text' name='username' placeholder='Felhasználónév' required></p>\
							<p>Teljes név: <input type='text' name='name' placeholder='Vezetéknév Utónév' required></p>\
							<p>E-mail cím: <input type='text' name='email' placeholder='email@provider.mail' required></p>\
							<p>Jogosultság: \
								<select name='role'>\
									<option value='visitor' selected>Ált. felhasználó</option>\
									<option value='editor'>Szerkesztő</option>\
									<option value='admin'>Csoport adminisztrátor</option>\
								</select></p>\
							<p>Aktív legyen?\
								<select name='active'>\
									<option value='1' selected>Igen</option>\
									<option value='0'>Nem</option>\
								</select></p>\
							<input type='hidden' name='id' value=''>\
					</form>");

	var $tileTempl = $("<li>\
							<div class='top clearfix'>\
								<div class='left'>\
									<span class='typcn typcn-user'></span>\
									<span class='id'></span>\
								</div>\
								<div class='right'>\
									<span class='vnev'></span> <span class='knev'></span>\
								</div>\
							</div>\
							<div class='bottom'>\
								<a class='typcn typcn-pencil js_user_edit' href='' title='Módosítás'></a>\
								<a class='typcn typcn-key js_user_editAccessData' href='' title='Hozzáférési adatok módosítása'></a>\
								<a class='typcn typcn-user-delete js_user_delete' href='' title='Törlés'></a>\
							</div>\
						</li>");

	var $accessFormTempl = $("<form id='js_form'>\
								<p>Új jelszó: <input type='password' name='newpassword' placeholder='Jelszó' required></p>\
								<p>Jelszó megerősítése: <input type='password' name='vernewpasswd' placeholder='Jelszó megerősítése' required></p>\
								<input type='hidden' name='id' value=''>\
							</form>");

	/*$.ajax({
		method: "POST",
		url: "/users/getPatterns",
		success: function(data){
			if (typeof data === 'string'){
				console.log(data);
				$(window).trigger('ajaxerror');
				return false;
			}

			$.each(data,function(key,value){
				if (key != 'message' && key != 'status')
					$formTempl.find('[name=' + key + ']').attr('pattern',value);
			});
		}
	});*/

	// Patternek hozzácsatolása az űrlapelemekhez
	if (typeof Patterns != undefined){
		$.each(Patterns,function(key,value){
			if (key != 'message' && key != 'status'){
				var $patternInput = $formTempl.find('[name=' + key + ']');
				var $accessPatternInput = $accessFormTempl.find('[name=' + key + ']');

				if ($patternInput.length)
					$patternInput.attr('pattern',value);

				if ($accessPatternInput.length)
					$accessPatternInput.attr('pattern',value);
			}
		});
	}

	var $addForm = $('.invite_form').detach().css('display','block'),
					$clonedAddForm;

	var e_invite = function(e){
		e.preventDefault();

		if ($clonedAddForm instanceof jQuery) $clonedAddForm.remove();
		$clonedAddForm = $addForm.clone();

		$('main').append($clonedAddForm);

		$(document.body).animate({scrollTop: $clonedAddForm.offset().top - 10 }, 500);

		/* Hozzáadás gomb eseménye */
		$('.addlesson').click(function(){
			var $name = $('[name=name]').val(),
				$email = $('[name=email]').val(),
				$ul_list = $('.l_l_utag'),
				nesreturn = false,
				title = "Felhasználók meghívása";

			var $nameRegExp = new RegExp($('[name=name]').attr('pattern')),
				$emailRegExp = new RegExp($('[name=email]').attr('pattern'));

			//Formátum ellenörzése
			if (!$nameRegExp.test($name)){
				$.Dialog.fail(title,"A felhasználó nevének formátuma nem megfelelő! Kérjük írjon be egy helyes nevet!");
				return;
			}
			if (!$emailRegExp.test($email)){
				$.Dialog.fail(title,"Az e-mail cím formátuma nem megfelelő! Kérjük írjon be egy helyes e-mail címet!");
				return;
			}

			//Létezik-e már ilyen elem?
			$.each($ul_list.children(),function(i,entry){
				var lesname = $(entry).attr('data-email');

				if (lesname == $email){
					$.Dialog.fail(title,"Már hozzáadtad ezt a felhasználót!");
					nesreturn = true;
					$('[name=name]').val('');
					$('[name=email]').val('');
					return;
				}
			});
			if (nesreturn) return;

			//Hozzáadás a listához
			$('.l_l_utag').append('<li data-email="'+ $email +'" data-name="'+ $name +'"><b>' + $name + '</b> (' + $email + ')<span class="typcn typcn-times l_l_deleteopt"></span></li>');

			//Beviteli mezők alaphelyzetbe állítása és üres jelzés eltávolítása
			$('.l_l_empty').remove();
			$('[name=name]').val('');
			$('[name=email]').val('');

			/* Törlés eseménye */
			$('.l_l_deleteopt').on('click',function(){
				var $li = $(this).parent(),
					$ul = $('.l_l_utag');

				$li.remove();

				if ($ul.children().size() == 0)
					$ul.append('<li class="l_l_empty">(nincs)</li>');
			});
		});

		var invitations = [], adding = false;
		/* Elküldés gomb eseménye */
		$('.a_t_f_sendButton').click(function(){
			if (adding === true) return;
			adding = true;

			var $ul = $('.l_l_utag'),
				title = 'Felhasználók meghívása';

			//Meghívottak listájának előkészítése
			$.each($ul.children(),function(i,entry){
				if ($(entry).hasClass('l_l_empty')) return;
				var name = $(entry).attr('data-name'),
					email = $(entry).attr('data-email');

				invitations.push({'name': name, 'email': email});
			});

			$.Dialog.wait(title);

			//Kommunikáció a szerverrel
			$.ajax({
				method: 'POST',
				url: '/users/invite',
				data: pushToken({'invitations': invitations}),
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}

					if (data.status){
						$.Dialog.success(title,data.message,true);

						$clonedAddForm.remove();
						$clonedAddForm = undefined;

						$.Dialog.close();
					}
					else $.Dialog.fail(title,data.message);
				},
				complete: function(){
					adding = false;
				}
			});
		});
	};
	$('.js_invite').on('click',e_invite);

	var e_user_editAccessData = function(e){
		e.preventDefault();

		var title = 'Felhasználó szerkesztése',
			id = $(e.currentTarget).attr('href').substring(1);

		var $dialog = $accessFormTempl.clone();

		$dialog.find('[name=id]').attr('value',id);

		$.Dialog.request(title,$dialog.prop('outerHTML'),'js_form','Mentés',function(){
			var $urlap = $('#js_form');

			$urlap.on('submit',function(e){
				e.preventDefault();

				$.Dialog.wait(title);

				$.ajax({
					method: "POST",
					url: "/users/editAccessData",
					data: $urlap.serializeForm(),
					success: function(data2){
						if (typeof data2 === 'string'){
							console.log(data2);
							$(window).trigger('ajaxerror');
							return false;
						}
						if (data2.status)
							$.Dialog.close();

						else $.Dialog.fail(title,data2.message);
					}
				});
			});
		});
	};

	var e_user_edit = function(e){
		e.preventDefault();

		var title = 'Felhasználó szerkesztése',
			id = $(e.currentTarget).attr('href').substring(1);

		$.ajax({
			method: "POST",
			url: "/users/get",
			data: pushToken({'id': id}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				var $dialog = $formTempl.clone();

				$dialog.find('[name=username]').attr('value',data.username).attr('disabled','true');
				$dialog.find('[name=name]').attr('value',data.name);
				$dialog.find('[name=email]').attr('value',data.email);
				$dialog.find('[name=role]').children('option[value=' + data.role + ']').attr('selected', true);
				$dialog.find('[name=active]').children('option[value=' + data.active + ']').attr('selected', true);
				$dialog.find('[name=id]').attr('value',id);

				$.Dialog.request(title,$dialog.prop('outerHTML'),'js_form','Mentés',function(){
					var $urlap = $('#js_form');

					$urlap.on('submit',function(e){
						e.preventDefault();

						$.Dialog.wait(title);

						$.ajax({
							method: "POST",
							url: "/users/edit",
							data: $urlap.serializeForm(),
							success: function(data2){
								if (typeof data2 === 'string'){
									console.log(data2);
									$(window).trigger('ajaxerror');
									return false;
								}
								if (data2.status){
									var $elemlista = $('ul'),
										$elem = $elemlista.children('[data-id=' + id + ']'),
										$urlapelemek = $urlap.children();

									var tagoltNev = $urlapelemek.find('[name=name]').val().split(' ');

									$elem.find('.vnev').text(tagoltNev.slice(0,1).toString());
									$elem.find('.knev').text(tagoltNev.slice(1).join(' '));

									var $newLessonTile = $('.new').detach();

									$elemlista.sortChildren('.vnev',false);
									$elemlista.append($newLessonTile);

									$.Dialog.close();
								}

								else $.Dialog.fail(title,data2.message);
							}
						});
					});
				});
			}
		});
	};

	var e_user_add = function(e){
		e.preventDefault();

		var title = 'Felhasználó hozzáadás',
			$dialog = $formTempl.clone();

		$dialog.find('[name=id]').remove();

		$.Dialog.request(title,$dialog.prop('outerHTML'),'js_form','Mentés',function(){
			var $urlap = $('#js_form');

			$urlap.on('submit',function(e){
				e.preventDefault();

				$.Dialog.wait(title);

				$.ajax({
					method: "POST",
					url: "/users/add",
					data: $urlap.serializeForm(),
					success: function(data2){
						if (typeof data2 === 'string'){
							console.log(data2);
							$(window).trigger('ajaxerror');
							return false;
						}
						if (data2.status){
							var $elem = $tileTempl.clone(),
								$urlapelemek = $urlap.children();

							var tagoltNev = $urlapelemek.find('[name=name]').val().split(' ');

							$elem.find('.vnev').text(tagoltNev.slice(0,1).toString());
							$elem.find('.knev').text(tagoltNev.slice(1).join(' '));
							$elem.find('.id').text('#' + data2.id);
							$elem.find('.js_user_edit').attr('href','#' + data2.id);
							$elem.find('.js_user_delete').attr('href','#' + data2.id);
							$elem.find('.js_user_editAccessData').attr('href','#' + data2.id);
							$elem.attr('data-id',data2.id);

							var $elemlista = $('ul');
							$elemlista.append($elem);

							var $newLessonTile = $('.new').detach();

							$elemlista.sortChildren('.vnev',false);
							$elemlista.append($newLessonTile);

							$elem.find('.js_user_edit').on('click', e_user_edit);
							$elem.find('.js_user_delete').on('click', e_user_delete);
							$elem.find('.js_user_editAccessData').on('click', e_user_editAccessData);

							$.Dialog.close();
						}

						else $.Dialog.fail(title,data2.message);
					}
				});
			});
		});
	};

	var e_user_delete = function(e){
		e.preventDefault();

		var title = 'Felhasználó törlése';
		$.Dialog.confirm(title,'Biztosan törölni szeretnéd a felhasználót? A művelet nem visszavonható!',['Felh. törlése','Visszalépés'],function(sure){
			if (!sure) return;
			$.Dialog.wait();

			var id = $(e.currentTarget).attr('href').substring(1);

			$.ajax({
				method: "POST",
				url: "/users/delete",
				data: pushToken({'id':id}),
				success: function(data){
					if (data.status){
						$(e.currentTarget).parent().parent().remove();
						$.Dialog.close();
					}
					else $.Dialog.fail(title,data.message);
				}
			})
		});
	};

	$('.js_user_edit').on('click', e_user_edit);
	$('.js_user_add').on('click', e_user_add);
	$('.js_user_delete').on('click', e_user_delete);
	$('.js_user_editAccessData').on('click', e_user_editAccessData);
});
