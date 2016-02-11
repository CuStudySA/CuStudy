<?php
	function Page(){
		global $ENV,$db;

		if (ROLE != 'guest') return 1;
		if (!isset($ENV['URL'][0])) return 2;
		$token = $ENV['URL'][0];

		$data = $db->where('invitation',$token)->getOne('invitations');
		if (empty($data)) return 3;
		if (!$data['active']) return 4;

		return [$data,$token];
	}

	$action = Page();
	if (!is_array($action)) System::Redirect("/?invitationErr=".Message::Respond('invitation.view',$action));

	$data = $action[0];
	$token = $action[1];
?>
<script>
	var Patterns = <?=json_encode(System::GetHtmlPatterns())?>;
</script>
<main>
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
						<p>Teljes név: <input type='text' name='name' value='<?=$data['name']?>' required></p>
						<p>E-mail cím: <input type='text' name='email' value='<?=$data['email']?>' readonly></p>
						<input type='hidden' name='token' value='<?=$token?>'>
						<p><button class='btn'>Alapadatok mentése és továbblépés</button></p>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>