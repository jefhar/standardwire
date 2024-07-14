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
class PWGIFFILEHEADER {
	public $m_lpVer;
	public $m_nWidth;
	public $m_nHeight;
	public $m_bGlobalClr;
	public $m_nColorRes;
	public $m_bSorted;
	public $m_nTableSize;
	public $m_nBgColor;
	public $m_nPixelRatio;
	public $m_colorTable;
	public $m_bAnimated;
	public function __construct(protected $extended = false) {
		unSet($this->m_lpVer);
		unSet($this->m_nWidth);
		unSet($this->m_nHeight);
		unSet($this->m_bGlobalClr);
		unSet($this->m_nColorRes);
		unSet($this->m_bSorted);
		unSet($this->m_nTableSize);
		unSet($this->m_nBgColor);
		unSet($this->m_nPixelRatio);
		unSet($this->m_colorTable);
		unSet($this->m_bAnimated);
	}
	public function load($lpData, &$hdrLen) {
		$hdrLen = 0;
		$this->m_lpVer = substr((string) $lpData, 0, 6);
		if(($this->m_lpVer <> 'GIF87a') && ($this->m_lpVer <> 'GIF89a')) {
			return false;
		}
		// @Horst: store if we have more then one animation frames
		$this->m_bAnimated = 1 < preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', (string) $lpData);
		$this->m_nWidth  = $this->w2i(substr((string) $lpData, 6, 2));
		$this->m_nHeight = $this->w2i(substr((string) $lpData, 8, 2));
		if(!$this->m_nWidth || !$this->m_nHeight) {
			return false;
		}
		$b = ord(substr((string) $lpData, 10, 1));
		$this->m_bGlobalClr  = ($b & 0x80) ? true : false;
		$this->m_nColorRes   = ($b & 0x70) >> 4;
		$this->m_bSorted	 = ($b & 0x08) ? true : false;
		$this->m_nTableSize  = 2 << ($b & 0x07);
		$this->m_nBgColor	= ord(substr((string) $lpData, 11, 1));
		$this->m_nPixelRatio = ord(substr((string) $lpData, 12, 1));
		$hdrLen = 13;
		if($this->m_bGlobalClr) {
			$this->m_colorTable = new PWGIFCOLORTABLE($this->extended);
			$tmp1 = $this->m_nTableSize;
			if(!$this->m_colorTable->load(substr((string) $lpData, $hdrLen), $tmp1)) {
				return false;
			}
			$this->m_nTableSize = $tmp1;
			$hdrLen += 3 * $this->m_nTableSize;
		}
		return true;
	}
	private function w2i($str) {
		return ord(substr((string) $str, 0, 1)) + (ord(substr((string) $str, 1, 1)) << 8);
	}
}
