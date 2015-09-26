<?php
	if (empty($ENV['POST']['username']) || empty($ENV['POST']['password']))
		System::Respond();

	$action = System::Login($ENV['POST']['username'], $ENV['POST']['password']);

	System::Respond(is_array($action) ? true : Message::Respond('system.login',$action));