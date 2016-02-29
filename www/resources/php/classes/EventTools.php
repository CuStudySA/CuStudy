<?php

	class EventTools {
		static function GetEvents($start, $end){
			global $db, $user;

			$data = $db->where('classid', $user['class'][0])->get('events');

			$output = [];
			foreach ($data as $event){
				if (!(strtotime('12 am',strtotime($start)) < strtotime('12 am',strtotime($event['end']))
					&& strtotime('12 am',strtotime($event['start'])) < strtotime('12 am',strtotime($end))))
						continue;
				
				$output[] = array(
					'id' => $event['id'],
					'title' => $event['title'],
					'start' => $event['start'],
					'end' => $event['end'],
					'allDay' => (bool)$event['isallday'],
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

		/** @return int|array */
		static function Add($data){
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
				'isallday' => isset($data['isFullDay']),
			));

			return !is_int($action) ? 6 : 0;
		}

		static function GetEventInfos($id){
			global $db,$user;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('events');
			if (empty($data)) return 1;

			return array(
				'Esemény címe' => $data['title'],
				'Esemény kezdete' => date(!$data['isallday'] ? 'Y.m.d. H:i' : 'Y.m.d.',strtotime($data['start'])),
				'Esemény vége' => date(!$data['isallday'] ? 'Y.m.d. H:i' : 'Y.m.d.',strtotime($data['end'])),
				'Egész napos?' => $data['isallday'] ? 'igen' : 'nem',
				'Esemény leírása' => $data['description'],
			);
		}

		/** @return int|array */
		static function Edit($data){
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
				'isallday' => isset($data['isFullDay']),
			));

			return $action ? 0 : 6;
		}

		static function Delete($id){
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
				$Events = $db->where('start > NOW()')->where('classid',$user['class'][0])->orderBy('start', 'ASC')->get('events', 10);
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
				$time = $ev['isallday'] ? '' : date('H', $starttime).":$mp-$rag ";
				$append = '';
				if (!$sameMonthDay)
					$append .= HomeworkTools::FormatMonthDay($endtime);
				if (!$ev['isallday'])
					$append .= ' '.date('H:i',$endtime).'-ig';
				else if (!$sameMonthDay) $append .= '-ig';
				if (!empty($append))
					$time .= $append;
				if ($ev['isallday']){
					$time .= ', egész nap';
					$time = preg_replace('/^, eg/','Eg',$time);
				}

				$HTML .= "<li><div class='calendar'><span class='top'>{$start[0]}</span><span class='bottom'>{$start[1]}</span></div>".
					"<div class='meta'><span class='title'>{$ev['title']}</span><span class='time'>$time</span></div></li>";
			}
			echo $HTML.'</ul>';
		}
	}

