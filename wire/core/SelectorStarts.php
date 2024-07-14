<?php namespace ProcessWire;

use Override;
/**
 * Selector that matches if the value exists at the beginning of another value
 *
 */
class SelectorStarts extends Selector { 
	#[Override]
 public static function getOperator(): string { return '^='; }
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
 public static function getLabel() { return __('Starts with', __FILE__); }
	#[Override]
 public static function getDescription(): string { return SelectorContains::buildDescription('phrase-start fulltext'); }
	#[Override]
 protected function match($value1, $value2) { 
		return $this->evaluate(stripos(trim((string) $value1), $value2) === 0); 
	}
}
