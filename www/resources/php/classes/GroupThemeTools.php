<?php

	class GroupThemeTools {
		static function Add($data){
			global $db,$user;

			# Jog. ellenörzése
			If (System::PermCheck('groupThemes.add')) return 1;

			# Szüks. értékek ellenörzése
			$data = System::TrashForeignValues(['name'],$data,true);
			if (!System::ValuesExists($data,['name'])) return 2;

			foreach ($data as $key => $value){
				switch ($key){
					case 'name':
						$type = 'text';
					break;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			$data['classid'] = $user['class'][0];

			$action = $db->insert('group_themes',$data);

			if ($action === false) return 3;
			else return [$action];
		}

		static function Edit($data){
			global $db;

			# Jog. ellenörzése
			If (System::PermCheck('groupThemes.edit',$data['id'])) return 1;

			# Szüks. értékek ellenörzése
			$data = System::TrashForeignValues(['name','id'],$data,true);
			if (!System::ValuesExists($data,['name'])) return 2;
			foreach ($data as $key => $value){
				switch ($key){
					case 'name':
						$type = 'text';
					break;
					case 'id':
						continue 2;
				}
				if (System::InputCheck($value,$type)) return 2;
			}

			$action = $db->where('id',$data['id'])->update('group_themes',$data);

			if ($action) return 0;
			else return 3;
		}

		static function Delete($id){
			global $db,$user;

			# Jog. ellenörzése
			if (System::PermCheck('groupThemes.delete',$id)) return 1;

			# Csoportok törlése
			$groups = $db->where('classid',$user['class'][0])->where('theme',$id)->get('groups');

			foreach ($groups as $group)
				GroupTools::Delete($group['id']);

			# Kategória törlése
			$action = $db->where('id',$id)->delete('group_themes');

			if ($action) return 0;
			else return 2;
		}
	}

