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

			System::Respond(Message::Respond('teachers.add',is_array($action) ? 0 : $action), is_array($action) ? 1 : 0, is_array($action) ? ['id' => $action[0]] : array());
		break;

		case 'edit':
			$action = TeacherTools::Edit($ENV['POST']);

			System::Respond(Message::Respond('teachers.edit',$action), $action == 0 ? 1 : 0);
		break;

		case 'delete':
			$action = TeacherTools::Delete($ENV['POST']['id']);

			System::Respond(Message::Respond('teachers.delete',$action), $action == 0 ? 1 : 0);
		break;

		default:
			System::Respond();
		break;
	}