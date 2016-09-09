<h1 id=h1cim><?=System::Article($ENV['class']['classid'], true)?> osztály eseményei</h1>

<?php
	$Btns = array();
	if (!System::PermCheck('events.add')){
		$Btns[] = array('plus','add','Hozzáadás');
	}
	if (!System::PermCheck('events.edit') || !System::PermCheck('events.delete'))
		array_splice($Btns,0,0,array(array('spanner','switchToSelectionMode','Esemény kijelölése')));

	if (!System::PermCheck('events.edit'))
		$Btns[] = array('pencil','edit','Szerkesztés',true);

	if (!System::PermCheck('events.delete'))
		$Btns[] = array('trash','delete','Törlés',true);

	foreach ($Btns as $btn)
		echo "<button class='btn typcn typcn-{$btn[0]} js_{$btn[1]}'".(isset($btn[3])?' disabled':'').">{$btn[2]}</button> "; ?>

<span class='selectNotify'>A folytatáshoz kattintson egy eseményre, majd nyomja meg újra a gombot!</span>
<div id='calendar'></div>
