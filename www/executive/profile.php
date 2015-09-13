<?php
	switch (end($ENV['URL'])){
		case 'deactivate':
			if (!empty($ENV['POST']))
				$action = ExtConnTools::DeactAndAct($ENV['POST']['id']);
			else System::Respond();

			if ($action === 0)
				System::Respond('A távoli szolgátatóval történő összekacsolás deaktiválása megtörtént! Az oldal frissül, várjon...',1);
			else
				System::Respond('A távoli szolgátatóval történő összekacsolás deaktiválás sikertelen volt, mert '.Message::GetError('extconndeactivate',$action).'! (Hibakód: '.$action.')');
		break;

		case 'activate':
			if (!empty($ENV['POST']))
				$action = ExtConnTools::DeactAndAct($ENV['POST']['id'],'activate');
			else System::Respond();

			if ($action === 0)
				System::Respond('A távoli szolgátatóval történő összekacsolás aktiválása megtörtént! Az oldal frissül, várjon...',1);
			else
				System::Respond('A távoli szolgátatóval történő összekacsolás aktiválása sikertelen volt, mert '.Message::GetError('extconnactivate',$action).'! (Hibakód: '.$action.')');
		break;

		case 'unlink':
			if (!empty($ENV['POST']))
				$action = ExtConnTools::Unlink($ENV['POST']['id']);
			else System::Respond();

			if ($action === 0)
				System::Respond('A távoli szolgátató fiókjának leválasztása sikeresen megtörtént! Az oldal frissül, várjon...',1);
			else
				System::Respond('A távoli szolgátató fiókjának leválasztása sikertelen volt, mert '.Message::GetError('extconndeactivate',$action).'! (Hibakód: '.$action.')');
		break;

		case 'edit':
			if (!empty($ENV['POST']))
				$action = UserTools::EditMyProfile($ENV['POST']);
			else System::Respond();

			if ($action === 0)
				System::Respond('A felhasználói adatok frissítése sikeresen megtörtént!',1);
			else
				System::Respond('A felhasználói adatok frissítése sikertelen volt, mert '.Message::GetError('editmyprofile',$action).'! (Hibakód: '.$action.')');
		break;
	}