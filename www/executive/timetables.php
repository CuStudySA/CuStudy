<?php
	switch ($ENV['URL'][0]){
		case 'getoptions':
			$Groups = $db->rawQuery("SELECT g.id, g.name
				FROM groups g
				WHERE g.classid = ?", array($user['classid']));

			$Lessons = $db->rawQuery(
				"SELECT
					l.id, l.name, l.color,
					@teacher := l.teacherid as teacherid,
					(SELECT short FROM teachers t WHERE t.id = @teacher) as teacher
				FROM lessons l
				WHERE l.classid = ? ORDER BY l.name", array($user['classid']));

			System::Respond('',1,array(
				'groups' => $Groups,
				'lessons' => $Lessons,
			));
		break;

		case 'save':
			if (isset($ENV['POST']))
				$action = Timetable::ProgressTable($ENV['POST']);
			else System::Respond();

			if ($action === 0)
				System::Respond('Az órarend frissítése sikeres volt!',1);
			else
				System::Respond('Az órarend frissítése sikertelen volt, mert '.Message::GetError('edit_tt',$action).'! (Hibakód: '.$action.')');
		break;
	}