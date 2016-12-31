<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title><?=!empty($pages[$do]['title'])?$pages[$do]['title'].' - ':''?>CuStudy</title>

<link rel="apple-touch-icon" sizes="180x180" href="/resources/img/favicons-v1/apple-touch-icon.png">
<link rel="icon" type="image/png" href="/resources/img/favicons-v1/favicon-32x32.png" sizes="32x32">
<link rel="icon" type="image/png" href="/resources/img/favicons-v1/favicon-16x16.png" sizes="16x16">
<link rel="manifest" href="/resources/img/favicons-v1/manifest.json">
<link rel="mask-icon" href="/resources/img/favicons-v1/safari-pinned-tab.svg" color="#171d2d">
<link rel="shortcut icon" href="/resources/img/favicons-v1/favicon.ico">
<meta name="apple-mobile-web-app-title" content="CuStudy">
<meta name="application-name" content="CuStudy">
<meta name="msapplication-config" content="/resources/img/favicons-v1/browserconfig.xml">
<meta name="theme-color" content="#171d2d">

<meta property="og:type" content="website" />
<meta property="og:image" content="<?=ABSPATH?>/resources/img/logo.png">
<meta property="og:title" content="CuStudy">
<meta property="og:url" content="<?=ABSPATH?>/">
<meta property="og:description" content="A CuStudy Software Alliance által tanulók számára fejlesztett webes alkalmazás">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1, maximum-scale=1, user-scalable=no">
<?php

	foreach ($css_list as $value)
		echo "<link rel='stylesheet' href='$value'>\n";

	# Beépülő modulok betöltése
	if (!empty($pages[$do]['addons'])){
		foreach ($pages[$do]['addons'] as $addonName){
			if (empty($addons[$addonName]['css'])) continue;
			foreach ($addons[$addonName]['css'] as $css)
				echo "<link rel='stylesheet' href='{$rootdoc}resources/addons/$css'>\n";
		}
	} ?>

	<script src='<?=$rootdoc?>resources/js/min/jquery-1.11.2.js'></script>
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
<?php }

	echo System::GetMobileHeader($do);

	if (!empty($ENV['sidebar'])){
		require dirname(__FILE__)."/sidebar.php";
		echo "<main>";
	}
