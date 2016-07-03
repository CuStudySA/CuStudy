<?php
	$case = !empty($ENV['URL'][0]) ? $ENV['URL'][0] : 'default';

	switch ($case){
		default: ?>
			<div id="links">
				<a href='#' id='pw-forgot'>Elfelejtett jelszó?</a> |
				<a href="mailto:mbalint987@pageloop.tk?subject=CuStudy%20Hibabejelentés">Hibabejelentés</a>
			</div>
			<!-- Amber flag start -->
			<p id='underDevelopment'>Fejlesztés alatt álló szoftververzió!</p>
			<!-- Amber flag end -->
			<div id="main">
				<div id="wrap">
					<div id="mid">
						<div id="inner">
							<div>
								<a href="/" title="Vissza a főoldalra" class="logo">
									<img src="/resources/img/logo-login.png">
								</a>
							</div>
							<h1>CuStudy</h1>
							<!-- Amber flag start -->
							<h3> (Amber)</h3>
							<!-- Amber flag end -->
<?php       if (!empty($ENV['GET']['r'])){ ?>
							<p class="redirect">A kért oldal megtekintéséhez be kell jelentkezned!</p>
<?php       } ?>
							<form id="loginform">
								<input type='text' name='username' placeholder='Felhasználónév' required tabindex=1 autocomplete="off">
								<input type='password' name='password' placeholder='Jelszó' required tabindex=2>
<?php       if (!empty($ENV['GET']['r'])){ ?>
								<input type='hidden' name='r' value='<?=$ENV['GET']['r']?>'>
<?php       } ?>
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
					System::Redirect(ABSPATH.'/?error='.urlencode(str_replace('@provider',ucfirst($provider),Message::Respond('extConnTools.login',6))));
					die(); //PhpStorm miatt
				}

				$aToken = $Auth['access_token'];
				$remoteUser = $api->getUserInfo($aToken);

				$action = System::ExternalLogin($remoteUser,$provider);

				if ($action === 0) System::Redirect('/#');
				else System::Redirect(ABSPATH.'/?error='.urlencode(str_replace('@provider',ucfirst($provider),Message::Respond('extConnTools.login',$action))));
			}
		break;
	}
