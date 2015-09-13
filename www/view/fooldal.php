<?php
	$minute = (int)date('i');
	$hour = (int)date('H');

	if ($hour < 6) $welcome = 'Jó éjszakát';
	else if ($hour < 10) $welcome = 'Jó reggelt';
	else if ($hour < 18) $welcome = 'Jó napot';
	else if ($hour < 22) $welcome = 'Jó estét';
	else $welcome = 'Jó éjszakát';
?>

<h1><?=$welcome?> <span class='welcomeName'><?=$user['realname']?></span>!</h1>

<h3>Elkészítésre váró házi feladatok</h3>

<table class='homeworks'>
	<tr>
<?php
		$homeWorks = HomeworkTools::GetHomeworks(1);

		if (!empty($homeWorks)){
			$day = array_keys($homeWorks)[0];

			print "<td>";

			foreach($homeWorks[$day] as $key => $array){
				if ($key % 2 == 1) continue; ?>
		        <div class='hw'>
		            <span class='lesson-name'><?=$array['lesson']?></span><span class='lesson-number'><?=$array['lesson_th']?>. óra</span>
		            <div class='hw-text'><?=$array['homework']?></div>
		        </div>
<?php   	}

			print "</td><td>";

			foreach($homeWorks[$day] as $key => $array){
				if ($key % 2 == 0) continue; ?>
		        <div class='hw'>
		            <span class='lesson-name'><?=$array['lesson']?></span><span class='lesson-number'><?=$array['lesson_th']?>. óra</span>
		            <div class='hw-text'><?=$array['homework']?></div>
		        </div>
<?php   	}

			print "</td>"; ?>
	<tr>
</table>
<?php   }
	else print "<p>Nincs megjeleníthető házi feladat.</p>";

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
					WHERE `classid` = ? && `userid` = ?',array($user['classid'],$user['id']));

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
									WHERE tt.classid = ? && (tt.week = ? && tt.day > ?) || tt.week = ?
									ORDER BY tt.week {$sorting}, tt.day, tt.lesson",

									array($user['classid'],$actualWeek,$day,$actualWeek == 'A' ? 'b' : 'a'));
	}
	else {
		$timeTable_partWeek = $db->rawQuery("SELECT tt.week, tt.day, tt.lesson, l.name, t.name as teacher, l.color, tt.groupid
									FROM timetable tt
									LEFT JOIN (lessons l, teachers t)
									ON tt.lessonid = l.id && l.teacherid = t.id
									WHERE tt.classid = ? && tt.day > ?
									ORDER BY tt.day, tt.lesson",

									array($user['classid'],$day));
		if (empty($timeTable_partWeek))
			$timeTable_entireWeek = $db->rawQuery("SELECT tt.week, tt.day, tt.lesson, l.name, t.name as teacher, l.color, tt.groupid
								FROM timetable tt
								LEFT JOIN (lessons l, teachers t)
								ON tt.lessonid = l.id && l.teacherid = t.id
								WHERE tt.classid = ?
								ORDER BY tt.day, tt.lesson",

								array($user['classid']));
		else $timeTable_entireWeek = [];

		$timeTable = array_merge($timeTable_partWeek,$timeTable_entireWeek);
	}

	if (empty($timeTable)) echo "<p>Nincs megjeleníthető óra.</p>";
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
	} ?>
