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

			<h1 id=h1cim><?=System::Article($ENV['class']['classid'], true)?> osztály felhasználóinak kezelése</h1>
			<ul class="customers flex">
<?php		foreach ($data as $subarray){
				$nev = explode(' ',$subarray['name']);
				$vnev = array_splice($nev,0,1)[0];
				$knev = implode(' ',$nev); ?>
				<li data-id='<?=$subarray['id']?>'>
					<div class="top clearfix">
						<div class="left">
							<img class="picture" src="<?=UserTools::GetAvatarURL($subarray)?>">
							<span class="id">#<?=$subarray['id']?></span>
						</div>
						<div class="right">
							<span class="vnev"><?=$vnev?></span> <span class="knev"><?=$knev?></span>
						</div>
					</div>
					<div class="bottom">
<?php                   if ($subarray['id'] != $user['id']){ ?>
							<a class="typcn typcn-edit js_user_edit" href="#<?=$subarray['id']?>" title="Adatok módosítása"></a>
							<a class="typcn typcn-media-eject js_user_eject" href="#<?=$subarray['id']?>" title="Felhasználó osztálybeli szerepkörének törlése"></a>
<?php                   } ?>
					</div>
				</li>
<?php		} ?>
				<li class='new'>
					<div class="top clearfix">
						<div class="left">
							<span class="typcn typcn-starburst"></span>
						</div>
						<div class="right">
							<span class="vnev">Felhasználó</span> <span class="knev">hozzáadás</span>
						</div>
					</div>
					<div class="bottom">
						<a class="typcn typcn-mail js_invite" href="#" title="Felhasználók meghívása"></a>
					</div>
				</li>
			</ul>
			<div class='invite_form' style='display: none;'>
				<h3>Felhasználók meghívása</h3>

				<p>A meghívás módja megváltozott! A már létező felhasználók esetében <strong>csak egy új szerepkör kerül hozzáadásra a fiókhoz</strong>, és nem hozunk létre új fiókot!</p>

				<div class="lesson_list">
					<p class="l_l_addedtext">Meghívásra jelölt felhasználók:</p>
					<ul class="l_l_utag">
						<li class="l_l_empty">(nincs)</li>
					</ul>
				</div>

				<div class='add_lesson'>
					<p title="A rendszer ezt az értéket csak akkor veszi figyelmebe, ha a felhasználó még nem létezik a CuStudy rendszerben!">
						Felhasználó teljes neve: <input type='text' name='name' pattern='^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$' autocomplete="off" required>
					</p>
					<p title="Új felhasználó esetén ide küldjük ki a meghívót, meglévő felhasználót pedig ezen cím alapján azonosítjuk!">
						Felhasználó e-mail címe: <input type='text' name='email' pattern='^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9]+)?@[a-z0-9]+\.[a-z]{2,4}$' autocomplete="off" required>
					</p>
					<a href='#' class='btn addlesson'>Hozzáadás</a>
				</div>
				<button class='btn a_t_f_sendButton'>Felhasználók meghívása</button>
			</div>
<?php	break;
	}
