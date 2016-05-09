<?php
	class Logging {
		static $subTables = array(
			'login' => 'login',
		);

		static $ActionLabels = array(
			'users' => array(
				'modifyRole' => 'Felhasználó lokális szerepkörének szerkesztése',
				'eject' => 'Felhasználó lokális szerepkörének eltávolítása',
				'editMyProfile' => 'Felh. saját adatainak módosítása',
				'enrollUser' => 'Új lokális szerepkör hozzáadása',
			),
			'lessons' => array(
				'add' => 'Tantárgy hozzáadása',
				'edit' => 'Tantárgy szerkesztése',
				'delete' => 'Tantárgy törlése',
			),
			'teachers' => array(
				'add' => 'Tanár hozzáadása',
				'edit' => 'Tanár szerkesztése',
				'delete' => 'Tanár törlése',
			),
			'system' => array(
				'login' => 'Bejelentkezés',
			),
			'invitation' => array(
				'registration' => 'Regisztráció meghívó segítségével',
			),
			'mantis_users' => array(
				'create' => 'Mantis felhasználó létrehozása',
				'edit' => 'Mantis felhasználó szerkesztése',
			),
			'homeworks' => array(
				'add' => 'Házi feladat hozzáadása',
				'delete' => 'Házi feladat törlése',
			),
			'events' => array(
				'add' => 'Esemény hozzáadása',
			)
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

			return $db->insert('log__central',array_merge($data,$this->_getHeader()));
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

			return $db->insert('log__'.$datab,$data);
		}

		private function _spliceData($data){
			$splita = ['action','user','errorcode','u_classid'];

			foreach ($splita as $value){
				if (isset($data[$value])){
					$splito['central'][$value] = $data[$value];
					unset($data[$value]);
				}
			}
			$splito['sublog'] = $data;

			return $splito;
		}

		static function Insert($data){
			global $db, $user, $ENV;

/*          array(
				(req)'action' => 'login',
				(opt)'db' => 'login',
				(opt)'user' => 0,
				(opt)'errorcode' => 0,
			);                              */

			$logclass = new Logging();


			# Cselekvő akt. szerekörének meghatározása
			if (!isset($data['user']) && !empty($user)){
				$data['user'] = $user['id'];

				if (!empty($ENV['class']))
					$data['u_classid'] = $ENV['class']['id'];
			}

			# Adatok szétválasztása a funkcióknak
			$separated = $logclass->_spliceData($data);

			# Bejegyzés készítése az altáblába
			$action = $logclass->_insertSubLog($separated['sublog']);

			# Altábla bejegyzés ellenörzése
			if ($action === false) return 2;
			if ($action === true) $separated['central']['sublogid'] = 0;
			$separated['central']['sublogid'] = $action;

			# Bejegyzés készítése a főtáblába
			$action = $logclass->_insertCentral(array_merge($separated['central'],array(
				'db' => $data['db'],
			)));

			# Eredmény feldolgozása
			return $action ? 0 : 3;
		}

		static function GetLog(){
			global $user, $db;

			$fullReadable = array('teachers','lessons');

			if (!System::PermCheck('logs.getClassLog')){
				$Log = $db->rawQuery('SELECT lc.*, u.username
										FROM log__central lc
										LEFT JOIN users u
										ON u.id = lc.user
										WHERE lc.u_classid = ?
										ORDER BY lc.time DESC
										LIMIT 30',array($user['class'][0]));
			}

			else if (!System::PermCheck('logs.getAllUserLog'))
				$Log = $db->rawQuery('SELECT lc.*, u.username
										FROM log__central lc
										LEFT JOIN users u
										ON u.id = lc.user
										ORDER BY lc.time DESC
										LIMIT 30');
			else
				return 1;

			$Return = array();
			foreach ($Log as $entry){
				$action = explode('.',$entry['action']);

				if (count($action) == 1) $Action = 'Esemény';
				else {
					if (!empty(self::$ActionLabels[$action[0]][$action[1]]))
						$Action = self::$ActionLabels[$action[0]][$action[1]];
					else
						$Action = 'Esemény';
				}

				$Return[] = array(
					'ip' => $entry['ipaddr'],
					'time' => date('c',strtotime($entry['time'])),
					'id' => $entry['id'],
					'action' => $Action,
					'username' => !empty($entry['username']) ? $entry['username'] : '(ismeretlen)',
				);
			}

			return $Return;
		}

		static private function _progressData($data, $type = 'global'){
			global $dBTitles;

			$r = [];

			foreach ($data as $k => $v){
				if (empty($dBTitles[$type][$k])) continue;
				if (empty($v) && $k != 'errorcode') continue;

				$tile = $dBTitles[$type][$k];
				if (!is_array($tile))
					$r[$tile] = $v;
				else {
					$entry = $data;
					$r[$tile[0]] = call_user_func($tile[1],$v,$entry);
				}
			}

			return $r;
		}

		static function GetDetails($id){
			global $db, $ENV;

			$central = $db->where('id',$id)->getOne('log__central');

			$sub = [];
			if (!empty($central['sublogid'])){
				$sub = $db->where('id',$central['sublogid'])->getOne('log__'.$central['db']);

				if (empty($sub))
					$sub = [];
			}

			return array('global' => self::_progressData($central), 'sub' => self::_progressData($sub,$central['db']));
		}
	}
