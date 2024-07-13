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
class PWGIF {
	public $m_gfh;
	public $m_lpData;
	public $m_img;
	public $m_bLoaded;
	// @Horst: added param $extended
	//  - true = it also loads and parse Bitmapdata
	//  - false = it only loads Headerdata
	public function __construct($extended = false) {
		$this->m_gfh	 = new PWGIFFILEHEADER($extended);
		$this->m_img	 = new PWGIFIMAGE($extended);
		$this->m_lpData  = '';
		$this->m_bLoaded = false;
	}
	public function loadFile($lpszFileName, $iIndex) {
		if($iIndex < 0) {
			return false;
		}
		// READ FILE
		if(!($fh = @fopen($lpszFileName, 'rb'))) {
			return false;
		}
		$this->m_lpData = @fRead($fh, @fileSize($lpszFileName));
		fclose($fh);
		// GET FILE HEADER
		$len = 0;
		if(!$this->m_gfh->load($this->m_lpData, $len)) {
			return false;
		}
		$this->m_lpData = substr($this->m_lpData, $len);
		do {
			$imgLen = 0;
			if(!$this->m_img->load($this->m_lpData, $imgLen)) {
				return false;
			}
			$this->m_lpData = substr($this->m_lpData, $imgLen);
		}
		while($iIndex-- > 0);
		$this->m_bLoaded = true;
		return true;
	}
}
