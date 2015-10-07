<?php
	$case = !empty($ENV['URL'][0]) ? $ENV['URL'][0] : 'default';

	switch ($case){
		case 'send':
			if (!empty($ENV['POST']['email'])){
				$action = PasswordReset::SendMail($ENV['POST']['email']);

				System::Respond(Message::Respond('passwordReset.sendMail',$action), $action == 0 ? 1 : 0);
			}
			else System::Respond();
		break;

		case 'reset':
			if (!empty($ENV['POST'])){
				$action = PasswordReset::Reset($ENV['POST']);

				System::Respond(Message::Respond('passwordReset.reset',$action), $action == 0 ? 1 : 0);
			}
			else System::Respond();
		break;
	}
