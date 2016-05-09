<?php

	class CSRF {
		const tokenLength = 10;
		static function Generate(){
			if (Cookie::get('JSSESSID') !== false) Cookie::delete('JSSESSID');
			Cookie::set('JSSESSID',Password::Generalas(self::tokenLength),false);
		}

		static function Check($post){
			$cookie = Cookie::get('JSSESSID');

			if ($cookie === false) return false;
			if ($cookie == $post) return true;
			else return false;
		}
	}

