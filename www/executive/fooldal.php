<?php
	$case  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case){
		case 'roles':
			$scase  = isset($ENV['URL'][1]) ? $ENV['URL'][1] : 'def';

			switch ($scase){
				case 'get':
					if (isset($user['tempSession']))
						System::Respond(false);

					$Roles = System::GetAvailableRoles();
					$roles = array();

					foreach ($Roles as $role){
						$Role = $role;

						if ($Role['entryId'] != 0)
							$Role['intezmeny'] = "{$Role['intezmeny']} {$Role['osztaly']} osztálya";

						$roles[] = $Role;
					}

					System::Respond('',1,array('roles' => $roles));
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