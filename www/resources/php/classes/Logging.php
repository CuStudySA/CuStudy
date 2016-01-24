<?php

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

			'failed_login' => array(
				'userid' => 'Felhasználó azonosító',
				'ip' => 'IP cím',
				'at' => 'Próbálkozás időbélyege',
			),
		);

		static $ActionLabels = array(
			'login' => 'Bejelentkezés',
			'lesson_add' => 'Új tantárgy felvétele',
			'lesson_edit' => 'Tantárgy szerkesztése',
			'lesson_del' => 'Tantárgy törlése',
			'user_add' => 'Új felhasználó felvétele',
			'user_edit' => 'Felhasználó szerkesztése',
			'user_del' => 'Felhasználó törlése',
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
