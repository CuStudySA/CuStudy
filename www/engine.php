<?php
	# DogM Engine - Developed by mbalint987 (member of BetonSoft)

	# Karakterkódolás beállítása
	header('Content-Type: text/html; charset=utf-8;');
	
	# Dok. gyökér meghatározása
	$root = isset($repRoot) ? $repRoot : $_SERVER['DOCUMENT_ROOT'];
	if (substr($root,-1) !== '/') $root .= '/';
	
	$rootdoc = '/';
	define('ABSPATH',(!empty($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['SERVER_NAME']);

	# Konfigurációs fájl betöltése
	require "conf.inc.php";

	# Külső erőforrás fájlok betöltése
	require $root.'resources/php/Cookie.php';
	require $root.'resources/php/MysqliDb.php';

	# Üzenettár betöltése
	require $root.'resources/php/messages.php';

	# Funkciótárolók betöltése
	require $root.'resources/php/classes/System.php';
	spl_autoload_register('System::LoadCoreClass');

	# Segédfájlok betöltése
	require $root.'resources/php/dBTitles.php';
	require $root.'resources/php/EmailNotifications.php';

	# Külső szolgáltatók API-jának betöltése
	require $root.'resources/php/ExternalAPIs.php';

	# Egy üres MysqliDb instance, hogy a PhpStorm megtalálja
	$db = System::ConnectToDatabase();

	# Karbantartási állapot ellenörzése, kapcsolódás az adatbázishoz
	System::LoadMaintenance();

	# Scipt futattásának kezdeti idejének lekérése
	$ENV['EXECTIME'] = array('start' => microtime(true));

	# CloudFlare IP cím visszafejtés
	if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])){
		require 'resources/php/CloudFlare.php';
		if (CloudFlare::CheckUserIP())
			$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_CF_CONNECTING_IP'];
	}

	# POST és/vagy GET adatok ill. tevékenység lekérése, változók definiálása
	if (!empty($_GET['do'])){
		$ENV['do'] = $_GET['do'];
		unset($_GET['do']);
	}
	
	$ENV['GET'] = $_GET;
	$ENV['POST'] = $_POST;
	$ENV['SERVER'] = $_SERVER;

	# URL szétbontása tömbbé
	if (!empty($ENV['GET']['data'])){
		if (!System::InputCheck($ENV['GET']['data'],'suburl')){
			$ENV['URL'] = explode('/',$ENV['GET']['data']);
			unset($ENV['GET']['data']);
		}
		else
			unset($ENV['GET']['data']);
	}
	unset($_GET,$_POST);

	# Jogosultsági szintek és felhasználói profil beállítása
	$user = System::CheckLogin();

	if (!is_array($user)) define('ROLE',$user);
	else {
		define('ROLE',$user[0]);
		$user = $user[1];
	}

	# Beállítások lekérése
	GlobalSettings::Load();
	UserSettings::Load(null,PUSH_TO_USERVAR);

	# Tevékenység meghatározása
	if (empty($ENV['do']))
		$do = ROLE !== 'guest' ? 'fooldal': 'login';
	else if (!isset($pages[$ENV['do']]) && $ENV['do'] != 'logout'){
		if ($ENV['do'] === 'bb-webhook' && !empty($ENV['GET']['auth']) && $ENV['GET']['auth'] === BB_AUTHCODE){
			$out = array();
			exec("git reset HEAD --hard", $out);
			exec("git pull", $out);
			echo implode("<br>", $out);
			die();
		}
		$do = 'not-found';
	}
	else {
		if ($ENV['do'] == 'login' && ROLE != 'guest') $do = 'fooldal';
		else $do = $ENV['do'];
	}

	# URL fixálása
	if (($do === "login" || $do === "fooldal") && empty($ENV['URL']))
		System::FixPath('/');

	# Ha kilépésre van szükség...
	if ($do === 'logout'){
		if (empty($ENV['URL'][0]))
			$status = !System::Logout();
		else if ($ENV['URL'][0] == 'exit')
			AdminClassTools::ExitClass();
		else
			System::Respond();

		if ($_SERVER['REQUEST_METHOD'] === 'GET') System::Redirect('/');
		else System::Respond(true);
	}

	# Események lekérésénél 'Executive' végrehajtása
	if (!empty($ENV['URL'][0]))
		if (preg_match('/^get(Global)?Events$/',$ENV['URL'][0]) && $do == 'events'){
			$ENV['SERVER']['REQUEST_METHOD'] = 'POST';
			$skipCSRF = true;
		}


	# Üzeneteket tartalmazó tömb áthelyezése
	Message::$Messages = $ENV['Messages'];

	# MantisBT integráció alapért. értékének beállítása
	$MantisDB = 1001;

	# Jogosultságok előkészítése
	System::CompilePerms();

	# Frisstési szkript futtatása (ha frissítés történt a rendszeren)
	System::RunUpdatingTasks();

	// 'Executive' rész \\
	if ($ENV['SERVER']['REQUEST_METHOD'] == 'POST'){
		# Létező oldal?
		if (!isset($pages[$do])) System::Respond('A kérés nem teljesíthető, mert nem található a kért oldal!');

		# Jogosultság ellenörzése
		if (System::PermCheck("{$do}.view")) System::Respond('A kérés nem teljesíthető, mert az oldalhoz a hozzáférés megtagadva!');

		# Létező fájl?
		if (!file_exists("executive/{$pages[$do]['file']}.php")) System::Respond();

		# Szükséges oldalak betöltése
		$pages[$do]['addons'] = array_merge(!empty($pages[$do]['addons']) ? $pages[$do]['addons'] : array(),$addon);
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
			if (empty($ENV['POST']['JSSESSID'])) System::Respond('A művelet nem teljesíthető, mert a kéréshez nem tartozik CSRF token!');
			if (!CSRF::Check($ENV['POST']['JSSESSID'])) System::Respond('A művelet nem teljesíthető, mert a kéréshez tartozó CSRF token nem egyezik a várt CSRF tokennel!');

			unset($ENV['POST']['JSSESSID']);

			CSRF::Generate();
		}

		# Ha már be van jelentkezve...
		if ($do == 'fooldal' && ROLE != 'guest' && empty($ENV['URL']))
			System::Respond('A rendszerbe egy felhasználó már be van jelentkezve ezzel a böngészővel! Kérem, frissítse az oldalt...');

		# Oldal betöltése
		die(include "executive/{$pages[$do]['file']}.php");
	}

	# Hozzáférési jogosultság ellenörzése
	if (System::PermCheck("$do.view")){
		if (ROLE == 'guest')
			Message::AccessDenied();
		else
			$do = 'access-denied';
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
	$resc = $root."view/{$pages[$do]['file']}.php";
	if (!file_exists($resc))
		Message::Missing($resc);
		
	# Erőforrások ellenörzése és előkészítése
	if (ROLE !== 'guest') $js[] = 'signed_in.js';

	$css_list = array_merge($css, !empty($pages[$do]['css']) ? $pages[$do]['css'] : array());
	$js_list = array_merge($js, !empty($pages[$do]['js']) ? $pages[$do]['js'] : array());

	if (!empty($pages[$do]['sub_js'])){
		foreach($pages[$do]['sub_js'] as $key => $value){
			if (empty($key) && empty($ENV['URL'][0])){
				$js_list[] = $value;
				break;
			}
			else if (empty($ENV['URL'])) continue;

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
		$resc = "resources/css/";
		$minvalue = preg_replace('~\.css$~', '.min.css', $value);
		if (file_exists($root.$resc.$minvalue))
			$value = $minvalue;
		else if (!file_exists($root.$resc.$value))
			Message::Missing($rootdoc.$resc.$value);
		$css_list[$i] = $value.'?'.filemtime($root.$resc.$value);
	}

	foreach ($js_list as $i => $value){
		$resc = "resources/js/";
		$minvalue = preg_replace('~\.js$~', '.min.js', $value);
		if (file_exists($root.$resc.$minvalue))
			$value = $minvalue;
		else if (!file_exists($root.$resc.$value))
			Message::Missing($rootdoc.$resc.$value);
		$js_list[$i] = $value.'?'.filemtime($root.$resc);
	}

	# HTTP státuszkód visszadaása
	if (isset($pages[$do]['http_code'])) Message::StatusCode($pages[$do]['http_code']);

	# Szükséges oldalak betöltése
	$pages[$do]['addons'] = array_merge(!empty($pages[$do]['addons']) ? $pages[$do]['addons'] : array(),$addon);
	if (!empty($pages[$do]['sub_addons'][!empty($ENV['URL'][0]) ? $ENV['URL'][0] : '']))
			$pages[$do]['addons'][] = $pages[$do]['sub_addons'][$ENV['URL'][0]];

	if (!empty($pages[$do]['addons'])){
		foreach ($pages[$do]['addons'] as $addonName){
			if (empty($addons[$addonName]['php'])) continue;
			foreach ($addons[$addonName]['php'] as $php)
				require "resources/addons/$php";
		}
	}

	if (!($ENV['SERVER']['REQUEST_METHOD'] === 'GET' && isset($ENV['GET']['via-js']))){
		# Szükséges dokumentumok listájának előkészítése
		$doc_list = ['header'];
		if (ROLE !== 'guest' && empty($pages[$do]['withoutSidebar']))
			$ENV['sidebar'] = true;
		$doc_list[] = $pages[$do]['file'];
		$doc_list[] = 'footer';

		foreach ($doc_list as $doc)
			require "view/$doc.php";
	}
	else {
		$respond = array(
			'title' => "{$pages[$do]['title']} - CuStudy",
			'css' => array(),
			'js' => array(),
		);

		foreach ($css_list as $value)
			$respond['css'][] = "{$rootdoc}resources/css/$value";

		$pages[$do]['addons'] = array_merge($pages[$do]['addons'],$addon);

		if (!empty($pages[$do]['sub_addons'][!empty($ENV['URL'][0]) ? $ENV['URL'][0] : '']))
			$pages[$do]['addons'][] = $pages[$do]['sub_addons'][$ENV['URL'][0]];

		if (!empty($pages[$do]['addons'])){
			foreach ($pages[$do]['addons'] as $addonName){
				if (!empty($addons[$addonName]['css'])){
					foreach ($addons[$addonName]['css'] as $css)
						$respond['css'][] = "{$rootdoc}resources/addons/$css";
				}
				if (!empty($addons[$addonName]['js'])){
					foreach ($addons[$addonName]['js'] as $js)
						$respond['js'][] = "{$rootdoc}resources/addons/$js";
				}
			}
		}

		foreach ($js_list as $value)
			$respond['js'][] = "{$rootdoc}resources/js/$value";

		ob_start();
		require "view/{$pages[$do]['file']}.php";
		$respond['main'] = ob_get_clean();

		if (ROLE !== 'guest'){
			ob_start();
			require "view/sidebar.php";
			$respond['sidebar'] = ob_get_clean();
		}

		System::Respond($respond);
	}
