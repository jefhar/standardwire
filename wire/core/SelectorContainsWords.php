<?php namespace ProcessWire;

/**
 * Selector that matches one string value that happens to have all of its words present in another string value (regardless of individual word location)
 *
 */
class SelectorContainsWords extends Selector { 
	public static function getOperator() { return '~='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypeWords | 
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Contains all words', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('words-all words-whole fulltext'); }
	protected function match($value1, $value2) { 
		$hasAll = true; 
		$words = $this->wire()->sanitizer->wordsArray($value2); 
		foreach($words as $word) if(!preg_match('/\b' . preg_quote((string) $word) . '\b/i', (string) $value1)) {
			$hasAll = false;
			break;
		}
		return $this->evaluate($hasAll); 
	}
}
