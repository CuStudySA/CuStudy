$(function(){
	var $formTempl = $("<form id='js_form'>\
							<p>Felhasználónév: <input type='text' name='username' placeholder='Felhasználónév' required></p>\
							<p>Teljes név: <input type='text' name='realname' placeholder='Vezetéknév Utónév' required></p>\
							<p>E-mail cím: <input type='text' name='email' placeholder='email@provider.mail' required></p>\
							<p>Jogosultság: \
								<select name='priv'>\
									<option value='user' selected>Ált. felhasználó</option>\
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
					data: $urlap.serialize(),
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
			data: {'id': id},
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				var $dialog = $formTempl.clone();

				$dialog.find('[name=username]').attr('value',data.username);
				$dialog.find('[name=realname]').attr('value',data.realname);
				$dialog.find('[name=email]').attr('value',data.email);
				$dialog.find('[name=priv]').children('option[value=' + data.priv + ']').attr('selected', true);
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
							data: $urlap.serialize(),
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

									var tagoltNev = $urlapelemek.find('[name=realname]').val().split(' ');

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
					data: $urlap.serialize(),
					success: function(data2){
						if (typeof data2 === 'string'){
							console.log(data2);
							$(window).trigger('ajaxerror');
							return false;
						}
						if (data2.status){
							var $elem = $tileTempl.clone(),
								$urlapelemek = $urlap.children();

							var tagoltNev = $urlapelemek.find('[name=realname]').val().split(' ');

							$elem.find('.vnev').text(tagoltNev.slice(0,1).toString());
							$elem.find('.knev').text(tagoltNev.slice(1).join(' '));
							$elem.find('.id').text('#' + data2.id);
							$elem.find('.js_user_edit').attr('href','#' + data2.id);
							$elem.find('.js_user_delete').attr('href','#' + data2.id);
							$elem.attr('data-id',data2.id);

							var $elemlista = $('ul');
							$elemlista.append($elem);

							var $newLessonTile = $('.new').detach();

							$elemlista.sortChildren('.vnev',false);
							$elemlista.append($newLessonTile);

							$('.js_user_add').on('click', e_user_add);
							$elem.find('.js_user_edit').on('click', e_user_edit);
							$elem.find('.js_user_delete').on('click', e_user_delete);

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

	$('.js_user_edit').on('click', e_user_edit);
	$('.js_user_add').on('click', e_user_add);
	$('.js_user_delete').on('click', e_user_delete);
	$('.js_user_editAccessData').on('click', e_user_editAccessData);
});
