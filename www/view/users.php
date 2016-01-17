<?php

	$case = !isset($ENV['URL'][0]) ? 'default' : $ENV['URL'][0];

	switch ($case){
		default:
			$data = $db->rawQuery('SELECT u.*
									FROM `users` u
									LEFT JOIN `class_members` cm
									ON u.id = cm.userid
									WHERE cm.classid = ?
									ORDER BY u.name',array($user['class'][0])); ?>

			<script>
				var Patterns = <?=json_encode(System::GetHtmlPatterns())?>;
			</script>

			<h1 id=h1cim>A(z) <?=$ENV['class']['classid']?> felhasználóinak kezelése</h1>
			<ul class="customers flex">
<?php		foreach ($data as $subarray){
				$nev = explode(' ',$subarray['name']);
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
							<a class="typcn typcn-key js_user_editAccessData" href="#<?=$subarray['id']?>" title="Hozzáférési adatok módosítása"></a>
							<a class="typcn typcn-user-delete js_user_delete" href="#<?=$subarray['id']?>" title="Törlés"></a>
<?php                   } ?>
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
						<a class="typcn typcn-group js_invite" href="#" title="Felhasználók meghívása"></a>
						<a class="typcn typcn-user-add js_user_add" href="/users/add" title="Új felh. hozzáadása"></a>
					</div>
				</li>
			</ul>
			<div class='invite_form' style='display: none;'>
				<h3>Felhasználók meghívása</h3>

				<div class="lesson_list">
					<p class="l_l_addedtext">Meghívásra jelölt felhasználók:</p>
					<ul class="l_l_utag">
						<li class="l_l_empty">(nincs)</li>
					</ul>
				</div>

				<div class='add_lesson'>
					<p>Felhasználó neve: <input type='text' name='name' pattern='^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$' autocomplete="off" required></p>
					<p>Felhasználó e-mail címe: <input type='text' name='email' pattern='^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9])?@[a-z0-9]+\.[a-z]{2,4}$' autocomplete="off" required></p>
					<a href='#' class='btn addlesson'>Hozzáadás</a>
				</div>
				<button class='btn a_t_f_sendButton'>Felhasználók meghívása</button>
			</div>
<?php	break;
	}
