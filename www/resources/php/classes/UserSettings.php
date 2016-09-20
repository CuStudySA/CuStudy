<?php
	class UserSettings {
		static $defaultValues = [];

		static $keys = array(
			'general' => array(
				'name' => 'Általános',

				'nextDaySwitch' => array(
					'name' => 'Ettől az időponttól a következő napi teendők lesznek láthatóak',
					'type' => 'time',
					'defaultValue' => '08:00',
				),
			),
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
				'checkUserAgent' => array(
					'name' => 'Munkamenet böngészési környezethez kényszerítése',
					'options' => array(
						'true' => 'Engedélyezve',
						'false' => 'Letiltva',
					),
					'type' => 'select',
					'defaultValue' => 'true',
				),
				'enableMultiSession' => array(
					'name' => 'Bejelentkezéskor a többi böngészőben maradjak bejelentkezve',
					'options' => array(
						'true' => 'Engedélyezve',
						'false' => 'Letiltva',
					),
					'type' => 'select',
					'defaultValue' => 'false',
				),
			),
		);

		static function Load($userid = null, $setToEnv = false){
			global $db, $user, $ENV;

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
				else{
					if ($setToEnv)
						$ENV['userSettings'] = self::$defaultValues;

					return self::$defaultValues;
				}
			}

			$data = $db->where('userid',$userid)->get('settings_user');

			$return = self::$defaultValues;
			foreach ($data as $a)
				$return[$a['category']][$a['key']] = $a['value'];


			if ($setToEnv)
				$ENV['userSettings'] = $return;

			return $return;
		}

		static private function _get($storage,$key){
			$keys = explode('.',$key,2);
			return $storage[$keys[0]][$keys[1]];
		}

		static function Get($key, $userid = null){
			global $ENV;

			return self::_get((empty($userid) ? $ENV['userSettings'] : self::Load($userid)),$key);
		}

		static function Apply($data){
			global $user, $db, $ENV;

			$userdb = $db->where('userid',$user['id'])->get('settings_user');
			$userSett = [];
			foreach ($userdb as $array)
				$userSett[$array['category']][$array['key']] = array($array['value'],$array['id']);


			foreach ($data as $k => $newValue){
				list($category, $key) = explode('_',$k,2);

				if (empty(self::$keys[$category][$key]))
					continue;

				if ($ENV['userSettings'][$category][$key] == $newValue)
					continue;

				// Alapértelmezett érték kipucolása
				if (self::$keys[$category][$key]['defaultValue'] === $newValue){
					if (!empty($userSett[$category][$key]))
						$db->where('id',$userSett[$category][$key][1])->delete('settings_user');
				}
				else {
					// Input ellenörzés
					switch (self::$keys[$category][$key]['type']){
						case "select":
							if (empty(self::$keys[$category][$key]['options'][$newValue]))
								return array(1, self::$keys[$category]['name'], self::$keys[$category][$key]['name']);
						break;
						case "time":
							$ts = strtotime($newValue);
							if ($ts === false)
								return array(1, self::$keys[$category]['name'], self::$keys[$category][$key]['name']);
							$newValue = date('H:i',$ts);
						break;
					}

					if (!empty($userSett[$category][$key]))
						$db->where('id',$userSett[$category][$key][1])->update('settings_user',array(
							'value' => $newValue,
						));
					else $db->insert('settings_user',array(
						'value' => $newValue,
						'category' => $category,
						'key' => $key,
						'userid' => $user['id'],
					));
				}
			}

			return 0;
		}
	}
