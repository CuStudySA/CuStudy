<?php
	switch ($ENV['URL'][0]){
		case 'getoptions':
			$Groups =       $db->where('classid', $user['class'][0])->orderBy('name','ASC')->get('groups',      null,'id,name,theme');
			$GroupThemes =  $db->where('classid', $user['class'][0])->orderBy('name','ASC')->get('group_themes',null,'id,name');
			$Lessons =      $db->where('classid', $user['class'][0])->orderBy('name','ASC')->get('lessons',     null,'id,name,color,teacherid');
			$Teachers =     $db->where('classid', $user['class'][0])->orderBy('name','ASC')->get('teachers',    null,'id,name');


			System::Respond('',1,array(
				'groups' => $Groups,
				'lessons' => $Lessons,
				'gthemes' => $GroupThemes,
				'teachers' => $Teachers,
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
