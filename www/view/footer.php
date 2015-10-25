</main>
<?php
	foreach ($js_list as $value){
		print '<script src="'.$rootdoc.'resources/js/'.$value.'"></script>'."\n";
	}

	# Script futtatás összidejének meghatározása
	$ENV['EXECTIME']['end'] = microtime(true);

	# Script futtatás össidejének kiíratása ?>

	<!-- Script execution time: <?=round($ENV['EXECTIME']['end'] - $ENV['EXECTIME']['start'],4)?>s -->

<?php
	# Szoftver információ kiíratása ?>
	<!-- <?=$ENV['SOFTWARE']['NAME']?> <?=$ENV['SOFTWARE']['VER']?> (commit ID: <?=!empty($ENV['SOFTWARE']['COMMIT']) ? $ENV['SOFTWARE']['COMMIT'] : 'unknown'?>, codename: <?=$ENV['SOFTWARE']['CODENAME']?>) -->
	<!-- Software engine: <?=$ENV['ENGINE']['NAME']?> <?=$ENV['ENGINE']['VER']?> (codename: <?=$ENV['ENGINE']['CODENAME']?>) -->

<?php
	# Easter Egg üzenet kiíratása
	if (!empty($ENV['EE_MESSAGE'])) { ?>

		<!-- <?=$ENV['EE_MESSAGE']?> -->
<?php	} ?>

</body>
</html>