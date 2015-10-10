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

			//return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
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

		static function GetDetails($id){
			global $db,$user,$ENV;
			$logclass = new Logging();

			# Bejegyzés ellenörzése
			if (!preg_match('/^\d+$/',$id)) return 1;
			$dataid = $db->where('id',$id)->getOne('log_central');
			$userdataid = $db->where('id',$dataid['user'])->getOne('users');

			# Jogosultság ellenörzése
			switch (USRGRP){
				case 'admin':
					if ($dataid['user'] === 0) return 2;
					if ($userdataid['classid'] != $user['classid']) return 3;
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
			'realname' => 'name',
			'verpasswd' => 'password',
			'newpassword' => 'password',
			'vernewpasswd' => 'password',
			'short' => 'shortn_teacher',
		);

		static $Inputs = array(
			'users' => ['username','realname','email','password','verpasswd','newpassword','vernewpasswd'],
			'teachers' => ['name','short'],
			'invitation' => ['username','realname','email','password','verpasswd'],
		);

		static $Days = array(
			1 => 'Hétfő',
			2 => 'Kedd',
			3 => 'Szerda',
			4 => 'Csütörtök',
			5 => 'Péntek',
			6 => 'Szombat',
			7 => 'Vasárnap',
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
					#$preg = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/';
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
					$preg = '/^[0-9A-ZÁÉÍÓÖŐÚÜŰa-záéíóöőúüű. ]{2,}$/';
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

		// Aktív-e a felh. az öröklődő csop. alapján?
		static function UserActParent($userarray){
			global $db,$ENV;

			# Osztály ellenörzése
			$ENV['class'] = $db->where('id',$userarray['classid'])->getOne('class');
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

			if (self::UserActParent($user)) return 'guest';

			return $user['priv'];
		}

		// Bejelentkezés
		static private function _login($username,$password){
			global $db;

			# Formátum ellenörzése
			if (self::InputCheck($username,'username')) return 1;

			$isadmin = 'users';

			$data = $db->where('username',$username)->getOne('users');
			if (empty($data)){
				$data = $db->where('username',$username)->getOne('admins');
				if (empty($data)) return 2;
				$isadmin = 'admins';
			}

			if (!Password::Ellenorzes($password,$data['password'])) return 2;
			if (!$data['active']) return 4;

			if ($isadmin == 'users')
				if (self::UserActParent($data)) return 5;

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

		// Jogosultság ellenörző
		static function PermCheck($minjog, $maxjog = null){
			global $PERM;

			if (empty($maxjog))
				return USRPERM < $PERM[$minjog];

			return USRPERM < $PERM[$minjog] || USRPERM > $PERM[$maxjog];
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
			echo json_encode($r);
			exit;
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

		// Osztályadmin. szerk. jog. ell.
		static function ClassPermCheck($id,$class){
			global $db,$user;

			if (!System::PermCheck('sysadmin')) return false;

			$usrdata = $db->where('id',$id)->getOne($class);
			if (empty($usrdata)) return true;
			return !($usrdata['classid'] == $user['classid'] && !System::PermCheck('admin'));
		}

		static function WriteAttackLog($data){
			$string = "----------\r\nEntry\r\n---------\r\n";
			foreach($data as $key => $value)
				$string .= "DATA ".$key.": ".$value."\r\n";

			file_put_contents('attact.log',$string);
		}


		// POST-kérés indítása
		static function PostRequest($url, $data, $json = false){
			$options = array(
			    'http' => array(
			        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
			        'method'  => 'POST',
			        'content' => http_build_query($data),
			    ),
			);
			$context  = stream_context_create($options);

			$contents = file_get_contents($url, false, $context);
			return $json ? json_decode($contents, true) : $contents;
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

			if (System::PermCheck('user','admin') && System::UserActParent($user))
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

			$transport = Swift_SmtpTransport::newInstance(MAIL_HOST, MAIL_PORT, 'ssl') //Kapcsolódási objektum létrehozása és csatlakozási adatok a Google Mailhez
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

		/**
		 * "Rögzíti" a jelenlegi lekérés URL-jét
		 * Használatával könnyen változtatható az oldal URL-je
		 *   átirányítás kezdeményezése nélkül.
		 *
		 * @param string $desired_path A kívánt elérési útvonal
		 * @param int $http Az átirányítást végző funkciónak átadandó státusz kód
		 *
		 * @return void
		 */
		static function FixPath($desired_path, $http = 301){
			$query = !empty($_SERVER['QUERY_STRING']) ? preg_replace('~do=[^&]*&data=[^&]*(&|$)~','',$_SERVER['QUERY_STRING']) : '';
			if (!empty($query)) $query = "?$query";
			if ($_SERVER['REQUEST_URI'] !== "$desired_path$query")
				self::Redirect("$desired_path$query", STAY_ALIVE, $http);
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
		const CLIENTID = '183120119367-egq0lq9dg49h3gjooitkv53tgblsob0d.apps.googleusercontent.com';
		const SECRET = 'giQc4gGUK5BvbgtN-DG-hNwQ';
		const GET_AS_JSON = true;
		static $PROVIDERS = ['google'];

		static function Request($url, $token = null, $postdata = null){
			$r = curl_init($url);
			curl_setopt($r, CURLOPT_RETURNTRANSFER, 1);
			if (!empty($token)) curl_setopt($r, CURLOPT_HTTPHEADER, array("Authorization: Bearer $token"));

			if (!empty($postdata)){
				$query = '';
				foreach($postdata as $k => $v) $query .= "$k=$v&";
				rtrim($query, '&');
				curl_setopt($r,CURLOPT_POST, count($postdata));
				curl_setopt($r,CURLOPT_POSTFIELDS, $query);
			}
			$response = curl_exec($r);
			curl_close($r);

		    return json_decode($response, true);
		}

		static function GetAccessToken($code, $url = 'https://custudy.tk/googleauth'){
			$data = self::Request('https://www.googleapis.com/oauth2/v3/token',null,array(
				'code' => $code,
				'client_id' => self::CLIENTID,
				'client_secret' => self::SECRET,
				'redirect_uri' => $url,
				'grant_type' => 'authorization_code',
			));

			if (isset($data['error'])){
				die(header("Location: /?errtype=remote"));
			}

			return $data['access_token'];
		}

		static function DeactAndAct($connid, $type = 'deactivate'){
			global $db, $user;

			if (System::InputCheck($connid,'numeric')) return 1;

			$data = $db->where('id',$connid)->getOne('ext_connections');
			if (empty($data)) return 2;

			if ($data['userid'] != $user['id'] && !System::PermCheck('admin','admin'))
				if (System::ClassPermCheck($data['userid'],'users')) return 3;

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

			if ($data['userid'] != $user['id'] && !System::PermCheck('admin','admin'))
				if (System::ClassPermCheck($data['userid'],'users')) return 3;

			$action = $db->where('id',$connid)->delete('ext_connections');

			return !$action ? 4 : 0;
		}
	}

	class Message {
		static $Messages = array(
			'system' => array(
				'login' => array(
					'errors' => array(
						1 => 'valamelyik megadott adat formátuma hibás',
						2 => 'a felhasználó nem létezik, esetleg hibás a jelszó',
						4 => 'a felhasználó állapota tiltott',
						5 => 'az osztály vagy iskola állapota tiltott',
					),
					'messages' => array(
						1 => 'A bejelentkezés sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				)
			),

			'users' => array(
				'add' => array(
					'errors' => array(
						1 => 'hiányzik egy szükséges adat',
						2 => 'valamelyik megadott adat formátuma hibás',
						3 => 'a megadott jelszavak nem egyeznek',
						4 => 'már foglalt a megadott felhasználónév',
						5 => 'már foglalt a megadott felhasználónév',
						6 => 'létezik felhasználó a megadott e-mail címmel',
						7 => 'nincs jogosultsága a művelethez'
					),
					'messages' => array(
						0 => 'A felhasználó hozzáadása sikeresen megtörtént!',
						1 => 'A felhasználót nem sikerült létrehozni, mert @msg! (Hibakód: @code)',
					),
				),
				'edit' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'valamelyik megadott adat formátuma hibás',
						6 => 'létezik felhasználó a megadott e-mail címmel',
						7 => 'az űrlap adatai nem változtak (vagy adatb. hiba)'
					),
					'messages' => array(
						0 => 'A felhasználó adatainak módosítása sikeresen megtörtént!',
						1 => 'A felhasználó adatait nem sikerült módosítani, mert @msg! (Hibakód: @code)',
					),
				),
				'delete' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
					),
					'messages' => array(
						0 => 'A felhasználó törlése sikeresen megtörtént!',
						1 => 'A felhasználó törlése sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'editAccessData' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'a megadott két új jelszó nem egyezik meg',
					),
					'messages' => array(
						0 => 'A felhasználó hozzáférési adatainak módosítása sikeres volt!',
						1 => 'A felhasználó hozzáférési adatainak módosítása sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'extConnTools' => array(
				'deactivate' => array(
					'errors' => array(
						1 => 'valamelyik megadott adat formátuma hibás',
						2 => 'az összekapcsolás nem található',
						3 => 'nincs jogosultsága a művelethez',
						4 => 'az összekapcsolás státusza már deaktív'
					),
					'messages' => array(
						0 => 'A távoli szolgátatóval történő összekacsolás deaktiválása megtörtént! Az oldal frissül, várjon...',
						1 => 'A távoli szolgátatóval történő összekacsolás deaktiválás sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'activate' => array(
					'errors' => array(
						1 => 'valamelyik megadott adat formátuma hibás',
						2 => 'az összekapcsolás nem található',
						3 => 'nincs jogosultsága a művelethez',
						4 => 'az összekapcsolás státusza már aktív'
					),
					'messages' => array(
						0 => 'A távoli szolgátatóval történő összekacsolás aktiválása megtörtént! Az oldal frissül, várjon...',
						1 => 'A távoli szolgátatóval történő összekacsolás aktiválása sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'unlink' => array(
					'errors' => array(
						1 => 'valamelyik megadott adat formátuma hibás',
						2 => 'az összekapcsolás nem található',
						3 => 'nincs jogosultsága a művelethez',
					),
					'messages' => array(
						0 => 'A távoli szolgátató fiókjának leválasztása sikeresen megtörtént! Az oldal frissül, várjon...',
						1 => 'A távoli szolgátató fiókjának leválasztása sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'editMyProfile' => array(
					'errors' => array(
						1 => 'a megadott jelszó nem egyezik meg a felhasználó jelszavával',
						2 => 'a megadott két új jelszó nem egyezik meg',
					),
					'messages' => array(
						0 => 'A felhasználói adatok frissítése sikeresen megtörtént!',
						1 => 'A felhasználói adatok frissítése sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'lessons' => array(
				'add' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'valamelyik megadott adat formátuma hibás',
					),
					'messages' => array(
						0 => 'A tantárgy hozzáadása sikeres volt!',
						1 => 'A tantárgy hozzáadása sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'edit' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'valamelyik megadott adat formátuma hibás',
					),
					'messages' => array(
						0 => 'A tantárgy szerkesztése sikeres volt!',
						1 => 'A tantárgy szerkesztése sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'delete' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
					),
					'messages' => array(
						0 => 'A tantárgy törlése sikeres volt!',
						1 => 'A tantárgy törlése sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'invitation' => array(
				'batchInvite' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'egyes felhasználóknak nem sikerült elküldeni a meghívó e-mailt',
					),
					'messages' => array(
						0 => 'A felhasználók meghívása sikeresen befejeződött. A meghívók megérkezése azonban akár 12 órát is igénybe vehet!',
						1 => 'Az összes felhasználó meghívása nem sikerült, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'groups' => array(
				'add' => array(
					'errors' => array(
						2 => 'valamelyik megadott adat formátuma hibás',
						3 => 'valamelyik megadott adat formátuma hibás',
						4 => 'a megadott kategória nem található',
						5 => 'a csoporthoz hozzáadandó felhasználók valamelyike nem található',
					),
					'messages' => array(
						0 => 'A csoport hozzáadása sikeres volt!',
						1 => 'A csoport hozzáadása sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'edit' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'valamelyik megadott adat formátuma hibás',
						3 => 'valamelyik megadott adat formátuma hibás',
						4 => 'valamelyik megadott adat formátuma hibás',
					),
					'messages' => array(
						0 => 'A csoport szerkesztése sikeres volt!',
						1 => 'A csoport szerkesztése sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'delete' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'valamelyik megadott adat formátuma hibás',
						3 => 'nem létezik a csoport',
					),
					'messages' => array(
						0 => 'A csoport törlése sikeres volt!',
						1 => 'A csoport törlése sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'groupThemes' => array(
				'edit' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'valamelyik megadott adat formátuma hibás',
					),
					'messages' => array(
						0 => 'A csoportkategória szerkesztése sikeres volt!',
						1 => 'A csoportkategória szerkesztése sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'homeworks' => array(
				'add' => array(
					'errors' => array(
						0x1 => 'nincs jogosultsága a művelethez',
						0x2 => 'valamelyik megadott adat formátuma hibás',
						0x3 => 'az órarend-bejegyzés nem található',
						0x4 => 'a meadott órarend-bejegyzés a kapott hét sorszámával nem összeegyeztethető',
					),
					'messages' => array(
						0 => 'A házi feladat hozzáadása sikeresen befejezeődött!',
						1 => 'A házi feladat hozzáadása sikertelenül záródott, mert @msg! (Hibakód: @code)',
					),
				),
				'delete' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'valamelyik megadott adat formátuma hibás',
					),
					'messages' => array(
						1 => 'A házi feladat törlése sikertelenül záródott, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'teachers' => array(
				'add' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'valamelyik megadott adat formátuma hibás',
						4 => 'néhány tantárgy hozzáadása nem sikerült',
					),
					'messages' => array(
						0 => 'A tanár (és tantárgyak) hozzáadása sikeres volt!',
						1 => 'A tanár (vagy/és tantárgyak) hozzáadása sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'edit' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'valamelyik megadott adat formátuma hibás',
					),
					'messages' => array(
						0 => 'A tanár adatainak módosítása sikeres volt!',
						1 => 'A tanár adatainak módosítása sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
				'delete' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
					),
					'messages' => array(
						0 => 'A tanár törlése a rendszerből sikeres volt!',
						1 => 'A tanár törlése a rendszerből sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'timetables' => array(
				'progressTable' => array(
					'errors' => array(
						1 => 'valamelyik megadott adat formátuma hibás',
						2 => 'nincs jogosultsága a művelethez',
					),
					'messages' => array(
						0 => 'Az órarend frissítése sikeres volt!',
						1 => 'Az órarend frissítése sikertelen volt, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'passwordReset' => array(
				'sendMail' => array(
					'errors' => array(
						1 => 'valamelyik megadott adat formátuma hibás',
						2 => 'nem található az e-mail címhez kapcsolt felhasználó',
						4 => 'a levél elküldése közben problémák adódtak',
					),
					'messages' => array(
						0 => 'A jelszóvisszaállító levél a felhasználó e-mail címére elküldve!',
						1 => 'A jelszóvisszaállító levél elküldése nem sikerült, mert @msg! (Hibakód: @code)',
					),
				),
				'reset' => array(
					'errors' => array(
						1 => 'nincs megadva visszaállító azonosító',
						2 => 'a visszaállító azonosító nem létezik, estleg lejárt',
						3 => 'nincs megadva új jleszó',
						4 => 'a felhasználó nem található',
						5 => 'a megadott jelszavak nem egyeznek',
					),
					'messages' => array(
						0 => 'A jelszóvisszaállítás sikeresen megtörtént. Kérjük jelentkezzen be!',
						1 => 'A jelszóvisszaállítás nem sikerült, mert @msg! (Hibakód: @code)',
					),
				),
			),

			'files' => array(
				'uploadFiles' => array(
					'errors' => array(
						1 => 'egy fájl egy hiba miatt nem töltődött fel a szerverre',
						2 => 'egy fájl mérete nagyobb a megengedettnél',
						3 => 'az osztály tárhelyén nincs elég szabad hely',
						4 => 'a kiszolgálón nincs elég hely egy fájl feltöltéséhez',
					),
					'messages' => array(
						0 => 'A fájlok feltöltése sikeresen megtörtént!',
						1 => 'Valemlyik fájl (vagy fájlok) feltöltése nem sikerült, mert @msg! (Hibakód: @code)',
					),
				),
				'delete' => array(
					'errors' => array(
						1 => 'nincs jogosultsága a művelethez',
						2 => 'a fájl nem található az adatbázisban',
					),
					'messages' => array(
						0 => 'A fájl törlése sikeresen megtörtént!',
						1 => 'A fájl törlése nem sikerült, mert @msg! (Hibakód: @code)',
					),
				),
			),
		);

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
					die(header('Location: /404'));
			}
		}

		# 404-es hiba esetén
		static function Missing($path = ''){
			global $ENV;

			if ($ENV['do'] != 404)
				die(header('Location: /404?path='.$path));
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

		<p>Üdvözlettel,<br><br>
		<b>Mészáros Bálint</b> és <b>Kiss Antal</b><br>
		 a BetonSoft igazgatóságának tagjai</p>
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
			if (System::PermCheck('admin')) return 1;

			if (System::InputCheck($email,'email')) return 2;

			$data = $db->where('email',$email)->getOne('users');
			if (!empty($data)) return 5;

			$invId = Password::Generalas(12);
			$action = $db->insert('invitations',array(
				'invitation' => $invId,
				'name' => $name,
				'email' => $email,
				'classid' => $user['classid'],
				'inviter' => $user['id'],
			));

			if (!$action) return 3;

			$body = self::$inviteBody;

			$body = str_replace('++NAME++',$name,$body);
			$body = str_replace('++SCHOOL++',$ENV['school']['name'],$body);
			$body = str_replace('++CLASS++',$ENV['class']['classid'],$body);
			$body = str_replace('++ID++',$invId,$body);
			$body = str_replace('++SENDER++',$user['realname'],$body);

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
			if (System::PermCheck('admin')) return 1;

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
				'realname' (string)
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

					case 'realname':
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
				'realname' => $data['realname'],
				'classid' => $token_d['classid'],
				'email' => $token_d['email'],
				'priv' => 'user',
				'active' => 1,
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
											WHERE g.classid = gt.classid = ?',array($token_d['classid']));

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
											WHERE `classid` = ?',array($user['classid']));
			$groups = $db->rawQuery('SELECT `id`,`theme`
								FROM `groups`
								WHERE `classid` = ?',array($user['classid']));
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
					'classid' => $user['classid'],
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

		static function GetFreeSpace(){
			global $db, $user, $ENV;

			$data = $db->rawQuery('SELECT `size`
									FROM `files`
									WHERE `classid` = ?',array($user['classid']));
			$usedSpace = 0;

			foreach ($data as $array)
				$usedSpace += $array['size'];

			return self::CLASS_SPACE - $usedSpace;
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
			// Van-e jogosultság?
			if (System::PermCheck('editor')) return 6;

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

			$data = $db->where('id',$id)->where('classid',$user['classid'])->getOne('files');

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
			if (System::PermCheck('admin')) return 1;

			$data = $db->where('id',$id)->where('classid',$user['classid'])->getOne('files');
			if (empty($data)) return 2;

			$path = "$root/usr_uploads/".$data['tempname'];
			if (file_exists($path))
				unlink($path);

			$action = $db->where('id',$id)->delete('files');

			return $action ? 0 : 3;
		}

		static function GetFileInfo($id){
			global $db, $user, $root;

			$data = $db->where('id',$id)->where('classid',$user['classid'])->getOne('files');
			if (empty($data)) return 1;

			$lesson = $db->where('id',$data['lessonid'])->getOne('lessons');
			$uploader = $db->where('id',$data['uploader'])->getOne('users');

			return array(
				'name' => $data['name'],
				'description' => $data['description'],
				'lesson' => empty($lesson) ? 'nincs hozzárendelve' : $lesson['name'],
				'size' => self::FormatSize($data['size']),
				'time' => $data['time'],
				'uploader' => empty($uploader) ? 'ismeretlen' : $uploader['realname'].' (#'.$uploader['id'].')',
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
			if (System::PermCheck('admin')) return 1;

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
				'classid' => $user['classid'],
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
				if (!System::InputCheck($value,'attack')) return 99;
			}

			# Jogosultság ellenörzése
			if (System::ClassPermCheck($data_a['id'],'lessons')) return 1;

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
				'classid' => $user['classid'],
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
			if (System::ClassPermCheck($id,'lessons')) return 1;

			$data = $db->where('id',$id)->getOne('lessons');
			$data = System::TrashForeignValues(['classid','name','teacherid','color'],$data);

			$action = self::_delete($id);

			Logging::Insert(array_merge(array(
				'action' => 'lesson_del',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'lesson_del',
			),$data,array(
				'classid' => $user['classid'],
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
// Felh. hozzáadása
		private static function _addUser($data_a){
			global $db, $user;

			# Jog. ellelnörzése
			if(System::PermCheck('admin')) return 7;

			# Bevitel ellenörzése
			if (!System::ValuesExists($data_a,['username','realname','priv','email','active'])) return 1;
			foreach ($data_a as $key => $value){
				if (in_array($key,['classid','priv'])) continue;

				switch ($key){
					case 'realname':
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
			if (System::OptionCheck($data_a['priv'],['user','editor','admin'])) return 2;

			if (USRGRP != 'sysadmin')
				$data_a['classid'] = $user['classid'];

			# Létezik-e már ilyen felhasználó?
			$data = $db->where('username',$data_a['username'])->getOne('users');
			if (!empty($data)) return 4;
			$data = $db->where('username',$data_a['username'])->getOne('admins');
			if (!empty($data)) return 5;
			$data = $db->where('email',$data_a['email'])->getOne('users');
			if (!empty($data)) return 6;

			# Ideiglenes jelszó készítése
			$data_a['password'] = Password::Kodolas(Password::Generalas(6));

			# Regisztráció
			return [$db->insert('users',$data_a)];
		}

		static function AddUser($data_a){
			global $user;
/*			array(
				'username',
				'realname',
				'priv',
				'email',
				'active',
			);					*/
			$action = self::_addUser($data_a);

			$data_a = System::TrashForeignValues(['username','realname','priv','email','active'],$data_a);

			Logging::Insert(array_merge(array(
				'action' => 'user_add',
				'user' => $user['id'],
				'errorcode' => (!is_array($action) ? $action : 0),
				'db' => 'user_add',
			),$data_a,array(
				'classid' => $user['classid'],
				'e_id' => (is_array($action) ? $action[0] : 0),
			)));

			return $action;
		}
// Felh. hozzáadás vége

// Felh. adatainak módosítása
		private static function _modifyUser($id,$datas){
			global $db, $user;

			# Jog. ellenörzése
			if (System::ClassPermCheck($id,'users')) return 1;

			# Formátum ellenörzése
			foreach ($datas as $key => $value){
				if (in_array($key,['classid','priv'])) continue;

				switch ($key){
					case 'realname':
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
			if (System::OptionCheck($datas['priv'],['user','editor','admin'])) return 2;

			if (USRGRP != 'sysadmin')
				$datas['classid'] = $user['classid'];

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

			$datas = System::TrashForeignValues(['username','realname','priv','email','active'],$datas);

			Logging::Insert(array_merge(array(
				'action' => 'user_edit',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'user_edit',
			),$datas,array(
				'classid' => $user['classid'],
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
			if (System::ClassPermCheck($id,'users')) return 1;

			$data = $db->where('id',$id)->getOne('users');
			$data = System::TrashForeignValues(['username','realname','priv','email','active'],$data);

			$action = self::_deleteUser($id);

			Logging::Insert(array_merge(array(
				'action' => 'user_del',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'user_del',
			),$data,array(
				'classid' => $user['classid'],
				'e_id' => $id,
			)));

			return $action;
		}
// Felh. törlése vége

		static function EditAccessData($id,$data){
			/* @param $id
			 * @param $data = array('newpassword','vernewpasswd')
			 */

            global $db;

			# Jog. ellenörzése
			if (System::ClassPermCheck($id,'users')) return 1;

			if ($data['newpassword'] != $data['vernewpasswd']) return 2;

			$action = $db->where('id',$id)->update('users',array(
				'password' => Password::Kodolas($data['newpassword']),
			));

			if ($action) return 0;
			else return 3;
		}

		static function EditMyProfile($data){
/*          array(
				(req)'realname',
				(req)'email',
				(opt)'oldpassword',
				(opt)'password',
				(opt)'verpasswd'
			)                       */

			global $db,$user;

			# Jelszóváltoztatás esetén...
			if (!empty($data['oldpassword']) && !empty($data['password']) && !empty($data['verpasswd'])){
				if (!Password::Ellenorzes($data['oldpassword'],$user['password'])) return 1;
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

			$User = $db->where('email', $email)->getOne('users','id,realname,email');
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
			$body = str_replace('++NAME++',$User['realname'],$body);
			$body = str_replace('++URL++',ABSPATH.'/pw-reset?key='.urlencode($hash),$body);
			$body = str_replace('++VALID++',date('Y-m-d H:i:s',$valid),$body);

			if (System::SendMail(array(
				'title' => 'CuStudy - Jelszóvisszaállítási kérelem',
				'to' => array(
					'name' => $User['realname'],
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
				'classid' => $user['classid'],
				'name' => $data['name'],
				'theme' => $data['theme'],
			));

			$users = $db->rawQuery('SELECT *
									FROM `users`
									WHERE `classid` = ?',array($user['classid']));
			$users_l = array();
			foreach ($users as $entry)
				$users_l[] = $entry['id'];

			$grpmem = explode(',',$data['group_members']);

			if (empty($data['group_members'])) return 0;
			foreach($grpmem as $mem){
				if (!in_array($mem,$users_l)) return 5;
				$db->insert('group_members',array(
					'classid' => $user['classid'],
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
			if (System::ClassPermCheck($id,'groups')) return 1;

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
											WHERE group_members.classid = ? && group_members.groupid = ?',array($user['classid'],$id));
				$memb = array();
				foreach($members as $member)
					$memb[] = $member['id'];
				$members = $memb;

				foreach ($grpm as $entry){
					if (System::InputCheck($entry,'numeric')) return 4;
					if (in_array($entry,$members)) continue;
					$db->insert('group_members',array(
						'classid' => $user['classid'],
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
			if (System::PermCheck('admin','admin')) return 1;

			# Csop. ellenörzése
			$group = $db->rawQuery('SELECT *
						FROM `groups`
						WHERE `classid` = ? && `id` = ?',array($user['classid'],$id));
			if (empty($group)) return 3;

			$members = $db->rawQuery('SELECT *
									FROM `group_members`
									WHERE `classid` = ? && `groupid` = ?',array($user['classid'],$id));

			if (!empty($members)){
				$uids = [];
				foreach ($members as $entry)
					$uids[] = $entry['userid'];

				$query = 'DELETE FROM `group_members`
							WHERE `groupid` = ? && userid IN ('.implode(',',$uids).')';

				$db->rawQuery($query,array($id));
			}

			$action = $db->where('id',$id)->delete('groups');

			return $action ? 0 : 4;
		}
	}

	class GroupThemeTools {
		static function Edit($id,$data){
			global $db;

			# Jog. ellenörzése
			If (System::ClassPermCheck($id,'group_themes')) return 1;

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

			$action = $db->where('id',$id)->update('group_themes',$data);

			if ($action) return 0;
			else return 3;
		}
	}

	class TeacherTools {
		static function Add($datas){
			global $db,$user;

			# Jog. ellenörzése
			if(System::PermCheck('admin')) return 1;

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
			$basedata['classid'] = $user['classid'];
			$action = $db->insert('teachers',$basedata);
			if (!is_numeric($action)) return 3;

			# Tantárgyak hozzáadása
			if (!isset($datas['lessons']) || empty($datas['lessons'])) return [$action];
			foreach ($datas['lessons'] as $sublesson){
				$action_l = $db->insert('lessons',array(
					'classid' => $user['classid'],
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
			if (System::ClassPermCheck($data['id'],'teachers')) return 1;

			# Adatbázisba írás
			$action = $db->where('id',$data['id'])->update('teachers',$data);

			if ($action) return 0;
			else return 3;
		}

		static function Delete($id){
			global $db;

			# Jog. ellenörzése
			if (System::ClassPermCheck($id,'teachers')) return 1;

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
			return HomeworkTools::$RomanMonths[date('m', $time)].'.'.date('d', $time);
		}

		static function Add($data){
			global $db, $user;

			# Jog. ellenörzése
			if(System::PermCheck('editor')) return 0x1;

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
										WHERE tt.classid = l.classid = t.classid = ? && tt.id = ? && t.name IS NOT NULL && l.name IS NOT NULL',
							array($user['classid'],$data['lesson']));

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
						'classid' => $user['classid'],
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

			$db->insert('homeworks',array_merge($data,array('author' => $user['id'], 'classid' => $user['classid'])));

			return $uploadStatus;
		}

		static function Delete($id){
			global $db;

			# Form. ellenörzése
			if (System::InputCheck($id,'numeric')) return 2;

			# Jog. ellenörzése
			if (System::ClassPermCheck($id,'homeworks')) return 1;

			$action = $db->where('id',$id)->delete('homeworks');

			if ($action) return 0;
			else return 3;
		}

		static function GetHomeworks($numberOfHomework = 3, $onlyListActive = false){
			global $db, $user;

			$grpmember = $db->rawQuery('SELECT `groupid`
							FROM `group_members`
							WHERE `classid` = ? && `userid` = ?',array($user['classid'],$user['id']));

			$addon = [$user['id'],$user['classid']];
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
			//var_dump($timetable);
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
				->where('classid', $user['classid'])
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
			if ($check) return $check;

			# Adatbázisba írás
			$action = $db->insert('hw_markdone',array(
				'userid' => $user['id'],
				'homework' => $id,
				'classid' => $user['classid'],
			));

			return $action ? 0 : 4;
		}

		static function UndoMarkedDone($id){
			global $db, $user;

			# Ellenőrzés
			$check = self::CheckMarkedDone($id, self::CAN_EXIST);
			if ($check) return $check;

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
			if (!System::PermCheck('admin')){ ?>
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
<?php       if (!System::PermCheck('editor')){ ?>
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

	class Timetable {
		static function GetWeekNum(){
			$dateObj = new DateTime();
			return $dateObj->format("W");
		}

		static function GetNumberOfWeeks(){
			global $db,$user;

			$data = $db
				->where('classid', $user['classid'])
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
									WHERE `classid` = ? && `week` = ?',array($user['classid'],'b'));
			if (empty($data))
				return $sorting ? 'ASC' : 'A';

			$weekNum = date('W',$timestamp);

			$tsyear = date('Y',$timestamp);

			$jan1 = strtotime("1 jan $tsyear");
			$aug31 = strtotime("1 sept $tsyear");

			$start = strtotime('+7 days',strtotime('this week', $jan1));
			$end = strtotime('+7 days',strtotime('this week', $aug31));

			$yearPassed = $timestamp >= $start && $timestamp < $end;

			return !$sorting
				? $ENV['class']['pairweek'] === 'A'
					? (
						$weekNum % 2 == 0
						? (!$yearPassed ? 'A' : 'B')
						: (!$yearPassed ? 'B' : 'A')
					)
					: (
						$weekNum % 2 == 0
						? (!$yearPassed ? 'B' : 'A')
						: (!$yearPassed ? 'A' : 'B')
					)
				: $ENV['class']['pairweek'] === 'A'
					? (
						$weekNum % 2 == 0
						? (!$yearPassed ? 'ASC' : 'DESC')
						: (!$yearPassed ? 'DESC' : 'ASC')
					)
					: (
						$weekNum % 2 == 0
						? (!$yearPassed ? 'DESC' : 'ASC')
						: (!$yearPassed ? 'ASC' : 'DESC')
					);
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
					'classid' => $user['classid'],
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
			global $db;

			foreach ($toDelete as $sub){
				if (!isset($sub['id'])) return 5;
				$id = $sub['id'];
				if (System::InputCheck($id,'numeric')) return 6;

				$action = $db->where('id',$id)->delete('timetable',$id);

				# Órarend-entryhez tartozó HW-k törlése
				$db->where('lesson',$id)->delete('homeworks');

				if (!$action) return 7;
			}
		}

		static function ProgressTable($data){
			# Jog. ellenörzése
			if (System::PermCheck('admin')) return 2;

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

			$addon = array($user['classid']);

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
			// TODO A $currentWeek nincs használva, ha nem kell, töröld
			$currentWeek = date('W', $weekday);

			$dualWeek = Timetable::GetNumberOfWeeks() == 1 ? false : true;

			$userInGroups = $db->rawQuery('SELECT `groupid`
											FROM `group_members`
											WHERE `classid` = ? && `userid` = ?',array($user['classid'],$user['id']));
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
									,array($user['classid'],$hour >= 8 && $minute >= 0 ? $dayInWeek : $dayInWeek-1));

				$data_nextWeek = $db->rawQuery("SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
											FROM timetable tt
											LEFT JOIN lessons l
											ON (l.id = tt.lessonid && l.classid = tt.classid)
											WHERE tt.classid = ? $onlyGrp
											ORDER BY tt.day, tt.lesson"
									,array($user['classid']));

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
					$Timetable[$lesson][$date][] = array($class['name'],'',$class['color'],$class['id'],$class['group_name'],date('W',$date));
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
			$data = array($user['classid'],$user['classid'],$week);

			$groupdata = $db->rawQuery(
				"SELECT DISTINCT g.id
				FROM group_members gm
				LEFT JOIN groups g ON gm.groupid = g.name
				WHERE gm.userid = ? && gm.classid = ?", array($user['id'], $user['classid']));

			# Ha minden csoport adatait szeretnénk lekérni...
			if ($allgroups == false){
	            $query .= ' && groupid = ?';
	            $data[] = '0';
				foreach ($groupdata as $subgd){
					$query .= " || groupid = ?";
					$data[] = $subgd['id'];
				}
			}

			$groups = $db->rawQuery('SELECT `id`, `name` FROM `groups` WHERE classid = ?',array($user['classid']));
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
				if (isset($class['name']))
					$Timetable[$lesson][$weekday][] = array($class['name'],$class['teacher'],$class['color'],$class['id'],$grp_list[$class['groupid']]);
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
		if (!empty($week) && !System::PermCheck('admin')) print "<button class='btn sendbtn'>Módosítások mentése</button>";
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
