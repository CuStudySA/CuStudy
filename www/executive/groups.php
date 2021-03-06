<?php
	$act = $ENV['URL'][0];

	switch ($act) {
		case 'add':
			if (!empty($ENV['POST']))
				$action = GroupTools::Add($ENV['POST']);
			else System::Respond();

			System::Respond(Message::Respond('groups.add',$action), $action == 0 ? 1 : 0);
		break;

		case 'edit':
			if (!empty($ENV['URL'][1]) && !empty($ENV['POST']))
				$action = GroupTools::Edit($ENV['URL'][1],$ENV['POST']);
			else System::Respond();

			System::Respond(Message::Respond('groups.edit',$action), $action == 0 ? 1 : 0);

		break;

		case 'delete':
			if (isset($ENV['POST']['id']))
				$action = GroupTools::Delete($ENV['POST']['id']);
			else System::Respond();

			System::Respond(Message::Respond('groups.delete',$action), $action == 0 ? 1 : 0);
		break;

		case 'members':
			$id = $ENV['POST']['id'];

			$group = $db->rawQuery('SELECT *
									FROM `groups`
									WHERE `classid` = ? AND `id` = ?',array($user['class'][0],$id))[0];
			if (empty($group)) System::Respond();

			$html = "<p><b>A(z) {$group['name']} ({$ENV['class']['classid']}) csoport tagjai:</b></p><ul>";

			$members = $db->rawQuery('SELECT users.name as `name`
									FROM `group_members`
									LEFT JOIN `users`
									ON group_members.userid = users.id
									WHERE group_members.classid = ? && group_members.groupid = ?',array($user['class'][0],$id));

			foreach ($members as $member)
				$html.= "<li>{$member['name']}</li>";

			if (empty($members))
				$html .= "<li>(nincs tagja a csoportnak)</li>";

			$html .= "</ul>";

			System::Respond('',1,array('html' => $html));
		break;

		case 'theme':
			switch ($ENV['URL'][1]){
				case 'get':
					if (empty($ENV['POST']['id'])) System::Respond();

					$data = $db->rawQuery('SELECT *
											FROM `group_themes`
											WHERE `id` = ? && `classid` = ?',array($ENV['POST']['id'],$user['class'][0]));

					if (empty($data)) System::Respond();
					else $data = $data[0];

					$json = array(
						'name' => $data['name'],
					);

					System::Respond('', 1, $json);
				break;

				case 'edit':
					$action = GroupThemeTools::Edit($ENV['POST']);

					System::Respond(Message::Respond('groupThemes.edit',$action), $action == 0 ? 1 : 0);
				break;

				case 'delete':
					if (empty($ENV['POST']['id'])) System::Respond();
					$action = GroupThemeTools::Delete($ENV['POST']['id']);

					System::Respond(Message::Respond('groupThemes.delete',$action), $action == 0 ? 1 : 0);
				break;

				case 'add':
					$action = GroupThemeTools::Add($ENV['POST']);

					System::Respond(Message::Respond('groupThemes.add',is_array($action) ? 0 : $action), is_array($action) ? 1 : 0, is_array($action) ? array('id' => $action[0]) : array());
				break;
			}
		break;
	}