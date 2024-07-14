<?php namespace ProcessWire;

use Override;
/**
 * Selector that matches a bitwise AND '&'
 *
 */
class SelectorBitwiseAnd extends Selector { 
	#[Override]
 public static function getOperator(): string { return '&'; }
	#[Override]
 public static function getCompareType(): int { return Selector::compareTypeBitwise; }
	#[Override]
 public static function getLabel() { return __('Bitwise AND', __FILE__); }
	#[Override]
 public static function getDescription() {
		return __('Given integer value matches bitwise AND with compared integer value.', __FILE__);
	}
	#[Override]
 protected function match($value1, $value2) { return $this->evaluate(((int) $value1) & ((int) $value2)); }
}
