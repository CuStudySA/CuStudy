<?php
	$case  = isset($ENV['URL'][0]) ? $ENV['URL'][0] : 'def';

	switch ($case){
		case 'getFileInfo':
		break;

		case 'uploadFiles':
			var_dump($_FILES);
		break;
	}