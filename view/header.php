<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<!-- Show empty favicon
<link href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAYAAABPYyMiAAAABmJLR0T///////8JWPfcAAAACXBIWXMAAABIAAAASABGyWs+AAAAF0lEQVRIx2NgGAWjYBSMglEwCkbBSAcACBAAAeaR9cIAAAAASUVORK5CYII=" rel="icon" type="image/x-icon" />
Favicon end -->
<title><?=$pages[$do]['title']?> - CuStudy</title>
<?php
	foreach ($css_list as $value){
		print '<link rel="stylesheet" href="'.$rootdoc.'resources/css/'.$value.'">'."\n";
	}
	if (!isset($_REQUEST['no-header-js'])){ ?>
<script src="<?=$rootdoc.'resources/js/'?>jquery.min.js"></script>
<script src="<?=$rootdoc.'resources/js/'?>prefixfree.min.js"></script>
<?php } ?>
</head>
<body>