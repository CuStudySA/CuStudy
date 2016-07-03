<?php
	class MantisTools {
		static private function _createUser($User, $password = null){
			global $db;

			System::LoadLibrary('mantisIntegration');
			global $MantisDB;

			if (is_int($MantisDB))
				return 3;

			if (!is_array($User))
				$user = $db->where('id',$User)->getOne('users');
			else
				$user = $User;

			if (!empty($user['mantisAccount']))
				return 1;

			if (!empty($MantisDB->where('email',$user['email'])->getOne('mantis_user_table')) ||
				!empty($MantisDB->where('username',$user['username'])->getOne('mantis_user_table')))
					return 4;

			$time = time();
			$action = $MantisDB->insert('mantis_user_table',array(
				'username' => $user['username'],
				'realname' => $user['name'],
				'email' => $user['email'],
				'password' => empty($password) ? $user['password'] : Password::Kodolas($password),
				'enabled' => 1,
				'protected' => 1,
				'access_level' => 25,
				'cookie_string' => Password::Generalas(),
				'last_visit' => $time,
				'date_created' => $time,
			));

			if ($action === false) return 2;

			if (!is_array($User)){
				$db->where('id',$User)->update('users',array(
					'mantisAccount' => $action,
				));
			}

			return [$action];
		}

		static function CreateUser($User, $password = null){
			global $db, $user;

			$action = self::_createUser($User, $password);

			$isSuccess = is_array($action);

			if (!is_array($User))
				$profile = $db->where('id',$User)->getOne('users');
			else
				$profile = $User;

			if (is_array($profile))
				$profile = System::TrashForeignValues(['username','name','email'],$profile);

			Logging::Insert(array_merge(array(
				'action' => 'mantis_users.create',
				'errorcode' => $isSuccess ? 0 : $action,
				'db' => 'mantis_users',
				'user' => !empty($user['id']) ? $user['id'] : $User,
			),$isSuccess ? array(
				'e_id' => $action[0],
			) : array(),
			$isSuccess ? array(
				'userid' => is_array($User) ? $User['id'] : $User,
			) : array(),
			is_array($profile) ? $profile : array()));

			return $action;
		}

		static private function _editUser($id,$data){
			/**
			 * array(
			 *  'username'
			 *  'name'
			 *  'password'
			 *  'email'
			 * )
			 */

			System::LoadLibrary('mantisIntegration');
			global $MantisDB;

			if (is_int($MantisDB))
				return 1;

			$check = $MantisDB->where('id',$id)->getOne('mantis_user_table');
			if (empty($check))
				return 2;

			$data = System::TrashForeignValues(['username','name','password','email'],$data);
			foreach ($data as $key => $value){
				if (System::InputCheck($value,$key)) return 3;
			}

			if (!empty($data['name'])){
				$data['realname'] = $data['name'];
				unset($data['name']);
			}

			if (!empty($data['password']))
				$data['password'] = Password::Kodolas($data['password']);

			$action = $MantisDB->where('id',$id)->update('mantis_user_table',$data);

			if ($action) return 0;
			else return 4;
		}

		static function EditUser($id,$data){
			global $user;

			$action = self::_editUser($id,$data);

			$data = System::TrashForeignValues(['username','name','email'],$data);

			Logging::Insert(array_merge(array(
				'action' => 'mantis_users.edit',
				'errorcode' => $action,
				'db' => 'mantis_users',
				'user' => $user['id'],
			),$data,array(
				'e_id' => $id,
			)));

			return $action;
		}

		static function GetUserMantisStatus($userid){
			global $db;

			System::LoadLibrary('mantisIntegration');
			global $MantisDB;

			if (is_int($MantisDB))
				return 1;

			$User = $db->where('id',$userid)->getOne('users');
			if (empty($User))
				return 2;

			if (empty($User['mantisAccount']))
				return 'not_connected';

			$check = $MantisDB->where('id',$User['mantisAccount'])->getOne('mantis_user_table');
			if (empty($check))
				return 'not_connected';

			return [$User['mantisAccount']];
		}

		static private function _updateUser($id = null, $userid = null){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.users.view')) return 1;

			System::LoadLibrary('mantisIntegration');
			global $MantisDB;

			if (is_int($MantisDB))
				return 2;

			if (empty($id)){
				$getId = $db->where('id',$userid)->getOne('users');
				if (empty($getId))
					return 3;
				if (empty($getId['mantisAccount']))
					return 3;

				$id = $getId['mantisAccount'];
			}

			if (empty($userid)){
				$getUser = $db->where('mantisAccount',$id)->getOne('users');

				if (empty($getUser))
					return 3;

				$userid = $getUser['id'];
			}

			if (!isset($getUser))
				$getUser = $db->where('id',$userid)->getOne('users');

			$action = $MantisDB->where('id',$id)->update('mantis_user_table',array(
				'email' => $getUser['email'],
				'realname' => $getUser['name'],
				'password' => $getUser['password'],
				'username' => $getUser['username'],
			));

			if ($action) return 0;
			else return 4;
		}

		static function UpdateUser($id = null, $userid = null){
			global $user;

			$action = self::_updateUser($id,$userid);

			Logging::Insert(array_merge(array(
				'action' => 'mantis_users.update',
				'errorcode' => $action,
				'db' => 'mantis_users',
				'user' => $user['id'],
			),!empty($id) ? array('e_id' => $id) : array(),
			  !empty($userid) ? array('userid' => $userid) : array()));

			return $action;
		}

		static private function _deleteUser($id = null, $userid = null){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.users.view')) return 1;

			System::LoadLibrary('mantisIntegration');
			global $MantisDB;

			if (is_int($MantisDB))
				return 2;

			if (empty($id)){
				$getId = $db->where('id',$userid)->getOne('users');
				if (empty($getId))
					return 3;
				if (empty($getId['mantisAccount']))
					return 3;

				$id = $getId['mantisAccount'];
			}

			$User = $MantisDB->where('id',$id)->getOne('mantis_user_table');
			if (empty($User))
				return 3;

			$data = [];
			if (!empty($User['username']))
				$data['username'] = $User['username'];
			if (!empty($User['realname']))
				$data['name'] = $User['realname'];
			if (!empty($User['email']))
				$data['email'] = $User['email'];

			if (!empty($userid)){
				$userid = $db->where('mantisAccount',$id)->getOne('users');
				if (!empty($userid))
					$data['userid'] = $userid['id'];
			}

			// Függőségek törlése
			$MantisDB->where('user_id',$id)->delete('mantis_user_pref_table');
			$MantisDB->where('user_id',$id)->delete('mantis_user_profile_table');
			$MantisDB->where('user_id',$id)->delete('mantis_project_user_list_table');

			$action = $MantisDB->where('id',$id)->delete('mantis_user_table');

			$db->where('id',$data['userid'])->update('users',array(
				'mantisAccount' => 0,
			));

			if ($action) return $data;
			else return 4;
		}

		static function DeleteUser($id = null, $userid = null){
			global $user, $db;

			System::LoadLibrary('mantisIntegration');
			global $MantisDB;

			$action = self::_deleteUser($id,$userid);

			Logging::Insert(array_merge(array(
				'action' => 'mantis_users.delete',
				'errorcode' => is_array($action) ? 0 : $action,
				'db' => 'mantis_users',
				'user' => $user['id'],
			),is_array($action) ? $action : [],
			!empty($id) ? array('e_id' => $id) : [], !empty($userid) && empty($action['userid']) ? array('userid' => $userid) : []));

			return $action;
		}
	}