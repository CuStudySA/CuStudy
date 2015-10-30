<?php
	# K�rnyezeti v�ltoz� defini�l�sa
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

	# Karbantart�si inform�ci�k be�ll�t�sa
/**
 * Ennek a t�mbnek az �rt�keivel szab�lyozhat� a karbantart�si �llapot m�k�d�se, �s a karbantart�s ideje �s �llapota.
 *
 * �rt�kek:
 *      'enabled' => false, //a karbanntart�si �zemm�d eneged�lyezett-e? (true vagy false)
 *      'start' => '1970-01-01 00:00:00', //a karbantart�si �zemm�d kezdete
 *          Lehets�ges �rt�kei �s jelent�s�k:
 *              1) �rv�nyes (p�ld�hoz hasonl�) d�tum �s id�: a karbantart�s idej�nek kezdet�t jelenti (amikor a karb. �zemm�d �rv�nybe l�p)
 *              2) �rv�nytelen d�tum �s id�: a karbantart�snak nincs meghat�rozott id�tartama
 *      'end' => '1970-01-01 00:00:00', //a karbantart�si �zemm�d v�ge
 *          Lehets�ges �rt�kei �s jelent�s�k:
 *              1) �rv�nyes (p�ld�hoz hasonl�) d�tum �s id�: a karbantart�s idej�nek v�g�t jelenti (amikor a karb. �zemm�d kikapcsol)
 *              2) �rv�nytelen d�tum �s id�: a karbantart�snak nincs meghat�rozott id�tartama
 *      'requiredDoc' => '', //a karbantart�si �zemm�d alatt futtatand� .php kiterjeszt�s� f�jl �tvonala (a $root v�ltoz� haszn�lhat�)
 */
	$ENV['maintenance'] = array(
		'enabled' => false,
		'start' => '',
		'end' => '',
		'requiredDoc' => '',
	);