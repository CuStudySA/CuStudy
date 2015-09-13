<?php
	class Cookie {
		/**
		 * Delete a cookie
		 *
		 * @param string $name
		 */
		public static function delete($name) {
			if (isset($_COOKIE[$name])) {
				unset($_COOKIE[$name]);
				return setcookie ($name, "", time() - 3600, (defined('RELPATH')?RELPATH:"/"));
			}
		}

		/**
		 * Get the value of a cookie if it exists
		 *
		 * @param string $name
		 * @return Value of cookie
		 */
		public static function get($name) {
			if (isset($_COOKIE[$name])) {
				return $_COOKIE[$name];
			} else return false;
		}
		
		
		public static function exists($name) {
			return isset($_COOKIE[$name]);
		}

		/**
		 * Create a cookie
		 *
		 * @param string $name
		 * @param string $value
		 * @param string $time
		 * @return If cookie is set
		 */
		public static function set($name, $value, $time = null) {
			// Variables
			if (isset($time)){
				if ($time === false) $_ttl = 0;
				else $_ttl = $time;
			}
			else $_ttl = time() + 60 * 60 * 24 * 30;
			
			$success = setcookie($name, $value, $_ttl, (defined('RELPATH')?RELPATH:"/"));
			if ($success) $_COOKIE[$name] = $value;
			return $success;
		}
	}
?>