<?php
	$case = !empty($ENV['URL'][0]) ? $ENV['URL'][0] : 'default';

	switch ($case){
		default: ?>
			<div id="heading">
				<div id="heading-content">
					<a href="/" class="logo-link"><img src="/resources/img/landing-logo-header.svg" alt="CuStudy logó"><h1>CuStudy</h1></a>
					<div class="help-wrap"><span class="btn typcn typcn-support help-link">Segítség</span></div>
				</div>
			</div>
			<div id="links" class="desktop-only">
				<a href='#' id='pw-forgot'>Elfelejtett jelszó?</a> |
				<a href="https://support.custudy.hu">Online ügyfélszolgálat</a>
			</div>
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
<?php       if (!empty($ENV['GET']['r'])){ ?>
							<p class="redirect">A kért oldal megtekintéséhez be kell jelentkezned!</p>
<?php       } ?>
							<form id="loginform">
								<div class="input-wrap">
									<input type='text' name='username' placeholder='Felhasználónév' required tabindex='1' autocomplete="off" spellcheck="false">
									<input type='password' name='password' placeholder='Jelszó' required tabindex='2'>
								</div>
<?php       if (!empty($ENV['GET']['r'])){ ?>
								<input type='hidden' name='r' value='<?=htmlspecialchars($ENV['GET']['r'], ENT_HTML5 | ENT_QUOTES)?>'>
<?php       } ?>
								<div>
									<button class='btn' tabindex=§4§>Belépés</button>&nbsp;
									<label>
										<input type="checkbox" name="remember" tabindex=3 checked> Megjegyzés
									</label>
								</div>
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

				if ($action === 0) System::TempRedirect('/#');
				else System::TempRedirect(ABSPATH.'/?error='.urlencode(str_replace('@provider',ucfirst($provider),Message::Respond('extConnTools.login',$action))));
			}
		break;
	}
