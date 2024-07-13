<?php namespace ProcessWire;

/**
 * Selector that matches a bitwise AND '&'
 *
 */
class SelectorBitwiseAnd extends Selector { 
	public static function getOperator() { return '&'; }
	public static function getCompareType() { return Selector::compareTypeBitwise; }
	public static function getLabel() { return __('Bitwise AND', __FILE__); }
	public static function getDescription() {
		return __('Given integer value matches bitwise AND with compared integer value.', __FILE__);
	}
	protected function match($value1, $value2) { return $this->evaluate(((int) $value1) & ((int) $value2)); }
}
