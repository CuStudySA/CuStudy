$(function(){
	$('#js_hideShowFilter').on('click',function(){
		var $btn = $(this),
			$filterForm = $('#filterFormContainer');

		$btn.toggleClass('typcn-arrow-up-thick typcn-arrow-down-thick hide show');

		$filterForm[$btn.hasClass('hide') ? 'show' : 'hide']();
		$btn.text($btn.hasClass('hide') ? 'Szűrőpanel összecsukása' : 'Szűrőpanel kinyitása');

		$btn.blur();
	});

	var timeEnabled = true,
		drpBaseConfig = {
			startOfWeek: 'monday',
			separator: ' ~ ',
			autoClose: false,
			language: 'hu',
		},
		optgroups = {};
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

	var $resultCont = $('#resultContainer'),
		$filterForm = $('#filterForm'),
		$formTempl = $("<form id='js_form'>\
							<label><span>Esemény címe</span><input type='text' name='title' required></label>\
							<label><span>Időtartam</span><input type='text' name='interval' id='dateRangePicker' required></label>\
	                        <label><input type='checkbox' name='isFullDay' value='1'> Egész napos esemény</label>\
	                        <label><input type='checkbox' name='isGlobal' value='1'> Globális esemény</label>\
							<label><span>Osztály</span><select name='classid' required></select></label>\
							<label><span>Esemény rövid leírása</span><textarea name='description' required></textarea></label>\
						</form>"),
		$editBtn = $.mk('button').attr('class','btn typcn typcn-pencil').text('Szerkesztés').on('click',function(e){
			e.preventDefault();

			var $tr = $(this).parents('tr'),
				id = $tr.children().first().text().trim();
			$.Dialog.wait('Esemény szerkesztése','Esemény adatainak lekérése');

			$.post('/system.events/get',pushToken({id:id}),function(data){
				if (typeof data === 'string'){
					console.log(data);
					$(window).trigger('ajaxerror');
					return false;
				}
				if (!data.status)
					return $.Dialog.fail(false,data.message);

				var $form = $formTempl.clone(true,true),
					$classid = $form.find('select[name=classid]');
				timeEnabled = data.isallday != 1;
				$form.find('input[name=isFullDay]').attr('checked',!timeEnabled);
				$form.find('input[name=isGlobal]').attr('checked', Boolean(data.global)).triggerHandler('change');
				$form.find('input[name=interval]').val(data.start + ' ~ ' + data.end);
				$form.find('input[name=title]').val(data.title);
				$form.find('textarea[name=description]').text(data.description);
				$classid.val(data.classid);

				$.Dialog.request(false, $form, 'js_form', 'Módosítások mentése', function(){
					$form.drpConfigure().on('submit',function(e){
						e.preventDefault();

						var data = pushToken($form.serializeForm());
						data.id = id;
						$.Dialog.wait(false, 'Módosítások mentése');

						$.post('/system.events/edit',data,function(data){
							if (typeof data === 'string'){
								console.log(data);
								$(window).trigger('ajaxerror');
								return false;
							}
							if (!data.status)
								return $.Dialog.fail(false,data.message);

							$filterForm.triggerHandler('submit');
						});
					});
				});
			});
		}),
		$deleteBtn = $.mk('button').attr('class','btn typcn typcn-trash').text('Törlés').on('click',function(e){
			e.preventDefault();

			var $tr = $(this).parents('tr');

			$.Dialog.confirm('Esemény törlése','Valóbaln törölni szeretnéd ezt az eseményt?',function(sure){
				if (!sure) return;

				var id = $tr.children().first().text().trim();
				$.Dialog.wait('Esemény törlése');

				$.post('/system.events/delete',pushToken({id:id}),function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}
					if (!data.status)
						return $.Dialog.fail(false,data.message);

					$tr.remove();
					$.Dialog.close();
				});
			});
		});
	$.each(window.classes,function(_, row){
		if (typeof optgroups[row.school] === 'undefined')
			optgroups[row.school] = $.mk('optgroup').attr('label', row.school);

		optgroups[row.school].append($.mk('option').attr('value', row.id).text(row.school+' '+row.name));
	});
	var _$classid = $formTempl.find('select[name=classid]'),
		$classFilter = $('#classid_selector');
	$.each(optgroups,function(_, $grp){
		_$classid.append($grp.clone());
		$classFilter.append($grp.clone());
	});
	$formTempl.find('input[name=isGlobal]').on('change',function(){
		$(this).parents('form').find('select[name=classid]').attr('disabled', this.checked);
	});

	function mkResultTR(row){
		var props = [];
		if (row.isallday)
			props.push('Egész napos');
		if (row.global)
			props.push('Globális esemény');
		return $.mk('tr').append(
			$.mk('td').text(row.id),
			$.mk('td').text(row.title),
			$.mk('td').html(row.start),
			$.mk('td').text(row.end),
			$.mk('td').text(props.length ? props.join(', ') : ' '),
			$.mk('td').attr('class', 'actions').append($editBtn.clone(true,true),$deleteBtn.clone(true,true))
		);
	}

	$filterForm.on('submit',function(e){
		e.preventDefault();

		$.Dialog.wait();

		$.post('/system.events/filter', $(this).serializeForm(), function(data){
			if (typeof data === 'string'){
				console.log(data);
				$(window).trigger('ajaxerror');
				return false;
			}
			if (!data.status)
				return $.Dialog.fail(false,data.message);

			var $resultTable = $.mk('table')
					.attr('class', 'resultTable')
					.append('<thead><tr><th>'+['ID','Cím','Kezdet','Befejezés','Tulajdonságok','Kezelés'].join('</th><th>')+'</th></tr></thead>'),
				$tbody = $.mk('tbody');
			if (data.events)
				$.each(data.events, function(_,row){
					$tbody.append(mkResultTR(row));
				});
			$resultCont.empty().append(
				"<h3>A lekérdezés eredménye: "+data.events.length+" esemény</h3>",
				$resultTable.append($tbody)
			);
			$.Dialog.close();
		});
	});

	$('#js_addEvent').on('click',function(e){
		e.preventDefault();

		var title = 'Esemény hozzáadása',
			$form = $formTempl.clone(true,true);

		$.Dialog.request(title,$form,'js_form','Mentés',function(){
			$form.drpConfigure().on('submit',function(e){
				e.preventDefault();

				var data = $form.serializeForm();
				$.Dialog.wait(title);

				$.post('/system.events/add', data, function(data){
					if (typeof data === 'string'){
						console.log(data);
						$(window).trigger('ajaxerror');
						return false;
					}
					if (!data.status) return $.Dialog.fail(title,data.message);

					if ($resultCont.is(':not(:empty)'))
						$filterForm.triggerHandler('submit');
				});
			});
		});
	});
});
