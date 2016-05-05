<?php

	$Notifications = array(
		// Üzenetek fejléce és lábléce
		'template' => array(
			'header' => <<<STRING
				<h2>CuStudy - ++TITLE++</h2>

				<h3>Tisztelt ++NAME++!</h3>
STRING
,
			'footer' => <<<STRING
				<p>Üdvözlettel,<br>
				CuStudy Software Alliance</p>
STRING
		),

		'invitation' => array(
			'title' => 'Meghívó a CuStudy rendszerbe',
			'body' => <<<STRING
				<p>Örömmel értesítünk, hogy meghívót kaptál a CuStudy rendszerbe a(z) <b>++SCHOOL++</b> iskola <b>++CLASS++</b> osztálya által. A meghívót <b>++SENDER++</b> csoportadminisztrátor küldte neked.</p>

				<p><b>Mi is az a CuStudy?</b> A CuStudy a BetonHomeWork utódjaként továbbra is egy kellemetlen, de kötelező feladatra koncentrál: a házi feladatokra. A CuStudy - a BHW méltó utódjaként - teszi lehetővé számodra, hogy értesülhess a házi feladataidról és egyéb kötelességeidről, sőt a program használatával akutális és frissülő órarended is láthatod. Az elődünkhöz képest azonban jócskán fejlődtünk: mostantól <b>webes felületen</b> érheted el az információkat, illetve új felületet és számos izgalmas, funkcionalitást érintő fejlesztést is eszközöltünk, így az alapötlet egy remek felülettel és sok funkcióval párosul.</p>

				<p>A meghívás elfogadásához <a href="++ABSPATH++/invitation/++ID++">kattints ide</a>! A linkre kattintva meg kell adnod néhány adatot magadról, be kell állítanod a jelszavad, és az űrlap elküldése után automatikusan átirányítunk a program főoldalára.</p><p>Ha a fenti gomb valamilyen okból kifolyólag nem működne, másold be az alábbi URL-t a böngésződ címsorába:<br><a href="++ABSPATH++/invitation/++ID++">++ABSPATH++/invitation/++ID++</a></p><p>Bízunk benne, hogy a CuStudy a Te tetszésedet is elnyeri majd!</p>
STRING
		),
		'role' => array(
			//Variables: initiator, role
			'enrollment' => array(
				'title' => 'Új szerepkör hozzáadva a CuStudy fiókodhoz',
				'body' => <<<STRING
					<p>Értesítünk, hogy a fiókodhoz új szerepkört adtunk hozzá! A művelet kezdeményezője: ++INITIATOR++, az új szerepkör: <b>++ROLE++</b>. A szerepkör használatához bejelentkezés után kattints a profilképedre az oldalsávon, és válaszd ki a szerepkört.</p>

					<p>Az alapértelmezett szerepköröd ezzel a művelettel nem módosult, ám amennyiben a most felvett szerepkört szeretnéd alapértelmezettként beállítani, arra lehetőséged van a 'Profilom' oldalon!</p>

					<p>Amennyiben ez a szerepkör felvétele váratlanul ért, vagy úgy gondolod, hogy tévedés történt, a szerepkört bármikor leválaszthatod a 'Profilom' oldalon!</p>
STRING
			),
		),
		'users' => array(
			//Variables: initiator
			'change-password' => array(
				'title' => 'Jelszavad megváltozott',
				'body' => <<<STRING
					<p>Értesítünk, hogy a CuStudy fiókod jelszava megváltozott! A műveletet ++INITIATOR++ kezdeményezte! A továbbiakban a fiókodat - hagyományos bejelentkezési módot választva - csak az új jelszavad megadásával érheted el!</p>

					<p>Amennyiben a jelszóváltoztatást nem Te, vagy egy ügyedben eljáró munkatársunk kezdeményezte, kérlek haladéktalanul vedd fel a kapcsolatot az ügyfélszolgálatunkkal!</p>
STRING
			),
		),
	);