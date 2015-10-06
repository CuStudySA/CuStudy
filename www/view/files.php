<h1 id=h1cim>A(z) <?=$ENV['class']['classid']?> osztály dokumentumai</h1>

<h2 class='title'>Nemrégiben hozzáadva</h2>
<ul class='files flex'>
<?php
	$data = $db->rawQuery("SELECT *
							FROM `files`
							WHERE `classid` = ?
							ORDER BY `time` DESC
							LIMIT 10",array($user['classid']));
	define('MAX_DESC_LIMIT',30);

	foreach($data as $file){ ?>
		<li>
			<div class="top">
				<span class='rovid'><?=$file['name']?></span>
				<span class='nev'><?=$file['description'] == substr($file['description'],0,MAX_DESC_LIMIT) ? $file['description'] : substr($file['description'],0,MAX_DESC_LIMIT).'...'?></span>
			</div>
			<div class="bottom">
				<a class="typcn typcn-info-large js_more_info" href="#<?=$file['id']?>" title="További információk"></a>
				<a class="typcn typcn-trash js_delete" href="#<?=$file['id']?>" title="Fájl törlése"></a>
				<a class="typcn typcn-download" href="/files/download/<?=$file['id']?>" title="Fájl letöltése" download></a>
			</div>
		</li>
<?php } ?>
	<li class='new'>
		<div class="top">
			<span class='rovid'>Új dokumentum</span>
			<span class='nev'>Új dok. feltöltése</span>
		</div>
		<div class="bottom">
			<a class="typcn typcn-upload js_file_add" href="#" title="Fájl(ok) feltöltése"></a>
		</div>
	</li>
</ul>
<div class='uploadFileForm' style='display: none;'>
	<h3>Dokumentumok feltöltése</h3>
	<div class='fileFormContainer'>
		<div class='uploadContainer'>
			<input type="file" class='uploadField' name='uploadField'>
			<div class='infoContainer' style='display: none;'>
				<p class='fileTitle'><input type='text' name='fileTitle' placeholder='Dokumentum címe' required></p>
				<textarea name='fileDesc' placeholder='Dokumentum tartalma, leírása' required></textarea>
			</div>
		</div>
	</div>
	<a href='#' class='js_uploadFiles btn'>Fájl(ok) feltöltése</a>
</div>

<?php
	if (empty($data))
		print "<p>Nincs megjeleníthető dokumentum.</p>";