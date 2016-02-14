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
 * Ennek a tömbnek az értékeivel szabályozható a karbantartási állapot működése, a karbantartás ideje és állapota.
 *
 * Értékek:
 *      'enabled' => false, //a karbanntartási üzemmód enegedélyezett-e? (true vagy false)
 *      'start' => '1970-01-01 00:00:00', //a karbantartási üzemmód kezdete
 *          Lehetséges értékei és jelentésük:
 *              1) érvényes (példához hasonló) dátum és idő: a karbantartás idejének kezdetét jelenti (amikor az üzemmód érvénybe lép)
 *              2) érvénytelen dátum és idő: a karbantartásnak nincs meghatározott időtartama
 *      'end' => '1970-01-01 00:00:00', // az üzemmód vége
 *          Lehetséges értékei és jelentésük:
 *              1) érvényes (példához hasonló) dátum és idő: a karbantartás idejének végét jelenti (amikor az üzemmód kikapcsol)
 *              2) érvénytelen dátum és idő: a karbantartásnak nincs meghatározott időtartama
 *      'requiredDoc' => '', //az üzemmód alatt futtatandó .php kiterjesztésű fájl elérési útja (a $root változó használható)
 */
	$ENV['maintenance'] = array(
		'enabled' => false,
		'start' => '',
		'end' => '',
		'requiredDoc' => '',
	);

/**
 * Külső szolgáltatókhoz tartozó kliens azonosítő és titkos kulcs tároló.
 *
 * Példa a lenti tömb egyik elemére:
 * array(
 *    'id' => '123456789' //kliens azonosítója
 *    'secret' => 'abcdefghijklmn', //klienshez tartozó titkos kulcs
 * )
 */
	$ENV['oAuthAPI'] = array(
	);

	# Mantis integráció
	define('MANTIS_HOST','');
	define('MANTIS_USER','');
	define('MAINTIS_PASS','');
	define('MANTIS_NAME','');
