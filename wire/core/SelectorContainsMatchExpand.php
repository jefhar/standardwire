<?php namespace ProcessWire;

use Override;
/**
 * Selector that uses standard MySQL MATCH/AGAINST behavior with implied DB-score sorting
 *
 * This selector is only useful for database $pages->find() queries. 
 *
 */
class SelectorContainsMatchExpand extends SelectorContainsMatch {
	#[Override]
 public static function getOperator() { return '**+='; }
	#[Override]
 public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAny |
			Selector::compareTypeWords | 
			Selector::compareTypeExpand | 
			Selector::compareTypeDatabase | 
			Selector::compareTypeFulltext; 
	}
	#[Override]
 public static function getLabel() { return __('Contains match expand', __FILE__); }
	#[Override]
 public static function getDescription() { return SelectorContains::buildDescription('words-match words-whole expand fulltext'); }
}
