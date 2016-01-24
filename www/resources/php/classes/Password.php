<?php

	class Password {
		static function Kodolas($str){
			$final = '$SHA$';
			$hash = hash('sha256', $str);
			$salt = substr((string) md5(time()+rand()),0,16);
			$final .= $salt.'$'.hash('sha256',$hash.$salt);
			return $final;
		}
		static function Ellenorzes($input,$dbpass){
			$tmp = explode('$', $dbpass);
			return hash_equals(hash('sha256', hash('sha256', $input) . $tmp[2]), $tmp[3]);
		}
		static function Generalas($length = 10) {
			$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$randomString = '';
			for ($i = 0; $i < $length; $i++) {
				$randomString .= $characters[rand(0, strlen($characters) - 1)];
			}
			return $randomString;
		}
		static function GetSession($username){
			global $_SERVER;
			return sha1($username.microtime().$_SERVER['REMOTE_ADDR']);
		}
	}

