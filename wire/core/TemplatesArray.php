<?php namespace ProcessWire;

use Override;
/**
 * ProcessWire Templates
 *
 * WireArray of Template instances as used by Templates class.
 *
 * ProcessWire 3.x, Copyright 2016 by Ryan Cramer
 * https://processwire.com
 *
 */
class TemplatesArray extends WireArray {

	#[Override]
 public function isValidItem($item) {
		return $item instanceof Template;
	}

	#[Override]
 public function isValidKey($key) {
		return is_int($key) || ctype_digit($key);
	}

	#[Override]
 public function getItemKey($item) {
		return $item->id;
	}

	#[Override]
 public function makeBlankItem() {
		return $this->wire(new Template());
	}

}
