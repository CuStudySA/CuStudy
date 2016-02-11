<?php

	class InviteTools {
		static $inviteBody = <<<STRING
			<h2>CuStudy - Meghívó a CuStudy rendszerbe</h2>

			<h3>Tisztelt ++NAME++!</h3>

			<p>Örömmel értesítünk, hogy meghívót kaptál a CuStudy rendszerbe a(z) <b>++SCHOOL++</b> iskola <b>++CLASS++</b> osztálya által. A meghívót <b>++SENDER++</b> csoportadminisztrátor küldte neked.</p>

			<p><b>Mi is az a CuStudy?</b> A CuStudy a BetonHomeWork utódjaként továbbra is egy kellemetlen, de kötelező feladatra koncentrál: a házi feladatokra. A CuStudy - a BHW méltó utódjaként - teszi lehetővé számodra, hogy értesülhess a házi feladataidról és egyéb kötelességeidről, sőt a program használatával akutális és frissülő órarended is láthatod. Az elődünkhöz képest azonban jócskán fejlődtünk: mostantól <b>webes felületen</b> érheted el az információkat, illetve új felületet és számos izgalmas, funkcionalitást érintő fejlesztést is eszközöltünk, így az alapötlet egy remek felülettel és sok funkcióval párosul.</p>

			<p>A meghívás elfogadásához <a href="https://custudy.tk/invitation/++ID++">kattints ide</a>! A linkre kattintva meg kell adnod néhány adatot magadról, be kell állítanod a jelszavad, és az űrlap elküldése után automatikusan átirányítunk a program főoldalára.</p><p>Ha a fenti gomb valamilyen okból kifolyólag nem működne, másold be az alábbi URL-t a böngésződ címsoráva:<br><a href="https://custudy.tk/invitation/++ID++">https://custudy.tk/invitation/++ID++</a></p><p>Bízunk benne, hogy a CuStudy a Te tetszésedet is elnyeri majd!</p>

			<p>Üdvözlettel,<br>
			CuStudy Software Alliance</p>
STRING;

		static $addBody = <<<STRING
			<h2>CuStudy - Új szerepkör hozzáadva</h2>

			<h3>Tisztelt felhasználónk!</h3>

			<p>A(z) <b><++SCHOOL++/b> iskola ++CLASS++ osztályának adminisztrátora felvett a CuStudy-ban létrehozott osztályába. Ez azt jelenti, hogy hozzáférhetsz az osztály órarendjéhez, házi feladatihoz és eseményeihez. Az osztályba történő felvételkor a jogosultsági szinted itt Tanuló, ha ezt az adminisztrátor módosítja a későbbiekben, arról értesítést küldünk neked!</p>

			<p>Az alapértelmezett szerepköröd ezzel a művelettel nem módosult, ám amennyiben a most felvett szerepkört szeretnéd alapértelmezettként beállítani, arra lehetőséged van a 'Profilom' oldalon!</p>

			<p>Amennyiben ez a szerepkör felvétele váratlanul ért, vagy úgy gondolod, hogy tévedés történt, a szerepkört bármikor leválaszthatod a 'Profilom' oldalon!</p>

			<p>Üdvözlettel,<br>
			CuStudy Software Alliance</p>
STRING;

	static $groupChooser = array(<<<STRING
		<p>Most kérünk, hogy válaszd ki az osztálybeli csoportjaidat! Ez nem kötelező lépés...</p>
		<form id='groupDataForm'>
STRING
,
<<<STRING
		<p><button class='btn'>Csoportadatok mentése és tovább a CuStudy-ra</button></p>
		</form>
STRING
);

		static function Invite($email,$name){
			global $db, $user, $ENV;

			# Jog. ellenörzése
			if (System::PermCheck('users.invite')) return 1;

			if (System::InputCheck($email,'email')) return 2;

			$data = $db->where('email',$email)->getOne('users');
			if (!empty($data)){
				$action = $db->insert('class_members',array(
					'classid' => $user['class'][0],
					'userid' => $data['id'],
				));

				Message::SendNotify('role.enrollment',$email,$data['name'],array(
					'initiator' => $user['name'],
					'role' => UserTools::$roleLabels['visitor']." a(z) {$ENV['school']['name']} iskola {$ENV['class']['classid']} osztályában",
				));

				return $data;
			}

			$invId = Password::Generalas(12);
			$action = $db->insert('invitations',array(
				'invitation' => $invId,
				'name' => $name,
				'email' => $email,
				'classid' => $user['class'][0],
				'inviter' => $user['id'],
			));

			if (!$action) return 3;

			$action = Message::SendNotify('invitation',$email,$name,array(
				'SCHOOL' => $ENV['school']['name'],
				'CLASS' => $ENV['class']['classid'],
				'ID' => $invId,
				'SENDER' => $user['name'],
				'ABSPATH' => ABSPATH,
			));

			if ($action) return 4;

			return 0;
		}

		static function BatchInvite($emails){
			# Jog. ellenörzése
			if (System::PermCheck('users.invite')) return 1;

			$invalidEntrys = $enrolledUsers = [];
			foreach ($emails as $array){
				$action = self::Invite($array['email'],$array['name']);

				if (is_int($action)){
					if ($action != 0)
						$invalidEntrys[] = array_merge(array('error' => $action),$array);
				}
				else
					$enrolledUsers[] = array(
						'id' => $action['id'],
						'name' => $action['name'],
					);
			}

			return array(
				'invalidEntrys' => $invalidEntrys,
				'enrolledUsers' => $enrolledUsers,
			);
		}

		static function Registration($data){
/*          array(
				'token' (string)
				'username' (string)
				'password' (string)
				'name' (string)
			) */

			global $db;

			$token = $data['token'];
			# Formátum ellenörzése
			foreach ($data as $key => $value){
				switch ($key){
					case 'username':
					case 'password':
						$type = $key;
					break;

					case 'name':
						$type = 'name';
					break;

					default:
						unset($data[$key]);
						continue 2;
					break;
				}
				if (System::InputCheck($value,$type)) return 3;
			}

			$token_d = $db->where('invitation',$token)->getOne('invitations');
			if (empty($token_d)) return 1;
			if (!$token_d['active']) return 2;

			$action = $db->where('email',$token)->getOne('users');
			if (!empty($action)) return 4;

			$db->where('invitation',$token)->update('invitations',array(
				'active' => 0,
			));

			$session = Password::GetSession($data['username']);
			$envInfos = System::GetBrowserEnvInfo();
			if (!is_array($envInfos)) return 5;

			$id = $db->insert('users',array(
				'username' => $data['username'],
				'password' => Password::Kodolas($data['password']),
				'name' => $data['name'],
				'email' => $token_d['email'],
				'role' => 'none',
				'active' => 1,
			));

			# Hozzáadás a csoporthoz
			$defSession = $db->insert('class_members',array(
				'classid' => $token_d['classid'],
				'userid' => $id,
				'role' => 'visitor',
			));

			$db->where('id',$id)->update('users',array(
				'defaultSession' => $defSession,
			));

			$db->insert('sessions',array(
				'session' => md5($session),
				'userid' => $id,
				'ip' => $envInfos['ip'],
				'useragent' => $envInfos['useragent'],
				'activeSession' => $defSession
			));

			Cookie::set('PHPSESSID',$session,false);

			$print = self::$groupChooser[0];

			$group_data = $db->rawQuery('SELECT g.id, g.name, gt.id as `theme_id`, gt.name as `theme_name`
											FROM `group_themes` gt
											LEFT JOIN `groups`g
											ON (g.theme = gt.id)
											WHERE g.classid = ?',array($token_d['classid']));

			if (empty($group_data)) return 10;

			$groups = array();
			$gt_names = array();
			if (!empty($group_data)){
				$lastGTId = $group_data[0]['theme_id'];
				$lastGTName = $group_data[0]['theme_name'];
				foreach ($group_data as $entry){
					if ($entry['theme_id'] != $lastGTId) $lastGTId = $entry['theme_id'];
					if ($entry['theme_name'] != $lastGTName) $lastGTName = $entry['theme_name'];

					if (!in_array($lastGTId,array_keys($gt_names))) $gt_names[$lastGTId] = $lastGTName;

					$groups[$lastGTId][] = $entry;
				}
			}

			foreach ($gt_names as $key => $value){
				$print .= "<p>{$value} csoport: <select name='{$key}'><option value='0' selected>(nincs)</option>";
				foreach ($groups[$key] as $entry)
					$print .= "<option value='{$entry['id']}'>{$entry['name']}</option>";

				$print .= "</select></p>";
			}

			return [$print.self::$groupChooser[1]];
		}

		static function SetGroupMembers($data){
			global $db,$user;

			$gt = $g = array();
			$group_themes = $db->rawQuery('SELECT `id`
											FROM `group_themes`
											WHERE `classid` = ?',array($user['class'][0]));
			$groups = $db->rawQuery('SELECT `id`,`theme`
								FROM `groups`
								WHERE `classid` = ?',array($user['class'][0]));
			if (empty($group_themes)) return 1;


			foreach ($group_themes as $themea)
				$gt[] = $themea['id'];

			foreach ($groups as $entry)
				$g[$entry['id']] = $entry['theme'];

			foreach ($data as $key => $value){
				if ($value == 0) continue;

				if (array_search($key,$gt) === false) return 2;
				if (!isset($g[$value])) return 3;
				if ($g[$value] != $key) return 4;

				$db->insert('group_members',array(
					'classid' => $user['class'][0],
					'groupid' => $value,
					'userid' => $user['id'],
				));
			}

			return 0;
		}
	}

