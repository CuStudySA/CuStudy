<?php
	$case  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case){
		case 'getFileInfo':
			if (!empty($ENV['POST']['id'])){
				$data = FileTools::GetFileInfo($ENV['POST']['id']);
				if (is_int($data)) System::Respond();

				$html = '';

				$translate = array(
					'name' => 'Dokumentum címe',
					'description' => 'Dokumentum leírása',
					'lesson' => 'Hozzárendelt tantárgy',
					'size' => 'Fájl mérete',
					'time' => 'Feltöltés ideje',
					'uploader' => 'Feltöltő',
					'filename' => 'Fájl neve',
				);

				foreach($data as $key => $value)
					$html .= "<p><b>{$translate[$key]}: </b>{$value}</p>";

				System::Respond('',1,array('html' => $html));
			}
		break;

		case 'uploadFiles':
			if (!empty($_FILES)){
				$infos = [];

				foreach ($ENV['POST'] as $key => $value){
					$keys = explode('_',$key);
					$infos[(int)$keys[0]][$keys[1]] = $value;
				}

				foreach ($_FILES as $key => $file){
					$action = FileTools::UploadFile($file);

					if (is_int($action))
						System::Respond(Message::Respond('files.uploadFiles',$action));

					$db->insert('files',array(
						'name' => isset($infos[$key]['title']) ? $infos[$key]['title'] : 'Feltöltött dokumentum',
						'description' => isset($infos[$key]['desc']) ? $infos[$key]['desc'] : 'Egy feltöltött dokumentum leírása',
						'lessonid' => 0,
						'classid' => $user['classid'],
						'uploader' => $user['id'],
						'size' => $file['size'],
						'filename' => $file['name'],
						'tempname' => $action[0],
					));
				}
			}

			System::Respond(Message::Respond('files.uploadFiles',0),1);
		break;

		case 'delete':
			if (!empty($ENV['POST']['id']))
				$action = FileTools::DeleteFile($ENV['POST']['id']);
			else System::Respond();

			System::Respond(Message::Respond('files.delete',$action),$action == 0 ? 1 : 0);
		break;
	}