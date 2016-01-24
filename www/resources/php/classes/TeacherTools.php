<?php

	class TeacherTools {
		static function Add($datas){
			global $db,$user;

			# Jog. ellenörzése
			if(System::PermCheck('teachers.add')) return 1;

			# Alapadatok feldolgozása
			if (!isset($datas['name']) || !isset($datas['short'])) return 2;
			$basedata = array(
				'name' => $datas['name'],
				'short' => $datas['short'],
			);
			foreach ($basedata as $key => $value){
				switch ($key){
					case 'short':
						$type = 'shortn_teacher';
						break;
					default:
						$type = $key;
						break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}
			$basedata['classid'] = $user['class'][0];
			$action = $db->insert('teachers',$basedata);
			if (!is_numeric($action)) return 3;

			# Tantárgyak hozzáadása
			if (!isset($datas['lessons']) || empty($datas['lessons'])) return [$action];
			foreach ($datas['lessons'] as $sublesson){
				$action_l = $db->insert('lessons',array(
					'classid' => $user['class'][0],
					'name' => $sublesson['name'],
					'teacherid' => $action,
					'color' => $sublesson['color'],
				));
				if (!$action_l) return 4;
			}

			return [$action];
		}

		static function Edit($data){
			global $db;

			# Formátum ellenörzése
			if (!System::ValuesExists($data,['short','name','id'])) return 2;
			foreach ($data as $key => $value){
				switch ($key){
					case 'short':
						$type = 'shortn_teacher';
						break;
					case 'id':
						$type = 'numeric';
						break;
					case 'name':
						$type = 'name';
						break;
					default:
						return 2;
						break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			# Jog. ellenörzése
			if (System::PermCheck('teachers.edit',$data['id'])) return 1;

			# Adatbázisba írás
			$action = $db->where('id',$data['id'])->update('teachers',$data);

			if ($action) return 0;
			else return 3;
		}

		static function Delete($id){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('teachers.delete',$id)) return 1;

			$action = $db->where('id',$id)->delete('teachers');

			$data = $db->rawQuery('SELECT id
									FROM `lessons`
									WHERE teacherid = ?',array($id));

			foreach ($data as $array)
				LessonTools::Delete($array['id']);

			if ($action) return 0;
			else return 2;
		}
	}
