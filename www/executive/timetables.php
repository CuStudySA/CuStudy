<?php
	switch ($ENV['URL'][0]){
		case 'getoptions':
			$Groups = $db->rawQuery("SELECT g.id, g.name
				FROM groups g
				WHERE g.classid = ?", array($user['class'][0]));

			$Lessons = $db->rawQuery(
				"SELECT
					l.id, l.name, l.color,
					@teacher := l.teacherid as teacherid,
					(SELECT short FROM teachers t WHERE t.id = @teacher) as teacher
				FROM lessons l
				WHERE l.classid = ? ORDER BY l.name", array($user['class'][0]));

			System::Respond('',1,array(
				'groups' => $Groups,
				'lessons' => $Lessons,
			));
		break;

		case 'save':
			if (!isset($ENV['POST']))
				System::Respond();
			$action = Timetable::ProcessTable($ENV['POST']);

			System::Respond(Message::Respond('timetables.progressTable',$action), !$action);
		break;

		case 'showTimetable':
			Timetable::Step($ENV['POST']['dispDays'][0], $ENV['URL'][1] === 'all');
		break;
	}
