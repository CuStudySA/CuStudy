<?php

	class AdminManTools {
		# Admin. hozzáadása
		static function Add($dataf){
			global $db;

			# Jog. ellenörzése
			if (System::PermCheck('sysadmin')) return 2;

			# Bevitel ellenörzése
			foreach ($dataf as $key => $value){
				if (System::InputCheck($value,$key)) return 3;

				# Jelszó kódolása
				if ($key == 'password') $dataf[$key] = Password::Kodolas($value);
			}

			# Létezik-e már ilyen felh.?
			if ($db->where('username',$dataf['username'])->getOne('admins') != false) return 4;

			# Regisztráció
			$action = $db->insert('admins',$dataf);

			if (!$action) return 5;
			else return 0;
		}
	}

