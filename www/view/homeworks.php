<?php
	$action = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'default';

	switch($action){
		case 'new':
			//Timetable előkészítése renderléshez
			$TT = Timetable::GetHWTimeTable();

			$days = $TT['opt'];
			unset($TT['opt']);

			sort($days,SORT_NUMERIC);
			$days = array_splice($days,0,3);

			function RenderTT() { global $TT, $days; return Timetable::Render(null, $TT, $days); } ?>

			<script>
				var _dispDays = <?=json_encode($days)?>;
			</script>

			<h1>Új házi feladat hozzáadása</h1>

<?php		if (!empty($days)){ ?>
				<p><b>1. lépés:</b> Kattintással <b>válaszd ki azt az órát</b>, ahova szeretnéd hozzáadni a házi feladatot!</p>

				<p class='weekPickerP'>
					<button class='btn backWeek' disabled><< Vissza az előző napokra</button>
					<span class='startDate'>
						Kezdő nap megadása:
						<input type='date' value='<?=date('Y-m-d')?>' id='startDatePicker'>
					</span>
					<button class='btn nextWeek'>Előre a következő napokhoz >></button>
				</p>

				<div id='lessonPicker'><?=RenderTT()?></div>
				<p class='step2p'><b>2. lépés:</b> <b>Add meg</b> a feladat <b>szövegét</b>!</p>
				<p style='margin-top: 0'><textarea class='BBCodeEditor'></textarea></p>
				<button class='btn sendForm'>Adatok mentése</button> vagy <a href='/homeworks'>visszatérés a házi feladatokhoz</a>
<?php       }
			else print "<p>Úgy néz ki, hogy az osztály órarendje üres. Kérjük, tölstd fel azt az <a href='/timetables'>Órarend menüpont</a> segítségével!</p>";

		break;

		default:
			print "<h1>Házi feladatok</h1><div class='hwContent'>";
				HomeworkTools::RenderHomeworks(3,true);
			print "</div>";
		break;
	}