<?php namespace ProcessWire;

/**
 * Selector that matches all words with query expansion
 *
 */
class SelectorContainsWordsExpand extends SelectorContainsWords {
	#[\Override]
 public static function getOperator() { return '~+='; }
	#[\Override]
 public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypeWords |
			Selector::compareTypeExpand | 
			Selector::compareTypeFulltext; 
	}
	#[\Override]
 public static function getLabel() { return __('Contains all words expand', __FILE__); }
	#[\Override]
 public static function getDescription() { return SelectorContains::buildDescription('words-all words-whole expand fulltext'); }
}
