<?php namespace ProcessWire;

/**
 * Selector that matches two values that aren't equal
 *
 */
class SelectorNotEqual extends Selector {
	public static function getOperator() { return '!='; }
	public static function getCompareType() { return Selector::compareTypeExact; }
	public static function getLabel() { return __('Not equals', __FILE__); }
	public static function getDescription() { return __('Given value is not the same as value compared to.', __FILE__); }
	protected function match($value1, $value2) { return $this->evaluate($value1 != $value2); }
}
