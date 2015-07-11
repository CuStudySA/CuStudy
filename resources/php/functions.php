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
			/*$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, strlen($characters) - 1)];
			}*/

			return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
		}
		static function GetSession($username){
			global $_SERVER;
			return sha1($username.microtime().$_SERVER['REMOTE_ADDR']);
		}
	}

	class Logging{
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

			return $db->insert('log_'.$datab,$data,true);
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
				break;
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

			global $db;
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
			if ($action) return 0;
			else return 3;
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
				break;
			}

			if (!isset(self::$subTables[$dataid['action']])) return 5;

			$action['details'] = $logclass->_translateDbTitles($dataid);

			if ($action['details'] !== false) return $action;
			else return 6;
		}
	}

	class System {
		static $Patterns = array(
			'username' => '^[a-zA-Z\d]{3,15}$',
			'name' => '^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$',
			'password' => '^[\w\d]{6,20}$',
			'email' => '^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9])?@[a-z0-9]+\.[a-z]{2,4}$',
		);

		static $ResolveNames = array(
			'realname' => 'name',
			'verpasswd' => 'password',
			'newpassword' => 'password',
			'vernewpasswd' => 'password',
		);

		static $Inputs = array(
			'users' => ['username','realname','email','password','verpasswd','newpassword','vernewpasswd'],
		);

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
					$preg = '/^[a-zA-Z0-9.-_]+(\+[a-zA-Z0-9])?@[a-z0-9]+\.[a-z]{2,4}$/';
				break;
				case 'name':
					$preg = '/^[A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű.]+[ ][A-ZÁÉÍÓÖŐÚÜŰ][a-záéíóöőúüű]+[ a-zA-ZáéíóöőúüűÁÉÍÓÖŐÚÜŰ]*$/';
				break;
				case 'class':
					$preg = '/^\d{1,2}\.?[A-Za-z]*$/';
				break;
				case 'numeric':
					$preg = '/^\d+$/';
				break;
				case 'text':
					$preg = '/^[A-ZÁÉÍÓÖŐÚÜŰa-záéíóöőúüű ]{2,}$/';
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
			if (!in_array($text,$values)) return true;
			else return false;
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

		//Cookie ellenőrzés & '$user' generálása
		static function CheckLogin() {
			global $db,$user;

			if (!Cookie::exists('PHPSESSID')) return 'guest';
			$session = Cookie::get('PHPSESSID');
			if (empty($session)) return 'guest';
			$isadmin = false;
			
			$user = $db->where('session',$session)->getOne('users');
			if (!isset($user)) {
				$user = $db->where('session',$session)->getOne('admins');
				if (empty($user)) return 'guest';
				$isadmin = true;
			}
			
			if (!$isadmin)
				if (self::UserActParent($user)) return 'guest';
			
			unset ($session);

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

			if (!Password::Ellenorzes($password,$data['password'])) return 3;
			if (!$data['active']) return 4;

			if ($isadmin == 'users')
				if (self::UserActParent($data)) return 5;

			$session = Password::GetSession($username);
			$action = $db->where('username',$username)->update($isadmin,array('session' => $session));

			Cookie::set('PHPSESSID',$session,null);

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
			
			$db->where('username',$user['username'])->update('users',array(
				'session' => '',
			));
			
			Cookie::delete('PHPSESSID');

			return 0;
		}
		
		// Jogosultság ellenörző
		static function PermCheck($minjog, $maxjog = ''){
			global $PERM;

			if (empty($maxjog)){
				if (USRPERM < $PERM[$minjog]) return true;
				return false;
			}

			if (USRPERM < $PERM[$minjog] || USRPERM > $PERM[$maxjog])
				return true;

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
			die(json_encode(array_merge(array(
				"message" => $m,
				"status" => $s,
			),$x)));
		}

		// Asszociatív tömb-e?
		static function IsAssoc($array) {
            return ($array !== array_values($array));
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
			$arr = array();
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

			if ($json)
				return json_decode(file_get_contents($url, false, $context),true);
			else
				return file_get_contents($url, false, $context);
		}

		static function Redirect($url){
			return die(header('Location: '.$url));
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
			$db->where('username',$user['username'])->update('users',array('session' => $session));

			Cookie::set('PHPSESSID',$session,null);

			System::Redirect('/');
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

		static function GetAccessToken($code, $url = 'http://custudy.tk/googleauth'){
			$data = self::Request('https://www.googleapis.com/oauth2/v3/token',null,array(
				'code' => $code,
				'client_id' => self::CLIENTID,
				'client_secret' => self::SECRET,
				'redirect_uri' => $url,
				'grant_type' => 'authorization_code',
			));

			if (isset($data['error']))
				die(header("Location: /?errtype=remote"));

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

			if (!$action) return 5;
			else return 0;
		}

		static function Unlink($connid){
			global $db, $user;

			if (System::InputCheck($connid,'numeric')) return 1;

			$data = $db->where('id',$connid)->getOne('ext_connections');
			if (empty($data)) return 2;

			if ($data['userid'] != $user['id'] && !System::PermCheck('admin','admin'))
				if (System::ClassPermCheck($data['userid'],'users')) return 3;
			else
				$usr = $user['id'];

			$action = $db->where('id',$connid)->delete('ext_connections');

			if (!$action) return 4;
			else return 0;
		}
	}

	class Message {
		static $ErrorCode = array(
			'adduser' => array(
				1 => 'hiányzik egy szükséges adat',
				2 => 'valamelyik megadott adat formátuma hibás',
				3 => 'a megadott jelszavak nem egyeznek',
				4 => 'már foglalt a megadott felhasználónév',
				5 => 'már foglalt a megadott felhasználónév',
				6 => 'létezik felhasználó a megadott e-mail címmel',
				7 => 'az űrlap adatai nem változtak (vagy adatb. hiba)'
			),
			'edituser' => array(
				1 => 'nincs jogosultsága a művelethez',
				2 => 'valamelyik megadott adat formátuma hibás',
				3 => 'a megadott jelszavak nem egyeznek',
				4 => 'már foglalt a megadott felhasználónév',
				5 => 'már foglalt a megadott felhasználónév',
				6 => 'létezik felhasználó a megadott e-mail címmel',
				7 => 'az űrlap adatai nem változtak (vagy adatb. hiba)'
			),
			'login' => array(
				1 => 'valamelyik megadott adat formátuma hibás',
				2 => 'a felhasználó nem létezik',
				3 => 'a megadott jelszó nem egyezik a felhasználó jelszavával',
				4 => 'a felhasználó állapota tiltott',
				5 => 'az osztály vagy iskola állapota tiltott',
			),
		);

		// Hibakód feldolgozása (to string)
		static function GetError($activity,$code){
			if (isset(self::$ErrorCode[$activity][$code])) return self::$ErrorCode[$activity][$code];
			else return 'a hibakód nem létezik';
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
					die(header('Location: /403'));
			}
		}
		
		# 404-es hiba esetén
		static function Missing($path = ''){
			global $ENV;

			if ($ENV['do'] != 404)
				die(header('Location: /404?path='.$path));
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
			#var_dump($data_a);
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
				if (!System::InputCheck($value,'attack')) return 99;
			}

			if (!isset($data_a['color']) || $data_a['color'] == '#000000') $data_a['color'] = 'default';
			$data_a['classid'] = $ENV['class']['id'];

			return [$db->insert('lessons',$data_a,true)];
		}
		static function Add($data_a){
			global $db,$user;

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
			global $user,$db;

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
			if(System::PermCheck('admin')) return 8;

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

			# Regisztráció
			return [$db->insert('users',$data_a,true)];
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
			/**
		        * @param $username
		        * @param $datas
			    * @return int
		    */
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

			if($datas['username'] != $userdata['username']){
				$data = $db->where('username',$datas['username'])->getOne('users');
				if (!empty($data)) return 4;

				$data = $db->where('username',$datas['username'])->getOne('admins');
				if (!empty($data)) return 5;
			}

			if($datas['email'] != $userdata['email']){
				$data = $db->where('email',$datas['email'])->getOne('users');
				if (!empty($data)) return 6;
			}

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
			if (!empty($data['password'])){
				if (!Password::Ellenorzes($data['oldpassword'],$user['password'])) return 1;
				if ($data['password'] != $data['verpasswd']) return 2;

				$data['password'] = Password::Kodolas($data['password']);
			}
			unset($data['oldpassword']);
			unset($data['verpasswd']);

			$action = $db->where('id',$user['id'])->update('users',$data);

			if (!$action) return 3;
			else return 0;
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
			),true);

			$users = $db->rawQuery('SELECT *
									FROM `users`
									WHERE `classid` = ?',array($user['classid']));
			$users_l = array();
			foreach ($users as $entry)
				$users_l[] = $entry['id'];

			$grpmem = explode(',',$data['group_members']);
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
				if (!System::InputCheck($value,'attack')) return 99;
			}

			$db->where('id',$id)->update('groups',array(
				'name' => $data['name'],
				'theme' => $data['theme'],
			));

			if (!empty($data['class_members'])){
				$grpm = explode(',',$data['class_members']);

				$query = 'DELETE FROM `group_members`
							WHERE `groupid` = ? && (';
				$qdata = array($id);

				foreach ($grpm as $entry){
					if (System::InputCheck($entry,'numeric')) return 4;
					$query .= ' `userid` = ? ||';
					$qdata[] = $entry;
				}
				$query = rtrim($query,' |').')';

				$db->rawQuery($query,$qdata);
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
				$query = 'DELETE FROM `group_members`
							WHERE `groupid` = ? && (';
				$qdata = array($id);

				foreach ($members as $entry){
					$query .= ' `userid` = ? ||';
					$qdata[] = $entry['userid'];
				}
				$query = rtrim($query,' |').')';

				$db->rawQuery($query,$qdata);
			}

			$action = $db->where('id',$id)->delete('groups');

			if ($action) return 0;
			else return 4;
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
			$action = $db->insert('teachers',$basedata,true);
			if (!is_numeric($action)) return 3;

			# Tantárgyak hozzáadása
			if (!isset($datas['lessons']) || empty($datas['lessons'])) return 0;
			foreach ($datas['lessons'] as $sublesson){
				$action_l = $db->insert('lessons',array(
					'classid' => $user['classid'],
					'name' => $sublesson['name'],
					'teacherid' => $action,
					'color' => $sublesson['color'],
				));
				if (!$action_l) return 4;
			}

			return 0;
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

			if ($action) return 0;
			else return 2;
		}
	}

	class Timetable {
		// Órarend módosítások feldolgozása
		static function ProgressTable($data){
			global $db, $user;

			# Hét ellenörzése
			$week = strtolower($data['week']);
			if (!in_array($week,['a','b'])) return 1;

			# Bejegyzések hozzáadása
			if(isset($data['add'])){
				$toAdd = $data['add'];
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

			# Bejegyzések törlése
			if(isset($data['delete'])){
				$toDelete = $data['delete'];
				foreach ($toDelete as $sub){
					if (!isset($sub['id'])) return 5;
					$id = $sub['id'];
					if (System::InputCheck($id,'numeric')) return 6;

					$action = $db->where('id',$id)->delete('timetable',$id);
					if (!$action) return 7;
				}
			}

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
			LEFT JOIN lessons l ON l.id = tt.lessonid
				AND l.classid = ?
			WHERE tt.classid = ? && tt.week = ?";

			# Órarend lekérés segédtömb elékészítése
			$data = array($user['classid'],$user['classid'],$week);

			$groupdata = $db->rawQuery(
				"SELECT DISTINCT g.id
				FROM group_members gm
				LEFT JOIN groups g ON gm.groupid = g.name
				WHERE gm.userid = ? && gm.classid = ?", array($user['id'], $user['classid']));

			# Ha minden csoport adatait szeretnénk lekérni...
			if ($allgroups !== true){
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
		static function Render($week,$Timetable){ ?>
			<table class='timet'>
				<thead>
					<tr>
						<th class="week"><?=strtoupper($week)?></th>
						<th class="weekday">Hétfő</th>
						<th class="weekday">Kedd</th>
						<th class="weekday">Szerda</th>
						<th class="weekday">Csütörtök</th>
						<th class="weekday">Péntek</th>
					</tr>
				</thead>

				<tbody>
<?php
			for ($lesson = 0; $lesson <= 8; $lesson++){
				if (empty($Timetable[$lesson])) continue; ?>
				<tr class="lesson-field">
					<th><?=$lesson+1?></th>
<?php               for ($weekday = 0; $weekday < 5; $weekday++){
						$class = isset($Timetable[$lesson][$weekday]) ? $Timetable[$lesson][$weekday] : null;
						self::_RenderClass($class);
					} ?>
				</tr>
<?php       } ?>
				</tbody>
			</table>
			<p class='btn sendbtn'>Változtatások mentése</p>
<?php  }

		// Órarend cella kirenderelő
		static private function _RenderClass($class){
			if (isset($class) && (!is_array($class) || !empty($class))){
				$echo = '<td>';
				if (!is_array($class)) $class = array($class);
				foreach($class as $c){
					if (empty($c[4])) $grpstr = '';
					else $grpstr = ' ('.$c[4].')';

					$echo .= "<span class='lesson' style='background: {$c[2]}'>{$c[0]}{$grpstr}<span class='del typcn typcn-times' data-id='$c[3]'></span></span>";
				}
			}
			else $echo = '<td class="empty">';
			$echo .= '<span class="add typcn typcn-plus"></span>';
			echo "$echo</td>";
		}
	}