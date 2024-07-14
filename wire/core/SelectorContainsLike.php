<?php namespace ProcessWire;

/**
 * Same as SelectorContains but serves as operator placeholder for SQL LIKE operations
 *
 */
class SelectorContainsLike extends SelectorContains {
	public static function getOperator() { return '%='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypePartial |
			Selector::compareTypePhrase |
			Selector::compareTypeLike;
	}
	public static function getLabel() { return __('Contains text like', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('phrase like'); }
	protected function match($value1, $value2) { return $this->evaluate(stripos((string) $value1, $value2) !== false); }
}
