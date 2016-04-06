<?php
	$minute = (int)date('i');
	$hour = (int)date('H');

	if ($hour < 6 || $hour >= 22) $welcome = 'Jó éjszakát';
	else if ($hour < 10) $welcome = 'Jó reggelt';
	else if ($hour < 18) $welcome = 'Jó napot';
	else if ($hour < 22) $welcome = 'Jó estét'; ?>

	<h1><?=$welcome?> <span class='welcomeName'><?=$user['name']?></span>!</h1>

<?php
	switch (ROLE){
		case 'systemadmin':
			print System::Notice('info','Válasszon a menüsáv valamelyik adminisztrációs lehetősége közül!');
		break;

		default: ?>
			<div class='hWContent'>
		<?php
				HomeworkTools::RenderHomeworksMainpage(); ?>
			</div>

		<?php

			// Következő tanítási napi órarend
			$sort = Timetable::GetActualWeek(true);
			$day = Timetable::GetDayNumber();

			$minute = date('i');
			$hour = date('H');
			if (!($hour >= 8 && $minute >= 0))
				$day = $day == 1 ? 7 : $day - 1;

			$sorting = $day > 5 ? ($sort == 'ASC' ? 'DESC' : 'ASC') : $sort;


			$grpmember = $db->rawQuery('SELECT `groupid`
							FROM `group_members`
							WHERE `classid` = ? && `userid` = ?',array($user['class'][0],$user['id']));

			$ids = array(0);
			foreach ($grpmember as $array)
				$ids[] = $array['groupid'];

			$dualWeek = Timetable::GetNumberOfWeeks() == 1 ? false : true;
			$actualWeek = Timetable::GetActualWeek();

			if ($dualWeek){
				$timeTable = $db->rawQuery("SELECT tt.week, tt.day, tt.lesson, l.name, t.name as teacher, l.color, tt.groupid
											FROM timetable tt
											LEFT JOIN (lessons l, teachers t)
											ON tt.lessonid = l.id && l.teacherid = t.id
											WHERE tt.classid = ? && ((tt.week = ? && tt.day > ?) || tt.week = ?)
											ORDER BY tt.week {$sorting}, tt.day, tt.lesson",

											array($user['class'][0],$actualWeek,$day,$actualWeek == 'A' ? 'b' : 'a'));
			}
			else {
				$timeTable_partWeek = $db->rawQuery("SELECT tt.week, tt.day, tt.lesson, l.name, t.name as teacher, l.color, tt.groupid
											FROM timetable tt
											LEFT JOIN (lessons l, teachers t)
											ON tt.lessonid = l.id && l.teacherid = t.id
											WHERE tt.classid = ? && tt.day > ?
											ORDER BY tt.day, tt.lesson",

											array($user['class'][0],$day));
				if (empty($timeTable_partWeek))
					$timeTable_entireWeek = $db->rawQuery("SELECT tt.week, tt.day, tt.lesson, l.name, t.name as teacher, l.color, tt.groupid
										FROM timetable tt
										LEFT JOIN (lessons l, teachers t)
										ON tt.lessonid = l.id && l.teacherid = t.id
										WHERE tt.classid = ?
										ORDER BY tt.day, tt.lesson",

										array($user['class'][0]));
				else $timeTable_entireWeek = [];

				$timeTable = array_merge($timeTable_partWeek,$timeTable_entireWeek);
			}

			if (empty($timeTable)) echo "<h3>Következő napi órarend</h3><p>Nincs megjeleníthető óra.</p>";
			else {
				$lessons = array();

				$firstLesson = $timeTable[0];

				foreach ($timeTable as $entry){
					if ($entry['week'] == $firstLesson['week'] && $entry['day'] == $firstLesson['day'])
						$lessons[] = $entry;
				}
				if (!empty($lessons)){
					$weekdays = ['Hétfő','Kedd','Szerda','Csütörtök','Péntek','Szombat','Vasárnap'];

					echo "<h3>".$weekdays[$lessons[0]['day']-1]."i órarend</h3>"; ?>
			<div class='lessonList'>
		<?php       foreach ($lessons as $lesson){
						if (!in_array($lesson['groupid'],$ids)) continue; ?>
					<div>
						<span class='lessonNumber'><?=$lesson['lesson']?>.</span> óra:
						<span class='lessonName' style='background-color: <?=$lesson['color']?>'><?=$lesson['name']?></span>
						(tanítja: <span class='lessonTeacher'><?=$lesson['teacher']?></span>)
					</div>
		<?php	    } ?>
			</div>
		<?php   }
			}

			EventTools::ListEvents();
	break;
}
