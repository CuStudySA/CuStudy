<?php
	# $root beállítása
	$repRoot = '/path/to/custudy';

	# Tevékenység beáálítása
	$_GET['do'] = 'mail.cron';

	# Engine meghívása
	require "{$repRoot}/www/engine.php";