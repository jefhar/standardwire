<?php namespace ProcessWire;

/**
 * Selector that matches any words with query expansion
 *
 */
class SelectorContainsAnyWordsExpand extends SelectorContainsAnyWords {
	#[\Override]
 public static function getOperator() { return '~|+='; }
	#[\Override]
 public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAny |
			Selector::compareTypeWords | 
			Selector::compareTypeExpand | 
			Selector::compareTypeFulltext; 
	}
	#[\Override]
 public static function getLabel() { return __('Contains any words expand', __FILE__); }
	#[\Override]
 public static function getDescription() { return SelectorContains::buildDescription('words-any expand fulltext'); }
	#[\Override]
 protected function match($value1, $value2) {
		$hasAny = false;
		$textTools = $this->wire()->sanitizer->getTextTools();
		$words = $this->wire()->sanitizer->wordsArray($value2);
		foreach($words as $word) {
			if(stripos((string) $value1, (string) $word) !== false && preg_match('/\b' . preg_quote((string) $word) . '\b/i', (string) $value1)) {
				$hasAny = true;
				break;
			}
			$alternates = $textTools->getWordAlternates($word); 
			foreach($alternates as $alternate) {
				if(stripos((string) $value1, (string) $alternate) && preg_match('/\b' . preg_quote((string) $alternate) . '\b/i', (string) $value1)) {
					$hasAny = true;
					break;
				}
			}
			if($hasAny) break;
		}
		return $this->evaluate($hasAny);
	}
}
