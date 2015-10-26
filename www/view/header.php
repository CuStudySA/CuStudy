<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?=$pages[$do]['title']?> - CuStudy</title>
<?php
	foreach ($css_list as $value){
		print '<link rel="stylesheet" href="'.$rootdoc.'resources/css/'.$value.'">'."\n";
	}

	# Beépülő modulok betöltése
	if (!empty($pages[$do]['addons'])){
		foreach ($pages[$do]['addons'] as $addonName){
			if (empty($addons[$addonName]['css'])) continue;
			foreach ($addons[$addonName]['css'] as $css)
				print '<link rel="stylesheet" href="'.$rootdoc.'resources/addons/'.$css.'">'."\n";
		}
	}

	if (!isset($_REQUEST['no-header-js'])){ ?>
		<script src="<?=$rootdoc.'resources/js/'?>jquery.min.js"></script>
<?php }

	# Beépülő modulok betöltése
	if (!empty($pages[$do]['addons'])){
		foreach ($pages[$do]['addons'] as $addonName){
			if (empty($addons[$addonName]['js'])) continue;
			foreach ($addons[$addonName]['js'] as $js)
				print '<script src="'.$rootdoc.'resources/addons/'.$js.'"></script>'."\n";
		}
	} ?>

</head>
<body>
	<script>
		var _USRGRP = "<?=USRGRP?>";
	</script>
