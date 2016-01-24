<?php

	class Timetable {
		static function GetWeekNum(){
			$dateObj = new DateTime();
			return $dateObj->format("W");
		}

		static function GetNumberOfWeeks(){
			global $db,$user;

			$data = $db
				->where('classid', $user['class'][0])
				->where('week', 'b')
				->has('timetable');

			return empty($data) ? 1 : 2;
		}

		static function GetEdgesOfWeek($date){
			$ts = strtotime($date);
		    $start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);

		    return array(date('Y-m-d', $start),date('Y-m-d', strtotime('next saturday', $start)));
		}

		static function GetActualWeek($sorting = false, $timestamp = null){
			global $ENV,$db,$user;

			if (empty($timestamp))
				$timestamp = time();

			$data = $db->rawQuery('SELECT *
									FROM `timetable`
									WHERE `classid` = ? && `week` = ?',array($user['class'][0],'b'));
			if (empty($data))
				return $sorting ? 'ASC' : 'A';

			$weekNum = date('W',$timestamp);

			$tsyear = date('Y',$timestamp);

			$jan1 = strtotime("1 jan $tsyear");
			$aug31 = strtotime("1 sept $tsyear");

			$start = strtotime('+7 days',strtotime('this week', $jan1));
			$end = strtotime('+7 days',strtotime('this week', $aug31));

			$yearPassed = $timestamp >= $start && $timestamp < $end;

			if (!$sorting){
				if ($ENV['class']['pairweek'] === 'A'){
					if ($weekNum % 2 == 0)
						return !$yearPassed ? 'A' : 'B';
					else
						return !$yearPassed ? 'B' : 'A';
				}
				else {
					if ($weekNum % 2 == 0)
						return !$yearPassed ? 'B' : 'A';
					else
						return !$yearPassed ? 'A' : 'B';
				}
			}
			else {
				if ($ENV['class']['pairweek'] === 'A'){
					if ($weekNum % 2 == 0)
						return !$yearPassed ? 'ASC' : 'DESC';
					else
						return !$yearPassed ? 'DESC' : 'ASC';
				}
				else {
					if ($ENV['class']['pairweek'] === 'A'){
						if ($weekNum % 2 == 0)
							return !$yearPassed ? 'DESC' : 'ASC';
						else
							return !$yearPassed ? 'ASC' : 'DESC';
					}
				}
			}
		}

		static function GetDayNumber($timestamp = null) {
			$ts = date('w' ,empty($timestamp) ? time() : $timestamp);
			return $ts == 0 ? 7 : $ts;
		}

		// Órarend módosítások feldolgozása
		static function AddEntrys($toAdd,$week){
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

		static function DeleteEntrys($toDelete){
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

		static function ProgressTable($data){
			# Jog. ellenörzése
			if (System::PermCheck('timetables.edit')) return 2;

			# Hét ellenörzése
			$week = strtolower($data['week']);
			if (!in_array($week,['a','b'])) return 1;

			# Bejegyzések hozzáadása
			if(isset($data['add']))
				self::AddEntrys($data['add'],$week);

			# Bejegyzések törlése
			if(isset($data['delete']))
				self::DeleteEntrys($data['delete']);

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

		static $TT_Types = array(
			'a' => "'A'",
			'b' => "'B'",
		);

		static function MoveNextBack($move,$dispDays,$showAllGroups = true){
			$numberOfDays = count($dispDays);

			if ($move == 'next') $fromDate = $dispDays[count($dispDays)-1];
			else $fromDate = strtotime("- {$numberOfDays} days",$dispDays[0]);

			$dates = [];

			while(count(array_diff($dates,$dispDays)) != count($dispDays)){
				$day = Timetable::GetDayNumber($fromDate);
				$week = Timetable::GetActualWeek(false,$fromDate);

				$TT = Timetable::GetHWTimeTable(date('W',$fromDate),$day,$showAllGroups);

				$dates = $TT['opt'];
				unset($TT['opt']);

				sort($dates,SORT_NUMERIC);
				$dates = array_splice($dates,0,$numberOfDays);

				$fromDate = strtotime(($move == 'next' ? '+' : '-').' 1 days',$fromDate);
			}

			Timetable::Render(null, $TT, $dates);
			$fDate = strtotime('12 am',$dates[0]);
			$now = strtotime('12 am');

			if (strtotime('- 1 days',$fDate) == $now) $lockBack = true;
			else if (Timetable::GetDayNumber() == 6 && strtotime('+ 2 days',$now) == $fDate) $lockBack = true;
			else if (Timetable::GetDayNumber() == 7 && strtotime('+ 1 days',$now) == $fDate) $lockBack = true;
			else $lockBack = false;

			?>
			<span class='dispDays'><?=json_encode($dates)?></span>
			<span class='lockBack'><?=json_encode($lockBack)?></span>
<?php	}

		static function MoveDate($date,$numberOfDays = 3,$showAllGroups = true){
			$date = strtotime('- 1 days',strtotime($date));

			$week = date('W',$date);
			$day = Timetable::GetDayNumber($date);

			$TT = Timetable::GetHWTimeTable($week,$day,$showAllGroups);

			$dates = $TT['opt'];
			unset($TT['opt']);

			sort($dates,SORT_NUMERIC);
			$dates = array_splice($dates,0,$numberOfDays);

			Timetable::Render(null, $TT, $dates);

			$fDate = strtotime('12 am',$dates[0]);
			$now = strtotime('12 am');

			if ($fDate == $now) $lockBack = true;
			else if (Timetable::GetDayNumber() == 6 && strtotime('+ 2 days',$now) == $fDate) $lockBack = true;
			else if (Timetable::GetDayNumber() == 7 && strtotime('+ 1 days',$now) == $fDate) $lockBack = true;
			else $lockBack = false;

			?>
			<span class='dispDays'><?=json_encode($dates)?></span>
			<span class='lockBack'><?=json_encode($lockBack)?></span>
<?php	}

		static function SwitchView($fromDate,$allgroup = true){
			$fromDate = strtotime('- 1 days',$fromDate);
			$day = Timetable::GetDayNumber($fromDate);

			$TT = Timetable::GetHWTimeTable(date('W',$fromDate),$day,$allgroup);

			$days = $TT['opt'];
			unset($TT['opt']);

			sort($days,SORT_NUMERIC);
			$days = array_splice($days,0,5);

			Timetable::Render(null, $TT, $days);

			$fDate = strtotime('12 am',$days[0]);
			$now = strtotime('12 am');

			if ($fDate == $now) $lockBack = true;
			else if (Timetable::GetDayNumber() == 6 && strtotime('+ 2 days',$now) == $fDate) $lockBack = true;
			else if (Timetable::GetDayNumber() == 7 && strtotime('+ 1 days',$now) == $fDate) $lockBack = true;
			else $lockBack = false;

			?>
			<span class='dispDays'><?=json_encode($days)?></span>
			<span class='lockBack'><?=json_encode($lockBack)?></span>
<?php	}

		static function GetHWTimeTable($week = null, $lastDay = null, $allgroup = true){
			global $user, $db;

			$addon = array($user['class'][0]);

			if (!empty($week) && !empty($lastDay)){
				$weekday = strtotime('+ '.($week - date('W')).' weeks', strtotime('12 am'));
				if (Timetable::GetDayNumber() < $lastDay) $weekday = strtotime('+ '.($lastDay - Timetable::GetDayNumber()).' days',$weekday);
				else $weekday = strtotime('- '.(Timetable::GetDayNumber() - $lastDay).' days',$weekday);
				$actWeek = strtolower(Timetable::GetActualWeek(false,$weekday));
				$addon = array_merge($addon,[$actWeek, $lastDay, $actWeek == 'a' ? 'b' : 'a']);
				$dayInWeek = $lastDay;
				$hour = $minute = 9;
			}
			else {
				$minute = (int)date('i');
				$hour = (int)date('H');

				$weekday = time();

				$addon = array_merge($addon,[self::GetActualWeek(),
							$hour >= 8 && $minute >= 0 ? self::GetDayNumber() : self::GetDayNumber()-1,
							strtolower(self::GetActualWeek()) == 'a' ? 'b' : 'a']);

				$actWeek = strtolower(Timetable::GetActualWeek());
				$dayInWeek = Timetable::GetDayNumber();
			}

			$dualWeek = Timetable::GetNumberOfWeeks() == 1 ? false : true;

			$userInGroups = $db->rawQuery('SELECT `groupid`
											FROM `group_members`
											WHERE `classid` = ? && `userid` = ?',array($user['class'][0],$user['id']));
			$groups = array(0);
			foreach ($userInGroups as $array)
				$groups[] = $array['groupid'];

			if (!$allgroup)
				$onlyGrp = '&& tt.groupid IN ('.implode(',',$groups).')';
			else
				$onlyGrp = '';

			if ($dualWeek){
				$whereString = "&& ((tt.week = ? && tt.day > ?) || tt.week = ?)";
				$data = $db->rawQuery("SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
							FROM timetable tt
							LEFT JOIN lessons l
							ON (l.id = tt.lessonid && l.classid = tt.classid)
							WHERE tt.classid = ? $whereString $onlyGrp
							ORDER BY tt.week, tt.day, tt.lesson ASC",$addon);
			}
			else {
				$data_onWeek = $db->rawQuery("SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
											FROM timetable tt
											LEFT JOIN lessons l
											ON (l.id = tt.lessonid && l.classid = tt.classid)
											WHERE tt.classid = ? && tt.day > ? $onlyGrp
											ORDER BY tt.day, tt.lesson"
									,array($user['class'][0],$hour >= 8 && $minute >= 0 ? $dayInWeek : $dayInWeek-1));

				$data_nextWeek = $db->rawQuery("SELECT l.name, l.color, tt.id, tt.lesson, tt.day, tt.week, (SELECT `name` FROM `groups` WHERE `id` = tt.groupid) as group_name
											FROM timetable tt
											LEFT JOIN lessons l
											ON (l.id = tt.lessonid && l.classid = tt.classid)
											WHERE tt.classid = ? $onlyGrp
											ORDER BY tt.day, tt.lesson"
									,array($user['class'][0]));

				$data_nW = array();
				foreach ($data_nextWeek as $array){
					$nextD = $hour >= 8 && $minute >= 0;
					$if = $dayInWeek == 1 ? ($nextD ? $dayInWeek : 7) : ($nextD ? $dayInWeek : $dayInWeek - 1);

					if ($array['day'] <= $if)
						$data_nW[] = $array;
				}

				$data = array_merge($data_onWeek,$data_nW);
			}

			$Timetable = array_fill(0,8,array_fill(0,1,array()));

			$days = [];

			//var_dump(date('Y-m-d',$weekday));
			foreach ($data as $class){
				$lesson = $class['lesson']-1;

				if ($actWeek == $class['week']){
					if ($class['day'] <= $dayInWeek)
						if ($dualWeek)
							$date = strtotime('+ '.(14 + $class['day']).' days',$weekday);
						else
							$date = strtotime('+ '.((7 - $dayInWeek) + $class['day']).' days',$weekday);
					else
						$date = strtotime('+ '.($class['day'] - $dayInWeek).' days',$weekday);
				}
				else {
					$date = strtotime('+ '.(7 - $dayInWeek).' days',$weekday);

					$date = strtotime("+ {$class['day']} days", $date);
				};

				if (array_search($date,$days) === false) $days[] = $date;

				if (isset($class['name']))
					$Timetable[$lesson][$date][] = array($class['name'],'',$class['color'],$class['id'],$allgroup ? $class['group_name'] : '',date('W',$date));
			}
			$Timetable['opt'] = $days;

			return $Timetable;
		}

		// Órarend lekérése
		static function GetTimeTable($week, $allgroups = false){
			global $user, $db;

			# Formátum ellenörzése
			if (strpos('ab',$week) === false) trigger_error('Érvénytelen hét');

			# Órarend lekérés előkészítése
			$query = "SELECT
				tt.*,
				l.name,	l.color,
				@teacher := l.teacherid,
				(SELECT short FROM teachers t WHERE t.id = @teacher) as teacher
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
			if ($allgroups == false){
	            $query .= ' && groupid = ?';
	            $data[] = '0';
				foreach ($groupdata as $subgd){
					$query .= " || groupid = ?";
					$data[] = $subgd['id'];
				}
			}

			$groups = $db->rawQuery('SELECT `id`, `name` FROM `groups` WHERE classid = ?',array($user['class'][0]));
			$grp_list = array();
			foreach ($groups as $subg)
				$grp_list[$subg['id']] = $subg['name'];
			$grp_list['0'] = '';

			# Plusz adatok hozzáadása a lekéréshez
			$query .= ' ORDER BY tt.week ASC, tt.day ASC, tt.lesson ASC';

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
					$Timetable[$lesson][$weekday][] = array($class['name'],$class['teacher'],$class['color'],$class['id'],$grp_list[$class['groupid']]);
				}
			}

			return $Timetable;
		}

		const MANAGE = true;
		//Órarend kirenderelése
		static function Render($week,$Timetable,$weekdays = null){
			if (empty($weekdays) && empty($week)) return;
			if (!empty($weekdays)){
				// Hetek kirenderelésének előkészítése
				$weeks = [];
				foreach ($weekdays as $day){
					$wNum = (int)date('W',$day);
					if (array_search($wNum,array_keys($weeks)) === false) $weeks[$wNum] = array(1, Timetable::GetActualWeek(false,$day));
					else $weeks[$wNum][0]++;
				}
				ksort($weeks);
			} ?>

			<table class='timet'>
				<thead>
<?php				if (!empty($weeks)) {
						print "<tr><th>H</th>";
						foreach ($weeks as $key => $array){
							print "<th colspan='$array[0]'>{$array[1]}. hét ({$key}. hét)</th>";
						}
						print "</tr>";
					} ?>
					<tr>
						<th class="week"><?= empty($week) ? 'D' : strtoupper($week) ?></th>
<?php                   if (empty($weekdays)) { ?>
							<th class="weekday">Hétfő</th>
							<th class="weekday">Kedd</th>
							<th class="weekday">Szerda</th>
							<th class="weekday">Csütörtök</th>
							<th class="weekday">Péntek</th>
<?php                   }
						else
							foreach ($weekdays as $day)
								print "<th class='weekday'>".HomeworkTools::FormatMonthDay($day).' '.System::$Days[Timetable::GetDayNumber($day)]."</th>"; ?>
					</tr>
				</thead>

				<tbody>
<?php       if (empty($weekdays)){
				for ($lesson = 0; $lesson <= 8; $lesson++){
					if (empty($Timetable[$lesson])) continue; ?>
					<tr class="lesson-field">
						<th><?=$lesson+1?></th>
<?php                   for ($weekday = 0; $weekday < (empty($weekdays) ? 5 : count($weekdays)); $weekday++){
							$class = isset($Timetable[$lesson][$weekday]) ? $Timetable[$lesson][$weekday] : null;
							self::_RenderClass($class);
						} ?>
					</tr>
<?php           }
			}
			else {
				$days = array_keys($weekdays);
				for ($lesson = 0; $lesson <= 8; $lesson++){
					if (empty($Timetable[$lesson])) continue; ?>
					<tr class="lesson-field">
						<th><?=$lesson+1?></th>
<?php                   for ($day = 0; $day < count($days); $day++){
							//var_dump($days[$day]);
							$class = isset($Timetable[$lesson][$weekdays[$day]]) ? $Timetable[$lesson][$weekdays[$day]] : null;
							self::_RenderClass($class);
						} ?>
					</tr>
<?php           }
			} ?>
<?php
		print "</tbody></table>";
		if (!empty($week) && !System::PermCheck('timetables.edit')) print "<button class='btn sendbtn'>Módosítások mentése</button>";
		}

		// Órarend cella kirenderelő
		static private function _RenderClass($class){
			if (isset($class) && (!is_array($class) || !empty($class))){
				$echo = '<td>';
				if (!is_array($class)) $class = array($class);
				foreach($class as $c){
					if (empty($c[4])) $grpstr = '';
					else $grpstr = ' ('.$c[4].')';

					$week = isset($c[5]) ? "data-week='".$c[5]."'" : '';

					$echo .= "<span class='lesson' $week style='background: {$c[2]}'>{$c[0]}{$grpstr}<span class='del typcn typcn-times' data-id='$c[3]'></span></span>";
				}
			}
			else $echo = '<td class="empty">';
			$echo .= '<span class="add typcn typcn-plus"></span>';
			echo "$echo</td>";
		}
	}
