<?php

	class AdminTools {
		static function FilterUsers($form){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.view')) return 1;

			$query = 'SELECT u.*, c.classid as classname, c.id as classid, s.name as schoolname
						FROM `users` u
						LEFT JOIN (`class` c, `school` s, `class_members` cm)
						ON (cm.userid = u.id && cm.classid = c.id && c.school = s.id)
						WHERE ';

			$whereIsUsed = false;
			foreach ($form as $key => $value){
				if (empty($value)) continue;

				if (substr($key,0,1) == 'u') $query .= str_replace('_','.',$key)." REGEXP '^{$value}$' && ";
				else {
					$cn = substr($key,0,1);
					if (is_numeric($value)) $cn .= '.id';
					else $cn .= '.'.($cn == 'c' ? 'classid' : 'name');
					$query .= "{$cn} REGEXP '^{$value}$' && ";
				}
				$whereIsUsed = true;
			}

			if ($whereIsUsed)
				$query = substr($query,0,strlen($query)-4);
			else
				$query = substr($query,0,strlen($query)-6);

			$data = $db->rawQuery($query);
			$return = array();

			foreach ($data as $array){
				if (!isset($return[$array['id']]))
					$return[$array['id']] = $array;
				else {
					if (is_array($return[$array['id']]['classid']))
						$return[$array['id']]['classid'][] = $array['classid'];
					else
						$return[$array['id']]['classid'] = array($return[$array['id']]['classid'],$array['classid']);

					if (is_array($return[$array['id']]['classname']))
						$return[$array['id']]['classname'][] = $array['classname'];
					else
						$return[$array['id']]['classname'] = array($return[$array['id']]['classname'],$array['classname']);

					if (is_array($return[$array['id']]['schoolname']))
						$return[$array['id']]['schoolname'][] = $array['schoolname'];
					else
						$return[$array['id']]['schoolname'] = array($return[$array['id']]['schoolname'],$array['schoolname']);
				}
			}

			return $return;
		}

		static function UserLookup($id){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.view')) return 1;
		}
	}
