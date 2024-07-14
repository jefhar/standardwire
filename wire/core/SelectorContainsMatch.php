<?php namespace ProcessWire;

use Override;
/**
 * Selector that uses standard MySQL MATCH/AGAINST behavior with implied DB-score sorting
 *
 * This selector is only useful for database $pages->find() queries. 
 *
 */
class SelectorContainsMatch extends SelectorContainsAnyWords {
	#[Override]
 public static function getOperator(): string { return '**='; }
	#[Override]
 public static function getCompareType(): int { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAny | 
			Selector::compareTypeWords | 
			Selector::compareTypeDatabase | 
			Selector::compareTypeFulltext; 
	}
	#[Override]
 public static function getLabel() { return __('Contains match', __FILE__); }
	#[Override]
 public static function getDescription(): string { return SelectorContains::buildDescription('words-match words-whole fulltext'); }
}
