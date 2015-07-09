<?php
	if (isset($ENV['POST']['username']) && isset($ENV['POST']['password'])){
		$action = System::Login($ENV['POST']['username'],$ENV['POST']['password']);
		Logging::Insert(array(
			'action' => 'login',
			'user' => (is_array($action) ? $action[0] : 0),
			'errorcode' => (!is_array($action) ? $action : 0),
			'db' => 'login',
			'username' => $ENV['POST']['username'],
		));
	}
	else System::Respond();

	if (is_array($action))
		System::Respond('Juhé',1);
	else
		System::Respond('A bejelentkezés sikertelen volt, mert '.Message::GetError('login',$action).'! (Hibakód: '.$action.')');