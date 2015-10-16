<?php
	switch ($ENV['URL'][0]){
		case 'getEvents':
			echo json_encode(EventTools::GetEvents($ENV['GET']['start'],$ENV['GET']['end']));
		break;

		case 'add':
			if (!empty($ENV['POST']))
				$action = EventTools::Add($ENV['POST']);
			else
				System::Respond();

			System::Respond(Message::Respond('events.add',$action), $action == 0 ? 1 : 0);
		break;

		case 'edit':
			if (!empty($ENV['POST']))
				$action = EventTools::Edit($ENV['POST']);
			else
				System::Respond();

			System::Respond(Message::Respond('events.edit',$action), $action == 0 ? 1 : 0);
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

					System::Respond('',1,array('html' => $html));
				}
				else {
					$data = $db->where('id',$ENV['POST']['id'])->where('classid',$user['classid'])->getOne('events');

					if (!empty($data)){
						$data['start'] = str_replace('.','-',$data['start']);
						$data['end'] = str_replace('.','-',$data['end']);

						System::Respond('',1,$data);
					}
					else
						System::Respond();
				}
			}
			else
				System::Respond();
		break;
	}