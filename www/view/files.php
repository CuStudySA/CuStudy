<h1 id=h1cim><?=System::Article($ENV['class']['classid'], true)?> osztály dokumentumai</h1>

<?php
	$echo = FileTools::RenderList();

	if (empty($echo) && System::PermCheck('files.add'))
		print System::Notice('info','Nem találhatóak az osztályhoz kapcsolódó dokumentumok!');

	echo $echo;
	if (!System::PermCheck('files.add')){
		$Storage = FileTools::GetSpaceUsage(); ?>
<div id="storage-use">
<h2>Rendelkezésre álló tárhely</h2>
<p><?=$Storage['Used']?> (<?=$Storage['Used%']?>%) felhasználva az osztály számára elérhető <?=$Storage['Available']?>-ból.<br>Fájlonkénti maximális méret: <?=FileTools::GetMaxUploadSize()?></p>
<div class="indicator"><?php
	if (!is_string($Storage['Used%']) && $Storage['Used%'] > 0){
		$class = 'used '.($Storage['Used%'] > 75 ? 'high' : 'low');
		echo "<div class='$class' style='width:{$Storage['Used%']}%'></div>";
	}
?></div>

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
	<a href='#' class='js_uploadFiles btn typcn typcn-upload'>Feltöltés</a>
</div>
<?php } ?>
