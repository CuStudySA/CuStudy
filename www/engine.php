<?php
	# DogM Engine - Developed by mbalint987 (member of BetonSoft)

	# Konfigurációs fájl betöltése
	require "conf.inc.php";

	# Karakterkódolás beállítása
	header('Content-Type: text/html; charset=utf-8;');
	
	# Dok. gyökér meghatározása
	$root = $_SERVER['DOCUMENT_ROOT'];
	if (substr($root,-1) !== '/') $root .= '/';
	
	$rootdoc = '/';
	define('ABSPATH',(!empty($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['SERVER_NAME']);
	
	# Külső erőforrás fájlok betöltése
	require $root.'resources/php/Cookie.php';
	require $root.'resources/php/MysqliDb.php';

	# Adatbázis kapcsolat felépítése
	$db = new MysqliDb(DB_HOST,DB_USER,DB_PASS,DB_NAME);
	
	# Funkciótár betöltése
	require $root.'resources/php/functions.php';

	# Scipt futattásának kezdeti idejének lekérése
	$ENV['EXECTIME'] = array('start' => microtime(true));

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

	# Jogosultsági szintek beállítása
	define('USRGRP',System::CheckLogin());
	define('USRPERM',$PERM[USRGRP]);

	# Rendszerbeállítások lekérése
	GlobalSettings::Load();

	# Tevékenység meghatározása
	if (empty($ENV['do']))
		$do = USRGRP !== 'guest' ? 'fooldal': 'login';
	else if (!isset($pages[$ENV['do']]) && $ENV['do'] != 'logout'){
		if ($ENV['do'] === 'bb-webhook' && !empty($ENV['GET']['auth']) && $ENV['GET']['auth'] === BB_AUTHCODE){
			$out = array();
			exec("git reset HEAD --hard", $out);
			exec("git pull", $out);
			echo implode("<br>", $out);
			die();
		}
		$do = '404';
	}
	else {
		if ($ENV['do'] == 'login' && USRGRP != 'guest') $do = 'fooldal';
		else $do = $ENV['do'];
	}

	if ($do === "login" || $do === "fooldal")
		System::FixPath('/');

	# Kiléptetés
	if ($do === 'logout'){
		$status = !System::Logout();
		if ($_SERVER['REQUEST_METHOD'] === 'GET') System::Redirect('/');
		else System::Respond(true);
	}

	# Események lekérésénél 'Executive' végrehajtása
	if (!empty($ENV['URL'][0]))
		if ($ENV['URL'][0] == 'getEvents' && $do == 'events'){
			$ENV['SERVER']['REQUEST_METHOD'] = 'POST';
			$skipCSRF = true;
		}

	// 'Executive' rész \\
	if ($ENV['SERVER']['REQUEST_METHOD'] == 'POST'){
		# Jogosultság ellenörzése
		if (System::PermCheck($pages[$do]['minperm'],$pages[$do]['maxperm'])) System::Respond();

		# Létező oldal?
		if (!isset($pages[$do])) System::Respond();

		# Létező fájl?
		if (!file_exists("executive/{$pages[$do]['file']}.php")) System::Respond();

		# Szükséges oldalak betöltése
		if (!empty($pages[$do]['addons'])){
			foreach ($pages[$do]['addons'] as $addonName){
				if (empty($addons[$addonName]['php'])) continue;
				foreach ($addons[$addonName]['php'] as $php)
					require "resources/addons/$php";
			}
		}

		# POST-kérés méretének ellenörzése
		$postMaxSize = ini_get('post_max_size');
		if (!empty($_SERVER['CONTENT_LENGTH']))
			if ((int)$_SERVER['CONTENT_LENGTH'] > (int)substr($postMaxSize,0,strlen($postMaxSize)-1) * 1024 * 1024)
				System::Respond('A POST-kérés mérete túl lett lépve, így a művelet megszakadt! Ez általában túl nagy fájlok feltöltése miatt fordul elő.');

		# CSRF-elleni védelem
		if (!isset($skipCSRF)){
			if (empty($ENV['POST']['JSSESSID'])) System::Respond();
			if (!CSRF::Check($ENV['POST']['JSSESSID'])) System::Respond();

			unset($ENV['POST']['JSSESSID']);

			CSRF::Generate();
		}

		die(include "executive/{$pages[$do]['file']}.php");
	}

	// Fájlletöltés \\
	if (!isset($ENV['URL'][0])) $suburl = '';
	else $suburl = $ENV['URL'][0];
	if ($do == 'files' && $suburl == 'download')
		FileTools::DownloadFile($ENV['URL'][1]);

	// Oldal felépítése \\

	# JS token generálása
	CSRF::Generate();
	
	# Létezik a megjelenítésfájl?
	$resc = "view/{$pages[$do]['file']}.php";
	if (!file_exists($resc))
		Message::Missing($resc);
		
	# Léteznek-e az erőforrások?
	if (USRGRP !== 'guest') $js[] = 'signed_in.js';

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

	foreach ($css_list as $i => $value){
		$resc = "resources/css/$value";
		if (!file_exists($root.$resc)) Message::Missing($rootdoc.$resc);
		$css_list[$i] = $value.'?'.filemtime($root.$resc);
	}

	foreach ($js_list as $i => $value){
		$resc = "resources/js/$value";
		if (!file_exists($root.$resc)) Message::Missing($rootdoc.$resc);
		$js_list[$i] = $value.'?'.filemtime($root.$resc);
	}

	# HTTP státuszkód visszadaása
	if (isset($pages[$do]['http_code'])) Message::StatusCode($pages[$do]['http_code']);
	
	# Hozzáférési jogosultság ellenörzése
	if (System::PermCheck($pages[$do]['minperm'],$pages[$do]['maxperm'])) Message::AccessDenied();
	
	# Szükséges dokumentumok listájának előkészítése
	$doc_list = ['header','footer'];

	if (USRGRP !== 'guest') array_splice($doc_list,1,0,['sidebar']);
	array_splice($doc_list,-1,0,[$pages[$do]['file']]);

	# Szükséges oldalak betöltése
	if (!empty($pages[$do]['addons'])){
		foreach ($pages[$do]['addons'] as $addonName){
			if (empty($addons[$addonName]['php'])) continue;
			foreach ($addons[$addonName]['php'] as $php)
				require "resources/addons/$php";
		}
	}
	foreach ($doc_list as $doc)
		require "view/$doc.php";
