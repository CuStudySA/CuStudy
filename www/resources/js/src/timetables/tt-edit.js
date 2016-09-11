/*
 * JS add-on for CuStudy
 * @copyright (C) 2016 CuStudy Software Alliance
 */

$(function(){
	var title = "Tanárok és tantárgyak lekérése", $tds = $('table tbody td'),
		postDatas = {},
		USRGRP = _USRGRP,
		entireClassGroupName = 'Egész osztály';

	//Módosítást tároló tömbök létr.
	var container = {
		'delete': [],
		'add': [],
		'week': '',
	};

	//Órarend-választás <select> tag működése
	$('#select_tt').change(function(){
		var goto = (sure) => sure ? $.Dialog.wait(false,'Átirányítás',() => window.location.href = '/timetables/week/' + this.value) : false;
		if (container.delete.length || container.add.length)
			return $.Dialog.confirm('Órarend váltás','Ha órarendet váltasz a módosításaid elvesznek, folytatod?',goto);

		goto(true);
	});
	$(window).on('beforeunload',function(e){
		if (container.delete.length || container.add.length){
			let m = 'Ha másik oldalra lépsz, a módosításaid elvesznek.';
			e.returnValue = m;
			return m;
		}
	});

	if (USRGRP == 'user' || USRGRP == 'editor'){
		$('tr td').addClass('notAdmin');
		return;
	}

	// Órarend hozz.-t segítő listaelemek hozzáadása
	$.ajax({
		method: 'POST',
		url: '/timetables/getoptions',
		success: function(data){
			if (typeof data === 'string') return console.log(data) === $(window).trigger('ajaxerror');

			if (data.status){
				var $LOptions = $(document.createElement('div')),
					$GOptions = $(document.createElement('div')),
					hasLessons = data.lessons && data.lessons.length,
					gthemeoptgroups = {},
					teacheroptgroups = {};

				$.each(data.teachers,function(_,teacher){
					teacheroptgroups[teacher.id] = $.mk('optgroup').attr('label',teacher.name).appendTo($LOptions);
				});
				if (hasLessons) $.each(data.lessons,function(_,lesson){
					(lesson.teacherid?teacheroptgroups[lesson.teacherid]:$GOptions).append($.mk('option').attr('value',lesson.id).attr('data-name',lesson.name).text(lesson.name));
				});
				$GOptions.append($.mk('option').attr('value','0').text(entireClassGroupName));
				$.each(data.gthemes,function(_,gtheme){
					gthemeoptgroups[gtheme.id] = $.mk('optgroup').attr('label',gtheme.name).appendTo($GOptions);
				});
				$.each(data.groups,function(_,group){
					(group.theme?gthemeoptgroups[group.theme]:$GOptions).append($.mk('option').attr('value',group.id).text(group.name));
				});

				$.extend($.fn.powerTip.defaults,{
					placement:"se",
					mouseOnToPopup: true,
					smartPlacement: true,
					manual: true,
				});

				var $form = $('#form-template').children();
				$form.find('.groups').html($GOptions.children().clone());
				var _$lessons = $form.find('.lessons');
				if (hasLessons)
					_$lessons.html($LOptions.children().clone());
				else _$lessons.attr('disabled', true).html("<option disabled>(nincs hozzáadva tantárgy)</option>");
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

					if ($lessons.is('[disabled]'))
						return $.Dialog.fail('Óra hozzáadása','Nem található tantárgy az adatbázisban!<br>A folytatáshoz adj hozzá legalább egy tantárgyat a <a href="/lessons">Tantárgyak</a> menüpontban!');

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
						if (ntn == lesson.name)
							ntc = lesson.color;
					});

					if (grpnmef != entireClassGroupName)
						grpnme = ' (' + grpnmef + ')';

					$td.append("<span class='lesson' style='background: "+ ntc +"'>"+ ntn + grpnme +"<span class='del typcn typcn-times' data-ttid='#"+ nwelem.tantargy +"'></span></span>");
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
			dataid = $elem.attr('data-ttid');
		var $ttobj = $elem.parent(),
			$cella = $ttobj.parent();

		if (dataid.substring(0,1) != '#')
			container.delete.push({'id': dataid});
		else {
			var weekday = $cella.index(),
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

	var e_modify = function(){
		var title = 'Órarend mentése';
		container.week = $('.week').text();

		$.Dialog.wait(title);

		$.ajax({
			method: 'POST',
			url: '/timetables/save',
			data: container,
			success: function(data){
				if (typeof data === 'string') return console.log(data) === $(window).trigger('ajaxerror');

				if (!data.status) return $.Dialog.fail(title,data.message);

				$.Dialog.success(title,data.message);
				setTimeout(function(){
					location.reload();
				},2500);
			}
		});
	};

	$('.sendbtn').on('click',function(){
		if (container.delete.length == 0)
			return e_modify();
		$.Dialog.confirm('Órarend mentése',
			'Arra készülsz, hogy mented az órarend változtatásait, köztük számos bejegyzés törlését.<br>Ezzel a törölt bejegyzésekhez tartozó adatok is elvesznek.<br>Biztos vagy benne, hogy végrehajtod a változtatásokat?',
			['Változtatások mentése','Visszalépés'],

			function(sure){
				if (!sure) return;
				e_modify();
			}
		);
	});
});
