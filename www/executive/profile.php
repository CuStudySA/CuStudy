<?php
	$task = strtolower(array_slice($ENV['URL'],-1)[0]);
	switch ($task){
		case 'activate':
		case 'deactivate':
			if (empty($ENV['POST']['id']))
				System::Respond();
			$action = ExtConnTools::DeactAndAct($ENV['POST']['id'], $task);

			System::Respond(Message::Respond("extConnTools.$task",$action), $action == 0);
		break;

		case 'unlink':
			if (empty($ENV['POST']['id']))
				System::Respond();
			$action = ExtConnTools::Unlink($ENV['POST']['id']);

			System::Respond(Message::Respond("extConnTools.$task",$action), $action == 0);
		break;

		case 'edit':
			if (empty($ENV['POST']))
				System::Respond();
			$action = UserTools::EditMyProfile($ENV['POST']);

			System::Respond(Message::Respond('extConnTools.editMyProfile',$action), $action == 0);
		break;

		case 'setavatarprovider':
			$prov = !empty($ENV['POST']['provider']) ? $ENV['POST']['provider'] : null;
			$action = UserTools::SetAvatarProvider($prov);

			if ($action[0] != 0)
				System::Respond(Message::Respond("extConnTools.$task",$action[0]), 0);
			else {
				$connwrap = '';
				foreach (ExtConnTools::GetAvailProviders() as $entry)
					$connwrap .= ExtConnTools::GetConnWrap($entry);
				System::Respond(array(
					'connwraps' => $connwrap,
					'picture' => UserTools::GetAvatarURL($user),
				));
			}
		break;
	}

	if (isset($ENV['URL'][0]) ? $ENV['URL'][0] : '' == 'roles'){
		$case = isset($ENV['URL'][1]) ? $ENV['URL'][1] : '';

		switch ($case){
			case 'eject':
				if (!(isset($ENV['POST']['id']) && isset($ENV['POST']['password'])))
					System::Respond();

				$action = System::EjectRole($ENV['POST']['id'],$ENV['POST']['password']);

				System::Respond(Message::Respond('roles.eject',is_bool($action) ? 0 : $action), is_bool($action) ? 1 : 0, !is_bool($action) ? array() : array(
					'reload' => $action ? 1 : 0,
				));
			break;

			case 'changeDefault':
				if (!(isset($ENV['POST']['id'])))
					System::Respond();

				$action = System::ChangeDefaultRole($ENV['POST']['id']);

				System::Respond(Message::Respond('roles.changeDefault',$action),$action == 0 ? 1 : 0);
			break;
		}
	}
