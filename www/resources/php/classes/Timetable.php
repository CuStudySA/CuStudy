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
			// Ha szept. 1 hétfő/kedd/szerda akkor az az első nap
			if ($sept1day < 4)
				$firstDay = strtotime('this monday', $sept1);
			// Ha szept. 1 csüt./péntek/szombat/vas. akkor jövő hétfő az első nap
			else $firstDay = strtotime('next monday', $sept1);

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
			$ts = date('w' ,empty($timestamp) ? time() : $timestamp);
			return $ts == 0 ? 7 : $ts;
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

		static function CalcTimetableDays(&$TT, $count, $output = false){
			$days = $TT['opt'];
			unset($TT['opt']);
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

		static function JSTimetable($dispDays, $allgroups = true, $move = null){
			if (is_array($dispDays))
				$dispDays = array_map('strtotime', $dispDays);
			else $dispDays = strtotime($dispDays);

			$today = strtotime('today');
			if (!is_string($move))
				$date = strtotime('- 1 day',is_array($dispDays) ? $dispDays[0] : $dispDays);
			else {
				$mult = $move === 'next' ? 1 : -1;
				$targetWeekdays = count($dispDays);
				$foundWeekdays = 0;
				$moved = -1;
				$startDate =
				$currentDate = $dispDays[0];
				while (abs($foundWeekdays) < $targetWeekdays){
					$currentDate += self::OneDayInSeconds*$mult;
					$day = self::GetDay($currentDate);
					if ($day < 6)
						$foundWeekdays += $mult;
					$moved += $mult;
				}

				$date = $startDate + (self::OneDayInSeconds*$moved);
			}

			if (self::GetDay($date) >= 5)
				$date = strtotime('next monday', $date);

			if ($date < $today)
				$date = $today;

			$week = (int)date('W', $date);
			$day = Timetable::GetDay($date);

			$TT = Timetable::GetHWTimeTable($week,$day,$allgroups);
			$days = self::CalcTimetableDays($TT, is_numeric($move) ? (int)$move : 5);

			$timetable = Timetable::Render(null, $TT, $days, false);

			$firstDay = strtotime('midnight',$days[0]);
			$lockBack = $firstDay-60*60*24 <= $today || strtotime('+ '.(8-Timetable::GetDay()).' days',$today) == $firstDay;

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
		 * @param int|null $week     Hétszám
		 * @param int|null $lastDay  A hét utolsó lekérdezendő napja
		 * @param bool     $allgroup Összes csoport órarendjének lekérése
		 *
		 * @return array
		 */
		static function GetHWTimeTable($week = null, $lastDay = null, $allgroup = true){
			global $user, $db;

			$addon = array($user['class'][0]);
			$ma_éjfél = strtotime('midnight');
			$currWeek = (int)date('W', $ma_éjfél);
			$currDay = Timetable::GetDay($ma_éjfél);

			$now = time();
			$switchOn = strtotime('8 am', $now);
			$switch = $now > $switchOn ? 0 : -1;

			if (!empty($week) && !empty($lastDay)){
				// Jelenlegi dátumhoz hozzáadja a {cél hét - mostani hét} értékét
				// pl. (10. hét /cél/ - 15. hét /most/) => -5 hét a mostani dátumhoz képest
				//     (12. hét /cél/ - 10. hét /most/) => +2 hét a mostani dátumhoz képest
				$weekday = strtotime('+ '.($week - $currWeek).' weeks', $ma_éjfél);

				// Ellenörzi, hogy a jelenlegi hét napja korábban van, mint a lekérdezett utolsó nap
				//   (azaz a lekérdezés a jelenlegi héten belül marad-e)
				// pl. (hétfő (1) /ma/ < péntek (5) /cél/) => igaz
				//     (péntek (5) /ma/ < kedd (2) /cél/) => hamis
				if ($currDay < $lastDay)
					// Ha a lekérdezés a jelenlegi héten belül marad, hozzáadja a dátumhoz
					//   a {cél nap - mai nap} értékét
					// pl. (péntek (5) /cél/ - hétfő (1) /ma/) => +4 nap
					$weekday = strtotime('+ '.($lastDay - $currDay).' days',$weekday);
				// Ha a lekérdezés átcsúszik a következő hétre, kivonja a dátumból
				//   a {mai nap - cél nap} értékét
				// pl. (péntek (5) /ma/ - kedd (2) /cél/) => -3 nap
				else $weekday = strtotime('- '.($currDay - $lastDay).' days',$weekday);

				//var_dump(date("Y-m-d H:i:s D (W. \\hé\\t)", $weekday));

				// Hét betűjelének lekérése
				$actWeek = strtolower(Timetable::GetWeekLetter($weekday));
				$addon = array_merge($addon,[
					$actWeek,
					$lastDay,
					self::GetUpcomingWeek($actWeek)
				]);
				$dayOfWeek = Timetable::GetDay($lastDay);
				$switchedDay = $dayOfWeek+$switch;
				if ($switch === -1 && $dayOfWeek === 1)
					$switchedDay = 7;
			}
			else {
				$weekday = $ma_éjfél;
				$actWeek = strtolower(Timetable::GetWeekLetter($weekday));

				$dayOfWeek = Timetable::GetDay();
				$switchedDay = $dayOfWeek+$switch;
				if ($switch === -1 && $dayOfWeek === 1)
					$switchedDay = 7;
				$addon = array_merge($addon,[
					$actWeek,
					$switchedDay,
					self::GetUpcomingWeek($actWeek)
				]);
			}

			$groups = UserTools::GetClassGroupIDs();
			$onlyGrp = !$allgroup ? "&& tt.groupid IN ($groups)" : '';

			$weekcnt = Timetable::GetNumberOfWeeks();
			switch ($weekcnt){
				case 1:
					/*
					$db->rawQuery("SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
								FROM timetable tt
								LEFT JOIN lessons l
								ON (l.id = tt.lessonid && l.classid = tt.classid)
								WHERE tt.classid = ? $whereString $onlyGrp
								ORDER BY tt.week, tt.day, tt.lesson",$addon);
					*/
					$ttentries = $db->rawQuery(
						"SELECT tt.id, tt.lesson, tt.lessonid, tt.day, tt.week, (SELECT name FROM groups WHERE id = tt.groupid) as group_name
						FROM timetable tt
						WHERE tt.classid = ? && ((tt.week = ? && tt.day > ?) || tt.week = ?) $onlyGrp
						ORDER BY tt.week, tt.day, tt.lesson", $addon);
				break;
				case 2:
					/*
						SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
						FROM timetable tt
						LEFT JOIN lessons l
						ON (l.id = tt.lessonid && l.classid = tt.classid)
						WHERE tt.classid = ? && tt.day > ? $onlyGrp
						ORDER BY tt.day, tt.lesson"

						array($user['class'][0], $dayOfWeek+$switch)
					*/
					$tt_thisWeek = $db->rawQuery(
						"SELECT tt.id, tt.lesson, tt.lessonid, tt.day, tt.week, (SELECT name FROM groups WHERE id = tt.groupid) as group_name
						FROM timetable tt
						WHERE tt.classid = ? && tt.day > ? $onlyGrp
						ORDER BY tt.day, tt.lesson", array($user['class'][0], $switchedDay));
					/*
						SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
						FROM timetable tt
						LEFT JOIN lessons l
						ON (l.id = tt.lessonid && l.classid = tt.classid)
						WHERE tt.classid = ? && tt.day < ? $onlyGrp
						ORDER BY tt.day, tt.lesson"

						array($user['class'][0])
					*/
					$tt_nextWeek = $db->rawQuery(
						"SELECT tt.id, tt.lesson, tt.lessonid, tt.day, tt.week, (SELECT name FROM groups WHERE id = tt.groupid) as group_name
						FROM timetable tt
						WHERE tt.classid = ? && tt.day < ? $onlyGrp
						ORDER BY tt.day, tt.lesson", array($user['class'][0], $switchedDay));

					$ttentries = array_merge($tt_thisWeek,$tt_nextWeek);
				break;
				default:
					$ttentries = null;
					trigger_error('Nem támogatott hétszám', E_USER_ERROR);
			}

			$Timetable = array();

			$days = [];
			$LessonCache = array();
			foreach ($ttentries as $entry){
				$lesson = $entry['lesson']-1;
				$day = $entry['day']-1;

				if ($actWeek == $entry['week']){
					if ($entry['day'] <= $dayOfWeek){
						// FIXME Így csak mx 2 hetet kezel
						if ($weekcnt === 2)
							$date = strtotime('+ '.(14 + $entry['day']).' days', $weekday);
						else $date = strtotime('+ '.((7 - $dayOfWeek) + $entry['day']).' days', $weekday);
					}
					else $date = strtotime('+ '.($entry['day'] - $dayOfWeek).' days',$weekday);
				}
				else $date = strtotime('+ '.(7 - $dayOfWeek + $entry['day']).' days',$weekday);

				if (array_search($date,$days) === false)
					$days[] = $date;

				$LessonID = $entry['lessonid'];
				if (!isset($LessonCache[$LessonID]))
					$LessonCache[$LessonID] = $db->where('id',$LessonID)->getOne('lessons', 'id, name, color');

				if (!empty($LessonCache[$LessonID]))
					$Timetable[$lesson][$day][] = array(
						'name' => $LessonCache[$LessonID]['name'],
						'bgcolor' => $LessonCache[$LessonID]['color'],
						'lid' => $LessonCache[$LessonID]['id'],
						'group' => $allgroup ? $entry['group_name'] : '',
						'week' => date('W',$date)
					);
			}
			$Timetable['opt'] = $days;

			return $Timetable;
		}

		// Órarend lekérése
		static function GetTimeTable($week, $allgroups = true){
			global $user, $db;

			# Formátum ellenörzése
			if (!self::ValidateWeek($week))
				throw new Exception('Érvénytelen hét');

			# Órarend lekérés előkészítése
			$query = "SELECT
				tt.*,
				l.name,	l.color,
				(SELECT short FROM teachers t WHERE t.id = l.teacherid) as teacher
			FROM timetable tt
			LEFT JOIN lessons l ON (l.id = tt.lessonid && l.classid = ?)
			WHERE tt.classid = ? && tt.week = ?";

			# Órarend lekérés segédtömb elékészítése
			$data = array($user['class'][0],$user['class'][0],$week);

			$groupdata = $db->rawQuery(
				"SELECT DISTINCT g.id
				FROM group_members gm
				LEFT JOIN groups g ON gm.groupid = g.name
				WHERE gm.userid = ? && gm.classid = ?", array($user['id'], $user['class'][0]));

			# Ha minden csoport adatait szeretnénk lekérni...
			if ($allgroups){
				$groups = '0';
				foreach ($groupdata as $subgd)
					$groups .= ','.$subgd['id'];
				$query .= " && groupid IN ($groups)";
			}

			$groups = $db->where('classid', $user['class'][0])->get('groups','id,name');
			$grp_list = array(0 => '');
			foreach ($groups as $subg)
				$grp_list[$subg['id']] = $subg['name'];

			# Plusz adatok hozzáadása a lekéréshez
			$query .= ' ORDER BY tt.week, tt.day, tt.lesson';

			# Lekérés végrehajtása
			$data = $db->rawQuery($query,$data);

			# Tömb feltötése üres adatokkal
			$Timetable = array_fill(0,8,array_fill(0,1,array()));

			# Órarend adatok rendezése
			foreach ($data as $class){
				$lesson = $class['lesson']-1;
				$weekday = $class['day']-1;
				if (isset($class['name'])){
					if (!isset($grp_list[$class['groupid']])) continue;
					$Timetable[$lesson][$weekday][] = array(
						'name' => $class['name'],
						'teacher' => $class['teacher'], // FIXME Kell-e ez?
						'bgcolor' => $class['color'],
						'lid' => $class['id'],
						'group' => $grp_list[$class['groupid']]
					);
				}
			}

			return $Timetable;
		}

		const MANAGE = true;
		//Órarend kirenderelése
		static function Render($week, $Timetable, $weekdays = null, $wrap = true){
			if (empty($weekdays) && empty($week)) return;
			if (!empty($weekdays)){
				// Hetek kirenderelésének előkészítése
				$weeks = [];
				foreach ($weekdays as $day){
					$wNum = (int)date('W',$day);
					if (array_search($wNum,array_keys($weeks)) === false)
						$weeks[$wNum] = array(1, Timetable::GetWeekLetter($day));
					else $weeks[$wNum][0]++;
				}
				ksort($weeks);
			}

			$HTML = $wrap ? "<table class='timet'>" : '';
			$HTML .= "<thead>";
			if (!empty($weeks)) {
				$HTML .= "<tr><th>H</th>";
				foreach ($weeks as $key => $array){
					$HTML .= "<th colspan='$array[0]'>{$array[1]}. hét ({$key}. hét)</th>";
				}
				$HTML .= "</tr>";
			}
			$HTML .= '<tr><th class="week">'.(empty($week) ? 'D' : strtoupper($week)).'</th>';
			if (empty($weekdays)) {
				$HTML .=
					'<th class="weekday">Hétfő</th>'.
					'<th class="weekday">Kedd</th>'.
					'<th class="weekday">Szerda</th>'.
					'<th class="weekday">Csütörtök</th>'.
					'<th class="weekday">Péntek</th>';
			}
			else foreach ($weekdays as $day)
				$HTML .= '<th class="weekday">'.HomeworkTools::FormatMonthDay($day).' '.System::$Days[Timetable::GetDay($day)].'</th>';
			$HTML .= "</tr></thead><tbody>";

			$maxls = empty($Timetable) ? 8 : max(8, count($Timetable));
			$maxwd = empty($weekdays) ? 5 : count($weekdays);
			for ($lesson = 0; $lesson < $maxls; $lesson++){
				if (empty($Timetable[$lesson]))
					continue;
				$TR = '<tr class="lesson-field"><th>'.($lesson+1).'</th>';
				for ($weekday = 0; $weekday < $maxwd; $weekday++)
					$TR .= self::_RenderClass(@$Timetable[$lesson][$weekday]);
				$HTML .= "$TR</tr>";
			}

			$HTML .= "</tbody></table>";
			if (isset($week) && !System::PermCheck('timetables.edit'))
				$HTML .= "<button class='btn sendbtn'>Módosítások mentése</button>";
			return $HTML;
		}

		// Órarend cella kirenderelő
		static private function _RenderClass($class){
			if (!empty($class)){
				$HTML = "<td>";
				if (!is_array($class))
					$class = array($class);
				foreach($class as $c){
					$name = $c['name'];
					if (!empty($c['group']))
						$name .= " ({$c['group']})";

					$week = isset($c['week']) ? "data-week='{$c['week']}'" : '';
					$deleteIcon = "<span class='del typcn typcn-times' data-id='{$c['lid']}'></span>";
					$HTML .= "<span class='lesson' $week style='background: {$c['bgcolor']}'>$name$deleteIcon</span>";
				}
			}
			else $HTML = '<td class="empty">';
			$HTML .= '<span class="add typcn typcn-plus"></span>';
			return "$HTML</td>";
		}
	}
