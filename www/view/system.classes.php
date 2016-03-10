<h1>Osztályok kezelése</h1>

<?php
	if (empty($ENV['URL'][0])){ ?>
		<h2 id='filterTitle'>Osztályok szűrése</h2>

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
							<td>Iskolán belüli azonosító</td>
							<td><input type="text" name='c_classid'></td>
						</tr>
						<tr>
							<td>Osztály ID</td>
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
		        <button class='btn typcn typcn-zoom js_filterClasses'>Osztályok szűrése</button>
		    </form>
		</div>

		<div id='resultContainer'></div>
<?php
	}

	else {
		$Class = $db->rawQuery('SELECT c.*, s.name as schoolName, s.id as schoolId
								FROM `class` c
								LEFT JOIN `school` s
								ON c.school = s.id
								WHERE c.id = ?',array($ENV['URL'][0]));
		if (empty($Class)) System::Redirect('/system.classes');
		$Class = $Class[0];?>

		<h2 id='filterTitle'>Kiválasztott osztály: <span class='className'><?=$Class['schoolName']?> <?=$Class['classid']?> osztálya (#<?=$Class['id']?>)</span></h2>

		<h3 class='dataTitle'>Alapvető adatok</h3>
		<ul class='dataList'>
			<li class='entry'><span>Iskola: </span><?=$Class['schoolName']?> (#<?=$Class['schoolId']?>)</li>
			<li class='entry'><span>Osztály iskolai azonosítója: </span><?=$Class['classid']?></li>
		</ul>

		<h3 class='dataTitle'>Műveletek</h3>
		<ol class='actions'>
			<li><a class='typcn typcn-pencil' id='js_editUserInfos' href='#' data-id='<?=$Class['id']?>'> Osztály alapadatainak szerkesztése</a></li>
			<li><a class='typcn typcn-lock-closed' id='js_editRoles' href='#' data-id='<?=$Class['id']?>'> Szerepkörök szerkesztése</a></li>
			<li><a class='typcn typcn-user-delete' id='js_deleteUser' href='#' data-id='<?=$Class['id']?>'> Felhasználó törlése</a></li>
		</ol>
<?php }