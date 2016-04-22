<?php
	class UserSettings {
		static $defaultValues = array(
			'timetable' => array(
				'defaultViewMode' => 'original',
			),
		);

		static $keys = array(
			'timetable' => array(
				'name' => 'Órarend',

				'defaultViewMode' => array(
					'printJs' => true,
					'name' => 'Személyre szabott órarend alapért. megjelenési módja',
					'options' => array(
						'original' => 'Hagyományos',
						'compact' => 'Kompakt',
					),
					'type' => 'select',
				),
			),
		);

		static function Load($userid = null, $setToProfile = false){
			global $db, $user;

			if (empty($userid)){
				if (!empty($user) && is_array($user))
					$userid = $user['id'];
				else
					return self::$defaultValues;
			}

			$data = $db->where('userid',$userid)->get('settings_user');

			$return = self::$defaultValues;
			foreach ($data as $a){
				if (!isset($a['value']))
					continue;

				$keys = explode('.',$a['key'],2);
				$return[$keys[0]][$keys[1]] = $a['value'];
			}

			if ($setToProfile)
				$user['settings'] = $return;

			return $return;
		}

		static private function _get($storage,$key){
			$keys = explode('.',$key,2);
			return $storage['settings'][$keys[0]][$keys[1]];
		}

		static function Get($key, $userid = null){
			global $user;

			return self::_get((empty($userid) ? $user['settings'] : self::Load($userid)),$key);
		}
	}