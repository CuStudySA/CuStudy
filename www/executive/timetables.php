<?php
	switch ($ENV['URL'][0]){
		case 'getoptions':
			$Groups = $db->where('classid', $user['class'][0])->orderBy('name','ASC')->get('groups',null,'id,name,theme');

			$GroupThemes = $db->where('classid', $user['class'][0])->orderBy('name','ASC')->get('group_themes',null,'id,name');

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
				'gthemes' => $GroupThemes,
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
