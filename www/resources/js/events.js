$(function(){
	var $selectedEvent,
		inSelectMode = false,
		btn_switchToViewMode = "<button class='typcn typcn-zoom btn js_switchToViewMode'>Visszatérés nézegető módba</button>",
		btn_switchToSelectionMode = $('.js_switchToSelectionMode').clone(),
		timeEnabled = true;

	var $formTempl = $("<form id='js_form'>\
	                        <p>Egész napos? <input type='checkbox' name='isFullDay' value='1'>ez az esemény egész napos</p>\
							<p>Időtartam: <input type='text' name='interval' id='dateRangePicker'></p>\
							<p>Cím: <input type='text' name='title' placeholder='Esemény címe' required></p>\
							<p>Rövid leírás: <br><textarea name='description' placeholder='Esemény leírása'></textarea></p>\
						</form>");

	var e_getEventInfos = function(event){
			var title = 'Esemény információinak lekérése';

			$.Dialog.wait(title);

			$.ajax({
					method: "POST",
					url: "/events/getEventInfos",
					data: pushToken({'id': event.id}),
					success: function(data){
						if (typeof data === 'string'){
							console.log(data);
							$(window).trigger('ajaxerror');
							return false;
						}
						if (data.status)
							$.Dialog.info(title,data.html);

						else $.Dialog.fail(title,data.message);
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

	$('.js_add').on('click',function(e){
		e.preventDefault();

		var $dialog = $formTempl.clone(),
			title = 'Esemény hozzáadása';

		$.Dialog.request(title,$dialog.prop('outerHTML'),'js_form','Mentés',function(){
			$('#dateRangePicker').dateRangePicker({
				startOfWeek: 'monday',
				separator : ' ~ ',
				format: 'YYYY.MM.DD HH:mm',
				autoClose: false,
				time: {
					enabled: true
				},
			});

			$('input[name=isFullDay]').change(function(){
				$('#dateRangePicker').data('dateRangePicker').destroy();
				$('#dateRangePicker').dateRangePicker({
					startOfWeek: 'monday',
					separator : ' ~ ',
					format: 'YYYY.MM.DD. HH:mm',
					autoClose: false,
					time: {
						enabled: !timeEnabled
					},
				});
				timeEnabled = !timeEnabled;
			});

			var $urlap = $('#js_form');

			$urlap.on('submit',function(e){
				e.preventDefault();

				$.Dialog.wait(title);

				$.ajax({
					method: "POST",
					url: "/events/add",
					data: $urlap.serializeForm(),
					success: function(data){
						if (typeof data === 'string'){
							console.log(data);
							$(window).trigger('ajaxerror');
							return false;
						}
						if (data.status){
							$calendar.fullCalendar('refetchEvents');

							$.Dialog.close();
						}
						else $.Dialog.fail(title,data.message);
					}
				});
			});
		});
	});


	$('.js_edit').on('click',function(e){
		e.preventDefault();

		var $dialog = $formTempl.clone(),
			title = 'Esemény szerkesztése';

		if (typeof $selectedEvent == 'undefined') return;

		$.ajax({
			method: "POST",
			url: "/events/getEventInfos/all",
			data: pushToken({'id': $selectedEvent.id}),
			success: function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (data.status){
					$dialog.find('input[name=isFullDay]').attr('checked',data.isallday == 1);
					$dialog.find('input[name=interval]').attr('value',data.start + ' ~ ' + data.end);
					$dialog.find('input[name=title]').attr('value',data.title);
					$dialog.find('textarea[name=description]').text(data.description);

					$.Dialog.request(title,$dialog.prop('outerHTML'),'js_form','Mentés',function(){
						$('#dateRangePicker').dateRangePicker({
							startOfWeek: 'monday',
							separator : ' ~ ',
							format: 'YYYY.MM.DD HH:mm',
							autoClose: false,
							time: {
								enabled: true
							},
						});

						$('input[name=isFullDay]').change(function(){
							$('#dateRangePicker').data('dateRangePicker').destroy();
							$('#dateRangePicker').dateRangePicker({
								startOfWeek: 'monday',
								separator : ' ~ ',
								format: 'YYYY.MM.DD. HH:mm',
								autoClose: false,
								time: {
									enabled: !timeEnabled
								},
							});
							timeEnabled = !timeEnabled;
						});

						var $urlap = $('#js_form');

						$urlap.on('submit',function(e){
							e.preventDefault();

							$.Dialog.wait(title);

							var $data = $urlap.serializeForm();
							$data['id'] = $selectedEvent.id;

							$.ajax({
								method: "POST",
								url: "/events/edit",
								data: $data,
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
				}
				else return $.Dialog.fail(title,data.message);
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
				data: pushToken({'id': $selectedEvent.id}),
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
