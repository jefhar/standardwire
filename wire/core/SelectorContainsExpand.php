<?php namespace ProcessWire;

/**
 * Same as SelectorContains but query expansion when used for database searching
 *
 */
class SelectorContainsExpand extends SelectorContains {
	public static function getOperator() { return '*+='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypePartial |
			Selector::compareTypePhrase | 
			Selector::compareTypeExpand | 
			Selector::compareTypeDatabase | 
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Contains phrase expand', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('phrase expand fulltext'); }
}
