<?php

	// Database connection info
	define('DB_HOST', '');
	define('DB_USER', '');
	define('DB_PASS', '');
	define('DB_NAME', '');

	// Webhook
	define('BB_AUTHCODE','');
	
	// Git adatok
	$git = 'git';
	define('LATEST_COMMIT_ID',rtrim(shell_exec("$git rev-parse --short=7 HEAD")));
