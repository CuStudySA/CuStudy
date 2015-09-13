<?php
	if (!isset($ENV['URL'][0]))
		$case = 'default';
	else
		$case = $ENV['URL'][0];

	switch ($case){
		default:
			print "<h1 id=h1cim>".System::Nevelo($ENV['class']['classid'],true)." osztály órarendje</h1>"; ?>

			<p>Órarend választása: <select id='select_tt'>
<?php       foreach (Timetable::$TT_Types as $key => $value){
				$selected = $key == 'a' ? ' selected' : '';
				print "<option value='{$key}'".$selected.">{$value} órarend</option>";
			} ?>
			</select></p>

			<h2>'A' órarend</h2>

<?php		echo '<div class="template" id="form-template">'.Timetable::ADD_FORM_HTML.'</div>';
			// TODO A Timetable::Render max. 3 paramétert fogad el,viszont 4-et adsz meg
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
			</select></p>

<?php		print "<h2>'".strtoupper($week)."' órarend</h2>";
			echo '<div class="template" id="form-template">'.Timetable::ADD_FORM_HTML.'</div>';
			Timetable::Render($week, Timetable::GetTimeTable($week,true), null, true);
		break;
	}
