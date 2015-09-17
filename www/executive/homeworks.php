<?php
	$case  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case){
		case 'new':
			if (empty($ENV['POST'])) System::Respond();

			$action = HomeworkTools::Add($ENV['POST']);

			if ($action == 0) System::Respond('A házi feladat hozzáadása sikeresen befejezeődött!',1);
			else System::Respond('A házi feladat hozzáadása sikertelenül záródott, mert '.Message::GetError('rewriteThis',$action)."! (hibakód: $action)",0);
		break;

		case 'delete':
			if (empty($ENV['POST']['id'])) System::Respond();

			$action = HomeworkTools::Delete($ENV['POST']['id']);

			if ($action == 0) System::Respond('',1);
			else System::Respond('A házi feladat törlése sikertelenül záródott, mert '.Message::GetError('rewriteThis',$action)."! (hibakód: $action)",0);
		break;

		case 'makeMarkedDone':
			if (!empty($ENV['POST']['id'])){
				$action = HomeworkTools::MakeMarkedDone($ENV['POST']['id']);

				if ($action == 0)
					System::Respond('A kiválasztott házi feladat késznek lett jelölve, így az nem fog már megjelenni!',1);
				else
					System::Respond("A kiválasztott házi feladat nem lett késznek jelölve, mert ismeretlen hiba történt a művelet során! (Hibakód: {$action})",0);
			}
		break;

		case 'getDoneHomeworks':
			HomeworkTools::RenderHomeworks(3,false);
		break;

		case 'getNotDoneHomeworks':
			HomeworkTools::RenderHomeworks(3,true);
		break;

		case 'getTimetable':
			$case  = isset($ENV['URL'][1]) ? $ENV['URL'][1] : System::Respond();

			switch ($case){
				case 'nextBack':
					$move = $ENV['POST']['move'];
					$dispDays = $ENV['POST']['dispDays'];

					if (empty($dispDays)) return;

					if ($move == 'next') $fromDate = $dispDays[count($dispDays)-1];
					else $fromDate = strtotime('- 3 days',$dispDays[0]);

					$dates = [];

					while(count(array_diff($dates,$dispDays)) != count($dispDays)){
						$day = Timetable::GetDayNumber($fromDate);
						$week = Timetable::GetActualWeek(false,$fromDate);

						$TT = Timetable::GetHWTimeTable(date('W',$fromDate),$day);

						$dates = $TT['opt'];
						unset($TT['opt']);

						sort($dates,SORT_NUMERIC);
						$dates = array_splice($dates,0,3);

						$fromDate = strtotime(($move == 'next' ? '+' : '-').' 1 days',$fromDate);
					}

					Timetable::Render(null, $TT, $dates);
					$fDate = strtotime('12 am',$dates[0]);
					$now = strtotime('12 am');

					if (strtotime('- 1 days',$fDate) == $now) $lockBack = true;
					else if (Timetable::GetDayNumber() == 6 && strtotime('+ 2 days',$now) == $fDate) $lockBack = true;
					else if (Timetable::GetDayNumber() == 7 && strtotime('+ 1 days',$now) == $fDate) $lockBack = true;
					else $lockBack = false;

					?>
					<span class='dispDays'><?=json_encode($dates)?></span>
					<span class='lockBack'><?=json_encode($lockBack)?></span>
<?php
				break;

				case 'date':
					$date = strtotime('- 1 days',strtotime($ENV['POST']['date']));

					$week = date('W',$date);
					$day = Timetable::GetDayNumber($date);

					$TT = Timetable::GetHWTimeTable($week,$day);

					$dates = $TT['opt'];
					unset($TT['opt']);

					sort($dates,SORT_NUMERIC);
					$dates = array_splice($dates,0,3);

					Timetable::Render(null, $TT, $dates);

					$fDate = strtotime('12 am',$dates[0]);
					$now = strtotime('12 am');

					if ($fDate == $now) $lockBack = true;
					else if (Timetable::GetDayNumber() == 6 && strtotime('+ 2 days',$now) == $fDate) $lockBack = true;
					else if (Timetable::GetDayNumber() == 7 && strtotime('+ 1 days',$now) == $fDate) $lockBack = true;
					else $lockBack = false;

					?>
					<span class='dispDays'><?=json_encode($dates)?></span>
					<span class='lockBack'><?=json_encode($lockBack)?></span>
<?php           break;
			}
		break;
	}
