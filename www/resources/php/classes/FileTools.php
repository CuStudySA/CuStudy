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

		static function Upload($file){
			// Sikerült-e a fájlfeltöltés?
			if ($file['error'] != 0) return 1;
			
			// Van-e hely a tárhelyen?
			if ($file['size'] > self::GetFreeSpace()) return 3;
			
			// Van-e hely a szerveren?
			if ($file['size'] > disk_free_space('/')) return 4;
			
			// Hely meghatározása
			$fileName = Password::Generalas();
			global $root;
			$path = "{$root}usr_uploads/{$fileName}";
			
			// Mozgatás a végleges helyre
			if (move_uploaded_file($file['tmp_name'],$path)) return [$fileName, md5_file($path)];
			else return 5;
		}

		/**
		 * array(
		 *    (req)'file' => array() //PHP File Array
		 *    (req)'name'
		 *    (req)'description'
		 *    (opt)'classid'
		 *    (opt)'uploader'
		 *    (opt)'lessonid'
		 * )
		 * @param $data
		 * @return int|array
		 */
		static function Insert($data){
			global $user;

			$action = self::_insert($data);
			$isSuccess = is_array($action);

			$basicData = System::TrashForeignValues(['name','description','classid','uploader','lessonid'],$data);

			$fileInfo = [];
			if (!empty($data['file']['size']))
				$fileInfo['size'] = $data['file']['size'];
			if (!empty($data['file']['name']))
				$fileInfo['filename'] = $data['file']['name'];
			if ($isSuccess){
				$fileInfo['md5'] = $action['md5'];
				$fileInfo['tempname'] = $action['tempname'];
				$fileInfo['e_id'] = $action['file_id'];
			}

			Logging::Insert(array_merge(array(
				'action' => 'files.uploadFile',
				'errorcode' => $isSuccess ? 0 : $action,
				'db' => 'files',
			),$basicData,$fileInfo,array(
				'time' => date('c'),
				'lessonid' => !empty($data['lessonid']) ? $data['lessonid'] : 0,
				'classid' => !empty($data['classid']) ? $data['classid'] : $user['class'][0],
				'uploader' => !empty($data['uploader']) ? $data['uploader'] : $user['id'],
			)));

			return $data;
		}

		static private function _insert($data){
			global $user,$db;

			$file = FileTools::Upload($data['file']);

			if (is_int($file))
				return 1;

			$action = $db->insert('files',array(
				'name' => !empty($data['name']) ? $data['name'] : 'Feltöltött dokumentum',
				'description' => !empty($data['description']) ? $data['description'] : 'Egy feltöltött dokumentum leírása',
				'lessonid' => !empty($data['lessonid']) ? $data['lessonid'] : 0,
				'classid' => !empty($data['classid']) ? $data['classid'] : $user['class'][0],
				'uploader' => !empty($data['uploader']) ? $data['uploader'] : $user['id'],
				'size' => $data['file']['size'],
				'filename' => $data['file']['name'],
				'tempname' => $file[0],
				'md5' => $file[1],
			));
			if (!is_numeric($action)) return 2;

			return array(
				'file_id' => $action,
				'tempname' => $file[0],
				'md5' => $file[1],
			);
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
			global $db, $user;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('files');

			if (!empty($data))
				$data = System::TrashForeignValues(['name','lessonid','description','classid','size','time','uploader','filename','tempname','md5'],$data);
			else $data = [];

			$action = self::_deleteFile($id);

			Logging::Insert(array_merge(array(
				'action' => 'files.delete',
				'errorcode' => $action,
				'db' => 'files',
			),$data,array(
				'e_id' => $id,
			)));

			return $action;
		}

		static private function _deleteFile($id){
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
			global $db, $user;

			$data = $db->where('id',$id)->where('classid',$user['class'][0])->getOne('files');
			if (empty($data)) return 1;

			$lesson = $db->where('id',$data['lessonid'])->getOne('lessons','name');
			$uploader = $db->where('id',$data['uploader'])->getOne('users','name,id');

			return array(
				'name' => $data['name'],
				'description' => $data['description'],
				'lesson' => empty($lesson) ? 'nincs hozzárendelve' : $lesson['name'],
				'size' => self::FormatSize($data['size']),
				'time' => $data['time'],
				'uploader' => empty($uploader) ? 'ismeretlen' : $uploader['name'].' (#'.$uploader['id'].')',
				'filename' => $data['filename'],
				'md5' => !empty($data['md5']) ? $data['md5'] : '(ismeretlen)',
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

				$mivel = System::Article(self::IsOfficeFile($file['filename']) ? 'Office Online' : 'böngésző');
				$openBtn = FileTools::IsOpenableFile($file['filename']) ?
							'<a class="typcn typcn-zoom js_open_external_viewer" href="#'.$file['id'].'" title="Fájl megtekintése '.$mivel.' segítségével"></a>' :
							'';

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
						$openBtn
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

		static function GenerateViewingToken($file, $fileid){
			global $db;

			if (System::PermCheck('files.view',$fileid))
				return 1;

			$token = Password::Generalas();

			if (!defined('OFFICE_VIEWING_URL'))
				return 2;

			$action = $db->insert('files_external_viewing',array(
				'file' => $fileid,
				'token' => $token,
			));

			if ($action === false)
				return 3;

			if (self::IsOfficeFile($file['filename']))
				return OFFICE_VIEWING_URL.'?src='.urlencode(preg_replace('/^https/','http',ABSPATH)."/files/getFileForViewer/$token");

			return ABSPATH."/files/getFileForViewer/$token";
		}

		private static $OFFICE_EXTENSTIONS = ['doc','docx','xls','xlsx','ppt','pps','pptx','ppsx'];
		public static $OPENABLE_EXTENSIONS = ['txt','jpeg','gif','svg','html','png','jpg','pdf'];

		static function IsOfficeFile($name, $extension = false){
			return in_array($extension ? $name : self::GetFileExtension($name), self::$OFFICE_EXTENSTIONS);
		}

		static function IsOpenableFile($name, $extension = false){
			return in_array($extension ? $name : self::GetFileExtension($name),array_merge(self::$OFFICE_EXTENSTIONS,self::$OPENABLE_EXTENSIONS));
		}

		private static function GetFileExtension($name){
			return array_slice(explode('.',$name), -1, 1)[0];
		}

		private static function GetMimeType($fname){
			$ext = self::GetFileExtension($fname);

			switch ($ext){
				case "html":
					return "text/html";
				case "txt":
					return "text/plain";
				case "svg":
					return "image/svg+xml";
				case "jpg":
					$ext = 'jpeg';
				case "jpeg":
				case "gif":
				case "png":
					return "image/$ext";
				case "doc":
					return "application/msword";
				case "docx":
					return "application/vnd.openxmlformats-officedocument.wordprocessingml.document";
				case "xls":
					return "application/vnd.ms-excel";
				case "xlsx":
					return "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
				case "ppt":
				case "pps":
					return "application/vnd.ms-powerpoint";
				case "pptx":
					return "application/vnd.openxmlformats-officedocument.presentationml.presentation";
				case "ppsx":
					return "application/vnd.openxmlformats-officedocument.presentationml.slideshow";
				case "pdf":
					return "application/pdf";
				default:
					trigger_error("Nem található MIME típus a(z) $ext kiterjesztéshez", E_USER_WARNING);
					return "text/plain";
			}
		}

		static function OpenFileForViewing($param){
			global $db, $root, $ENV;

			$token = explode('.',$param)[0];

			$data = $db->where('token',$token)->getOne('files_external_viewing');
			if (empty($data))
				Message::Missing($ENV['SERVER']['REQUEST_URI']);
			else if (time() < strtotime($data['gen'])+60){
				$data = $db->where('id',$data['file'])->getOne('files');
				$fileName = $data['filename'];

				$path = "$root/usr_uploads/{$data['tempname']}";
				if (!file_exists($path)){
					Message::StatusCode(500);
					die();
				}

				header('Content-Length: '.filesize($path));
				header('Content-Type: '.self::GetMimeType($fileName));
				header("Content-Disposition: ".(self::IsOfficeFile($fileName) ? 'attachment' : 'inline')."; filename={$fileName}");
				//header("Content-Disposition: attachment; filename=".preg_replace('/[a-z.\d]/i','_',$fileName)."; filename*= UTF-8''".urlencode($fileName));

				readfile($path);
				die();
			}
			else {
				$db->where('token',$token)->delete('files_external_viewing');
				$header = 410;
				$desc = 'A fájl megtekintési ideje lejárt, nyisd meg újra a <a href="/files">Dokumentumok</a>. menüpontból.';
			}

			Message::StatusCode($header);
			die("<h1>$header</h1><p>$desc</p>");
		}
	}

