<?php namespace ProcessWire;

/**
 * Selector that matches equality between two values
 *
 */
class SelectorEqual extends Selector {
	#[\Override]
 public static function getOperator() { return '='; }
	#[\Override]
 public static function getCompareType() { return Selector::compareTypeExact; }
	#[\Override]
 public static function getLabel() { return __('Equals', __FILE__); }
	#[\Override]
 public static function getDescription() { return __('Given value is the same as value compared to.', __FILE__); }
	#[\Override]
 protected function match($value1, $value2) { return $this->evaluate($value1 == $value2); }
}
