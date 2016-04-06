<?php
	class TeacherTools {
		static function Add($data){
			global $user;

			$action = self::_add($data);

			unset($data['lessons']);
			$data = System::TrashForeignValues(['name','short'],$data);

			Logging::Insert(array_merge(array(
				'action' => 'teacher_add',
				'user' => $user['id'],
				'errorcode' => is_array($action) ? 0 : $action,
				'db' => 'teacher_add',
			),$data,array(
				'classid' => $user['class'][0],
				'e_id' => is_array($action) ? $action[0] : 0,
			)));

			return $action;
		}

		static private function _add($datas){
			global $db,$user;

			# Jog. ellenörzése
			if (System::PermCheck('teachers.add')) return 1;

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
			foreach ($datas['lessons'] as $sublesson)
				LessonTools::Add(array(
					'name' => $sublesson['name'],
					'teacherid' => $action,
					'color' => $sublesson['color']
				));

			return [$action];
		}

		static function Edit($data){
			global $user;

			$action = self::_edit($data);

			$data = System::TrashForeignValues(['id','short','name'],$data);

			if (!empty($data['id'])){
				$id = $data['id'];
				unset($data['id']);
			}
			else
				$id = 0;

			Logging::Insert(array_merge(array(
				'action' => 'teacher_edit',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'teacher_edit',
			),$data,array(
				'e_id' => $id,
			)));

			return $action;
		}

		static private function _edit($data){
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
			global $user, $db;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('teachers');
			if (!empty($data))
				unset($data['id']);
			else
				$data = [];

			$action = self::_delete($id);

			Logging::Insert(array_merge(array(
				'action' => 'teacher_del',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'teacher_del',
			),$data,array(
				'e_id' => $id,
			)));

			return $action;
		}

		static private function _delete($id){
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

