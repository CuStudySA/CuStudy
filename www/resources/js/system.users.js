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

		var data = $(this).serializeForm(),
			title = 'Felhasználók szűrése';

		data.noSidebar = noSidebar;
		$.Dialog.wait();

		$.ajax({
			method: "POST",
			url: "/system.users/filter",
			data: data,
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					$('#resultContainer').empty().append(data.html);
					$.Dialog.close();
					$('.js_filterUsers').blur();
				}

				else $.Dialog.fail(title,data.message);
			}
		});
	});

	var $editForm = $("<form id='js_form'>\
						<p>Felhasználónév: <input type='text' name='username' placeholder='Felhasználónév' required></p>\
						<p>E-mail cím: <input type='text' name='email' placeholder='name@domain.tld' required></p>\
						<p>Teljes név: <input type='text' name='name' placeholder='Vezetéknév Keresztnév' required></p>\
						<input type='hidden' name='id' value=''>\
				</form>");

	var $chooseRoleForm = $("<form id='js_form'>\
							<p>Milyen műveletet szeretne végrehajtani?</p>\
							<label style='text-align:left'><input type='radio' name='action' value='edit' required checked> <strong>Szerkesztés</strong></label>\
							<label style='text-align:left'><input type='radio' name='action' value='delete' required id='roleDelete'> <strong>Törlés</strong></label>\
							\
							<p>Kérem válassza ki a szerkeszteni vagy törölni kívánt szerepkört:</p>\
							</form>\
	");

	var $editRoleForm = $("<form id='js_form_edit'>\
							<p>Jogosultság: \
								<select name='role'>\
									<option value='visitor' selected>Ált. felhasználó</option>\
									<option value='editor'>Szerkesztő</option>\
									<option value='admin'>Csoport adminisztrátor</option>\
								</select></p>\
								<input type='hidden' name='id'>\
							</form>");

	var $btEditForm = $("<form id='js_form'>\
							<p class='actual'><strong>Aktuális állapot: </strong><span></span></p>\
							<p>Válasszon műveletet:</p>\
							\
							<label style='text-align:left'>\
								<input type='radio' name='action' value='new' required> <strong>Hozzáférés létrehozása</strong>\
							</label>\
							<label style='text-align:left'>\
								<input type='radio' name='action' value='update' required> <strong>Meglévő hozzáférés frissítése</strong>\
							</label>\
							<label style='text-align:left'>\
								<input type='radio' name='action' value='remove' required> <strong>Hozzáférés törlése</strong>\
							</label>\
							\
						</form>");

	// Actions
	$('#js_editUserInfos').on('click',function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('data-id'),
			title = 'Felhasználó alapadatainak szerkesztése';

		$.Dialog.wait(title,'Alapadatok lekérdezése');

		$.ajax({
			method: "POST",
			url: "/system.users/get/userInfos",
			data: pushToken({'id': id}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					var $form = $editForm.clone();

					$form.find('[name=name]').attr('value',data.name);
					$form.find('[name=username]').attr('value',data.username);
					$form.find('[name=email]').attr('value',data.email);
					$form.find('[name=id]').attr('value',id);

					$.Dialog.request(title,$form,'js_form','Mentés',function($urlap){
						$urlap.on('submit',function(e){
							e.preventDefault();

							var data = $urlap.serializeForm();
							$.Dialog.wait(title);

							$.ajax({
								method: "POST",
								url: "/system.users/editBasicInfos",
								data: data,
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
										}, 1500)
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

	$('#js_deleteUser').on('click',function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('data-id'),
			title = 'Felhasználó eltávolítása a rendszerből';

		$.Dialog.confirm(title,'A felhasználó törlésével teljesen eltávolításra kerülnek a hozzá kapcsolódó információk és személyes adatok a rendszerből! Csak akkor töröljön egy felhasználót, ha az feltétlenül szükséges! Folytatja?',['Felhasználó törlése','Visszalépés'],function(action){
			if (!action) return;

			$.Dialog.wait(title);

			$.ajax({
				method: "POST",
				url: "/system.users/deleteUser",
				data: pushToken({'id': id}),
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}
					if (data.status){
						$.Dialog.success(title,data.message);
						setTimeout(function(){
							window.location.reload();
						}, 1500)
					}
					else $.Dialog.fail(title,data.message);
				}
			});
		});
	});

	$("#js_btEdit").on('click',function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('data-id'),
			title = 'BugTracker állapot szerkesztése';

		$.Dialog.wait(title,'Aktuális állapot lekérdezése');

		$.ajax({
			method: "POST",
			url: "/system.users/get/bugTrackerStatus",
			data: {'id': id},
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					var $form = $btEditForm.clone();

					for (var k in data.data){
						if (data.data.hasOwnProperty(k)){
							var v = data.data[k];
							$form.find('[value=' + k + ']').attr('disabled',!v);
						}
					}
					$form.find('span').text(data.conn_status);

					$.Dialog.request(title,$form,'js_form','Művelet végrehajtása',function(){
						var $form = $('#js_form');

						$form.on('submit',function(e){
							e.preventDefault();

							var labels = {
								'new': 'Arra készül, hogy létrehoz egy új felhasználót a BugTracker rendszerben a kiválasztott CuStudy-felhasználó jelenlegi adataival! Folytatja?',
								'update': 'Arra készül, hogy frissíti a kiválasztott CuStudy-felhasználóhoz kapcsolódó BugTracker felhasználó adatait a CuStudy rendszerben találhatóakkal! Folytatja?',
								'remove': 'Arra készül, hogy törli a kiválasztott CuStudy-felhasználóhoz kapcsolódó BugTracker-felhasználót a rendszerből! A művelet nem visszavonható! Folytatja?',
							};

							$.each($form.find('input'),function(_,e){
								var $e = $(e);

								if ($e.prop('checked')){
									var action = $e.attr('value');

									$.Dialog.confirm(title,labels[action],['Igen','Nem'],function(sure){
										if (!sure) return;

										$.Dialog.wait(title);
										$.ajax({
											method: "POST",
											url: "/system.users/editBugTrackerStatus",
											data: {'id': id, 'action': action},
											success: function(data){
												if (typeof data === 'string'){
													console.log(data);
													$(window).trigger('ajaxerror');
													return false;
												}
												if (data.status){
													$.Dialog.success(title,data.message);
													setTimeout(function(){
														window.location.reload();
													}, 1500)
												}
												else $.Dialog.fail(title,data.message);
											}
										});
									});
								}
							});
						});
					});
				}
			}
		});
	});

	$('#js_editRoles').on('click',function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('data-id'),
			title = 'Felhasználó szerepköreinek lekérdezése';

		$.Dialog.wait(title,'Az elérhető szerepkörök lekérdezése folyamatban');

		$.ajax({
			method: "POST",
			url: "/system.users/get/roles",
			data: pushToken({'id': id}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					var $listElement = $('<label style="text-align:left"><input type="radio" name="role" required> <b class="school"></b>: <span class="role"></span></label>');
					var i = 0,
						$text = $chooseRoleForm.clone(),
						availableRoles = data.roles;

					if (availableRoles.length == 0)
						return $.Dialog.fail(title,'Nincs szerkeszthető vagy törölhető szerepkör!');

					for (i = 0; i < availableRoles.length; i++){
						var $input = $listElement.clone(),
							elem = availableRoles[i];

						$input.find('input').attr('value',elem.id);
						$input.find('.school').text(elem.school);
						$input.find('.role').text(elem.role);

						$text.filter('form').append($input);
					}

					$.Dialog.request(title,$text,'js_form','Művelet végrehajtása',function(){
						var $form = $('#js_form');

						$form.on('submit',function(e){
							e.preventDefault();

							$form.find('input').attr('disabled',true);
							var $Role = $form.find(':checked').filter('[name=role]');

							if ($Role.length == 0)
								return $.Dialog.fail(title,'A művelet nem folytatható, mert nincs kiválasztott szerepkör!');

							// Törlés esetén
							if ($form.find('#roleDelete').prop('checked')){
								$.Dialog.confirm(title,'A kiválasztott szerepkör törlésére készül! Folytatja a műveletet?',['Szerepkör törlése','Visszalépés'],function(action){
									if (!action) return;

									$.Dialog.wait(title);

									$.ajax({
										method: "POST",
										url: "/system.users/deleteRole",
										data: pushToken({'id': $Role.attr('value'), 'userId': id}),
										success: function(data){
											if (typeof data === 'string'){
												console.log(data);
												$(window).trigger('ajaxerror');
												return false;
											}
											if (data.status){
												$.Dialog.success(title,data.message);
												setTimeout(function(){
													window.location.reload();
												}, 1500)
											}
											else $.Dialog.fail(title,data.message);
										}
									});
								});
							}

							// Módosítás esetén
							else {
								$.Dialog.wait(title,'Szerepkör adatainak lekérdezése');

								$.ajax({
									method: "POST",
									url: "/system.users/get/role",
									data: pushToken({'id': $Role.attr('value')}),
									success: function(data){
										if (typeof data === 'string'){
											console.log(data);
											$(window).trigger('ajaxerror');
											return false;
										}
										if (data.status){
											var $eForm = $editRoleForm.clone();
											$eForm.find('[name=role]').children('option[value=' + data.role + ']').attr('selected', true);
											$eForm.find('[name=id]').attr('value',$Role.attr('value'));

											$.Dialog.request(title,$eForm,'js_form_edit','Művelet végrehajtása',function(){
												var $formEdit = $('#js_form_edit');

												$formEdit.on('submit',function(e){
													e.preventDefault();

													$.Dialog.wait(title,'Módosítások mentése');

													$.ajax({
														method: "POST",
														url: "/system.users/editRole",
														data: $formEdit.serializeForm(),
														success: function(data){
															if (typeof data === 'string'){
																console.log(data);
																$(window).trigger('ajaxerror');
																return false;
															}
															if (data.status){
																$.Dialog.success(title,data.message);
																setTimeout(function(){
																	window.location.reload();
																},1000);
															}
															else {
																$.Dialog.fail(title,data.message);
															}
														}
													});
												});
											});
										}
										else $.Dialog.fail(title,data.message);
									}
								});
							}
						});
					});
				}
			}
		});
	});
});
