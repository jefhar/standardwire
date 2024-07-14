<?php namespace ProcessWire;

use Override;
/**
 * Selector that matches if the value exists at the beginning of another value (specific to SQL LIKE)
 *
 */
class SelectorStartsLike extends SelectorStarts {
	#[Override]
 public static function getOperator() { return '%^='; }
	#[Override]
 public static function getCompareType() {
		return
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypePhrase |
			Selector::compareTypeBoundary |
			Selector::compareTypeLike; 
	}
	#[Override]
 public static function getLabel() { return __('Starts like', __FILE__); }
	#[Override]
 public static function getDescription() { return SelectorContains::buildDescription('phrase-start like'); }
}
