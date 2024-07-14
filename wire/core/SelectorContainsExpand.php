<?php namespace ProcessWire;

use Override;
/**
 * Same as SelectorContains but query expansion when used for database searching
 *
 */
class SelectorContainsExpand extends SelectorContains {
	#[Override]
 public static function getOperator() { return '*+='; }
	#[Override]
 public static function getCompareType(): int { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypePartial |
			Selector::compareTypePhrase | 
			Selector::compareTypeExpand | 
			Selector::compareTypeDatabase | 
			Selector::compareTypeFulltext; 
	}
	#[Override]
 public static function getLabel() { return __('Contains phrase expand', __FILE__); }
	#[Override]
 public static function getDescription() { return SelectorContains::buildDescription('phrase expand fulltext'); }
}
