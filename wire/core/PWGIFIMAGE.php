<?php namespace ProcessWire;
///////////////////////////////////////////////////////////////////////////////////////////////////
// GIF Util - (C) 2003 Yamasoft (S/C)
// http://www.yamasoft.com
// All Rights Reserved
// This file can be freely copied, distributed, modified, updated by anyone under the only
// condition to leave the original address (Yamasoft, http://www.yamasoft.com) and this header.
///////////////////////////////////////////////////////////////////////////////////////////////////
// GIF Inspector - April 2016 - Horst Nogajski
// Original code by Fabien Ezber, modified by Horst Nogajski, to be used for image inspection only
// for ProcessWire 3+ (http://processwire.com/)
///////////////////////////////////////////////////////////////////////////////////////////////////
class PWGIFIMAGE {
	public $m_disp;
	public $m_bUser;
	public $m_bTrans;
	public $m_nDelay;
	public $m_nTrans;
	public $m_lpComm;
	public $m_gih;
	public $m_data;
	public $m_lzw;
	protected $extended;	  // @Horst: added flag
	public function __construct($extended = false) {
		unSet($this->m_disp);
		unSet($this->m_bUser);
		unSet($this->m_bTrans);
		unSet($this->m_nDelay);
		unSet($this->m_nTrans);
		unSet($this->m_lpComm);
		unSet($this->m_data);
		$this->m_gih = new PWGIFIMAGEHEADER($extended);
		if($extended) $this->m_lzw = new PWGIFLZW();
		$this->extended = $extended;
	}
	public function load($data, &$datLen): bool {
		$datLen = 0;
		while(true) {
			$b = ord($data[0]);
			$data = substr((string) $data, 1);
			$datLen++;
			switch($b) {
				case 0x21: // Extension
					$len = 0;
					if(!$this->skipExt($data, $len)) {
						return false;
					}
					$datLen += $len;
					break;
				case 0x2C: // Image
					// LOAD HEADER & COLOR TABLE
					$len = 0;
					if(!$this->m_gih->load($data, $len)) {
						return false;
					}
					$data = substr($data, $len);
					$datLen += $len;
					// @Horst: early return, because we only want to inspect the image,
					// not alter its bitmap data
					if(!$this->extended) {
						return true;
					}
					// ALLOC BUFFER
					$len = 0;
					if(!($this->m_data = $this->m_lzw->deCompress($data, $len))) {
						return false;
					}
					$data = substr($data, $len);
					$datLen += $len;
					if($this->m_gih->m_bInterlace) {
						$this->deInterlace();
					}
					return true;
				case 0x3B: // EOF
				default:
					return false;
			}
		}
		return false;
	}
	function skipExt(&$data, &$extLen): bool {
		$extLen = 0;
		$b = ord($data[0]);
		$data = substr((string) $data, 1);
		$extLen++;
		switch($b) {
			case 0xF9: // Graphic Control
				$b = ord($data[1]);
				$this->m_disp   = ($b & 0x1C) >> 2;
				$this->m_bUser  = ($b & 0x02) ? true : false;
				$this->m_bTrans = ($b & 0x01) ? true : false;
				$this->m_nDelay = $this->w2i(substr($data, 2, 2));
				$this->m_nTrans = ord($data[4]);
				break;
			case 0xFE: // Comment
				$this->m_lpComm = substr($data, 1, ord($data[0]));
				break;
			case 0x01: // Plain text
				break;
			case 0xFF: // Application
				break;
		}
		// SKIP DEFAULT AS DEFS MAY CHANGE
		$b = ord($data[0]);
		$data = substr($data, 1);
		$extLen++;
		while($b > 0) {
			$data = substr($data, $b);
			$extLen += $b;
			$b	= ord($data[0]);
			$data = substr($data, 1);
			$extLen++;
		}
		return true;
	}
	private function w2i($str): int {
		return ord(substr((string) $str, 0, 1)) + (ord(substr((string) $str, 1, 1)) << 8);
	}
	function deInterlace() {
		$data = $this->m_data;
		for($i = 0; $i < 4; $i++) {
			switch($i) {
				case 0:
					$s = 8;
					$y = 0;
					break;
				case 1:
					$s = 8;
					$y = 4;
					break;
				case 2:
					$s = 4;
					$y = 2;
					break;
				case 3:
					$s = 2;
					$y = 1;
					break;
			}
			for(; $y < $this->m_gih->m_nHeight; $y += $s) {
				$lne = substr((string) $this->m_data, 0, $this->m_gih->m_nWidth);
				$this->m_data = substr((string) $this->m_data, $this->m_gih->m_nWidth);
				$data =
					substr((string) $data, 0, $y * $this->m_gih->m_nWidth) .
					$lne .
					substr((string) $data, ($y + 1) * $this->m_gih->m_nWidth);
			}
		}
		$this->m_data = $data;
	}
}
