<?php
namespace {
	require_once 'phpqrcode.php';
}

namespace RobThree\Auth\Providers\Qr {
	class LocalQRCodeProvider implements IQRCodeProvider {
		public function getMimeType(){
			return 'image/png';
		}

		public function getQRCodeImage($qrtext, $size){
			ob_start();
			\QRcode::png($qrtext, null, QR_ECLEVEL_L, 10, 2, false, 0xFFFFFF, 0x171D2D);
			$result = ob_get_contents();
			ob_end_clean();

			return $result;
		}
	}
}
