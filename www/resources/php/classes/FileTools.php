<?php

	class FileTools {
		const CLASS_SPACE = 268435456;
		const CLASS_MAX_FILESIZE = 36700160;

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
			
			// Méret ellenörzése
			if ($file['size'] > self::CLASS_MAX_FILESIZE) return 2;
			
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
	}

