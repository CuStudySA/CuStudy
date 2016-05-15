<?php
	$emails = $db->get('mail_queue');

	foreach ($emails as $email){
		System::DispatchMail(array(
			'title' => $email['title'],
			'to' => array(
				'name' => $email['name'],
				'address' => $email['address']
			),
			'body' => $email['body'],
		));

		$db->where('id',$email['id'])->delete('mail_queue');
	}