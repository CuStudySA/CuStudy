<?php
	$Log = Logging::GetLog(); ?>

<h1>Rendszernapló</h1>

<?php
	if (is_int($Log))
		print System::Notice('fail','Nem tudtuk lekérdezni a rendszernaplót! Kérem próbálja újra később! Hibakód: '.$Log);
	else if (empty($Log))
		print System::Notice('info','Nincs megjeleníthető bejegyzés a naplóban!');
	else { ?>
		<table id="logs">
			<thead>
				<tr>
					<th class="entryid">#</th>
					<th class="timestamp">Időpont</th>
					<th class="ip">Kezdeményező</th>
					<th class="reftype">Esemény</th>
				</tr>
			</thead>
			<tbody>
<?php
			foreach ($Log as $entry){ ?>
				<tr>
					<td class="entryid"><?=$entry['id']?></td>
					<td class="timestamp"><time datetime="<?=$entry['time']?>" class="dynt"></time><span class="dynt-el"></span></td>
					<td class="ip"><span class="name"><?=$entry['username']?></span><br><?=$entry['ip']?></td>
					<td class="reftype"><span class="js_getDetails typcn typcn-plus expand-section" data-id='<?=$entry['id']?>'><?=$entry['action']?></span></td>
				</tr>
<?php       } ?>

			</tbody>
		</table>
<?php } ?>