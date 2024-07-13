<?php namespace ProcessWire;

/**
 * Selector that matches all words with query expansion
 *
 */
class SelectorContainsWordsExpand extends SelectorContainsWords {
	public static function getOperator() { return '~+='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypeWords |
			Selector::compareTypeExpand | 
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Contains all words expand', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('words-all words-whole expand fulltext'); }
}
