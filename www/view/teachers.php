<?php

	$case = !isset($ENV['URL'][0]) ? 'default' : $ENV['URL'][0];

	switch ($case){
		default:
			$data = $db->rawQuery("SELECT te.id, te.short, te.name
									FROM teachers te
									WHERE te.classid = ?
									ORDER BY te.short",array($user['class'][0])); ?>

			<script>
				var Patterns = <?=json_encode(System::GetHtmlPatterns())?>;
			</script>

			<h1 id="h1cim"><?=System::Article($ENV['class']['classid'], true)?> osztály tanárai</h1>
			<ul class='teachers flex'>
<?php foreach ($data as $subarray){  ?>
			<li data-id='<?=$subarray['id']?>'>
				<div class="top">
					<span class='rovid'><?=$subarray['short']?></span>
					<span class='nev'><?=$subarray['name']?></span>
				</div>
<?php       if (!System::PermCheck('teachers.edit') || !System::PermCheck('teachers.delete')) { ?>
						<div class="bottom">
<?php                     if (!System::PermCheck('teachers.edit')) { ?>
								<a class="typcn typcn-pencil js_teacher_edit" href="#<?=$subarray['id']?>" title="Módosítás"></a>
<?php                     }
							if (!System::PermCheck('teachers.delete')) { ?>
								<a class="typcn typcn-minus js_teacher_del" href="#<?=$subarray['id']?>" title="Törlés"></a>
<?php                     } ?>
						</div>
<?php       } ?>
					</li>
<?php }
	  if (!System::PermCheck('teachers.add')) { ?>
		<li class='new'>
			<div class="top clearfix">
				<span class='rovid'>Új tanár</span>
				<span class='nev'>Új tanár hozzáadása</span>
			</div>
			<div class="bottom">
				<a class="typcn typcn-plus js_teacher_add" href="#" title="Hozzáadás"></a>
			</div>
		</li>
<?php } ?>
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
	}
