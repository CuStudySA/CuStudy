$(function(){
	var $selectedEvent,
		inSelectMode = false,
		btn_switchToViewMode = "<button class='typcn typcn-zoom btn js_switchToViewMode'>Visszatérés nézegető módba</button>",
		btn_switchToSelectionMode = $('.js_switchToSelectionMode').clone(),
		timeEnabled = true;

	// TODO az ellenörzéshez használt ált.kif.-eket külön kell mozgatni, és itt valahogy hozzáadni pattern attribútumként
	var $formTempl = $("<form id='js_form'>\
							<label><span>Esemény címe</span><input type='text' name='title' required></label>\
							<label><span>Időtartam</span><input type='text' name='interval' id='dateRangePicker' required></label>\
	                        <label><input type='checkbox' name='isFullDay' value='1' autocomplete='off'> Egész napos esemény</label>\
							<label><span>Esemény rövid leírása</span><textarea name='description' required></textarea></label>\
						</form>");

	var e_getEventInfos = function(event){
			$.Dialog.wait('Esemény részletei','Esemény részleteinek lekérése');

			$.ajax({
				method: "POST",
				url: "/events/getEventInfos",
				data: {'id': event.id},
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}

					if (data.status)
						$.Dialog.info(false, data.html);
					else $.Dialog.fail(false, data.message);
				}
			});
		},
		calendarSettings = {
			events: '/events/getEvents',
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,basicWeek,basicDay'
			},
			eventLimit: true,
			height: 530,
			nextDayThreshold: '00:00:00',
			timezone: 'local',

			eventClick: function(event) {
				if (inSelectMode){
					$('.selectedEvent').removeClass('selectedEvent');
					$(this).addClass('selectedEvent');
					$selectedEvent = event;

					$('.js_edit').attr('disabled',false);
					$('.js_delete').attr('disabled',false);
				}
				else
					e_getEventInfos(event);
			}
		},
		$calendar = $('#calendar');

	// Naptár inicializálása
	$calendar.fullCalendar(calendarSettings);
	$calendar.fullCalendar( 'addEventSource', {
		url: '/events/getGlobalEvents',
		className: 'global-event',
	});

	var e_switchToSelectionMode = function(){
		$(this).blur();
		inSelectMode = true;

		$('.js_switchToSelectionMode').replaceWith(btn_switchToViewMode);
		$('.js_switchToViewMode').on('click',e_switchToViewMode);
	};
	$('.js_switchToSelectionMode').on('click',e_switchToSelectionMode);

	var e_switchToViewMode = function(){
		$(this).blur();
		inSelectMode = false;

		$('.js_edit').attr('disabled',true);
		$('.js_delete').attr('disabled',true);

		$selectedEvent = undefined;
		$('.selectedEvent').removeClass('selectedEvent');

		$('.js_switchToViewMode').replaceWith(btn_switchToSelectionMode);
		$('.js_switchToSelectionMode').on('click',e_switchToSelectionMode);
	};

	var drpBaseConfig = {
		startOfWeek: 'monday',
		separator: ' ~ ',
		autoClose: false,
		language: 'hu',
	};
	$.fn.drpConfigure = function(){
		var $form = this,
			$drp = $('#dateRangePicker'),
			$fullDay = $form.find('input[name=isFullDay]');

		$fullDay.on('change', function(){
			timeEnabled = !this.checked;
			if (typeof $drp.data('dateRangePicker') !== 'undefined')
				$drp.data('dateRangePicker').destroy();
			$drp.dateRangePicker($.extend({
				format: 'YYYY.MM.DD.'+(timeEnabled?' HH:mm':''),
				time: { enabled: timeEnabled },
			},drpBaseConfig));
			$drp.triggerHandler('change');
		}).triggerHandler('change');

		return this;
	};

	$('.js_add').on('click',function(e){
		e.preventDefault();

		var title = 'Esemény hozzáadása';

		$.Dialog.request(title,$formTempl.clone(),'js_form','Mentés',function($urlap){
			$urlap.drpConfigure().on('submit',function(e){
				e.preventDefault();

				var data = $urlap.serializeForm();
				$.Dialog.wait(title);

				$.post('/events/add', data, function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}
					if (!data.status) return $.Dialog.fail(title,data.message);

					$('#calendar').fullCalendar('refetchEvents');

					$.Dialog.close();
				});
			});
		});
	});


	$('.js_edit').on('click',function(e){
		e.preventDefault();

		var $dialog = $formTempl.clone();

		if (typeof $selectedEvent == 'undefined') return;

		$.Dialog.wait('Esemény szerkesztése', 'Esemény adatainak lekérése');

		$.ajax({
			method: "POST",
			url: "/events/getEventInfos/all",
			data: {'id': $selectedEvent.id},
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					timeEnabled = data.isallday != 1;
					$dialog.find('input[name=isFullDay]').attr('checked',!timeEnabled);
					$dialog.find('input[name=interval]').attr('value',data.start + ' ~ ' + data.end);
					$dialog.find('input[name=title]').attr('value',data.title);
					$dialog.find('textarea[name=description]').text(data.description);

					$.Dialog.request(false,$dialog,'js_form','Mentés',function($urlap){
						$urlap.drpConfigure().on('submit',function(e){
							e.preventDefault();

							var data = $urlap.serializeForm();
							data.id = $selectedEvent.id;
							$.Dialog.wait(false);

							$.post('/events/edit', data, function(data){
								if (typeof data === 'string'){
									console.log(data);
									$(window).trigger('ajaxerror');
									return false;
								}

								if (!data.status) return $.Dialog.fail(false,data.message);
								$('#calendar').fullCalendar('refetchEvents');

								$selectedEvent = undefined;
								$('.js_edit').attr('disabled',true);
								$('.js_delete').attr('disabled',true);

								$('.js_switchToViewMode').replaceWith(btn_switchToSelectionMode);
								$('.js_switchToSelectionMode').on('click',e_switchToSelectionMode);
								inSelectMode = false;

								$.Dialog.close();
							});
						});
					});
				}
				else return $.Dialog.fail(false,data.message);
			}
		});
	});

	$('.js_delete').on('click',function(e){
		e.preventDefault();

		var title = 'Esemény törlése';

		if (typeof $selectedEvent == 'undefined') return;

		$.Dialog.confirm(title,'Arra készülsz, hogy törlöd a kiválasztott eseményt! Folytatod?',['Esemény törlése','Visszalépés'],function(sure){
			if (!sure) return;

			$.Dialog.wait();

			$.ajax({
				method: "POST",
				url: "/events/delete",
				data: {'id': $selectedEvent.id},
				success: function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}
					if (data.status){
						$calendar.fullCalendar('refetchEvents');

						$selectedEvent = undefined;
						$('.js_edit').attr('disabled',true);
						$('.js_delete').attr('disabled',true);

						$('.js_switchToViewMode').replaceWith(btn_switchToSelectionMode);
						$('.js_switchToSelectionMode').on('click',e_switchToSelectionMode);
						inSelectMode = false;

						$.Dialog.close();
					}
					else $.Dialog.fail(title,data.message);
				}
			});
		});
	});
});
