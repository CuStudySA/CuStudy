<?php

	class LessonTools {
// Tantárgy hozzáadása
		private static function _add($data){
			global $db,$ENV;

			# Jog. ellenörzése
			if (System::PermCheck('lessons.add')) return 1;

			# Formátum ellenörzése
			if (!System::ValuesExists($data,['name','teacherid'])) return 2;
			foreach ($data as $key => $value){
				if ($key == 'color') continue;
				switch ($key){
					case 'name':
						$type = 'lesson';
					break;
					case 'teacherid':
						$type = 'numeric';
					break;
					default:
						unset($data[$key]);
						continue 2;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			if (!isset($data['color']) || $data['color'] == '#000000') $data['color'] = 'default';
			$data['classid'] = $ENV['class']['id'];

			return [$db->insert('lessons',$data)];
		}
		static function Add($data_a){
			global $user;

			$action = self::_add($data_a);

			Logging::Insert(array_merge(array(
				'action' => 'lessons.add',
				'user' => $user['id'],
				'errorcode' => (!is_array($action) ? $action : 0),
				'db' => 'lessons',
			),$data_a,array(
				'classid' => $user['class'][0],
				'e_id' => (is_array($action) ? $action[0] : 0),
			)));


			return $action;
		}
// Tantárgy hozzáadása vége

// Tantárgy szerkesztése
		private static function _edit($data_a){
			global $db;

			# Formátum ellenörzése
			if (!System::ValuesExists($data_a,['name','teacherid','id'])) return 2;
			foreach ($data_a as $key => $value){
				if ($key == 'color') continue;
				switch ($key){
					case 'name':
						$type = 'lesson';
					break;
					case 'teacherid':
						$type = 'numeric';
					break;
					case 'id':
						$type = 'numeric';
					break;
					default:
						unset($data_a[$key]);
						continue;
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			# Jogosultság ellenörzése
			if (System::PermCheck('lessons.edit',$data_a['id'])) return 1;

			$action = $db->where('id',$data_a['id'])->update('lessons',$data_a);

			if ($action) return 0;
			else return 3;
		}
		static function Edit($data_a){
			global $user;

			$action = self::_edit($data_a);

			if (isset($data_a['id'])){
				$data_a['e_id'] = $data_a['id'];
				unset($data_a['id']);
			}
			else $data_a['id'] = 0;

			Logging::Insert(array_merge(array(
				'action' => 'lessons.edit',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'lessons',
			),$data_a,array(
				'classid' => $user['class'][0],
			)));

			return $action;
		}
// Tantárgy szerkesztése vége

// Tantárgy törlése
		private static function _delete($id){
			global $db;

			$action = $db->where('id',$id)->delete('lessons');

			$data = $db->rawQuery('SELECT tt.id
									FROM `timetable` tt
									WHERE tt.lessonid = ?',array($id));


			if (!empty($data)){
				Timetable::DeleteEntrys($data);
			}

			if ($action) return 0;
			else return 2;
		}
		static function Delete($id){
			global $user,$db;

			# Jog. ellenörzése
			if (System::PermCheck('lessons.delete',$id)) return 1;

			$data = $db->where('id',$id)->getOne('lessons');
			$data = System::TrashForeignValues(['classid','name','teacherid','color'],$data);

			$action = self::_delete($id);

			Logging::Insert(array_merge(array(
				'action' => 'lessons.delete',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'lessons',
			),$data,array(
				'classid' => $user['class'][0],
				'e_id' => $id,
			)));

			return $action;
		}
	}
// Tantárgy törlése vége

