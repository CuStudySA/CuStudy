<?php
	if (!isset($ENV['URL'][0]))
		$case = 'default';
	else
		$case = $ENV['URL'][0];

	switch ($case){
		default:
			// Órarend előkészítése
			$TT = Timetable::GetHWTimeTable(null,null,false);

			$days = $TT['opt'];
			unset($TT['opt']);

			// Ha nincs elérhető óra, akkor irányítson át a Szerkesztői nézethez
			if (empty($days))
				System::Redirect('/timetables/edit');

			sort($days,SORT_NUMERIC);
			$days = array_splice($days,0,5);

			function RenderTT() { global $TT, $days; return Timetable::Render(null, $TT, $days); }

			print "<h1 id=h1cim>A személyre szabott órarendem</h1>"; ?>
			<script>var _dispDays = <?=json_encode($days)?></script>
			<a class='btn typcn typcn-pencil' href='/timetables/edit'>Szerkesztői nézet</a>
			<a class='btn js_showAllTT typcn typcn-group' href='#'>Teljes nézet</a>
			<a class='btn typcn typcn-eye' id='js_switchView' style='float: right;'>Kompakt nézet</a>
			<p class='weekPickerP'>
				<button class='btn backWeek' disabled><< Vissza az előző napokra</button>
				<span class='startDate'>
					Kezdő nap megadása:
					<input type='date' value='<?=date('Y-m-d')?>' id='startDatePicker'>
				</span>
				<button class='btn nextWeek'>Előre a következő napokhoz >></button>
			</p>

			<div id='lessonPicker'><?=RenderTT()?></div>
<?php
		break;

		case 'edit':
			print "<h1 id=h1cim>".System::Nevelo($ENV['class']['classid'],true)." osztály órarendje</h1>"; ?>

			<p>Órarend választása: <select id='select_tt'>
<?php       foreach (Timetable::$TT_Types as $key => $value){
				$selected = $key == 'a' ? ' selected' : '';
				print "<option value='{$key}'".$selected.">{$value} órarend</option>";
			} ?>
			</select> <a class='btn goToMyTT' href='/timetables'><< Visszalépés a saját órarendemhez</a></p>

			<h2>'A' órarend</h2>

<?php		echo '<div class="template" id="form-template">'.Timetable::ADD_FORM_HTML.'</div>';

			Timetable::Render('a', Timetable::GetTimeTable('a',true), null, true);
		break;

		case 'week':
			if (!isset($ENV['URL'][1])) System::Redirect('/timetables');
			$week = $ENV['URL'][1];
			if ($week != 'a' && $week != 'b') System::Redirect('/timetables');
			print "<h1 id=h1cim>".System::Nevelo($ENV['class']['classid'],true)." osztály órarendje</h1>"; ?>

			<p>Órarend választása: <select id='select_tt'>
<?php       foreach (Timetable::$TT_Types as $key => $value){
				$selected = $key == $week ? ' selected' : '';
				print "<option value='{$key}'".$selected.">{$value} órarend</option>";
			} ?>
			</select> <a class='btn goToMyTT' href='/timetables'><< Visszalépés a saját órarendemhez</a></p>

<?php		print "<h2>'".strtoupper($week)."' órarend</h2>";
			echo '<div class="template" id="form-template">'.Timetable::ADD_FORM_HTML.'</div>';
			Timetable::Render($week, Timetable::GetTimeTable($week,true), null, true);
		break;
	}
