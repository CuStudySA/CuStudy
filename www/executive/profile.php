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

			System::Respond(Message::Respond('users.editMyProfile',$action), $action == 0);
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

		case 'settings':
			if (empty($ENV['POST']))
				System::Respond();

			$action = UserSettings::Apply($ENV['POST']);

			if (is_array($action))
				$response = System::Article($action[1],true).' részen belül '.System::Article($action[2],false,' "').'" beállítás értéke nem megfelelő! Próbáld meg <a href="javascript:window.location.reload()">újratölteni az oldalt</a> és azután módosítani.';
			else $response = Message::Respond('users.applySettings',$action);

			System::Respond($response, $action == 0);
		break;

		case '2fa':
			if (!isset($ENV['GET']['a']))
				System::Respond('Érvénytelen művelet');

			switch ($ENV['GET']['a']){
				case "enable":
					if (!empty($user['2fa']))
						System::Respond('A kétlépcsős azonosítás már engedélyezve van');

					if (!is_numeric($ENV['GET']['step']))
						System::Respond('Érvénytelen kérés');

					$tfa = UserTools::Get2FAObject();
					switch (intval($ENV['GET']['step'], 10)){
						case 2:
							$secret = $tfa->createSecret();
							System::Respond(array(
								'secret' => $secret,
								'qr' => $tfa->getQRCodeImageAsDataUri($user['username'], $secret)
							));
						break;
						case 3:
							if (!UserTools::Check2FACode($ENV['POST']['code'], $ENV['POST']['secret']))
								System::Respond('A megadott kód érvénytelen. Kérlek ellenőrizd, hogy az eszközön lévő idő pontos (a szerveridő '.date('Y-m-d H:i:s').') és próbálkozz újra.');

							$db->where('id', $user['id'])->update('users', array(
								'2fa' => ($user['2fa'] = $ENV['POST']['secret'])
							));
							UserTools::GenerateStore2FACodes();

							System::Respond('A kétlépcsős azonosítás sikeresen engedélyezve. Alább láthatod az egyszer használatos tartalék kódokat, amelyekkel az eszköz elvesztése esetén is hozzá tudsz férni a fiókodhoz. Írd le őket valahova, lehetőleg ne a számítógépeden tárold. A Profilom menüpontban később is meg tudod nézni ezeket a kódokat.'.UserTools::Get2FABackupCodes(),1,array(
								'twofactor_html' => UserTools::GetProfile2FASection(),
							));
						break;
						default:
							System::Respond('Érvénytelen lépés');
					}
				break;
				case "disable":
					if (empty($user['2fa']))
						System::Respond('A kétlépcsős azonosítás nince engedélyezve');

					$db->where('id', $user['id'])->update('users', array(
						'2fa' => ($user['2fa'] = null)
					));
					$db->where('userid', $user['id'])->delete('twofactor_backupcodes');

					System::Respond('Kétlépcsős azonosítás sikeresen kikapcsolva.',1,array(
						'twofactor_html' => UserTools::GetProfile2FASection(),
					));
				break;
				case "codes":
					if (empty($user['2fa']))
						System::Respond('A kétlépcsős azonosítás nince engedélyezve');

					if (isset($ENV['GET']['regen']))
						UserTools::GenerateStore2FACodes();

					System::Respond(array(
						'codes_html' => UserTools::Get2FABackupCodes(),
					));
				break;
				default:
					System::Respond('Érvénytelen művelet');
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
