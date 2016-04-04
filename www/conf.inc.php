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
			'php' => ['jbbcode/Parser.php','jbbcode/_BlueSkyCodeDefSet.php'],
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
	);

	# Menüpontok beállítása, JS, CSS és addonok tömbjeinek kezdeti értékadása
	$css = ['theme.css'];
	$js = ['jquery.functions.js','dialog.js'];
	$addon = ['swiftMailer'];

	$pages = array(
		'fooldal' => array(
			'title' => 		'Kezdőoldal',
			'css' => 		['fooldal.css'],
			'js' => 		['fooldal.js'],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'fooldal',
		),

		'login' => array(
			'title' => 		'Bejelentkezés',
			'css' => 		['login.css','amber.css'],
			'js' => 		['login/login.js'],
			'minperm' => 	'guest',
			'maxperm' => 	'guest',
			'reqdoc' => 	[],
			'file' => 		'login',
			'theme-color' =>'#71151F', //Amber flag
		),

		'not-found' => array(
			'title' => 		'404',
			'css' => 		['404.css'],
			'js' => 		[],
			'minperm' => 	'guest',
			'maxperm' => 	'',
			'reqdoc' => 	[],
			'file' => 		'404',
			'http_code' =>   404,
		),

		'users' => array(
			'title' => 		'Felhasználók',
			'css' => 		['users.css'],
			'js' => 		['users.js'],
			'customjs' =>   [],
			'minperm' => 	'admin',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'users',
			'addons' =>     [],
		),

		/*'logs' => array(
			'title' => 		'Naplók megtekintése',
			'css' => 		['logs.css'],
			'js' => 		['logs/logs.js','dyntime.js'],
			'customjs' =>   [],
			'minperm' => 	'admin',
			'maxperm' => 	'sysadmin',
			'reqdoc' => 	[],
			'file' => 		'logs',
		),*/

		'lessons' => array(
			'title' => 		'Tantárgyak',
			'css' => 		['lessons.css'],
			'js' => 		['lessons.js','spectrum.js','colorpicker.js'],
			'customjs' =>   [],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'lessons',
		),

		'timetables' => array(
			'title' => 		'Órarend',
			'css' => 		['timet.css','timetables.css'],
			'js' => 		['jquery.powertip.min.js'],
			'customjs' =>   ['' => 'timetables/ttedit.js', 'edit' => 'timetables/ttedit2.js','week' => 'timetables/ttedit2.js'],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'timetables',
		),

		'teachers' => array(
			'title' => 		'Tanárok',
			'css' => 		['teachers.css'],
			'js' => 		['teachers.js','spectrum.js'],
			'customjs' =>   [],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'teachers',
		),

		'groups' => array(
			'title' => 		'Csoportok',
			'css' => 		['groups.css'],
			'js' => 		[],
			'customjs' =>   ['' => 'groups.js', 'edit' => 'groups/edit.js','add' => 'groups/add.js',],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'groups',
		),

		'pw-reset' => array(
			'title' => 		'Jelszóvisszaállítás',
			'css' => 		['login.css'],
			'js' => 		['pw-reset.js'],
			'customjs' =>   [],
			'minperm' => 	'guest',
			'maxperm' => 	'guest',
			'reqdoc' => 	[],
			'file' => 		'pw-reset',
			'addons' =>     [],
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

		'homeworks' => array(
			'title' => 		'Házi feladatok',
			'css' => 		['homeworks.css','timet.css'],
			'js' => 		['homeworks.js'],
			'customjs' =>   [],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'homeworks',
			'addons' =>     ['sceditor','jbbcode'],
		),

		'invitation' => array(
			'title' => 		'Meghívás',
			'css' => 		['login.css'],
			'js' => 		['invitation.js'],
			'customjs' =>   [],
			'minperm' => 	'guest',
			'maxperm' => 	'user',
			'reqdoc' => 	[],
			'file' => 		'invitation',
			'addons' =>     [],
		),

		'files' => array(
			'title' => 		'Dokumentumok',
			'css' => 		['files.css'],
			'js' => 		['files.js'],
			'customjs' =>   [],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'files',
			'addons' =>     [],
		),

		'events' => array(
			'title' => 		'Események',
			'css' => 		['events.css'],
			'js' => 		['events.js'],
			'customjs' =>   [],
			'minperm' => 	'user',
			'maxperm' => 	'admin',
			'reqdoc' => 	[],
			'file' => 		'events',
			'addons' =>     ['fullCalendar','dateRangePicker'],
		),

		'system.users' => array(
			'title' => 		'Rendszerfelhasználók',
			'css' => 		['system.users.css'],
			'js' => 		['system.users.js'],
			'customjs' =>   [],
			'reqdoc' => 	[],
			'file' => 		'system.users',
			'addons' =>     [],
		),

		'system.classes' => array(
			'title' => 		'Osztályok',
			'css' => 		['system.classes.css'],
			'js' => 		['system.classes.js'],
			'customjs' =>   [],
			'reqdoc' => 	[],
			'file' => 		'system.classes',
			'addons' =>     [],
		),

		'system.popup' => array(
			'title' => 		'Felhasználók szűrése',
			'css' => 		['system.users.css'],
			'js' => 		['system.users.js','system.popup.js'],
			'customjs' =>   [],
			'reqdoc' => 	[],
			'file' => 		'system.popup',
			'addons' =>     [],
			'withoutSidebar' => true,
		),

		'system.events' => array(
			'title' => 		'Rendszeresemények',
			'css' => 		['system.events.css'],
			'js' => 		['system.events.js'],
			'customjs' =>   [],
			'reqdoc' => 	[],
			'file' => 		'system.events',
			'addons' =>     ['dateRangePicker'],
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
			),
		),
		'guest' => array(
			'pw-reset' => ['view'],
			'login' => ['view'],
			'not-found' => ['view'],
			'invitation' => ['view'],
		),
		'systemadmin' => array(
			'system.users' => ['view'],
			'system.classes' => ['view'],
			'system.popup' => ['view'],
			'system.events' => ['view'],
		),
		'everybody' => array(
			'not-found' => ['view'],
			'fooldal' => ['view'],
			'profile' => ['view'],
			'invitation' => ['view'],
		),
	);

	# 'PermCheck' funkció működéséhez szükséges tömb
	$permKeyDB = array(
		'timetables' => 'timetable',
		'groupThemes' => 'group_themes',
	);
