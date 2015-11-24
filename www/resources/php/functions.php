<?php
	class Password {
		static function Kodolas($str){
			$final = '$SHA$';
			$hash = hash('sha256', $str);
			$salt = substr((string) md5(time()+rand()),0,16);
			$final .= $salt.'$'.hash('sha256',$hash.$salt);
			return $final;
		}
		static function Ellenorzes($input,$dbpass){
			$tmp = explode('$', $dbpass);
			return (hash('sha256', hash('sha256', $input) . $tmp[2]) == $tmp[3]);
		}
		static function Generalas($length = 10) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, strlen($characters) - 1)];
			}
			return $randomString;
		}
		static function GetSession($username){
			global $_SERVER;
			return sha1($username.microtime().$_SERVER['REMOTE_ADDR']);
		}
	}

	class Logging {
		static $subTables = array(
			'login' => 'login',
		);

		static $DataTitles = array(
			'CONSTANS' => array(
				'useragent' => 'Böngésző azonosítója',
				'ipaddr' => 'IP-cím',
				'errorcode' => 'Hibakód',
			),

			'login' => array(
				'username' => 'Begépelt felhasználónév',
				'user' => 'Belépett felhasználó',
			),
		);

		private function _getHeader(){
			global $ENV;

			if (!isset($ENV['SERVER']['HTTP_USER_AGENT']) || !isset($ENV['SERVER']['REMOTE_ADDR'])) return 1;
			return array(
				'useragent' => $ENV['SERVER']['HTTP_USER_AGENT'],
				'ipaddr' => $ENV['SERVER']['REMOTE_ADDR'],
			);
		}

		private function _insertCentral($data){
/*          array(
				(req)'action' => 'login',
				(opt)'user' => 0,
				(opt)'errorcode' => 0,
				(opt)'sublogid' => 0,
			);                              */

			global $user,$db;

			if (!isset($data['user'])) $data['user'] = $user['id'];

			return $db->insert('log_central',array_merge($data,$this->_getHeader()));
		}

		private function _insertSubLog($data = null){
/*          array(
				(req)'db' => 'login',
				(opt) ...
			);                              */
			global $db;

			if (empty($data)) return true;

			$datab = $data['db'];
			unset($data['db']);

			return $db->insert('log_'.$datab,$data);
		}

		private function _spliceData($data){
			$splita = ['action','user','errorcode'];

			foreach ($splita as $value){
				if (isset($data[$value])){
					$splito['central'][$value] = $data[$value];
					unset($data[$value]);
				}
			}
			$splito['sublog'] = $data;

			return $splito;
		}

		private function _translateDbTitles($centraldata){
			global $db;

			switch ($centraldata['action']){
				case 'login':
					$query = 'SELECT username,user,ipaddr,errorcode
							FROM `log_login` INNER JOIN log_central
							ON log_central.sublogid = log_login.id
							WHERE log_login.id = '.$centraldata['sublogid'];
				break;

				default:
					return false;
			}

			$data = $db->rawQuery($query);
			if (empty($data)) return false;

			$titles = array_merge(self::$DataTitles['CONSTANS'],self::$DataTitles[$centraldata['action']]);

			$Finished = array();

			foreach ($data as $subdata)
				foreach ($subdata as $key => $value)
					$Finished[] = array($titles[$key],$value);

			return $Finished;
		}

		static function Insert($data_p){
/*          array(
				(req)'action' => 'login',
				(opt)'db' => 'login',
				(opt)'user' => 0,
				(opt)'errorcode' => 0,
			);                              */

			$logclass = new Logging();

			# Adatok szétválasztása a funkcióknak
			$data = $logclass->_spliceData($data_p);

			# Bejegyzés készítése az altáblába
			$action = $logclass->_insertSubLog($data['sublog']);

			# Altábla bejegyzés ellenörzése
			if ($action === false) return 2;
			if ($action === true) $data['central']['sublogid'] = 0;
			$data['central']['sublogid'] = $action;

			# Bejegyzés készítése a főtáblába
			$action = $logclass->_insertCentral(array_merge($data['central'],array('db' => $data_p['db'])));

			# Eredmény feldolgozása
			return $action ? 0 : 3;
		}

		# TODO Át kell írni a GetDetails() funkciót az új jogosultsági szintekhez!
		static function GetDetails($id){
			global $db,$user,$ENV;
			$logclass = new Logging();

			# Bejegyzés ellenörzése
			if (!preg_match('/^\d+$/',$id)) return 1;
			$dataid = $db->where('id',$id)->getOne('log_central');
			$userdataid = $db->where('id',$dataid['user'])->getOne('users');

			# Jogosultság ellenörzése
			switch (ROLE){
				case 'admin':
					if ($dataid['user'] === 0) return 2;
					if ($userdataid['classid'] != $user['class'][0]) return 3;
				break;

				case 'schooladmin':
					if ($dataid['user'] === 0) return 2;
					$classdata = $db->where('id',$userdataid['classid'])->getOne('class');
					if ($ENV['school']['id'] != $classdata['id']) return 3;
				break;

				case 'sysadmin': break;

				default:
					return 4;
			}

			if (!isset(self::$subTables[$dataid['action']])) return 5;

			$action['details'] = $logclass->_translateDbTitles($dataid);

			return $action['details'] !== false ? $action : 6;
		}
	}

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
					$preg = '/^[0-9A-ZÁÉÍÓÖŐÚÜŰa-záéíóöőúüű.-?,!()"" ]{2,}$/';
				break;
				case 'suburl':
					$preg = '/^[a-zA-Z0-9\/]{1,}$/';
				break;
				case 'shortn_teacher':
					$preg = '/^[A-ZÖÜÓÚŐÉÁŰa-zéáűőúöüó.]{2,}$/';
				break;
				case 'lesson':
					$preg = '/^[A-Za-zöüóőúéáűÖÜÓŐÚÉÁŰ.() ]{4,15}$/';
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

		static function GetUserClasses($userid){
			global $user, $db;

			$data = $db->where('userid',$userid)->get('class_members');
			$classes = array();
			foreach ($data as $array)
				$classes[] = $array['classid'];

			if (!empty($user)) $user['class'] = $classes;

			return $classes;
		}

		//Cookie ellenőrzés & '$user' generálása
		static function CheckLogin() {
			global $db, $user, $ENV;

			if (!Cookie::exists('PHPSESSID')) return 'guest';
			$session = Cookie::get('PHPSESSID');

			if (empty($session)) return 'guest';
			else $session = md5($session);

			$envInfos = self::GetBrowserEnvInfo();
			if (!is_array($envInfos)) return 'guest';

			$query = $db->rawQuery("SELECT *
						FROM `sessions`
						WHERE `session` = ? && `ip` = ? && `useragent` = ?
						LIMIT 1",array($session,$envInfos['ip'],$envInfos['useragent']));

			if (empty($query)) return 'guest';
			else $userId = $query[0]['userid'];

			$user = $db->where('id',$userId)->getOne('users');
			if (empty($user)) return 'guest';

			# Osztálytagságok megállapítása
			self::GetUserClasses($user['id']);

			if (self::UserActParent($user['class'][0])) return 'guest';

			return $user['role'];
		}

		// Bejelentkezés
		static private function _login($username,$password){
			global $db;

			# Formátum ellenörzése
			if (self::InputCheck($username,'username')) return 1;

			$data = $db->where('username',$username)->getOne('users');
			if (empty($data)) return 2;

			if (!Password::Ellenorzes($password,$data['password'])) return 2;
			if (!$data['active']) return 4;

			if (self::UserIsStudent($data['role']))
				if (self::UserActParent(self::GetUserClasses($data['id'])[0])) return 5;

			# Session generálása és süti beállítása
			$session = Password::GetSession($username);
			Cookie::set('PHPSESSID',$session,false);

			$envInfos = self::GetBrowserEnvInfo();
			if (!is_array($envInfos)) return 'guest';

			$db->rawQuery("DELETE FROM `sessions`
						WHERE `userid` = ?",array($data['id']));

			$db->insert('sessions',array(
				'session' => md5($session),
				'userid' => $data['id'],
				'ip' => $envInfos['ip'],
				'useragent' => $envInfos['useragent'],
			));

			return [$data['id']];
		}
		static function Login($username,$password){
			$action = self::_login($username,$password);

			Logging::Insert(array(
				'action' => 'login',
				'user' => (is_array($action) ? $action[0] : 0),
				'errorcode' => (!is_array($action) ? $action : 0),
				'db' => 'login',

				'username' => $username,
			));

			return $action;
		}

		// Kiléptetés
		static function Logout(){
			global $db, $user;

			# Felh. bejelentkézésnek ellenörzése
			if (empty($user)) return 1;

			$db->rawQuery("DELETE FROM `sessions`
						WHERE `userid` = ?",array($user['id']));

			Cookie::delete('PHPSESSID');

			return 0;
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

		// Jogosultság ellenörző
		static function PermCheck($action, $id = null, $selector = 'id'){
			global $ENV, $user, $permKeyDB, $db;

			# Alapjog. ell.
			$array = explode('.',$action);
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
		static function Respond($m = 'A művelet végrehajtása sikertelen volt!', $s = 0, $x = array()){
			header('Content-Type: application/json');
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
		static function TrashForeignValues($req,$array,$assoc = true){
			$ret = array();
			if ($assoc){
				foreach ($array as $key => $value)
					if (in_array($key,$req)) $ret[$key] = $value;
			}
			return $ret;
		}

		// Névelő
		static function Nevelo($str,$upperc = false,$btw = ''){
			$match = '/^(a|á|o|ó|u|ú|e|é|i|í|ö|ő|ü|ű|1|5)/i';

			if ($upperc === true || $upperc == 'true' ) $a = "A";
			else $a = "a";

			if (preg_match($match,preg_replace('/[^\w\d]/i','',trim($str)))) return $a.'z '.($btw ? ' '.$btw : '').$str;
			else return $a.' '.($btw ? ' '.$btw : '').$str;
		}

		static function Redirect($url, $die = true, $http = 301){
			header("Location: $url",$die,$http);
			if ($die) die();
		}

		static function ExternalLogin($userID, $provider = 'google'){
			global $db;

			$data = $db->where('account_id',$userID)->where('provider',$provider)->getOne('ext_connections');

			if (empty($data)) System::Redirect("/?errtype=local&prov={$provider}&err=nem található a távoli fiókhoz kacsolt felhasználó");
			if (!$data['active']) System::Redirect("/?errtype=local&prov={$provider}&err=inaktív az összekapcsolás");

			$user = $db->where('id',$data['userid'])->getOne('users');
			if (empty($user)) System::Redirect("/?errtype=local&prov={$provider}&err=az összekapcsolás létezik, de nem található a helyi felhasználó");

			if (self::UserIsStudent($user['role']))
				if (self::UserActParent(self::GetUserClasses($user['id'])[0]))
					System::Redirect("/?errtype=local&prov={$provider}&err=az osztály vagy iskola nem aktív a rendszerben");

			$session = Password::GetSession($user['username']);
			$envInfos = self::GetBrowserEnvInfo();
			if (!is_array($envInfos)) System::Redirect('/');

			$db->rawQuery("DELETE FROM `sessions`
						WHERE `userid` = ?",array($user['id']));

			$db->insert('sessions',array(
				'session' => md5($session),
				'userid' => $user['id'],
				'ip' => $envInfos['ip'],
				'useragent' => $envInfos['useragent'],
			));

			Cookie::set('PHPSESSID',$session,null);

			System::Redirect('/');
		}

		static $mailSended = false;
		static function SendMail($mail){
/*          array(
				'title' (string)
				'to' => array(
					'name' (string)
					'address' (string)
				)
				'body' (string)
			) */

			if (!class_exists('Swift_Message'))
				trigger_error('Nincs betöltve a swiftMailer addon', E_USER_ERROR);

			$message = Swift_Message::newInstance($mail['title']); //Üzenet objektum beállítása és tárgy létrehozása

			$message->setBody($mail['body'], 'text/html'); //Szövegtörzs beállítása és szövegtípus beállítása
			$message->setFrom(array(MAIL_ADDR => MAIL_DISPNAME)); //Feladó e-mail és feladó név
			$message->setTo(array($mail['to']['address'] => $mail['to']['name'])); //Címzett e-mail és címzett

			$transport = Swift_SmtpTransport::newInstance(MAIL_HOST, MAIL_PORT, 'ssl') //Kapcsolódási objektum létrehozása
		     ->setUsername(MAIL_USRNAME) //SMTP felhasználónév
		     ->setPassword(MAIL_PWD) //SMTP jelszó
		     ->setSourceIp('0.0.0.0'); //IPv4 kényszerítése

		    $mailer = Swift_Mailer::newInstance($transport); //Küldő objektum létrehozása

		    $action = $mailer->send($message,$fail); //Levél küldése

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

		static function CheckMaintenance(){
			global $ENV, $db, $error;

			try {
				$db = new MysqliDb(DB_HOST,DB_USER,DB_PASS,DB_NAME);
				@$db->connect();
			}
			catch (Exception $e){
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
	}

	class CSRF {
		const tokenLength = 8;
		static function Generate(){
			if (Cookie::get('JSSESSID') !== false) Cookie::delete('JSSESSID');
			Cookie::set('JSSESSID',Password::Generalas(self::tokenLength),false);
		}

		static function Check($post){
			$cookie = Cookie::get('JSSESSID');

			if ($cookie === false) return false;
			if ($cookie == $post) return true;
			else return false;
		}
	}

	class GlobalSettings {
		static $Settings;

		// Globális beállítások betöltése
		static function Load(){
			global $db;

			$data = $db->get('global_settings');

			foreach ($data as $array)
				self::$Settings[$array['key']] = $array['value'];
		}

		// Beállítás lekérdezése
		static function Get($key){
			return !empty(self::$Settings[$key]) ? self::$Settings[$key] : null;
		}
	}

	class ExtConnTools {
		static $resolveAPIname = array(
			'facebook' => 'FacebookAPI',
			'google' => 'GoogleAPI',
			'microsoft' => 'MicrosoftAPI',
		);

		static $apiDisplayName = array(
			'facebook' => 'Facebook',
			'google' => 'Google',
			'microsoft' => 'Microsoft',
		);

		static function DeactAndAct($connid, $type = 'deactivate'){
			global $db, $user;

			if (System::InputCheck($connid,'numeric')) return 1;

			$data = $db->where('id',$connid)->getOne('ext_connections');
			if (empty($data)) return 2;

			if ($data['userid'] != $user['id']) return 3;

			if ($type == 'deactivate'){
				if (!$data['active']) return 4;
			}
			else
				if ($data['active']) return 4;

			$action = $db->where('id',$connid)->update('ext_connections',array(
				'active' => $type == 'deactivate' ? 0 : 1,
			));

			return !$action ? 5 : 0;
		}

		static function Unlink($connid){
			global $db, $user;

			if (System::InputCheck($connid,'numeric')) return 1;

			$data = $db->where('id',$connid)->getOne('ext_connections');
			if (empty($data)) return 2;

			if ($data['userid'] != $user['id']) return 3;

			$action = $db->where('id',$connid)->delete('ext_connections');

			return !$action ? 4 : 0;
		}
	}

	class Message {
		static $Messages = array();

		// Hibakód feldolgozása (to string)
		static function Respond($activity,$code = 0){
			$array = explode('.',$activity);

			$class = $array[0];
			$action = $array[1];

			if ($code != 0){
				$errorMsg = isset(self::$Messages[$class][$action]['errors'][$code]) ? self::$Messages[$class][$action]['errors'][$code] :
					'ismeretlen hiba történt a művelet során';

				return str_replace('@code',$code,str_replace('@msg',$errorMsg,self::$Messages[$class][$action]['messages'][1]));
			}
			
			else
				return isset(self::$Messages[$class][$action]['messages'][0]) ? self::$Messages[$class][$action]['messages'][0] : 'A művelet sikerült!';
		}

		static $HTTP_STATUS_CODES = array(
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Moved Temporarily',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Time-out',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Large',
			415 => 'Unsupported Media Type',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Time-out',
			505 => 'HTTP Version not supported',
		);

		static function StatusCode($code){
			if (!isset(self::$HTTP_STATUS_CODES[$code]))
				trigger_error('Érvénytelen státuszkód: '.$code,E_USER_ERROR);
			else
				header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.self::$HTTP_STATUS_CODES[$code]);
		}

		# 403-as hiba esetén
		static function AccessDenied($json = false){
			if ($json)
				System::Respond();
			else {
				if (USRGRP == 'guest')
					die(header('Location: /login'));
				else
					die(header('Location: /not-found'));
			}
		}

		# 404-es hiba esetén
		static function Missing($path = ''){
			global $ENV;

			if ($ENV['do'] != 'not-found')
				die(header('Location: /not-found?path='.$path));
		}

		static $DB_FAIL = "Hiba történt az adatbázisba mentés során";
	}

	class InviteTools {
		/*
			TODO Meghívó e-mail HTML javítása

			A felesleges HTML-t kiszedtem, ha el akarod távolitani
			a linkekről az aláhúzást, mindegyik linkre tegyél egy
			              style="text-decoration:none"
			attribútumot.
		*/
		static $inviteBody = <<<STRING
		<h2>CuStudy - Meghívó a CuStudy rendszerbe</h2>

		<h3>Tisztelt ++NAME++!</h3>

		<p>Örömmel értesítünk, hogy meghívót kaptál a CuStudy rendszerbe a(z) <b>++SCHOOL++</b> iskola <b>++CLASS++</b> osztálya által. A meghívót <b>++SENDER++</b> csoportadminisztrátor küldte neked.</p>

		<p><b>Mi is az a CuStudy?</b> A CuStudy a BetonHomeWork utódjaként továbbra is egy kellemetlen, de kötelező feladatra koncentrál: a házi feladatokra. A CuStudy - a BHW méltó utódjaként - teszi lehetővé számodra, hogy értesülhess a házi feladataidról és egyéb kötelességeidről, sőt a program használatával akutális és frissülő órarended is láthatod. Az elődünkhöz képest azonban jócskán fejlődtünk: mostantól <b>webes felületen</b> érheted el az információkat, illetve új felületet és számos izgalmas, funkcionalitást érintő fejlesztést is eszközöltünk, így az alapötlet egy remek felülettel és sok funkcióval párosul.</p>

		  <p>A meghívás elfogadásához <a href="https://custudy.tk/invitation/++ID++">kattints ide</a>! A linkre kattintva meg kell adnod néhány adatot magadról, be kell állítanod a jelszavad, és az űrlap elküldése után automatikusan átirányítunk a program főoldalára.</p><p>Ha a fenti gomb valamilyen okból kifolyólag nem működne, másold be az alábbi URL-t a böngésződ címsoráva:<br><a href="https://custudy.tk/invitation/++ID++">https://custudy.tk/invitation/++ID++</a></p><p>Bízunk benne, hogy a CuStudy a Te tetszésedet is elnyeri majd!</p>

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
			if (!empty($data)) return 5;

			$invId = Password::Generalas(12);
			$action = $db->insert('invitations',array(
				'invitation' => $invId,
				'name' => $name,
				'email' => $email,
				'classid' => $user['class'][0],
				'inviter' => $user['id'],
			));

			if (!$action) return 3;

			$body = self::$inviteBody;

			$body = str_replace('++NAME++',$name,$body);
			$body = str_replace('++SCHOOL++',$ENV['school']['name'],$body);
			$body = str_replace('++CLASS++',$ENV['class']['classid'],$body);
			$body = str_replace('++ID++',$invId,$body);
			$body = str_replace('++SENDER++',$user['name'],$body);

			$action = System::SendMail(array(
				'title' => 'CuStudy - Meghívásod érkezett',
				'to' => array(
					'name' => $name,
					'address' => $email,
				),
				'body' => $body,
			));

			if ($action) return 4;

			return 0;
		}

		static function BatchInvite($emails){
			# Jog. ellenörzése
			if (System::PermCheck('users.invite')) return 1;

			$invalidEntrys = [];
			foreach ($emails as $array){
				$action = self::Invite($array['email'],$array['name']);

				if ($action != 0) $invalidEntrys[] = array_merge(array('error' => $action),$array);
			}

			if (empty($invalidEntrys)) return 0;
			else return 2;
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
				'role' => 'visitor',
				'active' => 1,
			));

			# Hozzáadás a csoporthoz
			$db->insert('class_members',array(
				'classid' => $token_d['classid'],
				'userid' => $id,
			));

			$db->insert('sessions',array(
				'session' => md5($session),
				'userid' => $id,
				'ip' => $envInfos['ip'],
				'useragent' => $envInfos['useragent'],
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

	class FileTools {
		const CLASS_SPACE = 268435456;
		const CLASS_MAX_FILESIZE = 36700160;

		static function GetUsedSpace(){
			global $db, $user;

			return $db->where('classid', $user['class'][0])->getOne('files','SUM(size)')['SUM(size)'];
		}

		static function GetFreeSpace(){
			return self::CLASS_SPACE - self::GetUsedSpace();
		}

		static function GetSpaceUsage($key = null){
			$Used = (float) self::GetUsedSpace();
			$Available = self::CLASS_SPACE;

			$UsedPercent = 0;
			if ($Used > 0){
				$UsedPercent = round(($Used / $Available) * 1000) / 10;

				if ($UsedPercent == 0)
					$UsedPercent = '<0.01';
			}

			$UsedReadable = FileTools::FormatSize($Used);
			$AvailableReadable = FileTools::FormatSize($Available);

			$return = array(
				'Used' => $UsedReadable,
				'Available' => $AvailableReadable,
				'Used%' => $UsedPercent,
			);
			return !empty($key) ? $return[$key] : $return;
		}

		static function FormatSize($byte){
			if ($byte < 1024)
				return $byte.' B';
			else if ($byte > 1024 && $byte < 1024 * 1024)
				return round(($byte/1024),2).' KB';
			else
				return round(($byte/(1024*1024)),2).' MB';
		}

		static function UploadFile($file){
			// Sikerült-e a fájlfeltöltés?
			if ($file['error'] != 0) return 1;
			
			// Méret ellenörzése
			if ($file['size'] > self::CLASS_MAX_FILESIZE) return 2;
			
			// Van-e hely a tárhelyen?
			if ($file['size'] > self::GetFreeSpace()) return 3;
			
			// Van-e hely a szerveren?
			if ($file['size'] > disk_free_space('/')) return 4;
			
			// Hely meghatározása
			$fileName = Password::Generalas();
			$path = "usr_uploads/{$fileName}";
			
			// Mozgatás a végleges helyre
			if (move_uploaded_file($file['tmp_name'],$path)) return [$fileName];
			else return 5;
		}

		static function DownloadFile($id){
			global $db, $user, $root;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('files');

			if (empty($data)) die(header('Location: /files'));
			$fileName = $data['filename'];

			$path = "$root/usr_uploads/".$data['tempname'];
			if (!file_exists($path)) die();

			$finfo = finfo_open(FILEINFO_MIME_ENCODING);
			header('Content-Transfer-Encoding: utf-8');
			header("Content-Description: File Transfer");
			header("Content-Type: application/octet-stream");
			header('Content-Length: '.filesize($path));
			header("Content-Disposition: attachment; filename=\"$fileName\"");

			readfile($path);
			die();
		}

		static function DeleteFile($id){
			global $db, $user, $root;

			# Jog. ellenörzése
			if (System::PermCheck('files.delete')) return 1;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('files');
			if (empty($data)) return 2;

			$path = "$root/usr_uploads/".$data['tempname'];
			if (file_exists($path))
				unlink($path);

			$action = $db->where('id',$id)->delete('files');

			return $action ? 0 : 3;
		}

		static function GetFileInfo($id){
			global $db, $user, $root;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('files');
			if (empty($data)) return 1;

			$lesson = $db->where('id',$data['lessonid'])->getOne('lessons');
			$uploader = $db->where('id',$data['uploader'])->getOne('users');

			return array(
				'name' => $data['name'],
				'description' => $data['description'],
				'lesson' => empty($lesson) ? 'nincs hozzárendelve' : $lesson['name'],
				'size' => self::FormatSize($data['size']),
				'time' => $data['time'],
				'uploader' => empty($uploader) ? 'ismeretlen' : $uploader['name'].' (#'.$uploader['id'].')',
				'filename' => $data['filename'],
			);
		}
	}

	class AdminManTools {
		# Admin. hozzáadása
		static function Add($dataf){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('sysadmin')) return 2;

			# Bevitel ellenörzése
			foreach ($dataf as $key => $value){
				if (System::InputCheck($value,$key)) return 3;

				# Jelszó kódolása
				if ($key == 'password') $dataf[$key] = Password::Kodolas($value);
			}

			# Létezik-e már ilyen felh.?
			if ($db->where('username',$dataf['username'])->getOne('admins') != false) return 4;

			# Regisztráció
			$action = $db->insert('admins',$dataf);

			if (!$action) return 5;
			else return 0;
		}
	}

	class LessonTools {
// Tantárgy hozzáadása
		private static function _add($data_a){
			global $db,$ENV;

			# Jog. ellenörzése
			if (System::PermCheck('lessons.add')) return 1;

			# Formátum ellenörzése
			if (!System::ValuesExists($data_a,['name','teacherid'])) return 2;
			foreach ($data_a as $key => $value){
				if ($key == 'color') continue;
				switch ($key){
					case 'name':
						$type = 'lesson';
					break;
					case 'teacherid':
						$type = 'numeric';
					break;
					default:
						unset($data_a[$key]);
						continue 2;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			if (!isset($data_a['color']) || $data_a['color'] == '#000000') $data_a['color'] = 'default';
			$data_a['classid'] = $ENV['class']['id'];

			return [$db->insert('lessons',$data_a)];
		}
		static function Add($data_a){
			global $user;

			$action = self::_add($data_a);

			Logging::Insert(array_merge(array(
				'action' => 'lesson_add',
				'user' => $user['id'],
				'errorcode' => (!is_array($action) ? $action : 0),
				'db' => 'lesson_add',
			),$data_a,array(
				'classid' => $user['class'][0],
				'e_id' => (is_array($action) ? $action[0] : 0),
			)));


			return $action;
		}
// Tantárgy hozzáadása vége

// Tantárgy szerkesztése
		private static function _edit($data_a){
			global $db;

			# Formátum ellenörzése
			if (!System::ValuesExists($data_a,['name','teacherid','id'])) return 2;
			foreach ($data_a as $key => $value){
				if ($key == 'color') continue;
				switch ($key){
					case 'name':
						$type = 'lesson';
					break;
					case 'teacherid':
						$type = 'numeric';
					break;
					case 'id':
						$type = 'numeric';
					break;
					default:
						unset($data_a[$key]);
						continue;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			# Jogosultság ellenörzése
			if (System::PermCheck('lessons.edit',$data_a['id'])) return 1;

			$action = $db->where('id',$data_a['id'])->update('lessons',$data_a);

			if ($action) return 0;
			else return 3;
		}
		static function Edit($data_a){
			global $user;

			$action = self::_edit($data_a);

			if (isset($data_a['id'])){
				$data_a['e_id'] = $data_a['id'];
				unset($data_a['id']);
			}
			else $data_a['id'] = 0;

			Logging::Insert(array_merge(array(
				'action' => 'lesson_edit',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'lesson_edit',
			),$data_a,array(
				'classid' => $user['class'][0],
			)));

			return $action;
		}
// Tantárgy szerkesztése vége

// Tantárgy törlése
		private static function _delete($id){
			global $db;

			$action = $db->where('id',$id)->delete('lessons');

			$data = $db->rawQuery('SELECT tt.id
									FROM `timetable` tt
									WHERE tt.lessonid = ?',array($id));


			if (!empty($data)){
				Timetable::DeleteEntrys($data);
			}

			if ($action) return 0;
			else return 2;
		}
		static function Delete($id){
			global $user,$db;

			# Jog. ellenörzése
			if (System::PermCheck('lessons.delete',$id)) return 1;

			$data = $db->where('id',$id)->getOne('lessons');
			$data = System::TrashForeignValues(['classid','name','teacherid','color'],$data);

			$action = self::_delete($id);

			Logging::Insert(array_merge(array(
				'action' => 'lesson_del',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'lesson_del',
			),$data,array(
				'classid' => $user['class'][0],
				'e_id' => $id,
			)));

			return $action;
		}
	}
// Tantárgy törlése vége

	class ClassTools {
		static function AddClass($dataf){
			global $db;

			# Admin. jogkör ellenörzése
			if (System::PermCheck('schooladmin')) return 2;

/*			array(
				'classid' => 10.B
				'school' => 1
			);						*/

			# Formátum ellenörzése
			if (!System::ValuesExists($dataf,['classid','school'])) return 2;
			foreach ($dataf as $key => $value){
				if ($key == 'classid') $type = 'class';
				if ($key == 'school') $type = 'numeric';

				if (System::InputCheck($value,$type)) return 2;
			}

			# Létezik-e már ilyen osztály?
			if ($db->where('classid',$dataf['classid'])->getOne('class') != false) return 3;

			# Regisztráció
			$action = $db->insert('class',$dataf);

			return $action;
		}

		# Akitválás/Inaktiválás/Áll. lekérdezése
		static function ActiveI($case,$classid){
			global $db;

			# Admin. jogkör ellenörzése
			if (System::PermCheck('schooladmin')) return 2;

			switch ($case){
				case 'activate':
					return !$db->where('classid',$classid)->update('class',array(
						'active' => 1,
					));
				break;

				case 'inactivate':
					return !$db->where('classid',$classid)->update('class',array(
						'active' => 0,
					));
				break;

				case 'getstatus':
					$data = $db->where('classid',$classid)->getOne('class');

					# Felh. létezésének ellenörzése
					if (empty($data)) return 2;

					return $data['active'];
				break;
			}
		}
	}

	class UserTools {
		static $roleLabels = array(
			'visitor' => 'Ált. felhasználó',
			'editor' => 'Szerkesztő',
			'admin' => 'Csop. adminisztrátor',
			'systemadmin' => 'Rendszer adminisztrátor',
		);

// Felh. hozzáadása
		private static function _addUser($data_a){
			global $db, $user;

			# Jog. ellelnörzése
			if(System::PermCheck('users.add')) return 7;

			# Bevitel ellenörzése
			if (!System::ValuesExists($data_a,['username','name','role','email','active'])) return 1;
			foreach ($data_a as $key => $value){
				if (in_array($key,['classid','role'])) continue;

				switch ($key){
					case 'name':
						$type = 'name';
					break;
					case 'active':
						$type = 'numeric';
					break;
					default:
						$type = $key;
					break;
				}

				if (System::InputCheck($value,$type)) return 2;
			}
			if (System::OptionCheck($data_a['active'],['0','1'])) return 2;
			if (System::OptionCheck($data_a['role'],['visitor','editor','admin'])) return 2;

			# Létezik-e már ilyen felhasználó?
			$data = $db->where('username',$data_a['username'])->getOne('users');
			if (!empty($data)) return 4;
			$data = $db->where('email',$data_a['email'])->getOne('users');
			if (!empty($data)) return 6;

			# Ideiglenes jelszó készítése
			$data_a['password'] = Password::Kodolas(Password::Generalas(6));

			# Regisztráció
			$id = $db->insert('users',$data_a);
			if ($id === false) return 7;

			# Hozzáadás a csoporthoz
			$db->insert('class_members',array(
				'classid' => $user['class'][0],
				'userid' => $id,
			));

			return [$id];
		}

		static function AddUser($data_a){
			global $user;
/*			array(
				'username',
				'name',
				'priv',
				'email',
				'active',
			);					*/
			$action = self::_addUser($data_a);

			$data_a = System::TrashForeignValues(['username','name','role','email','active'],$data_a);

			Logging::Insert(array_merge(array(
				'action' => 'user_add',
				'user' => $user['id'],
				'errorcode' => (!is_array($action) ? $action : 0),
				'db' => 'user_add',
			),$data_a,array(
				'classid' => $user['class'][0],
				'e_id' => (is_array($action) ? $action[0] : 0),
			)));

			return $action;
		}
// Felh. hozzáadás vége

// Felh. adatainak módosítása
		private static function _modifyUser($id,$datas){
			global $db, $user;

			# Jog. ellenörzése
			if (System::PermCheck('users.edit')) return 1;

			# Formátum ellenörzése
			foreach ($datas as $key => $value){
				if (in_array($key,['classid','role'])) continue;

				switch ($key){
					case 'name':
						$type = 'name';
					break;
					case 'id':
						$type = 'numeric';
					break;
					case 'active':
						$type = 'numeric';
					break;
					default:
						$type = $key;
					break;
				}

				if (System::InputCheck($value,$type)) return 2;
			}
			if (System::OptionCheck($datas['active'],['0','1'])) return 2;
			if (System::OptionCheck($datas['role'],['visitor','editor','admin'])) return 2;

			# Jog. ellenörzése
			$data = $db->rawQuery('SELECT u.*
						FROM `users` u
						LEFT JOIN `class_members` cm
						ON u.id = cm.userid
						WHERE u.id = ? && cm.classid = ?',array($datas['id'],$user['class'][0]));
			if (empty($data)) return 1;

			# Létezik-e már ilyen felhasználó?
			$userdata = $db->where('id',$id)->getOne('users');

			if($datas['email'] != $userdata['email']){
				$data = $db->where('email',$datas['email'])->getOne('users');
				if (!empty($data)) return 6;
			}

			if (!empty($datas['username'])) unset($datas['username']);
			$action = $db->where('id',$id)->update('users',$datas);

			if ($action) return 0;
			else return 7;
		}

		static function ModifyUser($id,$datas){
			global $user;

			$action = self::_modifyUser($id,$datas);

			$datas = System::TrashForeignValues(['username','name','priv','email','active'],$datas);

			Logging::Insert(array_merge(array(
				'action' => 'user_edit',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'user_edit',
			),$datas,array(
				'classid' => $user['class'][0],
				'e_id' => $id,
			)));

			return $action;
		}
// Felh. adatainak módosítása vége

// Felh. törlése
		private static function _deleteUser($id){
			global $db;

			$action = $db->where('id',$id)->delete('users');

			if ($action) return 0;
			else return 2;
		}

		static function DeleteUser($id){
			global $user,$db;

			# Jog. ellenörzése
			if (System::PermCheck('users.delete')) return 1;

			$data = $db->rawQuery('SELECT u.*
									FROM `users` u
									LEFT JOIN `class_members` cm
									ON u.id = cm.userid
									WHERE u.id = ? && cm.classid = ?',array($id,$user['class'][0]));
			if (empty($data)) return 1;
			$data = $data[0];

			$data = System::TrashForeignValues(['username','name','role','email','active'],$data);

			$action = self::_deleteUser($id);

			Logging::Insert(array_merge(array(
				'action' => 'user_del',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'user_del',
			),$data,array(
				'classid' => $user['class'][0],
				'e_id' => $id,
			)));

			return $action;
		}
// Felh. törlése vége

		static function EditAccessData($id,$data){
			/* @param $id
			 * @param $data = array('newpassword','vernewpasswd')
			 */

            global $db,$user;

			# Jog. ellenörzése
			if (System::PermCheck('users.editSecurity')) return 1;
			$exists = $db->rawQuery('SELECT u.*
						FROM `users` u
						LEFT JOIN `class_members` cm
						ON u.id = cm.userid
						WHERE u.id = ? && cm.classid = ?',array($id,$user['class'][0]));
			if (empty($exists)) return 1;

			if ($data['newpassword'] != $data['vernewpasswd']) return 2;

			$action = $db->where('id',$id)->update('users',array(
				'password' => Password::Kodolas($data['newpassword']),
			));

			if ($action) return 0;
			else return 3;
		}

		static function EditMyProfile($data){
/*          array(
				(req)'name',
				(req)'email',
				(opt)'oldpassword',
				(opt)'password',
				(opt)'verpasswd'
			)                       */

			global $db,$user;

			# Felhasználó jelszavának ellenörzése
			if (!Password::Ellenorzes($data['oldpassword'],$user['password'])) return 1;

			# Jelszóváltoztatás esetén...
			if (!empty($data['oldpassword']) && !empty($data['password']) && !empty($data['verpasswd'])){
				if ($data['password'] != $data['verpasswd']) return 2;

				$data['password'] = Password::Kodolas($data['password']);
			}
			else unset($data['password']);

			unset($data['oldpassword']);
			unset($data['verpasswd']);

			$action = $db->where('id',$user['id'])->update('users',$data);

			if (!$action) return 3;
			else return 0;
		}

	}

	class PasswordReset {
		static function GetRow($hash){
			global $db;

			$Reset = $db->where('hash',$hash)->getOne('pw_reset');
			$Reset['expired'] = empty($Reset) || strtotime($Reset['expires']) < time();
			if ($Reset['expired'] && !empty($Reset['hash']))
				self::Invalidate($Reset['hash']);

			return $Reset;
		}

		static function Invalidate($hash){
			global $db;

			$db->where('id',$hash)->delete('pw_reset');
		}

		static $resetBody = <<<STRING
		<h2>CuStudy - Jelszóvisszaállítási kérelem</h2>

		<h3>Tisztelt ++NAME++!</h3>

		<p>A CuStudy rendszerében jelszava visszaállítását kezdeményezték. Ammennyiben nem Ön kérte ezt, az üzenetünket figyelmen kivül hagyhatja. Ellenkező esetben <a href="++URL++">kattintson ide</a> egy új jelszó megadásához, vagy másolja be ezt a linket a böngésző címsorába:<br><a href="++URL++">++URL++</a></p>

		<p>Felhívjuk figyelmét, hogy a link az üzenet küldéstől számítva 30 percig (++VALID++) használható. Amennyiben a lejárat előtt újabb jelzóvisszallítási kérelmet kezdeményez, a korábbi kérelmek törlésre kerülnek.</p>

		<p>Üdvözlettel,<br>
		<b>CuStudy Software Alliance</p>
STRING;

		static function SendMail($email){
			global $ENV, $db;

			$email = trim($email);
			if (System::InputCheck($email,'email')) return 1;

			$User = $db->where('email', $email)->getOne('users','id,name,email');
			if (empty($User)) return 2;

			// Korábbi visszaállítási kódok érvénytelenítése
			$db->where('userid', $User['id'])->delete('pw_reset');

			$hash = openssl_random_pseudo_bytes(64);
			$valid = strtotime('+30 minutes');

			if (!$db->insert('pw_reset',array(
				'hash' => $hash,
				'userid' => $User['id'],
				'expires' => date('c',$valid)
			))) return 3;

			$body = self::$resetBody;
			$body = str_replace('++NAME++',$User['name'],$body);
			$body = str_replace('++URL++',ABSPATH.'/pw-reset?key='.urlencode($hash),$body);
			$body = str_replace('++VALID++',date('Y-m-d H:i:s',$valid),$body);

			if (System::SendMail(array(
				'title' => 'CuStudy - Jelszóvisszaállítási kérelem',
				'to' => array(
					'name' => $User['name'],
					'address' => $User['email'],
				),
				'body' => $body,
			))) return 4;

			return 0;
		}

		static function Reset($data){
			global $ENV, $db;

			if (empty($data['hash'])) return 1;

			$Reset = self::GetRow(urldecode($data['hash']));
			if (empty($Reset) || $Reset['expired']) return 2;

			if (empty($data['password']) || empty($data['verpasswd'])) return 3;

			$password = $data['password'];
			$verpassword = $data['verpasswd'];

			$User = $db->where('id', $Reset['userid'])->getOne('users');
			if (empty($User)) return 4;

			if ($password != $verpassword) return 5;

			$password = Password::Kodolas($password);
			if (!$db->where('id', $User['id'])->update('users', array('password' => $password))) return 6;

			self::Invalidate($Reset['hash']);
			return 0;
		}
	}

	class GroupTools {
		static function Add($data){
			global $db,$user;

			# Jog. ellenörzése
			if (System::PermCheck('groups.add')) return 1;

			if (!System::ValuesExists($data,['name','theme','group_members'])) return 3;
			foreach ($data as $key => $value){
				switch($key){
					case 'name':
						$type = 'text';
					break;
					case 'theme':
						$type = 'numeric';
					break;
					default:
						continue 2;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			# Téma ellenörzése
			$theme = $db->where('id',$data['theme'])->getOne('group_themes');
			if (empty($theme)) return 4;

			$insertGroup = $db->insert('groups',array(
				'classid' => $user['class'][0],
				'name' => $data['name'],
				'theme' => $data['theme'],
			));

			$users = $db->rawQuery('SELECT u.*
										FROM `users` u
										LEFT JOIN `class_members` cm
										ON u.id = cm.userid
										WHERE cm.classid = ?',array($user['class'][0]));
			$users_l = array();
			foreach ($users as $entry)
				$users_l[] = $entry['id'];

			$grpmem = explode(',',$data['group_members']);

			if (empty($data['group_members'])) return 0;
			foreach($grpmem as $mem){
				if (!in_array($mem,$users_l)) return 5;
				$db->insert('group_members',array(
					'classid' => $user['class'][0],
					'groupid' => $insertGroup,
					'userid' => $mem,
				));
			}

			return 0;
		}

		static function Edit($id,$data){
			global $db,$user;

			if (System::InputCheck($id,'numeric')) return 2;

			# Jog. ellenörzése
			if (System::PermCheck('groups.edit',$id)) return 1;

			if (!System::ValuesExists($data,['name','theme'])) return 3;
			foreach ($data as $key => $value){
				switch($key){
					case 'name':
						$type = 'text';
					break;
					case 'theme':
						$type = 'numeric';
					break;
					default:
						continue 2;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			$db->where('id',$id)->update('groups',array(
				'name' => $data['name'],
				'theme' => $data['theme'],
			));

			if (!empty($data['class_members'])){
				$grpm = explode(',',$data['class_members']);

				$uids = [];
				foreach ($grpm as $entry){
					if (System::InputCheck($entry,'numeric')) return 4;
					$uids[] = $entry;
				}
				$query = 'DELETE FROM `group_members`
							WHERE `groupid` = ? && userid IN ('.implode(',',$uids).')';

				$db->rawQuery($query,array($id));
			}

			if (!empty($data['group_members'])){
				$grpm = explode(',',$data['group_members']);

				$members = $db->rawQuery('SELECT users.id
											FROM `group_members`
											LEFT JOIN `users`
											ON group_members.userid = users.id
											WHERE group_members.classid = ? && group_members.groupid = ?',array($user['class'][0],$id));
				$memb = array();
				foreach($members as $member)
					$memb[] = $member['id'];
				$members = $memb;

				foreach ($grpm as $entry){
					if (System::InputCheck($entry,'numeric')) return 4;
					if (in_array($entry,$members)) continue;
					$db->insert('group_members',array(
						'classid' => $user['class'][0],
						'groupid' => $id,
						'userid' => $entry,
					));
				}
			}

			return 0;
		}

		static function Delete($id){
			global $db,$user;

			if (System::InputCheck($id,'numeric')) return 2;

			# Jog. ellenörzése
			if (System::PermCheck('groups.delete',$id)) return 1;

			# Csop. ellenörzése
			$group = $db->rawQuery('SELECT *
						FROM `groups`
						WHERE `classid` = ? && `id` = ?',array($user['class'][0],$id));
			if (empty($group)) return 3;

			$members = $db->rawQuery('SELECT *
									FROM `group_members`
									WHERE `classid` = ? && `groupid` = ?',array($user['class'][0],$id));

			if (!empty($members)){
				$uids = [];
				foreach ($members as $entry)
					$uids[] = $entry['userid'];

				$query = 'DELETE FROM `group_members`
							WHERE `groupid` = ? && userid IN ('.implode(',',$uids).')';

				$db->rawQuery($query,array($id));
			}

			# Függőségek feloldása (timetable)
			$data = $db->where('classid',$user['class'][0])->where('groupid',$id)->get('timetable');
			foreach ($data as $array)
				Timetable::DeleteEntrys(array(array('id' => $array['id'])));

			$action = $db->where('id',$id)->delete('groups');

			return $action ? 0 : 4;
		}
	}

	class GroupThemeTools {
		static function Add($data){
			global $db,$user;

			# Jog. ellenörzése
			If (System::PermCheck('groupThemes.add')) return 1;

			# Szüks. értékek ellenörzése
			$data = System::TrashForeignValues(['name'],$data,true);
			if (!System::ValuesExists($data,['name'])) return 2;

			foreach ($data as $key => $value){
				switch ($key){
					case 'name':
						$type = 'text';
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			$data['classid'] = $user['class'][0];

			$action = $db->insert('group_themes',$data);

			if ($action === false) return 3;
			else return [$action];
		}

		static function Edit($data){
			global $db;

			# Jog. ellenörzése
			If (System::PermCheck('groupThemes.edit',$data['id'])) return 1;

			# Szüks. értékek ellenörzése
			$data = System::TrashForeignValues(['name','id'],$data,true);
			if (!System::ValuesExists($data,['name'])) return 2;
			foreach ($data as $key => $value){
				switch ($key){
					case 'name':
						$type = 'text';
					break;
					case 'id':
						continue 2;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			$action = $db->where('id',$data['id'])->update('group_themes',$data);

			if ($action) return 0;
			else return 3;
		}

		static function Delete($id){
			global $db,$user;

			# Jog. ellenörzése
			if (System::PermCheck('groupThemes.delete',$id)) return 1;

			# Csoportok törlése
			$groups = $db->where('classid',$user['class'][0])->where('theme',$id)->get('groups');

			foreach ($groups as $group)
				GroupTools::Delete($group['id']);

			# Kategória törlése
			$action = $db->where('id',$id)->delete('group_themes');

			if ($action) return 0;
			else return 2;
		}
	}

	class AdminTools {
		static function FilterUsers($form){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.view')) return 1;

			$query = 'SELECT u.*, c.classid as classname, c.id as classid, s.name as schoolname
						FROM `users` u
						LEFT JOIN (`class` c, `school` s, `class_members` cm)
						ON (cm.userid = u.id && cm.classid = c.id && c.school = s.id)
						WHERE ';

			$whereIsUsed = false;
			foreach ($form as $key => $value){
				if (empty($value)) continue;

				if (substr($key,0,1) == 'u') $query .= str_replace('_','.',$key)." REGEXP '^{$value}$' && ";
				else {
					$cn = substr($key,0,1);
					if (is_numeric($value)) $cn .= '.id';
					else $cn .= '.'.($cn == 'c' ? 'classid' : 'name');
					$query .= "{$cn} REGEXP '^{$value}$' && ";
				}
				$whereIsUsed = true;
			}

			if ($whereIsUsed)
				$query = substr($query,0,strlen($query)-4);
			else
				$query = substr($query,0,strlen($query)-6);

			$data = $db->rawQuery($query);
			$return = array();

			foreach ($data as $array){
				if (!isset($return[$array['id']]))
					$return[$array['id']] = $array;
				else {
					if (is_array($return[$array['id']]['classid']))
						$return[$array['id']]['classid'][] = $array['classid'];
					else
						$return[$array['id']]['classid'] = array($return[$array['id']]['classid'],$array['classid']);

					if (is_array($return[$array['id']]['classname']))
						$return[$array['id']]['classname'][] = $array['classname'];
					else
						$return[$array['id']]['classname'] = array($return[$array['id']]['classname'],$array['classname']);

					if (is_array($return[$array['id']]['schoolname']))
						$return[$array['id']]['schoolname'][] = $array['schoolname'];
					else
						$return[$array['id']]['schoolname'] = array($return[$array['id']]['schoolname'],$array['schoolname']);
				}
			}

			return $return;
		}

		static function UserLookup($id){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.view')) return 1;
		}
	}

	class TeacherTools {
		static function Add($datas){
			global $db,$user;

			# Jog. ellenörzése
			if(System::PermCheck('teachers.add')) return 1;

			# Alapadatok feldolgozása
			if (!isset($datas['name']) || !isset($datas['short'])) return 2;
			$basedata = array(
				'name' => $datas['name'],
				'short' => $datas['short'],
			);
			foreach ($basedata as $key => $value){
				switch ($key){
					case 'short':
						$type = 'shortn_teacher';
					break;
					default:
						$type = $key;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}
			$basedata['classid'] = $user['class'][0];
			$action = $db->insert('teachers',$basedata);
			if (!is_numeric($action)) return 3;

			# Tantárgyak hozzáadása
			if (!isset($datas['lessons']) || empty($datas['lessons'])) return [$action];
			foreach ($datas['lessons'] as $sublesson){
				$action_l = $db->insert('lessons',array(
					'classid' => $user['class'][0],
					'name' => $sublesson['name'],
					'teacherid' => $action,
					'color' => $sublesson['color'],
				));
				if (!$action_l) return 4;
			}

			return [$action];
		}

		static function Edit($data){
			global $db;

			# Formátum ellenörzése
			if (!System::ValuesExists($data,['short','name','id'])) return 2;
			foreach ($data as $key => $value){
				switch ($key){
					case 'short':
						$type = 'shortn_teacher';
					break;
					case 'id':
						$type = 'numeric';
					break;
					case 'name':
						$type = 'name';
					break;
					default:
						return 2;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			# Jog. ellenörzése
			if (System::PermCheck('teachers.edit',$data['id'])) return 1;

			# Adatbázisba írás
			$action = $db->where('id',$data['id'])->update('teachers',$data);

			if ($action) return 0;
			else return 3;
		}

		static function Delete($id){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('teachers.delete',$id)) return 1;

			$action = $db->where('id',$id)->delete('teachers');

			$data = $db->rawQuery('SELECT id
									FROM `lessons`
									WHERE teacherid = ?',array($id));

			foreach ($data as $array)
				LessonTools::Delete($array['id']);

			if ($action) return 0;
			else return 2;
		}
	}

	class HomeworkTools {
		static function GetWeekDates($week, $year = null) {
			$year = empty($year) ? date('Y') : $year;
			$dto = new DateTime();
			$ret['start'] = $dto->setISODate($year, $week)->format('Y-m-d');
			$ret['end'] = $dto->modify('+6 days')->format('Y-m-d');
			return $ret;
		}

		static $RomanMonths = array(null,'I','II','II','IV','V','VI','VII','VIII','IX','X','XI','XII');
		static function FormatMonthDay($time){
			return HomeworkTools::$RomanMonths[(int)date('m', $time)].'.'.date('d', $time);
		}

		static function Add($data){
			global $db, $user;

			# Jog. ellenörzése
			if(System::PermCheck('homeworks.add')) return 0x1;

			# Formátum ellenörzése
			if (!System::ValuesExists($data,['lesson','text','week'])) return 0x2;
			foreach ($data as $key => $value){
				switch ($key){
					case 'lesson':
						$type = 'numeric';
					break;
					case 'week':
						$type = 'numeric';
					break;
					case 'text':
						continue 2;

					case 'fileTitle':
						$type = 'text';
					break;
					case 'fileDesc':
						$type = 'text';
					break;

					default:
						return 0x2;
					break;
				}
				if (System::InputCheck($value,$type)) return 0x2;
			}

			$parser = new JBBCode\Parser();
			$parser->addCodeDefinitionSet(new JBBCode\BlueSkyCodeDefSet());

			$parser->parse(nl2br($data['text']));

			$data['text'] = strip_tags($parser->getAsHtml(),System::$AllowedHTMLTags);

			$dateFromUI = strtotime(self::GetWeekDates($data['week'])['start']);

			$dbdata = $db->rawQuery('SELECT tt.week as week
										FROM timetable tt
										LEFT JOIN (teachers t, lessons l)
										ON (tt.lessonid = l.id && l.teacherid = t.id)
										WHERE tt.classid = ? && tt.id = ? && t.name IS NOT NULL && l.name IS NOT NULL',
							array($user['class'][0],$data['lesson']));

			if (empty($dbdata)) return 0x3;
			else $dbdata = $dbdata[0];

			if (Timetable::GetActualWeek(false,$dateFromUI) != strtoupper($dbdata['week'])) return 0x4;

			// Mellékelt fájl feltöltése
			$uploadStatus = 0;
			if (!empty($_FILES)){
				$file = reset($_FILES);
				$uploadStatus = FileTools::UploadFile($file);

				if (is_array($uploadStatus)){
					$lessonId = $db->rawQuery('SELECT `lessonid`
												FROM `timetable`
												WHERE `id` = ?',array($data['lesson']))[0]['lessonid'];

					$action = $db->insert('files',array(
						'name' => isset($data['fileTitle']) ? $data['fileTitle'] : 'Házi feladathoz feltöltött fájl',
						'description' => isset($data['fileDesc']) ? $data['fileDesc'] : 'Házi feladathoz feltöltött fájl',
						'lessonid' => $lessonId,
						'classid' => $user['class'][0],
						'uploader' => $user['id'],
						'size' => $file['size'],
						'filename' => $file['name'],
						'tempname' => $uploadStatus[0],
					));
					$uploadStatus = 0;
					unset($data['fileTitle']);
					unset($data['fileDesc']);
				}
			}

			$db->insert('homeworks',array_merge($data,array('author' => $user['id'], 'classid' => $user['class'][0])));

			return $uploadStatus;
		}

		static function Delete($id){
			global $db,$user;

			# Form. ellenörzése
			if (System::InputCheck($id,'numeric')) return 2;

			# Jog. ellenörzése
			if (System::PermCheck('homeworks.delete',$id)) return 1;

			# Függőségek feloldása (hw_markdone)
			$data = $db->where('classid',$user['class'][0])->where('homework',$id)->get('hw_markdone');
			foreach ($data as $array)
				self::UndoMarkedDone($array['id']);

			$action = $db->where('id',$id)->delete('homeworks');

			if ($action) return 0;
			else return 3;
		}

		static function GetHomeworks($numberOfHomework = 3, $onlyListActive = false){
			global $db, $user;

			$grpmember = $db->rawQuery('SELECT `groupid`
							FROM `group_members`
							WHERE `classid` = ? && `userid` = ?',array($user['class'][0],$user['id']));

			$addon = [$user['id'],$user['class'][0]];
			$ids = array(0);
			foreach ($grpmember as $array)
				$ids[] = $array['groupid'];

			$weekNum = Timetable::GetWeekNum();
			$dayInWeek = Timetable::GetDayNumber();

			$active = $onlyListActive ? '&& (SELECT `id` FROM `hw_markdone` WHERE `homework` = hw.id && `userid` = ?) IS NULL' : '';

			$query = "SELECT hw.id, hw.text as `homework`, hw.week, tt.day, tt.lesson as `lesson_th`, l.name as `lesson`,
							(SELECT `id` FROM `hw_markdone` WHERE `homework` = hw.id && `userid` = ?) as markedDone
						FROM `timetable` tt
						LEFT JOIN (`homeworks` hw, `lessons` l)
						ON (hw.lesson = tt.id && l.id = tt.lessonid)
						WHERE tt.classid = ? && tt.groupid IN (".implode(',', $ids).") && ((hw.week = ? && tt.day > ?) || hw.week > ?) && hw.text IS NOT NULL {$active}
						ORDER BY hw.week, tt.day, tt.lesson";

			/*
			 *  $a = isset($_GET['a']) ? $_GET['a'] : '';
			 * $a = $_GET['a'] ?? '';
			 * */

			$minute = (int)date('i');
			$hour = (int)date('H');
			if (!($hour >= 8 && $minute >= 0)){
				if ($dayInWeek == 1){
					$_dayInWeek = 7;
					$_weekNum = $weekNum-1;
				}
				else {
					$_dayInWeek = $dayInWeek-1;
					$_weekNum = $weekNum;
				}
			}
			else {
				$_dayInWeek = $dayInWeek;
				$_weekNum = $weekNum;
			}

			$activeArray = $onlyListActive ? array($user['id']) : array();
			$timetable = $db->rawQuery($query,array_merge($addon,array($_weekNum, $_dayInWeek, $_weekNum),$activeArray));
			
			$homeWorks = [];

			$i = 0;
			while (true){
				if (empty($timetable[$i])) break;
				else $array = $timetable[$i];

				if ($weekNum == $array['week'])
					$hwTime = strtotime('+ '.($array['day'] - $dayInWeek).' days');

				else {
					$hwTime = strtotime('- '.($dayInWeek - 1).' days');
					$hwTime = strtotime('+ '.($array['week'] - $weekNum).' weeks', $hwTime);
					$hwTime = strtotime('+ '.($array['day'] - 1).' days', $hwTime);
				}

				$array['date'] = self::FormatMonthDay($hwTime);
				$array['dayString'] = System::$Days[Timetable::GetDayNumber($hwTime)];

				$homeWorks[$array['date']][] = $array;

				$i++;
			}

			array_splice($homeWorks,$numberOfHomework);
			return $homeWorks;
		}

		static function CheckMarkedDone($id, $canExist = false){
			global $db, $user;

			# Formátum ellenörzése
			if (System::InputCheck($id,'numeric')) return 1;

			# Létezik-e a H.Feladat?
			$data = $db->where('id',$id)->has('homeworks');
			if (!$data) return 3;

			# Késznek van-e már jelölve?
			$exists = $db
				->where('classid', $user['class'][0])
				->where('userid', $user['id'])
				->where('homework', $id)
				->has('hw_markdone');

			if ($canExist)
				$exists = !$exists;

			return $exists ? 2 : 0;
		}

		const CAN_EXIST = true;
		static function MakeMarkedDone($id){
			global $db, $user;

			# Ellenőrzés
			$check = self::CheckMarkedDone($id);
			if ($check != 0) return 2;

			# Adatbázisba írás
			$action = $db->insert('hw_markdone',array(
				'userid' => $user['id'],
				'homework' => $id,
				'classid' => $user['class'][0],
			));

			return $action ? 0 : 4;
		}

		static function UndoMarkedDone($id){
			global $db, $user;

			# Ellenőrzés
			$check = self::CheckMarkedDone($id, self::CAN_EXIST);
			if ($check != 0) return 2;

			# Adatbázisba írás
			$action = $db->where('homework',$id)->where('userid',$user['id'])->delete('hw_markdone');

			return $action ? 0 : 4;
		}

		static function RenderHomeworks($numberOfHomework = 3, $onlyListActive = false){
			$homeWorks = HomeworkTools::GetHomeworks($numberOfHomework,$onlyListActive);
?>

<?php       if (empty($homeWorks)) print "<p>Nincs megjelenítendő házi feladat! A kezdéshez adjon hozzá egyet, vagy váltson nézetet!</p>"; ?>

			<table class='homeworks'>
		        <tbody>
		            <tr>
<?php
					     foreach(array_keys($homeWorks) as $value)
					        print "<td><b>{$homeWorks[$value][0]['dayString']}</b> ({$value})</td>";
?>
		            </tr>
		            <tr>
<?php
						foreach(array_keys($homeWorks) as $value){
							print '<td>';
							foreach($homeWorks[$value] as $array){ ?>
						        <div class='hw'>
						            <span class='lesson-name'><?=$array['lesson']?></span><span class='lesson-number'><?=$array['lesson_th']?>. óra</span>
						            <div class='hw-text'><?=$array['homework']?></div>
<?php	    if (empty($array['markedDone'])){ ?>
				<a class="typcn typcn-tick js_makeMarkedDone" title='Késznek jelölés' href='#<?=$array['id']?>'></a>
<?php       }
			else { ?>
				<a class="typcn typcn-times js_undoMarkedDone" title='Késznek jelölés visszavonása' href='#<?=$array['id']?>'></a>
<?php       }
			if (!System::PermCheck('homeworks.delete')){ ?>
							            <a class="typcn typcn-info-large js_more_info" title='További információk' href='#<?=$array['id']?>'></a>
							            <a class="typcn typcn-trash js_delete" title='Bejegyzés törlése' href='#<?=$array['id']?>'></a>
<?php       } ?>
						          </div>
<?php				        }
							print '</td>';
						}
?>
		            </tr>
		        </tbody>
		    </table>
<?php       if (!System::PermCheck('homeworks.add')){ ?>
			    <a class='typcn typcn-plus btn js_add_hw' href='/homeworks/new'>Új házi feladat hozzáadása</a>
<?php       }
	        if ($onlyListActive)
				print "<a class='typcn typcn-tick btn js_add_hw js_showMarkedDone' href='#'>Elrejtett házi feladatok megjelenítése</a>";
	        else
	            print "<a class='typcn typcn-times btn js_add_hw js_hideMarkedDone' href='#'>Visszatérés az eredeti nézethez</a>";
	    }

	    static function RenderHomeworksMainpage(){
	        $homeWorks = HomeworkTools::GetHomeworks(1,true);

	        if (empty($homeWorks))
	            print "<h3>Elkészítésre váró házi feladatok</h3>";
	        else {
	            $day = array_keys($homeWorks)[0];
	            if ((int)substr($day,0,2) == 1 && (int)date('m') == 12) $year = (int)date('y') + 1;
	            else $year = (int)date('y');

				$date = explode('.',$day);

				$date[0] = array_search($date[0],HomeworkTools::$RomanMonths);
				$date[0] = strlen($date[0]) == 1 ? '0'.$date[0] : $date[0];
				$date = $year.'-'.implode('-',$date);

	            $time = strtotime($date);

	            print "<h3>Házi feladatok ".System::Nevelo(System::$Days[Timetable::GetDayNumber($time)])."i napra ({$day})</h3>";
	        }
	        ?>

			<table class='homeworks'>
				<tr>
<?php
					if (!empty($homeWorks)){
						$day = array_keys($homeWorks)[0];

						print "<td>";

						foreach($homeWorks[$day] as $key => $array){
							if ($key % 2 == 1) continue; ?>
					        <div class='hw'>
					            <span class='lesson-name'><?=$array['lesson']?></span><span class='lesson-number'><?=$array['lesson_th']?>. óra</span>
					            <div class='hw-text'><?=$array['homework']?></div>

								<a class="typcn typcn-tick js_makeMarkedDone" title='Késznek jelölés' href='#<?=$array['id']?>'></a>
					        </div>
<?php   	            }

						print "</td><td>";

						foreach($homeWorks[$day] as $key => $array){
							if ($key % 2 == 0) continue; ?>
					        <div class='hw'>
					            <span class='lesson-name'><?=$array['lesson']?></span><span class='lesson-number'><?=$array['lesson_th']?>. óra</span>
					            <div class='hw-text'><?=$array['homework']?></div>

					            <a class="typcn typcn-tick js_makeMarkedDone" title='Késznek jelölés' href='#<?=$array['id']?>'></a>
					        </div>
<?php               	}

						print "</td>"; ?>
				<tr>
			</table>
<?php               }

					else print "<p>Nincs megjeleníthető házi feladat.</p>";
		}
	}

	class EventTools {
		static function GetEvents($start, $end){
			global $db, $user;

			$data = $db->rawQuery('SELECT *
									FROM `events`
									WHERE `classid` = ?',

									array($user['class'][0]));

			$output = [];
			foreach ($data as $event){
				if (!(strtotime('12 am',strtotime($start)) < strtotime('12 am',strtotime($event['end']))
					&& strtotime('12 am',strtotime($event['start'])) < strtotime('12 am',strtotime($end))))
						continue;
				
				$output[] = array(
					'id' => $event['id'],
					'title' => $event['title'],
					'start' => $event['start'],
					'end' => $event['end'],
					'allDay' => (bool)$event['isallday'],
				);
			}

			return $output;
		}

		static function ParseDates($start,$end){
			$start = strtotime(preg_replace('/^(\d{4})\.(\d{2})\.(\d{2})\.? (\d{2})\:(\d{2})(\:(?:\d{2}))?$/','$1-$2-$3 $4:$5$6',trim($start)));
			if ($start === false) return false;

			$end = strtotime(preg_replace('/^(\d{4})\.(\d{2})\.(\d{2})\.? (\d{2})\:(\d{2})(\:(?:\d{2}))?$/','$1-$2-$3 $4:$5$6',trim($end)));
			if ($end === false) return false;

			return [$start,$end];
		}

		static function Add($data){
			global $db, $user;

			# Jog. ellenörzése
			if(System::PermCheck('events.add')) return 1;

			# Formátum ellenörzése
			if (!System::ValuesExists($data,['title','description','interval'])) return 2;
			foreach ($data as $key => $value){
				switch ($key){
					case 'isFullDay':
						$type = 'numeric';
					break;
					case 'title':
						$type = 'text';
					break;
					case 'description':
						$type = 'text';
					break;
					default:
						continue 2;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			# Dátum értelmezése
			$range = trim($data['interval']);
			$rangeParts = explode('~',$range);
			if (count($rangeParts) != 2) return 3;

			$dates = self::ParseDates($rangeParts[0],$rangeParts[1]);
			if (!is_array($dates)) return 4;

			$action = $db->insert('events',array(
				'classid' => $user['class'][0],
				'start' => date('c',$dates[0]),
				'end' => date('c',$dates[1]),
				'title' => $data['title'],
				'description' => $data['description'],
				'isallday' => isset($data['isFullDay']) ? true : false,
			));

			if (!is_int($action)) return 5;
			else return 0;
		}

		static function GetEventInfos($id){
			global $db,$user;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('events');
			if (empty($data)) return 1;

			return array(
				'Esemény címe' => $data['title'],
				'Esemény kezdete' => date(!$data['isallday'] ? 'Y.m.d. H:i' : 'Y.m.d.',strtotime($data['start'])),
				'Esemény vége' => date(!$data['isallday'] ? 'Y.m.d. H:i' : 'Y.m.d.',strtotime($data['end'])),
				'Egész napos?' => $data['isallday'] ? 'igen' : 'nem',
				'Esemény leírása' => $data['description'],
			);
		}

		static function Edit($data){
			global $db, $user;

			# Értékek ellenörzése
			if (!System::ValuesExists($data,['title','description','interval'])) return 2;

			# Jog. ellenörzése
			if(System::PermCheck('events.edit',$data['id'])) return 1;

			# Formátum ellenörzése
			foreach ($data as $key => $value){
				switch ($key){
					case 'isFullDay':
						$type = 'numeric';
					break;
					case 'title':
						$type = 'text';
					break;
					case 'description':
						$type = 'text';
					break;
					default:
						continue 2;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			# Dátum értelmezése
			$range = trim($data['interval']);
			$rangeParts = explode('~',$range);
			if (count($rangeParts) != 2) return 3;

			$dates = self::ParseDates($rangeParts[0],$rangeParts[1]);
			if (!is_array($dates)) return 4;

			$action = $db->where('id',$data['id'])->update('events',array(
				'start' => date('c',$dates[0]),
				'end' => date('c',$dates[1]),
				'title' => $data['title'],
				'description' => $data['description'],
				'isallday' => isset($data['isFullDay']) ? true : false,
			));

			if ($action) return 0;
			else return 5;
		}

		static function Delete($id){
			global $db;

			# Jog. ellenörzése
			if(System::PermCheck('events.delete',$id)) return 1;

			$action = $db->where('id',$id)->delete('events');
			return $action ? 0 : 2;
		}

		// Események listázása a főoldalon
		static function ListEvents($Events = null){
			if (empty($Events)){
				global $db, $user;
				$Events = $db->where('start > NOW()')->where('classid',$user['class'][0])->orderBy('start', 'ASC')->get('events', 10);
			}
			if (empty($Events)) return;

			$HTML = '<h3>Fontos dátumok</h3><ul id="events">';
			foreach ($Events as $i => $ev){
				$starttime = strtotime($ev['start']);
				$start = array(System::$ShortMonths[intval(date('n', $starttime))], date('j', $starttime));
				$endtime = strtotime($ev['end']);
				$end = array(System::$ShortMonths[intval(date('n', $endtime))], date('j', $endtime));

				$sameMonthDay = $start[0] == $end[0] && $start[1] == $end[1];
				$time = $ev['isallday'] ? '' : date('H:i', $starttime).(!$sameMonthDay?'-tól':'').' ';
				$append = '';
				if (!$sameMonthDay)
					$append .= HomeworkTools::FormatMonthDay($endtime);
				if (!$ev['isallday'])
					$append .= ' '.date('H:i',$endtime).'-ig';
				else if (!$sameMonthDay) $append .= '-ig';
				if (!empty($append))
					$time .= "$append";
				if ($ev['isallday']){
					$time .= ', egész nap';
					$time = preg_replace('/^, eg/','Eg',$time);
				}

				$HTML .= "<li><div class='calendar'><span class='top'>{$start[0]}</span><span class='bottom'>{$start[1]}</span></div>".
					"<div class='meta'><span class='title'>{$ev['title']}</span><span class='time'>$time</span></div></li>";
			}
			echo $HTML.'</ul>';
		}
	}

	class Timetable {
		static function GetWeekNum(){
			$dateObj = new DateTime();
			return $dateObj->format("W");
		}

		static function GetNumberOfWeeks(){
			global $db,$user;

			$data = $db
				->where('classid', $user['class'][0])
				->where('week', 'b')
				->has('timetable');

			return empty($data) ? 1 : 2;
		}

		static function GetEdgesOfWeek($date){
			$ts = strtotime($date);
		    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);

		    return array(date('Y-m-d', $start),date('Y-m-d', strtotime('next saturday', $start)));
		}

		static function GetActualWeek($sorting = false, $timestamp = null){
			global $ENV,$db,$user;

			if (empty($timestamp))
				$timestamp = time();

			$data = $db->rawQuery('SELECT *
									FROM `timetable`
									WHERE `classid` = ? && `week` = ?',array($user['class'][0],'b'));
			if (empty($data))
				return $sorting ? 'ASC' : 'A';

			$weekNum = date('W',$timestamp);

			$tsyear = date('Y',$timestamp);

			$jan1 = strtotime("1 jan $tsyear");
			$aug31 = strtotime("1 sept $tsyear");

			$start = strtotime('+7 days',strtotime('this week', $jan1));
			$end = strtotime('+7 days',strtotime('this week', $aug31));

			$yearPassed = $timestamp >= $start && $timestamp < $end;

			if (!$sorting){
				if ($ENV['class']['pairweek'] === 'A'){
					if ($weekNum % 2 == 0)
						return !$yearPassed ? 'A' : 'B';
					else
						return !$yearPassed ? 'B' : 'A';
				}
				else {
					if ($weekNum % 2 == 0)
						return !$yearPassed ? 'B' : 'A';
					else
						return !$yearPassed ? 'A' : 'B';
				}
			}
			else {
				if ($ENV['class']['pairweek'] === 'A'){
					if ($weekNum % 2 == 0)
						return !$yearPassed ? 'ASC' : 'DESC';
					else
						return !$yearPassed ? 'DESC' : 'ASC';
				}
				else {
					if ($ENV['class']['pairweek'] === 'A'){
						if ($weekNum % 2 == 0)
							return !$yearPassed ? 'DESC' : 'ASC';
						else
							return !$yearPassed ? 'ASC' : 'DESC';
					}
				}
			}
		}

		static function GetDayNumber($timestamp = null) {
			$ts = date('w' ,empty($timestamp) ? time() : $timestamp);
			return $ts == 0 ? 7 : $ts;
		}

		// Órarend módosítások feldolgozása
		static function AddEntrys($toAdd,$week){
			global $db,$user;

			$reqItems = ['day','lesson','tantargy','group'];
			foreach ($toAdd as $sub){
				if (empty($sub)) continue;
				foreach ($reqItems as $item)
					if (!isset($sub[$item])) return 2;

				foreach ($sub as $key => $value)
					if (System::InputCheck($value,'numeric')) return 3;

				$Entry = array(
					'classid' => $user['class'][0],
					'week' => $week,
					'day' => $sub['day'],
					'lesson' => $sub['lesson'],
					'lessonid' => $sub['tantargy'],
					'groupid' => $sub['group'],
				);

				$action = $db->insert('timetable',$Entry);
				if (!$action) return 4;
			}
		}

		static function DeleteEntrys($toDelete){
			global $db,$user;

			foreach ($toDelete as $sub){
				if (!isset($sub['id'])) return 5;
				$id = $sub['id'];
				if (System::InputCheck($id,'numeric')) return 6;

				$action = $db->where('id',$id)->delete('timetable',$id);

				# Órarend-entryhez tartozó HW-k törlése
				$data = $db->where('classid',$user['class'][0])->where('lesson',$id)->get('homeworks');
				foreach ($data as $array)
					HomeworkTools::Delete($array['id']);

				if (!$action) return 7;
			}
		}

		static function ProgressTable($data){
			# Jog. ellenörzése
			if (System::PermCheck('timetables.edit')) return 2;

			# Hét ellenörzése
			$week = strtolower($data['week']);
			if (!in_array($week,['a','b'])) return 1;

			# Bejegyzések hozzáadása
			if(isset($data['add']))
				self::AddEntrys($data['add'],$week);

			# Bejegyzések törlése
			if(isset($data['delete']))
				self::DeleteEntrys($data['delete']);

			return 0;
		}

		/*                                      */
		/*  ÓRAREND KIRENDERELÉS ÉS LETÖLTÉS    */
		/*                                      */

		// 'Új tantárgy hozzáadó' űrlap
		const ADD_FORM_HTML = <<<STRING
<form>
	<label>
		<span>Csoport:</span>
		<select class="groups" name="groups"></select>
	</label>
	<label>
		<span>Tantárgy:</span>
		<select class="lessons" name="lessons"></select>
	</label>
	<button class="btn addtt">Hozzáadás</button>
</form>
STRING;

		static $TT_Types = array(
			'a' => "'A'",
			'b' => "'B'",
		);

		static function MoveNextBack($move,$dispDays,$showAllGroups = true){
			$numberOfDays = count($dispDays);

			if ($move == 'next') $fromDate = $dispDays[count($dispDays)-1];
			else $fromDate = strtotime("- {$numberOfDays} days",$dispDays[0]);

			$dates = [];

			while(count(array_diff($dates,$dispDays)) != count($dispDays)){
				$day = Timetable::GetDayNumber($fromDate);
				$week = Timetable::GetActualWeek(false,$fromDate);

				$TT = Timetable::GetHWTimeTable(date('W',$fromDate),$day,$showAllGroups);

				$dates = $TT['opt'];
				unset($TT['opt']);

				sort($dates,SORT_NUMERIC);
				$dates = array_splice($dates,0,$numberOfDays);

				$fromDate = strtotime(($move == 'next' ? '+' : '-').' 1 days',$fromDate);
			}

			Timetable::Render(null, $TT, $dates);
			$fDate = strtotime('12 am',$dates[0]);
			$now = strtotime('12 am');

			if (strtotime('- 1 days',$fDate) == $now) $lockBack = true;
			else if (Timetable::GetDayNumber() == 6 && strtotime('+ 2 days',$now) == $fDate) $lockBack = true;
			else if (Timetable::GetDayNumber() == 7 && strtotime('+ 1 days',$now) == $fDate) $lockBack = true;
			else $lockBack = false;

			?>
			<span class='dispDays'><?=json_encode($dates)?></span>
			<span class='lockBack'><?=json_encode($lockBack)?></span>
<?php	}

		static function MoveDate($date,$numberOfDays = 3,$showAllGroups = true){
			$date = strtotime('- 1 days',strtotime($date));

			$week = date('W',$date);
			$day = Timetable::GetDayNumber($date);

			$TT = Timetable::GetHWTimeTable($week,$day,$showAllGroups);

			$dates = $TT['opt'];
			unset($TT['opt']);

			sort($dates,SORT_NUMERIC);
			$dates = array_splice($dates,0,$numberOfDays);

			Timetable::Render(null, $TT, $dates);

			$fDate = strtotime('12 am',$dates[0]);
			$now = strtotime('12 am');

			if ($fDate == $now) $lockBack = true;
			else if (Timetable::GetDayNumber() == 6 && strtotime('+ 2 days',$now) == $fDate) $lockBack = true;
			else if (Timetable::GetDayNumber() == 7 && strtotime('+ 1 days',$now) == $fDate) $lockBack = true;
			else $lockBack = false;

			?>
			<span class='dispDays'><?=json_encode($dates)?></span>
			<span class='lockBack'><?=json_encode($lockBack)?></span>
<?php	}

		static function SwitchView($fromDate,$allgroup = true){
			$fromDate = strtotime('- 1 days',$fromDate);
			$day = Timetable::GetDayNumber($fromDate);

			$TT = Timetable::GetHWTimeTable(date('W',$fromDate),$day,$allgroup);

			$days = $TT['opt'];
			unset($TT['opt']);

			sort($days,SORT_NUMERIC);
			$days = array_splice($days,0,5);

			Timetable::Render(null, $TT, $days);

			$fDate = strtotime('12 am',$days[0]);
			$now = strtotime('12 am');

			if ($fDate == $now) $lockBack = true;
			else if (Timetable::GetDayNumber() == 6 && strtotime('+ 2 days',$now) == $fDate) $lockBack = true;
			else if (Timetable::GetDayNumber() == 7 && strtotime('+ 1 days',$now) == $fDate) $lockBack = true;
			else $lockBack = false;

			?>
			<span class='dispDays'><?=json_encode($days)?></span>
			<span class='lockBack'><?=json_encode($lockBack)?></span>
<?php	}

		static function GetHWTimeTable($week = null, $lastDay = null, $allgroup = true){
			global $user, $db;

			$addon = array($user['class'][0]);

			if (!empty($week) && !empty($lastDay)){
				$weekday = strtotime('+ '.($week - date('W')).' weeks', strtotime('12 am'));
				if (Timetable::GetDayNumber() < $lastDay) $weekday = strtotime('+ '.($lastDay - Timetable::GetDayNumber()).' days',$weekday);
				else $weekday = strtotime('- '.(Timetable::GetDayNumber() - $lastDay).' days',$weekday);
				$actWeek = strtolower(Timetable::GetActualWeek(false,$weekday));
				$addon = array_merge($addon,[$actWeek, $lastDay, $actWeek == 'a' ? 'b' : 'a']);
				$dayInWeek = $lastDay;
				$hour = $minute = 9;
			}
			else {
				$minute = (int)date('i');
				$hour = (int)date('H');

				$weekday = time();

				$addon = array_merge($addon,[self::GetActualWeek(),
							$hour >= 8 && $minute >= 0 ? self::GetDayNumber() : self::GetDayNumber()-1,
							strtolower(self::GetActualWeek()) == 'a' ? 'b' : 'a']);

				$actWeek = strtolower(Timetable::GetActualWeek());
				$dayInWeek = Timetable::GetDayNumber();
			}

			$dualWeek = Timetable::GetNumberOfWeeks() == 1 ? false : true;

			$userInGroups = $db->rawQuery('SELECT `groupid`
											FROM `group_members`
											WHERE `classid` = ? && `userid` = ?',array($user['class'][0],$user['id']));
			$groups = array(0);
			foreach ($userInGroups as $array)
				$groups[] = $array['groupid'];

			if (!$allgroup)
				$onlyGrp = '&& tt.groupid IN ('.implode(',',$groups).')';
			else
				$onlyGrp = '';

			if ($dualWeek){
				$whereString = "&& ((tt.week = ? && tt.day > ?) || tt.week = ?)";
				$data = $db->rawQuery("SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
							FROM timetable tt
							LEFT JOIN lessons l
							ON (l.id = tt.lessonid && l.classid = tt.classid)
							WHERE tt.classid = ? $whereString $onlyGrp
							ORDER BY tt.week, tt.day, tt.lesson ASC",$addon);
			}
			else {
				$data_onWeek = $db->rawQuery("SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
											FROM timetable tt
											LEFT JOIN lessons l
											ON (l.id = tt.lessonid && l.classid = tt.classid)
											WHERE tt.classid = ? && tt.day > ? $onlyGrp
											ORDER BY tt.day, tt.lesson"
									,array($user['class'][0],$hour >= 8 && $minute >= 0 ? $dayInWeek : $dayInWeek-1));

				$data_nextWeek = $db->rawQuery("SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
											FROM timetable tt
											LEFT JOIN lessons l
											ON (l.id = tt.lessonid && l.classid = tt.classid)
											WHERE tt.classid = ? $onlyGrp
											ORDER BY tt.day, tt.lesson"
									,array($user['class'][0]));

				$data_nW = array();
				foreach ($data_nextWeek as $array){
					$nextD = $hour >= 8 && $minute >= 0;
					$if = $dayInWeek == 1 ? ($nextD ? $dayInWeek : 7) : ($nextD ? $dayInWeek : $dayInWeek - 1);

					if ($array['day'] <= $if)
						$data_nW[] = $array;
				}

				$data = array_merge($data_onWeek,$data_nW);
			}

			$Timetable = array_fill(0,8,array_fill(0,1,array()));

			$days = [];

			//var_dump(date('Y-m-d',$weekday));
			foreach ($data as $class){
				$lesson = $class['lesson']-1;

				if ($actWeek == $class['week']){
					if ($class['day'] <= $dayInWeek)
						if ($dualWeek)
							$date = strtotime('+ '.(14 + $class['day']).' days',$weekday);
						else
							$date = strtotime('+ '.((7 - $dayInWeek) + $class['day']).' days',$weekday);
					else
						$date = strtotime('+ '.($class['day'] - $dayInWeek).' days',$weekday);
				}
				else {
					$date = strtotime('+ '.(7 - $dayInWeek).' days',$weekday);

					$date = strtotime("+ {$class['day']} days", $date);
				};

				if (array_search($date,$days) === false) $days[] = $date;

				if (isset($class['name']))
					$Timetable[$lesson][$date][] = array($class['name'],'',$class['color'],$class['id'],$allgroup ? $class['group_name'] : '',date('W',$date));
			}
			$Timetable['opt'] = $days;

			return $Timetable;
		}

		// Órarend lekérése
		static function GetTimeTable($week, $allgroups = false){
			global $user, $db;

			# Formátum ellenörzése
			if (strpos('ab',$week) === false) trigger_error('Érvénytelen hét');

			# Órarend lekérés előkészítése
			$query = "SELECT
				tt.*,
				l.name,	l.color,
				@teacher := l.teacherid,
				(SELECT short FROM teachers t WHERE t.id = @teacher) as teacher
			FROM timetable tt
			LEFT JOIN lessons l ON (l.id = tt.lessonid && l.classid = ?)
			WHERE tt.classid = ? && tt.week = ?";

			# Órarend lekérés segédtömb elékészítése
			$data = array($user['class'][0],$user['class'][0],$week);

			$groupdata = $db->rawQuery(
				"SELECT DISTINCT g.id
				FROM group_members gm
				LEFT JOIN groups g ON gm.groupid = g.name
				WHERE gm.userid = ? && gm.classid = ?", array($user['id'], $user['class'][0]));

			# Ha minden csoport adatait szeretnénk lekérni...
			if ($allgroups == false){
	            $query .= ' && groupid = ?';
	            $data[] = '0';
				foreach ($groupdata as $subgd){
					$query .= " || groupid = ?";
					$data[] = $subgd['id'];
				}
			}

			$groups = $db->rawQuery('SELECT `id`, `name` FROM `groups` WHERE classid = ?',array($user['class'][0]));
			$grp_list = array();
			foreach ($groups as $subg)
				$grp_list[$subg['id']] = $subg['name'];
			$grp_list['0'] = '';

			# Plusz adatok hozzáadása a lekéréshez
			$query .= ' ORDER BY tt.week ASC, tt.day ASC, tt.lesson ASC';

			# Lekérés végrehajtása
			$data = $db->rawQuery($query,$data);

			# Tömb feltötése üres adatokkal
			$Timetable = array_fill(0,8,array_fill(0,1,array()));

			# Órarend adatok rendezése
			foreach ($data as $class){
				$lesson = $class['lesson']-1;
				$weekday = $class['day']-1;
				if (isset($class['name'])){
					if (!isset($grp_list[$class['groupid']])) continue;
					$Timetable[$lesson][$weekday][] = array($class['name'],$class['teacher'],$class['color'],$class['id'],$grp_list[$class['groupid']]);
				}
			}

			return $Timetable;
		}

		const MANAGE = true;
		//Órarend kirenderelése
		static function Render($week,$Timetable,$weekdays = null){
			if (empty($weekdays) && empty($week)) return;
			if (!empty($weekdays)){
				// Hetek kirenderelésének előkészítése
				$weeks = [];
				foreach ($weekdays as $day){
					$wNum = (int)date('W',$day);
					if (array_search($wNum,array_keys($weeks)) === false) $weeks[$wNum] = array(1, Timetable::GetActualWeek(false,$day));
					else $weeks[$wNum][0]++;
				}
				ksort($weeks);
			} ?>

			<table class='timet'>
				<thead>
<?php				if (!empty($weeks)) {
						print "<tr><th>H</th>";
						foreach ($weeks as $key => $array){
							print "<th colspan='$array[0]'>{$array[1]}. hét ({$key}. hét)</th>";
						}
						print "</tr>";
					} ?>
					<tr>
						<th class="week"><?= empty($week) ? 'D' : strtoupper($week) ?></th>
<?php                   if (empty($weekdays)) { ?>
							<th class="weekday">Hétfő</th>
							<th class="weekday">Kedd</th>
							<th class="weekday">Szerda</th>
							<th class="weekday">Csütörtök</th>
							<th class="weekday">Péntek</th>
<?php                   }
						else
							foreach ($weekdays as $day)
								print "<th class='weekday'>".HomeworkTools::FormatMonthDay($day).' '.System::$Days[Timetable::GetDayNumber($day)]."</th>"; ?>
					</tr>
				</thead>

				<tbody>
<?php       if (empty($weekdays)){
				for ($lesson = 0; $lesson <= 8; $lesson++){
					if (empty($Timetable[$lesson])) continue; ?>
					<tr class="lesson-field">
						<th><?=$lesson+1?></th>
<?php                   for ($weekday = 0; $weekday < (empty($weekdays) ? 5 : count($weekdays)); $weekday++){
							$class = isset($Timetable[$lesson][$weekday]) ? $Timetable[$lesson][$weekday] : null;
							self::_RenderClass($class);
						} ?>
					</tr>
<?php           }
			}
			else {
				$days = array_keys($weekdays);
				for ($lesson = 0; $lesson <= 8; $lesson++){
					if (empty($Timetable[$lesson])) continue; ?>
					<tr class="lesson-field">
						<th><?=$lesson+1?></th>
<?php                   for ($day = 0; $day < count($days); $day++){
							//var_dump($days[$day]);
							$class = isset($Timetable[$lesson][$weekdays[$day]]) ? $Timetable[$lesson][$weekdays[$day]] : null;
							self::_RenderClass($class);
						} ?>
					</tr>
<?php           }
			} ?>
<?php
		print "</tbody></table>";
		if (!empty($week) && !System::PermCheck('timetables.edit')) print "<button class='btn sendbtn'>Módosítások mentése</button>";
		}

		// Órarend cella kirenderelő
		static private function _RenderClass($class){
			if (isset($class) && (!is_array($class) || !empty($class))){
				$echo = '<td>';
				if (!is_array($class)) $class = array($class);
				foreach($class as $c){
					if (empty($c[4])) $grpstr = '';
					else $grpstr = ' ('.$c[4].')';

					$week = isset($c[5]) ? "data-week='".$c[5]."'" : '';

					$echo .= "<span class='lesson' $week style='background: {$c[2]}'>{$c[0]}{$grpstr}<span class='del typcn typcn-times' data-id='$c[3]'></span></span>";
				}
			}
			else $echo = '<td class="empty">';
			$echo .= '<span class="add typcn typcn-plus"></span>';
			echo "$echo</td>";
		}
	}
