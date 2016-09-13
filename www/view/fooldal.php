<?php

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
			<div class="section-container">
			<section class='homeworks'>
<?php
				HomeworkTools::RenderHomeworksMainpage(); ?>
			</section>

			<section class='lessons'>
<?php

			$timeTable = Timetable::Get(null,null,false,1);
			$day = Timetable::GetDay(Timetable::CalcDays($timeTable, 1)[0]);

			if (empty($timeTable)) echo "<h3>Következő napi órarend</h3><p>Nincs megjeleníthető óra.</p>";
			else {
				$lessonids = array();
				foreach ($timeTable as $row){
					foreach($row as $col){
						$lessonids[] = $col[0]['lid'];
					}
				}
				$teachers = array();
				$teachersRaw = $db->rawQuery(
					'SELECT t.name, t.short, l.id
					FROM lessons l
					LEFT JOIN teachers t ON t.id = l.teacherid
					WHERE l.classid = ? && l.id IN ('.implode(',',$lessonids).')', array($user['class'][0]));
				foreach ($teachersRaw as $row){
					$id = $row['id'];
					unset($row['id']);
					$teachers[$id] = $row;
				}
				unset($teachersRaw);
				$lessons = array();

				foreach ($timeTable as $row => $classes){
					foreach ($classes as $lesson){
						$lesson = $lesson[0];
						$lesson['teacher'] = $teachers[$lesson['lid']];
						$lessons[$row+1] = $lesson;
					}
				}
				if (!empty($lessons)){
					echo "<h3>".System::$Days[$day]."i órarend</h3>"; ?>

					<div class='lessonList'>
<?php                   foreach ($lessons as $row => $lesson){ ?>
							<div>
								<span class='lessonNumber'><?=$row?>.</span> óra:
								<span class='lessonName' style='background-color: <?=$lesson['bgcolor']?>'><?=$lesson['name']?></span>
								(tanítja: <span class='lessonTeacher'><span class="short"><?=$lesson['teacher']['short']?></span><span class="long"><?=$lesson['teacher']['name']?></span></span>)
							</div>
<?php	                } ?>
					</div>
<?php           }
			} ?>
			</section>

<?php       EventTools::ListEvents(); ?>
			</div>
<?php	break;
	}?>
