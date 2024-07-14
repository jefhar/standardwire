<?php namespace ProcessWire;

/**
 * Selector that has any of the given partial words (starting with, only 1 needs to match)
 *
 */
class SelectorContainsAnyWordsPartial extends Selector {
	public static function getOperator() { return '~|*='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAny |
			Selector::compareTypePartial | 
			Selector::compareTypeWords |
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Contains any partial words', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('words-any words-partial words-partial-begin fulltext'); }
	protected function match($value1, $value2) {
		$hasAny = false;
		$words = $this->wire()->sanitizer->wordsArray($value2);
		foreach($words as $word) {
			if(stripos($value1, (string) $word) !== false) {
				if(preg_match('!\b' . preg_quote($word) . '!i', $value1)) {
					$hasAny = true;
					break;
				}
			}
		}
		return $this->evaluate($hasAny);
	}
}
