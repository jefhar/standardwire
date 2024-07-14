<?php namespace ProcessWire;

/**
 * Selector for advanced text searches that interprets specific search commands
 * 
 * - `foo` Optional word has no prefix.
 * - `+foo` Required word has a "+" prefix.
 * - `+foo*` Required words starting with "foo" (i.e. "fool", "foobar", etc.) has "+" prefix and "*" wildcard suffix.
 * - `-bar` Disallowed word has a "-" prefix.
 * - `-bar*` Disallowed words starting with "bar" (i.e. "barn", "barbell", etc.) has "-" prefix and "*" wildcard suffix.
 * - `"foo bar baz"` Optional phrase surrounded in quotes.
 * - `+"foo bar baz"` Required phrase with "+" prefix followed by double-quoted value. 
 * - `-"foo bar baz"` Disallowed phrase with "-" prefix followed by double-quoted value. 
 * 
 * Note that to designate a phrase, it must be in "double quotes" (not 'single quotes'). 
 *
 */
class SelectorContainsAdvanced extends SelectorContains {
	public static function getOperator() { return '#='; }
	public static function getCompareType() { 
		return 
			Selector::compareTypeFind |
			Selector::compareTypeAny | 
			Selector::compareTypeWords |
			Selector::compareTypePhrase |
			Selector::compareTypeCommand | 
			Selector::compareTypeFulltext; 
	}
	public static function getLabel() { return __('Advanced text search', __FILE__); }
	public static function getDescription() {
		return 
			__('Match values with commands: +Word MUST appear, -Word MUST NOT appear, and unprefixed Word may appear.', __FILE__) . ' ' . 
			__('Add asterisk for partial match: Bar* or +Bar* matches bar, barn, barge; while -Bar* prevents matching them.') . ' ' . 
			__('Use quotes to match phrases: +(Must Match), -(Must Not Match), or (May Match).'); 
	}

	/**
	 * Return array of advanced search commands from given value
	 * 
	 * @param string $value
	 * @return array
	 * 
	 */
	public function valueToCommands($value) {
		$commands = [];
		$hasQuotes = strrpos($value, '"') || strrpos($value, '”') || strrpos($value, ')') || strrpos($value, '}');
		$substr = function_exists('\\mb_substr') ? '\\mb_substr' : '\\substr';
		$re = '/[-+]?("[^"]+"|\([^)]+\))\*?/';
		if($hasQuotes && preg_match_all($re, $value, $matches)) {
			// find all quoted phrases
			foreach($matches[0] as $key => $fullMatch) {
				$type = substr($fullMatch, 0, 1);
				$partial = str_ends_with($fullMatch, '*');
				if($type !== '+' && $type !== '-') $type = '';
				$phrase = $matches[1][$key];
				$phrase = trim($phrase, $substr($phrase, 0, 1) . $substr($phrase, -1)); // remove quotes
				$phrase = str_replace('+', '', trim($phrase, '-')); 
				if(str_contains($phrase, '-')) $phrase = preg_replace('/([^\w\d])-(.)/', '$1 $2', $phrase); 
				$value = str_replace($fullMatch, ' ', $value);
				while(str_contains($phrase, '  ')) $phrase = str_replace('  ', ' ', $phrase);
				if(!strlen($phrase)) continue;
				$phrase = str_replace('"', '', $phrase);
				$query = $type . '"' . $phrase . '"' . ($partial ? '*' : ''); 
				$a = ['type' => $type, 'value' => $phrase, 'query' => $query, 'partial' => $partial, 'phrase' => true];
				$commands[] = $a;
			}
		}
		$words = $this->wire()->sanitizer->wordsArray($value, ['keepChars' => ['+', '-', '*']]);
		foreach($words as $word) {
			$type = substr($word, 0, 1);
			$partial = str_ends_with($word, '*');
			if($type !== '+' && $type !== '-') $type = '';
			$word = trim($word, '+-*');
			$query = $type . $word . ($partial ? '*' : ''); 
			$a = ['type' => $type, 'value' => $word, 'query' => $query, 'partial' => $partial, 'phrase' => false];
			$commands[] = $a;
		}
		return $commands;
	}
	
	protected function match($value1, $value2) {
		$fail = false;
		$numMatch = 0;
		$numOptional = 0; 
		$commands = $this->valueToCommands($value2);
		foreach($commands as $command) {
			$re = '/\b' . preg_quote($command['value']) . ($command['partial'] ? '' : '\b') . '/i';
			$match = preg_match($re, $value1);
			if($command['type'] === '+') {
				// value must be present (+)
				if(!$match) $fail = true;
				if(!$fail) $numMatch++;
			} else if($command['type'] === '-') {
				// value must not be present (-)
				if($match) $fail = true;
				if(!$fail) $numMatch++;
			} else { 
				// value may be present (blank type)
				if($match) $numMatch++;
				$numOptional++;
			}
			if($fail) break;
		}
		if(!$fail && $numOptional && !$numMatch) $fail = true;
		return $this->evaluate(!$fail);
	}
}
