<?php

	if (empty($ENV['POST']['hash'])){
		if (empty($ENV['POST']['email']))
			System::Respond('Kérjük, adjon meg e-mail címet');

		PasswordReset::SendMail();
		exit;
	}

	PasswordReset::Reset();
