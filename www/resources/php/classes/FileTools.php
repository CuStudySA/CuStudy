<?php

	class FileTools {
		const CLASS_SPACE = 268435456;

		static function GetUsedSpace(){
			global $db, $user;

			return $db->where('classid', $user['class'][0])->getOne('files','SUM(size)')['SUM(size)'];
		}

		static function GetFreeSpace(){
			return self::CLASS_SPACE - self::GetUsedSpace();
		}

		static function GetSpaceUsage($key = null){
			$Used = (float) self::GetUsedSpace();
			$Available = self::CLASS_SPACE;

			$UsedPercent = 0;
			if ($Used > 0){
				$UsedPercent = round(($Used / $Available) * 1000) / 10;

				if ($UsedPercent == 0)
					$UsedPercent = '<0.01';
			}

			$UsedReadable = FileTools::FormatSize($Used);
			$AvailableReadable = FileTools::FormatSize($Available);

			$return = array(
				'Used' => $UsedReadable,
				'Available' => $AvailableReadable,
				'Used%' => $UsedPercent,
			);
			return !empty($key) ? $return[$key] : $return;
		}

		static function FormatSize($byte){
			if ($byte < 1024)
				return $byte.' B';
			else if ($byte > 1024 && $byte < 1024 * 1024)
				return round(($byte/1024),2).' KB';
			else
				return round(($byte/(1024*1024)),2).' MB';
		}

		static function UploadFile($file){
			// Sikerült-e a fájlfeltöltés?
			if ($file['error'] != 0) return 1;
			
			// Van-e hely a tárhelyen?
			if ($file['size'] > self::GetFreeSpace()) return 3;
			
			// Van-e hely a szerveren?
			if ($file['size'] > disk_free_space('/')) return 4;
			
			// Hely meghatározása
			$fileName = Password::Generalas();
			$path = "usr_uploads/{$fileName}";
			
			// Mozgatás a végleges helyre
			if (move_uploaded_file($file['tmp_name'],$path)) return [$fileName];
			else return 5;
		}

		static function DownloadFile($id){
			global $db, $user, $root;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('files');

			if (empty($data)) die(header('Location: /files'));
			$fileName = $data['filename'];

			$path = "$root/usr_uploads/".$data['tempname'];
			if (!file_exists($path)) die();

			$finfo = finfo_open(FILEINFO_MIME_ENCODING);
			header('Content-Transfer-Encoding: utf-8');
			header("Content-Description: File Transfer");
			header("Content-Type: application/octet-stream");
			header('Content-Length: '.filesize($path));
			header("Content-Disposition: attachment; filename=\"$fileName\"");

			readfile($path);
			die();
		}

		static function DeleteFile($id){
			global $db, $user, $root;

			# Jog. ellenörzése
			if (System::PermCheck('files.delete')) return 1;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('files');
			if (empty($data)) return 2;

			$path = "$root/usr_uploads/".$data['tempname'];
			if (file_exists($path))
				unlink($path);

			$action = $db->where('id',$id)->delete('files');

			return $action ? 0 : 3;
		}

		static function GetFileInfo($id){
			global $db, $user, $root;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('files');
			if (empty($data)) return 1;

			$lesson = $db->where('id',$data['lessonid'])->getOne('lessons');
			$uploader = $db->where('id',$data['uploader'])->getOne('users');

			return array(
				'name' => $data['name'],
				'description' => $data['description'],
				'lesson' => empty($lesson) ? 'nincs hozzárendelve' : $lesson['name'],
				'size' => self::FormatSize($data['size']),
				'time' => $data['time'],
				'uploader' => empty($uploader) ? 'ismeretlen' : $uploader['name'].' (#'.$uploader['id'].')',
				'filename' => $data['filename'],
			);
		}

		static function RenderList($classid = null, $wrap = true){
			global $db;
			$HTML = $wrap ? "<ul class='files flex'>" :'';

			if (empty($classid)){
				global $user;
				$classid = $user['class'][0];
			}

			$data = $db->where('classid', $classid)->orderBy('time')->get('files');

			if (empty($data) && System::PermCheck('files.add'))
				return '';

			foreach ($data as $file) {
				$deleteButton = !System::PermCheck('files.delete')
					? "<a class='typcn typcn-trash js_delete' href='#{$file['id']}' title='Fájl törlése'></a>"
					: '';
				$HTML .= <<<HTML
				<li>
					<div class="top">
						<span class='rovid'>{$file['name']}</span>
						<span class='nev'>{$file['description']}</span>
					</div>
					<div class="bottom">
						<a class="typcn typcn-info-large js_more_info" href="#{$file['id']}" title="További információk"></a>
						$deleteButton
						<a class="typcn typcn-download" href="/files/download/{$file['id']}" title="Fájl letöltése" download></a>
					</div>
				</li>
HTML;
			}
			if (!System::PermCheck('files.add')) {
				$HTML .= <<<HTML
				<li class='new'>
					<div class="top">
						<span class='rovid'>Új dokumentum</span>
						<span class='nev'>Új dok. feltöltése</span>
					</div>
					<div class="bottom">
						<a class="typcn typcn-upload js_file_add" href="#" title="Fájlfeltöltés"></a>
					</div>
				</li>
HTML;
			}
			return $HTML.($wrap?'</ul>':'');
		}

		static private function SizeInBytes($size){
			$unit = substr($size, -1);
			$value = intval(substr($size, 0, -1), 10);
			switch(strtoupper($unit)){
				case 'G':
					$value *= 1024;
				case 'M':
					$value *= 1024;
				case 'K':
					$value *= 1024;
				break;
			}
			return $value;
		}

		static function GetMaxUploadSize(){
			$sizes = array(ini_get('post_max_size'), ini_get('upload_max_filesize'));

			$workWith = $sizes[0];
			if ($sizes[1] !== $sizes[0]){
				$sizesBytes = array();
				foreach ($sizes as $s)
					$sizesBytes[] = FileTools::SizeInBytes($s);
				if ($sizesBytes[1] < $sizesBytes[0])
					$workWith = $sizes[1];
			}

			return preg_replace('/^(\d+)([GMk])$/', '$1 $2B', $workWith);
		}
	}

