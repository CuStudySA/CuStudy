<?php
	$act = end($ENV['URL']);

	switch ($act) {
		case 'invite':
			if (empty($ENV['POST']['invitations'])) System::Respond();

			$data = InviteTools::BatchInvite($ENV['POST']['invitations']);

			if (!empty($data['invalidEntrys'])){
				$text = "<p>A meghívás befejeződött, de néhány meghívót nem sikerült elküldeni:\
							<ul>";

				foreach ($data['invalidEntrys'] as $array){
					$text .= "<li>{$array['name']} / {$array['email']} (hibakód: {$array['error']})</li>";
				}

				$text .= "</ul>";
			}
			else
				$text = "A felhasználók meghívása befejeződött, de a meghívók megérkezéséig néhány óra eltelhet!";

			System::Respond($text,!empty($data['invalidEntrys']) ? 0 : 1,array('enrolledUsers' => $data['enrolledUsers']));
		break;

		case 'getPatterns':
			System::Respond('', 1, System::GetHtmlPatterns());
		break;

		case 'get':
			if (empty($ENV['POST']['id'])) System::Respond();

			$data = $db->rawQuery('SELECT u.*
									FROM `users` u
									LEFT JOIN `class_members` cm
									ON u.id = cm.userid
									WHERE u.id = ? && cm.classid = ?',array($ENV['POST']['id'],$user['class'][0]));

			if (empty($data)) System::Respond();
			else $data = $data[0];

			$classMem = $db->where('userid',$ENV['POST']['id'])->where('classid',$user['class'][0])->getOne('class_members');

			$json = array(
				'username' => $data['username'],
				'email' => $data['email'],
				'role' => $classMem['role'],
			);

			System::Respond('', 1, $json);
		break;

		case 'edit':
			if (isset($ENV['POST']['id']))
				$action = UserTools::ModifyRole($ENV['POST']['id'],$ENV['POST']);
			else System::Respond();

			System::Respond(Message::Respond('users.modifyRole',is_array($action) ? 0 : $action), is_array($action) ? 1 : 0);
		break;

		case 'eject':
			if (isset($ENV['POST']['id']))
				$action = UserTools::EjectUser($ENV['POST']['id']);
			else System::Respond();

			System::Respond(Message::Respond('users.eject',is_array($action) ? 0 : $action), is_array($action) ? 1 : 0);
		break;

		default:
			System::Respond();
		break;
	}