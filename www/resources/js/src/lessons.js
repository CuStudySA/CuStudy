$(function(){
	var $formTempl = $("<form method='POST' id='js_form'>\
						<p>Tantárgy neve: <input type='text' name='name' value='' pattern='^[A-Za-zöüóőúéáűÖÜÓŐÚÉÁŰ.() ]{4,15}$' required placeholder='Tantárgy'></p>\
						<p>Előadó (tanár) neve:\
							<select name='teacherid'>\
							</select>\
						</p>\
						<p>Tantárgy színe: <input id='colorpicker' name='color' type='hidden' value='#000000'></p>\
						<input type='hidden' name='id' value=''>\
					</form>");

	var $tileTempl = $("<li>\
							<div class='top'>\
								<span class='tantargy'></span>\
								<span class='tanar'></span>\
							</div>\
							<div class='bottom'>\
								<a class='typcn typcn-pencil js_lesson_edit' href='' title='Módosítás'></a>\
								<a class='typcn typcn-trash js_lesson_delete' href='' title='Törlés'></a>\
							</div>\
						</li>");

/*
*   $ad = $(document.createElemnt('li')).attr({method:'POST',id:'js_form'}).html("<div class='top'>\
								<span class='tantargy'></span>\
								<span class='tanar'></span>\
							</div>\
							<div class='bottom'>\
								<a class='typcn typcn-pencil js_lesson_edit' href='' title='Módosítás'></a>\
								<a class='typcn typcn-trash js_lesson_delete' href='' title='Törlés'></a>\
							</div>");
* */

	var options = [],
		opt_s = "";


/*	{
		0: '1elem',
		1: '2.elem',
		length: 2,
		spilce: function...
	} */

	$.ajax({
		method: "POST",
		url: "/lessons/get/teachers",
		success: function(data){
			if (typeof data === 'string'){
				console.log(data);
				$(window).trigger('ajaxerror');
				return false;
			}

			if (data.options)
				$.each(data.options, function(_, entry){
					opt_s += entry;
				});
		}
	});

	var e_lesson_edit = function(e){
		e.preventDefault();

		var title = 'Tantárgy szerkesztése',
			id = $(e.currentTarget).attr('href').substring(1);

		$.ajax({
			method: "POST",
			url: "/lessons/get",
			data: {'id': id},
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}

				var $dialog = $formTempl.clone();

				$dialog.find('[name=name]').attr('value',data.name);
				$dialog.find('[name=color]').attr('value',data.color);
				$dialog.find('[name=id]').attr('value',data.id);
				$dialog.find('[name=teacherid]').append(opt_s).children('option[value='+data.teacherid+']').attr('selected', true);

				$.Dialog.request(title,$dialog.prop('outerHTML'),'js_form','Mentés',function($urlap){
					$("#colorpicker").spectrum({
					    showInput: true,
					    showInitial: true,
					    preferredFormat: "hex",
						change: function(color) {
							$("#colorpicker").attr("value",color.toHexString());
						}
					});
					
					$urlap.on('submit',function(e){
						e.preventDefault();

						var data = $urlap.serializeForm();
						$.Dialog.wait(title);
						
						$.ajax({
							method: "POST",
							url: "/lessons/edit",
							data: data,
							success: function(data2){
								if (typeof data2 === 'string'){
									console.log(data2);
									$(window).trigger('ajaxerror');
									return false;
								}
								if (data2.status){
									var $elem = $('ul').children('[data-id=' + id + ']'),
										$urlapelemek = $urlap.children();

									$elem.find('.tantargy').text($urlapelemek.find('[name=name]').val());
									$elem.find('.tanar').text($urlapelemek.find('[name=teacherid]').find(':selected').text());
									$elem.css('background-color',$urlapelemek.find('[name=color]').val());

									var $lessons = $('.lessons'),
										$newLessonTiles = $('.new').detach();

									$lessons.sortChildren('.tantargy',false);
									$lessons.append($newLessonTiles);

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

	var e_lesson_delete = function(e){
		e.preventDefault();

		var title = 'Tantárgy törlése';
		$.Dialog.confirm(title,'Biztosan szeretnéd törölni a tantárgyat?',['Tantárgy törlése','Visszalépés'],function(sure){
			if (!sure) return;
			var id = $(e.currentTarget).attr('href').substring(1);
			$.Dialog.wait(title);

			$.ajax({
				method: "POST",
				url: "/lessons/delete",
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

	var e_lesson_add = function(e){
		e.preventDefault();

		var title = 'Tantárgy hozzáadás';

		if (opt_s.length === 0)
			return $.Dialog.fail(title, 'Tantárgyak felvétele előtt hozzá kell adnod tanárokat a <a href="/teachers">Tanárok</a> menüpontban!');

		var $dialog = $formTempl.clone();
		$dialog.find('[name=id]').remove();
		$dialog.find('[name=teacherid]').append(opt_s);

		$.Dialog.request(title,$dialog,'js_form','Mentés',function($urlap){

			$("#colorpicker").spectrum({
				change: function(color) {
					$("#colorpicker").attr("value",color.toHexString());
				}
			});

			$urlap.on('submit',function(e){
				e.preventDefault();

				var data = $urlap.serializeForm();
				$.Dialog.wait(title);

				$.ajax({
					method: "POST",
					url: "/lessons/add",
					data: data,
					success: function(data2){
						if (typeof data2 === 'string'){
							console.log(data2);
							$(window).trigger('ajaxerror');
							return false;
						}
						if (data2.status){
							var $elem = $tileTempl.clone(),
								$urlapelemek = $urlap.children();

							$elem.find('.tantargy').text($urlapelemek.find('[name=name]').val());
							$elem.find('.tanar').text($urlapelemek.find('[name=teacherid]').find(':selected').text());
							$elem.css('background-color',$urlapelemek.find('[name=color]').val());
							$elem.attr('data-id',data2.id);
							$elem.find('.js_lesson_edit').attr('href','#' + data2.id);
							$elem.find('.js_lesson_delete').attr('href','#' + data2.id);

							var $lessons = $('.lessons');
							$lessons.append($elem);

							var $newLessonTile = $('.new').detach();
							$lessons.sortChildren('.tantargy',false);
							$lessons.append($newLessonTile);

							$elem.find('.js_lesson_edit').on('click', e_lesson_edit);
							$elem.find('.js_lesson_delete').on('click', e_lesson_delete);

							$.Dialog.close();
						}

						else $.Dialog.fail(title,data2.message);
					}
				});
			});
		});
	};

	$('.js_lesson_add').on('click', e_lesson_add);
	$('.js_lesson_edit').on('click', e_lesson_edit);
	$('.js_lesson_delete').on('click', e_lesson_delete);
});
