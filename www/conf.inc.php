<?php
	# Konstansok, paraméterek betöltése
	require_once 'constants.inc.php';

	# Szoftveradatok definiálása
	$ENV['SOFTWARE'] = array(
		'NAME' => 'CuStudy',
		'CODENAME' => 'Amber',
		'VER' => '2.0b',
		'DEVELOPER' => 'CuStudy Software Alliance',
		'DEV_STARTED' => '2014',
		'COMMIT' => LATEST_COMMIT_ID,
	);

	$ENV['ENGINE'] = array(
		'NAME' => 'DogM Engine',
		'CODENAME' => 'Cornsilk',
		'VER' => '1.0',
		'DEVELOPER' => 'Bálint Mészáros (member of PageLoop)',
		'DEV_STARTED' => '2014',
		'COMMIT' => 'unknown',
	);

	# Tetszőleges üzenet kiíratása a HTML forrásba
	$ENV['EE_MESSAGE'] = '';

	# Beépülő modulok definiálása
	$addons = array(
		'sceditor' => array(
			'css' => ['sceditor/themes/default.min.css'],
			'js' => ['sceditor/jquery.sceditor.bbcode.min.js','sceditor/hu.js'],
		),
		'jbbcode' => array(
			'php' => ['jbbcode/Parser.php','jbbcode/_AmberCodeDefSet.php'],
		),
		'swiftMailer' => array(
			'php' => ['swiftMailer/swift_required.php'],
		),
		'fullCalendar' => array(
			'css' => ['fullCalendar/fullcalendar.css'],
			'js' => ['fullCalendar/lib/moment.min.js','fullCalendar/fullcalendar.js','fullCalendar/lang/hu.js'],
		),
		'dateRangePicker' => array(
			'css' => ['dateRangePicker/daterangepicker.css'],
			'js' => ['fullCalendar/lib/moment.min.js','dateRangePicker/jquery.daterangepicker.js'],
		),
		'mantisIntegration' => array(
			'php' => ['mantisIntegration/dataBaseConnect.php'],
		),
		'powertip' => array(
			'css' => ['powertip/jquery.powertip.css'],
			'js' => ['powertip/jquery.powertip.js'],
		),
	);

	# Menüpontok beállítása, JS, CSS és addonok tömbjeinek kezdeti értékadása
	$css = ['theme.css'];
	$js = ['jquery.functions.js','dialog.js'];
	$addon = [];

	$pages = array(
		'landing' => array(
			'css' =>        ['landing.css'],
			'js' =>         ['landing.js'],
			'file' => 		'landing',
		),

		'fooldal' => array(
			'title' => 		'Kezdőoldal',
			'css' => 		['fooldal.css'],
			'js' => 		['fooldal.js'],
			'file' => 		'fooldal',
		),

		'login' => array(
			'title' => 		'Bejelentkezés',
			'css' => 		['login.css'],
			'js' => 		['login.js'],
			'file' => 		'login',
		),

		'not-found' => array(
			'title' => 		'404',
			'css' => 		['404.css'],
			'file' => 		'404',
			'http_code' =>   404,
		),

		'access-denied' => array(
			'title' => 		'403',
			'css' => 		['404.css'],
			'file' => 		'403',
			'http_code' =>   403,
		),

		'users' => array(
			'title' => 		'Felhasználók',
			'css' => 		['users.css'],
			'js' => 		['users.js'],
			'file' => 		'users',
		),

		'logs' => array(
			'title' => 		'Rendszernapló',
			'css' => 		['logs.css'],
			'js' => 		['dyntime.js','logs.js'],
			'file' => 		'logs',
		),

		'lessons' => array(
			'title' => 		'Tantárgyak',
			'css' => 		['lessons.css'],
			'js' => 		['lessons.js','spectrum.js','colorpicker.js'],
			'file' => 		'lessons',
		),

		'timetables' => array(
			'title' => 		'Órarend',
			'css' => 		['timet.css','timetables.css'],
			'js' => 		[],
			'sub_js' =>     ['' => 'timetables/tt-view.js', 'edit' => 'timetables/tt-edit.js','week' => 'timetables/tt-edit.js'],
			'file' => 		'timetables',
			'addons' =>     ['powertip'],
		),

		'teachers' => array(
			'title' => 		'Tanárok',
			'css' => 		['teachers.css'],
			'js' => 		['teachers.js','spectrum.js'],
			'file' => 		'teachers',
		),

		'groups' => array(
			'title' => 		'Csoportok',
			'css' => 		['groups.css'],
			'sub_js' =>     ['' => 'groups.js', 'edit' => 'groups.edit.js','add' => 'groups.add.js',],
			'file' => 		'groups',
		),

		'pw-reset' => array(
			'title' => 		'Jelszóvisszaállítás',
			'css' => 		['login.css'],
			'js' => 		['pw-reset.js'],
			'file' => 		'pw-reset',
		),

		'profile' => array(
			'title' => 		'Profilom szerkesztése',
			'css' => 		['profile.css'],
			'js' => 		[],
			'sub_js' =>     ['' => 'profile.js', 'settings' => 'profile.settings.js'],
			'file' => 		'profile',
		),

		'homeworks' => array(
			'title' => 		'Házi feladatok',
			'css' => 		['homeworks.css','timet.css'],
			'js' => 		['homeworks.js'],
			'file' => 		'homeworks',
			'sub_addons' => ['new' => 'sceditor'],
		),

		'invitation' => array(
			'title' => 		'Meghívás',
			'css' => 		['login.css'],
			'js' => 		['invitation.js'],
			'file' => 		'invitation',
		),

		'files' => array(
			'title' => 		'Dokumentumok',
			'css' => 		['files.css'],
			'js' => 		['files.js'],
			'file' => 		'files',
		),

		'events' => array(
			'title' => 		'Események',
			'css' => 		['events.css'],
			'js' => 		['events.js'],
			'file' => 		'events',
			'addons' =>     ['fullCalendar','dateRangePicker'],
		),

		'system.users' => array(
			'title' => 		'Rendszerfelhasználók',
			'css' => 		['system.users.css'],
			'js' => 		['system.users.js'],
			'file' => 		'system.users',
		),

		'system.classes' => array(
			'title' => 		'Osztályok',
			'css' => 		['system.classes.css'],
			'js' => 		['system.classes.js'],
			'file' => 		'system.classes',
		),

		'system.popup' => array(
			'title' => 		'Felhasználók szűrése',
			'css' => 		['system.users.css'],
			'js' => 		['system.users.js','system.popup.js'],
			'file' => 		'system.popup',
			'withoutSidebar' => true,
		),

		'system.events' => array(
			'title' => 		'Rendszeresemények',
			'css' => 		['system.events.css'],
			'js' => 		['system.events.js'],
			'file' => 		'system.events',
			'addons' =>     ['dateRangePicker'],
		),

		'mail.cron' => array(
			'title' => 		'Sending mails using CronTab',
			'css' => 		[],
			'js' => 		[],
			'file' => 		'mail.cron',
		),

		'amber' => array(
			'title' => 		'Amber',
			'css' => 		['amber.css'],
			'js' => 		[],
			'file' => 		'amber',
		),
	);

	# Jogosultsági szintek definiálása
	$Perm = array(
		'students' => array(
			'visitor' => array(
				'timetables' => ['view'],
				'homeworks' => ['view'],
				'events' => ['view'],
				'files' => ['view'],
				'teachers' => ['view'],
				'lessons' => ['view'],
				'amber' => ['view'],
			),
			'editor' => array(
				'homeworks' => ['add'],
				'events' => ['add'],
				'files' => ['add'],
			),
			'admin' => array(
				'timetables' => ['edit'],
				'homeworks' => ['delete'],
				'events' => ['edit','delete'],
				'files' => ['edit','delete'],
				'teachers' => ['add','edit','delete'],
				'lessons' => ['add','edit','delete'],
				'groups' => ['view', 'add', 'edit', 'delete', 'list'],
				'groupThemes' => ['add', 'edit', 'delete'],
				'users' => ['view', 'edit', 'invite', 'eject'],
				'logs' => ['view','getClassLog'],
			),
		),
		'guest' => array(
			'pw-reset' => ['view'],
			'login' => ['view'],
			'landing' => ['view'],
			'not-found' => ['view'],
			'invitation' => ['view'],
			'mail.cron' => ['view'],
		),
		'systemadmin' => array(
			'system.users' => ['view'],
			'system.classes' => ['view'],
			'system.popup' => ['view'],
			'system.events' => ['view'],
			'logs' => ['view','getAllUserLog'],
		),
		'everybody' => array(
			'not-found' => ['view'],
			'fooldal' => ['view'],
			'profile' => ['view'],
			'invitation' => ['view'],
			'access-denied' => ['view'],
		),
	);

	# 'PermCheck' funkció működéséhez szükséges tömb
	$permKeyDB = array(
		'timetables' => 'timetable',
		'groupThemes' => 'group_themes',
	);

	# Viewing URL
	define('OFFICE_VIEWING_URL','https://view.officeapps.live.com/op/view.aspx');
