$(function(){
	$('#js_hideShowFilter').on('click',function(){
		var $btn = $(this),
			$filterForm = $('#filterFormContainer');

		$btn.toggleClass('typcn-arrow-up-thick typcn-arrow-down-thick hide show');

		$filterForm[$btn.hasClass('hide') ? 'show' : 'hide']();
		$btn.text($btn.hasClass('hide') ? 'Szűrőpanel összecsukása' : 'Szűrőpanel kinyitása');

		$btn.blur();
	});

	$('#filterForm').on('submit',function(e){
		e.preventDefault();

		$.Dialog.wait();

		$.ajax({
			method: "POST",
			url: "/system.classes/filter",
			data: $('#filterForm').serializeForm(),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					$('#resultContainer').empty().append(data.html);
					$.Dialog.close();
					$('.js_filterClasses').blur();
				}

				else $.Dialog.fail(title,data.message);
			}
		});
	});

	var $editForm = $("<form id='js_form'>\
						<p>Osztály iskolai azonosítója: <input type='text' name='classid' placeholder='10.X' required></p>\
						<input type='hidden' name='id' value=''>\
					</form>");

	$('#js_editBasicInfos').on('click',function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('data-id'),
			title = 'Osztály alapadatainak szerkesztése';

		$.Dialog.wait(title,'Alapadatok lekérdezése...');

		$.ajax({
			method: "POST",
			url: "/system.classes/get/basicInfos",
			data: pushToken({'id': id}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					var $form = $editForm.clone();

					$form.find('[name=classid]').attr('value',data.classid);
					$form.find('[name=id]').attr('value',id);

					$.Dialog.request(title,$form,'js_form','Mentés',function(){
						var $urlap = $('#js_form');

						$urlap.on('submit',function(e){
							e.preventDefault();

							$.Dialog.wait(title);

							$.ajax({
								method: "POST",
								url: "/system.classes/editBasicInfos",
								data: $urlap.serializeForm(),
								success: function(data2){
									if (typeof data2 === 'string'){
										console.log(data2);
										$(window).trigger('ajaxerror');
										return false;
									}
									if (data2.status){
										$.Dialog.success(title,data2.message);
										setTimeout(function(){
											window.location.reload();
										},1500)
									}

									else $.Dialog.fail(title,data2.message);
								}
							});
						});
					});
				}

				else $.Dialog.fail(title,data.message);
			}
		});
	});

	$('#js_enterClass').on('click',function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('data-id'),
			title = 'Belépés az osztályba mint adminisztrátor';

		$.Dialog.confirm(title,'Arra készül, hogy belép a kiválasztott osztályba, mint az osztály adminisztrátora! Folytatja a műveletet?',['Belépés az osztályba','Visszalépés'],function(action){
			if (!action) return;

			$.Dialog.wait(title);

			$.ajax({
				method: "POST",
				url: "/system.classes/enterClass",
				data: pushToken({'classid': id}),
				success: function(data2){
					if (typeof data2 === 'string'){
						console.log(data2);
						$(window).trigger('ajaxerror');
						return false;
					}
					if (data2.status){
						$.Dialog.success(title,data2.message);
						setTimeout(function(){
							window.location.href = '/';
						},1500)
					}

					else $.Dialog.fail(title,data2.message);
				}
			});
		});
	});

	// Felhasználó hozzáadása az osztályhoz
	var $trTempl = $("<tr>\
						<td class='check'><input type='checkbox' data-id=''></td>\
						<td data-type='id'></td>\
						<td data-type='name'></td>\
						<td data-type='email'></td>\
						<td data-type='role' class='check'>\
							<select>\
								<option value='visitor'>Ált. felhasználó</option>\
								<option value='editor'>Szerkesztő</option>\
								<option value='admin'>Csoport adminisztrátor</option>\
							</select>\
						</td>\
					</tr>");

	$('#js_openUserSelector').on('click',function(e){
		e.preventDefault();
		$(e.currentTarget).blur();

		var selectorWindow = window.open('/system.popup','Felhasználók kiválasztása','height=720,width=800');

		window.response = function(data){
			selectorWindow.close();

			for (var i = 0; i < data.length; i++){
				var e = data[i];

				// Létezik már egy ilyen felhasználó a listában?
				if ($('input[data-id=' + e.id + ']').length != 0)
					continue;

				var $tr = $trTempl.clone();

				$tr.find('.check > input').attr('data-id', e.id).addClass('via-js');
				$tr.find('[data-type=id]').text(e.id);
				$tr.find('[data-type=name]').text(e.name);
				$tr.find('[data-type=email]').text(e.email);

				$('tbody .new').before($tr);
				$('.via-js').click(viajsClick);
			}
		};
	});

	// Javascripttel hozzáadott elemek törlése kattintásra
	var viajsClick = function(e){
		$(e.currentTarget).parent().parent().remove();
	};

	$('#js_sendForm').on('click',function(e){
		e.preventDefault();

		var title = 'Taglista módosítása',
			respond = [];
		$.Dialog.wait(title);

		$('input[type=checkbox]').each(function(_,e){
			var $e = $(e),
				$tr = $e.parent().parent();

			respond.push({
				'id': $e.attr('data-id'),
				'role' : $tr.find('[data-type="role"]').children('select').val(),
				'remove': $e.prop('checked') ? '1' : '0',
				'classid': $('input[name=classid]').val(),
			});
		});

		$.ajax({
			method: "POST",
			url: "/system.classes/manageMembers",
			data: pushToken({'data': respond}),
			success: function(data2){
				if (typeof data2 === 'string'){
					console.log(data2);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data2.status){
					$.Dialog.success(title,data2.message);
					setTimeout(function(){
						window.location.reload();
					},1500)
				}

				else $.Dialog.fail(title,data2.message);
			}
		});
	});
});