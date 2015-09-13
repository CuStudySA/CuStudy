<?php
	if (isset($ENV['URL'][0])) $act = $ENV['URL'][0];
	else System::Respond();

	switch ($act){
		case 'registration':
			if (!empty($ENV['POST'])) $action = InviteTools::Registration($ENV['POST']);
			else System::Respond();

			if (is_array($action))
				System::Respond('',1,array('html' => $action[0]));
			else
				System::Respond("A regisztráció sikertelenül zárult, mert ismeretlen hiba történt a művelet közben! (Hibakód: {$action})",0);
		break;

		case 'setGroupMembers':
			if (!empty($ENV['POST'])) $action = InviteTools::SetGroupMembers($ENV['POST']);
			else System::Respond();

			if ($action == 0)
				System::Respond('',1);
			else
				System::Respond("A regisztráció sikertelenül zárult, mert ismeretlen hiba történt a művelet közben! (Hibakód: {$action})",0);
		break;
	}