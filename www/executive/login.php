<?php
	if (empty($ENV['POST']['username']) || empty($ENV['POST']['password']))
		System::Respond();

	$action = System::Login($ENV['POST']['username'], $ENV['POST']['password'], $ENV['POST']['code'] ?? null);

	// 2fa kód szükséges
	if ($action === 8)
		System::Respond(array(
			'status' => false,
			'twofa' => UserTools::TWOFA_BACKUP_CODE_CHARS,
		));

	System::Respond(is_array($action) ? true : Message::Respond('system.login',$action));
