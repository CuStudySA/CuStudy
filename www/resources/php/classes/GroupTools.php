<?php

	class GroupTools {
		static function Add($data){
			global $db,$user;

			# Jog. ellenörzése
			if (System::PermCheck('groups.add')) return 1;

			if (!System::ValuesExists($data,['name','theme','group_members'])) return 3;
			foreach ($data as $key => $value){
				switch($key){
					case 'name':
						$type = 'text';
						break;
					case 'theme':
						$type = 'numeric';
						break;
					default:
						continue 2;
						break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			# Téma ellenörzése
			$theme = $db->where('id',$data['theme'])->getOne('group_themes');
			if (empty($theme)) return 4;

			$insertGroup = $db->insert('groups',array(
				'classid' => $user['class'][0],
				'name' => $data['name'],
				'theme' => $data['theme'],
			));

			$users = $db->rawQuery('SELECT u.*
										FROM `users` u
										LEFT JOIN `class_members` cm
										ON u.id = cm.userid
										WHERE cm.classid = ?',array($user['class'][0]));
			$users_l = array();
			foreach ($users as $entry)
				$users_l[] = $entry['id'];

			$grpmem = explode(',',$data['group_members']);

			if (empty($data['group_members'])) return 0;
			foreach($grpmem as $mem){
				if (!in_array($mem,$users_l)) return 5;
				$db->insert('group_members',array(
					'classid' => $user['class'][0],
					'groupid' => $insertGroup,
					'userid' => $mem,
				));
			}

			return 0;
		}

		static function Edit($id,$data){
			global $db,$user;

			if (System::InputCheck($id,'numeric')) return 2;

			# Jog. ellenörzése
			if (System::PermCheck('groups.edit',$id)) return 1;

			if (!System::ValuesExists($data,['name','theme'])) return 3;
			foreach ($data as $key => $value){
				switch($key){
					case 'name':
						$type = 'text';
						break;
					case 'theme':
						$type = 'numeric';
						break;
					default:
						continue 2;
						break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			$db->where('id',$id)->update('groups',array(
				'name' => $data['name'],
				'theme' => $data['theme'],
			));

			if (!empty($data['class_members'])){
				$grpm = explode(',',$data['class_members']);

				$uids = [];
				foreach ($grpm as $entry){
					if (System::InputCheck($entry,'numeric')) return 4;
					$uids[] = $entry;
				}
				$query = 'DELETE FROM `group_members`
							WHERE `groupid` = ? && userid IN ('.implode(',',$uids).')';

				$db->rawQuery($query,array($id));
			}

			if (!empty($data['group_members'])){
				$grpm = explode(',',$data['group_members']);

				$members = $db->rawQuery('SELECT users.id
											FROM `group_members`
											LEFT JOIN `users`
											ON group_members.userid = users.id
											WHERE group_members.classid = ? && group_members.groupid = ?',array($user['class'][0],$id));
				$memb = array();
				foreach($members as $member)
					$memb[] = $member['id'];
				$members = $memb;

				foreach ($grpm as $entry){
					if (System::InputCheck($entry,'numeric')) return 4;
					if (in_array($entry,$members)) continue;
					$db->insert('group_members',array(
						'classid' => $user['class'][0],
						'groupid' => $id,
						'userid' => $entry,
					));
				}
			}

			return 0;
		}

		static function Delete($id){
			global $db,$user;

			if (System::InputCheck($id,'numeric')) return 2;

			# Jog. ellenörzése
			if (System::PermCheck('groups.delete',$id)) return 1;

			# Csop. ellenörzése
			$group = $db->rawQuery('SELECT *
						FROM `groups`
						WHERE `classid` = ? && `id` = ?',array($user['class'][0],$id));
			if (empty($group)) return 3;

			$members = $db->rawQuery('SELECT *
									FROM `group_members`
									WHERE `classid` = ? && `groupid` = ?',array($user['class'][0],$id));

			if (!empty($members)){
				$uids = [];
				foreach ($members as $entry)
					$uids[] = $entry['userid'];

				$query = 'DELETE FROM `group_members`
							WHERE `groupid` = ? && userid IN ('.implode(',',$uids).')';

				$db->rawQuery($query,array($id));
			}

			# Függőségek feloldása (timetable)
			$data = $db->where('classid',$user['class'][0])->where('groupid',$id)->get('timetable');
			foreach ($data as $array)
				Timetable::DeleteEntrys(array(array('id' => $array['id'])));

			$action = $db->where('id',$id)->delete('groups');

			return $action ? 0 : 4;
		}
	}
