<?php namespace ProcessWire;

/**
 * Selector that matches partial words at either beginning or ending
 *
 */
class SelectorContainsWordsLike extends Selector {
	#[\Override]
 public static function getOperator() { return '~%='; }
	#[\Override]
 public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAll |
			Selector::compareTypePartial |
			Selector::compareTypeWords |
			Selector::compareTypeLike;
	}
	#[\Override]
 public static function getLabel() { return __('Contains all words like', __FILE__); }
	#[\Override]
 public static function getDescription() { return SelectorContains::buildDescription('words-all words-partial words-partial-any like'); }
	#[\Override]
 protected function match($value1, $value2) {
		$hasAll = true;
		$words = $this->wire()->sanitizer->wordsArray($value2);
		foreach($words as $word) {
			if(stripos((string) $value1, (string) $word) === false) {
				$hasAll = false;
				break;
			}
		}
		return $this->evaluate($hasAll);
	}
}
