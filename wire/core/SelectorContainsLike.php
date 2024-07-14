<?php namespace ProcessWire;

use Override;
/**
 * Same as SelectorContains but serves as operator placeholder for SQL LIKE operations
 *
 */
class SelectorContainsLike extends SelectorContains {
	#[Override]
 public static function getOperator() { return '%='; }
	#[Override]
 public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypePartial |
			Selector::compareTypePhrase |
			Selector::compareTypeLike;
	}
	#[Override]
 public static function getLabel() { return __('Contains text like', __FILE__); }
	#[Override]
 public static function getDescription() { return SelectorContains::buildDescription('phrase like'); }
	#[Override]
 protected function match($value1, $value2) { return $this->evaluate(stripos((string) $value1, $value2) !== false); }
}
