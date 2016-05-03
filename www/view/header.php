<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?=$pages[$do]['title']?> - CuStudy</title>
<link rel="shortcut icon" href="/favicon.ico">
<meta property="og:type" content="website" />
<meta property="og:image" content="<?=ABSPATH?>/resources/img/logo.png">
<meta property="og:title" content="CuStudy">
<meta property="og:url" content="<?=ABSPATH?>/">
<meta property="og:description" content="A CuStudy Software Alliance által tanulók számára fejlesztett webes alkalmazás">
<?php
	if (!empty(DEFAULT_THEME_COLOR))
		$color = DEFAULT_THEME_COLOR;
	if (!empty($pages[$do]['theme-color']))
		$color = $pages[$do]['theme-color'];

	if (!empty($color))
		print '<meta name="theme-color" content="'.$color.'">';

	foreach ($css_list as $value)
		echo "<link rel='stylesheet' href='{$rootdoc}resources/css/$value'>\n";

	# Beépülő modulok betöltése
	if (!empty($pages[$do]['addons'])){
		foreach ($pages[$do]['addons'] as $addonName){
			if (empty($addons[$addonName]['css'])) continue;
			foreach ($addons[$addonName]['css'] as $css)
				echo "<link rel='stylesheet' href='{$rootdoc}resources/addons/$css'>\n";
		}
	} ?>

	<script src='<?=$rootdoc?>resources/js/jquery.min.js'></script>
<?php
	# Beépülő modulok betöltése
	if (!empty($pages[$do]['addons'])){
		foreach ($pages[$do]['addons'] as $addonName){
			if (empty($addons[$addonName]['js'])) continue;
			foreach ($addons[$addonName]['js'] as $js)
				echo "<script src='{$rootdoc}resources/addons/$js'></script>\n";
		}
	} ?>
</head>
<body>
	<script>var _USRGRP="<?=ROLE?>"</script>

<?php if (isset($ENV['userSettings'])){ ?>
	<!-- User settings -->
	<script>
		var userSettings = <?=json_encode($ENV['userSettings'])?>;
	</script>
<?php } ?>

<?php
	if (!empty($ENV['sidebar'])){
		require dirname(__FILE__)."/sidebar.php";
		echo "<main>";
	}
