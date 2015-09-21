<?php
	if (isset($ENV['GET']['error']))
		//die(header("Location: /?errtype=remote&prov=google")); //&errdesc={$ENV['GET']['error']}
		var_dump($ENV['GET']['error']);

	$response = ExtConnTools::Request('https://www.googleapis.com/plus/v1/people/me',ExtConnTools::GetAccessToken($ENV['GET']['code']));

	if (isset($response['error']))
		//die(header("Location: /?errtype=remote&prov=google")); //&errdesc={$response['error']['message']}
		var_dump($ENV['GET']['error']);

	System::ExternalLogin($response['id']);

