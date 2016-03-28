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
		private static function _modifyUser($id,$data){
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

		static function ModifyUser($id,$data){
			global $user;

			$data = System::TrashForeignValues(['role'],$data);

			$action = self::_modifyUser($id,$data);
			$isSuccess = is_array($action);

			Logging::Insert(array_merge(array(
				'action' => 'role_edit',
				'user' => $user['id'],
				'errorcode' => $isSuccess ? 0 : $action,
				'db' => 'role_edit',
			),$data,array(
				$isSuccess ? 'e_id' : 'userid' => $isSuccess ? $action[0] : $id,
			)));

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

			if ($action) return [$data['id'],$data['role']];
			else return 2;
		}

		static function EjectUser($id){
			global $user;

			$action = self::_ejectUser($id);
			$isSuccess = is_array($action);

			Logging::Insert(array_merge(array(
				'action' => 'role_del',
				'user' => $user['id'],
				'errorcode' => $isSuccess ? 0 : $action,
				'db' => 'role_del',
			),array(
				$isSuccess ? 'e_id' : 'userid' => $isSuccess ? $action[0] : $id,
			),$isSuccess ? array(
				'role' => $action[1],
			) : array()));

			return $action;
		}

		static function EditMyProfile($data){
/*          array(
				(req)'name',
				(req)'email',
				(opt)'oldpassword',
				(opt)'password',
				(opt)'verpasswd'
			)                       */

			global $db,$user,$MantisDB;

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
			if (!is_int($MantisDB)){
				if (!empty($user['mantisAccount'])){
					$data_m = $data;

					if (!empty($data_m['password']))
						$data_m['password'] = $oPwd;

					MantisTools::EditUser($user['mantisAccount'],$data_m);
				}
			}

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

