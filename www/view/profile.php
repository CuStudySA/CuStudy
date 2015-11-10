<?php
	$do = !isset($ENV['URL'][0]) ? 'default' : $ENV['URL'][0];

	switch ($do) {
		case 'connect':
			if (!isset($ENV['URL'][1])) System::Redirect('/profile');
			$provider = $ENV['URL'][1];

			if (!in_array($provider,array_keys($ENV['oAuthAPI']))) die();

			$apiName = ExtConnTools::$resolveAPIname[$provider];
			$api = new $apiName($ENV['oAuthAPI'][$provider]['id'],$ENV['oAuthAPI'][$provider]['secret'],"/profile/connect/{$provider}");

			if (empty($ENV['GET']['code']))
				$api->getCode();

			else {
				$code = $ENV['GET']['code'];
				try {
					$Auth = $api->getTokens($code, 'authorization_code');
				}
				catch(oAuthRequestException $e){
					die(header(ABSPATH."/?errtype=remote&prov={$provider}"));
				}
				$aToken = $Auth['access_token'];
				$remUser = $api->getUserInfo($aToken);

				$data = $db->where('account_id',$remUser['id'])->where('provider',$provider)->getOne('ext_connections');

				if (!empty($data)){
					if ($data['userid'] == $user['userid']) System::Redirect('/profile?error=A fiók összekapcsolása nem sikerült, mert ez a fiók már össze van kapcsolva az ön CuStudy fiókjával!');
					else System::Redirect('/profile?error=A fiók összekapcsolása nem sikerült, mert ez a fiók egy másik CuStudy-felhasználóhoz korábban már össze lett kapcsolva!');
				}

				$data = $db->where('provider',$provider)->where('userid',$user['id'])->getOne('ext_connections');
				if (!empty($data)) System::Redirect('/profile?error=A fiók összekapcsolása nem sikerült, mert ez a fiók már össze van kapcsolva a kiválasztott szolgáltató valamely fiókjával!');

				$insertData = array();
				if ($provider == 'google')
					$insertData = array(
						'email' => $remUser['emails'][0]['value'],
						'picture' => $remUser['image']['url'],
					);

				$db->insert('ext_connections',array_merge(array(
					'userid' => $user['id'],
					'provider' => $provider,
					'account_id' => $remUser['id'],
					'name' => $remUser[$provider == 'google' ? 'displayName' : 'name'],
				),$insertData));

				die(header('Location: /profile'));
			}
		break;

		default:
			$data = $db->rawQuery('SELECT *
									FROM `ext_connections`
									WHERE `userid` = ?',array($user['id']));
			$actprovs = [];
			foreach ($data as $entry)
				$actprovs[] = $entry['provider']; ?>

			<h1>Profilom szerkesztése</h1>
			<form id='dataform'>
				<p>Felhasználónév: <input type='text' name='username' placeholder='felhasznalonev' pattern='^[a-zA-Z\d]{3,15}$' disabled value='<?=$user['username']?>'> <i>(nem módosítható)</i></p>
				<p>Teljes név: <input type='text' name='name' placeholder='Vezetéknév Utónév' required pattern='^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$' value='<?=$user['name']?>'> <i>(2-3 névtag - magyar betűk)</i></p>
				<p>Új jelszó: <input type='password' name='password' placeholder='Új jelszó' pattern='^[\w\d]{6,20}$'> <i>(csak jelszóváltoztatáskor - 6-20 karakter)</i></p>
				<p>Új jelszó megerősítése: <input type='password' name='verpasswd' placeholder='Új jelszó megerősítése' pattern='^[\w\d]{6,20}$'> <i>(a fenti jelszó újraírása)</i></p>
				<p>E-mail cím: <input type='text' name='email' placeholder='teszt@teszt.hu' pattern='^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9])?@[a-z0-9]+\.[a-z]{2,4}$' required value='<?=$user['email']?>'> <i>(valós e-mail cím)</i></p>
				<p><b>Jelenlegi jelszó: <input type='password' name='oldpassword' placeholder='Jelenlegi jelszó' pattern='^[\w\d]{6,20}$'></b></p>
				<p><button class="btn">Adatok mentése</button></p>
			</form>
			<h1 style='margin-top: 25px !important;'>Összekapcsolt fiókok</h1>
			<p style='margin-bottom: 0;'>Új fiók összekapcsolása: <select id='connect_s'>
<?php
			foreach (array_diff(array_keys(ExtConnTools::$apiDisplayName),$actprovs) as $entry){
				$provider = ExtConnTools::$apiDisplayName[$entry]; ?>
				<option value='<?=$entry?>'><?=$provider?></option>
<?php
			}
?>
			</select><a href='#' id='connect' class='btn' style='margin-left: 7px;'>Összekapcsolás</a></p>
<?php
			foreach($data as $entry){
				$provider = ExtConnTools::$apiDisplayName[$entry['provider']]; ?>

				<h2><?=$provider?>-fiók</h2>
				<div class='connected'>
					<p><b>Kapcsolat állapota: </b>Összekapcsolva, az összekapcsolás<?=!$entry['active'] ? ' nem' : ''?> aktív</p>
					<p><b>Fiók azonosítója: </b><?=$entry['account_id']?> (<?=!empty($entry['email']) ? $entry['email'] : $entry['name']?>)</p>
					<a href="#<?=$entry['id']?>" class="btn disconnect">Fiók leválasztása</a> <a href='#<?=$entry['id']?>' class='btn <?=$entry['active'] ? 'deactivate' : 'activate'?>'>Kapcsolat <?=$entry['active'] ? 'deaktiválása' : 'aktiválása'?></a>
				</div>
<?php       }
	}
