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

		case 'getEventInfos':
			if (!empty($ENV['POST']['id'])){
				$data = EventTools::GetEventInfos($ENV['POST']['id']);

				if (is_int($data)) System::Respond(Message::Respond('events.getInfos',$data), $data == 0 ? 1 : 0);

				$html = '';
				foreach ($data as $key => $value){
					$kettosPont = $key == 'Egész napos?' ? '' : ':';
					$html .= "<p><b>{$key}{$kettosPont}</b> {$value}</p>";
				}

				System::Respond('',1,array('html' => $html));
			}
			else
				System::Respond();
		break;
	}