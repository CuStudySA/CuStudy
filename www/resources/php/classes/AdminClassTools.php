<?php
	class AdminClassTools {
		static function FilterClasses($form){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.classes.view')) return 1;

			$query = 'SELECT c.classid as className, c.id as classId, s.name as schoolName, s.id as schoolId
						FROM `class` c
						LEFT JOIN `school` s
						ON (c.school = s.id)
						WHERE ';

			$whereIsUsed = false;
			foreach ($form as $key => $value){
				if (empty($value)) continue;

				if (substr($key,0,1) == 'c') $query .= str_replace('_','.',$key)." REGEXP '^{$value}$' && ";
				else {
					$s = substr($key,2);
					if ($s == 'id')
						$query .= (is_int($value) ? 's.id' : 's.name')." REGEXP '^{$value}$' && ";
					else
						continue;
				}

				$whereIsUsed = true;
			}

			if ($whereIsUsed)
				$query = substr($query,0,strlen($query)-4);
			else
				$query = substr($query,0,strlen($query)-6);

			$data = $db->rawQuery($query);

			foreach ($data as $key => $array){
				$users = $db->rawQuery('SELECT DISTINCT *
										FROM `class_members`
										WHERE `classid` = ?',array($array['classId']));
				$data[$key]['userCount'] = count($users);
			}

			return $data;
		}

		static function EditBasicInfos($data){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.classes.view')) return 1;

			# Bevitel ellenörzése
			$data = System::TrashForeignValues(['id','classid'],$data);
			if (!System::ValuesExists($data,['id','classid'])) return 2;
			foreach ($data as $key => $value){
				$keys = [
					'classid' => 'class',
					'id' => 'numeric',
				];
				if (System::InputCheck($value,isset($keys[$key]) ? $keys[$key] : $key)) return 2;
			}

			$action = $db->where('id',$data['id'])->update('class',$data);

			if ($action) return 0;
			else return 3;
		}

		static function ManageMembers($data){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.classes.view')) return 1;

			foreach ($data as $entry){
				# Bevitel ellenörzése
				if (!System::ValuesExists($entry,['id','role','remove','classid'])) return 2;
				if (System::InputCheck($entry['id'],'numeric') || System::InputCheck($entry['classid'],'numeric')) return 2;
				if (System::OptionCheck($entry['role'],array_keys(array_slice(UserTools::$roleLabels,0,3)))) return 2;

				$classid = $entry['classid'];

				// Ha el kell távolítani...
				if ($entry['remove'] == 1){
					$db->where('userid',$entry['id'])->where('classid',$classid)->delete('class_members');
					continue;
				}

				$cm = $db->where('userid',$entry['id'])->where('classid',$classid)->getOne('class_members');

				// Ha hozzá kell adni...
				if (empty($cm)){
					$db->insert('class_members',array(
						'userid' => $entry['id'],
						'classid' => $classid,
						'role' => $entry['role'],
					));

					continue;
				}

				// Ha módosítani kell a lok. jogosultságát
				if ($cm['role'] != $entry['role']){
					$db->where('id',$cm['id'])->update('class_members',array(
						'role' => $entry['role'],
					));

					continue;
				}
			}

			return 0;
		}

		static function EnterClass($classid){
			global $db, $user, $ENV;

			# Jog. ellenörzése
			if (System::PermCheck('system.classes.view')) return 1;

			# Bevitel ellenörzése
			if (System::InputCheck($classid,'numeric')) return 2;

			$action = $db->insert('temporary_roles',array(
				'sessionid' => $ENV['session'][0]['id'],
				'classid' => $classid,
				'role' => 'admin',
			));

			if ($action === false) return 3;
			return 0;
		}

		static function ExitClass(){
			global $user, $ENV, $db;

			if (empty($user) || !is_array($user)) return 1;

			$db->where('sessionid',$ENV['session'][0]['id'])->delete('temporary_roles');
			return 0;
		}
	}