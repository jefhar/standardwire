<?php namespace ProcessWire;

use Override;
/**
 * Selector that matches if the value exists at the end of another value
 *
 */
class SelectorEnds extends Selector { 
	#[Override]
 public static function getOperator(): string { return '$='; }
	#[Override]
 public static function getCompareType(): int {
		return
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypePhrase |
			Selector::compareTypeBoundary |
			Selector::compareTypeFulltext; 
	}
	#[Override]
 public static function getLabel() { return __('Ends with', __FILE__); }
	#[Override]
 public static function getDescription(): string { return SelectorContains::buildDescription('phrase-end fulltext'); }
	#[Override]
 protected function match($value1, $value2) { 
		$value2 = trim($value2); 
		$value1 = substr((string) $value1, -1 * strlen($value2));
		return $this->evaluate(strcasecmp($value1, $value2) == 0);
	}
}
