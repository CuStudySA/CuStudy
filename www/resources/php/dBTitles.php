<?php
	$dBTitles = array(
		'global' => array(
			'ipaddr' => 'IP-cím',
			'time' => 'Dátum és idő',
			'useragent' => array('Eszköz és böngésző',function($x){
				$agent = UserAgentTools::Parse($x);

				if (!empty($agent['version']))
					return "{$agent['browser']} böngésző {$agent['version']} verziója ({$agent['platform']} alatt)";

				return "{$agent['browser']} böngésző {$agent['platform']} alatt";
			}),
			'user' => array('Kezdeményező felhasználó',function($x){
				global $db;

				if ($x == 0) return 'Anonymus felhasználó';

				$data = $db->where('id',$x)->getOne('users');
				if (empty($data)) return "Ismeretlen felhasználó (#{$x})";

				return "{$data['name']} (#{$x})";
			}),
			'errorcode' => array('Művelet végeredménye',function($x){
				global $db, $entry;

				if ($x == 0)
					return 'Sikeresen végrehajtva';
				else {
					if (empty($entry) || empty($entry['error_desc']))
						return "Sikertelen, hiba ismeretlen (hibakód: {$x})";
					else
						return "Sikertelen, mert {$entry['error_desc']} (hibakód: {$x})";
				}
			}),
			'action' => array('Művelet',function($x){
				$action = explode('.',$x);

				if (count($action) == 1) $Action = "Ismeretlen (adatbázisban: {$x})";
				else {
					if (!empty(Logging::$ActionLabels[$action[0]][$action[1]]))
						$Action = Logging::$ActionLabels[$action[0]][$action[1]];
					else
						$Action = "Ismeretlen (adatbázisban: {$x})";
				}

				return $Action;
			}),
		),

		'login' => array(
			'username' => 'Használt felhasználónév',
		),
		'users' => array(
			'username' => 'Felhasználónév',
			'name' => 'Teljes név',
			'role' => array('Glob. szerepkör',function($x){
				return UserTools::$roleLabels[$x];
			}),
			'active' => array('Állapota',function($x){
				return $x == 1 ? 'Aktív' : 'Inaktív';
			}),
			'email' => 'E-mail cím',
		),
		'roles' => array(
			'e_id' => 'Szerekör azonosítója',
			'role' => array('Szerepkör',function($x){
				return UserTools::$roleLabels[$x];
			}),
			'userid' => array('Felhasználó',function($x){
				global $db;

				if ($x == 0) return 'Anonymus felhasználó';

				$data = $db->where('id',$x)->getOne('users');
				if (empty($data)) return "Ismeretlen felhasználó (#{$x})";

				return "{$data['name']} (#{$x})";
			}),
		),
	);