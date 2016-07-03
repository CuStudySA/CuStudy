<?php
	print "<h1>Rendszerfelhasználók kezelése</h1>";

	if (!empty($ENV['URL'][0])){
		$User = $db->where('id',$ENV['URL'][0])->getOne('users');
		if (empty($User)) System::Redirect('/system.users');

		$Classes = AdminUserTools::GetLocalRoles($ENV['URL'][0]);

		//$Logs = $db->rawQuery('SELECT c.id, c.action, c.errorcode, c.useragent, c.ipaddr, c.time
		//						FROM `log__central` c
		//						WHERE c.user = ?
		//						ORDER BY c.time DESC
		//						LIMIT 5',array($ENV['URL'][0]));

		System::LoadLibrary('mantisIntegration');
		$Mantis = MantisTools::GetUserMantisStatus($User['id']); ?>

		<h2 id='filterTitle'>Kiválasztott felhasználó: <span class='userName'><?=$User['name']?> (#<?=$User['id']?>)</span></h2>

		<h3 class='dataTitle'>Alapvető adatok</h3>
		<ul class='dataList'>
			<li class='entry'><span>Felhasználónév: </span><?=$User['username']?></li>
			<li class='entry'><span>Teljes név: </span><?=$User['name']?></li>
			<li class='entry'><span>E-mail cím: </span><?=$User['email']?></li>
			<li class='entry'><span>Globális rendszerjogosultság: </span><?=UserTools::$roleLabels[$User['role']]?></li>

<?php   if (!is_int($Mantis)){ ?>
				<li class='entry'><span>BugTracker kapcsolat állapota: </span><?=is_array($Mantis) ? 'Összekapcsolva (#'.$Mantis[0].')' : 'Nincs összekapcsolva'?></li>
<?php		} ?>
		</ul>

		<h3 class='dataTitle'>Osztálytagságok és szerepkörök</h3>
		<ol>
<?php       if (empty($Classes)) print "<p>A felhasználó nem tagja egyik osztálynak sem!</p>";
			foreach ($Classes as $role){ ?>
				<li>
					<ul class='dataList'>
						<li class='entry'><span>Iskola neve: </span><?=$role['schoolName']?> (#<?=$role['schoolId']?>)</li>
						<li class='entry'><span>Osztály neve: </span><?=$role['className']?> (#<?=$role['classId']?>)</li>
						<li class='entry'><span>Lokális szerepkör: </span><?=UserTools::$roleLabels[$role['role']]?></li>
					</ul>
				</li>
<?php				} ?>
		</ol>

		<h3 class='dataTitle'>Utolsó naplóbejegyzései</h3>
		<p>Tekintse meg a felhasználóhoz kapcsolódó bejegyzéseket a <a href='/logs?userid=<?=$User['id']?>'>Rendszernapló</a> oldalon!</p>

		<h3 class='dataTitle'>Műveletek</h3>
		<ol class='actions'>
			<li><a class='typcn typcn-pencil' id='js_editUserInfos' href='#' data-id='<?=$User['id']?>'> Felhasználó alapadatainak szerkesztése</a></li>
<?php   if (!is_int($Mantis)){ ?>
				<li><a class='typcn typcn-cog' id='js_btEdit' href='#' data-id='<?=$User['id']?>'> BugTracker összekapcsolás szerkesztése</a></li>
<?php		} ?>
			<li><a class='typcn typcn-lock-closed' id='js_editRoles' href='#' data-id='<?=$User['id']?>'> Szerepkörök szerkesztése</a></li>
			<li><a class='typcn typcn-user-delete' id='js_deleteUser' href='#' data-id='<?=$User['id']?>'> Felhasználó törlése</a></li>
		</ol>
<?php		}
	else {
		AdminUserTools::ShowFilter();
	}