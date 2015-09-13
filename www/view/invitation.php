<?php
	if (!isset($ENV['URL'][0])) System::Redirect('/');
	$token = $ENV['URL'][0];
	if (strlen($token) != 12) System::Redirect('/');

	$data = $db->where('invitation',$token)->getOne('invitations');
	if (empty($data)) System::Redirect('/');
	if (!$data['active']) System::Redirect('/');
?>
<script>
	var Patterns = <?=json_encode(System::GetHtmlPatterns())?>;
</script>

<div id="main">
	<div id="wrap">
		<div id="mid">
			<div id="inner">
				<h1>CuStudy</h1>
				<div id='contentDiv'>
					<p>A regisztrációhoz kérünk töltsd ki az alábbi űrlapot az alapadataiddal:</p>
					<form id='baseDataForm'>
						<p>Felhasználónév: <input type='text' name='username' required></p>
						<p>Jelszó: <input type='password' name='password' required></p>
						<p>Jelszó megerősít.: <input type='password' name='verpasswd' required></p>
						<p>Teljes név: <input type='text' name='realname' value='<?=$data['name']?>' required></p>
						<p>E-mail cím: <input type='text' name='email' value='<?=$data['email']?>' readonly></p>
						<input type='hidden' name='token' value='<?=$token?>'>
						<p><button class='btn'>Alapadatok mentése és továbblépés</button></p>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>