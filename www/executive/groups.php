<?php
	$act = $ENV['URL'][0];

	switch ($act) {
		case 'add':
			if (!empty($ENV['POST']))
				$action = GroupTools::Add($ENV['POST']);
			else System::Respond();

			System::Respond(Message::Respond('groups.add',$action), $action == 0 ? 1 : 0);
		break;

		case 'edit':
			if (!empty($ENV['URL'][1]) && !empty($ENV['POST']))
				$action = GroupTools::Edit($ENV['URL'][1],$ENV['POST']);
			else System::Respond();

			System::Respond(Message::Respond('groups.edit',$action), $action == 0 ? 1 : 0);

		break;

		case 'delete':
			if (isset($ENV['POST']['id']))
				$action = GroupTools::Delete($ENV['POST']['id']);
			else System::Respond();

			System::Respond(Message::Respond('groups.delete',$action), $action == 0 ? 1 : 0);
		break;

		case 'theme':
			switch ($ENV['URL'][1]){
				case 'edit':
					$action = GroupThemeTools::Edit(end($ENV['URL']),$ENV['POST']);

					System::Respond(Message::Respond('groupThemes.edit',$action), $action == 0 ? 1 : 0);
				break;
			}
		break;
	}