$(function(){
	var $formTempl = $(`<form id='js_form'>
						<p>Felhasználónév: <input type='text' name='username' placeholder='Felhasználónév' required disabled></p>
						<p>E-mail cím: <input type='text' name='email' placeholder='email@provider.mail' required disabled></p>
						<p>Jogosultság: 
							<select name='role'>
								<option value='visitor' selected>Ált. felhasználó</option>
								<option value='editor'>Szerkesztő</option>
								<option value='admin'>Csoport adminisztrátor</option>
								<!--<option value='teacher'>Tanár</option>-->
							</select></p>
						<input type='hidden' name='id' value=''>
				</form>`);

	var $tileTempl = $(`<li>
							<div class='top clearfix'>
								<div class='left'>
									<span class='typcn typcn-user'></span>
									<span class='id'></span>
								</div>
								<div class='right'>
									<span class='vnev'></span> <span class='knev'></span>
								</div>
							</div>
							<div class='bottom'>
								<a class='typcn typcn-edit js_user_edit' href='' title='Módosítás'></a>
								<a class='typcn typcn-media-eject js_user_eject' href='' title='Felhasználó osztálybeli szerepkörének törlése'></a>
							</div>
						</li>`);

	/////\\\\\ Nincs használatban! /////\\\\\
	// Patternek hozzácsatolása az űrlapelemekhez
	//if (typeof Patterns != undefined){
	//	$.each(Patterns,function(key,value){
	//		if (key != 'message' && key != 'status'){
	//			var $patternInput = $formTempl.find('[name=' + key + ']');
	//
	//			if ($patternInput.length)
	//				$patternInput.attr('pattern',value);
	//		}
	//	});
	//}

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
			var $name = $('[name=name]'),
				name = $name.val(),
				$email = $('[name=email]'),
				email = $email.val(),
				$ul_list = $('.l_l_utag'),
				nesreturn = false,
				title = "Felhasználók meghívása";

			var $nameRegExp = new RegExp($name.attr('pattern')),
				$emailRegExp = new RegExp($email.attr('pattern'));

			//Formátum ellenörzése
			if (!$nameRegExp.test(name)){
				$.Dialog.fail(title,"A felhasználó nevének formátuma nem megfelelő! Kérjük írjon be egy helyes nevet!");
				return;
			}
			if (!$emailRegExp.test(email)){
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
			$ul_list.append('<li data-email="'+ email +'" data-name="'+ name +'"><b>' + name + '</b> (' + email + ')<span class="typcn typcn-times l_l_deleteopt"></span></li>');

			//Beviteli mezők alaphelyzetbe állítása és üres jelzés eltávolítása
			$('.l_l_empty').remove();
			$name.val('');
			$email.val('');

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
				title = 'Felhasználók meghívása',
				$elemlista = $('.customers');

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
				data: {'invitations': invitations},
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}

					if (data.status){
						$clonedAddForm.remove();
						$clonedAddForm = undefined;

						for (var i = 0; i < data.enrolledUsers.length; i++){
							var item = data.enrolledUsers[i];

							var $elem = $tileTempl.clone(),
								tagoltNev = item.name.split(' ');

							$elem.find('.vnev').text(tagoltNev.slice(0,1).toString());
							$elem.find('.knev').text(tagoltNev.slice(1).join(' '));
							$elem.attr('data-id',item.id);
							$elem.find('.id').text('#' + item.id);
							$elem.find('.js_user_edit').attr('href','#' + item.id);
							$elem.find('.js_user_eject').attr('href','#' + item.id);

							$elemlista.append($elem);

							var $newLessonTile = $('.new').detach();

							$elemlista.sortChildren('.vnev',false);
							$elemlista.append($newLessonTile);

							$elem.find('.js_user_edit').on('click', e_user_edit);
							$elem.find('.js_user_eject').on('click', e_user_eject);
						}

						$.Dialog.success(title,data.message,true);
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

	var e_user_edit = function(e){
		e.preventDefault();

		var title = 'Felhasználó szerkesztése',
			id = $(e.currentTarget).attr('href').substring(1);

		$.ajax({
			method: "POST",
			url: "/users/get",
			data: {'id': id},
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				var $dialog = $formTempl.clone();

				$dialog.find('[name=username]').attr('value',data.username);
				$dialog.find('[name=email]').attr('value',data.email);
				$dialog.find('[name=role]').children('option[value=' + data.role + ']').attr('selected', true);
				$dialog.find('[name=id]').attr('value',id);

				$.Dialog.request(title,$dialog.prop('outerHTML'),'js_form','Mentés',function(){
					var $urlap = $('#js_form');

					$urlap.on('submit',function(e){
						e.preventDefault();

						var data = $urlap.serializeForm();
						$.Dialog.wait(title);

						$.ajax({
							method: "POST",
							url: "/users/edit",
							data: data,
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
			}
		});
	};
	$('.js_user_edit').on('click', e_user_edit);

	var e_user_eject = function(e){
		e.preventDefault();

		var title = 'Felhasználó szerepkörének leválasztása';
		$.Dialog.confirm(title,'Arra készülsz, hogy törlöd a kiválasztott felhasználó osztáybeli szerepkörét, azaz törlöd a felhasználót az osztályból! Biztosan folytatod?',['Felhasználó törlése az osztályból','Visszalépés'],function(sure){
			if (!sure) return;
			var id = $(e.currentTarget).attr('href').substring(1);
			$.Dialog.wait(title);

			$.ajax({
				method: "POST",
				url: "/users/eject",
				data: {'id':id},
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
	$('.js_user_eject').on('click', e_user_eject);
});
