<?php

	$action = $ENV['URL'][0] ?? null;

	switch ($action){
		default: ?>
	<h1>Tanárok által összeállított feladatsorok</h1>

	<h2>Lezáratlan</h2>

	<h2>Lezárt</h2>
<?php   break;
	}
