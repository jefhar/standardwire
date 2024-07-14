<?php namespace ProcessWire;

use Override;
/**
 * Selector that matches one string value (phrase) that happens to be present in another string value
 *
 */
class SelectorContains extends Selector { 
	#[Override]
 public static function getOperator() { return '*='; }
	#[Override]
 public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypePartial |
			Selector::compareTypePhrase | 
			Selector::compareTypeFulltext;
	}
	#[Override]
 public static function getLabel() { return __('Contains phrase', __FILE__); }
	#[Override]
 public static function getDescription() { return SelectorContains::buildDescription('phrase fulltext'); }
	#[Override]
 protected function match($value1, $value2) { 
		$matches = stripos((string) $value1, $value2) !== false && preg_match('/\b' . preg_quote($value2) . '/i', (string) $value1); 
		return $this->evaluate($matches);
	}

	/**
	 * Build description from predefined keys for SelectorContains* classes
	 * 
	 * @param array|string $keys
	 * @return string
	 * 
	 */
	public static function buildDescription($keys) {
		$a = [];
		if(!is_array($keys)) $keys = explode(' ', $keys);
		foreach($keys as $key) {
			$a[] = match ($key) {
       'text' => __('Given text appears in value compared to.', __FILE__),
       'phrase' => __('Given phrase or word appears in value compared to.', __FILE__),
       'phrase-start' => __('Given word or phrase appears at beginning of compared value.', __FILE__),
       'phrase-end' => __('Given word or phrase appears at end of compared value.', __FILE__),
       'expand' => __('Expand to include potentially related terms and word variations.', __FILE__),
       'words-all' => __('All given words appear in compared value, in any order.', __FILE__),
       'words-any' => __('Any given words appear in compared value, in any order.', __FILE__),
       'words-match' => __('Any given words match against compared value.', __FILE__),
       'words-whole' => __('Matches whole words.', __FILE__),
       'words-partial' => __('Matches whole or partial words.', __FILE__),
       'words-partial-any' => __('Partial matches anywhere within words.', __FILE__),
       'words-partial-begin' => __('Partial matches from beginning of words.', __FILE__),
       'words-partial-last' => __('Partial matches last word in given value.', __FILE__),
       'fulltext' => __('Uses “fulltext” index.', __FILE__),
       'like' => __('Matches using “like”.', __FILE__),
       'like-words' => __('Matches without regard to word boundaries (using “like”).', __FILE__),
       default => "UNKNOWN:$key",
   };
		}
		return implode(' ', $a);
	}
}
