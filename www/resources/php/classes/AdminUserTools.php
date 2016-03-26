<?php

	class AdminUserTools {
		static function GetLocalRoles($id){
			global $db;

			return $db->rawQuery('SELECT cm.*, c.classid as className, c.id as classId, s.name as schoolName, s.id as schoolId
									FROM `class_members` cm
									LEFT JOIN (`class` c, `school` s)
									ON (c.id = cm.classid && s.id = c.school)
									WHERE cm.userid = ?',array($id));
		}

		static function FilterUsers($form){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.users.view')) return 1;

			$query = 'SELECT u.*, c.classid as classname, c.id as classid, s.name as schoolname
						FROM `users` u
						LEFT JOIN (`class` c, `school` s, `class_members` cm)
						ON (cm.userid = u.id && cm.classid = c.id && c.school = s.id)
						WHERE ';

			$whereIsUsed = false;
			foreach ($form as $key => $value){
				if (empty($value)) continue;

				if (substr($key,0,1) == 'u') $query .= str_replace('_','.',$key)." REGEXP '^{$value}$' && ";
				else {
					$cn = substr($key,0,1);
					if (is_numeric($value)) $cn .= '.id';
					else $cn .= '.'.($cn == 'c' ? 'classid' : 'name');
					$query .= "{$cn} REGEXP '^{$value}$' && ";
				}
				$whereIsUsed = true;
			}

			if ($whereIsUsed)
				$query = substr($query,0,strlen($query)-4);
			else
				$query = substr($query,0,strlen($query)-6);

			$data = $db->rawQuery($query);
			$return = array();

			foreach ($data as $array){
				if (!isset($return[$array['id']]))
					$return[$array['id']] = $array;
				else {
					if (is_array($return[$array['id']]['classid']))
						$return[$array['id']]['classid'][] = $array['classid'];
					else
						$return[$array['id']]['classid'] = array($return[$array['id']]['classid'],$array['classid']);

					if (is_array($return[$array['id']]['classname']))
						$return[$array['id']]['classname'][] = $array['classname'];
					else
						$return[$array['id']]['classname'] = array($return[$array['id']]['classname'],$array['classname']);

					if (is_array($return[$array['id']]['schoolname']))
						$return[$array['id']]['schoolname'][] = $array['schoolname'];
					else
						$return[$array['id']]['schoolname'] = array($return[$array['id']]['schoolname'],$array['schoolname']);
				}
			}

			return $return;
		}

		static private function _editBasicInfos($data){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.users.view')) return 1;

			$data = System::TrashForeignValues(['id','name','username','email'],$data);
			foreach ($data as $key => $value)
				if (System::InputCheck($value,$key == 'id' ? 'numeric' : $key)) return 2;

			if (!empty($data['email'])){
				$emailCheck = $db->where('email',$data['email'])->getOne('users');
				if (!empty($emailCheck))
					if ($emailCheck['email'] != $data['email'])
						return 3;
			}

			if (!empty($data['username'])){
				$usernameCheck = $db->where('username',$data['username'])->getOne('users');
				if (!empty($usernameCheck))
					if ($usernameCheck['username'] != $data['username'])
						return 4;
			}

			$action = $db->where('id',$data['id'])->update('users',$data);

			if ($action) return 0;
			else return 5;
		}

		static function EditBasicInfos($data){
			global $user;

			$data = System::TrashForeignValues(['id','name','username','email'],$data);
			$action = self::_editBasicInfos($data);

			$eid = $data['id'];
			unset($data['id']);

			Logging::Insert(array_merge(array(
				'action' => 'user_edit',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'user_edit',
			),$data,array(
				'e_id' => $eid,
			)));

			return $action;
		}

		private static function _ChangeDefaultRole($toRemoveRole,$User){
			global $db;

			if ($User['defaultSession'] == $toRemoveRole){
				$defSession = 0;

				if ($User['role'] == 'none'){
					$Roles = $db->where('userid',$User['id'])->get('class_members');

					if (!empty($Roles))
						$defSession = $Roles[0]['id'];
				}

				$db->where('id',$User['id'])->update('users',array(
					'defaultSession' => $defSession,
				));
			}
		}

		static private function DeleteRole($id,$userid){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.users.view')) return 1;

			$Juzer = $db->where('id',$userid)->getOne('users');
			if (empty($Juzer)) return 2;

			if ($id == 0){
				self::_ChangeDefaultRole($id,$Juzer);

				$action = $db->where('id',$userid)->update('users',array(
					'role' => 'none',
				));

				if ($action) return 0;
				else return 3;
			}

			$data = $db->where('id',$id)->getOne('class_members');
			if (empty($id)) return 4;

			self::_ChangeDefaultRole($id,$Juzer);

			$action = $db->where('id',$id)->delete('class_members');

			if ($action) return 0;
			else return 3;
		}

		static function EditRole($data){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.users.view')) return 1;

			# Bevitel ellenörzése
			if (!isset($data['role']) || !isset($data['id'])) return 2;
			if (System::OptionCheck($data['role'],['visitor','editor','admin'])) return 3;

			if (empty($db->where('id',$data['id'])->getOne('class_members'))) return 4;

			$action = $db->where('id',$data['id'])->update('class_members',array(
				'role' => $data['role'],
			));

			if ($action) return 0;
			else return 5;
		}

		static private function _deleteUser($id){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('system.users.view')) return 1;

			# Létezik-e a felhasználó?
			$data = $db->where('id',$id)->getOne('users');
			if (empty($data)) return 2;

			# Felhasználó kiléptetése a rendszerből
			$db->where('userid',$id)->delete('sessions');

			# Felhasználó kiléptetése a szerepköreiből
			$db->where('userid',$id)->delete('class_members');

			# Felhasználó kiléptetése a csoportjaiból
			$db->where('userid',$id)->delete('group_members');

			# Egy kis takarítás a felhasználó után...
			$db->where('userid',$id)->delete('hw_markdone');

			# Törlés a felhasználók táblájából
			$db->where('id',$id)->delete('users');

			return $data;
		}

		static function DeleteUser($id){
			global $db, $user;

			$action = self::_deleteUser($id);

			if (is_array($action)){
				$data = System::TrashForeignValues(['username','name','role','active','email','defaultSession','avatar_provider','mantisAccount'],$action);

				$action = is_array($action) ? 0 : $action;
			}
			else
				$data = [];

			Logging::Insert(array_merge(array(
				'action' => 'user_del',
				'user' => $user['id'],
				'errorcode' => $action,
				'db' => 'user_del',
			),$data,array(
				'e_id' => $id,
			)));

			return $action;
		}

		static function ShowFilter($noSidebar = false){ ?>
			<script>
				var noSidebar = <?=$noSidebar ? 'true' : 'false'?>;
			</script>

			<h2 id='filterTitle' <?=$noSidebar ? 'style="margin-top: 0"' : ''?>>Felhasználók szűrése</h2>

			<button class='btn typcn typcn-arrow-up-thick hide' id='js_hideShowFilter'>Szűrőpanel összecsukása</button>

			<div id='filterFormContainer'>
				<form id='filterForm'>
					<table class='filterFormTable'>
						<thead>
							<tr>
							  <td>Szűrési feltétel</td>
							  <td>Érték</td>
							</tr>
		                </thead>

		                <tbody>
							<tr>
								<td>Felhasználónév</td>
								<td><input type="text" name='u_username'></td>
							</tr>
							<tr>
								<td>Teljes név</td>
								<td><input type="text" name='u_name'></td>
							</tr>
							<tr>
								<td>E-mail cím</td>
								<td><input type="text" name='u_email'></td>
							</tr>
							<tr>
								<td>Felhasználó ID</td>
								<td><input type="text" name='u_id'></td>
							</tr>

							<tr>
			                    <td colspan="2" class='focim'>Osztály</td>
			                </tr>
			                <tr>
								<td>Osztály neve / Osztály ID</td>
								<td><input type="text" name='c_id'></td>
							</tr>

			                <tr>
			                    <td colspan="2" class='focim'>Iskola</td>
			                </tr>
			                <tr>
						      <td>Iskola neve / Iskola ID</td>
						      <td><input type="text" name='s_id'></td>
			                </tr>
		                </tbody>
		            </table>
		            <button class='btn typcn typcn-zoom js_filterUsers'>Felhasználók szűrése</button>
		        </form>
	        </div>

	        <div id='resultContainer'></div>
<?php	}
	}