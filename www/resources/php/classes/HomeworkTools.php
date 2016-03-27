<?php

	class HomeworkTools {
		static function GetWeekDates($week, $year = null) {
			$year = empty($year) ? date('Y') : $year;
			$dto = new DateTime();
			$ret['start'] = $dto->setISODate($year, $week)->format('Y-m-d');
			$ret['end'] = $dto->modify('+6 days')->format('Y-m-d');
			return $ret;
		}

		static $RomanMonths = array(null,'I','II','II','IV','V','VI','VII','VIII','IX','X','XI','XII');
		static function FormatMonthDay($time){
			return HomeworkTools::$RomanMonths[(int)date('m', $time)].'.'.date('d', $time);
		}

		static function Add($data){
			global $db, $user;

			# Jog. ellenörzése
			if(System::PermCheck('homeworks.add')) return 0x1;

			# Formátum ellenörzése
			if (!System::ValuesExists($data,['lesson','text','week'])) return 0x2;
			foreach ($data as $key => $value){
				switch ($key){
					case 'lesson':
						$type = 'numeric';
					break;
					case 'week':
						$type = 'numeric';
					break;
					case 'text':
						continue 2;

					case 'fileTitle':
						$type = 'text';
					break;
					case 'fileDesc':
						$type = 'text';
					break;

					default:
						return 0x2;
					break;
				}
				if (System::InputCheck($value,$type)) return 0x2;
			}

			$parser = new JBBCode\Parser();
			$parser->addCodeDefinitionSet(new JBBCode\BlueSkyCodeDefSet());

			$parser->parse(nl2br($data['text']));

			$data['text'] = strip_tags($parser->getAsHtml(),System::$AllowedHTMLTags);

			$dateFromUI = strtotime(self::GetWeekDates($data['week'])['start']);

			$dbdata = $db->rawQuery('SELECT tt.week as week
										FROM timetable tt
										LEFT JOIN (teachers t, lessons l)
										ON (tt.lessonid = l.id && l.teacherid = t.id)
										WHERE tt.classid = ? && tt.id = ? && t.name IS NOT NULL && l.name IS NOT NULL',
							array($user['class'][0],$data['lesson']));

			if (empty($dbdata)) return 0x3;
			else $dbdata = $dbdata[0];

			if (Timetable::GetActualWeek(false,$dateFromUI) != strtoupper($dbdata['week'])) return 0x4;

			// Mellékelt fájl feltöltése
			$uploadStatus = 0;
			if (!empty($_FILES)){
				$file = reset($_FILES);
				$uploadStatus = FileTools::UploadFile($file);

				if (is_array($uploadStatus)){
					$lessonId = $db->rawQuery('SELECT `lessonid`
												FROM `timetable`
												WHERE `id` = ?',array($data['lesson']))[0]['lessonid'];

					$action = $db->insert('files',array(
						'name' => isset($data['fileTitle']) ? $data['fileTitle'] : 'Házi feladathoz feltöltött fájl',
						'description' => isset($data['fileDesc']) ? $data['fileDesc'] : 'Házi feladathoz feltöltött fájl',
						'lessonid' => $lessonId,
						'classid' => $user['class'][0],
						'uploader' => $user['id'],
						'size' => $file['size'],
						'filename' => $file['name'],
						'tempname' => $uploadStatus[0],
					));
					$uploadStatus = 0;
					unset($data['fileTitle']);
					unset($data['fileDesc']);
				}
			}

			$db->insert('homeworks',array_merge($data,array('author' => $user['id'], 'classid' => $user['class'][0])));

			return $uploadStatus;
		}

		static function Delete($id){
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
			global $db, $user;

			$grpmember = $db->rawQuery('SELECT `groupid`
							FROM `group_members`
							WHERE `classid` = ? && `userid` = ?',array($user['class'][0],$user['id']));

			$addon = [$user['id'],$user['class'][0]];
			$ids = array(0);
			foreach ($grpmember as $array)
				$ids[] = $array['groupid'];

			$weekNum = Timetable::GetWeekNum();
			$dayInWeek = Timetable::GetDayNumber();

			$active = $onlyListActive ? '&& (SELECT `id` FROM `hw_markdone` WHERE `homework` = hw.id && `userid` = ?) IS NULL' : '';

			$query = "SELECT hw.id, hw.text as `homework`, hw.week, tt.day, tt.lesson as `lesson_th`, l.name as `lesson`, hw.year as year,
							(SELECT `id` FROM `hw_markdone` WHERE `homework` = hw.id && `userid` = ?) as markedDone
						FROM `timetable` tt
						LEFT JOIN (`homeworks` hw, `lessons` l)
						ON (hw.lesson = tt.id && l.id = tt.lessonid)
						WHERE tt.classid = ? && tt.groupid IN (".implode(',', $ids).") && ((hw.week = ? && tt.day > ?) || hw.week > ?) && hw.text IS NOT NULL {$active}
						ORDER BY hw.week, tt.day, tt.lesson";

			/*
			 *  $a = isset($_GET['a']) ? $_GET['a'] : '';
			 * $a = $_GET['a'] ?? '';
			 * */

			$minute = (int)date('i');
			$hour = (int)date('H');
			if (!($hour >= 8 && $minute >= 0)){
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

			$activeArray = $onlyListActive ? array($user['id']) : array();
			$timetable = $db->rawQuery($query,array_merge($addon,array($_weekNum, $_dayInWeek, $_weekNum),$activeArray));
			
			$homeWorks = [];

			$i = 0;
			while (true){
				if (empty($timetable[$i])) break;
				else $array = $timetable[$i];

				if ($array['year'] < date('Y')){
					$i++;
					continue;
				}

				if ($weekNum == $array['week'])
					$hwTime = strtotime('+ '.($array['day'] - $dayInWeek).' days');

				else {
					$hwTime = strtotime('- '.($dayInWeek - 1).' days');
					$hwTime = strtotime('+ '.($array['week'] - $weekNum).' weeks', $hwTime);
					$hwTime = strtotime('+ '.($array['day'] - 1).' days', $hwTime);
				}

				$array['date'] = self::FormatMonthDay($hwTime);
				$array['dayString'] = System::$Days[Timetable::GetDayNumber($hwTime)];

				$homeWorks[$array['date']][] = $array;

				$i++;
			}

			array_splice($homeWorks,$numberOfHomework);
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

<?php       if (empty($homeWorks)) print System::Notice('info','Nincs megjelenítendő házi feladat! A kezdéshez adjon hozzá egyet, vagy váltson nézetet!'); ?>

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
							foreach($homeWorks[$value] as $array){ ?>
						        <div class='hw'>
						            <span class='lesson-name'><?=$array['lesson']?></span><span class='lesson-number'><?=$array['lesson_th']?>. óra</span>
						            <div class='hw-text'><?=$array['homework']?></div>
<?php	    if (empty($array['markedDone'])){ ?>
				<a class="typcn typcn-tick js_makeMarkedDone" title='Késznek jelölés' href='#<?=$array['id']?>'></a>
<?php       }
			else { ?>
				<a class="typcn typcn-times js_undoMarkedDone" title='Késznek jelölés visszavonása' href='#<?=$array['id']?>'></a>
<?php       }
			if (!System::PermCheck('homeworks.delete')){ ?>
							            <a class="typcn typcn-info-large js_more_info" title='További információk' href='#<?=$array['id']?>'></a>
							            <a class="typcn typcn-trash js_delete" title='Bejegyzés törlése' href='#<?=$array['id']?>'></a>
<?php       } ?>
						          </div>
<?php				        }
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

	    static function RenderHomeworksMainpage(){
	        $homeWorks = HomeworkTools::GetHomeworks(1,true);

	        if (empty($homeWorks))
	            print "<h3>Elkészítésre váró házi feladatok</h3><p>Nincs megjeleníthető házi feladat.</p>";
	        else {
	            $day = array_keys($homeWorks)[0];
	            if ((int)substr($day,0,2) == 1 && (int)date('m') == 12) $year = (int)date('y') + 1;
	            else $year = (int)date('y');

				$date = explode('.',$day);

				$date[0] = array_search($date[0],HomeworkTools::$RomanMonths);
				$date[0] = strlen($date[0]) == 1 ? '0'.$date[0] : $date[0];
				$date = $year.'-'.implode('-',$date);

	            $time = strtotime($date);

				print "<h3>Házi feladatok ".System::Article(System::$Days[Timetable::GetDayNumber($time)])."i napra ({$day})</h3>";
		        $day = array_keys($homeWorks)[0]; ?>
				<table class='homeworks'>
					<tr>
						<td>
<?php					foreach($homeWorks[$day] as $key => $array){
							if ($key % 2 == 1) continue; ?>
					        <div class='hw'>
					            <span class='lesson-name'><?=$array['lesson']?></span><span class='lesson-number'><?=$array['lesson_th']?>. óra</span>
					            <div class='hw-text'><?=$array['homework']?></div>

								<a class="typcn typcn-tick js_makeMarkedDone" title='Késznek jelölés' href='#<?=$array['id']?>'></a>
					        </div>
<?php   	            } ?>
						</td>
						<td>
<?php           foreach($homeWorks[$day] as $key => $array){
					if ($key % 2 == 0) continue; ?>
					        <div class='hw'>
					            <span class='lesson-name'><?=$array['lesson']?></span><span class='lesson-number'><?=$array['lesson_th']?>. óra</span>
					            <div class='hw-text'><?=$array['homework']?></div>

					            <a class="typcn typcn-tick js_makeMarkedDone" title='Késznek jelölés' href='#<?=$array['id']?>'></a>
					        </div>
<?php               } ?>
						</td>
					<tr>
				</table>
<?php       }
		}
	}

