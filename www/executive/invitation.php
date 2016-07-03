<?php
	if (isset($ENV['URL'][0])) $act = $ENV['URL'][0];
	else System::Respond();

	switch ($act){
		case 'registration':
			if (empty($ENV['POST']))
				System::Respond();
			$action = InviteTools::Registration($ENV['POST']);

			if (is_array($action)){
				if (count($action) == 2)
					System::Respond(array('html' => $action[0]));
				else
					System::Respond(null,0,array('nogroup' => true));
			}
			else
				System::Respond("A regisztráció sikertelenül zárult, mert ismeretlen hiba történt a művelet közben! (Hibakód: {$action})");
		break;

		case 'setGroupMembers':
			if (empty($ENV['POST']))
				System::Respond();
			$action = InviteTools::SetGroupMembers($ENV['POST']);

			if ($action == 0)
				System::Respond(true);
			else
				System::Respond("A regisztráció sikertelenül zárult, mert ismeretlen hiba történt a művelet közben! (Hibakód: {$action})");
		break;
	}
