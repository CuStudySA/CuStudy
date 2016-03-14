<?php
	$case1  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case1){
		case 'get':
			$subaction  = isset($ENV['URL'][1]) ? $ENV['URL'][1] : 'def';
			switch ($subaction){
				case 'basicInfos':
					if (!isset($ENV['POST']['id'])) System::Respond();
					$id = $ENV['POST']['id'];

					$data = $db->where('id',$id)->getOne('class');
					if (empty($data)) System::Respond();

					System::Respond('',1,array(
						'classid' => $data['classid'],
					));
				break;
			}
		break;

		case 'editBasicInfos':
			if (!empty($ENV['POST']))
				$action = AdminClassTools::EditBasicInfos($ENV['POST']);
			else
				System::Respond();

			System::Respond(Message::Respond('adminClassTools.editBasicInfos',$action),$action == 0 ? 1 : 0);
		break;

		case 'filter':
			if (empty($ENV['POST'])) System::Respond();

			$data = AdminClassTools::FilterClasses($ENV['POST']);
			$html = '<h3>A lekérdezés eredménye: '.count($data).' osztály</h3>'.
					 '<table class="resultTable">
						 <thead>
							<tr>
							  <td>ID</td>
							  <td>Osztály neve</td>
							  <td>Iskola</td>
							  <td>Osztály tanulóinak száma</td>
							  <td>Osztály kezelése</td>
							</tr>
						  </thead>

						  <tbody>';

			foreach ($data as $entry){
				$html .= '<tr>';

				$toPrint = ['classId','className','schoolName','userCount'];
				foreach ($toPrint as $value)
					$html .= "<td>{$entry[$value]}".($value == 'schoolName' ? " (#{$entry['schoolId']})" : '')."</td>";

				$html .= "<td><a href='/system.classes/{$entry['classId']}'>{$entry['className']}</a></td></tr>";
			}

			$html .= '</tbody>
					</table>';

			System::Respond('',1,['html' => $html]);
		break;

		case 'manageMembers':
			if (!empty($ENV['POST']['data']))
				$action = AdminClassTools::ManageMembers($ENV['POST']['data']);
			else
				System::Respond();

			System::Respond(Message::Respond('adminClassTools.manageMembers',$action),$action == 0 ? 1 : 0);
		break;
	}