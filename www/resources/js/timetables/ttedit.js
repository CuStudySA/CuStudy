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
		simpleView = false,
		fullView = true,

		backNextWeek = function(button){
			var $bWButton = $('.backWeek');

			if (button == 'back' && $bWButton.is(':disabled'))
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

					$('.timet').html($data.filter('.timet').html());

					$bWButton.attr('disabled', lockBack).blur();
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

				$('.timet').html($data.filter('.timet').html());

				$bWButton.attr('disabled', lockBack);

				$.Dialog.close();
			}
		});
	});

	var $lP = $('#lessonPicker'),
		$bWButton = $('.backWeek'),
		$switchView = $('#js_switchView');

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

				$('.timet').removeClass('single').html($data.filter('.timet').html());

				$bWButton.attr('disabled',lockBack);
				$switchView.attr('disabled', fullView = true);

				$('.js_showAllTT').replaceWith('<a class="btn js_hideAllTT typcn typcn-user" href="#">Saját órarend</a>');
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

				$('.timet').html($data.filter('.timet').html());

				$bWButton.attr('disabled', lockBack);
				$switchView.attr('disabled', fullView = false);
				if (simpleView)
					$('.timet').addClass('single');

				$('.js_hideAllTT').replaceWith("<a class='btn js_showAllTT typcn typcn-group' href='#'>Teljes nézet</a>");
				$('.js_showAllTT').on('click',e_showAllTT);

				$.Dialog.close();
			}
		});
	};
	$('.js_hideAllTT').on('click',e_hideAllTT);

	$switchView.on('click',function(){
		simpleView = !simpleView;
		$('.timet')[(simpleView ? 'add' : 'remove') + 'Class']('single');

		$switchView.toggleClass('typcn-eye typcn-eye-outline').text(simpleView ? 'Hagyományos nézet' : 'Kompakt nézet');
	});
});