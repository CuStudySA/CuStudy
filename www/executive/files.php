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
					'md5' => 'MD5 Hash',
				);

				foreach($data as $key => $value)
					$html .= "<p><b>{$translate[$key]}: </b>{$value}</p>";

				System::Respond('',1,array('html' => $html));
			}
		break;

		case 'uploadFiles':
			if (System::PermCheck('files.add'))
				System::Respond();

			if (empty($_FILES))
				System::Respond(Message::Respond('files.uploadFiles', 5));

			$infos = [];

			foreach ($ENV['POST'] as $key => $value){
				if (empty($value) || $value == 'null') continue;
				$keys = explode('_',$key);
				if (empty($keys[1])) continue;
				$infos[(int)$keys[0]][$keys[1]] = $value;
			}

			foreach ($_FILES as $key => $file){
				$action = FileTools::Insert(array(
					'file' => $file,
					'name' => !empty($infos[$key]['title']) ? $infos[$key]['title'] : 'Feltöltött dokumentum',
					'description' => !empty($infos[$key]['desc']) ? $infos[$key]['desc'] : 'Egy feltöltött dokumentum leírása',
				));

				if (is_int($action))
					System::Respond(Message::Respond('files.uploadFiles',$action));
			}

			System::Respond(array(
				'filelist' => FileTools::RenderList($user['class'][0], false),
				'storage' => FileTools::GetSpaceUsage(),
			));
		break;

		case 'delete':
			if (!empty($ENV['POST']['id']))
				$action = FileTools::DeleteFile($ENV['POST']['id']);
			else System::Respond();

			System::Respond(Message::Respond('files.delete',$action),$action == 0 ? 1 : 0, array(
				'storage' => FileTools::GetSpaceUsage()
			));
		break;

		case 'openExternalViewer':
			if (empty($ENV['POST']['id']))
				System::Respond();
			
			$fileid = $ENV['POST']['id'];
			$file = FileTools::GetFileInfo($fileid);
			$action = FileTools::GenerateViewingToken($file, $fileid);

			System::Respond(Message::Respond('files.openExternalViewer',is_int($action) ? $action : 0), !is_int($action), !is_int($action) ? array(
				'url' => $action,
			) : array());
		break;
	}
