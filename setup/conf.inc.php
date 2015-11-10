<?php
	# Konstansok, paraméterek betöltése
	require_once 'constants.inc.php';

	# Szoftveradatok definiálása
	$ENV['SOFTWARE'] = array(
		'NAME' => '',
		'CODENAME' => '',
		'VER' => '',
		'DEVELOPER' => '',
		'DEV_STARTED' => '',
		'COMMIT' => LATEST_COMMIT_ID,
	);

	$ENV['ENGINE'] = array(
		'NAME' => 'DogM Engine',
		'CODENAME' => 'Cornsilk',
		'VER' => '1.0',
		'DEVELOPER' => 'Bálint Mészáros (BetonSoft)',
		'DEV_STARTED' => '2014',
		'COMMIT' => 'unknown',
	);

	$ENV['EE_MESSAGE'] = '';

	# Beépülő modulok leírásai
/**
 * Az add-onok fájlait tároló tömb. Egy add-on tömbje:
 *
 * 'addon_neve' => array(
 *      'js' => array(), //az add-on JS fájlainak elérési útjait tároló tömb (vagy fájlnevei)
 *      'css' => array(), //az add-on CSS fájlainak elérési útjait tároló tömb (vagy fájlnevei)
 *      'php' => array(), //az add-on PHP fájlainak elérési útjait tároló tömb (vagy fájlnevei)
 * )
 */
	$addons = array(
	);

	# Menüpontok beállítása, JS és CSS fájlok tömbjeinek kezdeti értékadása
/**
 * A $CSS és a $JS tömbök tartalmazzák azon fájlok listáját, amit minden oldalletöltéskor be kell töltenie a betöltőprogramnak.
 */
	$css = [];
	$js = [];

/**
 * A tevékenységek (oldalak) tömbjeit tartalmazó tömb. Egy tevékenység tömbje:
 *
 * 'oldal_neve' => array(
 *		'title' => 		'', //címsorban megjelenő szöveg
 *		'css' => 		[], //tevékenység használatakor betöltendő CSS fájlok elérési útjai (vagy fájlnevei)
 *		'js' => 		[], //tevékenység használatakor betöltendő JS fájlok elérési útjai (vagy fájlnevei)
 *		'minperm' => 	'', //az oldal meglátogatásához szükséges minimum jogosultsági szint string formátumban (lsd. $PERM)
 *		'maxperm' => 	'', //az oldal meglátogatásához szükséges maximum jogosultsági szint string formátumban (lsd. $PERM)
 *		'reqdoc' => 	[], //nem használt érték (deprecated)
 *		'file' => 		'', //a betöltendő PHP fájl neve kiterjesztés nélkül
 *      'addons' =>     [], //a betöltendő add-onok nevei (lsd. $addons)
 *      'http_code' =>  000,//a visszaadandó HTTP státuszkód
 * )
 */
	$pages = array(
	);

	$Perm = array(
		'students' => array(
		),
	);

	$permKeyDB = array(
	);