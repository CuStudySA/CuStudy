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
