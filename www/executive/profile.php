<?php
	switch (end($ENV['URL'])){
		case 'deactivate':
			if (!empty($ENV['POST']))
				$action = ExtConnTools::DeactAndAct($ENV['POST']['id']);
			else System::Respond();

			System::Respond(Message::Respond('extConnTools.deactivate',$action), $action == 0 ? 1 : 0);
		break;

		case 'activate':
			if (!empty($ENV['POST']))
				$action = ExtConnTools::DeactAndAct($ENV['POST']['id'],'activate');
			else System::Respond();

			System::Respond(Message::Respond('extConnTools.activate',$action), $action == 0 ? 1 : 0);
		break;

		case 'unlink':
			if (!empty($ENV['POST']))
				$action = ExtConnTools::Unlink($ENV['POST']['id']);
			else System::Respond();

			System::Respond(Message::Respond('extConnTools.unlink',$action), $action == 0 ? 1 : 0);
		break;

		case 'edit':
			if (!empty($ENV['POST']))
				$action = UserTools::EditMyProfile($ENV['POST']);
			else System::Respond();

			System::Respond(Message::Respond('extConnTools.editMyProfile',$action), $action == 0 ? 1 : 0);
		break;
	}