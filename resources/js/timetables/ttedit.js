/* JS add-on for BetonHomeWork
 * Copyright(C) 2015. BetonSoft csoport */

$(function(){
	var title = "Tanárok és tantárgyak lekérése", $tds = $('table tbody td'),
		postDatas = {};

	// Órarend hozz.-t segítő listaelemek hozzáadása
	$.ajax({
		method: 'POST',
		url: '/timetables/getoptions',
		success: function(data){
			if (typeof data === 'string') return console.log(data) === $(window).trigger('ajaxerror');

			if (data.status){
				var $LOptions = $(document.createElement('div')),
					$GOptions = $(document.createElement('div'));

				$.each(data.lessons,function(_,lesson){
					$LOptions.append($(document.createElement('option')).attr('value',lesson.id).attr('data-name',lesson.name).text(lesson.name+' ('+lesson.teacher+')'));
				});
				$GOptions.append($(document.createElement('option')).attr('value','0').text('Teljes o.'));
				$.each(data.groups,function(_,group){
					$GOptions.append($(document.createElement('option')).attr('value',group.id).text(group.name));
				});

				$.extend($.fn.powerTip.defaults,{
					placement:"se",
					mouseOnToPopup: true,
					smartPlacement: true,
					manual: true,
				});

				var $form = $('#form-template').children();
				$form.find('.lessons').html($LOptions.children().clone());
				$form.find('.groups').html($GOptions.children().clone());
				$form.on('submit',function(e){
					// Órarend-elem hozzáadásakor
					e.preventDefault();
					var $form = $(this),
						toolTipIDArray = $form.parent().attr('id').split('-').slice(1),
						weekday = parseInt(toolTipIDArray[0]),
						lesson = parseInt(toolTipIDArray[1]),
						$td = $('.timet tbody tr').eq(lesson).children().eq(weekday),
						$selects = $form.find('select'),
						$groups = $selects.filter('[name=groups]'),
						$lessons = $selects.filter('[name=lessons]');

					var nwelem = {'group': $groups.val(), 'tantargy': $lessons.val(), 'lesson': lesson+1, 'day': weekday};
					container.add.push(nwelem);

					$td.removeClass('empty');
					if ($td.hasClass('editing')){
						$td.removeClass('editing');
						$.powerTip.hide();
					}

					var ntn = $lessons.find('option:selected').attr('data-name'),
						ntc = "#E1E4FA",
						grpnme = '',
						grpnmef = $groups.find('option:selected').text();
					$.each(postDatas.lessons,function(_,lesson){
						if (ntn == lesson.name) ntc = lesson.color;
					});

					if (grpnmef != 'Teljes o.')
						grpnme = ' (' + grpnmef + ')';

					$td.append("<span class='lesson' style='background: "+ ntc +"'>"+ ntn + grpnme +"<span class='del typcn typcn-times' data-id='#"+ nwelem.tantargy +"'></span></span>");
				});
				$tds.each(function(){
					var $add = $(this).find('.add'),
						$td = $add.parent(),
						popupId = 'add-'+$td.index()+'-'+$td.parent().index();

					$add.data('powertipjq',$form).powerTip({
						popupId: popupId,
					});
					$('.tooltips, #'+popupId).off('mouseenter mouseleave');
				});

				postDatas = data;
			}
			else $.Dialog.fail(title);
		}
	});

	//Órarend-választás <select> tag működése
	$('#select_tt').change(function(){
		window.location.href = '/timetables/week/' + $('#select_tt').children().filter(':selected').attr('value');
	});

	//Módosítást tároló tömbök létr.
	var container = {
		'delete': [],
		'add': [],
		'week': '',
	};

	$tds.on('click',function(e){
		e.preventDefault();
		e.stopPropagation();

		var $this = $(this),
			isEditing = $this.hasClass('editing');
		if (!isEditing) $tds.removeClass('editing');

		$this[(isEditing?'remove':'add')+'Class']('editing');
		if (!isEditing){
			$this.find('.add').removeClass('typcn-minus').addClass('typcn-plus');
			$.powerTip.hide();
		}
	}).find('.add').on('click',function(e){
		e.stopPropagation();
		var $this = $(this);

		if (!$this.parent().hasClass('editing'))
			return $this.parent().trigger('click');

		if ($this.hasClass('typcn-plus'))
			$this.powerTip('show');
		else $.powerTip.hide();

		$this.toggleClass('typcn-plus typcn-minus');
	});

	// Megnyitott elemre való kattintáskor eltűnik a szerkesztő
	$(document).on('click',function(e){
		var $target = $(e.target);
		if ($(e.target).closest('[id^="add-"]').length === 0 && $target.closest('.lesson-field').length === 0){
			$tds.removeClass('editing');
			$.powerTip.hide();
		}
	});

	// Törölt órarend-elemek listába foglalása
	$tds.on('click','.del',function(){
		var $elem = $(this),
			dataid = $elem.attr('data-id');
		var $ttobj = $elem.parent(),
			$cella = $ttobj.parent();

		if (dataid.substring(0,1) != '#')
			container.delete.push({'id': dataid});
		else {
			var weekday = $cella.index()+1,
				lesson = $cella.parent().index()+1,
				$ni = false,
				$lesson_e = dataid.substring(1);
			$.each(container.add,function(i,entry){
				if (weekday == entry.day && lesson == entry.lesson && $lesson_e == entry.tantargy)
					$ni = i;
			});
			if ($ni !== false){
				var seged = [];
				$.each(container.add,function(i,entry){
					if ($ni != i)
						seged.push(entry);
				});
				container.add = seged;
			}
		}

		if ($cella.children().length == 2) $cella.addClass('empty');
		$ttobj.remove();
	});

	$('.sendbtn').on('click',function(){
		var title = 'Órarend mentése';

		container.week = $('.week').text();
		$.Dialog.wait(title);
		$.ajax({
			method: 'POST',
			url: '/timetables/save',
			data: container,
			success: function(data){
				if (typeof data === 'string') return console.log(data) === $(window).trigger('ajaxerror');

				if (data.status){
					$.Dialog.success(title,data.message);
					setTimeout(function(){
						window.location.href = '/timetables';
					},2500);
				}

				else {
					$.Dialog.fail(title,data.message);
				}
			}
		});
	});

});