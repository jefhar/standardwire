<?php namespace ProcessWire;

/**
 * Selector that matches if the value exists at the beginning of another value
 *
 */
class SelectorStarts extends Selector { 
	public static function getOperator() { return '^='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypePhrase |
			Selector::compareTypeBoundary | 
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Starts with', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('phrase-start fulltext'); }
	protected function match($value1, $value2) { 
		return $this->evaluate(stripos(trim($value1), $value2) === 0); 
	}
}
