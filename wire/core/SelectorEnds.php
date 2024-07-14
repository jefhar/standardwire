<?php namespace ProcessWire;

/**
 * Selector that matches if the value exists at the end of another value
 *
 */
class SelectorEnds extends Selector { 
	public static function getOperator() { return '$='; }
	public static function getCompareType() {
		return
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypePhrase |
			Selector::compareTypeBoundary |
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Ends with', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('phrase-end fulltext'); }
	protected function match($value1, $value2) { 
		$value2 = trim($value2); 
		$value1 = substr((string) $value1, -1 * strlen($value2));
		return $this->evaluate(strcasecmp($value1, $value2) == 0);
	}
}
