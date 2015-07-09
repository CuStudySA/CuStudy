<?php
	if (isset($ENV['URL'][0])) $case = $ENV['URL'][0];
	else System::Respond();

	 switch ($case){
		 case 'details':
			if (!isset($ENV['URL'][1])) System::Respond();
			$data = Logging::GetDetails($ENV['URL'][1]);
			if (is_numeric($data)) System::Respond('A naplóbejegyzés lekérése nem sikerült, hibakód: '.$data.'!');
			header('Content-Type: application/json');
			print json_encode(($data));
		 break;
	 }