<?php namespace ProcessWire;

/**
 * Selector that matches all given words in whole or in part starting with
 *
 */
class SelectorContainsWordsPartial extends Selector {
	public static function getOperator() { return '~*='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypePartial |
			Selector::compareTypeWords | 
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Contains all partial words', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('words-all words-partial words-partial-begin fulltext'); }
	protected function match($value1, $value2) {
		$hasAll = true;
		$words = $this->wire()->sanitizer->wordsArray($value2); 
		foreach($words as $word) {
			if(!preg_match('/\b' . preg_quote($word) . '/i', $value1)) {
				$hasAll = false;
				break;
			}
		}
		return $this->evaluate($hasAll);
	}
}
