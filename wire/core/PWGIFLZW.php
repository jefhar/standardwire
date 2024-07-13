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
class PWGIFLZW {
	public $MAX_LZW_BITS;
	public $Fresh, $CodeSize, $SetCodeSize, $MaxCode, $MaxCodeSize, $FirstCode, $OldCode;
	public $ClearCode, $EndCode, $Next, $Vals, $Stack, $sp, $Buf, $CurBit, $LastBit, $Done, $LastByte;
	public function __construct() {
		$this->MAX_LZW_BITS = 12;
		unSet($this->Next);
		unSet($this->Vals);
		unSet($this->Stack);
		unSet($this->Buf);
		$this->Next  = range(0, (1 << $this->MAX_LZW_BITS)	   - 1);
		$this->Vals  = range(0, (1 << $this->MAX_LZW_BITS)	   - 1);
		$this->Stack = range(0, (1 << ($this->MAX_LZW_BITS + 1)) - 1);
		$this->Buf   = range(0, 279);
	}
	function deCompress($data, &$datLen) {
		$stLen  = strlen($data);
		$datLen = 0;
		$ret	= '';
		// INITIALIZATION
		$this->LZWCommand($data, true);
		while(($iIndex = $this->LZWCommand($data, false)) >= 0) {
			$ret .= chr($iIndex);
		}
		$datLen = $stLen - strlen($data);
		if($iIndex != -2) {
			return false;
		}
		return $ret;
	}
	function LZWCommand(&$data, $bInit) {
		if($bInit) {
			$this->SetCodeSize = ord($data[0]);
			$data = substr($data, 1);
			$this->CodeSize	= $this->SetCodeSize + 1;
			$this->ClearCode   = 1 << $this->SetCodeSize;
			$this->EndCode	 = $this->ClearCode + 1;
			$this->MaxCode	 = $this->ClearCode + 2;
			$this->MaxCodeSize = $this->ClearCode << 1;
			$this->GetCode($data, $bInit);
			$this->Fresh = 1;
			for($i = 0; $i < $this->ClearCode; $i++) {
				$this->Next[$i] = 0;
				$this->Vals[$i] = $i;
			}
			for(; $i < (1 << $this->MAX_LZW_BITS); $i++) {
				$this->Next[$i] = 0;
				$this->Vals[$i] = 0;
			}
			$this->sp = 0;
			return 1;
		}
		if($this->Fresh) {
			$this->Fresh = 0;
			do {
				$this->FirstCode = $this->GetCode($data, $bInit);
				$this->OldCode   = $this->FirstCode;
			}
			while($this->FirstCode == $this->ClearCode);
			return $this->FirstCode;
		}
		if($this->sp > 0) {
			$this->sp--;
			return $this->Stack[$this->sp];
		}
		while(($Code = $this->GetCode($data, $bInit)) >= 0) {
			if($Code == $this->ClearCode) {
				for($i = 0; $i < $this->ClearCode; $i++) {
					$this->Next[$i] = 0;
					$this->Vals[$i] = $i;
				}
				for(; $i < (1 << $this->MAX_LZW_BITS); $i++) {
					$this->Next[$i] = 0;
					$this->Vals[$i] = 0;
				}
				$this->CodeSize	= $this->SetCodeSize + 1;
				$this->MaxCodeSize = $this->ClearCode << 1;
				$this->MaxCode	 = $this->ClearCode + 2;
				$this->sp		  = 0;
				$this->FirstCode   = $this->GetCode($data, $bInit);
				$this->OldCode	 = $this->FirstCode;
				return $this->FirstCode;
			}
			if($Code == $this->EndCode) {
				return -2;
			}
			$InCode = $Code;
			if($Code >= $this->MaxCode) {
				$this->Stack[$this->sp] = $this->FirstCode;
				$this->sp++;
				$Code = $this->OldCode;
			}
			while($Code >= $this->ClearCode) {
				$this->Stack[$this->sp] = $this->Vals[$Code];
				$this->sp++;
				if($Code == $this->Next[$Code]) // Circular table entry, big GIF Error!
					return -1;
				$Code = $this->Next[$Code];
			}
			$this->FirstCode = $this->Vals[$Code];
			$this->Stack[$this->sp] = $this->FirstCode;
			$this->sp++;
			if(($Code = $this->MaxCode) < (1 << $this->MAX_LZW_BITS)) {
				$this->Next[$Code] = $this->OldCode;
				$this->Vals[$Code] = $this->FirstCode;
				$this->MaxCode++;
				if(($this->MaxCode >= $this->MaxCodeSize) && ($this->MaxCodeSize < (1 << $this->MAX_LZW_BITS))) {
					$this->MaxCodeSize *= 2;
					$this->CodeSize++;
				}
			}
			$this->OldCode = $InCode;
			if($this->sp > 0) {
				$this->sp--;
				return $this->Stack[$this->sp];
			}
		}
		return $Code;
	}
	function GetCode(&$data, $bInit) {
		if($bInit) {
			$this->CurBit   = 0;
			$this->LastBit  = 0;
			$this->Done	 = 0;
			$this->LastByte = 2;
			return 1;
		}
		if(($this->CurBit + $this->CodeSize) >= $this->LastBit) {
			if($this->Done) {
				if($this->CurBit >= $this->LastBit) {
					// Ran off the end of my bits
					return 0;
				}
				return -1;
			}
			$this->Buf[0] = $this->Buf[$this->LastByte - 2];
			$this->Buf[1] = $this->Buf[$this->LastByte - 1];
			$Count = ord($data[0]);
			$data  = substr($data, 1);
			if($Count) {
				for($i = 0; $i < $Count; $i++) {
					$this->Buf[2 + $i] = ord($data[$i]);
				}
				$data = substr($data, $Count);
			} else {
				$this->Done = 1;
			}
			$this->LastByte = 2 + $Count;
			$this->CurBit   = ($this->CurBit - $this->LastBit) + 16;
			$this->LastBit  = (2 + $Count) << 3;
		}
		$iRet = 0;
		for($i = $this->CurBit, $j = 0; $j < $this->CodeSize; $i++, $j++) {
			$iRet |= (($this->Buf[intval($i / 8)] & (1 << ($i % 8))) != 0) << $j;
		}
		$this->CurBit += $this->CodeSize;
		return $iRet;
	}
}
