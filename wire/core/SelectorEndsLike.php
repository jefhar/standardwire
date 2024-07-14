<?php namespace ProcessWire;

use Override;
/**
 * Selector that matches if the value exists at the end of another value (specific to SQL LIKE)
 *
 */
class SelectorEndsLike extends SelectorEnds {
	#[Override]
 public static function getOperator() { return '%$='; }
	#[Override]
 public static function getCompareType(): int {
		return
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypePhrase |
			Selector::compareTypeBoundary |
			Selector::compareTypeLike; 
	}
	#[Override]
 public static function getLabel() { return __('Ends like', __FILE__); }
	#[Override]
 public static function getDescription() { return SelectorContains::buildDescription('phrase-end like'); }
}
