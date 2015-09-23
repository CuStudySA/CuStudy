$(function(){
	var title = 'Házi feladat hozzáadása',
		dispDays = typeof _dispDays !== 'object' ? '' : _dispDays,
		showHidden = false;

	$("textarea").sceditor({
		plugins: "bbcode",
		toolbar: "bold,italic,underline|color,removeformat|cut,copy,paste|source",
		style: '/resources/addons/sceditor/jquery.sceditor.default.min.css',
		width: "80%",
		height: "170px",
		runWithoutWysiwygSupport: true,
		locale: 'hu',
		emoticonsEnabled: false,
	});

	var deleteEmptyTd = function(){
		var index = -1,
			$trs = $('tr');

		$trs.eq(1).children().each(function(i, el){
			var $el = $(el);
			if ($el.children().length == 0){
				$el.remove();
				index = i;
			}
		});
		if (index == -1) return;
		$trs.eq(0).children().eq(index).remove();

		$trs.each(function(i, el){
			if ($(el).children().length == 0) $(el).remove();
		});
	};

	$('.lesson').on('click',function(){
		$('.selectedLesson').removeClass('selectedLesson');
		$(this).addClass('selectedLesson');
	});

	$('.js_add_hw').click(function(){
		$(this).blur();
	});

	var makeMarkedDone = function(e){
		e.preventDefault();

		var $elem = $(e.currentTarget),
			id = $elem.attr('href').substring(1),
		    title = 'Házi feladat késznek jelölése';

		$.Dialog.wait(title);

		$.ajax({
			method: "POST",
			data: pushToken({'id': id}),
			url: '/homeworks/makeMarkedDone',
			success: function(data){
				if (typeof data !== 'object'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					if (!showHidden){
						$elem.parent().detach();
						deleteEmptyTd();

						if ($('tbody').children().length == 0)
							$('table').replaceWith('<p>Nincs megjelenítendő házi feladat! A kezdéshez adjon hozzá egyet, vagy váltson nézetet!</p>');
					}
					else {
						$(e.currentTarget).replaceWith("<a class='typcn typcn-times js_undoMarkedDone' title='Késznek jelölés visszavonása' href='#" + id + "'></a>");
						$('[href=#' + id + ']').filter('.js_undoMarkedDone').on('click',undoMarkedDone);
					}

					$.Dialog.close();
				}
				else $.Dialog.fail(title,data.message);
			}
		});
	};
	$('.js_makeMarkedDone').click(makeMarkedDone);

	var undoMarkedDone = function(e){
		e.preventDefault();

		var $elem = $(e.currentTarget),
			id = $elem.attr('href').substring(1),
		    title = 'Házi feladat kész jelölésének eltávolítása';

		$.Dialog.wait(title);

		$.ajax({
			method: "POST",
			data: pushToken({'id': id}),
			url: '/homeworks/undoMarkedDone',
			success: function(data){
				if (typeof data !== 'object'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					$(e.currentTarget).replaceWith("<a class='typcn typcn-tick js_makeMarkedDone' title='Késznek jelölés' href='#" + id + "'></a>");
					$('[href=#' + id + ']').filter('.js_makeMarkedDone').on('click',makeMarkedDone);

					$.Dialog.close();
				}
				else $.Dialog.fail(title,data.message);
			}
		});
	};
	$('.js_undoMarkedDone').click(undoMarkedDone);

	var getDoneHW = function(e){
		e.preventDefault();

		var $content = $('.hwContent'),
			title = 'Házi feladatok lekérése';

		$.Dialog.wait(title);

		$.ajax({
			method: "POST",
			data: pushToken({}),
			url: '/homeworks/getDoneHomeworks',
			success: function(data){
				$content.empty().append(data);
				$('.js_hideMarkedDone').on('click',getNotDoneHW);
				$.Dialog.close();

				$('.js_undoMarkedDone').click(undoMarkedDone);
				$('.js_makeMarkedDone').click(makeMarkedDone);

				showHidden = true;
			},
			error: function(){
				$.Dialog.fail(title,'A házi feladatok lekérése nem sikerült egy ismeretlen hiba miatt!');
			}
		});
	};
	$('.js_showMarkedDone').on('click',getDoneHW);

	var getNotDoneHW = function(e){
		e.preventDefault();

		var $content = $('.hwContent'),
			title = 'Házi feladatok lekérése';

		$.Dialog.wait(title);

		$.ajax({
			method: "POST",
			url: '/homeworks/getNotDoneHomeworks',
			data: pushToken({}),
			success: function(data){
				$content.empty().append(data);
				$('.js_showMarkedDone').on('click',getDoneHW);
				$.Dialog.close();

				$('.js_undoMarkedDone').click(undoMarkedDone);
				$('.js_makeMarkedDone').click(makeMarkedDone);

				showHidden = false;
			},
			error: function(){
				$.Dialog.fail(title,'A házi feladatok lekérése nem sikerült egy ismeretlen hiba miatt!');
			}
		});
	};

	$('.sendForm').on('click',function(e){
		e.preventDefault();

		var $selLesson = $('.selectedLesson'),
			text = $(".BBCodeEditor").data("sceditor").val();

		if ($selLesson.length == 0) return $.Dialog.fail(title,'A házi feladat hozzáadása nem sikerült, mert nincs kiválasztott tantárgy!');
		if (text.length <= 7) return $.Dialog.fail(title,'A házi feladat hozzáadása nem sikerült, mert a házi feladat szövege kisebb mint nyolc!');

		$.Dialog.wait(title);

		$.ajax({
			method: "POST",
			data: pushToken({'lesson': $selLesson.find('.del').attr('data-id'), 'text': text, 'week': $selLesson.attr('data-week')}),
			success: function(data){
				if (typeof data !== 'object'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					$.Dialog.success(title,data.message);
					setTimeout(function(){
						window.location.href = '/homeworks';
					},2500);
				}
				else $.Dialog.fail(title,data.message);
			}
		});
	});

	var title2 = 'Órakiválasztó-felület frissítése',
		backNextWeek = function(button){
			var $lP = $('#lessonPicker'),
				$bWButton = $('.backWeek');

			if (button == 'back' && $bWButton.attr('disabled') == 'disabled')
				return $.Dialog.fail(title2,'A jelenlegi hétről nem tudsz visszalépni egy előző hétre!');

			$.Dialog.wait(title2);

			$.ajax({
				method: "POST",
				url: '/homeworks/getTimetable/nextBack',
				data: pushToken({'move': button, 'dispDays': dispDays}),
				success: function(data){
					var $data = $(data);

					dispDays = JSON.parse($data.filter('.dispDays').detach().text());
					var lockBack = JSON.parse($data.filter('.lockBack').detach().text());

					$lP.empty().append($data.prop('outerHTML'));

					if (lockBack) $bWButton.attr('disabled','disabled');
					else $bWButton.removeAttr('disabled');

					$bWButton.blur();
					$('.nextWeek').blur();

					$('.lesson').on('click',function(){
						$('.selectedLesson').removeClass('selectedLesson');
						$(this).addClass('selectedLesson');
					});

					$.Dialog.close();
				}
			});
		};
	$('.backWeek').on('click',function(){
		backNextWeek('back');
	});
	$('.nextWeek').on('click',function(){
		backNextWeek('next');
	});

	$('#startDatePicker').on('change',function(){
		var $lP = $('#lessonPicker'),
			$bWButton = $('.backWeek');

		$.Dialog.wait(title2);

		$.ajax({
			method: "POST",
			url: '/homeworks/getTimetable/date',
			data: pushToken({'date': $(this).val()}),
			success: function(data){
				var $data = $(data);

				dispDays = JSON.parse($data.filter('.dispDays').detach().text());
				var lockBack = JSON.parse($data.filter('.lockBack').detach().text());

				$lP.empty().append($data.prop('outerHTML'));

				if (lockBack) $bWButton.attr('disabled','disabled');
				else $bWButton.removeAttr('disabled');

				$('.lesson').on('click',function(){
					$('.selectedLesson').removeClass('selectedLesson');
					$(this).addClass('selectedLesson');
				});

				$.Dialog.close();
			}
		});
	});

	$('.js_delete').click(function(e){
		e.preventDefault();

		var $elem = $(e.currentTarget),
			id = $elem.attr('href').substring(1),
		    title = 'Házi feladat törlése';

		$.Dialog.confirm(title,'Arra készülsz, hogy törlöd a kiválasztott házi feladatot. Ez nem azt jelenti, hogy nem kell azt megcsinálnod, csupán azt, hogy azt a rendszer többé nem jelzi. Biztosan törlöd a bejegyzést a rendszerből?',['Bejegyzés törlése', 'Visszalépés'],function(sure){
			if (!sure) return;

			$.Dialog.wait();

			$.ajax({
				method: "POST",
				url: '/homeworks/delete',
				data: pushToken({'id': id}),
				success: function(data){
					if (typeof data !== 'object'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}

					if (data.status){
						$elem.parent().remove();
						deleteEmptyTd();
						$.Dialog.close();
					}
					else $.Dialog.fail(title,data.message);
				}
			});
		});
	});

	var onDev = function(e){
		e.preventDefault();
		$.Dialog.fail('Funkció fejlesztés alatt','A kívánt funckió jelen pillanatban nem elérhető, mert az még fejlesztés alatt van! Próbálkozz később!');
	};
	$('.js_finished').click(onDev);
	$('.js_more_info').click(onDev);
});