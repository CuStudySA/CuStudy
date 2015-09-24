<?php
	if (USRGRP != 'guest')
		die(header('Location: /'));

	$username = Cookie::get('username'); ?>
<main>
<?php
	if (!isset($ENV['POST']['username']) && !isset($ENV['POST']['password'])) { ?>
	<div id="main">
		<div id="wrap">
			<div id="mid">
				<div id="inner">
					<h1>CuStudy</h1>
					<form id="loginform">

						<input type='text' name='username' placeholder='Felhasználónév' tabindex=1 autocomplete="off"
						<?=$username === false ? 'autofocus' : ''?> <?=$username !== false ? "value='{$username}'" : ''?> >

						<input type='password' name='password' placeholder='Jelszó' tabindex=2 <?=$username !== false ? 'autofocus' : ''?>>
						<p><button class='btn' tabindex=4>Belépés</button> <label><input type="checkbox" name="remember" tabindex=3 checked> Megjegyzés</label></p>
					</form>
					<div><a class='btn js_login_google' href='https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=<?=ExtConnTools::CLIENTID?>&redirect_uri=<?=ABSPATH?>/googleauth&scope=email'>Google-bejelentkezés</a></div>
				</div>
			</div>
		</div>
	</div>
<?php }
	else {
		$action = System::Login($ENV['POST']['username'],$ENV['POST']['password']);

		if ($action != 0)
			print "<p>Hiba történt, hibakód: $action</p>";
		else
			die(header('Location: /'));
	}
?>
<div id="links">
	<a href="mailto:webmaster@<?=$ENV['SERVER']['SERVER_NAME']?>?subject=CuStudy%20Hibabejelentés">Hibabejelentés</a>
</div>
