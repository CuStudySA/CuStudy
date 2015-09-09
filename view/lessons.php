<?php
	$data = $db->rawQuery("SELECT le.id, le.name, le.color, t.name AS teacher
							FROM lessons le
							LEFT JOIN teachers t ON t.id = le.teacherid
							WHERE le.classid = ?
							ORDER BY le.name",array($user['classid'])); ?>

	<h1 id="h1cim">A(z) <?=$ENV['class']['classid']?> osztály tantárgyai</h1>
	<ul class='lessons flex'>
<?php foreach ($data as $subarray){
		if ($subarray['color'] == 'default')
			$color = 'rgba(0,0,0,.40)';
		else
			$color = $subarray['color']; ?>

		<li style='background-color: <?=$color?>' data-id='<?=$subarray['id']?>'>
			<div class="top">
				<span class='tantargy'><?=$subarray['name']?></span>
				<span class='tanar'><?=$subarray['teacher']?></span>
			</div>
<?php if (!System::PermCheck('admin')) { ?>
			<div class="bottom">
				<a class="typcn typcn-pencil js_lesson_edit" href="#<?=$subarray['id']?>" title="Módosítás"></a>
				<a class="typcn typcn-trash js_lesson_delete" href="#<?=$subarray['id']?>" title="Törlés"></a>
			</div>
<?php } ?>
		</li>
<?php }
	if (!System::PermCheck('admin')) { ?>?>
		<li class='new'>
			<div class="top clearfix">
				<span class='tantargy'>Új tantárgy</span>
				<span class='tanar'>Új tantárgy hozzáadása</span>
			</div>
			<div class="bottom">
				<a class="typcn typcn-plus js_lesson_add" href="#" title="Hozzáadás"></a>
			</div>
		</li>
<?php } ?>
	</ul>