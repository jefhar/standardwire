<?php namespace ProcessWire;

use Override;
/**
 * Selector that matches two values that aren't equal
 *
 */
class SelectorNotEqual extends Selector {
	#[Override]
 public static function getOperator() { return '!='; }
	#[Override]
 public static function getCompareType() { return Selector::compareTypeExact; }
	#[Override]
 public static function getLabel() { return __('Not equals', __FILE__); }
	#[Override]
 public static function getDescription() { return __('Given value is not the same as value compared to.', __FILE__); }
	#[Override]
 protected function match($value1, $value2) { return $this->evaluate($value1 != $value2); }
}
