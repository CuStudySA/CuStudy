<?php

	class HomeworkTools {
		static function GetWeekDates($week, $year = null) {
			$year = empty($year) ? date('Y') : $year;
			$dto = new DateTime();
			$ret['start'] = $dto->setISODate($year, $week)->format('Y-m-d');
			$ret['end'] = $dto->modify('+6 days')->format('Y-m-d');
			return $ret;
		}

		static $RomanMonths = array(null,'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
		static function FormatMonthDay($time){
			return HomeworkTools::$RomanMonths[(int)date('m', $time)].'.'.date('d', $time);
		}

		static function Add($data){
			global $user;

			$action = self::_add($data);

			$data = System::TrashForeignValues(['lesson','year','week','text'],$data);

			if (!empty($data['week'])){
				$_match = array();
				if (!empty($data['week']) && preg_match('/(\d{4})w(\d{2})/i', $data['week'], $_match)){
					$data['week'] = $_match[2];
					$data['year'] = $_match[1];
				}
			}

			Logging::Insert(array_merge(array(
				'action' => 'homeworks.add',
				'errorcode' => is_array($action) ? 0 : $action,
				'db' => 'homeworks',
			),$data,array(
				'classid' => $user['class'][0],
				'e_id' => (is_array($action) ? $action[0] : 0),
			)));

			return $action;
		}

		static private function _add($data){
			global $db, $user;

			# Jog. ellenörzése
			if (System::PermCheck('homeworks.add')) return 1;

			# Formátum ellenörzése
			if (!System::ValuesExists($data,['lesson','text','week'])) return 2;
			unset($data['JSSESSID']);
			foreach ($data as $key => $value){
				switch ($key){
					case 'lesson':
					case 'year':
						$type = 'numeric';
					break;
					case 'week':
						$_match = array();
						if (!empty($value) && preg_match('/(\d{4})w(\d{2})/i', $value, $_match)){
							$data['week'] = $_match[2];
							$data['year'] = $_match[1];
							continue 2;
						}
						$type = 'numeric';
					break;
					case 'text':
						continue 2;

					case 'fileTitle':
					case 'fileDesc':
						$type = 'text';
					break;

					default:
						return 2;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			System::LoadLibrary('jbbcode');

			$parser = new JBBCode\Parser();
			$parser->addCodeDefinitionSet(new JBBCode\AmberCodeDefSet());

			$parser->parse(nl2br($data['text']));

			$data['text'] = strip_tags($parser->getAsHtml(),System::$AllowedHTMLTags);

			$dateFromUI = strtotime(self::GetWeekDates($data['week'])['start']);

			$dbdata = $db->rawQuery('SELECT tt.week as week
										FROM timetable tt
										LEFT JOIN (teachers t, lessons l)
										ON (tt.lessonid = l.id && l.teacherid = t.id)
										WHERE tt.classid = ? && tt.id = ? && t.name IS NOT NULL && l.name IS NOT NULL',
							array($user['class'][0],$data['lesson']));

			if (empty($dbdata)) return 3;

			if (Timetable::GetWeekLetter($dateFromUI) != strtoupper($dbdata[0]['week'])) return 4;

			if ($db->where('week', $data['week'])->where('year', $data['year'])->where('classid', $user['class'][0])->where('lesson', $data['lesson'])->has('homeworks'))
				return 5;

			// Mellékelt fájl feltöltése
			if (!empty($_FILES)){
				$file = reset($_FILES);

				$lessonId = $db->where('id', $data['lesson'])->getOne('timetable','lessonid')['lessonid'];

				$uploadStatus = FileTools::Insert(array(
					'file' => $file,
					'name' => isset($data['fileTitle']) ? $data['fileTitle'] : 'Házi feladathoz feltöltött fájl',
					'description' => isset($data['fileDesc']) ? $data['fileDesc'] : 'Házi feladathoz feltöltött fájl',
					'lessonid' => $lessonId,
					'classid' => $user['class'][0],
					'uploader' => $user['id'],
				));

				unset($data['fileTitle']);
				unset($data['fileDesc']);
			}

			$action = $db->insert('homeworks',array_merge($data,array(
				'author' => $user['id'],
				'classid' => $user['class'][0],
			)));

			return $action !== false ? [$action] : 6;
		}

		static function Delete($id){
			global $db, $user;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('homeworks');
			$data = System::TrashForeignValues(['lesson','year','week','text','classid','author'],!empty($data) ? $data : []);

			$action = self::_delete($id);

			Logging::Insert(array_merge(array(
				'action' => 'homeworks.delete',
				'errorcode' => $action,
				'db' => 'homeworks',
			),$data,array(
				'e_id' => $id,
			)));

			return $action;
		}

		static private function _delete($id){
			global $db,$user;

			# Form. ellenörzése
			if (System::InputCheck($id,'numeric')) return 2;

			# Jog. ellenörzése
			if (System::PermCheck('homeworks.delete',$id)) return 1;

			# Függőségek feloldása (hw_markdone)
			$data = $db->where('classid',$user['class'][0])->where('homework',$id)->get('hw_markdone');
			foreach ($data as $array)
				self::UndoMarkedDone($array['id']);

			$action = $db->where('id',$id)->delete('homeworks');

			if ($action) return 0;
			else return 3;
		}

		static function GetHomeworks($numberOfHomework = 3, $onlyListActive = false){
			global $db, $user, $ENV;

			$memberOfGroups = $db->where('classid',$user['class'][0])->where('userid',$user['id'])->get('group_members',null,'groupid');

			$groupIDs = array(0);
			foreach ($memberOfGroups as $row)
				$groupIDs[] = $row['groupid'];

			$weekNum = (int)date('W');
			$dayInWeek = Timetable::GetDay();

			$active = $onlyListActive ? '&& (SELECT `id` FROM `hw_markdone` WHERE `homework` = hw.id && `userid` = ?) IS NULL' : '';

			$now = time();
			if ($now > strtotime($ENV['userSettings']['general']['nextDaySwitch'], $now)){
				if ($dayInWeek == 1){
					$_dayInWeek = 7;
					$_weekNum = $weekNum-1;
				}
				else {
					$_dayInWeek = $dayInWeek-1;
					$_weekNum = $weekNum;
				}
			}
			else {
				$_dayInWeek = $dayInWeek;
				$_weekNum = $weekNum;
			}

			$params = array($user['id'],$user['class'][0],$_weekNum, $_dayInWeek, $_weekNum);
			if ($onlyListActive)
				$params[] = $user['id'];
			$timetable = $db->rawQuery(
				"SELECT hw.id, hw.text as `homework`, hw.week, tt.day, tt.lesson as `lesson_th`, l.name as `lesson`, l.color, hw.year as year,
					(SELECT `id` FROM `hw_markdone` WHERE `homework` = hw.id && `userid` = ?) as markedDone
				FROM `timetable` tt
				LEFT JOIN (`homeworks` hw, `lessons` l)
				ON (hw.lesson = tt.id && l.id = tt.lessonid)
				WHERE tt.classid = ? && tt.groupid IN (".implode(',', $groupIDs).") && ((hw.week = ? && tt.day > ?) || hw.week > ?) && hw.text IS NOT NULL {$active}
				ORDER BY hw.week, tt.day, tt.lesson",$params);
			
			$homeWorks = [];

			$i = 0;
			$currentYear = date('Y');
			while (true){
				if (empty($timetable[$i]))
					break;

				$row = $timetable[$i];

				if ($row['year'] < $currentYear){
					$i++;
					continue;
				}

				$hwTime = strtotime($row['year'].'W'.$row['week']);
				if ($row['day'] > 1)
					$hwTime = strtotime('+'.($row['day']-1).' days', $hwTime);

				$row['date'] = self::FormatMonthDay($hwTime);
				$row['dayString'] = System::$Days[Timetable::GetDay($hwTime)];

				if (!isset($homeWorks[$row['date']]) && count($homeWorks)+1 > $numberOfHomework)
					break;

				$homeWorks[$row['date']][] = $row;

				$i++;
			}

			return $homeWorks;
		}

		static function CheckMarkedDone($id, $canExist = false){
			global $db, $user;

			# Formátum ellenörzése
			if (System::InputCheck($id,'numeric')) return 1;

			# Létezik-e a H.Feladat?
			$data = $db->where('id',$id)->has('homeworks');
			if (!$data) return 3;

			# Késznek van-e már jelölve?
			$exists = $db
				->where('classid', $user['class'][0])
				->where('userid', $user['id'])
				->where('homework', $id)
				->has('hw_markdone');

			if ($canExist)
				$exists = !$exists;

			return $exists ? 2 : 0;
		}

		const CAN_EXIST = true;
		static function MakeMarkedDone($id){
			global $db, $user;

			# Ellenőrzés
			$check = self::CheckMarkedDone($id);
			if ($check != 0) return 2;

			# Adatbázisba írás
			$action = $db->insert('hw_markdone',array(
				'userid' => $user['id'],
				'homework' => $id,
				'classid' => $user['class'][0],
			));

			return $action ? 0 : 4;
		}

		static function UndoMarkedDone($id){
			global $db, $user;

			# Ellenőrzés
			$check = self::CheckMarkedDone($id, self::CAN_EXIST);
			if ($check != 0) return 2;

			# Adatbázisba írás
			$action = $db->where('homework',$id)->where('userid',$user['id'])->delete('hw_markdone');

			return $action ? 0 : 4;
		}

		static function RenderHomeworks($numberOfHomework = 3, $onlyListActive = false){
			$homeWorks = HomeworkTools::GetHomeworks($numberOfHomework,$onlyListActive);
?>

<?php       if (empty($homeWorks)) print System::Notice('info','Nincs megjelenítendő házi feladat! A kezdéshez adjon hozzá egyet, vagy váltson nézetet!');
			else print System::Notice('info','Nincs megjelenítendő házi feladat! A kezdéshez adjon hozzá egyet, vagy váltson nézetet!',null,false,true) ?>

			<table class='homeworks'>
				<tbody>
					<tr>
<?php
						 foreach(array_keys($homeWorks) as $value)
							print "<td><b>{$homeWorks[$value][0]['dayString']}</b> ({$value})</td>";
?>
					</tr>
					<tr>
<?php
						foreach(array_keys($homeWorks) as $value){
							print '<td>';
							foreach($homeWorks[$value] as $array){
								self::_renderHW($array, true);
							}
							print '</td>';
						}
?>
					</tr>
				</tbody>
			</table>
<?php       if (!System::PermCheck('homeworks.add')){ ?>
				<a class='typcn typcn-plus btn js_add_hw' href='/homeworks/new'>Új házi feladat hozzáadása</a>
<?php       }
			if ($onlyListActive)
				print "<a class='typcn typcn-tick btn js_add_hw js_showMarkedDone' href='#'>Elrejtett házi feladatok megjelenítése</a>";
			else
				print "<a class='typcn typcn-times btn js_add_hw js_hideMarkedDone' href='#'>Visszatérés az eredeti nézethez</a>";
		}

		private static function _renderHW($data, $controls){ ?>
			<div class='hw'>
				<div class='hw-lesson'>
					<span class='lesson-name'<?=!empty($data['color'])?"style='background-color:{$data['color']}'":''?>><?=$data['lesson']?></span><span class='lesson-number'><?=$data['lesson_th']?>. óra</span>
				</div>
				<div class='hw-text'><?=$data['homework']?></div>
<?php	    if (empty($data['markedDone'])){ ?>
				<a class="typcn typcn-tick js_makeMarkedDone" title='Késznek jelölés' href='#<?=$data['id']?>'></a>
<?php       }
			else { ?>
				<a class="typcn typcn-times js_undoMarkedDone" title='Késznek jelölés visszavonása' href='#<?=$data['id']?>'></a>
<?php       }
			if ($controls && !System::PermCheck('homeworks.delete')){ ?>
				<!-- <a class="typcn typcn-info-large js_more_info" title='További információk' href='#<?=$data['id']?>'></a> -->
				<a class="typcn typcn-trash js_delete" title='Bejegyzés törlése' href='#<?=$data['id']?>'></a>
<?php       } ?>
			</div>
<?php	}

		static function RenderHomeworksMainpage(){
			$homeWorks = HomeworkTools::GetHomeworks(1,true);

			if (empty($homeWorks))
				print "<h3>Elkészítésre váró házi feladatok</h3><p>Nincs megjeleníthető házi feladat.</p>";
			else {
				$hwKey = array_keys($homeWorks)[0];
				$date = explode('.',$hwKey);
				$month = array_search($date[0],HomeworkTools::$RomanMonths);
				$year = (int)date('Y');
				if ($month < intval(date('m'),10))
					$year++;
				$day = System::Pad($date[1]);
				$month = System::Pad($month);
				$time = strtotime("$year-$month-$day");

				$dayDisplay = date('W', $time) != date('W') ? " ($hwKey)" : '';
				echo '<h3>'.System::$Days[Timetable::GetDay($time)]."i$dayDisplay házi feladatok</h3>"; ?>
				<div class='homeworks'>
<?php					foreach($homeWorks[$hwKey] as $key => $array)
							self::_renderHW($array, false); ?>
				</div>
<?php       }
		}
	}

