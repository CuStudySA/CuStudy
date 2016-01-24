<?php

	class ClassTools {
		static function AddClass($dataf){
			global $db;

			# Admin. jogkör ellenörzése
			if (System::PermCheck('schooladmin')) return 2;

			/*			array(
							'classid' => 10.B
							'school' => 1
						);						*/

			# Formátum ellenörzése
			if (!System::ValuesExists($dataf,['classid','school'])) return 2;
			foreach ($dataf as $key => $value){
				if ($key == 'classid') $type = 'class';
				if ($key == 'school') $type = 'numeric';

				if (System::InputCheck($value,$type)) return 2;
			}

			# Létezik-e már ilyen osztály?
			if ($db->where('classid',$dataf['classid'])->getOne('class') != false) return 3;

			# Regisztráció
			$action = $db->insert('class',$dataf);

			return $action;
		}

		# Akitválás/Inaktiválás/Áll. lekérdezése
		static function ActiveI($case,$classid){
			global $db;

			# Admin. jogkör ellenörzése
			if (System::PermCheck('schooladmin')) return 2;

			switch ($case){
				case 'activate':
					return !$db->where('classid',$classid)->update('class',array(
						'active' => 1,
					));
					break;

				case 'inactivate':
					return !$db->where('classid',$classid)->update('class',array(
						'active' => 0,
					));
					break;

				case 'getstatus':
					$data = $db->where('classid',$classid)->getOne('class');

					# Felh. létezésének ellenörzése
					if (empty($data)) return 2;

					return $data['active'];
					break;
			}
		}
	}
