</main>
<?php
	foreach ($js_list as $value){
		print '<script src="'.$rootdoc.'resources/js/'.$value.'"></script>'."\n";
	}

	# Script futtatás összidejének meghatározása
	$ENV['EXECTIME']['end'] = microtime(true);

	# Script futtatás össidejének kiíratása ?>

	<!-- Script execution time: <?=round($ENV['EXECTIME']['end'] - $ENV['EXECTIME']['start'],4)?>s -->
	<!-- # of executed SQL queries: <?=MysqliDB::$numberOfExecution?> -->
<?php
	# Szoftver információ kiíratása ?>
	<!--
		                                                        ,,
		  .g8"""bgd             .M"""bgd mm                   `7MM
		.dP'     `M            ,MI    "Y MM                     MM
		dM'       ``7MM  `7MM  `MMb.   mmMMmm `7MM  `7MM   ,M""bMM `7M'   `MF'
		MM           MM    MM    `YMMNq. MM     MM    MM ,AP    MM   VA   ,V
		MM.          MM    MM  .     `MM MM     MM    MM 8MI    MM    VA ,V
		`Mb.     ,'  MM    MM  Mb     dM MM     MM    MM `Mb    MM     VVV
		  `"bmmmd'   `Mbod"YML.P"Ybmmd"  `Mbmo  `Mbod"YML.`Wbmd"MML.   ,V
		                                                              ,V
		                                                           OOb"
	-->
	<!-- <?=$ENV['SOFTWARE']['NAME']?> <?=$ENV['SOFTWARE']['VER']?> (commit ID: <?=!empty($ENV['SOFTWARE']['COMMIT']) ? $ENV['SOFTWARE']['COMMIT'] : 'unknown'?>, codename: <?=$ENV['SOFTWARE']['CODENAME']?>) -->
	<!-- Software engine: <?=$ENV['ENGINE']['NAME']?> <?=$ENV['ENGINE']['VER']?> (codename: <?=$ENV['ENGINE']['CODENAME']?>) -->
<?php
	# Easter Egg üzenet kiíratása
	if (!empty($ENV['EE_MESSAGE'])) { ?>

	<!-- <?=$ENV['EE_MESSAGE']?> -->
<?php  } ?>
</body>
</html>