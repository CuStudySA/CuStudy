<?php

	class UserTools {
		static $roleLabels = array(
			'visitor' => 'Ált. felhasználó',
			'editor' => 'Szerkesztő',
			'admin' => 'Csoport adminisztrátor',
			'systemadmin' => 'Rendszer adminisztrátor',
			'none' => 'Nincs jogosultság',
		);

// Felh. adatainak módosítása
		private static function _modifyRole($id,$data){
			global $db, $user;

			# Jog. ellenörzése
			if (System::PermCheck('users.edit')) return 1;

			# Bevitel ellenörzése
			if (System::OptionCheck($data['role'],['visitor','editor','admin'])) return 2;

			# Jog. ellenörzése
			$User = $db->rawQuery('SELECT u.*, cm.id as rId
						FROM `users` u
						LEFT JOIN `class_members` cm
						ON u.id = cm.userid
						WHERE u.id = ? && cm.classid = ?',array($id,$user['class'][0]));
			if (empty($User)) return 1;

			$action = $db->where('userid',$id)->where('classid',$user['class'][0])->update('class_members',$data);

			if ($action) return [$User[0]['rId']];
			else return 7;
		}

		static function ModifyRole($id,$data){
			global $user;

			$data = System::TrashForeignValues(['role'],$data);

			$action = self::_modifyRole($id,$data);
			$isSuccess = is_array($action);

			Logging::Insert(array_merge(array(
				'action' => 'users.modifyRole',
				'errorcode' => $isSuccess ? 0 : $action,
				'db' => 'roles',
			),$data,array(
				'userid' => $id,
			),$isSuccess ? array(
				'e_id' => $action[0],
			) : array()));

			return $action;
		}
// Felh. adatainak módosítása vége

		static private function _ejectUser($id){
			global $db, $user;

			# Jog. ellenörzése
			if (System::PermCheck('users.eject')) return 1;

			$data = $db->rawQuery('SELECT u.*
									FROM `users` u
									LEFT JOIN `class_members` cm
									ON u.id = cm.userid
									WHERE u.id = ? && cm.classid = ?',array($id,$user['class'][0]));
			if (empty($data)) return 1;

			$data = $db->where('userid',$id)->where('classid',$user['class'][0])->getOne('class_members');
			$Juzer = $db->where('id',$id)->getOne('users');
			$Session = $db->where('userid',$id)->where('activeSession',$data['id'])->get('sessions');

			if (!empty($Session))
				System::Logout($Juzer);

			$action = $db->where('userid',$id)->where('classid',$user['class'][0])->delete('class_members');

			if ($Juzer['defaultSession'] == $data['id']){
				$defSession = 0;

				if ($Juzer['role'] == 'none'){
					$Roles = $db->where('userid',$id)->get('class_members');

					if (!empty($Roles))
						$defSession = $Roles[0]['id'];
				}

				$db->where('id',$id)->update('users',array(
					'defaultSession' => $defSession,
				));
			}

			if ($action) return [$data['id'],$data['role'],$data['classid']];
			else return 2;
		}

		static function EjectUser($id){
			global $user;

			$action = self::_ejectUser($id);
			$isSuccess = is_array($action);

			Logging::Insert(array_merge(array(
				'action' => 'users.eject',
				'errorcode' => $isSuccess ? 0 : $action,
				'db' => 'roles',
			),$isSuccess ? array(
				'e_id' => $action[0],
			) : array(),
			$isSuccess ? array(
				'role' => $action[1],
				'classid' => $action[2],
				'userid' => $id,
			) : array()));

			return $action;
		}

		static private function _editMyProfile($data){
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

				$oPwd = $data['password'];
				$data['password'] = Password::Kodolas($data['password']);
			}
			else unset($data['password']);

			unset($data['oldpassword']);
			unset($data['verpasswd']);

			# MantisBT integráció
			$data_m = $data;
			if (!empty($data_m['password']))
				$data_m['password'] = $oPwd;

			if (!empty($user['mantisAccount']))
				MantisTools::EditUser($user['mantisAccount'],$data_m);

			$action = $db->where('id',$user['id'])->update('users',$data);
			$success = $action ? 0 : 3;

			if ($success == 0 && !empty($data['password']))
				Message::SendNotify('users.change-password',$user['id'],!empty($data['name']) ? $data['name'] : $user['name'],array(
					'initiator' => !empty($data['name']) ? $data['name'] : $user['name'],
				));

			return $success;
		}

		static function EditMyProfile($data){
			global $user;

			$action = self::_editMyProfile($data);

			$data = System::TrashForeignValues(['name','email'],$data);

			Logging::Insert(array_merge(array(
				'user' => $user['id'],
				'action' => 'users.editMyProfile',
				'errorcode' => $action,
				'db' => 'users',
			),$data,array(
				'e_id' => $user['id'],
			)));

			return $action;
		}

		static function GetClassGroupIDs($classIndex = 0, $dataType = 'string'){
			global $db, $user;
			$userInGroups = $db->where('classid',$user['class'][$classIndex])->where('userid',$user['id'])->get('group_members',null,'groupid');
			$groups = [0];
			foreach ($userInGroups as $in)
				$groups[] = $in['groupid'];
			switch ($dataType) {
				case 'string': return implode(',', $groups);
				case 'array': return $groups;
			}
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
				$url = 'https://www.gravatar.com/avatar/'.md5($user['email']).'?s=95&r=g&d='.urlencode($defaultAvatar);

			if (!isset($providerOverride))
				$user['picture'] = $url;

			return $url;
		}
	}

