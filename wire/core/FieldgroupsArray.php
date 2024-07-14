<?php namespace ProcessWire;

use Override;
/**
 * ProcessWire Fieldgroups Array
 *
 * WireArray of Fieldgroup instances as used by Fieldgroups class. 
 *
 * ProcessWire 3.x, Copyright 2016 by Ryan Cramer
 * https://processwire.com
 *
 *
 */
class FieldgroupsArray extends WireArray {

	/**
	 * Per WireArray interface, this class only carries Fieldgroup instances
	 *
	 */
	#[Override]
 public function isValidItem($item) {
		return $item instanceof Fieldgroup;
	}

	/**
	 * Per WireArray interface, items are keyed by their ID
	 *
	 */
	#[Override]
 public function getItemKey($item) {
		return $item->id;
	}

	/**
	 * Per WireArray interface, keys must be integers
	 *
	 */
	#[Override]
 public function isValidKey($key) {
		return is_int($key);
	}

	/**
	 * Per WireArray interface, return a blank Fieldgroup
	 *
	 */
	#[Override]
 public function makeBlankItem() {
		return $this->wire(new Fieldgroup());
	}

}
