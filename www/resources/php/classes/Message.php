<?php

	class Message {
		static $Messages = array();

		// Hibakód feldolgozása (to string)
		static function Respond($activity,$code = 0,$errorMsg = null){
			list($class,$action) = explode('.',$activity);

			if ($code){
				if (!is_string($errorMsg)){
					$errorMsg =
						isset(self::$Messages[$class][$action]['errors'][$code])
						? self::$Messages[$class][$action]['errors'][$code]
						: 'ismeretlen hiba történt a művelet során';
				}

				return str_replace('@code',$code,str_replace('@msg',$errorMsg,self::$Messages[$class][$action]['messages'][1]));
			}
			
			else
				return isset(self::$Messages[$class][$action]['messages'][0]) ? self::$Messages[$class][$action]['messages'][0] : 'A művelet sikerült!';
		}

		static $HTTP_STATUS_CODES = array(
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Moved Temporarily',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Time-out',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Large',
			415 => 'Unsupported Media Type',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Time-out',
			505 => 'HTTP Version not supported',
		);

		static function StatusCode($code){
			if (!isset(self::$HTTP_STATUS_CODES[$code]))
				trigger_error('Érvénytelen státuszkód: '.$code,E_USER_ERROR);
			else
				header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.self::$HTTP_STATUS_CODES[$code]);
		}

		# 403-as hiba esetén
		static function AccessDenied($json = false){
			if ($json)
				System::Respond();
			else {
				if (ROLE == 'guest'){
					global $ENV;
					System::Redirect('/login?r='.urlencode($ENV['SERVER']['REQUEST_URI']));
				}
				else System::Redirect('/not-found');
			}
		}

		# 404-es hiba esetén
		static function Missing($path = ''){
			global $ENV;

			if ($ENV['do'] != 'not-found')
				System::Redirect("/not-found?path=".urlencode($path));
		}

		static function SendNotify($activity,$addressOrId,$invocation = null,$parameters = array()){
			global $db, $user, $Notifications;

			$slices = explode('.',$activity,2);
			if (count($slices) == 1)
				$template = $Notifications[$activity];
			else
				$template = $Notifications[$slices[0]][$slices[1]];

			$text = $Notifications['template']['header'].$template['body'].$Notifications['template']['footer'];

			if (is_numeric($addressOrId)){
				$data = $db->where('id',$addressOrId)->getOne('users');

				if (empty($data)) return false;
				else $addressOrId = $data['email'];
			}

			if (empty($invocation)){
				$data = $db->where('email',$addressOrId)->getOne('users');
				if (!empty($data))
					$invocation = $data['name'];
			}

			$toReplace = array(
				'title' => $template['title'],
				'name' => !empty($invocation) ? $invocation : 'felhasználónk',
			);

			foreach (array_merge($toReplace,$parameters) as $key => $value)
				$text = str_replace('++'.strtoupper($key).'++',$value,$text);

			$action = System::SendMail(array(
				'title' => $template['title'],
				'to' => array(
					'name' => !empty($invocation) ? $invocation : 'CuStudy felhasználó',
					'address' => $addressOrId,
				),
				'body' => $text,
			));

			if ($action) return true;

			return false;
		}

		static $DB_FAIL = "Hiba történt az adatbázisba mentés során";
	}

