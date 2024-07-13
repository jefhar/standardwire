<?php namespace ProcessWire;

/**
 * Selector that matches equality between two values
 *
 */
class SelectorEqual extends Selector {
	public static function getOperator() { return '='; }
	public static function getCompareType() { return Selector::compareTypeExact; }
	public static function getLabel() { return __('Equals', __FILE__); }
	public static function getDescription() { return __('Given value is the same as value compared to.', __FILE__); }
	protected function match($value1, $value2) { return $this->evaluate($value1 == $value2); }
}
