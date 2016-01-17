<?php
	$minute = (int)date('i');
	$hour = (int)date('H');

	if ($hour < 6 || $hour >= 22) $welcome = 'Jó éjszakát';
	else if ($hour < 10) $welcome = 'Jó reggelt';
	else if ($hour < 18) $welcome = 'Jó napot';
	else if ($hour < 22) $welcome = 'Jó estét';
?>

<h1><?=$welcome?> <span class='welcomeName'><?=$user['name']?></span>!</h1>

	<div class='hWContent'>
<?php
		HomeworkTools::RenderHomeworksMainpage(); ?>
	</div>

<?php

	// Következő tanítási napi órarend
	$day = Timetable::GetDay();

	$minute = date('i');
	$hour = date('H');
	if (!($hour >= 8 && $minute >= 0))
		$day = $day == 1 ? 7 : $day - 1;

	$groups = UserTools::GetClassGroupIDs();
	$onlyGrp = "&& tt.groupid IN ($groups)";

	$actualWeek = Timetable::GetWeekLetter(strtotime('+1 day'));

	switch(Timetable::GetNumberOfWeeks()){
		case 1:
			$timeTable = $db->rawQuery("SELECT tt.week, tt.day, tt.lesson, l.name, t.name as teacher, l.color, tt.groupid
										FROM timetable tt
										LEFT JOIN (lessons l, teachers t)
										ON tt.lessonid = l.id && l.teacherid = t.id
										WHERE tt.classid = ? && ((tt.week = ? && tt.day > ?) || tt.week = ?) $onlyGrp
										ORDER BY tt.week ASC, tt.day, tt.lesson", array($user['class'][0], $actualWeek, $day, Timetable::GetUpcomingWeek($actualWeek)));
		break;
		case 2:
			$timeTable_partWeek = $db->rawQuery("SELECT tt.week, tt.day, tt.lesson, l.name, t.name AS teacher, l.color, tt.groupid
										FROM timetable tt
										LEFT JOIN (lessons l, teachers t)
										ON tt.lessonid = l.id && l.teacherid = t.id
										WHERE tt.classid = ? && tt.day > ? $onlyGrp
										ORDER BY tt.day, tt.lesson", array($user['class'][0], $day));
			if (empty($timeTable_partWeek)){
				$timeTable_entireWeek = $db->rawQuery("SELECT tt.week, tt.day, tt.lesson, l.name, t.name AS teacher, l.color, tt.groupid
									FROM timetable tt
									LEFT JOIN (lessons l, teachers t)
									ON tt.lessonid = l.id && l.teacherid = t.id
									WHERE tt.classid = ? $onlyGrp
									ORDER BY tt.day, tt.lesson", array($user['class'][0]));
			}
			else $timeTable_entireWeek = [];

			$timeTable = array_merge($timeTable_partWeek, $timeTable_entireWeek);
		break;
		default: trigger_error('Nem támogatott hétszám', E_USER_ERROR);
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
			echo "<h3>".System::$Days[$lessons[0]['day']]."i órarend</h3>"; ?>
	<div class='lessonList'>
<?php       foreach ($lessons as $lesson){ ?>
			<div>
				<span class='lessonNumber'><?=$lesson['lesson']?>.</span> óra:
				<span class='lessonName' style='background-color: <?=$lesson['color']?>'><?=$lesson['name']?></span>
				(tanítja: <span class='lessonTeacher'><?=$lesson['teacher']?></span>)
			</div>
<?php	    } ?>
	</div>
<?php   }
	}

	EventTools::ListEvents(); ?>
