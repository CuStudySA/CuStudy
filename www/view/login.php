<?php
	$case = !empty($ENV['URL'][0]) ? $ENV['URL'][0] : 'default';

	switch ($case){
		default: ?>
			<main>
				<!-- Amber flag start -->
				<p>Fejlesztés alatt álló szoftververzió!</p>
				<!-- Amber flag end -->
				<div id="main">
					<div id="wrap">
						<div id="mid">
							<div id="inner">
								<h1>CuStudy</h1>
								<!-- Amber flag start -->
								<h3> (Amber)</h3>
								<!-- Amber flag end -->
								<form id="loginform">

									<input type='text' name='username' placeholder='Felhasználónév' tabindex=1 autocomplete="off">
									<input type='password' name='password' placeholder='Jelszó' tabindex=2>
									<p><button class='btn' tabindex=4>Belépés</button> <label><input type="checkbox" name="remember" tabindex=3 checked> Megjegyzés</label></p>
								</form>
								<p class="or"><span class="line"></span><span class="text">VAGY</span><span class="line"></span></p>
								<p>Belépés külső szolgáltatóval:</p>
								<div id="extlogin-btns"><?php
			foreach (ExtConnTools::$apiShortName as $name => $class)
				echo "<a class='$class' href='/login/external/$name'></a>";
								?></div>
							</div>
						</div>
					</div>
				</div>
				<div id="links">
					<a href='#' id='pw-forgot'>Elfelejtett jelszó?</a> |
					<a href="mailto:mbalint987@pageloop.tk?subject=CuStudy%20Hibabejelentés">Hibabejelentés</a>
				</div>
<?php   break;

		case 'external':
			if (empty($ENV['URL'][1])) die();
			$provider = $ENV['URL'][1];

			if (!in_array($provider,array_keys($ENV['oAuthAPI']))) die();

			$apiName = ExtConnTools::$resolveAPIname[$provider];
			$api = new $apiName($ENV['oAuthAPI'][$provider]['id'],$ENV['oAuthAPI'][$provider]['secret'],"/login/external/{$provider}");

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
				$remoteUser = $api->getUserInfo($aToken);

				System::ExternalLogin($remoteUser['id'],$provider);
			}
		break;
	}
