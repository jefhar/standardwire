<?php namespace ProcessWire;

/**
 * Selector that matches one value less than or equal to another
 *
 */
class SelectorLessThanEqual extends Selector { 
	#[\Override]
 public static function getOperator() { return '<='; }
	#[\Override]
 public static function getCompareType() { return Selector::compareTypeSort; }
	#[\Override]
 public static function getLabel() { return __('Less than or equal', __FILE__); }
	#[\Override]
 public static function getDescription() { return __('Compared value is less than or equal to given value.', __FILE__); }
	#[\Override]
 protected function match($value1, $value2) { return $this->evaluate($value1 <= $value2); }
}
