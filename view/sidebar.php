<div id="sidebar">
	<div class="userdata clearfix">
		<img src="/resources/img/user.svg">
		<h2 class="name"><?=$user['realname']?></h2>
		<span class="email"><?=$user['email']?></span>
	</div>
	<div class="options"><?php
$Actions = array(
	array('home','fooldal','Főoldal'),
	array('user','profile','Profilom'),
);

if (!System::PermCheck('user','admin')){
	$Actions[] = array('calendar','timetables','Órarend');
	$Actions[] = array('contacts','teachers','Tanárok');
	$Actions[] = array('th-large','groups','Csoportok');
	$Actions[] = array('globe','homeworks','Házi feladatok');
}

if (!System::PermCheck('admin','admin')){
	$Actions[] = array('group','users','Felhasználók');
	$Actions[] = array('th-menu','lessons','Tantárgyak');
}
if (!System::PermCheck('schooladmin','schooladmin')){
	$Actions[] = array('mortar-board','classes','Osztályok');
	$Actions[] = array('group','users','Adminisztrátorok');
}
if (!System::PermCheck('admin','schooladmin'))
	$Actions[] = array('document-text','logs','Tevékenységnapló');

if (!System::PermCheck('sysadmin'))
	$Actions[] = array('document-text','logs','Tevékenységnapló');

$Actions[] = array('power','#logout','Kijelentkezés');

foreach ($Actions as $a){
	list($icon, $link, $text) = $a;
	if ($link[0] === '#') $link = array('id', substr($link,1));
	else {
		if ($do == $link) $icon .= ' current';
		$link = array('href',"/$link");
	}
	list($attr,$val) = $link;

	echo "<a $attr='$val' class='typcn typcn-{$icon}'>{$text}</a>";
}
	?></div>

	<h1>CuStudy</h1>
</div>
<main>