<?php

	class AdminEventTools {
		static function Filter($data){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.events.view')) return 1;

			if (!empty($data['evt_isallday']))
				$db->where('isFullDay',1);
			if (!empty($data['evt_isactive']))
				$db->where('start <= NOW()')->where('NOW() < end');
			if (!empty($data['evt_startdate'])){
				$start_time = strtotime(trim($data['evt_startdate']));
				if ($start_time === false)
					System::Respond('Érvénytelen kezdeti dátum');
				$start = date('Y-m-d', $start_time);
				$db->where('DATE(start)',$start);
			}
			if (!empty($data['evt_enddate'])){
				$end_time = strtotime(trim($data['evt_enddate']));
				if ($end_time === false)
					System::Respond('Érvénytelen befejezési dátum');
				$end = date('Y-m-d', $end_time);
				$db->where('DATE(end)',$end);
			}
			if (isset($data['evt_isglobal']))
				$db->where('classid', 0);
			else if (!empty($data['evt_classid']) && is_numeric($data['evt_classid'])){
				$classid = intval($data['evt_classid'],10);

				if ($classid > 0){
					if (!$db->where('id',$classid)->has('class'))
						System::Respond('Nincs ilyen ID-jű osztály');

					$db->where('classid', $classid);
				}
			}

			return $db->get('events',null,'*, classid = 0 AS global');
		}

		static function Store($data){
			global $db;

			# Jog. ellenörzése
			if(System::PermCheck('system.events.view')) return 1;

			# Értékek ellenörzése
			if (!System::ValuesExists($data,['title','description','interval'])) return 2;

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

			$dates = EventTools::ParseDates($rangeParts[0],$rangeParts[1],isset($data['isFullDay']));
			if (!is_array($dates)) return 4;

			$update = array(
				'start' => date('c',$dates[0]),
				'end' => date('c',$dates[1]),
				'title' => $data['title'],
				'description' => $data['description'],
				'isFullDay' => isset($data['isFullDay']),
			);

			if (isset($data['isGlobal']))
				$update['classid'] = 0;
			else if (isset($data['classid']) && is_numeric($data['classid'])){
				$classid = intval($data['classid'],10);

				if ($classid > 0){
					if (!$db->where('id',$classid)->has('class'))
						System::Respond('Nincs ilyen ID-jű osztály');
				}
				else $classid = 0;

				$update['classid'] = $classid;
			}

			$action = isset($data['id'])
				? $db->where('id',$data['id'])->update('events', $update)
				: $db->insert('events', $update);

			return $action ? 0 : 5;
		}

		static function Delete($event){
			global $db;

			# Jog. ellenörzése
			if(System::PermCheck('system.events.view')) return 1;

			if (!$db->where('id', $event['id'])->delete('events'))
				return 2;

			return 0;
		}
	}
