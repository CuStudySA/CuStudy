<?php
	$case  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case){
		case 'getDetails':
			if (empty($ENV['POST']['id'])) System::Respond();

			System::Respond('',1,Logging::GetDetails($ENV['POST']['id']));
		break;
	}