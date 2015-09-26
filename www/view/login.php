<main>
	<div id="main">
		<div id="wrap">
			<div id="mid">
				<div id="inner">
					<h1>CuStudy</h1>
					<form id="loginform">

						<input type='text' name='username' placeholder='Felhasználónév' tabindex=1 autocomplete="off">
						<input type='password' name='password' placeholder='Jelszó' tabindex=2>
						<p><button class='btn' tabindex=4>Belépés</button> <label><input type="checkbox" name="remember" tabindex=3 checked> Megjegyzés</label></p>
					</form>
					<div><a class='js_login_google' href='https://accounts.google.com/o/oauth2/auth?response_type=code&client_id=<?=ExtConnTools::CLIENTID?>&redirect_uri=<?=ABSPATH?>/googleauth&scope=email'><img src="/resources/img/google_login.png"></a></div>
				</div>
			</div>
		</div>
	</div>
	<div id="links">
		<a href="mailto:mbalint987@pageloop.tk?subject=CuStudy%20Hibabejelentés">Hibabejelentés</a>
	</div>
