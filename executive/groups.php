<?php
	$act = $ENV['URL'][0];

	switch ($act) {
		case 'edit':
			if (!empty($ENV['URL'][1]) && !empty($ENV['POST']))
				$action = GroupTools::Edit($ENV['URL'][1],$ENV['POST']);
			else System::Respond();

			if ($action === 0)
				System::Respond('A csoport szerkesztése sikeres volt!',1);
			else
				System::Respond('A csoport szerkesztése sikertelen volt, mert '.Message::GetError('editgroup',$action).'! (Hibakód: '.$action.')');

		break;

		case 'add':
			if (!empty($ENV['POST']))
				$action = GroupTools::Add($ENV['POST']);
			else System::Respond();

			if ($action === 0)
				System::Respond('A csoport hozzáadása sikeres volt!',1);
			else
				System::Respond('A csoport hozzáadása sikertelen volt, mert '.Message::GetError('addgroup',$action).'! (Hibakód: '.$action.')');
		break;

		case 'delete':
			if (isset($ENV['POST']['id']))
				$action = GroupTools::Delete($ENV['POST']['id']);
			else System::Respond();

			if ($action === 0)
				System::Respond('A csoport törlése sikeres volt!',1);
			else
				System::Respond('A csoport törlése sikertelen volt, mert '.Message::GetError('delgroup',$action).'! (Hibakód: '.$action.')');
		break;

		case 'theme':
			switch ($ENV['URL'][1]){
				case 'edit':
					$action = GroupThemeTools::Edit(end($ENV['URL']),$ENV['POST']);

					if ($action === 0)
						System::Respond('A csoportkategória szerkesztése sikeres volt!',1);
					else
						System::Respond('A csoportkategória szerkesztése sikertelen volt, mert '.Message::GetError('editgrptheme',$action).'! (Hibakód: '.$action.')');
				break;
			}
		break;
	}