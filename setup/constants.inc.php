<?php
	# Környezeti változó definiálása
	$ENV = array();

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

	// Mail exchange info
	define('MAIL_ADDR', '');
	define('MAIL_DISPNAME', '');
	define('MAIL_USRNAME', '');
	define('MAIL_PWD', '');
	define('MAIL_HOST', '');
	define('MAIL_PORT', 0);

	# Karbantartási információk beállítása
/**
 * Ennek a tömbnek az értékeivel szabályozható a karbantartási állapot mûködése, és a karbantartás ideje és állapota.
 *
 * Értékek:
 *      'enabled' => false, //a karbanntartási üzemmód enegedélyezett-e? (true vagy false)
 *      'start' => '1970-01-01 00:00:00', //a karbantartási üzemmód kezdete
 *          Lehetséges értékei és jelentésük:
 *              1) érvényes (példához hasonló) dátum és idõ: a karbantartás idejének kezdetét jelenti (amikor a karb. üzemmód érvénybe lép)
 *              2) érvénytelen dátum és idõ: a karbantartásnak nincs meghatározott idõtartama
 *      'end' => '1970-01-01 00:00:00', //a karbantartási üzemmód vége
 *          Lehetséges értékei és jelentésük:
 *              1) érvényes (példához hasonló) dátum és idõ: a karbantartás idejének végét jelenti (amikor a karb. üzemmód kikapcsol)
 *              2) érvénytelen dátum és idõ: a karbantartásnak nincs meghatározott idõtartama
 *      'requiredDoc' => '', //a karbantartási üzemmód alatt futtatandó .php kiterjesztésû fájl útvonala (a $root változó használható)
 */
	$ENV['maintenance'] = array(
		'enabled' => false,
		'start' => '',
		'end' => '',
		'requiredDoc' => '',
	);