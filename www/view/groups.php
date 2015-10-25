<?php
	if (!isset($ENV['URL'][0]))
		$case = 'default';
	else
		$case = $ENV['URL'][0];

	switch ($case){
		default:
			$themes = $db->rawQuery("SELECT *
									FROM `group_themes`
									WHERE `classid` = ?",array($user['classid']));

			$groups = $db->rawQuery("SELECT *
									FROM `groups`
									WHERE `classid` = ?",array($user['classid']));

			print "<h1 id=h1cim>Csoportok kezelése</h1><div id='groupContainer'>";

			foreach ($themes as $thm){
				print "<div data-thm='{$thm['id']}'><h2 class='grouptitle' data-thm='{$thm['id']}'>{$thm['name']} csoportok</h2><ul class='groups colorli'>";
				foreach($groups as $grp){
					if ($grp['theme'] != $thm['id']) continue; ?>

					<li>
						<div class="top">
							<span class='rovid'><?=$grp['name']?> csop.</span>
							<span class='nev'><?=$thm['name']?></span>
						</div>
						<div class="bottom">
							<a class="typcn typcn-pencil" href="/groups/edit/<?=$grp['id']?>" title="Módosítás"></a>
							<a class="typcn typcn-trash js_grp_del" href="#<?=$grp['id']?>" title="Törlés"></a>
							<a class="typcn typcn-group js_grp_members" href="#<?=$grp['id']?>" title="Tagok listájának megtekintése"></a>
						</div>
					</li>
<?php			} ?>
				<li>
					<div class="top">
						<span class='rovid'>Új csop.</span>
						<span class='nev newTile'>Új csoport hozzáadása</span>
					</div>
					<div class="bottom">
						<a class="typcn typcn-plus" href="/groups/add/<?=$thm['id']?>" title="Hozzáadás"></a>
					</div>
				</li></ul></div>
<?php		}

			print "</div><h2 class='grouptitle'>Csoport kategóriák</h2><ul class='groups grps'>";
			foreach ($themes as $theme){ ?>
				<li data-id='<?=$theme['id']?>'>
					<div class="top">
						<span class='rovid'><?=$theme['name']?> kat.</span>
						<span class='nev'></span>
					</div>
					<div class="bottom">
						<a class="typcn typcn-pencil js_thm_edit" href="#<?=$theme['id']?>" title="Módosítás"></a>
						<a class="typcn typcn-trash js_thm_del" href="#<?=$theme['id']?>" title="Törlés"></a>
					</div>
				</li>
<?php		} ?>
				<li>
					<div class="top">
						<span class='rovid'>Új kategória</span>
						<span class='nev'></span>
					</div>
					<div class="bottom">
						<a class="typcn typcn-plus js_thm_add" href="#" title="Hozzáadás"></a>
					</div>
				</li>
			</ul>
<?php break;

		case 'add':
			$classmembers = $db->rawQuery('SELECT *
										FROM `users`
										WHERE `classid` = ?',array($user['classid']));

			$themes = $db->rawQuery('SELECT *
									FROM `group_themes`
									WHERE `classid` = ?',array($user['classid']));

			$thmid = $ENV['URL'][1];
			if (System::InputCheck($thmid,'numeric')) die(header('Location: /groups')); ?>

			<h1>Új csoport hozzáadása</h1>
			<p class='ptag'>Csoport neve: <input type='text' id='name' placeholder='Csoportnév'></p>
			<p class='ptag'>Csoport témája (csop.elv.): <select id='theme'>
<?php           foreach($themes as $theme){
					if ($theme['id'] == $thmid) $str = ' selected';
					else $str = '';
					print "<option value='{$theme['id']}'{$str}>{$theme['name']}</option>";
				} ?>
			</select></p>
			<div class='selectdiv'>
				<p class='selectp'>Csoport tagjai:</p>
				<select multiple size='10' id='member'>
					<option value='empty'>(üres lista)</option>
				</select>
			</div>

			<div class='selectdiv'>
				<div class='selectbutton'>
					<input type='button' value='<<' id='2to1' style='margin-bottom: 3px'><br>
					<input type='button' value='>>' id='1to2'>
				</div>
			</div>

			<div class='selectdiv'>
				<p class='selectp'>Osztály többi tagja:</p>
				<select multiple size='10' id='class'>
<?php               foreach ($classmembers as $member){
						print "<option value='{$member['id']}'>{$member['realname']}</option>";
					} ?>
				</select>
			</div>

			<p><button id='sendform' class='btn'>Csoport hozzáadása</button> vagy <a href='/groups'>visszalépés</a></p>

<?php	break;

		case 'edit':
			$group = $db->rawQuery('SELECT *
									FROM `groups`
									WHERE `classid` = ? AND `id` = ?',array($user['classid'],$ENV['URL'][1]));
			if (empty($group)) die(header('Location: /groups'));
			else $group = $group[0];

			$members = $db->rawQuery('SELECT group_members.id AS id, users.realname as `name`, users.id as uid
									FROM `group_members`
									LEFT JOIN `users`
									ON group_members.userid = users.id
									WHERE group_members.classid = ? && group_members.groupid = ?',array($user['classid'],$ENV['URL'][1]));

			$classmembers = $db->rawQuery('SELECT *
										FROM `users`
										WHERE `classid` = ?',array($user['classid']));

			$themes = $db->rawQuery('SELECT *
									FROM `group_themes`
									WHERE `classid` = ?',array($user['classid']));

			$cmem = array();
			foreach($classmembers as $member)
				$cmem[$member['id']] = $member;
			$classmembers = $cmem;

			foreach($members as $member)
				unset($classmembers[$member['uid']]); ?>

			<h1>A(z) <?=$group['name']?> csoport módosítása</h1>
			<p class='ptag'>Csoport neve: <input type='text' id='name' value='<?=$group['name']?>'></p>
			<p class='ptag'>Csoport témája (csop.elv.): <select id='theme'>
<?php           foreach($themes as $theme){
					if ($group['theme'] == $theme['id']) $selected = ' selected';
					else $selected = '';
					print "<option value='{$theme['id']}'{$selected}>{$theme['name']}</option>";
				} ?>
			</select></p>
			<div class='selectdiv'>
				<p class='selectp'>Csoport tagjai:</p>
				<select multiple size='10' id='member'>
<?php               foreach ($members as $member){
						print "<option value='{$member['uid']}'>{$member['name']}</option>";
					} ?>
				</select>
			</div>

			<div class='selectdiv'>
				<div class='selectbutton'>
					<input type='button' value='<<' id='2to1' style='margin-bottom: 3px'><br>
					<input type='button' value='>>' id='1to2'>
				</div>
			</div>

			<div class='selectdiv'>
				<p class='selectp'>Osztály többi tagja:</p>
				<select multiple size='10' id='class'>
<?php               foreach ($classmembers as $member){
						print "<option value='{$member['id']}'>{$member['realname']}</option>";
					} ?>
				</select>
			</div>

			<p><button id='sendform' class='btn'>Módosítások mentése</button> vagy <a href='/groups'>visszalépés</a></p>
<?php	break;

		case 'members':
			$id = $ENV['URL'][1];
			if (System::InputCheck($id,'numeric')) die(header('Location: /groups'));
			if (System::ClassPermCheck($id,'groups')) die(header('Location: /groups'));

			$group = $db->rawQuery('SELECT *
									FROM `groups`
									WHERE `classid` = ? AND `id` = ?',array($user['classid'],$id))[0];
			if (empty($group)) die(header('Location: /groups'));

			print "<h1>A(z) {$group['name']} ({$ENV['class']['classid']}) csoport tagjainak megjelenítése</h1><p class='ptag memberparagh'>Csoport tagjai:</p><ul class='memberlist'>";

			$members = $db->rawQuery('SELECT users.realname as `name`
									FROM `group_members`
									LEFT JOIN `users`
									ON group_members.userid = users.id
									WHERE group_members.classid = ? && group_members.groupid = ?',array($user['classid'],$ENV['URL'][1]));

			foreach ($members as $member)
				print "<li>{$member['name']}</li>";

			if (empty($members))
				print "<li>(nincs tagja a csoportnak)</li>";

			print "</ul><p><a class='btn' href='/groups'>Visszalépés</a></p>";
		break;
	}