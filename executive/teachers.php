<?php
	if (!empty($ENV['URL'][0])) $act = $ENV['URL'][0];
	else System::Respond();

	switch ($act) {
		case 'get':
			if (!isset($ENV['POST']['id'])) System::Respond();

			$id = $ENV['POST']['id'];
			if (System::InputCheck($id,'numeric')) System::Respond();

			$data = $db->rawQuery('SELECT *
									FROM  `teachers`
									WHERE  `classid` = ? &&  `id` = ?',array($user['classid'],$id));
			if (empty($data)) System::Respond();

			System::Respond('',1,$data[0]);
		break;

		case 'add':
			$action = TeacherTools::Add($ENV['POST']);

			if (is_array($action))
				System::Respond('A tanár (és tantárgyak) hozzáadása sikeres volt!',1,array('id' => $action[0]));
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

		default:
			System::Respond();
		break;
	}