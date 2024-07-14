<?php namespace ProcessWire;

/**
 * Selector that has any of the given whole words (only 1 needs to match)
 *
 */
class SelectorContainsAnyWords extends Selector {
	public static function getOperator() { return '~|='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAny | 
			Selector::compareTypeWords |
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Contains any words', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('words-any words-whole fulltext'); }
	protected function match($value1, $value2) {
		$hasAny = false;
		$words = $this->wire()->sanitizer->wordsArray($value2);
		foreach($words as $word) {
			if(stripos($value1, (string) $word) !== false) {
				if(preg_match('!\b' . preg_quote($word) . '\b!i', $value1)) {
					$hasAny = true;
					break;
				}
			}
		}
		return $this->evaluate($hasAny);
	}
}
