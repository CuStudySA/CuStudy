<?php

	class PasswordReset {
		static function GetRow($hash){
			global $db;

			$Reset = $db->where('hash',$hash)->getOne('pw_reset');
			$Reset['expired'] = empty($Reset) || strtotime($Reset['expires']) < time();
			if ($Reset['expired'] && !empty($Reset['hash']))
				self::Invalidate($Reset['hash']);

			return $Reset;
		}

		static function Invalidate($hash){
			global $db;

			$db->where('id',$hash)->delete('pw_reset');
		}

		static $resetBody = <<<STRING
		<h2>CuStudy - Jelszóvisszaállítási kérelem</h2>

		<h3>Tisztelt ++NAME++!</h3>

		<p>A CuStudy rendszerében jelszava visszaállítását kezdeményezték. Ammennyiben nem Ön kérte ezt, az üzenetünket figyelmen kivül hagyhatja. Ellenkező esetben <a href="++URL++">kattintson ide</a> egy új jelszó megadásához, vagy másolja be ezt a linket a böngésző címsorába:<br><a href="++URL++">++URL++</a></p>

		<p>Felhívjuk figyelmét, hogy a link az üzenet küldéstől számítva 30 percig (++VALID++) használható. Amennyiben a lejárat előtt újabb jelzóvisszallítási kérelmet kezdeményez, a korábbi kérelmek törlésre kerülnek.</p>

		<p>Üdvözlettel,<br>
		<b>CuStudy Software Alliance</p>
STRING;

		static function SendMail($email){
			global $ENV, $db;

			$email = trim($email);
			if (System::InputCheck($email,'email')) return 1;

			$User = $db->where('email', $email)->getOne('users','id,name,email');
			if (empty($User)) return 2;

			// Korábbi visszaállítási kódok érvénytelenítése
			$db->where('userid', $User['id'])->delete('pw_reset');

			$hash = openssl_random_pseudo_bytes(64);
			$valid = strtotime('+30 minutes');

			if (!$db->insert('pw_reset',array(
				'hash' => $hash,
				'userid' => $User['id'],
				'expires' => date('c',$valid)
			))) return 3;

			$body = self::$resetBody;
			$body = str_replace('++NAME++',$User['name'],$body);
			$body = str_replace('++URL++',ABSPATH.'/pw-reset?key='.urlencode($hash),$body);
			$body = str_replace('++VALID++',date('Y-m-d H:i:s',$valid),$body);

			if (System::SendMail(array(
				'title' => 'CuStudy - Jelszóvisszaállítási kérelem',
				'to' => array(
					'name' => $User['name'],
					'address' => $User['email'],
				),
				'body' => $body,
			))) return 4;

			return 0;
		}

		static function Reset($data){
			global $ENV, $db;

			if (empty($data['hash'])) return 1;

			$Reset = self::GetRow(urldecode($data['hash']));
			if (empty($Reset) || $Reset['expired']) return 2;

			if (empty($data['password']) || empty($data['verpasswd'])) return 3;

			$password = $data['password'];
			$verpassword = $data['verpasswd'];

			$User = $db->where('id', $Reset['userid'])->getOne('users');
			if (empty($User)) return 4;

			if ($password != $verpassword) return 5;

			$password = Password::Kodolas($password);
			if (!$db->where('id', $User['id'])->update('users', array('password' => $password))) return 6;

			self::Invalidate($Reset['hash']);
			return 0;
		}
	}
