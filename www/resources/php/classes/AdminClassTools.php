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
	}