<?php
	if (!isset($ENV['URL'][0]))
		$case = 'default';
	else
		$case = $ENV['URL'][0];

	switch ($case){
		default: ?>
			<h1 id="h1cim">A(z) <?=$ENV['class']['classid']?> osztály tevékenységnaplója</h1>
			<table id='data'>
				<thead>
					<tr>
						<td>#ID</td>
						<td class='ttime'>Időpont</td>
						<td>Kezdeményező</td>
						<td>Esemény</td>
					</tr>
				</thead>
			<tbody>
<?php
			$data = $db->rawQuery('SELECT * FROM `log_central` WHERE `user` != 0 ORDER BY `id` DESC LIMIT 30');
			$i = 0;
			foreach ($data as $subdata){
				$i++;
				$userdata = $db->where('id',$subdata['user'])->getOne('users');
				if (empty($userdata)) continue;
				if (USRGRP == 'admin' && $userdata['classid'] != $user['class'][0]) continue;
				switch ($subdata['action']){
					case 'login':
						$action = 'Bejelentkezés';
					break;
				} ?>
						<tr>
							<td class="entryid"><?=$subdata['id']?></td>
							<td class="timestamp ttime"><time datetime="<?=date('c',strtotime($subdata['time']))?>" data-order="{{y}}. {{mo}} {{d}}.<br>{{h}}:{{mi}}"></time><span class="dynt-el"></span></td><!--:{{s}}-->
							<td><?=$userdata['name']?></td>
							<td class="reftype">
								<span class="expand-section"><?=$action?></span>
							</td>
						</tr>
<?php       }
            if ($i == 0) print "</table><p style='text-align: center'>Nem található bejegyzés!</p>";
            else print "</table>";
		break;
	}