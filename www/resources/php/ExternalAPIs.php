<?php
	require_once "oAuthProvider.php";

	class GoogleAPI extends oAuthProvider {
		protected
			$_user_authorize_uri = 'https://accounts.google.com/o/oauth2/auth',
			$_token_request_uri = 'https://www.googleapis.com/oauth2/v4/token',
			$_token_type = 'Bearer',
			$_auth_scope = 'email',
			$_force_POST = true;

		public function getUserInfo($access_token){
			$result = parent::_sendRequest("https://www.googleapis.com/plus/v1/people/me", $access_token);
			if (empty($result))
				return null;

			return $result;
		}
	}

	class FacebookAPI extends oAuthProvider {
		protected
			$_user_authorize_uri = 'https://www.facebook.com/dialog/oauth',
			$_token_request_uri = 'https://graph.facebook.com/v2.3/oauth/access_token',
			$_token_type = 'Bearer';

		public function getUserInfo($access_token){
			$result = parent::_sendRequest("https://graph.facebook.com/v2.3/me", $access_token);
			if (empty($result))
				return null;

			return $result;
		}
	}

	class MicrosoftAPI extends oAuthProvider {
		protected
			$_user_authorize_uri = 'https://login.live.com/oauth20_authorize.srf',
			$_token_request_uri = 'https://login.live.com/oauth20_token.srf',
			$_token_type = 'Bearer',
			$_auth_scope = 'wl.basic',
			$_force_POST = true;

		public function getUserInfo($access_token){
			$result = parent::_sendRequest("https://apis.live.net/v5.0/me", $access_token, null, true);
			if (empty($result))
				return null;

			return $result;
		}
	}