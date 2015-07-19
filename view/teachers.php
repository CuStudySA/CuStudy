<?php
	if (!isset($ENV['URL'][0]))
		$case = 'default';
	else
		$case = $ENV['URL'][0];

	switch ($case){
		default:
			$data = $db->rawQuery("SELECT te.id, te.short, te.name
									FROM teachers te
									WHERE te.classid = ?
									ORDER BY te.short",array($user['classid'])); ?>

			<script>
				var Patterns = <?=json_encode(System::GetHtmlPatterns())?>;
			</script>

			<h1 id="h1cim">A(z) <?=$ENV['class']['classid']?> osztály tanárai</h1>
			<ul class='teachers'>
<?php foreach ($data as $subarray){  ?>
			<li data-id='<?=$subarray['id']?>'>
				<div class="top">
					<span class='rovid'><?=$subarray['short']?></span>
					<span class='nev'><?=$subarray['name']?></span>
				</div>
				<div class="bottom">
					<a class="typcn typcn-pencil js_teacher_edit" href="#<?=$subarray['id']?>" title="Módosítás"></a>
					<a class="typcn typcn-minus js_teacher_del" href="#<?=$subarray['id']?>" title="Törlés"></a>
				</div>
			</li>
<?php } ?>
		<li class='new'>
			<div class="top clearfix">
				<span class='rovid'>Új tanár</span>
				<span class='nev'>Új tanár hozzáadása</span>
			</div>
			<div class="bottom">
				<a class="typcn typcn-plus js_teacher_add" href="#" title="Hozzáadás"></a>
			</div>
		</li>
	</ul>
	<div class='add_teacher_form' style='display: none;'>
		<h3>Tanár hozzáadása</h3>
		<div class='teacher_info'>
			<p class='a_t_f_firstParagraph'>Tanár neve: <input type='text' name='name' placeholder='Tanár neve' pattern='^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű.]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$' autocomplete="off" required></p>

			<p>Tanár rövid neve: <input type='text' name='short' placeholder='A.B.C'
				pattern='^[A-ZÖÜÓÚŐÉÁŰa-zéáűőúöüó.]{2,}$' autocomplete="off" required></p>
		</div>

		<div class="lesson_list">
			<p class="l_l_addedtext">Létrehozandó tantárgyak:</p>
			<ul class="l_l_utag">
				<li class="l_l_empty">(nincs)</li>
			</ul>
		</div>

		<div class='add_lesson'>
			<p>Tantárgy neve: <input class='a_l_name' type='text' name='name' pattern='^[A-Za-zöüóőúéáűÖÜÓŐÚÉÁŰ.() ]{4,15}$' autocomplete="off" required></p>
			<p>Tantárgy színe: <input id='colorpicker' name='color' type='hidden' autocomplete="off"  value='#000000'></p>
			<a href='#' class='btn addlesson'>Hozzáadás</a>
		</div>
		<button class='btn a_t_f_sendButton'>Tanár hozzáadása</button>
	</div>
<?php   break;

		case 'add':
			$lessonlist = $db->rawQuery("SELECT le.name AS name, le.color AS color, le.id as lid
									FROM lessons le
									WHERE le.classid = ?",array($user['classid'])); ?>

			<h1>Adja meg a felvenni kívánt tanár adatait:</h1>
			<div class='teacher_info'>
				<p>Tanár neve: <input type='text' name='name' placeholder='Tanár neve' pattern='^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű.]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$' autocomplete="off" required>
					<i>(kötelező - 2-3 névtag, magyar betűk)</i></p>

				<p>Tanár rövid neve: <input type='text' name='short' placeholder='A.B.C'
					pattern='^[A-ZÖÜÓÚŐÉÁŰa-zéáűőúöüó.]{2,}$' autocomplete="off" required>
					<i>(kötelező - 2-3 névtag, magyar betűk)</i></p>
			</div>

			<div class="lesson_list">
				<p class="l_l_addedtext">Létrehozandó tantárgyak:</p>
				<ul class="l_l_utag">
					<li class="l_l_empty">(nincs)</li>
				</ul>
			</div>

			<div class='add_lesson'>
				<p>Tantárgy neve: <input class='a_l_name' type='text' name='name' pattern='^[A-Za-zöüóőúéáűÖÜÓŐÚÉÁŰ.() ]{4,15}$' autocomplete="off" required></p>
				<p>Tantárgy színe: <input id='colorpicker' name='color' type='hidden' autocomplete="off"  value='#000000'></p>
				<a href='#' class='btn addlesson'>Hozzáadás</a>
			</div>

			<p><a href='#' class='btn sendform'>Tanár (és tantárgyak) hozzáadása</a> vagy <a href='/teachers'>visszalépés</a></p>
<?php	break;

		case 'edit':
			$id = $ENV['URL'][1];
			if (System::InputCheck($id,'numeric')) die(header('Location: /teachers'));
			$data = $db->rawQuery('SELECT *
									FROM  `teachers`
									WHERE  `classid` = ? &&  `id` = ?',array($user['classid'],$id));
			if (empty($data)) die(header('Location: /teachers'));
			$data = $data[0]; ?>

			<h1>Adja meg a kiválasztott tanár új adatait:</h1>
			<form class='sendeditform'>
				<p>Tanár neve: <input type='text' name='name' pattern='^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű.]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$' autocomplete="off" required value='<?=$data['name']?>'>
					<i>(kötelező - 2-3 névtag, magyar betűk)</i></p>

				<p>Tanár rövid neve: <input type='text' name='short' placeholder='A.B.C'
					pattern='^[A-ZÖÜÓÚŐÉÁŰa-zéáűőúöüó.]{2,}$' autocomplete="off" required value='<?=$data['short']?>'>
					<i>(kötelező - rövidítés pontokkal vagy nélküle)</i></p>

				<input type='hidden' name='id' value='<?=$data['id']?>'>
				<p><button class="btn">Tanár szerkesztése</button> vagy <a href='/teachers'>visszalépés</a></p>
			</form>
<?php	break;
	}