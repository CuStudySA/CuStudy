<?php
	$case1  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case1){
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
	}