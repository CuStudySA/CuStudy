<?php
	$case1  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case1){
		case 'get':
			$case2  = isset($ENV['URL'][1]) ? $ENV['URL'][1] : 'def';
			if (empty($ENV['POST']['id'])) System::Respond();
			$id = $ENV['POST']['id'];

			switch ($case2){
				case 'userInfos':
					$data = $db->where('id',$id)->getOne('users');
					if (empty($data)) System::Respond();

					System::Respond('',1,array(
						'username' => $data['username'],
						'name' => $data['name'],
						'email' => $data['email'],
					));
				break;

				case 'roles':
					$Classes = AdminUserTools::GetLocalRoles($id);
					$User = $db->where('id',$id)->getOne('users');

					$return = array();
					foreach ($Classes as $role){
						$return[] = array(
							'school' => $role['schoolName'].' '.$role['className'].' osztálya',
							'role' => UserTools::$roleLabels[$role['role']],
							'id' => $role['id'],
						);
					}

					if ($User['role'] != 'none')
						$return[] = array(
							'school' => 'CuStudy',
							'role' => UserTools::$roleLabels[$User['role']],
							'id' => 0,
						);

					System::Respond('',1,array('roles' => $return));
				break;

				case 'role':
					$role = $db->where('id',$id)->getOne('class_members');
					if (empty($role)) System::Respond();

					System::Respond('',1,array(
						'role' => $role['role'],
					));
				break;
			}
		break;

		case 'filter':
			if (empty($ENV['POST'])) System::Respond();

			$data = AdminUserTools::FilterUsers($ENV['POST']);
			$html = '<h3>A lekérdezés eredménye: '.count($data).' felhasználó</h3>'.
					 '<table class="resultTable">
					     <thead>
					        <tr>
					          <td>ID</td>
					          <td>Név</td>
					          <td>E-mail cím</td>
					          <td>Globális jogosultság</td>
					          <td>Felh. kezelése</td>
					        </tr>
		                  </thead>

		                  <tbody>';

		    foreach ($data as $entry){
		        $html .= '<tr>';

		        $toPrint = ['id','name','email','role'];
		        foreach ($toPrint as $label){
		            if ($label == 'role'){
		                $role = UserTools::$roleLabels[$entry[$label]];
		                $html .= "<td>{$role}</td>";
		                continue;
		            }

		            if (!is_array($entry[$label]))
		                $html .= "<td>".(empty($entry[$label]) ? '(ismeretlen)' : $entry[$label])."</td>";
		            else {
		                $string = implode('<br>',$entry[$label]);
		                $html .= "<td>{$string}</td>";
		            }
		        }
				$html .= "<td><a href='/system.users/{$entry['id']}'>{$entry['username']}</a></td>";

		        $html .= '</tr>';
		    }

		    $html .= '</tbody>
					</table>';

			System::Respond('',1,['html' => $html]);
		break;

		case 'editBasicInfos':
			if (!empty($ENV['POST']))
				$action = AdminUserTools::EditBasicInfos($ENV['POST']);
			else
				System::Respond();

			System::Respond(Message::Respond('adminUserTools.editBasicInfos',$action),$action == 0 ? 1 : 0);
		break;

		case 'deleteRole':
			if (isset($ENV['POST']['id']) && isset($ENV['POST']['userId']))
				$action = AdminUserTools::DeleteRole($ENV['POST']['id'],$ENV['POST']['userId']);
			else
				System::Respond();

			System::Respond(Message::Respond('adminUserTools.deleteRole',$action),$action == 0 ? 1 : 0);
		break;

		case 'editRole':
			if (!empty($ENV['POST']))
				$action = AdminUserTools::EditRole($ENV['POST']);
			else
				System::Respond();

			System::Respond(Message::Respond('adminUserTools.editRole',$action),$action == 0 ? 1 : 0);
		break;
	}