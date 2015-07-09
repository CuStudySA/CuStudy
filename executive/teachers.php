<?php
	$act = $ENV['URL'][0];

	switch ($act) {
		case 'add':
			$action = TeacherTools::Add($ENV['POST']);

			if ($action === 0)
				System::Respond('A tanár (és tantárgyak) hozzáadása sikeres volt!',1);
			else
				System::Respond('A tanár (vagy/és tantárgyak) hozzáadása sikertelen volt, mert '.Message::GetError('addteacher',$action).'! (Hibakód: '.$action.')');
		break;

		case 'edit':
			$action = TeacherTools::Edit($ENV['POST']);

			if ($action === 0)
				System::Respond('A tanár adatainak módosítása sikeres volt!',1);
			else
				System::Respond('A tanár adatainak módosítása sikertelen volt, mert '.Message::GetError('editteacher',$action).'! (Hibakód: '.$action.')');
		break;

		case 'delete':
			$action = TeacherTools::Delete($ENV['POST']['id']);

			if ($action === 0)
				System::Respond('A tanár törlése a rendszerből sikeres volt!',1);
			else
				System::Respond('A tanár törlése a rendszerből sikertelen volt, mert '.Message::GetError('deleteteacher',$action).'! (Hibakód: '.$action.')');
		break;
	}