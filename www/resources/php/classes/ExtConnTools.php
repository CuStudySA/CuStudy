<?php

	class ExtConnTools {
		static $resolveAPIname = array(
			'facebook' => 'FacebookAPI',
			'google' => 'GoogleAPI',
			'microsoft' => 'MicrosoftAPI',
			'deviantart' => 'DeviantArtAPI',
			'github' => 'GitHubAPI',
		);

		static $apiDisplayName = array(
			'facebook' => 'Facebook',
			'google' => 'Google',
			'microsoft' => 'Microsoft',
			'deviantart' => 'DeviantArt',
			'github' => 'GitHub',
		);

		static $apiShortName = array(
			'facebook' => 'fb',
			'google' => 'gp',
			'microsoft' => 'ms',
			'deviantart' => 'da',
			'github' => 'gh',
		);

		static function DeactAndAct($connid, $type = 'deactivate'){
			global $db, $user;

			if (System::InputCheck($connid,'numeric')) return 1;

			$data = $db->where('id',$connid)->getOne('ext_connections');
			if (empty($data)) return 2;

			if ($data['userid'] != $user['id']) return 3;

			if ($type == 'deactivate'){
				if (!$data['active']) return 4;
			}
			else
				if ($data['active']) return 4;

			$action = $db->where('id',$connid)->update('ext_connections',array(
				'active' => $type == 'deactivate' ? 0 : 1,
			));

			return !$action ? 5 : 0;
		}

		static function Unlink($connid){
			global $db, $user;

			if (System::InputCheck($connid,'numeric')) return 1;

			$data = $db->where('id',$connid)->getOne('ext_connections');
			if (empty($data)) return 2;

			if ($data['userid'] != $user['id']) return 3;

			$action = $db->where('id',$connid)->delete('ext_connections');

			return !$action ? 4 : 0;
		}

		static function GetAvailProviders(){
			global $user, $db;
			return $db->where('userid', $user['id'])->orderBy('provider','ASC')->get('ext_connections');
		}

		static function GetConnWrap($entry, $wrap = true){
			global $user;

			$provider = self::$apiDisplayName[$entry['provider']];
			$provClass = self::$apiShortName[$entry['provider']];
			$username = !empty($entry['email']) ? $entry['email'] : $entry['name'];
			$statusClass = 'typcn-'.(!$entry['active'] ? 'tick' : 'power');
			$statusText = ($entry['active'] ? 'A' : 'Ina').'ktív';
			$currentPicProvider = $user['avatar_provider'] === $entry['provider'];
			$picMakeDisable = $currentPicProvider ? 'disabled' : '';
			$statusText .= $currentPicProvider ? ', profilkép' : '';
			$actBtnText = ($entry['active'] ? 'Dea' : 'A').'ktiválás';
			$picture = $entry['picture'];

			$return = <<<HTML
<div class="conn">
	<div class="icon">
		<img src="$picture">
		<div class="logo $provClass" title="$provider"></div>
	</div>
	<div class="text">
		<span class="n">$username</span>
		<strong class="status">$statusText</strong>
		<span class="actions">
			<button class='btn makepicture typcn typcn-image' $picMakeDisable>Profilkép</button>
			<button class='btn activeToggle typcn $statusClass'>$actBtnText</button>
			<button class='btn disconnect typcn typcn-media-eject'>Leválasztás</button>
		</span>
	</div>
</div>
HTML;

			if ($wrap)
				$return = "<div class='conn-wrap' data-id='{$entry['id']}' data-prov='{$entry['provider']}'>$return</div>";
			return $return;
		}
	}

