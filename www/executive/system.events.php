<?php

	$task  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : null;

	switch ($task){
		case 'filter':
			if (empty($ENV['POST'])) System::Respond();
			$response['events'] = AdminEventTools::Filter($ENV['POST']);
			$response['query'] = $db->getLastQuery();

			System::Respond($response);
		break;
		case 'add':
			$action = AdminEventTools::Store($ENV['POST']);

			System::Respond(Message::Respond('adminEventTools.add',$action),$action == 0);
		break;

		case 'get':
		case 'edit':
		case 'delete':
			if (empty($ENV['POST']['id']) || !is_numeric($ENV['POST']['id']))
				System::Respond('Hiányzó eseményazonosító');
			$id = intval($ENV['POST']['id'], 10);

			$event = $db->where('id',$id)->getOne('events','*, classid = 0 AS global');
			if (empty($event))
				System::Respond('Az esemény nem létezik');

			switch ($task){
				case 'get':
					$event['start'] = date('c',strtotime(str_replace('.','-', $event['start'])));
					$event['end'] = date('c',strtotime(str_replace('.','-', $event['end'])));
					System::Respond($event);
				break;
				case 'delete':
					$action = AdminEventTools::Delete($ENV['POST']);

					System::Respond(Message::Respond('adminEventTools.delete',$action),$action == 0);
				break;
				case 'edit':
					$action = AdminEventTools::Store($ENV['POST']);

					System::Respond(Message::Respond('adminEventTools.edit',$action),$action == 0);
				break;
			}
		break;
	}
