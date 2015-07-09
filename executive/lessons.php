<?php
	$act = $ENV['URL'][0];

	switch ($act) {
		case 'add':
			if (isset($ENV['POST']['name']))
				$action = LessonTools::Add($ENV['POST']);

			else System::Respond();

			if (is_array($action))
				System::Respond('A tantárgy hozzáadása sikeres volt!',1,['id' => $action]);
			else
				System::Respond('A tantárgy hozzáadása sikertelen volt, mert '.Message::GetError('addlesson',$action).'! (Hibakód: '.$action.')');
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

					if (System::ClassPermCheck($lesid,'lessons')) System::Respond();

					$data = $db->rawQuery("SELECT le.name AS name, le.color AS color, t.id AS tid
											FROM lessons le
											LEFT JOIN teachers t ON t.id = le.teacherid
											WHERE le.classid = ? AND le.id = ?",array($user['classid'],$lesid));

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
												ORDER BY name",array($user['classid']));

					$json = array('options' => []);

					foreach ($teachers as $subt)
						$json['options'][] = "<option value='".$subt['id']."'>".$subt['name']."</option>";

					System::Respond('', 1, $json);
				break;
			}
		break;

		case 'delete':
			if (isset($ENV['POST']['id'])){
				$action = LessonTools::Delete($ENV['POST']['id']);

				$toLog = array(
					'action' => 'lesson_delete',
					'db' => 'lesson_del',
					'errorcode' => $action,
					'e_id' => intval($ENV['POST']['id']),
				);

				Logging::Insert($toLog);
			}
			else System::Respond();

			if ($action === 0)
				System::Respond('A tantárgy törlése sikeres volt!',1);
			else
				System::Respond('A tantárgy törlése sikertelen volt, mert '.Message::GetError('deletelesson',$action).'! (Hibakód: '.$action.')');
		break;

		case 'edit':
			if (isset($ENV['POST']['name'])){
				$action = LessonTools::Edit($ENV['POST']);

				$toLog = array(
					'action' => 'lesson_edit',
					'db' => 'lesson_edit',
					'errorcode' => $action,
				);

				if ($action != 99)
					$toLog = array_merge($toLog,array(
						'e_id' => $ENV['POST']['id'],
						'new_name' => $ENV['POST']['name'],
						'new_teacherid' => $ENV['POST']['teacherid'],
						'new_color' => !empty($ENV['POST']['color']) ? $ENV['POST']['color'] : 'default',
					));
				else
					System::WriteAttackLog($ENV['POST']);

				Logging::Insert($toLog);
			}
			else System::Respond();

			if ($action === 0)
				System::Respond('A tantárgy szerkesztése sikeres volt!',1);
			else
				System::Respond('A tantárgy szerkesztése sikertelen volt, mert '.Message::GetError('editlesson',$action).'! (Hibakód: '.$action.')');
		break;

	}