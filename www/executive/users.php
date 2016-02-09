<?php
	$act = end($ENV['URL']);

	switch ($act) {
		case 'invite':
			if (empty($ENV['POST']['invitations'])) System::Respond();

			$action = InviteTools::BatchInvite($ENV['POST']['invitations']);

			System::Respond(Message::Respond('invitation.batchInvite',$action), $action == 0 ? 1 : 0);
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
			if (isset($ENV['POST']['id'])){
				if ($user['id'] == $ENV['POST']['id']) System::Respond();
				$action = UserTools::ModifyUser($ENV['POST']['id'],$ENV['POST']);
			}
			else System::Respond();

			System::Respond(Message::Respond('users.edit',$action), $action == 0 ? 1 : 0);
		break;

		case 'eject':
			if (isset($ENV['POST']['id']))
				$action = UserTools::EjectUser($ENV['POST']['id']);

			else System::Respond();

			System::Respond(Message::Respond('users.eject',$action), $action == 0 ? 1 : 0);
		break;

		default:
			System::Respond();
		break;
	}