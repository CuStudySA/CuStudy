<?php

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

