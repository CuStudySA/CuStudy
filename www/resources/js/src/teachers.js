$(function(){
	var $formTempl = $("<form id='js_form'>\
							<p>Tanár neve: <input type='text' name='name' autocomplete='off' required></p>\
							<p>Tanár rövid neve: <input type='text' name='short' autocomplete='off' required></p>\
							<input type='hidden' name='id'>\
						</form>");

	var $tileTempl = $("\
			<li>\
				<div class='top'>\
					<span class='rovid'></span>\
					<span class='nev'></span>\
				</div>\
				<div class='bottom'>\
					<a class='typcn typcn-pencil js_teacher_edit' title='Módosítás'></a>\
					<a class='typcn typcn-minus js_teacher_del' title='Törlés'></a>\
				</div>\
			</li>");

	if (typeof Patterns !== 'undefined'){
		$.each(Patterns,function(key,value){
			var $patternInput = $formTempl.find('[name=' + key + ']');

			if ($patternInput.length == 1)
				$patternInput.attr('pattern',value);
		});
	}
	var $addForm = $('.add_teacher_form').detach().css('display','block'),
		$clonedAddForm;
	var e_teacher_add = function(e){
		e.preventDefault();

		if ($clonedAddForm instanceof jQuery) $clonedAddForm.remove();
		$clonedAddForm = $addForm.clone();

		$('main').append($clonedAddForm);

		$("#colorpicker").spectrum({
		    showInput: true,
		    showInitial: true,
		    preferredFormat: "hex",
		    change: function(color) {
		        $("#colorpicker").attr("value",color.toHexString());
		    }
		});

		$(document.body).animate({scrollTop: $clonedAddForm.offset().top - 10 }, 500);

		/* Hozzáadás gomb eseménye */
		$('.addlesson').click(function(e){
			e.preventDefault();
			
			var $lessonname = $('.a_l_name'),
				lessonname = $lessonname.val(),
				$lessoncolor = $('#colorpicker').val(),
				$nameregex = new RegExp("^[A-Za-zöüóőúéáűÖÜÓŐÚÉÁŰ.() ]{4,15}$"),
				$ul_list = $('.l_l_utag'),
				nesreturn = false,
				title = "Óra hozzáadása";

			//Formátum ellenörzése
			if (!$nameregex.test(lessonname)){
				$.Dialog.fail(title,"A tantárgy formátuma nem megfelelő! A tantárgy csak a magyar ABC kis-, és nagybetűit tartalmazhatja!");
				return;
			}

			//Létezik-e már ilyen elem?
			$.each($ul_list.children(),function(i,entry){
				var lesname = $(entry).attr('data-name');

				if (lesname == lessonname){
					$.Dialog.fail(title,"Már létezik ilyen nevű tantárgy, válassz másik nevet!");
					return !(nesreturn = true);
				}
			});
			if (nesreturn) return;

			//Hozzáadás a listához
			$ul_list.append('<li data-color="'+ $lessoncolor +'" data-name="'+ lessonname +'"><span style="color: '+ $lessoncolor +'" class="l_l_litag">'+ lessonname +'</span><span class="typcn typcn-times l_l_deleteopt"></span></li>');

			//Beviteli mezők alaphelyzetbe állítása és üres jelzés eltávolítása
			$('.l_l_empty').remove();
			$lessonname.val('');

			/* Törlés eseménye */
			$('.l_l_deleteopt').on('click',function(){
				var $li = $(this).parent(),
					$ul = $('.l_l_utag');

				$li.remove();

				if ($ul.children().size() == 0)
					$ul.append('<li class="l_l_empty">(nincs)</li>');
			});
		});

		var lessons = [], adding = false;
		/* Elküldés gomb eseménye */
		$('.a_t_f_sendButton').click(function(e){
			e.preventDefault();

			if (adding === true) return;
			adding = true;

			var $ul = $('.l_l_utag'),
				$inputs = $('.teacher_info').find('input'),
				title = 'Tanár hozzáadása';

			//Tantárgyak listájának előkészítése
			$.each($ul.children(),function(i,entry){
				if ($(entry).hasClass('l_l_empty')) return;
				var name = $(entry).attr('data-name'),
					color = $(entry).attr('data-color');

				lessons.push({'name': name, 'color': color});
			});

			var data = {'lessons': lessons};

			//Tanár adatainak előkészítése
			$inputs.filter('[name="name"], [name="short"]').each(function(i,entry){
				var $entry = $(entry),
					name = $entry.attr('name');
				data[name] = $entry.val();
			});

			$.Dialog.wait(title);

			//Kommunikáció a szerverrel
			$.ajax({
				method: 'POST',
				url: '/teachers/add',
				data: data,
				success: function(data2){
					if (typeof data2 === 'string'){
						console.log(data2);
						$(window).trigger('ajaxerror');
						return false;
					}

					if (data2.status){
						var $elem = $tileTempl.clone();

						$elem.find('.rovid').text(data.short);
						$elem.find('.nev').text(data.name);
						$elem.find('.js_teacher_edit').attr('href','#' + data2.id);
						$elem.find('.js_teacher_del').attr('href','#' + data2.id);
						$elem.attr('data-id',data2.id);

						var $elemlista = $('.teachers');

						var $newLessonTile = $('.new').detach();

						$('.js_teacher_add').on('click', e_teacher_add);
						$elem.find('.js_teacher_edit').on('click', e_teacher_edit);
						$elem.find('.js_teacher_del').on('click', e_teacher_del);

						$elemlista.append($elem);

						$elemlista.sortChildren('.rovid',false);
						$elemlista.append($newLessonTile);

						$clonedAddForm.remove();
						$clonedAddForm = undefined;
						$.Dialog.close();
					}
					else $.Dialog.fail(title,data2.message);
				},
				complete: function(){
					adding = false;
				}
			});
		});
	};
	var e_teacher_edit = function(e){
		e.preventDefault();

		var title = 'Tanár szerkesztése',
			id = $(e.currentTarget).attr('href').substring(1);

		$.ajax({
			method: "POST",
			url: "/teachers/get",
			data: {'id': id},
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				if (data.status != 1) return;

				var $dialog = $formTempl.clone();

				$dialog.find('[name=name]').attr('value',data.name);
				$dialog.find('[name=short]').attr('value',data.short);
				$dialog.find('[name=id]').attr('value',id);

				$.Dialog.request(title,$dialog.prop('outerHTML'),'js_form','Mentés',function($urlap){
					$urlap.on('submit',function(e){
						e.preventDefault();

						var data = $urlap.serializeForm();
						$.Dialog.wait(title);

						$.ajax({
							method: "POST",
							url: "/teachers/edit",
							data: data,
							success: function(data2){
								if (typeof data2 === 'string'){
									console.log(data2);
									$(window).trigger('ajaxerror');
									return false;
								}
								if (data2.status){
									var $elemlista = $('.teachers'),
										$elem = $elemlista.find('[data-id=' + id + ']');

									$elem.find('.rovid').text($urlap.find('[name=short]').val());
									$elem.find('.nev').text($urlap.find('[name=name]').val());

									var $newLessonTile = $('.new').detach();

									$elemlista.sortChildren('.rovid',false);
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

	var e_teacher_del = function(e){
		e.preventDefault();

		var id = $(e.currentTarget).attr('href').substring(1),
		    title = 'Tanár törlése';

		$.Dialog.confirm(title,'A kiválasztott tanár törlésére készülsz. Ez a művelet érintheti a tantárgyakat, és az egyéb tartalmakat is. Biztosan törlöd a tanárt a rendszerből?',['Tanár törlése', 'Visszalépés'],function(sure){
			if (!sure) return;

			$.Dialog.wait(title);

			$.ajax({
				method: "POST",
				url: "/teachers/delete",
				data: {'id': id},
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}

					if (data.status){
						$(document).find('[data-id=' + id + ']').remove();
						$.Dialog.close();
					}
					else $.Dialog.fail(title,data.message);
				}
			});
		});
	};

	$('.js_teacher_add').on('click',e_teacher_add);
	$('.js_teacher_edit').on('click',e_teacher_edit);
	$('.js_teacher_del').on('click',e_teacher_del);
});
