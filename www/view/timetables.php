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
			<a class='btn typcn typcn-pencil' href='/timetables/edit'>Szerkesztői nézet</a>
			<a class='btn js_fullPersonalToggle typcn typcn-group'>Teljes nézet</a>
			<a class='btn typcn typcn-eye' id='js_switchView' style='float: right;'>Kompakt nézet</a>
			<p class='weekPickerP'>
				<button class='btn backWeek' disabled>&lt;&lt; Vissza az előző napokra</button>
				<span class='startDate'>
					Kezdő nap megadása:
					<input type='date' value='<?=date('Y-m-d',$days[0])?>' id='startDatePicker'>
				</span>
				<button class='btn nextWeek'>Előre a következő napokhoz &gt;&gt;</button>
			</p>

			<div id='lessonPicker'><?=$table?></div>
<?php
		break;

		case 'edit':
			print "<h1 id=h1cim>".System::Article($ENV['class']['classid'],true)." osztály órarendje</h1>"; ?>

			<p>Órarend választása: <select id='select_tt'>
<?php       foreach (Timetable::$TT_Types as $key => $value){
				$selected = $key == 'a' ? ' selected' : '';
				print "<option value='{$key}'".$selected.">{$value} órarend</option>";
			} ?>
			</select> <a class='btn goToMyTT' href='/timetables'><< Visszalépés a saját órarendemhez</a></p>

			<h2>'A' órarend</h2>

<?php		echo '<div class="template" id="form-template">'.Timetable::ADD_FORM_HTML.'</div>';

			echo Timetable::Render('a', Timetable::GetForWeek('a'), null, true, true);
		break;

		case 'week':
			if (!isset($ENV['URL'][1])) System::Redirect('/timetables');
			$week = $ENV['URL'][1];
			if ($week != 'a' && $week != 'b') System::Redirect('/timetables');
			print "<h1 id=h1cim>".System::Article($ENV['class']['classid'],true)." osztály órarendje</h1>"; ?>

			<p>Órarend választása: <select id='select_tt'>
<?php       foreach (Timetable::$TT_Types as $key => $value){
				$selected = $key == $week ? ' selected' : '';
				print "<option value='{$key}'".$selected.">{$value} órarend</option>";
			} ?>
			</select> <a class='btn goToMyTT' href='/timetables'><< Visszalépés a saját órarendemhez</a></p>

<?php		print "<h2>'".strtoupper($week)."' órarend</h2>";
			echo '<div class="template" id="form-template">'.Timetable::ADD_FORM_HTML.'</div>';
			// FIXME Paraméter-szám eltérées - a funkció 3 paramétert fogad, de 4-el van meghívva
			echo Timetable::Render($week, Timetable::GetForWeek($week), null, true);
		break;
	}
