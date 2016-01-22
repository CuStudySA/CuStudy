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

				System::Redirect('/profile');
			}
		break;

		default:
			$data = $db->where('userid', $user['id'])->get('ext_connections');
			$actprovs = [];
			foreach ($data as $entry)
				$actprovs[] = $entry['provider']; ?>

			<h1>Profilom szerkesztése</h1>
			<form id='dataform'>
				<label>
					<span>Felhasználónév <em>(nem módosítható)</em></span>
					<input type='text' disabled value='<?=$user['username']?>'>
				</label>
				<label>
					<span>Teljes név <em>(2-3 névtag - magyar betűk)</em></span>
					<input type='text' name='name' placeholder='Vezetéknév Utónév' required pattern='^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$' value='<?=$user['name']?>'>
				</label>
				<label>
					<span>E-mail cím <em>(valós e-mail cím)</em></span>
					<input type='text' name='email' placeholder='teszt@teszt.hu' pattern='^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9])?@[a-z0-9]+\.[a-z]{2,4}$' required value='<?=$user['email']?>'>
				</label>
				<div class="pwmod">
					<strong>Jelszóváltoztatás</strong>
					<label>
						<span>Új jelszó <em>(6-20 karakter)</em></span>
						<input type='password' name='password' placeholder='Új jelszó' pattern='^[\w\d]{6,20}$'>
					</label>
					<label>
						<span>Új jelszó megerősítése <em>(a fenti jelszó újraírása)</em></span>
						<input type='password' name='verpasswd' placeholder='Új jelszó megerősítése' pattern='^[\w\d]{6,20}$'>
					</label>
				</div>
				<label>
					<span><strong>Jelenlegi jelszó</strong> <em>(kötelező megadni)</em></span>
					<input type='password' name='oldpassword' placeholder='Jelenlegi jelszó' pattern='^[\w\d]{6,20}$'>
				</label>
				<button class="btn">Adatok mentése</button>
			</form>
			<h1 style='margin-top: 25px !important;'>Összekapcsolt fiókok</h1>
<?php       $diff = array_diff(array_keys(ExtConnTools::$apiDisplayName),$actprovs);
			$newConnVisible = count($diff); ?>
			<div id="extconn-list">
				<div class="conn-wrap"<?=!$newConnVisible?' style="display:none"':''?>>
					<div class="conn">
						<div class="text">
							<span class="n">Új fiók összekapcsolása</span>
							<span class="status">Válasszon szolgáltatót</span>
							<span class="actions">
								<select id='connect_s'><?php
			foreach ($diff as $entry){
				$provider = ExtConnTools::$apiDisplayName[$entry];
				echo "<option value='$entry'>$provider</option>";
			}                   ?></select> <button id='connect' class='btn'<?=!count($diff)?' disabled':''?>>Összekapcsolás</button>
							</span>
						</div>
					</div>
				</div><?php
			foreach($data as $entry){
				$provider = ExtConnTools::$apiDisplayName[$entry['provider']];
				$provClass = ExtConnTools::$apiShortName[$entry['provider']];
				$username = !empty($entry['email']) ? $entry['email'] : $entry['name'];
				$statusClass = 'typcn-'.(!$entry['active'] ? 'tick' : 'power');
				$statusText = ($entry['active'] ? '' : 'in').'aktív';
				$actBtnText = ($entry['active'] ? 'Dea' : 'A').'ktiválás';
				$picture = $entry['picture'];

				echo <<<HTML
<div class="conn-wrap" data-id="{$entry['id']}" data-prov="{$entry['provider']}">
	<div class="conn">
		<div class="icon">
			<img src="$picture">
			<div class="logo $provClass" title="$provider"></div>
		</div>
		<div class="text">
			<span class="n">$username</span>
			<span class="status">Összekapcsolás $statusText</span>
			<span class="actions">
				<button class='btn activeToggle typcn $statusClass'>$actBtnText</button>
				<button class='btn disconnect typcn typcn-media-eject'>Leválasztás</button>
			</span>
		</div>
	</div>
</div>
HTML;
            }
	}
