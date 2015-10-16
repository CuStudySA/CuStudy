<?php
	define('ABSPATH_',str_replace('.lc','.tk',ABSPATH));
?>

<div id="sidebar">
	<div class="userdata clearfix">
		<div class="avatar">
			<img src="https://www.gravatar.com/avatar/<?=md5($user['email'])?>?s=70&r=g&d=<?=urlencode(ABSPATH_.'/resources/img/user.png')?>">
		</div>
		<h2 class="name"><?=$user['realname']?></h2>
		<span class="email"><?=$user['email']?></span>
	</div>
	<nav class="options"><?php
$Actions = array(
	array('home','','Főoldal'),
	array('calendar','timetables','Órarend'),
	array('globe','homeworks','Házi feladatok'),
	array('flash','events','Események'),
	array('document','files','Dokumentumok'),
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
<main>
