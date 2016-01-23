<?php

class oAuthRequestException extends Exception {
	private $debug;
	public function __construct($errMsg, $errCode = null, $debug = null){
		$this->message = $errMsg;
		$this->code = $errCode;
		$this->debug = $debug;
	}
	public function getDebugInfo(){
		return $this->debug;
	}
}

// This class serves as a base to derive other classes from, don't use directly
// Requires cURL (& gzdecode) support to work
class oAuthProvider {
	protected
		$_client_id,
		$_client_secret,
		$_redirect_uri,
		$_force_POST = false;

	// Must be set by child classes
	protected
		// The URI where the initial code request should be sent to
		$_user_authorize_uri,
		// The URI where authorization token requests should be sent to
		$_token_request_uri,
		// The token type returned by the provider
		$_token_type,
		// The scope(s) required to obtain unique user identification info
		$_auth_scope = '';

	/**
	 * Makes authenticated requests to the DeviantArt API
	 *
	 * @param string      $url
	 * @param null|string $token
	 * @param null|array $postdata
	 * @param bool       $msApi
	 *
	 * @throws oAuthRequestException
	 *
	 * @return array
	 */
	protected function _sendRequest($url, $token, $postdata = null, $msApi = false){
		global $http_response_header;

		$requestHeaders = array(
			"Accept: application/json",
			"Accept-Encoding: gzip",
			"User-Agent: CuStudy @ ".ABSPATH,
			'Content-Transfer-Encoding: binary',
		);
		if (!$msApi)
			$requestHeader[] = 'Content-Type: application/x-www-form-urlencoded';

		$requestURI = $url;
		if (!empty($token)){
			if (!$msApi)
				$requestHeaders[] = "Authorization: {$this->_token_type} $token";
			else  $requestURI .= '?access_token='.urlencode($token);
		}

		$r = curl_init($requestURI);
		$curl_opt = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HEADER => 1,
			CURLOPT_BINARYTRANSFER => 1,
			CURLOPT_FOLLOWLOCATION => 1,
		);
		if ($msApi && in_array($_SERVER['REMOTE_ADDR'],array('::1','127.0.0.1')))
			$curl_opt[CURLOPT_SSL_VERIFYPEER] = 0;

		if (isset($postdata)){
			$query = array();
			if (!empty($postdata)){
				foreach ($postdata as $k => $v)
					$query[] = urlencode($k).'='.urlencode($v);
			}
			$curl_opt[CURLOPT_POST] = true;
			if (!empty($query)){
				$curl_opt[CURLOPT_POSTFIELDS] = implode('&', $query);
				$postlength = strlen($curl_opt[CURLOPT_POSTFIELDS]);
			}
			else $postlength = 0;

			$requestHeaders[] = "Content-Length: $postlength";
		}
		$curl_opt[CURLOPT_HTTPHEADER] = $requestHeaders;
		curl_setopt_array($r, $curl_opt);

		$response = curl_exec($r);
		$responseCode = curl_getinfo($r, CURLINFO_HTTP_CODE);
		$headerSize = curl_getinfo($r, CURLINFO_HEADER_SIZE);

		$responseHeaders = rtrim(substr($response, 0, $headerSize));
		$response = substr($response, $headerSize);
		$http_response_header = array_map("rtrim",explode("\n",$responseHeaders));
		$curlError = curl_error($r);
		curl_close($r);

		if (preg_match('/Content-Encoding:\s?gzip/',$responseHeaders)) $response = gzdecode($response);
		if ($responseCode < 200 || $responseCode >= 300)
			throw new oAuthRequestException(rtrim("cURL fail for URL \"$requestURI\" (HTTP $responseCode); $curlError",' ;'), $responseCode, array(
				'response' => $response,
				'responseCode' => $responseCode,
				'curlError' => $curlError,
				'requestURI' => $requestURI,
				'requestHeaders' => $requestHeaders,
			));

		return json_decode($response, true);
	}

	public function __construct($client_id, $client_secret, $redirect_uri){
		$this->_client_id = $client_id;
		$this->_client_secret = $client_secret;
		$this->_redirect_uri = ABSPATH.'/'.ltrim($redirect_uri, '/');
	}

	/**
	 * Redirects the user ot the autorization page
	 *
	 * @param string|null $state optional state parameter to pass
	 */
	public function getCode($state = null){
		die(header("Location: {$this->_user_authorize_uri}".
		           "?response_type=code".
		           "&scope=".urlencode($this->_auth_scope).
		           "&client_id={$this->_client_id}".
		           "&redirect_uri={$this->_redirect_uri}".
		           (!empty($state)?"&state=$state":'')));
	}

	/**
	 * Gets authorization codes using one-time "code" or a "refresh_token" ($type)
	 *
	 * @param string $code Code obtained from the oAuth Provider
	 * @param string|null $type The type of the supplied code
	 *
	 * @throws oAuthRequestException
	 *
	 * @return array
	 */
	public function getTokens($code, $type = null){
		if (empty($type))
			$type = 'authorization_code';
		else if (!in_array($type,array('authorization_code','refresh_token')))
			throw new oAuthRequestException("Invalid token request type: $type");

		$URL = "{$this->_token_request_uri}".
			"?client_id={$this->_client_id}".
			"&client_secret={$this->_client_secret}".
			"&grant_type=$type";

		switch ($type){
			case "authorization_code":
				$requestURI = "$URL&code=$code&redirect_uri={$this->_redirect_uri}";
				$getparams = array();
				if ($this->_force_POST === true){
					$query = parse_url($requestURI, PHP_URL_QUERY);
					parse_str($query, $getparams);
				}
				$json = $this->_sendRequest($requestURI,false,$getparams);
			break;
			case "refresh_token":
				$json = $this->_sendRequest("$URL&refresh_token=$code",false);
			break;
		}

		if (empty($json))
			throw new oAuthRequestException("Error while requesting access token from provider: {$http_response_header[0]}");

		return $this->_formatTokenArray($json);
	}

	/**
	 * Should be overridden by child classes if keys don't match
	 ***********************************************************
	 * Expected output:
	 * array (
	 *     'access_token' => (string) {access token},
	 *     'refresh_token' => (string) {refresh token},
	 *     'expires' => (date string) {refresh token},
	 * )
	 *
	 * @param array $token_array
	 *
	 * @return array
	 */
	protected function _formatTokenArray($token_array){
		$r = array(
			'access_token' => $token_array['access_token'],
			'expires' => date('c', time()+intval($token_array['expires_in'], 10)),
		);
		if (!empty($token_array['refresh_token']))
			$r['refresh_token'] = $token_array['refresh_token'];
		return $r;
	}
}
