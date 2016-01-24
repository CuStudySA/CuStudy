<?php

	class GlobalSettings {
		static $Settings;

		// Globális beállítások betöltése
		static function Load(){
			global $db;

			$data = $db->get('global_settings');

			foreach ($data as $array)
				self::$Settings[$array['key']] = $array['value'];
		}

		// Beállítás lekérdezése
		static function Get($key){
			return !empty(self::$Settings[$key]) ? self::$Settings[$key] : null;
		}
	}

