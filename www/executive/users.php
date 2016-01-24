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
				'name' => $data['name'],
				'email' => $data['email'],
				'active' => $data['active'],
				'role' => $classMem['role'],
			);

			System::Respond('', 1, $json);
		break;

		case 'add':
			if (isset($ENV['POST']['username']))
				$action = UserTools::AddUser($ENV['POST']);

			else System::Respond();

			System::Respond(Message::Respond('users.add',is_array($action) ? 0 : $action), is_array($action) ? 1 : 0, is_array($action) ? ['id' => $action[0]] : array());
		break;

		case 'edit':
			if (isset($ENV['POST']['id'])){
				if ($user['id'] == $ENV['POST']['id']) System::Respond();
				$action = UserTools::ModifyUser($ENV['POST']['id'],$ENV['POST']);
			}
			else System::Respond();

			System::Respond(Message::Respond('users.edit',$action), $action == 0 ? 1 : 0);
		break;

		case 'delete':
			if (isset($ENV['POST']['id'])){
				if ($user['id'] == $ENV['POST']['id']) System::Respond();
				$action = UserTools::DeleteUser($ENV['POST']['id']);
			}
			else System::Respond();

			System::Respond(Message::Respond('users.delete',$action), $action == 0 ? 1 : 0);
		break;

		case 'editAccessData':
			if (!empty($ENV['POST']['id']) && !empty($ENV['POST'])){
				if ($user['id'] == $ENV['POST']['id']) System::Respond();
				$action = UserTools::EditAccessData($ENV['POST']['id'],$ENV['POST']);
			}

			System::Respond(Message::Respond('users.editAccessData',$action), $action == 0 ? 1 : 0);
		break;

		default:
			System::Respond();
		break;
	}