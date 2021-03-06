$(function(){
	var $formTempl = $("<form id='js_form'>\
							<p>Kategória neve: <input type='text' name='name' placeholder='Kategória neve' required></p>\
					  </form>"),
		$tileTempl = $("<li>\
							<div class='top'>\
								<span class='rovid'></span>\
								<span class='nev'></span>\
							</div>\
							<div class='bottom'>\
								<a class='typcn typcn-pencil js_thm_edit' title='Módosítás'></a>\
								<a class='typcn typcn-trash js_thm_del' title='Törlés'></a>\
							</div>\
					   </li>"),
		$listTempl = $("<div><h2 class='grouptitle'></h2>\
						<ul class='groups colorli'>\
							<li>\
								<div class='top'>\
									<span class='rovid'>Új csop.</span>\
									<span class='nev newTile'>Új csoport hozzáadása</span>\
								</div>\
								<div class='bottom'>\
									<a class='typcn typcn-plus' href='/groups/add/' title='Hozzáadás'></a>\
								</div>\
							</li>\
						</ul></div>"),
		$grpCont = $('#groupContainer');

	$('.js_grp_del').on('click',function(e){
		e.preventDefault();

		var title = 'Csoport törlése';
		$.Dialog.confirm(title,'Biztosan törlöd a csoportot? Ha a csoportnak vannak tagjai, a rendszer automatikusan kilépteti őket.',['Csop. törlése','Visszalépés'],function(sure){
			if (!sure) return;
			var id = $(e.currentTarget).attr('href').substring(1);

			$.ajax({
				method: "POST",
				url: "/groups/delete",
				data: {'id':id},
				success: function(data){
					if (data.status){
						$.Dialog.success(title,data.message);
						window.location.href = '/groups';
					}
					else $.Dialog.fail(title,data.message);
				}
			})
		});
	});

	var e_thm_edit = function(e){
		e.preventDefault();

		var title = 'Csoportkategória szerkesztése',
			id = $(e.currentTarget).attr('href').substring(1);

		$.Dialog.wait(title,'Információk lekérése a szerverről');

		$.ajax({
			method: "POST",
			url: "/groups/theme/get",
			data: {'id': id},
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				var $dialog = $formTempl.clone();

				$dialog.find('[name=name]').attr('value',data.name);

				$.Dialog.request(title,$dialog,'js_form','Mentés',function($urlap){
					$urlap.on('submit',function(e){
						e.preventDefault();

						var data = $urlap.serializeForm();
						data.id = id;
						$.Dialog.wait(title);

						$.post({
							method: "POST",
							url: "/groups/theme/edit",
							data: data,
							success: function(data2){
								if (typeof data2 === 'string'){
									console.log(data2);
									$(window).trigger('ajaxerror');
									return false;
								}
								if (data2.status){
									var $elemlista = $('.grps'),
										$elem = $elemlista.children('[data-id=' + id + ']'),
										$urlapelemek = $urlap.children(),
										newName = $urlapelemek.find('[name=name]').val();

									$elem.find('.rovid').text(newName + ' kat.');

									var $newLessonTile = $('.new').detach();

									$elemlista.sortChildren('.rovid',false);
									$elemlista.append($newLessonTile);

									$('h2[data-thm="' + id + '"]').text(newName + " csoportok");
									$('div[data-thm="' + id + '"]').find('ul').find('.nev:not(.newTile)').text(newName);

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
	$('.js_thm_edit').on('click',e_thm_edit);

	var e_thm_add = function(e){
		e.preventDefault();

		var title = 'Csoportkategória hozzáadása',
			$dialog = $formTempl.clone();

		$.Dialog.request(title,$dialog.prop('outerHTML'),'js_form','Mentés',function($urlap){
			$urlap.on('submit',function(e){
				e.preventDefault();

				var data = $urlap.serializeForm();
				$.Dialog.wait(title);

				$.ajax({
					method: "POST",
					url: "/groups/theme/add",
					data: data,
					success: function(data2){
						if (typeof data2 === 'string'){
							console.log(data2);
							$(window).trigger('ajaxerror');
							return false;
						}
						if (data2.status){
							var $elem = $tileTempl.clone(),
								$urlapelemek = $urlap.children(),
								newName = $urlapelemek.find('[name=name]').val();

							$elem.find('.rovid').text(newName + ' kat.');
							$elem.find('.js_thm_edit').attr('href','#' + data2.id);
							$elem.find('.js_thm_del').attr('href','#' + data2.id);
							$elem.attr('data-id',data2.id);

							var $elemlista = $('.grps');
							$elemlista.append($elem);

							var $newLessonTile = $('.new').detach();

							$elemlista.sortChildren('.rovid',false);
							$elemlista.append($newLessonTile);

							$elem.find('.js_thm_edit').on('click', e_thm_edit);
							$elem.find('.js_thm_del').on('click', e_thm_del);

							// Nincs csop.kat. üzenet eltávolítása
							$('.notice').hide();

							// Csop.kat. hozzáadása a felülethez
							var $templ = $listTempl.clone();
							$templ.find('.grouptitle').text(newName + ' csoportok').attr('data-thm',data2.id);
							$templ.find('.bottom').children().eq(0).attr('href','/groups/add/' + data2.id);
							$templ.filter('div').attr('data-thm',data2.id);
							$grpCont.append($templ);

							$.Dialog.close();
						}

						else $.Dialog.fail(title,data2.message);
					}
				});
			});
		});
	};
	$('.js_thm_add').on('click',e_thm_add);

	var e_thm_del = function(e){
		e.preventDefault();

		var title = 'Csoportkategória törlése';

		$.Dialog.confirm(title,'Biztosan törölni szeretnéd a csoportkategóriát? A kategóriában lévő összes csoport is törlődik. A művelet nem visszavonható!',['Kat. törlése','Visszalépés'],function(sure){
			if (!sure) return;
			$.Dialog.wait();

			var id = $(e.currentTarget).attr('href').substring(1);

			$.ajax({
				method: "POST",
				url: "/groups/theme/delete",
				data: {'id':id},
				success: function(data){
					if (data.status){
						$(e.currentTarget).parent().parent().remove();
						$('div[data-thm=' + id + ']').remove();

							// Nincs csop.kat. üzenet hozzáadása
							if ($('#groupContainer').children().length == 0)
								$('.missingThemes').show();

						$.Dialog.close();
					}
					else $.Dialog.fail(title,data.message);
				}
			})
		});
	};
	$('.js_thm_del').on('click',e_thm_del);

	var e_getMembers = function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('href').substring(1),
			title = 'Csoport tagjainak lekérése';

		$.Dialog.wait(title);

		$.ajax({
			method: 'POST',
			url: '/groups/members',
			data: {'id': id},
			success: function(data){
				if (typeof data !== 'object'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status)
					$.Dialog.info(title,data.html);

				else $.Dialog.fail(title,data.message);
			}
		});
	};
	$('.js_grp_members').on('click',e_getMembers);

});
