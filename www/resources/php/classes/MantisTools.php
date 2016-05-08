<?php
	class MantisTools {
		static private function _createUser($User, $password){
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
				'password' => Password::Kodolas($password),
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

		static function CreateUser($User, $password){
			global $db;

			$action = self::_createUser($User, $password);

			$isSuccess = is_array($action);

			if (!is_array($User))
				$user = $db->where('id',$User)->getOne('users');
			else
				$user = $User;

			if (is_array($user))
				$user = System::TrashForeignValues(['username','name','email'],$user);

			Logging::Insert(array_merge(array(
				'action' => 'mantis_users.create',
				'errorcode' => $isSuccess ? 0 : $action,
				'db' => 'mantis_users',
				'user' => $User,
			),$isSuccess ? array(
				'e_id' => $action[0],
			) : array(),
			$isSuccess ? array(
				'userid' => is_array($User) ? $User['id'] : $User,
			) : array(),
			is_array($user) ? $user : array()));

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
	}