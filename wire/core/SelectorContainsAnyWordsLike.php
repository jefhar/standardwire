<?php namespace ProcessWire;

/**
 * Selector that has any words like any of those given (only 1 needs to match)
 *
 */
class SelectorContainsAnyWordsLike extends Selector {
	#[\Override]
 public static function getOperator() { return '~|%='; }
	#[\Override]
 public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAny |
			Selector::compareTypePartial |
			Selector::compareTypeWords | 
			Selector::compareTypeLike;
	}
	#[\Override]
 public static function getLabel() { return __('Contains any words like', __FILE__); }
	#[\Override]
 public static function getDescription() { return SelectorContains::buildDescription('words-any words-partial words-partial-any like'); }
	#[\Override]
 protected function match($value1, $value2) {
		$hasAny = false;
		$words = $this->wire()->sanitizer->wordsArray($value2);
		foreach($words as $word) {
			if(stripos((string) $value1, (string) $word) !== false) {
				$hasAny = true;
				break;
			}
		}
		return $this->evaluate($hasAny);
	}
}
