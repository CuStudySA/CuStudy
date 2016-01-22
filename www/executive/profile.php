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

			System::Respond(Message::Respond('extConnTools.unlink',$action), $action == 0);
		break;

		case 'edit':
			if (empty($ENV['POST']))
				System::Respond();
			$action = UserTools::EditMyProfile($ENV['POST']);

			System::Respond(Message::Respond('extConnTools.editMyProfile',$action), $action == 0);
		break;
	}
