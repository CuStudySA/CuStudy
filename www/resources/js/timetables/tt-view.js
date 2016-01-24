/*
 * JS add-on for CuStudy
 * @copyright (C) 2016 CuStudy Software Alliance
 */

$(function(){
	// Szerkesztés letiltása
	$('tr td').addClass('notAdmin');

	var title2 = 'Órarend léptetése',
		dispDays = typeof _dispDays !== 'object' ? '' : _dispDays,
		prevDispDays = dispDays,
		showAllGroups = 0,
		simpleView = false,
		fullView = true,
		$bWButton = $('.backWeek'),
		$nWButton = $('.nextWeek'),
		$timetable = $('.timet'),
		$datePick = $('#startDatePicker'),
		currDate = $datePick.val(),
		stepWeek = function(button){
			if (button == 'back' && $bWButton.is(':disabled'))
				return $.Dialog.fail(title2,'A jelenlegi héttől nem lehetséges visszább léptetni!');

			$.Dialog.wait(title2);

			$.ajax({
				method: "POST",
				url: '/homeworks/getTimetable/nextBack',
				data: {
					move: button,
					dispDays: dispDays,
					showAllGroups: showAllGroups,
				},
				success: function(data){
					if (!data.status) return $.Dialog.fail(title2, data.message);

					prevDispDays = dispDays;
					dispDays = data.dispDays;
					$timetable.html(data.timetable);
					$bWButton.attr('disabled', data.lockBack).blur();

					$datePick.val(new Date(dispDays[0]).toISOString().substring(0,10))
					$nWButton.blur();

					$.Dialog.close();
				}
			});
		};
	$bWButton.on('click',function(){ stepWeek('back') });
	$nWButton.on('click',function(){ stepWeek('next') });

	$datePick.on('change',function(e){
		e.preventDefault();

		var date = $(this).val();
		if (date === currDate)
			return true;
		$.Dialog.wait(title2);

		if (isNaN(Date.parse(date)))
			return $.Dialog.fail('Léptetés', 'Érvénytelen dátum!');

		$.ajax({
			method: "POST",
			url: '/homeworks/getTimetable/date',
			data: {
				date: new Date($(this).val()).toISOString(),
				days: 5,
				showAllGroups: showAllGroups,
			},
			success: function(data){
				prevDispDays = dispDays;
				dispDays = data.dispDays;
				$timetable.html(data.timetable);
				$bWButton.attr('disabled', data.lockBack);
				currDate = new Date(dispDays[0]).toISOString().substring(0,10);
				$datePick.val(currDate);

				$.Dialog.close();
			}
		});
	});

	var $switchView = $('#js_switchView'),
		$fpToggle = $('.js_fullPersonalToggle');

	var e_showAllTT = function(){
		$.ajax({
			method: "POST",
			url: '/timetables/showTimetable/all',
			data: {dispDays: dispDays},
			success: function(data){
				showAllGroups = 1;
				dispDays = data.dispDays;
				$timetable.removeClass('single').html(data.timetable);
				$bWButton.attr('disabled',data.lockBack);
				$switchView.attr('disabled', fullView = true);

				$fpToggle.removeClass('typcn-group').addClass('typcn-user').text('Saját órarend');

				$.Dialog.close();
			}
		});
	};
	var e_hideAllTT = function(){
		$.ajax({
			method: "POST",
			url: '/timetables/showTimetable/my',
			data: {dispDays: dispDays},
			success: function(data){
				showAllGroups = 0;
				dispDays = data.dispDays;
				$timetable.html(data.timetable);
				$bWButton.attr('disabled', data.lockBack);
				$switchView.attr('disabled', fullView = false);
				if (simpleView)
					$timetable.addClass('single');

				$fpToggle.removeClass('typcn-user').addClass('typcn-group').text('Teljes nézet');

				$.Dialog.close();
			}
		});
	};
	$fpToggle.on('click',function(e){
		e.preventDefault();

		$.Dialog.wait();

		if (this.className.indexOf('typcn-group') !== -1)
			e_showAllTT();
		else e_hideAllTT();
	});

	$switchView.on('click',function(){
		simpleView = !simpleView;
		$('.timet')[(simpleView ? 'add' : 'remove') + 'Class']('single');

		$switchView.toggleClass('typcn-eye typcn-eye-outline').text(simpleView ? 'Hagyományos nézet' : 'Kompakt nézet');
	});
});
