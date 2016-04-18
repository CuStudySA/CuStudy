<div id="sidebar">
	<div class="userdata clearfix">
		<div class="avatar">
			<img src="<?=UserTools::GetAvatarUrl($user)?>">
			<a class="typcn typcn-cog sessionswitch"></a>
		</div>
		<h2 class="name"><?=$user['name']?></h2>
		<span class="email"><?=$user['email']?></span>
	</div>
	<nav class="options"><?php

$Actions = array(
	array('home','','Főoldal'),
);

if (in_array(ROLE,array_keys($Perm['students']))){
	$Actions = array_merge($Actions,array(
		array('calendar','timetables','Órarend'),
		array('globe','homeworks','Házi feladatok'),
		array('flash','events','Események'),
		array('document','files','Dokumentumok'),
		array('contacts','teachers','Tanárok'),
		array('th-menu','lessons','Tantárgyak'),
	));
}

if (in_array(ROLE,array_keys($Perm))){
	$Actions = array_merge($Actions,array(
		array('user','system.users','Felhasználók'),
		array('group','system.classes','Osztályok'),
		array('calendar','system.events','Események'),
		array('document-text','logs','Rendszernapló'),
	));
}

if (ROLE == 'admin')
	$Actions = array_merge($Actions,array(
		array('th-large','groups','Csoportok'),
		array('group','users','Felhasználók'),
		//array('document-text','logs','Tevékenységnapló'),
	));

$Actions[] = array('user','profile','Profilom');

if (!isset($user['tempSession']))
	$Actions[] = array('power','#logout','Kijelentkezés');
else
	$Actions[] = array('arrow-back','#exit','Kilépés az osztályból');

foreach ($Actions as $a){
	list($icon, $link, $text) = $a;

	if (preg_match("~^/$link($|/)~", strtok($ENV['SERVER']['REQUEST_URI'],'?')))
		$icon .= ' current';

	if (!empty($link) && $link[0] === '#')
		list($attr,$val) = array('id', substr($link, 1));
	else list($attr,$val) = array('href',"/$link");

	echo "<a $attr='$val' class='typcn typcn-{$icon}'>{$text}</a>";
}
	?></nav>

	<h1>CuStudy</h1>
</div>
