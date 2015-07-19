<?php
	# DogM Engine 1.0.4.1 - Developed by mbalint987 (member of BetonSoft)
	
	# Karakterkódolás beállítása
	header('Content-Type: text/html; charset=utf-8;');
	
	# Dok. gyökér meghatározása
	$root = $_SERVER['DOCUMENT_ROOT'];
	if (!preg_match('/\/$/',$root)) $root .= '/';
	
	$rootdoc = '/';
	define('ABSPATH','http://'.$_SERVER['SERVER_NAME']);
	
	# Adatbázis adatok beállítása
	$db_HOST = 'localhost';
	$db_USER = 'bhwbeta';
	$db_PASS = 'jellyBEAN22';
	$db_NAME = 'betonhomework';
	
	# Külső erőforrás fájlok betöltése
	include $root.'resources/php/Cookie.php';
	include $root.'resources/php/Database.php';

	# Adatbázis kapcsolat felépítése
	$db = new Database($db_NAME);
	
	# Funkciótár betöltése
	include $root.'resources/php/functions.php';

	# Scipt futattásának kezdeti idejének lekérése
	$ENV = array(
		'EXECTIME' => array(
			'start' => microtime(true),
		),
	);

	# POST és/vagy GET adatok ill. tevékenység lekérése
	if (!empty($_GET['do'])){
		$ENV['do'] = $_GET['do'];
		unset($_GET['do']);
	}
	
	$ENV['GET'] = $_GET;
	$ENV['POST'] = $_POST;
	$ENV['SERVER'] = $_SERVER;
	
	if (!empty($ENV['GET']['data'])){
		if (!System::InputCheck($ENV['GET']['data'],'suburl')){
			$ENV['URL'] = explode('/',$ENV['GET']['data']);
			unset($ENV['GET']['data']);
		}
		else
			unset($ENV['GET']['data']);
	}
	unset($_GET,$_POST);
	
	# Jogosultásgi szintek meghatározása
	define('USRGRP',System::CheckLogin());
	$PERM = array(
		'sysadmin' => 6,
		'schooladmin' => 5,
		'admin' => 4,
		'editor' => 3,
		'user' => 2,
		'guest' => 1,
	);
	define('USRPERM',$PERM[USRGRP]);

	# Szoftveradatok definiálása
	$ENV['SOFTWARE'] = array(
		'NAME' => 'CuStudy',
		'CODENAME' => 'BlueSky',
		'VER' => '0.2.1',
		'DEVELOPER' => 'BetonSoft',
		'DEV_STARTED' => '2014',
	);

	$ENV['ENGINE'] = array(
		'NAME' => 'DogM Engine',
		'CODENAME' => 'Cornsilk',
		'VER' => '2.2.1',
		'DEVELOPER' => 'Bálint Mészáros (BetonSoft)',
		'DEV_STARTED' => '2014',
	);

	$ENV['EE_MESSAGE'] = '';

	# Menüpontok beállítása
	$css = ['grid.css','header.css','theme.css','typicons.css','metro.css'];
	$js = ['metro-dialog.js','functions.jquery.js'];

	if (USRGRP !== 'guest') $js[] = 'signed_in.js';

	$pages = array(
		'fooldal' => array(
			'title' => 		'Kezdőoldal',
			'css' => 		[],
			'js' => 		[],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'fooldal',
		),

		'login' => array(
			'title' => 		'Bejelentkezés',
			'css' => 		['login.css'],
			'js' => 		['login/login.js'],
			'minperm' => 	'guest',
			'maxperm' => 	'guest',
			'reqdoc' => 	[],
			'file' => 		'login',
		),

		'404' => array(
			'title' => 		'404',
			'css' => 		[],
			'js' => 		[],
			'minperm' => 	'guest',
			'maxperm' => 	'',
			'reqdoc' => 	[],
			'file' => 		'404',
			'http_code' =>   404,
		),

		'users' => array(
			'title' => 		'Felh.-k kezelése',
			'css' => 		['users.css'],
			'js' => 		['users.js'],
			'customjs' =>   [],
			'minperm' => 	'admin',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'users',
		),

		'logs' => array(
			'title' => 		'Naplók megtekintése',
			'css' => 		['logs.css'],
			'js' => 		['logs/logs.js','dyntime.js'],
			'customjs' =>   [],
			'minperm' => 	'admin',
			'maxperm' => 	'sysadmin',
			'reqdoc' => 	[],
			'file' => 		'logs',
		),

		'lessons' => array(
			'title' => 		'Tantárgyak kezelése',
			'css' => 		['lessons.css','spectrum.css'],
			'js' => 		['spectrum.js','colorpicker.js','lessons.js'],
			'customjs' =>   [],
			'minperm' => 	'admin',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'lessons',
		),

		'timetables' => array(
			'title' => 		'Órarend kezelése',
			'css' => 		['jquery.powertip.min.css','timet.css'],
			'js' => 		['jquery.powertip.min.js','timetables/ttedit.js'],
			'customjs' =>   [],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'timetables',
		),

		'teachers' => array(
			'title' => 		'Tanárok kezelése',
			'css' => 		['teachers.css','spectrum.css'],
			'js' => 		['spectrum.js','colorpicker.js','teachers.js'],
			'customjs' =>   [],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'teachers',
		),

		'groups' => array(
			'title' => 		'Csoportok kezelése',
			'css' => 		['groups.css'],
			'js' => 		['groups/delete.js'],
			'customjs' =>   ['edit' => 'groups/edit.js','add' => 'groups/add.js', 'theme/edit' => 'groups/edittheme.js'],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'groups',
		),

		'googleauth' => array(
			'title' => 		'',
			'css' => 		[],
			'js' => 		[],
			'customjs' =>   [],
			'minperm' => 	'guest',
			'maxperm' => 	'guest',
			'reqdoc' => 	[],
			'file' => 		'googleauth',
		),

		'profile' => array(
			'title' => 		'Profilom szerkesztése',
			'css' => 		['profile.css'],
			'js' => 		['profile.js'],
			'customjs' =>   [],
			'minperm' => 	'user',
			'maxperm' => 	'sysadmin',
			'reqdoc' => 	[],
			'file' => 		'profile',
		),
	);

	# Tevékenység meghatározása
	if (empty($ENV['do']))
		$do = USRGRP !== 'guest' ? 'fooldal': 'login';
	else if (!isset($pages[$ENV['do']]) && $ENV['do'] != 'logout')
		$do = '404';
	else {
		if ($ENV['do'] == 'login' && USRGRP != 'guest') $do = 'fooldal';
		else $do = $ENV['do'];
	}

	# Kiléptetés
	if ($do === 'logout'){
		$status = !System::Logout();
		if ($_SERVER['REQUEST_METHOD'] === 'GET') header("Location: /");
		else {
			header('Content-Type: application/json;');
			echo json_encode(array('status' => $status));
		}
		die();
	}

	// 'Executive' rész \\
	if ($ENV['SERVER']['REQUEST_METHOD'] == 'POST'){
		# Jogosultság ellenörzése
		if (System::PermCheck($pages[$do]['minperm'],$pages[$do]['maxperm'])) Message::AccessDenied(true);

		# Létező oldal?
		if (!isset($pages[$do])) Message::Missing($do);

		# Létező fájl?
		if (!file_exists("executive/{$pages[$do]['file']}.php")) System::Respond();

		die(include "executive/{$pages[$do]['file']}.php");
	}

	// Oldal felépítése \\
	
	# Létezik a megjelenítésfájl?
	$resc = "view/{$pages[$do]['file']}.php";
	if (!file_exists($resc))
		Message::Missing($resc);
		
	# Léteznek-e az erőforrások?
	$css_list = array_merge($css, $pages[$do]['css']);
	$js_list = array_merge($js, $pages[$do]['js']);

	if (!empty($pages[$do]['customjs']) && !empty($ENV['URL'])){
		foreach($pages[$do]['customjs'] as $key => $value){
			if (strpos($key,'/') === false){
				if ($ENV['URL'][0] == $key)
					$js_list[] = $value;
			}
			else {
				$page_url = explode('/',$key);
				if ($ENV['URL'][0] == $page_url[0] && $ENV['URL'][1] == $page_url[1])
					$js_list[] = $value;
			}
		}
	}

	foreach ($css_list as $value){
		$resc = "resources/css/$value";
		if (!file_exists($root.$resc))
			Message::Missing($rootdoc.$resc);
	}

	foreach ($js_list as $value){
		$resc = "resources/js/$value";
		if (!file_exists($root.$resc))
			Message::Missing($rootdoc.$resc);
	}

	# HTTP státuszkód visszadaása
	if (isset($pages[$do]['http_code'])) Message::StatusCode($pages[$do]['http_code']);
	
	# Hozzáférési jogosultság ellenörzése
	if (System::PermCheck($pages[$do]['minperm'],$pages[$do]['maxperm'])) Message::AccessDenied();
	
	# Szükséges dokumentumok listájának előkészítése
	$def_doc = ['header','footer'];
	$doc_list = array_merge($pages[$do]['reqdoc'],$def_doc);

	if (USRGRP !== 'guest') array_splice($doc_list,1,0,['sidebar']);
	array_splice($doc_list,-1,0,[$pages[$do]['file']]);

	# Szükséges oldalak betöltése
	foreach ($doc_list as $doc)
		include "view/$doc.php";
?>