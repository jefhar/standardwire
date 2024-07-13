<?php namespace ProcessWire;

/**
 * Selector that matches if the value exists at the beginning of another value (specific to SQL LIKE)
 *
 */
class SelectorStartsLike extends SelectorStarts {
	public static function getOperator() { return '%^='; }
	public static function getCompareType() {
		return
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypePhrase |
			Selector::compareTypeBoundary |
			Selector::compareTypeLike; 
	}
	public static function getLabel() { return __('Starts like', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('phrase-start like'); }
}
