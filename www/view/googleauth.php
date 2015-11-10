<?php

	$ClassName = "google_oAuth";
	require_once "resources/php/$ClassName.php";
	$oAuth = new $ClassName(ExtConnTools::CLIENTID, ExtConnTools::SECRET, '/googleauth');

	if (empty($ENV['GET']['code']))
		$oAuth->getCode();
	else {
		if (empty($ENV['GET']['code']))
			Message::Missing();
		$code = $ENV['GET']['code'];

		try {
		$Auth = $oAuth->getTokens($code, 'authorization_code');
		} catch(oAuthRequestException $e){
			echo $e->getMessage();
			var_dump($http_response_header);
			die();
		}
		$User = $oAuth->getUserInfo($Auth['access_token']);
	}

	System::ExternalLogin($User['remote_id']);