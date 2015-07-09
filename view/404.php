<?=!in_array('sidebar',$doc_list) ? '<main>' : ''?>
<?php
	if (isset($ENV['GET']['path'])) $reqfile = $ENV['GET']['path'];
	else if (!empty($ENV['do'])) $reqfile = '/'.$ENV['do'];
	else $reqfile = '(ismeretlen)'; ?>

<h1>404 - Az oldal nem található</h1>
<p>A kersett oldalt nem sikerült betölteni, mert a kívánt erőforrás/oldal nem található!</p>
<p><b>Kért oldal: </b><?=$reqfile?></p>