<?php
	$case  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case){
		case 'new':
			if (empty($ENV['POST'])) System::Respond();

			$action = HomeworkTools::Add($ENV['POST']);

			System::Respond(Message::Respond('homeworks.add',$action), $action == 0 ? 1 : 0);
		break;

		case 'delete':
			if (empty($ENV['POST']['id'])) System::Respond();

			$action = HomeworkTools::Delete($ENV['POST']['id']);

			System::Respond(Message::Respond('homeworks.delete',$action), $action == 0 ? 1 : 0);
		break;

		case 'makeMarkedDone':
			if (!empty($ENV['POST']['id'])){
				$action = HomeworkTools::MakeMarkedDone($ENV['POST']['id']);

				if (!empty($ENV['URL'][1]))
					HomeworkTools::RenderHomeworksMainpage();

				else {
					if ($action == 0)
						System::Respond('A kiválasztott házi feladat késznek lett jelölve, így az nem fog már megjelenni!',1);
					else
						System::Respond("A kiválasztott házi feladat nem lett késznek jelölve, mert ismeretlen hiba történt a művelet során! (Hibakód: {$action})",0);
				}
			}
		break;

		case 'undoMarkedDone':
			if (!empty($ENV['POST']['id'])){
				$action = HomeworkTools::UndoMarkedDone($ENV['POST']['id']);

				if ($action == 0)
					System::Respond('A kiválasztott házi feladat kész jelölése eltávolítva!',1);
				else
					System::Respond("A kiválasztott házi feladat kész jelölése nem lett eltávolítva, mert ismeretlen hiba történt a művelet során! (Hibakód: {$action})",0);
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
					$showAllGroups = isset($ENV['POST']['showAllGroups']) ? ($ENV['POST']['showAllGroups'] == 0 ? false : true) : true;

					Timetable::MoveNextBack($move,$dispDays,$showAllGroups);
				break;

				case 'date':
					$showAllGroups = isset($ENV['POST']['showAllGroups']) ? ($ENV['POST']['showAllGroups'] == 0 ? false : true) : true;
					Timetable::MoveDate($ENV['POST']['date'], isset($ENV['POST']['days']) ? (int)$ENV['POST']['days'] : 3,$showAllGroups);
				break;
			}
		break;
	}
