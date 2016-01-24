<?php

	class UserTools {
		static $roleLabels = array(
			'visitor' => 'Ált. felhasználó',
			'editor' => 'Szerkesztő',
			'admin' => 'Csoport adminisztrátor',
			'systemadmin' => 'Rendszer adminisztrátor',
			'none' => 'Nincs jogosultság',
		);

		// Felh. hozzáadása
		private static function _addUser($data_a){
			global $db, $user;

			# Jog. ellelnörzése
			if(System::PermCheck('users.add')) return 7;

			# Bevitel ellenörzése
			if (!System::ValuesExists($data_a,['username','name','role','email','active'])) return 1;
			foreach ($data_a as $key => $value){
				if (in_array($key,['classid','role'])) continue;

				switch ($key){
					case 'name':
						$type = 'name';
						break;
					case 'active':
						$type = 'numeric';
						break;
					default:
						$type = $key;
						break;
				}

				if (System::InputCheck($value,$type)) return 2;
			}
			if (System::OptionCheck($data_a['active'],['0','1'])) return 2;
			if (System::OptionCheck($data_a['role'],['visitor','editor','admin'])) return 2;

			# Létezik-e már ilyen felhasználó?
			$data = $db->where('username',$data_a['username'])->getOne('users');
			if (!empty($data)) return 4;
			$data = $db->where('email',$data_a['email'])->getOne('users');
			if (!empty($data)) return 6;

			# Ideiglenes jelszó készítése
			$data_a['password'] = Password::Kodolas(Password::Generalas(6));

			# Regisztráció
			$id = $db->insert('users',$data_a);
			if ($id === false) return 7;

			# Hozzáadás a csoporthoz
			$db->insert('class_members',array(
				'classid' => $user['class'][0],
				'userid' => $id,
			));

			return [$id];
		}

		static function AddUser($data_a){
			global $user;
			/*			array(
							'username',
							'name',
							'priv',
							'email',
							'active',
						);					*/
			$action = self::_addUser($data_a);

			$data_a = System::TrashForeignValues(['username','name','role','email','active'],$data_a);

			Logging::Insert(array_merge(array(
				'action' => 'user_add',
				'user' => $user['id'],
				'errorcode' => (!is_array($action) ? $action : 0),
				'db' => 'user_add',
			),$data_a,array(
				'classid' => $user['class'][0],
				'e_id' => (is_array($action) ? $action[0] : 0),
			)));

			return $action;
		}
		// Felh. hozzáadás vége

		// Felh. adatainak módosítása
		private static function _modifyUser($id,$datas){
			global $db, $user;

			# Jog. ellenörzése
			if (System::PermCheck('users.edit')) return 1;

			# Formátum ellenörzése
			foreach ($datas as $key => $value){
				if (in_array($key,['classid','role'])) continue;

				switch ($key){
					case 'name':
						$type = 'name';
						break;
					case 'id':
						$type = 'numeric';
						break;
					case 'active':
						$type = 'numeric';
						break;
					default:
						$type = $key;
						break;
				}

				if (System::InputCheck($value,$type)) return 2;
			}
			if (System::OptionCheck($datas['active'],['0','1'])) return 2;
			if (System::OptionCheck($datas['role'],['visitor','editor','admin'])) return 2;

			# Jog. ellenörzése
			$data = $db->rawQuery('SELECT u.*
						FROM `users` u
						LEFT JOIN `class_members` cm
						ON u.id = cm.userid
						WHERE u.id = ? && cm.classid = ?',array($datas['id'],$user['class'][0]));
			if (empty($data)) return 1;

			# Létezik-e már ilyen felhasználó?
			$userdata = $db->where('id',$id)->getOne('users');

			if($datas['email'] != $userdata['email']){
				$data = $db->where('email',$datas['email'])->getOne('users');
				if (!empty($data)) return 6;
			}

			if (!empty($datas['username'])) unset($datas['username']);
			$action = $db->where('id',$id)->update('users',$datas);

			if ($action) return 0;
			else return 7;
		}

		static function ModifyUser($id,$datas){
			global $user;

			$action = self::_modifyUser($id,$datas);

			$datas = System::TrashForeignValues(['username','name','priv','email','active'],$datas);

			Logging::Insert(array_merge(array(
				'action' => 'user_edit',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'user_edit',
			),$datas,array(
				'classid' => $user['class'][0],
				'e_id' => $id,
			)));

			return $action;
		}
		// Felh. adatainak módosítása vége

		// Felh. törlése
		private static function _deleteUser($id){
			global $db;

			$action = $db->where('id',$id)->delete('users');

			if ($action) return 0;
			else return 2;
		}

		static function DeleteUser($id){
			global $user,$db;

			# Jog. ellenörzése
			if (System::PermCheck('users.delete')) return 1;

			$data = $db->rawQuery('SELECT u.*
									FROM `users` u
									LEFT JOIN `class_members` cm
									ON u.id = cm.userid
									WHERE u.id = ? && cm.classid = ?',array($id,$user['class'][0]));
			if (empty($data)) return 1;
			$data = $data[0];

			$data = System::TrashForeignValues(['username','name','role','email','active'],$data);

			$action = self::_deleteUser($id);

			Logging::Insert(array_merge(array(
				'action' => 'user_del',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'user_del',
			),$data,array(
				'classid' => $user['class'][0],
				'e_id' => $id,
			)));

			return $action;
		}
		// Felh. törlése vége

		static function EditAccessData($id,$data){
			/* @param $id
			 * @param $data = array('newpassword','vernewpasswd')
			 */

			global $db,$user;

			# Jog. ellenörzése
			if (System::PermCheck('users.editSecurity')) return 1;
			$exists = $db->rawQuery('SELECT u.*
						FROM `users` u
						LEFT JOIN `class_members` cm
						ON u.id = cm.userid
						WHERE u.id = ? && cm.classid = ?',array($id,$user['class'][0]));
			if (empty($exists)) return 1;

			if ($data['newpassword'] != $data['vernewpasswd']) return 2;

			$action = $db->where('id',$id)->update('users',array(
				'password' => Password::Kodolas($data['newpassword']),
			));

			if ($action) return 0;
			else return 3;
		}

		static function EditMyProfile($data){
			/*          array(
							(req)'name',
							(req)'email',
							(opt)'oldpassword',
							(opt)'password',
							(opt)'verpasswd'
						)                       */

			global $db,$user;

			# Felhasználó jelszavának ellenörzése
			if (!Password::Ellenorzes($data['oldpassword'],$user['password'])) return 1;

			# Jelszóváltoztatás esetén...
			if (!empty($data['oldpassword']) && !empty($data['password']) && !empty($data['verpasswd'])){
				if ($data['password'] != $data['verpasswd']) return 2;

				$data['password'] = Password::Kodolas($data['password']);
			}
			else unset($data['password']);

			unset($data['oldpassword']);
			unset($data['verpasswd']);

			$action = $db->where('id',$user['id'])->update('users',$data);

			return $action ? 0 : 3;
		}

		static function SetAvatarProvider($provider){
			global $user,$db;

			if (empty($provider))
				$provider = null;
			else {
				if (empty(ExtConnTools::$apiDisplayName[$provider]))
					return 1;

				$Linked = $db->where('userid',$user['id'])->where('provider', $provider)->has('ext_connections');
				if (!$Linked)
					return 2;
			}

			$action = $db->where('id',$user['id'])->update('users',array( 'avatar_provider' => $provider ));
			$user['avatar_provider'] = $provider;

			return $action ? 0 : 3;
		}

		static function GetAvatarURL(&$user, $providerOverride = null){
			global $db;

			if (isset($user['picture']) && !isset($providerOverride))
				return $user['picture'];

			$defaultAvatar = str_replace('.lc','.hu',ABSPATH).'/resources/img/user.png';
			$provider = isset($providerOverride) ? $providerOverride : $user['avatar_provider'];
			if ($provider !== 'gravatar'){
				$url = $db->where('userid', $user['id'])->where('provider', $provider)->getOne('ext_connections','picture');
				if (!empty($url))
					$url = $url['picture'];
			}
			if (empty($url))
				$url = 'https://www.gravatar.com/avatar/'.md5($user['email']).'?s=70&r=g&d='.urlencode($defaultAvatar);

			if (!isset($providerOverride))
				$user['picture'] = $url;

			return $url;
		}
	}
