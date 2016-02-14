<?php
	class MantisTools {
		static function CreateUser($User, $password){
			global $MantisDB, $db;

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
					return 3;

			$time = time();
			$action = $MantisDB->insert('mantis_user_table',array(
				'username' => $user['username'],
				'realname' => $user['name'],
				'email' => $user['email'],
				'password' => md5($password),
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

				return 0;
			}
			else
				return [$action];
		}

		static function EditUser($id,$data){
			/**
			 * array(
			 *  'username'
			 *  'name'
			 *  'password'
			 *  'email'
			 * )
			 */
			global $MantisDB;

			if (is_int($MantisDB))
				return 1;

			$check = $MantisDB->where('id',$id)->getOne('mantis_user_table');
			if (empty($cehck))
				return 2;

			$data = System::TrashForeignValues(['username','name','password','email'],$data);
			foreach ($data as $key => $value)
				if (System::InputCheck($value,$key)) return 3;

			if (!empty($data['name'])){
				$data['realname'] = $data['name'];
				unset($data['name']);
			}

			if (!empty($data['password']))
				$data['password'] = md5($data['password']);

			$action = $MantisDB->where('id',$id)->update('mantis_user_table',$data);

			if ($action) return 0;
			else return 4;
		}
	}