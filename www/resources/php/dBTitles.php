<?php
	$classid = function($x){
		global $db;

		$data = $db->rawQuery('SELECT c.classid as class, s.name as school
								FROM class c
								LEFT JOIN school s
								ON (c.school = s.id)
								WHERE c.id = ?',array($x));
		if (empty($data)) return "Ismeretlen (azonosítója: #{$x})";
		else return "{$data[0]['school']} - {$data[0]['class']} (#{$x})";
	};

	$userid = function($x){
		global $db;

		if ($x == 0) return 'Anonymus felhasználó';

		$data = $db->where('id',$x)->getOne('users');
		if (empty($data)) return "Ismeretlen felhasználó (#{$x})";

		return "{$data['name']} (#{$x})";
	};

	$dBTitles = array(
		'global' => array(
			'ipaddr' => 'IP-cím',
			'time' => 'Dátum és idő',
			'useragent' => array('Eszköz és böngésző',function($x){
				$agent = UserAgentTools::Parse($x);

				if (!empty($agent['version'])){
					$ver = substr($agent['version'],0,4);

					if (substr($ver,-1) == '.')
						$ver = substr($ver,0,-1);

					if (strlen($agent['version']) != strlen($ver))
						$ver .= '...';

					return "{$agent['browser']} böngésző {$ver} verziója ({$agent['platform']} alatt)";
				}

				return "{$agent['browser']} böngésző {$agent['platform']} alatt";
			}),
			'user' => array('Kezdeményező felhasználó',function($x){
				global $db;

				if ($x == 0) return 'Anonymus felhasználó';

				$data = $db->where('id',$x)->getOne('users');
				if (empty($data)) return "Ismeretlen felhasználó (#{$x})";

				return "{$data['name']} (#{$x})";
			}),
			'errorcode' => array('Művelet végeredménye',function($x,$entry){
				global $db, $ENV;

				if ($x == 0)
					return 'Sikeresen végrehajtva';

				$action = explode('.',$entry['action']);
				if (count($action) == 1) return "Sikertelen, hiba ismeretlen (hibakód: {$x})";

				if (!empty($ENV['Messages'][$action[0]][$action[1]]['errors'][$x]))
						return 'Sikertelen, "'.$ENV['Messages'][$action[0]][$action[1]]['errors'][$x].'" (hibakód: '.$x.')';
				else
					return "Sikertelen, hiba ismeretlen (hibakód: {$x})";
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
			'invitation_id' => 'Meghívás azonosítója',
		),
		'roles' => array(
			'e_id' => 'Szerekör azonosítója',
			'role' => array('Szerepkör',function($x){
				return UserTools::$roleLabels[$x];
			}),
			'userid' => array('Felhasználó',$userid),
		),
		'lessons' => array(
			'classid' => array('Osztály',$classid),
			'color' => array('Szín',function($x){
				return "<span class='color' style='background-color: {$x}'></span> ({$x})";
			}),
			'teacherid' => array('Tanár',function($x){
				global $db;

				$data = $db->where('id',$x)->getOne('teachers');

				if (empty($data)) return "Ismeretlen (adatbázisban: #{$x})";
				else return "{$data['name']} (#{$x})";
			}),
			'name' => 'Név',
		),
		'teachers' => array(
			'name' => 'Név',
			'short' => 'Rövid név',
			'classid' => array('Osztály',$classid),
		),
		'mantis_users' => array(
			'e_id' => array('Mantis felhasználó',function($x){
				global $db, $MantisDB;

				if (is_int($MantisDB))
					return "Ismeretlen (Mantis integr. kikapcs.)";

				$User = $MantisDB->where('id',$x)->getOne('mantis_user_table');

				if (empty($User))
					return "Ismeretlen (adatbázisban: #{$x})";

				return "{$User['realname']} (#{$User['id']})";
			}),
			'username' => 'Felhasználónév',
			'name' => 'Név',
			'email' => 'E-mail cím',
			'userid' => array('C.S. felhasználó',$userid),
		),
		'homeworks' => array(
			'e_id' => array('Bejegyzés azonosítója',function($x){ return '#'.$x; }),
			'author' => array('Szerző',$userid),
			'classid' => array('Osztály',$classid),
			'year' => 'Év',
			'week' => array('Hét',function($x){ return "{$x}. hét"; }),
			'text' => 'Szöveg',
			'lesson' => array('Órarend bejegyzés',function($x){
				global $db;

				$data = $db->rawQuery('SELECT l.name, tt.week, tt.day, tt.lesson
										FROM lessons l
										LEFT JOIN timetable tt
										ON (tt.lessonid = l.id)
										WHERE tt.id = ?',array($x));

				if (empty($data)) return "Ismeretlen (#{$x})";

				$data = $data[0];
				return strtoupper($data['week'])." héten, ".System::$Days[$data['day']]."i napon, {$data['lesson']}. órában ({$data['name']})";
			}),
		),
	);