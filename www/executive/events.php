<?php
	switch ($ENV['URL'][0]){
		case 'getEvents':
			if (!empty($ENV['GET']['start']) && !empty($ENV['GET']['end']))
				die(json_encode(EventTools::GetEvents($ENV['GET']['start'],$ENV['GET']['end'])));
		break;
		case 'getGlobalEvents':
			if (!empty($ENV['GET']['start']) && !empty($ENV['GET']['end']))
				die(json_encode(EventTools::GetEvents($ENV['GET']['start'],$ENV['GET']['end'],true)));
		break;

		case 'add':
		case 'edit':
			if (empty($ENV['POST']))
				System::Respond();

			$method = $ENV['URL'][0] === 'add' ? 'Add' : 'Edit';
			$action = EventTools::$method($ENV['POST']);
			$message = null;
			if (is_array($action)){
				$field = $action[1];
				$action = $action[0];
				$names = array(
					'isFullDay' => 'Egész napos',
					'title' => 'Cím',
					'description' => 'Rövid leírás',
				);
				$message = System::Article(isset($names[$field]) ? "\"$names[$field]\"" : ' egyik ').'  mező formátuma hibás';
			}

			System::Respond(Message::Respond("events.{$ENV['URL'][0]}",$action,$message), $action == 0 ? 1 : 0);
		break;

		case 'delete':
			if (!empty($ENV['POST']['id']))
				$action = EventTools::Delete($ENV['POST']['id']);
			else
				System::Respond();

			System::Respond(Message::Respond('events.delete',$action), $action == 0);
		break;

		case 'getEventInfos':
			if (!empty($ENV['POST']['id'])){
				if (empty($ENV['URL'][1])){
					$data = EventTools::GetEventInfos($ENV['POST']['id']);

					if (is_int($data)) System::Respond(Message::Respond('events.getInfos',$data), $data == 0 ? 1 : 0);

					$html = '';
					foreach ($data as $key => $value){
						$kettosPont = $key == 'Egész napos?' ? '' : ':';
						$html .= "<p><b>{$key}{$kettosPont}</b> {$value}</p>";
					}

					System::Respond(array('html' => $html));
				}
				else {
					$data = $db->where('id',$ENV['POST']['id'])->where('classid',$user['class'][0])->getOne('events');

					if (!empty($data)){
						$dates = EventTools::ParseDates($data['start'], $data['end'], $data['isallday']);
						$format = 'Y.m.d.'.(!$data['isallday']?' H:i:s':'');
						$data['start'] = date($format, $dates[0]);
						$data['end'] = date($format, $dates[1]);

						System::Respond($data);
					}
					else System::Respond();
				}
			}
			else
				System::Respond();
		break;
	}
