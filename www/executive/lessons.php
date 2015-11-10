<?php
	$act = $ENV['URL'][0];

	switch ($act) {
		case 'add':
			if (isset($ENV['POST']['name']))
				$action = LessonTools::Add($ENV['POST']);

			else System::Respond();

			System::Respond(Message::Respond('lessons.add',is_array($action) ? 0 : $action), is_array($action) ? 1 : 0, is_array($action) ? ['id' => $action[0]] : array());
		break;

		case 'edit':
			if (isset($ENV['POST']['name']))
				$action = LessonTools::Edit($ENV['POST']);

			else System::Respond();

			System::Respond(Message::Respond('lessons.edit',$action), $action == 0 ? 1 : 0);
		break;

		case 'delete':
			if (isset($ENV['POST']['id']))
				$action = LessonTools::Delete($ENV['POST']['id']);

			else System::Respond();

			System::Respond(Message::Respond('lessons.delete',$action), $action == 0 ? 1 : 0);
		break;

		case 'get':
			if (empty($ENV['URL'][1]))
				$func = 'default';
			else
				$func = $ENV['URL'][1];

			switch ($func){
				default:
					if (!isset($ENV['POST']['id'])) System::Respond();
					$lesid = $ENV['POST']['id'];

					if (System::InputCheck($lesid,'numeric')) System::Respond();

					if (System::PermCheck('lessons.edit',$lesid)) System::Respond();

					$data = $db->rawQuery("SELECT le.name AS name, le.color AS color, t.id AS tid
											FROM lessons le
											LEFT JOIN teachers t ON t.id = le.teacherid
											WHERE le.classid = ? AND le.id = ?",array($user['class'][0],$lesid));

					if (empty($data)) System::Respond();
					$data = $data[0];

					$json = array(
						'name' => $data['name'],
						'color' => $data['color'],
						'id' => $lesid,
						'teacherid' => $data['tid'],
					);

					System::Respond('', 1, $json);
				break;

				case 'teachers':
					$teachers = $db->rawQuery("SELECT *
												FROM teachers WHERE classid = ?
												ORDER BY name",array($user['class'][0]));

					$json = array('options' => []);

					foreach ($teachers as $subt)
						$json['options'][] = "<option value='".$subt['id']."'>".$subt['name']."</option>";

					System::Respond('', 1, $json);
				break;
			}
		break;
	}