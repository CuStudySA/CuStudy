<?php
	$ENV['Messages'] = array(
		'system' => array(
			'login' => array(
				'errors' => array(
					1 => 'valamelyik megadott adat formátuma hibás',
					2 => 'a felhasználó nem létezik, esetleg hibás a jelszó',
					3 => 'túl sokszor adtál meg hibás jelszót rövid időn belül. Várj 1-2 percet, és próbálkozz újra',
					4 => 'a felhasználó állapota tiltott',
					5 => 'a felhasználó nem rendelkezik aktív szerepkörrel',
					6 => 'nem sikerült kiolvasni a munkamenet indításához szükséges adatokat',
					7 => 'az alapértelmezett szerepkör nem akítv',
					// 8 fenntartva a hiányzó kétfaktoros azonosító kód ablak számára
					9 => 'a megadott kód érvénytelen',
					10 => 'a megadott kétfaktoros azonosító kód érvénytelen',
					11 => 'a megadott kétfaktoros azonosítás tartalék kód érvénytelen',
					12 => 'a megadott kétfaktoros azonosítás tartalék kódot már felhasználtad',
				),
				'messages' => array(
					1 => 'A bejelentkezés sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			)
		),

		'users' => array(
			'modifyRole' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
					6 => 'létezik felhasználó a megadott e-mail címmel',
					7 => 'az űrlap adatai nem változtak (vagy adatb. hiba)'
				),
				'messages' => array(
					0 => 'A felhasználó adatainak módosítása sikeresen megtörtént!',
					1 => 'A felhasználó adatait nem sikerült módosítani, mert @msg! (Hibakód: @code)',
				),
			),
			'delete' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
				),
				'messages' => array(
					0 => 'A felhasználó törlése sikeresen megtörtént!',
					1 => 'A felhasználó törlése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'editAccessData' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a megadott két új jelszó nem egyezik meg',
				),
				'messages' => array(
					0 => 'A felhasználó hozzáférési adatainak módosítása sikeres volt!',
					1 => 'A felhasználó hozzáférési adatainak módosítása sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'eject' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
				),
				'messages' => array(
					1 => 'A felhasználó törlése az osztályból sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'editMyProfile' => array(
				'errors' => array(
					1 => 'a megadott jelszó nem egyezik meg a felhasználó jelszavával',
					2 => 'a megadott két új jelszó nem egyezik meg',
				),
				'messages' => array(
					0 => 'A felhasználói adatok frissítése sikeresen megtörtént!',
					1 => 'A felhasználói adatok frissítése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'enrollUser' => array(
				'errors' => array(
					1 => 'már létezik szerepkör ehhez a felhasználóhoz',
					2 => 'hiba történt az adatbázisba íráskor',
				),
				'messages' => array(
				),
			),
			'applySettings' => array(
				'errors' => array(

				),
				'messages' => array(
					0 => 'A felhasználói beállítások frissítése sikeresen megtörtént!',
					1 => 'A felhasználói beállítások frissítése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'extConnTools' => array(
			'deactivate' => array(
				'errors' => array(
					1 => 'valamelyik megadott adat formátuma hibás',
					2 => 'az összekapcsolás nem található',
					3 => 'nincs jogosultsága a művelethez',
					4 => 'az összekapcsolás státusza már deaktív'
				),
				'messages' => array(
					0 => 'A távoli szolgátatóval történő összekacsolás deaktiválása megtörtént',
					1 => 'A távoli szolgátatóval történő összekacsolás deaktiválás sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'activate' => array(
				'errors' => array(
					1 => 'valamelyik megadott adat formátuma hibás',
					2 => 'az összekapcsolás nem található',
					3 => 'nincs jogosultsága a művelethez',
					4 => 'az összekapcsolás státusza már aktív'
				),
				'messages' => array(
					0 => 'A távoli szolgátatóval történő összekacsolás aktiválása megtörtént',
					1 => 'A távoli szolgátatóval történő összekacsolás aktiválása sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'unlink' => array(
				'errors' => array(
					1 => 'valamelyik megadott adat formátuma hibás',
					2 => 'az összekapcsolás nem található',
					3 => 'nincs jogosultsága a művelethez',
				),
				'messages' => array(
					0 => 'A távoli szolgátató fiókjának leválasztása sikeresen megtörtént',
					1 => 'A távoli szolgátató fiókjának leválasztása sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'setavatarprovider' => array(
				'errors' => array(
					1 => 'a megadott szolgáltató nem létezik',
					2 => 'ezzel a szolgáltatóval nincs összekötve a profilod',
					3 => 'probléma történt az adatbázisba mentés során'
				),
				'messages' => array(
					0 => 'A profilkép szolgáltató sikeresen módosítva',
					1 => 'A profilkép szolgáltató módosítása sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'login' => array(
				'errors' => array(
					1 => 'nem található a távoli fiókhoz kapcsolt felhasználó',
					2 => 'inaktív az összekapcsolás',
					3 => 'az összekapcsolás létezik, de nem található a helyi felhasználó',
					4 => 'az osztály vagy iskola nem aktív a rendszerben',
					5 => 'nem sikerült kiolvasni a munkamenet indításához szükséges adatokat',
					6 => 'a felhasználó nem rendelkezik aktív szerepkörrel',
				),
				'messages' => array(
					1 => 'Nem sikerült bejelentkezni a(z) @provider szolgáltató segítségével, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'lessons' => array(
			'add' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
				),
				'messages' => array(
					0 => 'A tantárgy hozzáadása sikeres volt!',
					1 => 'A tantárgy hozzáadása sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'edit' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
				),
				'messages' => array(
					0 => 'A tantárgy szerkesztése sikeres volt!',
					1 => 'A tantárgy szerkesztése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'delete' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
				),
				'messages' => array(
					0 => 'A tantárgy törlése sikeres volt!',
					1 => 'A tantárgy törlése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'invitation' => array(
			'batchInvite' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'egyes felhasználóknak nem sikerült elküldeni a meghívó e-mailt',
				),
				'messages' => array(
					0 => 'A felhasználók meghívása sikeresen befejeződött. Az új felhasználók esetén a meghívó megérkezése azonban akár 12 órát is igénybe vehet!',
					1 => 'Az összes felhasználó meghívása nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'view' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'nincs megadva a meghívó azonosítója',
					3 => 'a meghívó azonosítója nem található a rendszerben',
					4 => 'a meghívó inaktív, így nem felhasználható (lehet, hogy már felhasználták)',
				),
				'messages' => array(
					1 => 'Nem lehet regisztrálni meghívó segítségével a rendszerbe, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'groups' => array(
			'add' => array(
				'errors' => array(
					2 => 'valamelyik megadott adat formátuma hibás',
					3 => 'valamelyik megadott adat formátuma hibás',
					4 => 'a megadott kategória nem található',
					5 => 'a csoporthoz hozzáadandó felhasználók valamelyike nem található',
				),
				'messages' => array(
					0 => 'A csoport hozzáadása sikeresen megtörtént! Átirányítjuk...',
					1 => 'A csoport hozzáadása sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'edit' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
					3 => 'valamelyik megadott adat formátuma hibás',
					4 => 'valamelyik megadott adat formátuma hibás',
				),
				'messages' => array(
					0 => 'A csoport szerkesztése sikeresen megtörtént! Átirányítjuk...',
					1 => 'A csoport szerkesztése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'delete' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
					3 => 'nem létezik a csoport',
				),
				'messages' => array(
					0 => 'A csoport törlése sikeres volt!',
					1 => 'A csoport törlése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'groupThemes' => array(
			'edit' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
				),
				'messages' => array(
					0 => 'A csoportkategória szerkesztése sikeres volt!',
					1 => 'A csoportkategória szerkesztése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'add' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
				),
				'messages' => array(
					0 => 'A csoportkategória hozzáadása sikeres volt!',
					1 => 'A csoportkategória hozzáadása sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'delete' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
				),
				'messages' => array(
					0 => 'A csoportkategória törlése sikeres volt!',
					1 => 'A csoportkategória törlése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'homeworks' => array(
			'add' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
					3 => 'az órarend-bejegyzés nem található',
					4 => 'a meadott órarend-bejegyzés a kapott hét sorszámával nem összeegyeztethető',
					5 => 'ehhez az órához már hozzá van adva egy házi feladat',
				),
				'messages' => array(
					0 => 'A házi feladat hozzáadása sikeresen befejezeődött!',
					1 => 'A házi feladat hozzáadása sikertelenül záródott, mert @msg! (Hibakód: @code)',
				),
			),
			'delete' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
				),
				'messages' => array(
					1 => 'A házi feladat törlése sikertelenül záródott, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'teachers' => array(
			'add' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
					4 => 'néhány tantárgy hozzáadása nem sikerült',
				),
				'messages' => array(
					0 => 'A tanár (és tantárgyak) hozzáadása sikeres volt!',
					1 => 'A tanár (vagy/és tantárgyak) hozzáadása sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'edit' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
				),
				'messages' => array(
					0 => 'A tanár adatainak módosítása sikeres volt!',
					1 => 'A tanár adatainak módosítása sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
			'delete' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
				),
				'messages' => array(
					0 => 'A tanár törlése a rendszerből sikeres volt!',
					1 => 'A tanár törlése a rendszerből sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'timetables' => array(
			'progressTable' => array(
				'errors' => array(
					1 => 'valamelyik megadott adat formátuma hibás',
					2 => 'nincs jogosultsága a művelethez',
				),
				'messages' => array(
					0 => 'Az órarend sikeresen módosítva! Az oldal frissül, várjon...',
					1 => 'Az órarend frissítése sikertelen volt, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'passwordReset' => array(
			'sendMail' => array(
				'errors' => array(
					1 => 'valamelyik megadott adat formátuma hibás',
					2 => 'nem található az e-mail címhez kapcsolt felhasználó',
					4 => 'a levél elküldése közben problémák adódtak',
				),
				'messages' => array(
					0 => 'A jelszóvisszaállító levél a felhasználó e-mail címére elküldve!',
					1 => 'A jelszóvisszaállító levél elküldése nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'reset' => array(
				'errors' => array(
					1 => 'nincs megadva visszaállító azonosító',
					2 => 'a visszaállító azonosító nem létezik, estleg lejárt',
					3 => 'nincs megadva új jleszó',
					4 => 'a felhasználó nem található',
					5 => 'a megadott jelszavak nem egyeznek',
				),
				'messages' => array(
					0 => 'A jelszóvisszaállítás sikeresen megtörtént. Kérjük jelentkezzen be!',
					1 => 'A jelszóvisszaállítás nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
		),

		'files' => array(
			'uploadFiles' => array(
				'errors' => array(
					1 => 'az egyik fájl egy hiba miatt nem töltődött fel a szerverre',
					3 => 'az osztály tárhelyén nincs elég szabad hely',
					4 => 'a kiszolgálón nincs elég hely egy fájl feltöltéséhez',
					5 => 'egyetlen fájl sem érkezett me ga szerverre',
				),
				'messages' => array(
					0 => 'A fájlok feltöltése sikeresen megtörtént!',
					1 => 'Valemlyik fájl (vagy fájlok) feltöltése nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'uploadFile' => array(
				'errors' => array(
					1 => 'a fájl nem mozgatható a szerverre',
					2 => 'nem lehetett a bejegyzést létrehozni',
				),
				'messages' => array(
				),
			),
			'delete' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a fájl nem található az adatbázisban',
				),
				'messages' => array(
					0 => 'A fájl törlése sikeresen megtörtént!',
					1 => 'A fájl törlése nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'openExternalViewer' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'hiányzik a szolgáltatás elérhetősége',
					3 => 'nem lehetett létrehozni a hozzáférési azonosítót',
				),
				'messages' => array(
					1 => 'A hozzáférési azonostíó létrehozása nem sikerült, mert @msg! (Hibakód: @code)',
					0 => 'A hozzáférési azonosítót lérehoztuk, most átirányítunk...',
				),
			),
		),
		'events' => array(
			'add' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik mezű üresen maradt',
					3 => 'valamelyik megadott adat formátuma hibás',
					4 => "az esemény időtartama nem 'kezdet ~ vég' formátumban lett megadva",
					5 => 'a megadott intervallum nem értelmezhető',
				),
				'messages' => array(
					0 => 'Az esemény hozzáadása sikeresen megtörtént!',
					1 => 'Az esemény hozzáadása nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'edit' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik mezű üresen maradt',
					3 => 'valamelyik megadott adat formátuma hibás',
					4 => "az esemény időtartama nem 'kezdet ~ vég' formátumban lett megadva",
					5 => 'a megadott intervallum nem értelmezhető',
				),
				'messages' => array(
					0 => 'Az esemény szerkesztése sikeresen megtörtént!',
					1 => 'Az esemény szerkesztése nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'delete' => array(
				'errors' => array(
					1 => 'az esemény nem található vagy nincs engedélye a törléshez',
				),
				'messages' => array(
					1 => 'Az esemény törlése nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'getInfos' => array(
				'errors' => array(
					1 => 'az esemény nem található',
				),
				'messages' => array(
					1 => 'Az esemény információinak lekérése nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
		),
		'roles' => array(
			'set' => array(
				'errors' => array(
					1 => 'nincs engedélye a kiválasztott szerepkör használatához',
					3 => 'a megadott szerepkör már aktív a munkameneten',
				),
				'messages' => array(
					1 => 'Nem sikerült az új szerepkörre történő váltás, mert @msg! (Hibakód: @code)',
					0 => 'A szerepkör-váltás megtörtént, most átirányítjuk...',
				),
			),
			'eject' => array(
				'errors' => array(
					1 => 'a globális szerepkör nem leválasztható',
					2 => 'a szerepkör nem található',
					3 => 'kísérlet történt az alapértelmezett szerepkör leválasztására',
					4 => 'az utolsó szerepkör nem leválasztható',
					5 => 'a megadott jelszó nem egyezik a felhasználó jelszavával',
				),
				'messages' => array(
					1 => 'Nem sikerült leválasztani a szerepkört, mert @msg! (Hibakód: @code)',
					0 => 'A szerepkör leválasztása sikeresen megtörtént!',
				),
			),
			'changeDefault' => array(
				'errors' => array(
					1 => 'a szerepkör nem található',
				),
				'messages' => array(
					1 => 'Nem sikerült megváltoztatni az alapértelmezett szerepkört, mert @msg! (Hibakód: @code)',
					0 => 'Az alapértelmezett szerepkör beállítása sikeresen megtörtént!',
				),
			),
		),

		'adminUserTools' => array(
			'editBasicInfos' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a megadott adatok formátuma nem megfelelő',
					3 => 'az e-mail cím már foglalt a rendszerben',
					4 => 'a felhasználói név már foglalt a rendszerben',
				),
				'messages' => array(
					1 => 'Nem sikerült a felhasználó alapadatait módosítani, mert @msg! (Hibakód: @code)',
					0 => 'A felhasználó alapadatainak módosítása sikeresen befejeződött, most átirányítjuk...',
				),
			),
			'deleteRole' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'nem található a felhasználó',
					4 => 'nem található a szerepkör',
				),
				'messages' => array(
					1 => 'Nem sikerült törölni a kiválasztott szerepkört, mert @msg! (Hibakód: @code)',
					0 => 'A kiválasztott szerepkör törlése sikeresen befejeződött, most átirányítjuk...',
				),
			),
			'editRole' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a művelet végrehajtásához nincs elég információ',
					3 => 'a megadott adatok formátuma nem megfelelő',
					4 => 'nem található a szerepkör',
				),
				'messages' => array(
					1 => 'Nem sikerült szerkeszteni a kiválasztott szerepkört, mert @msg! (Hibakód: @code)',
					0 => 'A kiválasztott szerepkör szerkesztése sikeresen befejeződött, most átirányítjuk...',
				),
			),
			'deleteUser' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a felhasználó nem létezik',
				),
				'messages' => array(
					1 => 'Nem sikerült törölni a felhasználót, mert @msg! (Hibakód: @code)',
					0 => 'A kiválasztott felhasználó törlése sikeresen befejeződött, most átirányítjuk...',
				),
			),
		),
		'adminClassTools' => array(
			'editBasicInfos' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a megadott adatok formátuma nem megfelelő',
				),
				'messages' => array(
					1 => 'Nem sikerült az osztály alapadatait módosítani, mert @msg! (Hibakód: @code)',
					0 => 'Az osztály alapadatainak módosítása sikeresen befejeződött, most átirányítjuk...',
				),
			),
			'manageMembers' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a megadott adatok formátuma nem megfelelő',
				),
				'messages' => array(
					1 => 'Nem sikerült az osztály taglistájának módosítása, mert @msg! (Hibakód: @code)',
					0 => 'Az osztály taglistájának módosítása sikeresen befejeződött, most átirányítjuk...',
				),
			),
			'enterClass' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a megadott adatok formátuma nem megfelelő',
				),
				'messages' => array(
					1 => 'Nem sikerült belépni az osztályba, mert @msg! (Hibakód: @code)',
					0 => 'Átirányítás az osztály főoldalára, kérem várjon...',
				),
			),
		),
		'adminEventTools' => array(
			'delete' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'hiba történt az adatbázisba mentés közben',
				),
				'messages' => array(
					0 => 'Az esemény szerkesztése sikeresen megtörtént',
					1 => 'Az esemény szerkesztése nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'edit' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'valamelyik megadott adat formátuma hibás',
					3 => "az esemény időtartama nem 'kezdet ~ vég' formátumban lett megadva",
					4 => 'a megadott intervallum nem értelmezhető',
				),
				'messages' => array(
					0 => 'Az esemény szerkesztése sikeresen megtörtént!',
					1 => 'Az esemény szerkesztése nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
		),
		'mantis_users' => array(
			'create' => array(
				'errors' => array(
					1 => 'a felhasználónak már van Mantis fiókja',
					2 => 'nem sikerült az adatbázisba írás',
					3 => "a Mantis integráció ki van kapcsolva",
					4 => 'már van a Mantis rendszerben azonos adatokkal rendelkező felh.',
				),
				'messages' => array(
					0 => 'A BugTracker-felhasználó regisztrálása sikeresen befejeződött, átirányítjuk...',
					1 => 'A BugTracker-felhasználó regisztrálása nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'edit' => array(
				'errors' => array(
					1 => "a Mantis integráció ki van kapcsolva",
					2 => 'a Mantis fiók nem található',
					3 => 'valamelyik megadott adat formátuma hibás',
					4 => 'nem sikerült az adatbázisba írás',
				),
				'messages' => array(
				),
			),
			'delete' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a Mantis integráció ki van kapcsolva',
					3 => "nem található a keresett bejegyzés",
					4 => 'nem sikerült az adatbázisba írás',
				),
				'messages' => array(
					0 => 'A BugTracker-felhasználó eltávolítása a rendszerből sikeresen befejeződött, átirányítjuk...',
					1 => 'A BugTracker-felhasználó eltávolítása a rendszerből nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
			'update' => array(
				'errors' => array(
					1 => 'nincs jogosultsága a művelethez',
					2 => 'a Mantis integráció ki van kapcsolva',
					3 => "nem található a keresett bejegyzés",
					4 => 'nem sikerült az adatbázisba írás',
				),
				'messages' => array(
					0 => 'A BugTracker-felhasználó frissítése a C.S. rendszer adataival sikeresen befejeződött, átirányítjuk...',
					1 => ' BugTracker-felhasználó frissítése a C.S. rendszer adataival nem sikerült, mert @msg! (Hibakód: @code)',
				),
			),
		),
	);
