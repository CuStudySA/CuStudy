<?php
	if (ROLE !== 'guest')
		echo "</main>";
	if (isset($ENV['GET']['path'])) $reqfile = $ENV['GET']['path'];
	else if (!empty($ENV['do'])) $reqfile = '/'.$ENV['do'];
	else $reqfile = '(ismeretlen)';

	if ($reqfile == '/404') $reqfile = '(ismeretlen)'; ?>

<div id="wrap">
	<div id="mid">
		<div id="inner">
			<h1>404</h1>
			<p>A keresett oldal nem található.<br><strong>Elérési útvonal: </strong><?=$reqfile?></p>
			<p>
				<a href="/" class="btn typcn typcn-home">Vissza a főodalra</a>
				<a href="mailto:mbalint987@pageloop.tk?subject=CuStudy%20Hibabejelentés" class="btn typcn typcn-mail">Hibabejelentés</a>
			</p>
		</div>
	</div>
</div>
