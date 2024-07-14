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
class PWGIFCOLORTABLE {
	public $m_nColors;
	public $m_arColors;
	public function __construct(protected $extended = false) {
		unSet($this->m_nColors);
		unSet($this->m_arColors);
	}
	public function load($lpData, $num): bool {
		$this->m_nColors  = 0;
		$this->m_arColors = [];
		for($i = 0; $i < $num; $i++) {
			$rgb = substr((string) $lpData, $i * 3, 3);
			if(strlen($rgb) < 3) {
				return false;
			}
			if($this->extended) {
				$this->m_arColors[] = (ord($rgb[2]) << 16) + (ord($rgb[1]) << 8) + ord($rgb[0]);
			}
			$this->m_nColors++;
		}
		return true;
	}
	public function toString(): string {
		$ret = '';
		for($i = 0; $i < $this->m_nColors; $i++) {
			$ret .=
				chr(($this->m_arColors[$i] & 0x000000FF))	   . // R
				chr(($this->m_arColors[$i] & 0x0000FF00) >>  8) . // G
				chr(($this->m_arColors[$i] & 0x00FF0000) >> 16);  // B
		}
		return $ret;
	}
	public function toRGBQuad(): string {
		$ret = '';
		for($i = 0; $i < $this->m_nColors; $i++) {
			$ret .=
				chr(($this->m_arColors[$i] & 0x00FF0000) >> 16) . // B
				chr(($this->m_arColors[$i] & 0x0000FF00) >>  8) . // G
				chr(($this->m_arColors[$i] & 0x000000FF))	   . // R
				"\x00";
		}
		return $ret;
	}
}
