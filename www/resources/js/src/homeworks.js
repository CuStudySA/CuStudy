$(function(){
	var title = 'Házi feladat hozzáadása',
		dispDays = typeof _dispDays !== 'object' ? '' : _dispDays.slice(),
		showHidden = false,
		files = [], $lP = $('#lessonPicker'), $sDP = $('#startDatePicker');

	if (typeof $.fn.sceditor !== 'undefined')
		$(".BBCodeEditor").sceditor({
			plugins: "bbcode",
			toolbar: "bold,italic,underline|color,removeformat|cut,copy,paste|source",
			style: '/resources/addons/sceditor/jquery.sceditor.default.min.css',
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

	$lP.on('click','.lesson',function(){
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
			data: {id: id},
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
							$('.notice').show();
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
			data: {id: id},
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
			url: '/homeworks/getDoneHomeworks',
			dataType: 'html',
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
			dataType: 'html',
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

	$('.uploadField').on('change',function(e){
		files = e.target.files;
		var $infoCont = $('.infoContainer');

		if (typeof files[0] != 'undefined'){
			$infoCont.show();
			$(document.body).animate({scrollTop: $infoCont.offset().top - 10 }, 500);
		}
		else
			$infoCont.hide();
	});
	$('.sendForm').on('click',function(e){
		e.preventDefault();

		var $selLesson = $('.selectedLesson'),
			text = $(".BBCodeEditor").data("sceditor").val();

		if ($selLesson.length == 0) return $.Dialog.fail(title,'A házi feladat hozzáadása nem sikerült, mert nincs kiválasztott tantárgy!');
		if (text.length <= 7) return $.Dialog.fail(title,'A házi feladat hozzáadása nem sikerült, mert a házi feladat szövege kisebb mint nyolc!');

		$.Dialog.wait(title);

		var data = new FormData();

		$.each(files, function(key, value){
			data.append(key, value);
		});

		if (typeof files[0] != 'undefined'){
			data.append('fileTitle',$('[name=fileTitle]').val());
			data.append('fileDesc',$('[name=fileDesc]').val());
		}

		$.each({
			lesson: $selLesson.find('.del').attr('data-ttid'),
			text: text,
			week: $selLesson.closest('td').attr('data-week')
		}, function(key, value){
			data.append(key, value);
		});

		$.ajax({
			method: "POST",

			// For file uploading
			cache: false,
			dataType: 'json',
			processData: false,
			contentType: false,

			data: data,
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

	var $bWButton = $('.backWeek').on('click',function(){ backNextWeek('back') }),
		$nWButton = $('.nextWeek').on('click',function(){ backNextWeek('next') }),
		title2 = 'Órakiválasztó-felület frissítése',
		backNextWeek = function(button){
			var $lP = $('#lessonPicker'),
				$bWButton = $('.backWeek');

			if (button == 'back' && $bWButton.attr('disabled') == 'disabled')
				return $.Dialog.fail(title2,'A jelenlegi hétről nem tudsz visszalépni egy előző hétre!');

			$.Dialog.wait(title2);

			$.ajax({
				method: "POST",
				url: '/homeworks/getTimetable/nextBack',
				data: {move: button, dispDays: dispDays},
				success: function(data){
					dispDays = data.dispDays;
					$lP.children().html(data.timetable);
					$bWButton.attr('disabled',data.lockBack).blur();
					$nWButton.blur();
					$sDP.val(dispDays[0]);

					$.Dialog.close();
				}
			});
		},
		sdrq;
	$sDP.on('change',function(e){
		e.preventDefault();

		var date = $(this).val();
		if (isNaN(Date.parse(date)))
			return $.Dialog.fail('Léptetés', 'Érvénytelen dátum!');

		if (typeof sdrq !== 'undefined'){
			sdrq.abort();
			sdrq = undefined;
		}
		if (!$.Dialog.open || $.Dialog.open.type !== 'wait')
			$.Dialog.wait(title2);

		sdrq = $.ajax({
			method: "POST",
			url: '/homeworks/getTimetable/date',
			data: {date: date},
			success: function(data){
				dispDays = data.dispDays;
				$lP.children().html(data.timetable);
				$bWButton.attr('disabled',data.lockBack).blur();
				$nWButton.blur();
				$sDP.val(dispDays[0]);

				$.Dialog.close();
				sdrq = undefined;
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
				data: {id: id},
				success: function(data){
					if (typeof data !== 'object'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}

					if (data.status){
						$elem.parent().remove();
						deleteEmptyTd();

						if ($('tbody').children().length == 0)
							$('.notice').show();

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
