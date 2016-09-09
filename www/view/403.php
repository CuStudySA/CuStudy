<?php
	if (ROLE !== 'guest')
		echo "</main>";
	if (isset($ENV['GET']['path'])) $reqfile = $ENV['GET']['path'];
	else if (!empty($ENV['do'])) $reqfile = '/'.$ENV['do'];
	else $reqfile = '(ismeretlen)'; ?>

<div id="wrap">
	<div id="mid">
		<div id="inner">
			<h1>403</h1>
			<p>A keresett oldalhoz nincs hozzáférése.<br><strong>Elérési útvonal: </strong><?=$reqfile?></p>
			<p>
				<a href="/" class="btn typcn typcn-home">Vissza a főodalra</a>
				<a href="https://support.custudy.hu" class="btn typcn typcn-world">Hibabejelentés</a>
			</p>
		</div>
	</div>
</div>
