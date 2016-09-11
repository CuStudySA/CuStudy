<?php
	if (!isset($ENV['URL'][0]))
		$case = 'default';
	else
		$case = $ENV['URL'][0];

	switch ($case){
		default:
			// Órarend előkészítése
			$TT = Timetable::Get(null,null,false);
			$days = Timetable::CalcDays($TT, 5, true);

			$table = Timetable::Render(null, $TT, $days);  ?>

			<h1 id=h1cim>A személyre szabott órarendem</h1>
			<a class='btn typcn typcn-pencil' href='/timetables/edit'>Szerk<span class="mobile-only">.</span><span class="desktop-only">esztői nézet</span></a>
			<a class='btn js_fullPersonalToggle typcn typcn-group'>Teljes<span class="desktop-only"> nézet</span></a>
			<a class='btn typcn typcn-eye' id='js_switchView' style='float: right;'>Kompakt<span class="desktop-only"> nézet</span></a>
			<p class='weekPickerP'>
				<button class='btn backWeek' disabled>&laquo; Vissza<span class="desktop-only"> az előző napokra</span></button>
				<span class='startDate'>
					<span class="desktop-only">Kezdő nap megadása:</span>
					<input type='date' value='<?=date('Y-m-d',$days[0])?>' id='startDatePicker'>
				</span>
				<button class='btn nextWeek'>Előre<span class="desktop-only"> a következő napokhoz</span> &raquo;</button>
			</p>

			<div id='lessonPicker'><?=$table?></div>
<?php
		break;

		case 'edit':
			System::Redirect('/timetables/week/a');
		break;

		case 'week':
			if (!isset($ENV['URL'][1]))
				System::Redirect('/timetables');
			$week = $ENV['URL'][1];
			if (!Timetable::ValidateWeek($week))
				System::TempRedirect('/timetables');
			print "<h1 id=h1cim>".System::Article($ENV['class']['classid'],true)." osztály órarendjének szerkesztése</h1>"; ?>

			<p>Órarend<span class="desktop-only"> váltása</span>: <select id='select_tt'>
<?php       foreach (Timetable::$TT_Types as $key => $value){
				$selected = $key == $week ? ' selected' : '';
				print "<option value='$key'$selected>'$value' órarend</option>";
			} ?>
			</select> <a class='btn goToMyTT' href='/timetables'>&laquo; Visszalépés<span class="desktop-only"> a saját órarendemhez</span></a></p>

<?php		print "<h2>'".strtoupper($week)."' órarend</h2>";
			echo '<div class="template" id="form-template">'.Timetable::ADD_FORM_HTML.'</div>';
			echo Timetable::Render($week, Timetable::GetForWeek($week, true), null, true, true);
		break;
	}
