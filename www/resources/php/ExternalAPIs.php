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

			return array(
				'account_id' => $result['id'],
				'name' => isset($result['nickname']) ? $result['nickname'] : $result['name']['familyName'].' '.$result['name']['givenName'],
				'picture' => System::MakeHttps(str_replace('?sz=50','?sz=95',$result['image']['url'])),
				'email' => $result['emails'][0]['value'],
			);
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

			return array(
				'account_id' => $result['id'],
				'name' => $result['name'],
				'picture' => "https://graph.facebook.com/v2.5/{$result['id']}/picture?width=95&height=95",
			);
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

			return array(
				'account_id' => $result['id'],
				'name' => $result['name'],
				'picture' => "https://apis.live.net/v5.0/{$result['id']}/picture"
			);
		}
	}

	class DeviantArtAPI extends oAuthProvider {
		protected
			$_user_authorize_uri = 'https://www.deviantart.com/oauth2/authorize',
			$_token_request_uri = 'https://www.deviantart.com/oauth2/token',
			$_token_type = 'Bearer',
			$_auth_scope = 'user';

		public function getUserInfo($access_token){
			$result = parent::_sendRequest("https://www.deviantart.com/api/v1/oauth2/user/whoami", $access_token);
			if (empty($result))
				return null;

			return array(
				'account_id' => $result['userid'],
				'name' => $result['username'],
				'picture' => System::MakeHttps($result['usericon']),
			);
		}
	}

	class GitHubAPI extends oAuthProvider {
		protected
			$_user_authorize_uri = 'https://github.com/login/oauth/authorize',
			$_token_request_uri = 'https://github.com/login/oauth/access_token',
			$_token_type = 'Bearer',
			$_auth_scope = 'user:email';

		public function getUserInfo($access_token){
			$result = parent::_sendRequest("https://api.github.com/user", $access_token);
			if (empty($result))
				return null;

			return array(
				'account_id' => $result['id'],
				'name' => $result['login'],
				'email' => $result['email'],
				'picture' => $result['avatar_url'],
			);
		}

		protected function _formatTokenArray($token_array){
			return array('access_token' => $token_array['access_token']);
		}
	}
