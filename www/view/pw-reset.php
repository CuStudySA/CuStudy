<?php
	if (empty($ENV['GET']['key']))
		System::Redirect('/');

	$Reset = PasswordReset::GetRow($ENV['GET']['key']);
	if (!$Reset['expired']){
		$User = $db->where('id', $Reset['userid'])->getOne('users');
		if (empty($User))
			$Reset['expired'] = true;
	} ?>
<div id="main">
	<div id="wrap">
		<div id="mid">
			<div id="inner">
				<h1>CuStudy</h1>
				<div id='contentDiv'>
<?php   if (!$Reset['expired']){
			$User = $db->where('id', $Reset['userid'])->getOne('users'); ?>
					<p><?=$User['realname']?>, kérjük adja meg az új jelszavát:</p>
					<form id='pw-reset-form'>
						<p>Jelszó: <input type='password' name='password' required></p>
						<p>Jelszó megerősít.: <input type='password' name='verpasswd' required></p>
						<input type='hidden' name='hash' value='<?=urlencode($Reset['hash'])?>'>
						<p><button class='btn'>Jelszóváltoztatás</button></p>
					</form>
<?php   }
		else { ?>
					<p><?=empty($User)?'A kéréshez tartozó felhasználó nem létezik vagy törlésre került':'Ez a jelszóvisszaállítási kérelem már lejárt vagy érvénytelen'?>.</p>
					<a href='/' class='btn'>Vissza a főoldalra</a>
<?php   } ?>
				</div>
			</div>
		</div>
	</div>
</div>
