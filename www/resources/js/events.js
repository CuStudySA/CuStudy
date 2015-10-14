$(function(){
	var $selectedEvent = undefined,
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
					$(this).addClass('selectedEvent');
					$selectedEvent = event;

					$('.js_edit').attr('disabled',false);
					$('.js_delete').attr('disabled',false);
				}
				else
					e_getEventInfos(event);
			}
		};

	// Naptár inicializálása
	$('#calendar').fullCalendar(calendarSettings);

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
							var $calendar = $('#calendar');
							$calendar.fullCalendar('destroy');
							$calendar.fullCalendar(calendarSettings);

							$.Dialog.close();
						}
						else $.Dialog.fail(title,data.message);
					}
				});
			});
		});
	});
});