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

				$data = $db->where('account_id',$remUser['account_id'])->where('provider',$provider)->getOne('ext_connections');

				if (!empty($data)){
					if ($data['userid'] == $user['userid']) System::Redirect('/profile?error=A fiók összekapcsolása nem sikerült, mert ez a fiók már össze van kapcsolva az ön CuStudy fiókjával!');
					else System::Redirect('/profile?error=A fiók összekapcsolása nem sikerült, mert ez a fiók egy másik CuStudy-felhasználóhoz korábban már össze lett kapcsolva!');
				}

				$data = $db->where('provider',$provider)->where('userid',$user['id'])->getOne('ext_connections');
				if (!empty($data)) System::Redirect('/profile?error=A fiók összekapcsolása nem sikerült, mert ez a fiók már össze van kapcsolva a kiválasztott szolgáltató valamely fiókjával!');

				$db->insert('ext_connections',array_merge(array(
					'userid' => $user['id'],
					'provider' => $provider,
				),$remUser));

				die(header('Location: /profile'));
			}
		break;

		default:
			$data = $db->where('userid', $user['id'])->get('ext_connections');
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
			<p>Új fiók összekapcsolása: <select id='connect_s'><?php

			$diff = array_diff(array_keys(ExtConnTools::$apiDisplayName),$actprovs);
			if (!count($diff))
				echo "<option value=''>(nincs elérhető szolg.)</option>";
			else foreach ($diff as $entry){
				$provider = ExtConnTools::$apiDisplayName[$entry];
				echo "<option value='$entry'>$provider</option>";
			}

			?></select>&nbsp;<button id='connect' class='btn'<?=!count($diff)?' disabled':''?>>Összekapcsolás</button></p>
			<div id="extconn-list"><?php
			foreach($data as $entry){
				$provider = ExtConnTools::$apiDisplayName[$entry['provider']];
				$provClass = ExtConnTools::$apiShortName[$entry['provider']];
				$username = !empty($entry['email']) ? $entry['email'] : $entry['name'];
				$statusClass = 'typcn-'.(!$entry['active'] ? 'tick' : 'power');
				$statusText = ($entry['active'] ? '' : 'in').'aktív';
				$actBtnClass = ($entry['active'] ? 'de' : '').'activate';
				$actBtnText = ($entry['active'] ? 'Dea' : 'A').'ktiválás';
				/* ?>

				<h2><?=$provider?>-fiók</h2>
				<div class='connected'>
					<p><b>Kapcsolat állapota: </b>Összekapcsolva, az összekapcsolás<?=!$entry['active'] ? ' nem' : ''?> aktív</p>
					<p><b>Fiók azonosítója: </b><?=$entry['account_id']?> (<?=!empty($entry['email']) ? $entry['email'] : $entry['name']?>)</p>
					<a href="#<?=$entry['id']?>" class="btn disconnect">Fiók leválasztása</a> <a href='#<?=$entry['id']?>' class='btn <?=$entry['active'] ? 'deactivate' : 'activate'?>'>Kapcsolat <?=$entry['active'] ? 'deaktiválása' : 'aktiválása'?></a>
				</div>
<?php       */
				echo <<<HTML
<div class="conn-wrap">
	<div class="conn" data-id="{$entry['id']}">
		<div class="icon $provClass" title="$provider"></div>
		<div class="text">
			<span class="n">$username</span>
			<span class="status">Összekapcsolás $statusText<br></span>
			<span class="actions">
				<button class='btn $actBtnClass typcn $statusClass'>$actBtnText</button>
				<button class='btn disconnect typcn typcn-media-eject'>Leválasztás</button>
			</span>
		</div>
	</div>
</div>
HTML;
            }
	}
