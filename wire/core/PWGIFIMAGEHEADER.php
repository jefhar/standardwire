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
class PWGIFIMAGEHEADER {
	public $m_nLeft;
	public $m_nTop;
	public $m_nWidth;
	public $m_nHeight;
	public $m_bLocalClr;
	public $m_bInterlace;
	public $m_bSorted;
	public $m_nTableSize;
	public $m_colorTable;
	public function __construct(protected $extended = false) {
		unSet($this->m_nLeft);
		unSet($this->m_nTop);
		unSet($this->m_nWidth);
		unSet($this->m_nHeight);
		unSet($this->m_bLocalClr);
		unSet($this->m_bInterlace);
		unSet($this->m_bSorted);
		unSet($this->m_nTableSize);
		unSet($this->m_colorTable);
	}
	public function load($lpData, &$hdrLen) {
		$hdrLen = 0;
		$this->m_nLeft   = $this->w2i(substr((string) $lpData, 0, 2));
		$this->m_nTop	= $this->w2i(substr((string) $lpData, 2, 2));
		$this->m_nWidth  = $this->w2i(substr((string) $lpData, 4, 2));
		$this->m_nHeight = $this->w2i(substr((string) $lpData, 6, 2));
		if(!$this->m_nWidth || !$this->m_nHeight) {
			return false;
		}
		$b = ord($lpData[8]);
		$this->m_bLocalClr  = ($b & 0x80) ? true : false;
		$this->m_bInterlace = ($b & 0x40) ? true : false;
		$this->m_bSorted	= ($b & 0x20) ? true : false;
		$this->m_nTableSize = 2 << ($b & 0x07);
		$hdrLen = 9;
		if($this->m_bLocalClr) {
			$this->m_colorTable = new PWGIFCOLORTABLE($this->extended);
			if(!$this->m_colorTable->load(substr((string) $lpData, $hdrLen), $this->m_nTableSize)) {
				return false;
			}
			$hdrLen += 3 * $this->m_nTableSize;
		}
		return true;
	}
	private function w2i($str) {
		return ord(substr((string) $str, 0, 1)) + (ord(substr((string) $str, 1, 1)) << 8);
	}
}
