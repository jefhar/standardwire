<?php namespace ProcessWire;

/**
 * Selector that uses standard MySQL MATCH/AGAINST behavior with implied DB-score sorting
 *
 * This selector is only useful for database $pages->find() queries. 
 *
 */
class SelectorContainsMatchExpand extends SelectorContainsMatch {
	public static function getOperator() { return '**+='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAny |
			Selector::compareTypeWords | 
			Selector::compareTypeExpand | 
			Selector::compareTypeDatabase | 
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Contains match expand', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('words-match words-whole expand fulltext'); }
}
