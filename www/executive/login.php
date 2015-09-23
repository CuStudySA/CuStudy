<?php
	if (empty($ENV['POST']['username']) || empty($ENV['POST']['password']))
		System::Respond();

	$action = System::Login($ENV['POST']['username'], $ENV['POST']['password'],isset($ENV['POST']['remember']) ? true : false);

	System::Respond(is_array($action) ? true : 'A bejelentkezés sikertelen volt, mert '.Message::GetError('login',$action).'! (Hibakód: '.$action.')');
