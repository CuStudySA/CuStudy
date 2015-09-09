<div id="sidebar">
	<div class="userdata clearfix">
		<img src="/resources/img/user.svg">
		<h2 class="name"><?=$user['realname']?></h2>
		<span class="email"><?=$user['email']?></span>
	</div>
	<div class="options"><?php
$Actions = array(
	array('home','fooldal','Főoldal'),
	array('calendar','timetables','Órarend'),
	array('globe','homeworks','Házi feladatok'),
	array('user','profile','Profilom'),
	array('contacts','teachers','Tanárok'),
	array('th-menu','lessons','Tantárgyak'),
);

if (USRGRP == 'admin')
	$Actions = array_merge($Actions,array(
		array('th-large','groups','Csoportok'),
		array('group','users','Felhasználók'),
		//array('document-text','logs','Tevékenységnapló'),
	));

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