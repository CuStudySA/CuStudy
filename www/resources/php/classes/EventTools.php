<?php

	class EventTools {
		static function GetEvents($start, $end, $global = false){
			global $db, $user;

			$start = date('c',strtotime($start));
			$end = date('c',strtotime($end));

			$data = $db->rawQuery('SELECT *
									FROM `events`
									WHERE start <= ? && end >= ? && classid = ?',array($end,$start,$global ? 0 : $user['class'][0]));

			$output = [];
			foreach ($data as $event){
				$allday = (bool)$event['isFullDay'];

				$endtime = strtotime($event['end']);
				// A befejezés időpontja exklúzív, ezért ki kell bővíteni, ha megfelelően akarjuk, hogy megjelenjen
				if ($allday)
					$endtime = strtotime('+1 day', $endtime);
				else $endtime = strtotime('+1 second', $endtime);

				$starttime = strtotime($event['start']);

				$output[] = array(
					'id' => $event['id'],
					'title' => $event['title'],
					'start' => date('c',$starttime),
					'end' => date('c',$endtime),
					'allDay' => $allday,
				);
			}

			return $output;
		}

		static function ParseDates($start,$end,$allDay){
			$regex = '/^(\d{4})\.(\d{2})\.(\d{2})\.?(?: (\d{2})\:(\d{2})(\:(?:\d{2}))?)?$/';
			$replace = '$1-$2-$3'.(!$allDay?' $4:$5$6':'');
			$start = strtotime(preg_replace($regex,$replace,trim($start)));
			if ($start === false) return false;

			$end = strtotime(preg_replace($regex,$replace,trim($end)));
			if ($end === false) return false;

			return [$start,$end];
		}

		static function Add($data){
			global $user;

			$action = self::_add($data);
			$data = System::TrashForeignValues(['interval','isFullDay','title','description'],$data);

			Logging::Insert(array_merge(array(
				'action' => 'events.add',
				'errorcode' => is_array($action) ? 0 : $action,
				'db' => 'events',
			),$data,is_array($action) ? array(
				'e_id' => $action[0],
			) : array(),array(
				'classid' => $user['class'][0],
			)));

			return is_array($action) ? 0 : $action;
		}

		/** @return int|array */
		static private function _add($data){
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
				if (System::InputCheck($value,$type)) return [3, $key];
			}

			# Dátum értelmezése
			$range = trim($data['interval']);
			$rangeParts = explode('~',$range);
			if (count($rangeParts) != 2) return 4;

			$dates = self::ParseDates($rangeParts[0],$rangeParts[1],isset($data['isFullDay']));
			if (!is_array($dates)) return 5;

			$action = $db->insert('events',array(
				'classid' => $user['class'][0],
				'start' => date('c',$dates[0]),
				'end' => date('c',$dates[1]),
				'title' => $data['title'],
				'description' => $data['description'],
				'isFullDay' => isset($data['isFullDay']),
			));

			return !is_int($action) ? 6 : [$action];
		}

		static function GetEventInfos($id){
			global $db,$user;

			$data = $db->where('id',$id)->getOne('events');
			if (empty($data) || ($data['classid'] !== 0 && $data['classid'] !== $user['class'][0])) return 1;

			return array(
				'Esemény címe' => $data['title'],
				'Esemény kezdete' => date(!$data['isFullDay'] ? 'Y.m.d. H:i' : 'Y.m.d.',strtotime($data['start'])),
				'Esemény vége' => date(!$data['isFullDay'] ? 'Y.m.d. H:i' : 'Y.m.d.',strtotime($data['end'])),
				'Egész napos?' => $data['isFullDay'] ? 'igen' : 'nem',
				'Esemény leírása' => $data['description'],
			);
		}

		/** @return int|array */
		static private function _edit($data){
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
				if (System::InputCheck($value,$type)) return [3, $key];
			}

			# Dátum értelmezése
			$range = trim($data['interval']);
			$rangeParts = explode('~',$range);
			if (count($rangeParts) != 2) return 4;

			$dates = self::ParseDates($rangeParts[0],$rangeParts[1],isset($data['isFullDay']));
			if (!is_array($dates)) return 5;

			$action = $db->where('id',$data['id'])->update('events',array(
				'start' => date('c',$dates[0]),
				'end' => date('c',$dates[1]),
				'title' => $data['title'],
				'description' => $data['description'],
				'isFullDay' => isset($data['isFullDay']),
			));

			return $action ? 0 : 6;
		}

		static function Edit($data){
			global $user;

			$action = self::_edit($data);
			$data = System::TrashForeignValues(['interval','isFullDay','title','description','id'],$data);

			if (!empty($data['id'])){
				$data['e_id'] = $data['id'];
				unset($data['id']);
			}

			Logging::Insert(array_merge(array(
				'action' => 'events.edit',
				'errorcode' => $action,
				'db' => 'events',
			),$data));

			return $action;
		}

		static function Delete($id){
			global $user, $db;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('events');

			if (!empty($data))
				$data = System::TrashForeignValues(['start','isFullDay','title','description','end'],$data);
			else
				$data = [];

			$action = self::_delete($id);

			Logging::Insert(array_merge(array(
				'action' => 'events.delete',
				'errorcode' => $action,
				'db' => 'events',

				'e_id' => $id,
			),$data));

			return $action;
		}

		static private function _delete($id){
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
				$Events = $db->where('start > NOW()')->where("classid IN ({$user['class'][0]},0)")->orderBy('start', 'ASC')->get('events', 10);
			}
			if (empty($Events)) return;

			$HTML = '<h3>Fontos dátumok</h3><ul id="events">';
			foreach ($Events as $i => $ev){
				$starttime = strtotime($ev['start']);
				$start = array(System::$ShortMonths[intval(date('n', $starttime))], date('j', $starttime));
				$endtime = strtotime($ev['end']);
				$end = array(System::$ShortMonths[intval(date('n', $endtime))], date('j', $endtime));

				$sameMonthDay = $start[0] == $end[0] && $start[1] == $end[1];
				$mp = date('i',  $starttime);
				$mpint = intval($mp, 10);
				$rag = ($mpint !== 10 && in_array($mpint % 10, [0,3,6,8])) ? 'tól': 'től';
				$time = $ev['isFullDay'] ? '' : date('H', $starttime).":$mp-$rag ";
				$append = '';
				if (!$sameMonthDay)
					$append .= HomeworkTools::FormatMonthDay($endtime);
				if (!$ev['isFullDay'])
					$append .= ' '.date('H:i',$endtime).'-ig';
				else if (!$sameMonthDay) $append .= '-ig';
				if (!empty($append))
					$time .= $append;
				if ($ev['isFullDay']){
					$time .= ', egész nap';
					$time = preg_replace('/^, eg/','Eg',$time);
				}

				$HTML .= "<li><div class='calendar'><span class='top'>{$start[0]}</span><span class='bottom'>{$start[1]}</span></div>".
					"<div class='meta'><span class='title'>{$ev['title']}</span><span class='time'>$time</span></div></li>";
			}
			echo $HTML.'</ul>';
		}
	}

