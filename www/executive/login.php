<?php
	if (isset($ENV['POST']['username']) && isset($ENV['POST']['password']))
		$action = System::Login($ENV['POST']['username'],$ENV['POST']['password'],isset($ENV['POST']['remember']) ? true : false);

	else System::Respond();

	if (is_array($action))
		System::Respond('Juhé',1);
	else
		System::Respond('A bejelentkezés sikertelen volt, mert '.Message::GetError('login',$action).'! (Hibakód: '.$action.')');