<?php
	$case  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case){
		case 'users':
			$case1  = isset($ENV['URL'][1]) ? $ENV['URL'][1] : 'def';

			switch ($case1){
				case 'filter':
					if (empty($ENV['POST'])) System::Respond();

					$data = AdminTools::FilterUsers($ENV['POST']);
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
						$html .= "<td><a href='/system/users/{$entry['id']}'>{$entry['username']}</a></td>";

				        $html .= '</tr>';
				    }

				    $html .= '</tbody>
							</table>';

					System::Respond('',1,['html' => $html]);
				break;
			}
		break;
	}