<?php namespace ProcessWire;

/**
 * Selector that matches one value greater than or equal to another
 *
 */
class SelectorGreaterThanEqual extends Selector { 
	public static function getOperator() { return '>='; }
	public static function getCompareType() { return Selector::compareTypeSort; }
	public static function getLabel() { return __('Greater than or equal', __FILE__); }
	public static function getDescription() { return __('Compared value is greater than or equal to given value.', __FILE__); }
	protected function match($value1, $value2) { return $this->evaluate($value1 >= $value2); }
}
