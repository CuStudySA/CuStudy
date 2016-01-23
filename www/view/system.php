<?php
	$case = !empty($ENV['URL'][0]) ? $ENV['URL'][0] : 'default';

	switch ($case){
		case 'users':
			print "<h1>Rendszerfelhasználók kezelése</h1>";

			if (!empty($ENV['URL'][1])){
				$User = $db->where('id',$ENV['URL'][1])->getOne('users');
				if (empty($User)) System::Redirect('/system/users');

				$Classes = $db->rawQuery('SELECT cm.*, c.classid as className, c.id as classId, s.name as schoolName, s.id as schoolId
											FROM `class_members` cm
											LEFT JOIN (`class` c, `school` s)
											ON (c.id = cm.classid && s.id = c.school)
											WHERE cm.userid = ?',array($ENV['URL'][1]));

				$Logs = $db->rawQuery('SELECT c.id, c.action, c.errorcode, c.useragent, c.ipaddr, c.time
										FROM `log_central` c
										WHERE c.user = ?
										ORDER BY c.time DESC
										LIMIT 5',array($ENV['URL'][1])); ?>

				<h2 id='filterTitle'>Kiválasztott felhasználó: <span class='userName'><?=$User['name']?> (#<?=$User['id']?>)</span></h2>

				<h3 class='dataTitle'>Alapvető adatok</h3>
				<ul class='dataList'>
					<li class='entry'><span>Teljes név: </span><?=$User['name']?></li>
					<li class='entry'><span>E-mail cím: </span><?=$User['email']?></li>
					<li class='entry'><span>Globális rendszerjogosultság: </span><?=UserTools::$roleLabels[$User['role']]?></li>
				</ul>

				<h3 class='dataTitle'>Osztálytagságok és szerepkörök</h3>
				<ol>
<?php               if (empty($Classes)) print "<p>A felhasználó nem tagja egyik osztálynak sem!</p>";
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

				<h3 class='dataTitle'>Utolsó 5 naplóbejegyzése</h3>
				<ol>
<?php           if (empty($Classes)) print "<p>A felhasználóhoz nem tartozik naplóbejegyzés!</p>";
				foreach ($Logs as $log){ ?>
						<li>
							<ul class='dataList'>
								<li class='entry'><span>Bejegyzés száma: </span>#<?=$log['id']?></li>
								<li class='entry'><span>Esemény: </span><?=Logging::$ActionLabels[$log['action']]?></li>
								<li class='entry'><span>Időpont: </span><?=$log['time']?></li>
								<li class='entry'><span>Művelet hibakódja: </span><?=$log['errorcode'] == 0 ? '0 (a művelet sikeresen végrehajtva)' : $log['errorcode'].' (hiba történt a művelet közben)'?></li> <!-- TODO az új log db szerkezettel a hibakód-kijelzés javítva lesz -->
							</ul>
						</li>
<?php				} ?>
				</ol>

				<h3 class='dataTitle'>Műveletek</h3>
				<ol>
					<li><a class='typcn typcn-pencil' id='js_editUserInfos' href='#'> Felhasználó alapadatainak szerkesztése</a></li>
				</ol>
<?php		}
			else { ?>
				<h2 id='filterTitle'>Felhasználók szűrése</h2>

				<button class='btn typcn typcn-arrow-up-thick hide' id='js_hideShowFilter'>Szűrőpanel összecsukása</button>

				<div id='filterFormContainer'>
					<form id='filterForm'>
						<table class='filterFormTable'>
							<thead>
								<tr>
								  <td>Szűrési feltétel</td>
								  <td>Érték</td>
								</tr>
			                </thead>

			                <tbody>
								<tr>
									<td>Felhasználónév</td>
									<td><input type="text" name='u_username'></td>
								</tr>
								<tr>
									<td>Teljes név</td>
									<td><input type="text" name='u_name'></td>
								</tr>
								<tr>
									<td>E-mail cím</td>
									<td><input type="text" name='u_email'></td>
								</tr>
								<tr>
									<td>Felhasználó ID</td>
									<td><input type="text" name='u_id'></td>
								</tr>

								<tr>
				                    <td colspan="2" class='focim'>Osztály</td>
				                </tr>
				                <tr>
									<td>Osztály neve / Osztály ID</td>
									<td><input type="text" name='c_id'></td>
								</tr>

				                <tr>
				                    <td colspan="2" class='focim'>Iskola</td>
				                </tr>
				                <tr>
							      <td>Iskola neve / Iskola ID</td>
							      <td><input type="text" name='s_id'></td>
				                </tr>
			                </tbody>
			            </table>
			            <button class='btn typcn typcn-zoom js_filterUsers'>Felhasználók szűrése</button>
			        </form>
	            </div>

	            <div id='resultContainer'></div>
<?php       }
		break;
	}