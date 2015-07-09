<?php
	if (!isset($ENV['URL'][0]))
		$case = 'default';
	else
		$case = $ENV['URL'][0];

	switch ($case){
		default:
			$data = $db->rawQuery('SELECT *
									FROM `users`
									WHERE `classid` = ?
									ORDER BY `realname`',array($user['classid'])); ?>

			<script>
				var Patterns = <?=json_encode(System::GetHtmlPatterns())?>;
			</script>

			<h1 id=h1cim>A(z) <?=$ENV['class']['classid']?> felhasználóinak kezelése</h1>
			<ul class="customers">
<?php		foreach ($data as $subarray){
				$nev = explode(' ',$subarray['realname']);
				$vnev = array_splice($nev,0,1)[0];
				$knev = implode(' ',$nev); ?>
				<li data-id='<?=$subarray['id']?>'>
					<div class="top clearfix">
						<div class="left">
							<span class="typcn typcn-user"></span>
							<span class="id">#<?=$subarray['id']?></span>
						</div>
						<div class="right">
							<span class="vnev"><?=$vnev?></span> <span class="knev"><?=$knev?></span>
						</div>
					</div>
					<div class="bottom">
<?php                   if ($subarray['id'] != $user['id']){ ?>
							<a class="typcn typcn-pencil js_user_edit" href="#<?=$subarray['id']?>" title="Adatok módosítása"></a>
							<a class="typcn typcn-key js_user_pwdedit" href="#<?=$subarray['id']?>" title="Hozzáférési adatok módosítása"></a>
							<a class="typcn typcn-user-delete js_user_delete" href="#<?=$subarray['id']?>" title="Törlés"></a>
<?php                   }
						else { ?>
							<a class="typcn typcn-times" href="#" title="Nincs engedélyezett művelet!"></a>
<?php                   }   ?>
					</div>
				</li>
<?php		} ?>
				<li class='new'>
					<div class="top clearfix">
						<div class="left">
							<span class="typcn typcn-user"></span>
							<span class="id">*</span>
						</div>
						<div class="right">
							<span class="vnev">Új</span> <span class="knev">felhasz.</span>
						</div>
					</div>
					<div class="bottom">
						<a class="typcn typcn-user-add js_user_add" href="/users/add" title="Új felh. hozzáadása"></a>
					</div>
				</li>
			</ul>
<?php	break;

		case 'add': ?>
				 <h1>Adja meg az új felhasználó adatait:</h1>
				 <form method='POST' action='/users/add' id=useradd>
					<p>Felhasználónév: <input type='text' name='username' placeholder='felhasznalonev' pattern='^[a-zA-Z\d]{3,15}$' required> <i>(3-15 karakter - számok és angol betűk)</i></p>
					<p>Teljes név: <input type='text' name='realname' placeholder='Vezetéknév Utónév' required pattern='^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$'> <i>(2-3 névtag - magyar betűk)</i></p>
					<p>Jelszó: <input type='password' name='password' placeholder='Jelszó' pattern='^[\w\d]{6,20}$'> <i>(nem kötelező - 6-20 karakter)</i></p>
					<p>Jelszó megerősítése: <input type='password' name='verpasswd' placeholder='Jelszó megerősítése' pattern='^[\w\d]{6,20}$'> <i>(a fenti jelszó újraírása)</i></p>
					<p>E-mail cím: <input type='text' name='email' placeholder='teszt@teszt.hu' pattern='^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9])?@[a-z0-9]+\.[a-z]{2,4}$' required> <i>(valós e-mail cím)</i></p>
					<p>Jogosultsági szint:
						<select name='priv'>
							<option value='user' selected>Ált. felhasználó</option>
							<option value='editor'>Szerkesztő</option>
							<option value='admin'>Csoport adminisztrátor</option>
						</select></p>
					<p>Aktív legyen?
						<select name='active'>
							<option value='1' selected>Igen</option>
							<option value='0'>Nem</option>
						</select></p>
					<p><button class="btn">Új felhasználó létrehozása</button> vagy <a href='/users'>visszalépés</a></p>
				</form>
<?php	break;

		case 'edit':
			if (System::InputCheck($ENV['URL'][1],'numeric')) die(header('Location: /users'));

			$userid = $ENV['URL'][1];
			if (UserTools::PermCheck($userid) || $user['id'] == $userid) die(header('Location: /users'));

			$data = $db->where('id',$userid)->getOne('users');

			if (empty($data)) die(header('Location: /users')); ?>
	       	<h1>Felhasználó szerkesztése (#<?=$data['username']?>)</h1>
				 <form method='POST' action='/users/edit' id=useredit>
					<p>Felhasználónév: <input type='text' name='username' value='<?=$data['username']?>' pattern='^[a-zA-Z\d]{3,15}$' required> <i>(3-15 karakter - számok és angol betűk)</i></p>
					<p>Teljes név: <input type='text' name='realname' value='<?=$data['realname']?>' required pattern='^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$'> <i>(2-3 névtag - magyar betűk)</i></p>
					<p>Jelszó: <input type='password' name='password' placeholder='Jelszó' pattern='^[\w\d]{6,20}$'> <i>(nem kötelező - 6-20 karakter)</i></p>
					<p>Jelszó megerősítése: <input type='password' name='verpasswd' placeholder='Jelszó megerősítése' pattern='^[\w\d]{6,20}$'> <i>(a fenti jelszó újraírása)</i></p>
					<p>E-mail cím: <input type='text' name='email' value='<?=$data['email']?>' pattern='^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9])?@[a-z0-9]+\.[a-z]{2,4}$' required> <i>(valós e-mail cím)</i></p>
					<p>Jogosultsági szint:
						<select name='priv'>
							<option value='user' <?=$data['priv'] == 'user' ? 'selected' : ''?>>Ált. felhasználó</option>
							<option value='editor' <?=$data['priv'] == 'editor' ? 'selected' : ''?>>Szerkesztő</option>
							<option value='admin' <?=$data['priv'] == 'admin' ? 'selected' : ''?>>Csoport adminisztrátor</option>
						</select></p>
					<p>Aktív legyen?
						<select name='active'>
							<option value='1' <?=$data['active'] ? 'selected' : ''?>>Igen</option>
							<option value='0'<?=!$data['active'] ? 'selected' : ''?>>Nem</option>
						</select></p>
						<input type='hidden' name='id' value='<?=$userid?>'>
					<p><button class="btn">Felhasználó adatainak szerkesztése</button> vagy <a href='/users'>visszalépés</a></p>
				</form>
<?php       break;
	}