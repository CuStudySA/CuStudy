<?php
	$act = end($ENV['URL']);

	switch ($act) {
		case 'invite':
			if (empty($ENV['POST']['invitations'])) System::Respond();

			$action = InviteTools::BatchInvite($ENV['POST']['invitations']);

			if (is_array($action)) System::Respond('A felhasználók meghívása befejeződött, de néhány felhasználó meghívása nem sikerült.',0);
			else System::Respond('A felhasználók meghívása sikeresen befejeződött. A meghívók megérkezése azonban akár 12 órát is igénybe vehet!',1);
		break;

		case 'getPatterns':
			System::Respond('', 1, System::GetHtmlPatterns());
		break;

		case 'get':
			if (empty($ENV['POST']['id'])) System::Respond();

			$data = $db->rawQuery('SELECT *
									FROM `users`
									WHERE `id` = ? && `classid` = ?',array($ENV['POST']['id'],$user['classid']));

			if (empty($data)) System::Respond();
			else $data = $data[0];

			$json = array(
				'username' => $data['username'],
				'realname' => $data['realname'],
				'priv' => $data['priv'],
				'email' => $data['email'],
				'active' => $data['active'],
			);

			System::Respond('', 1, $json);
		break;

		case 'add':
			if (isset($ENV['POST']['username']))
				$action = UserTools::AddUser($ENV['POST'],true);

			else System::Respond();

			System::Respond(Message::Respond('users.add',$action), is_array($action) ? 1 : 0);
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

			if ($action === 0)
				System::Respond('A felhasználó törlése sikeres volt!',1);
			else
				System::Respond('A felhasználó törlése sikertelen volt, mert '.Message::GetError('deleteuser',$action).'! (Hibakód: '.$action.')');
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