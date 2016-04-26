<?php
	class UserSettings {
		static $defaultValues = [];

		static $keys = array(
			'timetable' => array(
				'name' => 'Órarend',

				'defaultViewMode' => array(
					'name' => 'Személyre szabott órarend alapért. megjelenési módja',
					'options' => array(
						'original' => 'Hagyományos',
						'compact' => 'Kompakt',
					),
					'type' => 'select',
					'defaultValue' => 'original',
				),
			),

			'security' => array(
				'name' => 'Biztonság',

				'checkSessionIp' => array(
					'name' => 'Munkamenet IP-címhez kényszerítése',
					'options' => array(
						'true' => 'Engedélyezve',
						'false' => 'Letiltva',
					),
					'type' => 'select',
					'defaultValue' => 'true',
				),
			),
		);

		static function Load($userid = null, $setToProfile = false){
			global $db, $user;

			if (empty(self::$defaultValues)){
				foreach (self::$keys as $key => $value){
					foreach ($value as $k => $v){
						if (!is_array($v)) continue;

						self::$defaultValues[$key][$k] = $v['defaultValue'];
					}
				}
			}

			if (empty($userid)){
				if (!empty($user) && is_array($user))
					$userid = $user['id'];
				else
					return self::$defaultValues;
			}

			$data = $db->where('userid',$userid)->get('settings_user');

			$return = self::$defaultValues;
			foreach ($data as $a)
				$return[$a['category']][$a['key']] = $a['value'];


			if ($setToProfile)
				$user['settings'] = $return;

			return $return;
		}

		static private function _get($storage,$key){
			$keys = explode('.',$key,2);
			return $storage[$keys[0]][$keys[1]];
		}

		static function Get($key, $userid = null){
			global $user;

			return self::_get((empty($userid) ? $user['settings'] : self::Load($userid)),$key);
		}

		static function Apply($data){
			global $user, $db;

			$userdb = $db->where('userid',$user['id'])->get('settings_user');
			$userSett = [];
			foreach ($userdb as $array)
				$userSett[$array['category']][$array['key']] = array($array['value'],$array['id']);


			foreach ($data as $key => $value){
				$keys = explode('_',$key,2);

				if (empty(self::$keys[$keys[0]][$keys[1]]))
					continue;

				if ($user['settings'][$keys[0]][$keys[1]] == $value)
					continue;

				if (self::$keys[$keys[0]][$keys[1]]['defaultValue'] == $value && !empty($userSett[$keys[0]][$keys[1]]))
					$db->where('id',$userSett[$keys[0]][$keys[1]][1])->delete('settings_user');

				if (self::$keys[$keys[0]][$keys[1]]['defaultValue'] != $value && !empty($userSett[$keys[0]][$keys[1]]))
					$db->where('id',$userSett[$keys[0]][$keys[1]][1])->update('settings_user',array(
						'value' => $value,
					));

				if (self::$keys[$keys[0]][$keys[1]]['defaultValue'] != $value && empty($userSett[$keys[0]][$keys[1]]))
					$db->insert('settings_user',array(
						'value' => $value,
						'category' => $keys[0],
						'key' => $keys[1],
						'userid' => $user['id'],
					));
			}

			return 0;
		}
	}