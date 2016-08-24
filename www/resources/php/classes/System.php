<?php
	class System {
		static $Patterns = array(
			'username' => '^[a-zA-Z\d]{3,15}$',
			'name' => '^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$',
			'password' => '^[\w\d]{6,20}$',
			'email' => '^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9])?@[a-z0-9]+\.[a-z]{2,4}$',
			'shortn_teacher' => '^[A-ZÖÜÓÚŐÉÁŰa-zéáűőúöüó.]{2,}$',
		);

		static $ResolveNames = array(
			'name' => 'name',
			'verpasswd' => 'password',
			'newpassword' => 'password',
			'vernewpasswd' => 'password',
			'short' => 'shortn_teacher',
		);

		static $Inputs = array(
			'users' => ['username','name','email','password','verpasswd','newpassword','vernewpasswd'],
			'teachers' => ['name','short'],
			'invitation' => ['username','name','email','password','verpasswd'],
		);

		static $Days = array(null,
			'Hétfő',
			'Kedd',
			'Szerda',
			'Csütörtök',
			'Péntek',
			'Szombat',
			'Vasárnap',
		);

		static $ShortMonths = array(null,
			'Jan',  'Febr', 'Márc',
			'Ápr',  'Máj',  'Jún',
			'Júl',  'Aug',  'Szep',
			'Okt',  'Nov',  'Dec'
		);

		static /** @noinspection HtmlDeprecatedTag */
			$AllowedHTMLTags = '<b><i><u><span><br><br/>';

		// Bevitel helyességének ellenörzése
		static function InputCheck($text,$type){
			switch ($type){
				case 'username':
					$preg = '/^[a-zA-Z\d]{3,15}$/';
				break;
				case 'password':
					$preg = '/^[\w\d]{6,20}$/';
				break;
				case 'email':
					$preg = '/^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9]+)?@[a-z0-9]+\.[a-z]{2,4}$/';
				break;
				case 'name':
					$preg = '/^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű.]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$/u';
				break;
				case 'class':
					$preg = '/^\d{1,2}\.?[A-Za-z]*$/';
				break;
				case 'numeric':
					$preg = '/^\d+$/';
				break;
				case 'text':
					$preg = '/^[ -~ÁÉÍÓÖŐÚÜŰáéíóöőúüű]{2,}$/';
				break;
				case 'suburl':
					$preg = '/^[a-zA-Z0-9\/]{1,}$/';
				break;
				case 'shortn_teacher':
					$preg = '/^[A-ZÖÜÓÚŐÉÁŰa-zéáűőúöüó.]{2,}$/';
				break;
				case 'lesson':
					$preg = '/^[A-Za-zöüóőúéáűÖÜÓŐÚÉÁŰ.() ]{4,20}$/';
				break;
				case 'attack':
					$preg = '/^[$<%>=\/\\:{}\[\]*+]+$/';
				break;
			}
			return !preg_match($preg,$text);
		}

		static function GetHtmlPatterns($page = false){
			global $do;

			if ($page === false) $page = $do;

			if (!isset(self::$Inputs[$do])) return false;

			$final = array();

			foreach (self::$Inputs[$do] as $item){
				if (isset(self::$ResolveNames[$item]))
					$final[$item] = self::$Patterns[self::$ResolveNames[$item]];
				else
					$final[$item] = self::$Patterns[$item];
			}

			return $final;
		}

		// HTML választómenü értékek ell.
		static function OptionCheck($text = '',$values = []){
			return !in_array($text, $values) ? true : false;
		}

		static function LoadCoreClass($className){
			global $root;

			if (strpos(strtolower($className),'swift') !== false)
				return;

			if (class_exists($className))
				return;

			$path = $root."resources/php/classes/{$className}.php";

			if (!file_exists($path))
				throw new Exception("Nem találom a {$className} osztályt!");

			require $path;
		}

		static function LoadLibrary($libraryName){
			global $ENV, $addons, $root;

			if (in_array($libraryName,$ENV['loaded_addons']))
				return;

			if (empty($addons[$libraryName]['php']))
				throw new Exception("Probléma a(z) {$libraryName} addon betöltése közben: nem találom az addont!");

			foreach ($addons[$libraryName]['php'] as $file){
				if (!file_exists($root."resources/addons/$file"))
					throw new Exception("Probléma a(z) {$libraryName} addon betöltése közben: nem találom a(z) {$file} fájlt!");

				require $root."resources/addons/$file";
			}

			$ENV['loaded_addons'][] = $libraryName;
		}

		static function UserIsStudent($role = null){
			if (empty($ROLE))
				return (ROLE == 'visitor' || ROLE == 'editor' || ROLE == 'admin');
			else
				return ($role == 'visitor' || $role == 'editor' || $role == 'admin');
		}

		// Aktív-e a felh. az öröklődő csop. alapján?
		static function UserActParent($classid){
			global $db,$ENV;

			# Osztály ellenörzése
			$ENV['class'] = $db->where('id',$classid)->getOne('class');
			if (!$ENV['class']['active']) return true;

			# Iskola ellenörzése
			$ENV['school'] = $db->where('id',$ENV['class']['school'])->getOne('school');
			if (!$ENV['school']['active']) return true;

			return false;
		}

		static function GetBrowserEnvInfo(){
			global $ENV;

			$returnKeys = array(
				'ip' => 'REMOTE_ADDR',
				'useragent' => 'HTTP_USER_AGENT',
			);

			$return = array();

			foreach ($returnKeys as $key => $value){
				if (empty($ENV['SERVER'][$value])) return 1;

				$return[$key] = $ENV['SERVER'][$value];
			}

			return $return;
		}

		//Cookie ellenőrzés & '$user' generálása
		static function CheckLogin() {
			global $db, $ENV;

			if (!Cookie::exists('PHPSESSID')) return 'guest';
			$sessionKey = Cookie::get('PHPSESSID');

			if (empty($sessionKey)) return 'guest';
			else $sessionKey = md5($sessionKey);

			$envInfos = self::GetBrowserEnvInfo();
			if (!is_array($envInfos)) return 'guest';

			$session = $ENV['session'] = $db->where('session', $sessionKey)
				->get('sessions');

			if (empty($session)) return 'guest';
			else $userId = $session[0]['userid'];

			$session = $session[0];

			$user = $db->where('id',$userId)->getOne('users');
			if (empty($user)) return 'guest';

			# IP-cím ellenörzése
			if (UserSettings::Get('security.checkSessionIp',$userId) != 'false')
				if ($session['ip'] != $envInfos['ip'])
					return 'guest';

			# User-agent ellenörzése
			if (UserSettings::Get('security.checkUserAgent',$userId) != 'false')
				if ($session['useragent'] != $envInfos['useragent'])
					return 'guest';

			# Felhasználó szerepkörének megállapítása
			if ($session['activeSession'] == 0){
				if ($user['role'] == 'none') return 'guest';

				$tempSession = $db->where('sessionid',$session['id'])->getOne('temporary_roles');
				if (!empty($tempSession)){
					if (self::UserActParent($tempSession['classid'])) return 'guest';

					$user['class'][0] = $tempSession['classid'];
					$user['tempSession'] = true;
					return array($tempSession['role'],$user);
				}

				return array($user['role'],$user);
			}

			$classMemShip = $db->where('id',$session['activeSession'])->getOne('class_members');

			if (empty($classMemShip)) return 'guest';
			if (self::UserActParent($classMemShip['classid'])) return 'guest';

			$user['class'][0] = $classMemShip['classid'];
			return array($classMemShip['role'],$user);
		}

		// Bejelentkezés
		static private function _login($username,$password,$calledAgain = false){
			global $db, $ENV;

			# Formátum ellenörzése
			if (self::InputCheck($username,'username')) return 1;

			$data = $db->where('username',$username)->getOne('users');
			if (empty($data)) return 2;

			if (!$data['active']) return 4;

			# Felhasználó rendelkezik-e élő szerepkörrel?
			if ($data['defaultSession'] == 0){
				if ($data['role'] == 'none'){
					if (!$calledAgain){
						self::FindRole($data);
						return self::_login($username,$password,true);
					}
					else
						return 5;
				}
			}
			else {
				$Roles = $db->where('id',$data['defaultSession'])->getOne('class_members');
				if (empty($Roles)){
					if (!$calledAgain){
						self::FindRole($data);
						return self::_login($username,$password,true);
					}
					else
						return 5;
				}
			}

			# Felhasználó beléphet-e a szerepköre alapján?
			if ($data['defaultSession'] != 0){
				$conn = $db->where('id',$data['defaultSession'])->getOne('class_members');
				if (System::UserActParent($conn['classid']))
					return 7;
			}

			$IP = $ENV['SERVER']['REMOTE_ADDR'];
			$failedLogins = $db->rawQuery(
				'SELECT COUNT(*) as cnt FROM log__failed_login
				WHERE userid = ? && ip = ? && corrected IS NULL && at > NOW() - INTERVAL 2 MINUTE',array($data['id'],$IP));
			if (!empty($failedLogins[0]['cnt']) && $failedLogins[0]['cnt'] > 5)
				return 3;

			if (!Password::Ellenorzes($password,$data['password'])){
				Logging::Insert(array(
					'action' => 'login.failed_login',
					'db' => 'failed_login',
					'userid' => $data['id'],
					'ip' => $IP,
					'user' => 0,
				));
				return 2;
			}
			else $db->where('userid', $data['id'])
					->where('ip', $IP)
					->where('corrected IS NULL')
					->update('log__failed_login', array('corrected' => date('c')));

			# Session generálása és süti beállítása
			$session = Password::GetSession($username);
			Cookie::set('PHPSESSID',$session,false);

			$envInfos = self::GetBrowserEnvInfo();
			if (!is_array($envInfos)) return 6;

			self::_clearSessions($data);

			$db->insert('sessions',array(
				'session' => md5($session),
				'userid' => $data['id'],
				'ip' => $envInfos['ip'],
				'useragent' => $envInfos['useragent'],
				'activeSession' => $data['defaultSession'],
			));

			return [$data['id']];
		}
		static function Login($username,$password){
			$action = self::_login($username,$password);

			Logging::Insert(array(
				'action' => 'system.login',
				'user' => (is_array($action) ? $action[0] : 0),
				'errorcode' => (!is_array($action) ? $action : 0),
				'db' => 'login',

				'username' => $username,
			));

			return $action;
		}

		// Kiléptetés
		static function Logout($User = null){
			global $user;

			if (!empty($User))
				self::_clearSessions($User);

			else {
				# Felh. bejelentkézésnek ellenörzése
				if (empty($user) || !is_array($user)) return 1;

				self::_clearSessions($user);
				Cookie::delete('PHPSESSID');
			}

			return 0;
		}

		// Munkamenetek törlése
		static private function _clearSessions($user){
			global $db;

			$db->where('userid', $user['id'])->delete('sessions');
		}

		static function FindRole($User){
			global $db;

			$defSession = 0;

			if ($User['role'] == 'none'){
				$Roles = $db->where('userid',$User['id'])->get('class_members');

				if (!empty($Roles))
					$defSession = $Roles[0]['id'];
			}

			$db->where('id',$User['id'])->update('users',array(
				'defaultSession' => $defSession,
			));
		}

		static function CompilePerms(){
			global $Perm, $ENV;

			if (ROLE == 'guest')
				return $ENV['permissions'] = $Perm['guest'];

			if (in_array(ROLE,array_keys($Perm))){
				$ENV['permissions'] = $Perm[ROLE];
				$ENV['permissions'] = array_merge_recursive($ENV['permissions'],$Perm['everybody']);

				return;
			}

			$roles = array_keys($Perm['students']);
			if (!in_array(ROLE,$roles) && ROLE != 'guest') return;

			$ENV['permissions'] = $Perm['everybody'];

			if (in_array(ROLE,$roles)){
				$index = array_search(ROLE,$roles);
				for ($i = 0; $i <= $index; $i++)
					$ENV['permissions'] = array_merge_recursive($ENV['permissions'],$Perm['students'][$roles[$i]]);
			}
			else
				$ENV['permissions'] = array_merge_recursive($ENV['permissions'],$Perm[ROLE]);
		}

		static function GetAvailableRoles($userid = null){
			global $db,$user,$ENV;

			if (empty($userid))
				$User = $user;

			else {
				$User = $db->where('id',$userid)->getOne('users');
				if (empty($User)) return [];
			}

			$classMem = $db->where('userid',$User['id'])->get('class_members');

			$roles = [];
			foreach ($classMem as $entry){
				$Class = $db->where('id',$entry['classid'])->getOne('class');
				if (empty($Class)) continue;

				$School = $db->where('id',$Class['school'])->getOne('school');

				$roles[] = array(
					'entryId' => $entry['id'],
					'intezmeny' => $School['name'],
					'osztaly' => $Class['classid'],
					'szerep' => UserTools::$roleLabels[$entry['role']],
					'active' => $entry['id'] == $ENV['session'][0]['activeSession'] ? 1 : 0,
				);
			}

			if ($User['role'] != 'none')
				$roles[] = array(
					'intezmeny' => "CuStudy",
					'osztaly' => 0,
					'szerep' => "Globális rendszeradminisztrátor",
					'entryId' => 0,
					'active' => $ENV['session'][0]['activeSession'] == 0 ? 1 : 0,
				);

			return $roles;
		}

		static function SetAvailableRoles($roleId){
			global $user, $db, $ENV;

			if ($roleId != 0){
				$data = $db->where('id',$roleId)->where('userid',$user['id'])->getOne('class_members');
				if (empty($data)) return 1;
			}
			else if ($user['role'] == 'none') return 1;

			if ($ENV['session'][0]['activeSession'] == $roleId) return 3;

			$action = $db->where('id',$ENV['session'][0]['id'])->update('sessions',array(
				'activeSession' => $roleId,
			));

			if ($action) return 0;
			else return 2;
		}

		static function EjectRole($roleId, $password){
			global $db, $user, $ENV;

			if ($roleId == 0) return 1;

			$roles = self::GetAvailableRoles();
			$founded = false;
			foreach ($roles as $role)
				if ($role['entryId'] == $roleId)
					$founded = true;

			if (!$founded) return 2;
			if ($user['defaultSession'] == $roleId) return 3;

			if (count($roles) == 1)
				return 4;

			if (!Password::Ellenorzes($password,$user['password']))
				return 5;

			$action = $db->where('id',$roleId)->delete('class_members');
			if (!$action) return 6;

			if ($ENV['session'][0]['activeSession'] == $roleId){
				$db->where('id',$ENV['session'][0]['id'])->update('sessions',array(
					'activeSession' => $user['defaultSession'],
				));

				return true;
			}
			else
				return false;
		}

		static function ChangeDefaultRole($roleId){
			global $db, $user, $ENV;

			$roles = self::GetAvailableRoles();
			$founded = false;
			foreach ($roles as $role)
				if ($role['entryId'] == $roleId)
					$founded = true;

			if (!$founded) return 1;

			$action = $db->where('id',$user['id'])->update('users',array(
				'defaultSession' => $roleId,
			));

			if ($action) return 0;
			else return 2;
		}

		// Jogosultság ellenörző
		static function PermCheck($action, $id = null, $selector = 'id'){
			global $ENV, $user, $permKeyDB, $db;

			# Alapjog. ell.
			$array = explode('.',$action);
			if (count($array) > 2){
				$Array = $array;
				$Array[0] = $array[0].'.'.$array[1];
				$Array[1] = $array[2];
				$array = $Array;
			}
			if (!isset($ENV['permissions'][$array[0]])) return true;
			if (!in_array($array[1],$ENV['permissions'][$array[0]])) return true;
			if (empty($id)) return false;

			# Módosítási jog. ellenörzése
			if (isset($permKeyDB[$array[0]])) $array[0] = $permKeyDB[$array[0]];
			$data = $db->where($selector,$id)->getOne($array[0]);

			if (empty($data)) return true;
			if (!in_array($data['classid'],$user['class'])) return true;

			return false;
		}

		// Szükséges értékek ellenörzése
		static function ValuesExists($data,$reqitem){
			foreach ($reqitem as $sub)
				if (!isset($data[$sub])) return false;

			return true;
		}

		// Válaszadó funkció AJAX-hoz
		static function Respond($m = 'A művelet végrehajtása sikertelen volt!', $s = 0, $x = array(), $httpCode = 200){
			header('Content-Type: application/json');
			http_response_code($httpCode);

			if ($m === true) $m = array();
			if (is_array($m) && $s == false && empty($x)){
				$m['status'] = true;
				echo json_encode($m);
				exit;
			}
			if ($m === Message::$DB_FAIL && System::PermCheck('admin')){
				global $db;
				$m .= ": ".$db->getLastError();
			}
			$r = array(
				"message" => $m,
				"status" => $s,
			);
			if (!empty($x)) $r = array_merge($r, $x);

			print json_encode($r);
			die();
		}

		// Asszociatív tömb-e?
		static function IsAssoc($array) {
            return $array !== array_values($array);
		}

		// Idegen értékek törlése a tömbből
		static function TrashForeignValues($req,$array){
			$ret = array();
			foreach ($array as $key => $value)
				if (in_array($key,$req)) $ret[$key] = $value;
			return $ret;
		}

		// Névelő
		static function Nevelo($str,$upperc = false,$btw = ''){
			trigger_error('A System::Nevelo funkció helyett a System::Article funkciót használd', E_USER_DEPRECATED);
			return System::Article($str, $upperc, $btw);
		}

		static function Redirect($url, $die = true, $http = 301){
			header("Location: $url",$die,$http);
			if ($die) die();
		}
		static function TempRedirect($url, $die = true){
			self::Redirect($url, $die, 302);
		}

		// Belépés külső szolgáltató segítségével
		static function ExternalLogin($userData, $provider){
			global $db;

			$data = $db->where('account_id', $userData['account_id'])->where('provider',$provider)->getOne('ext_connections');

			if (empty($data)) return 1;
			if (!$data['active']) return 2;

			$user = $db->where('id',$data['userid'])->getOne('users');
			if (empty($user)) return 3;

			if ($user['defaultSession'] != 0){
				$conn = $db->where('id',$user['defaultSession'])->getOne('class_members');
				if (System::UserActParent($conn['classid']))
					return 4;
			}

			# Felhasználó rendelkezik-e élő szerepkörrel?
			$Roles = $db->where('userid',$user['id'])->getOne('class_members');
			if (empty($Roles))
				return 6;

			$db->where('id', $data['id'])->update('ext_connections',array(
				'name' => isset($userData['name']) ? $userData['name'] : '',
				'email' => isset($userData['email']) ? $userData['email'] : '',
				'picture' => isset($userData['picture']) ? $userData['picture'] : '',
			));

			$session = Password::GetSession($user['username']);
			$envInfos = self::GetBrowserEnvInfo();
			if (!is_array($envInfos)) return 5;

			self::_clearSessions($user);

			$db->insert('sessions',array(
				'session' => md5($session),
				'userid' => $user['id'],
				'ip' => $envInfos['ip'],
				'useragent' => $envInfos['useragent'],
				'activeSession' => $user['defaultSession'],
			));

			Cookie::set('PHPSESSID',$session,null);

			return 0;
		}

		static function SendMail($mail){
			global $db;

			if (!defined('MAIL_USE_CRON'))
				$cron = false;
			else
				$cron = MAIL_USE_CRON;

			if ($cron){
				$db->insert('mail_queue',array(
					'title' => $mail['title'],
					'name' => $mail['to']['name'],
					'address' => $mail['to']['address'],
					'body' => $mail['body'],
				));

				return 0;
			}
			else
				return self::DispatchMail($mail);
		}

		static $mailSended = false;
		static function DispatchMail($mail){
/*          array(
				'title' (string)
				'to' => array(
					'name' (string)
					'address' (string)
				)
				'body' (string)
			) */

			System::LoadLibrary('swiftMailer');

			$message = Swift_Message::newInstance($mail['title']); // Üzenet objektum beállítása és tárgy létrehozása

			$message->setBody($mail['body'], 'text/html'); // Szövegtörzs beállítása és szövegtípus beállítása
			$message->setFrom(array(MAIL_ADDR => MAIL_DISPNAME)); // Feladó e-mail és feladó név
			$message->setTo(array($mail['to']['address'] => $mail['to']['name'])); // Címzett e-mail és címzett

			$transport = Swift_SmtpTransport::newInstance(MAIL_HOST, MAIL_PORT, 'ssl') // Kapcsolódási objektum létrehozása
		     ->setUsername(MAIL_USRNAME) // SMTP felhasználónév
		     ->setPassword(MAIL_PWD) // SMTP jelszó
		     ->setSourceIp('0.0.0.0'); // IPv4 kényszerítése

		    $mailer = Swift_Mailer::newInstance($transport); // Küldő objektum létrehozása

		    $action = $mailer->send($message); // Levél küldése

			// Várakoztatás
		    if (!self::$mailSended) usleep(100);
		    self::$mailSended = true;

			return $action ? 0 : 1;
		}

		static function FixPath($desired_path, $http = 301){
			$query = !empty($_SERVER['QUERY_STRING']) ? preg_replace('~do=[^&]*&data=[^&]*(&|$)~','',$_SERVER['QUERY_STRING']) : '';
			if (!empty($query)) $query = "?$query";
			if ($_SERVER['REQUEST_URI'] !== "$desired_path$query")
				self::Redirect("$desired_path$query", STAY_ALIVE, $http);
		}

		static function ConnectToDatabase(){
			try {
				$db = new MysqliDb(DB_HOST,DB_USER,DB_PASS,DB_NAME);
				@$db->connect();
			}
			catch (Exception $e){
				return false;
			}

			return $db;
		}

		static function CheckMaintenance(){
			global $ENV, $db, $error;

			if (!is_object($db)){
				$error = 'DB_CONNECTION_FALIED';
				return true;
			}

			$main = $ENV['maintenance'];

			if (!$main['enabled']) return false;

			if (!empty($main['start']) && !empty($main['end'])){
				$start = strtotime($main['start']);
				$end = strtotime($main['end']);

				if ($start !== false && $end !== false){
					$now = time();
					return ($start <= $now && $end >= $now) ? true : false;
				}
			}

			return true;
		}

		static function LoadMaintenance(){
			global $ENV, $error;

			if (!self::CheckMaintenance()) return;

			if (!file_exists($ENV['maintenance']['requiredDoc'])) die();

			require $ENV['maintenance']['requiredDoc'];
			die();
		}

		# Make any absolute URL HTTPS
		static function MakeHttps($url){
			return preg_replace('~^(https?:)?//~','https://',$url);
		}

		/**
		 * Határozott névelő hozzáadása egy stringhez
		 *
		 * @param string $str    Karaktersorozat
		 * @param bool   $upperc Nagybetűvel kezdődjön-e a névelő
		 * @param string $btw    Névelő és szó közé beillesztendő szöveg
		 *
		 * @return string
		 */
		static function Article($str, $upperc = false, $btw = ''){
			$a = $upperc ? 'A' : 'a';
			$str = trim($str);
			if (preg_match('/^(\d+)/', $str, $num)){
				$number = intval($num[1], 10);
				if (
					($number < 10 && ($number == 1 || $number == 5)) ||
					($number >= 20 && $number != 100 && strpos('15',strval($number)[0]) !== false)
				) $a .= 'z';
			}
			else if (preg_match('/^[aáoóuúeéiíöőüű]/i',$str))
				$a .= 'z';
			return "$a ".($btw ? "$btw " : '').$str;
		}

		// Figyelmeztető üzenet
		static function Notice($type, $title, $text = null, $center = false, $hidden = false){
			$NOTICE_TYPES = array('info','success','fail','warn','caution');

			if (!in_array($type, $NOTICE_TYPES))
				throw new Exception("Invalid notice type $type");

			if (!is_string($text)){
				if (is_bool($text))
					$center = $text;
				$text = $title;
				unset($title);
			}

			$HTML = '';
			if (!empty($title))
				$HTML .= '<label>'.htmlspecialchars($title).'</label>';

			$textRows = preg_split("/(\r\n|\n|\r){2}/", $text);
			foreach ($textRows as $row)
				$HTML .= '<p>'.trim($row).'</p>';

			if ($center)
				$type .= ' align-center';

			$hidden = $hidden ? 'style="display: none;"' : '';
			return "<div class='notice $type' $hidden>$HTML</div>";
		}

		/**
		 * A funkció segítségével megoldható az, hogy csak egy új commit után csak egyszer végrehajtódjon egy PHP szkript.
		 * Hasznos lehet például abban az esetben, ha szükséges az új rendszerhez az adatbázis-szekezet frissítése, és ezt automatizálni szeretnénk.
		 */
		static function RunUpdatingTasks(){
			global $db, $root, $ENV;

			$script = $root.'update.inc.php';

			# Kell-e futtatni a frissítő szkriptet?
			if (empty($ENV['SOFTWARE']['COMMIT']))
				return;

			$data = $db->where('`key`','lastRunningCommit')->getOne('settings_global');
			if (empty($data)) return;

			if ($data['value'] == $ENV['SOFTWARE']['COMMIT'])
				return;

			# Szkript újboli futtatásának megakadályozása
			$db->where('`key`','lastRunningCommit')->update('settings_global',array(
				'value' => $ENV['SOFTWARE']['COMMIT'],
			));

			# Létezik-e a frissítő szkript?
			if (!file_exists($script))
				return;

			# Minden rendben van, futtassuk az Updating szkriptet...
			require_once $script;

			# Törlöm a szkriptet, nehogy lefusson mégegyszer!
			unlink($script);

			# Figyelmeztető üzenet a felhasználónak, funckió vége!
			die("A CuStudy frissítése befejeződött, a frissítési utómunkálatok végrehajtódtak! Kérem, frissítse ezt az oldalt a CuStudy betöltéséhez!");
		}

		/**
		 * @param string $do
		 * @return string
		 */
		static function GetMobileHeader($do){
			if ($do === 'landing' || $do === 'login')
				return '';
			return <<<HTML
<header id="mobile-header">
	<div class="sidebar-toggle"></div>
	<h1>CuStudy</h1>
</header>
HTML;
		}
	}
