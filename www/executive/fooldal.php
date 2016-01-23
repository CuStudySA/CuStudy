<?php
	$case  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case){
		case 'roles':
			$scase  = isset($ENV['URL'][1]) ? $ENV['URL'][1] : 'def';

			switch ($scase){
				case 'get':
					System::Respond('',1,array('roles' => System::GetAvailableRoles()));
				break;

				case 'set':
					if (isset($ENV['POST']['role']))
						$action = System::SetAvailableRoles($ENV['POST']['role']);
					else System::Respond();

					System::Respond(Message::Respond('roles.set',$action),$action == 0 ? 1 : 0);
				break;
			}
		break;
	}