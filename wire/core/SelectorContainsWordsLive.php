<?php namespace ProcessWire;

use Override;
/**
 * Selector that matches entire words except for last word, which must start with
 * 
 * Useful in matching "live" search results where someone is typing and last word may be partial.
 *
 */
class SelectorContainsWordsLive extends Selector {
	#[Override]
 public static function getOperator() { return '~~='; }
	#[Override]
 public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAll | 
			Selector::compareTypeWords |
			Selector::compareTypePartial |
			Selector::compareTypeFulltext; 
	}
	#[Override]
 public static function getLabel() { return __('Contains all words live', __FILE__); }
	#[Override]
 public static function getDescription() { return SelectorContains::buildDescription('words-all words-partial-last fulltext'); }
	#[Override]
 protected function match($value1, $value2) {
		$hasAll = true;
		$words = $this->wire()->sanitizer->wordsArray($value2); 
		$lastWord = array_pop($words);
		foreach($words as $word) {
			if(!preg_match('/\b' . preg_quote((string) $word) . '\b/i', (string) $value1)) {
				// full-word match
				$hasAll = false;
				break;
			}
		}
		// last word only needs to match beginning of word
		$hasAll = $hasAll && preg_match('\b' . preg_quote((string) $lastWord) . '/i', (string) $value1);
		return $this->evaluate($hasAll);
	}
}
