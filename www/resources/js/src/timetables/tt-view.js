/*
 * JS add-on for CuStudy
 * @copyright (C) 2016 CuStudy Software Alliance
 */

$(function(){
	// Szerkesztés letiltása
	$('.timet').addClass('notAdmin');

	var title2 = 'Órarend léptetése',
		dispDays = typeof _dispDays !== 'object' ? '' : _dispDays,
		prevDispDays = dispDays,
		showAllGroups = 0,
		simpleView = false,
		fullView = true,
		$bWButton = $('.backWeek'),
		$nWButton = $('.nextWeek'),
		$timetable = $('.timet'),
		sdrq,
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
				sdrq = undefined;
			}
		});
	});

	var $switchView = $('#js_switchView'),
		$fpToggle = $('.js_fullPersonalToggle');

	function e_toggleAllTT(hide){
		$.ajax({
			method: "POST",
			url: `/timetables/showTimetable/${hide ? 'my' : 'all'}`,
			data: {dispDays: dispDays},
			success: function(data){
				showAllGroups = hide ? 0 : 1;
				dispDays = data.dispDays;
				if (!hide)
					$timetable.removeClass('single');
				$timetable.html(data.timetable);
				$bWButton.attr('disabled', data.lockBack);
				$switchView.attr('disabled', fullView = false);
				if (simpleView)
					$timetable.addClass('single');

				$fpToggle[hide?'removeClass':'addClass']('typcn-user')[!hide?'removeClass':'addClass']('typcn-group').html(
					hide
					? 'Teljes<span class="desktop-only"> nézet</span>'
					: 'Saját<span class="desktop-only"> órarend</span>'
				);

				$.Dialog.close();
			}
		});
	}
	$fpToggle.on('click',function(e){
		e.preventDefault();

		$.Dialog.wait();

		e_toggleAllTT(this.className.indexOf('typcn-group') === -1);
	});

	var e_switchView  = function(){
		simpleView = !simpleView;
		$('.timet')[(simpleView ? 'add' : 'remove') + 'Class']('single');

		$switchView.toggleClass('typcn-eye typcn-eye-outline').html((simpleView ? 'Hagyományos' : 'Kompakt')+'<span class="desktop-only"> nézet</span>');
	};
	$switchView.on('click',e_switchView);

	if (getUserSetting("timetable.defaultViewMode") == 'compact')
		e_switchView();
});
