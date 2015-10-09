<h1 id=h1cim>A(z) <?=$ENV['class']['classid']?> osztály dokumentumai</h1>

<!-- <h2 class='title'>Nemrégiben hozzáadva</h2> -->
<ul class='files flex'>
<?php
	$data = $db->rawQuery("SELECT *
							FROM `files`
							WHERE `classid` = ?
							ORDER BY `time` DESC
							LIMIT 10",array($user['classid']));

	foreach($data as $file){ ?>
		<li>
			<div class="top">
				<span class='rovid'><?=$file['name']?></span>
				<span class='nev'><?=$file['description']?></span>
			</div>
			<div class="bottom">
				<a class="typcn typcn-info-large js_more_info" href="#<?=$file['id']?>" title="További információk"></a>
<?php           if (!System::PermCheck('admin')){ ?>
					<a class="typcn typcn-trash js_delete" href="#<?=$file['id']?>" title="Fájl törlése"></a>
<?php           } ?>
				<a class="typcn typcn-download" href="/files/download/<?=$file['id']?>" title="Fájl letöltése" download></a>
			</div>
		</li>
<?php }
	if (!System::PermCheck('editor')) { ?>
		<li class='new'>
			<div class="top">
				<span class='rovid'>Új dokumentum</span>
				<span class='nev'>Új dok. feltöltése</span>
			</div>
			<div class="bottom">
				<a class="typcn typcn-upload js_file_add" href="#" title="Fájl(ok) feltöltése"></a>
			</div>
		</li>
<?php } ?>
</ul>
<div class='uploadFileForm' style='display: none;'>
	<h3>Dokumentumok feltöltése</h3>
	<div class='fileFormContainer'>
		<div class='uploadContainer'>
			<input type="file" class='uploadField' name='uploadField'>
			<div class='infoContainer' style='display: none;'>
				<input type='text' name='fileTitle' placeholder='Dokumentum címe' required autofocus='true'>
				<textarea name='fileDesc' placeholder='Dokumentum tartalma, leírása' required></textarea>
			</div>
		</div>
	</div>
	<a href='#' class='js_uploadFiles btn'>Fájl(ok) feltöltése</a>
</div>