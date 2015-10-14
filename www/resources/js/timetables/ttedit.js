/* JS add-on for BetonHomeWork
 * Copyright(C) 2015. BetonSoft csoport */

$(function(){
	var title = "Tanárok és tantárgyak lekérése", $tds = $('table tbody td'),
		postDatas = {},
		USRGRP = _USRGRP;

	var title2 = 'Órakiválasztó-felület frissítése',
		dispDays = typeof _dispDays !== 'object' ? '' : _dispDays,
		prevDispDays = dispDays,
		showHidden = false,
		showAllGroups = 0,

		backNextWeek = function(button){
			var $lP = $('#lessonPicker'),
				$bWButton = $('.backWeek');

			if (button == 'back' && $bWButton.attr('disabled') == 'disabled')
				return $.Dialog.fail(title2,'A jelenlegi hétről nem tudsz visszalépni egy előző hétre!');

			$.Dialog.wait(title2);

			$.ajax({
				method: "POST",
				url: '/homeworks/getTimetable/nextBack',
				data: pushToken({'move': button, 'dispDays': dispDays, 'showAllGroups': showAllGroups}),
				success: function(data){
					var $data = $(data);

					prevDispDays = dispDays;
					dispDays = JSON.parse($data.filter('.dispDays').detach().text());
					var lockBack = JSON.parse($data.filter('.lockBack').detach().text());

					$lP.empty().append($data.prop('outerHTML'));

					if (lockBack) $bWButton.attr('disabled','disabled');
					else $bWButton.removeAttr('disabled');

					$bWButton.blur();
					$('.nextWeek').blur();

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
			data: pushToken({'date': $(this).val(), 'days': 5, 'showAllGroups': showAllGroups}),
			success: function(data){
				var $data = $(data);

				prevDispDays = dispDays;
				dispDays = JSON.parse($data.filter('.dispDays').detach().text());
				var lockBack = JSON.parse($data.filter('.lockBack').detach().text());

				$lP.empty().append($data.prop('outerHTML'));

				if (lockBack) $bWButton.attr('disabled','disabled');
				else $bWButton.removeAttr('disabled');

				$.Dialog.close();
			}
		});
	});

	var $lP = $('#lessonPicker'),
		$bWButton = $('.backWeek');

	var e_showAllTT = function(e){
		e.preventDefault();

		$.Dialog.wait();

		$.ajax({
			method: "POST",
			url: '/timetables/showTimetable/all',
			data: pushToken({'dispDays': dispDays}),
			success: function(data){
				showAllGroups = 1;
				var $data = $(data);

				dispDays = JSON.parse($data.filter('.dispDays').detach().text());
				var lockBack = JSON.parse($data.filter('.lockBack').detach().text());

				$lP.empty().append($data.prop('outerHTML'));

				if (lockBack) $bWButton.attr('disabled','disabled');
				else $bWButton.removeAttr('disabled');

				$('.js_showAllTT').replaceWith('<a class="btn js_hideAllTT typcn typcn-user" href="#">A saját órarendem megjelenítése</a>');
				$('.js_hideAllTT').on('click',e_hideAllTT);

				$.Dialog.close();
			}
		});
	};
	$('.js_showAllTT').on('click',e_showAllTT);

	var e_hideAllTT = function(e){
		e.preventDefault();

		$.Dialog.wait();

		$.ajax({
			method: "POST",
			url: '/timetables/showTimetable/my',
			data: pushToken({'dispDays': dispDays}),
			success: function(data){
				showAllGroups = 0;
				var $data = $(data);

				dispDays = JSON.parse($data.filter('.dispDays').detach().text());
				var lockBack = JSON.parse($data.filter('.lockBack').detach().text());

				$lP.empty().append($data.prop('outerHTML'));

				if (lockBack) $bWButton.attr('disabled','disabled');
				else $bWButton.removeAttr('disabled');

				$('.js_hideAllTT').replaceWith("<a class='btn js_showAllTT typcn typcn-group' href='#'>Az egész osztály órarendjének megjelenítése</a>");
				$('.js_showAllTT').on('click',e_showAllTT);

				$.Dialog.close();
			}
		});
	};
	$('.js_hideAllTT').on('click',e_hideAllTT);
});