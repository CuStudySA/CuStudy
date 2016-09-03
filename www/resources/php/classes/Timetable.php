<?php

	class Timetable {
		static function GetNumberOfWeeks(){
			global $db,$user;

			$data = $db->rawQuery('SELECT COUNT(*) as weekcnt FROM (SELECT DISTINCT week FROM timetable WHERE classid = ?) t',array($user['class'][0]));

			return !empty($data[0]['weekcnt']) ? $data[0]['weekcnt'] : 1;
		}

		static function GetEdgesOfWeek($date){
			$ts = strtotime($date);
		    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);

		    return array(date('Y-m-d', $start),date('Y-m-d', strtotime('next saturday', $start)));
		}

		const OneDayInSeconds = 86400;
		const OneWeekInSeconds = 604800;
		static $weekLetters = 'ABCDEFGHIJKLMOPQRSTUVWXYZ';
		static function GetWeekLetter($timestamp = null){
			$weeekcnt = self::GetNumberOfWeeks();
			if ($weeekcnt === 1)
				return self::$weekLetters[0];

			if (empty($timestamp))
				$timestamp = time();

			$today = strtotime('today', $timestamp);
			$year = date('Y', $timestamp);
			// Ha még szept.1 előtt vagyunk akkor -1 év
			if ($today < strtotime("1 sept $year"))
				$year--;
			$sept1 = strtotime("1 sept $year");
			$sept1day = self::GetDay($sept1);
			// Ha szept. 1 hétfő/kedd/szerda/csüt. akkor az az első nap
			if ($sept1day < 5)
				$firstDay = strtotime('this week', $sept1);
			// Ha szept. 1 péntek/szombat/vas. akkor jövő hétfő az első nap
			else $firstDay = strtotime('next week', $sept1);

			$weeksPassed = floor(($today - $firstDay) / self::OneWeekInSeconds);
			return self::$weekLetters[$weeksPassed % $weeekcnt];
		}
		static function GetUpcomingWeek($weekLetter){
			$pos = stripos(self::$weekLetters,$weekLetter); //0
			$weekcnt = self::GetNumberOfWeeks(); //1
			if ($pos+1 >= min($weekcnt,strlen(self::$weekLetters)))
				$pos = 0;
			else $pos++;
			return self::$weekLetters[$pos];
		}
		/**
		 * Visszaadja a hét jelenlegi napjának számértékét 1-7 között
		 * (hétfő = 1, ..., vasárnap = 7)
		 *
		 * @param int|null $timestamp Lekérdezéshez használandó időpont
		 *
		 * @return int
		 */
		static function GetDay($timestamp = null) {
			$ts = date('w' ,!isset($timestamp) ? time() : $timestamp);
			return $ts == 0 ? 7 : (int)$ts;
		}

		// Órarend módosítások feldolgozása
		static function AddEntries($toAdd, $week){
			global $db,$user;

			$reqItems = ['day','lesson','tantargy','group'];
			foreach ($toAdd as $sub){
				if (empty($sub)) continue;
				foreach ($reqItems as $item)
					if (!isset($sub[$item])) return 2;

				foreach ($sub as $key => $value)
					if (System::InputCheck($value,'numeric')) return 3;

				$Entry = array(
					'classid' => $user['class'][0],
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

		static function DeleteEntries($toDelete){
			global $db,$user;

			foreach ($toDelete as $sub){
				if (!isset($sub['id'])) return 5;
				$id = $sub['id'];
				if (System::InputCheck($id,'numeric')) return 6;

				$action = $db->where('id',$id)->delete('timetable',$id);

				# Órarend-entryhez tartozó HW-k törlése
				$data = $db->where('classid',$user['class'][0])->where('lesson',$id)->get('homeworks');
				foreach ($data as $array)
					HomeworkTools::Delete($array['id']);

				if (!$action) return 7;
			}
		}

		static function ProcessTable($data){
			global $user;

			$action = self::_processTable($data);

			Logging::Insert(array_merge(array(
				'action' => 'timetables.progressTable',
				'errorcode' =>$action,
				'db' => 'timetable',

				'classid' => $user['class'][0],
			)));

			return $action;
		}

		static private function _processTable($data){
			# Jog. ellenörzése
			if (System::PermCheck('timetables.edit')) return 2;

			# Hét ellenörzése
			$week = strtolower($data['week']);
			if (!Timetable::ValidateWeek($week)) return 1;

			# Bejegyzések hozzáadása
			if(isset($data['add']))
				self::AddEntries($data['add'],$week);

			# Bejegyzések törlése
			if(isset($data['delete']))
				self::DeleteEntries($data['delete']);

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

		/**
		 * Hét betűjel érvényesség ellenörző
		 *
		 * @param string $week Hét betűjele
		 *
		 * @return bool [true] ha érvényes, [false] ha nem
		 */
		static function ValidateWeek($week){
			return isset(self::$TT_Types[strtolower($week)]);
		}

		static $TT_Types = array(
			'a' => "'A'",
			'b' => "'B'",
			//'c' => "'C'",
			//'d' => "'D'",
			//'e' => "'E'",
			//'f' => "'F'",
		);

		static function CalcDays(&$TT, $count, $output = false){
			$days = $TT['opt'];
			unset($TT['opt']);
			if (empty($TT))
				return null;
			sort($days,SORT_NUMERIC);
			$days = array_splice($days,0,$count);
			if ($output){
				$dcopy = array();
				foreach ($days as $k => $day)
					$dcopy[$k] = date('Y-m-d', $day);
				echo '<script>var _dispDays = '.json_encode($dcopy).'</script>';
			}
			return $days;
		}

		/**
		 * Régi nevén JSTimetable, léptatéskor fut le
		 *
		 * @param string|array $dispDays       Megjelenítendő dátum (string)/dátumok (tömb)
		 * @param bool         $allgroups      Minden csoport adatainak lekérdezése
		 * @param int|string   $move           Elmozdulás mennyisége napokban VAGY 'next'/'back' következő/előző hét
		 * @param bool         $dataAttributes data-* attribútumok kiiratása
		 */
		static function Step($dispDays, $allgroups = true, $move = null, $dataAttributes = false){
			if (is_array($dispDays))
				$dispDays = array_map('strtotime', $dispDays);
			else $dispDays = strtotime($dispDays);
			$date = is_array($dispDays) ? $dispDays[0] : $dispDays;

			$targetWeekdays = null;
			if (is_string($move)){
				$mult = $move === 'next' ? 1 : -1;
				$targetWeekdays = count($dispDays);
				$foundWeekdays = 0;
				$moved = min(0, $mult);
				$currentDate = $date;
				while (abs($foundWeekdays) < $targetWeekdays){
					$currentDate += self::OneDayInSeconds*$mult;
					$day = self::GetDay($currentDate);
					if ($day < 6)
						$foundWeekdays += $mult;
					$moved += $mult;
				}

				$date += self::OneDayInSeconds * $moved;
			}

			$today = strtotime('today');
			$switchOn = strtotime('8 am', $today);
			if ($date < $today)
				$date = $today;
			if (is_string($move) && ($date-$switchOn) < self::OneDayInSeconds)
				$date = strtotime('+1 day', $date);

			$week = (int)date('W', $date);
			$day = Timetable::GetDay($date);

			$TT = Timetable::Get($week,$day,$allgroups, is_string($move) ? $targetWeekdays : (is_numeric($move) ? (int)$move : 5));
			$days = Timetable::CalcDays($TT, is_numeric($move) ? (int)$move : 5);

			$timetable = Timetable::Render(null, $TT, $days, false, $dataAttributes);

			$firstDay = strtotime('midnight',$days[0]);
			$lockBack = ($firstDay-$switchOn) < self::OneDayInSeconds;

			foreach ($days as $k => $day)
				$days[$k] = date('Y-m-d', $day);

			System::Respond(array(
				'dispDays' => $days,
				'lockBack' => $lockBack,
				'timetable' => $timetable,
			));
		}

		/**
		 * Órarend lekérdező funkció
		 *
		 * @param int|null $week        Hétszám
		 * @param int|null $lastWeekDay A hét utolsó lekérdezendő napja
		 * @param bool     $allgroup    Összes csoport órarendjének lekérése
		 * @param int|null $maxDays     Maximum hány napot adjon vissza a függvény (null = nincs korlát)
		 *
		 * @return array
		 */
		static function Get($week = null, $lastWeekDay = null, $allgroup = true, $maxDays = null){
			global $user, $db;

			$now = time();
			$currDate = strtotime('today', $now);
			$switchOn = strtotime('8 am', $now);

			// Hiányzó értékek esetén jelenlegi dátum használata
			if (empty($week) && empty($lastWeekDay)){
				$week = (int)date('W');
				$lastWeekDay = self::GetDay()+(is_int($maxDays) ? $maxDays : 5);
				if ($lastWeekDay > 5)
					$lastWeekDay += 2;
			}

			// Megfelelő hétre ugrás
			$weeksPassed = (int)$week;
			$weeksPassedSeconds = self::OneWeekInSeconds * $weeksPassed;
			$thisYear = strtotime("first monday 1 jan", $currDate);
			$lastWeekdayDate = strtotime('this week', $thisYear + $weeksPassedSeconds);
			// Megfelelő napra ugrás
			if ($lastWeekDay > 1){
				if ($lastWeekDay > 5){
					if ($lastWeekDay < 8)
						$lastWeekDay = 5;
					else $lastWeekDay -= 5-(8-$lastWeekDay);
				}
				$lastWeekdayDate = strtotime('+'.($lastWeekDay-1).' days', $lastWeekdayDate);
			}
			$firstWeekdayDate = $lastWeekdayDate - self::OneWeekInSeconds;

			// Hét betűjele
			$currWeekLetter = strtolower(Timetable::GetWeekLetter($firstWeekdayDate));

			// Első lekérdezendő nap a héten
			$firstWeekday = self::GetDay($firstWeekdayDate);
			$switch = $currDate === $firstWeekdayDate && $now < $switchOn ? -1 : 0;
			if ($switch)
				$firstWeekday = $firstWeekday == 1 ? 5 : $firstWeekday+$switch;

			// Csoport azonosítók
			$groups = UserTools::GetClassGroupIDs();
			$onlyGrp = !$allgroup ? "&& groupid IN ($groups)" : '';

			if ($firstWeekday === 1){
				$ttentries = $db->rawQuery(
					"SELECT
						id, lesson, lessonid, day, week,
						(CASE
							WHEN groupid IS NOT NULL
							THEN (SELECT name FROM groups WHERE id = timetable.groupid)
							ELSE NULL
						END) as group_name
					FROM timetable
					WHERE classid = ? && week = ? $onlyGrp
					ORDER BY week, day, lesson",array($user['class'][0], $currWeekLetter));
			}
			else {
				// Következő hét betűjele
				$nextWeekLetter = self::GetUpcomingWeek($currWeekLetter);

				$tt_thisWeek = $db->rawQuery(
					"SELECT
						id, lesson, lessonid, day, week,
						(CASE
							WHEN groupid IS NOT NULL
							THEN (SELECT name FROM groups WHERE id = timetable.groupid)
							ELSE NULL
						END) as group_name
					FROM timetable
					WHERE classid = ? && day >= ? && week = ? $onlyGrp
					ORDER BY day, lesson", array($user['class'][0], $firstWeekday, $currWeekLetter));
				$tt_nextWeek = $db->rawQuery(
					"SELECT
						id, lesson, lessonid, day, week,
						(CASE
							WHEN groupid IS NOT NULL
							THEN (SELECT name FROM groups WHERE id = timetable.groupid)
							ELSE NULL
						END) as group_name
					FROM timetable
					WHERE classid = ? && day < ? && week = ? $onlyGrp
					ORDER BY day, lesson", array($user['class'][0], $firstWeekday, $nextWeekLetter));

				$ttentries = array_merge($tt_thisWeek,$tt_nextWeek);
			}

			$Timetable = array();

			$LessonCache = array();
			$reqDays = array();
			foreach ($ttentries as $entry){
				$lesson = $entry['lesson']-1;

				$LessonID = $entry['lessonid'];
				if (!isset($LessonCache[$LessonID]))
					$LessonCache[$LessonID] = $db->where('id',$LessonID)->getOne('lessons', 'id, name, color');

				$day = $entry['day']-1;
				if (!isset($reqDays[$day]))
					$reqDays[$day] = true;

				if (!isset($Timetable[$lesson]))
					$Timetable[$lesson] = array_fill(0,5,array());

				if (!empty($LessonCache[$LessonID]))
					$Timetable[$lesson][$day][] = array(
						'name' => $LessonCache[$LessonID]['name'],
						'bgcolor' => $LessonCache[$LessonID]['color'],
						'lid' => $LessonCache[$LessonID]['id'],
						'ttid' => $entry['id'],
						'group' => $allgroup ? $entry['group_name'] : '',
						'week' => $entry['week']
					);
			}

			if (!is_int($maxDays))
				$maxDays = 5;

			$days = [];
			$loopDate = $firstWeekdayDate;
			while (count($days) < $maxDays){
				if (self::GetDay($loopDate) < 6)
					$days[] = $loopDate;
				$loopDate += self::OneDayInSeconds;
			}

			if ($firstWeekday !== 1){
				$firstDayNumber = self::GetDay($days[0]);
				foreach ($Timetable as $k => $lesson){
					$move = array_splice($lesson,$firstDayNumber-1,count($lesson));
					$Timetable[$k] = array_merge($move, $lesson);
				}
			}
			if ($maxDays !== 5){
				foreach ($Timetable as $k => $lesson)
					$Timetable[$k] = array_splice($lesson, 0, $maxDays);
			}

			$Timetable['opt'] = $days;

			return $Timetable;
		}

		// Órarend lekérése
		static function GetForWeek($week, $allgroups = true){
			global $user, $db;

			# Formátum ellenörzése
			if (!self::ValidateWeek($week))
				throw new Exception('Érvénytelen hét');

			$groups = $db->where('classid', $user['class'][0])->get('groups','id,name');
			$grp_list = array(0 => '');
			foreach ($groups as $subg)
				$grp_list[$subg['id']] = $subg['name'];

			# Ha minden csoport adatait szeretnénk lekérni...
			if ($allgroups){
				$groupdata = $db->rawQuery(
					"SELECT DISTINCT g.id
					FROM group_members gm
					LEFT JOIN groups g ON gm.groupid = g.name
					WHERE gm.userid = ? && gm.classid = ?", array($user['id'], $user['class'][0]));
				$groupsstr = '0';
				foreach ($groupdata as $subgd)
					$groupsstr .= ','.$subgd['id'];
				$db->where("groupid IN ($groupsstr)");
			}

			# Lekérés végrehajtása
			$ttentries = $db
				->where('classid', $user['class'][0])
				->where('week', $week)
				->orderBy('week','ASC')
				->orderBy('day','ASC')
				->orderBy('lesson','ASC')
				->get('timetable');

			# Tömb feltötése üres adatokkal
			$Timetable = array_fill(0,8,array_fill(0,5,array()));

			# Órarend adatok rendezése
			$LessonCache = array();
			foreach ($ttentries as $entry){
				$lesson = $entry['lesson']-1;
				$day = $entry['day']-1;

				if (!isset($grp_list[$entry['groupid']]))
					continue;

				$LessonID = $entry['lessonid'];
				if (!isset($LessonCache[$LessonID]))
					$LessonCache[$LessonID] = $db->where('id',$LessonID)->getOne('lessons', 'id, name, color');

				if (!empty($LessonCache[$LessonID]))
					$Timetable[$lesson][$day][] = array(
						'name' => $LessonCache[$LessonID]['name'],
						'bgcolor' => $LessonCache[$LessonID]['color'],
						'ttid' => $entry['id'],
						'lid' => $LessonCache[$LessonID]['id'],
						'group' => $grp_list[$entry['groupid']]
					);
			}

			return $Timetable;
		}

		const MANAGE = true;
		//Órarend kirenderelése
		static function Render($week, $Timetable, $weekdays = null, $wrap = true, $dataAttributes = false){
			if (empty($weekdays) && empty($week)) return;

			if (is_array($weekdays)){
				$classCount = array();
				foreach ($Timetable as $k => $lesson){
					foreach ($lesson as $weekday => $class){
						if (!isset($classCount[$weekday]))
							$classCount[$weekday] = 0;
						$classCount[$weekday] += count($class);
					}
				}
				$emptyWeekdays = array();
				foreach ($classCount as $weekday => $count){
					if ($count === 0)
						$emptyWeekdays[$weekday] = true;
				}
				//var_dump($emptyWeekdays);
			}

			if (!empty($weekdays)){
				// Hetek kirenderelésének előkészítése
				$weeks = [];
				foreach ($weekdays as $weekday => $date){
					if (isset($emptyWeekdays[$weekday]))
						continue;
					$wNum = (int)date('W',$date);
					if (!isset($weeks[$wNum]))
						$weeks[$wNum] = array('colspan' => 0, 'letter' => Timetable::GetWeekLetter($date));
					$weeks[$wNum]['colspan']++;
				}
				ksort($weeks);
			}

			$HTML = $wrap ? "<table class='timet'>" : '';
			$HTML .= "<thead>";
			if (!empty($weeks)) {
				$HTML .= "<tr><th>H</th>";
				foreach ($weeks as $wNum => $array)
					$HTML .= "<th colspan='{$array['colspan']}'>{$array['letter']}. hét ($wNum. hét)</th>";
				$HTML .= "</tr>";
			}
			$HTML .= '<tr><th class="week">'.(empty($week) ? 'D' : strtoupper($week)).'</th>';
			if (empty($weekdays)){
				$HTML .=
					'<th class="weekday">Hétfő</th>'.
					'<th class="weekday">Kedd</th>'.
					'<th class="weekday">Szerda</th>'.
					'<th class="weekday">Csütörtök</th>'.
					'<th class="weekday">Péntek</th>';
			}
			else foreach ($weekdays as $weekday => $date){
				if (isset($emptyWeekdays[$weekday]))
					continue;
				$HTML .= '<th class="weekday">'.HomeworkTools::FormatMonthDay($date).' '.System::$Days[Timetable::GetDay($date)].'</th>';
			}
			$HTML .= "</tr></thead><tbody>";

			ksort($Timetable);
			foreach ($Timetable as $lessoncount => $lesson){
				$TR = '';
				foreach ($lesson as $weekday => $class){
					if (isset($emptyWeekdays[$weekday]))
						continue;
					$td = self::_RenderClass($class, $dataAttributes);
					if ($dataAttributes)
						$td = str_replace('<td>','<td data-week="'.date('Y\WW',$weekdays[$weekday]).'">',$td);

					$TR .= $td;
				}
				if (!empty($TR))
					$HTML .= '<tr class="lesson-field"><th>'.($lessoncount+1)."</th>$TR</tr>";
			}

			$HTML .= "</tbody></table>";
			if (isset($week) && !System::PermCheck('timetables.edit'))
				$HTML .= "<button class='btn sendbtn'>Módosítások mentése</button>";
			return $HTML;
		}

		// Órarend cella kirenderelő
		static private function _RenderClass($class, $delIcon = false){
			if (!empty($class)){
				$HTML = "<td>";
				if (!is_array($class))
					$class = array($class);
				foreach($class as $c){
					$name = $c['name'];
					if (!empty($c['group']))
						$name .= " ({$c['group']})";

					$ids = '';
					if (!empty($c['ttid']))
						$ids .= " data-ttid='{$c['ttid']}'";
					if (!empty($c['lid']))
						$ids .= " data-lid='{$c['lid']}'";
					$deleteIcon = $delIcon ? "<span class='del typcn typcn-times' $ids></span>" : '';
					$HTML .= "<span class='lesson' style='background: {$c['bgcolor']}'>$name$deleteIcon</span>";
				}
			}
			else $HTML = '<td class="empty">';
			$HTML .= '<span class="add typcn typcn-plus"></span>';
			return "$HTML</td>";
		}
	}
