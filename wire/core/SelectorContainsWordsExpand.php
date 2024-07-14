<?php namespace ProcessWire;

use Override;
/**
 * Selector that matches all words with query expansion
 *
 */
class SelectorContainsWordsExpand extends SelectorContainsWords {
	#[Override]
 public static function getOperator(): string { return '~+='; }
	#[Override]
 public static function getCompareType(): int { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypeWords |
			Selector::compareTypeExpand | 
			Selector::compareTypeFulltext; 
	}
	#[Override]
 public static function getLabel() { return __('Contains all words expand', __FILE__); }
	#[Override]
 public static function getDescription(): string { return SelectorContains::buildDescription('words-all words-whole expand fulltext'); }
}
