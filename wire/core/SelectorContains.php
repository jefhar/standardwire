<?php namespace ProcessWire;

/**
 * Selector that matches one string value (phrase) that happens to be present in another string value
 *
 */
class SelectorContains extends Selector { 
	public static function getOperator() { return '*='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypePartial |
			Selector::compareTypePhrase | 
			Selector::compareTypeFulltext;
	}
	public static function getLabel() { return __('Contains phrase', __FILE__); }
	public static function getDescription() { return SelectorContains::buildDescription('phrase fulltext'); }
	protected function match($value1, $value2) { 
		$matches = stripos($value1, $value2) !== false && preg_match('/\b' . preg_quote($value2) . '/i', $value1); 
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
			switch($key) {
				case 'text': $a[] = __('Given text appears in value compared to.', __FILE__); break;
				case 'phrase': $a[] = __('Given phrase or word appears in value compared to.', __FILE__); break;
				case 'phrase-start': $a[] = __('Given word or phrase appears at beginning of compared value.', __FILE__); break;
				case 'phrase-end': $a[] = __('Given word or phrase appears at end of compared value.', __FILE__); break;
				case 'expand': $a[] = __('Expand to include potentially related terms and word variations.', __FILE__); break;
				case 'words-all': $a[] = __('All given words appear in compared value, in any order.', __FILE__); break;
				case 'words-any': $a[] = __('Any given words appear in compared value, in any order.', __FILE__); break;
				case 'words-match': $a[] = __('Any given words match against compared value.', __FILE__); break;
				case 'words-whole': $a[] = __('Matches whole words.', __FILE__); break;
				case 'words-partial': $a[] = __('Matches whole or partial words.', __FILE__); break;
				case 'words-partial-any': $a[] = __('Partial matches anywhere within words.', __FILE__); break;
				case 'words-partial-begin': $a[] = __('Partial matches from beginning of words.', __FILE__); break;
				case 'words-partial-last': $a[] = __('Partial matches last word in given value.', __FILE__); break;
				case 'fulltext': $a[] = __('Uses “fulltext” index.', __FILE__); break;
				case 'like': $a[] = __('Matches using “like”.', __FILE__); break;
				case 'like-words': $a[] = __('Matches without regard to word boundaries (using “like”).', __FILE__); break;
				default: $a[] = "UNKNOWN:$key";
			}
		}
		return implode(' ', $a);
	}
}
