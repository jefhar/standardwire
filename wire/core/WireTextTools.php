<?php namespace ProcessWire;

/**
 * ProcessWire Text Tools
 *
 * #pw-summary Specific text and markup tools for ProcessWire $sanitizer and elsewhere.
 *
 * ProcessWire 3.x, Copyright 2020 by Ryan Cramer
 * https://processwire.com
 * 
 * @since 3.0.101
 * 
 * @method array wordAlternates($word, array $options = array()) Protected method for hooking purposes only #pw-hooker #pw-internal
 * @method string wordStem($word) Protected method for hooking purposes only #pw-hooker #pw-internal
 *
 */

class WireTextTools extends Wire {

	/**
	 * mbstring support?
	 * 
	 * @var bool
	 * 
	 */
	protected $mb;

	/**
	 * Construct
	 * 
	 */
	public function __construct() {
		$this->mb = function_exists("mb_internal_encoding");
		parent::__construct();
	}
	
	/**
	 * Convert HTML markup to readable text
	 * 
	 * Like PHP’s strip_tags but with some small improvements in HTML-to-text conversion that
	 * improves the readability of the text. 
	 * 
	 * In 3.0.197+ inner content of script, style and object tags is now removed, rather than just the tags. 
	 * To revert this behavior or to remove content of additional tags, see the `clearTags` option. 
	 * 
	 * Note that this method differs from the `Sanitizer::markupToText()` method in that this method is newer,
	 * more powerful and has more options. But the two methods differ in how they perform markup-to-text 
	 * conversion so you may want to review and try both to determine which one better suits your needs.
	 * 
	 * @param string $str String to convert to text
	 * @param array $options 
	 *  - `keepTags` (array): Tag names to keep in returned value, i.e. [ "em", "strong" ]. (default=none)
	 *  - `clearTags` (array): Tags that should also have their content cleared. (default=[ "script", "style", "object" ]) Since 3.0.197
	 *  - `splitBlocks` (string): String to split paragraph and header elements. (default="\n\n")
	 *  - `convertEntities` (bool): Convert HTML entities to plain text equivalents? (default=true)
	 *  - `listItemPrefix` (string): Prefix for converted list item `<li>` elements. (default='• ')
	 *  - `linksToUrls` (bool): Convert links to `(url)` rather than removing? (default=true) Since 3.0.132
	 *  - `linksToMarkdown` (bool): Convert links to `[text](url)` rather than removing? (default=false) Since 3.0.197
	 *  - `uppercaseHeadlines` (bool): Convert headline tags to uppercase? (default=false) Since 3.0.132
	 *  - `underlineHeadlines` (bool): Underline headlines with "=" or "-"? (default=true) Since 3.0.132
	 *  - `collapseSpaces` (bool): Collapse extra/redundant extra spaces to single space? (default=true) Since 3.0.132
	 *  - `replacements` (array): Associative array of strings to manually replace. (default=['&nbsp;' => ' '])
	 * @return string
	 * @see Sanitizer::markupToText()
	 *
	 */
	public function markupToText($str, array $options = []) {
		
		$sanitizer = $this->wire()->sanitizer;
		
		$defaults = [
      'keepTags' => [],
      'clearTags' => ['script', 'style', 'object'],
      'linksToUrls' => true,
      // convert links to just URL rather than removing entirely
      'linksToMarkdown' => false,
      // convert links to Markdown style links
      'splitBlocks' => "\n\n",
      'uppercaseHeadlines' => false,
      'underlineHeadlines' => true,
      'convertEntities' => true,
      'listItemPrefix' => '• ',
      'preIndent' => '',
      // indent for text within a <pre>
      'collapseSpaces' => true,
      'replacements' => ['&nbsp;' => ' '],
      'finishReplacements' => [],
  ];
		
		$str = (string) $str;
		if(!strlen($str)) return '';

		// merge options using arrays
		foreach(['replacements'] as $key) {
			if(!isset($options[$key])) continue;
			$options[$key] = array_merge($defaults[$key], $options[$key]);
		}
		
		$options = array_merge($defaults, $options);

		if(str_contains($str, '>')) {

			// strip out everything up to and including </head>, if present
			if(str_contains($str, '</head>')) [, $str] = explode('</head>', $str);

			// ensure tags are separated by whitespace
			$str = str_replace('><', '> <', $str);

			// normalize newlines
			if(str_contains($str, "\r")) {
				$str = str_replace(["\r\n", "\r"], "\n", $str);
			}

			// normalize tabs to spaces
			if(str_contains($str, "\t")) {
				$str = str_replace("\t", " ", $str);
			}

			// ensure paragraphs and headers are followed by two newlines
			if(stripos($str, '</p') || stripos($str, '</h') || stripos($str, '</li') || stripos($str, '</bl') || stripos($str, '</div')) {
				$str = preg_replace('!(</?(?:p|h\d|ul|ol|pre|blockquote|div)>)!i', '$1' . $options['splitBlocks'], $str);
			}

			// ensure list items are on their own line and prefixed with a bullet
			if(stripos((string) $str, '<li') !== false) {
				$prefix = in_array('li', $options['keepTags']) ? '' : $options['listItemPrefix'];
				$str = preg_replace('![\s\r\n]+<li[^>]*>[\s\r\n]*!i', "\n<li>$prefix", $str);
				if($prefix) {
					$options['replacements']["\n$prefix "] = "\n$prefix"; // prevent extra space
					$prefix = trim((string) $prefix); 
					$options['finishReplacements']["\n$prefix\n$prefix"] = ""; // prevent blank items
					$options['finishReplacements']["\n$prefix\n"] = "";
					
				}
			}

			// convert <br> tags to be just a single newline
			if(stripos((string) $str, '<br') !== false) {
				$str = str_replace(['<br>', '<br/>', '<br />', '</li>'], "<br>\n", $str);
				while(stripos($str, "\n<br>") !== false) $str = str_replace("\n<br>", "<br>", $str);
				while(stripos($str, "<br>\n\n") !== false) $str = str_replace("<br>\n\n", "<br>\n", $str);
			}

			// make headlines more prominent with underlines or uppercase
			if(($options['uppercaseHeadlines'] || $options['underlineHeadlines']) && stripos((string) $str, '<h') !== false) {
				$topHtag = '';
				if($options['underlineHeadlines']) {
					// determine which is the top level headline tag 
					for($n = 1; $n <= 6; $n++) {
						if(stripos((string) $str, "<h$n") === false) continue;
						$topHtag = "h$n";
						break;
					}
				}
				if(preg_match_all('!<(h[123456])[^>]*>(.+?)</\1>!is', (string) $str, $matches)) {
					foreach($matches[2] as $key => $headline) {
						$fullMatch = $matches[0][$key];
						$tagName = strtolower($matches[1][$key]);
						$underline = '';
						//$headline = trim($headline);
						if($options['underlineHeadlines']) {
							$char = $tagName === $topHtag ? '=' : '-';
							$underline = "\n" . str_repeat($char, $this->strlen(trim(strip_tags($headline))));
						}
						if($options['uppercaseHeadlines']) $headline = strtoupper($headline);
						$str = str_replace($fullMatch, "\n\n<$tagName>$headline</$tagName>$underline", $str);
					}
				}
			}
		
			// convert "<a href='url'>text</a>" tags to "text (url)"
			if(($options['linksToUrls'] || $options['linksToMarkdown']) && stripos((string) $str, '<a ') !== false) {
				if(preg_match_all('!<a\s[^<>]*href=([^\s>]+)[^<>]*>(.+?)</a>!is', (string) $str, $matches)) {
					$links = [];
					foreach($matches[0] as $key => $fullMatch) {
						$href = trim($matches[1][$key], '"\'');
						if(str_starts_with($href, '#')) continue; // do not convert jumplinks
						$anchorText = trim($matches[2][$key]);
						$links[$fullMatch] = "[$anchorText]($href)";
					}
					if(count($links)) {
						$str = str_replace(array_keys($links), array_values($links), $str); 
					}
					unset($links);
				}
			}
		
			// indent within <pre>...</pre> sections
			if(strlen((string) $options['preIndent']) && str_contains((string) $str, '<pre')) {
				if(preg_match_all('!<pre(?:>|\s[^>]*>)(.+?)</pre>!is', (string) $str, $matches)) {
					foreach($matches[0] as $key => $fullMatch) {
						$lines = explode("\n", $matches[1][$key]);
						foreach($lines as $k => $line) {
							$lines[$k] = ':preIndent:' . rtrim($line); 
						}
						$str = str_replace($fullMatch, implode("\n", $lines), $str); 
						$options['finishReplacements'][':preIndent:'] = $options['preIndent'];
						unset($lines);
					}
				}
			}
		
			// strip tags AND their contents for specified tags
			foreach($options['clearTags'] as $s) {
				$s = strtolower((string) $s);
				if(stripos((string) $str, "<$s") === false) continue;
				$str = str_ireplace(["<$s", "</$s"], ["<$s", "</$s"], $str); // adjust case
				$parts = explode("<$s", $str); 
				foreach($parts as $key => $part) {
					if(!str_contains($part, "</$s>")) {
						if($key > 0) unset($parts[$key]); // remove nested inner content
					} else {
						$endparts = explode("</$s>", $part);
						$parts[$key] = array_pop($endparts); // convert to content after last </s>
					}
				}
				$str = implode("", $parts);
				unset($parts, $endparts, $s);
			}
		}

		// strip tags
		if(count($options['keepTags'])) {
			// some tags will be allowed to remain
			$keepTags = '';
			foreach($options['keepTags'] as $tag) {
				$keepTags .= "<" . trim((string) $tag, "<>") . ">";
			}
			$str = strip_tags((string) $str, $keepTags);

		} else {
			// not allowing any tags
			$str = strip_tags((string) $str);
			// if any possible tag characters remain, drop them now
			$str = str_replace(['<', '>'], ' ', $str);
		}

		// apply any other replacements
		foreach($options['replacements'] as $find => $replace) {
			$str = str_ireplace($find, $replace, $str);
		}

		// convert entities to plain text equivalents
		if($options['convertEntities'] && str_contains($str, '&')) {
			$str = $sanitizer->unentities($str);
		}
	
		// collapse any redundant/extra whitespace
		if($options['collapseSpaces']) {
			while(str_contains((string) $str, '  ')) $str = str_replace('  ', ' ', $str);
		}
		
		// normalize newlines and whitespace around newlines
		while(str_contains((string) $str, " \n")) $str = str_replace(" \n", "\n", $str);
		while(str_contains((string) $str, "\n ")) $str = str_replace("\n ", "\n", $str);
		while(str_contains((string) $str, "\n\n\n")) $str = str_replace("\n\n\n", "\n\n", $str);

		if(strpos((string) $str, '](')) {
			// contains links
			if(str_contains((string) $str, '[](') || str_contains((string) $str, '[ ](')) {
				// remove links that lack anchor text
				$str = preg_replace('!\[\s*\]\([^)]*\)!', '', $str);
			}
			if($options['linksToUrls']) {
				// convert markdown style "[text](url)" to "text (url)"
				if(!$options['linksToMarkdown']) $str = preg_replace('!\[\s*(.+?)\]\(!', '$1 (', $str);
			}
		}

		if(count($options['finishReplacements'])) {
			$str = str_replace(array_keys($options['finishReplacements']), array_values($options['finishReplacements']), $str); 
		}

		return trim((string) $str);
	}

	/**
	 * Remove (or close) unclosed HTML tags from given string
	 *
	 * Remove unclosed tags:
	 * ---------------------
	 * At present, if it finds an unclosed tag, it removes all tags of the same kind.
	 * This is in order to keep the function fast, by delegating what it can to strip_tags().
	 * This is sufficient for our internal use here, but may not be ideal for all situations.
	 * 
	 * Fix/close unclosed tags:
	 * ------------------------
	 * When the remove option is false, it will attempt to close unclosed tags rather than 
	 * remove them. It doesn't know exactly where they should be closed, so it appends the 
	 * close tags to the end of the string. 
	 * 
	 * @param string $str
	 * @param bool $remove Remove unclosed tags? If false, it will attempt to close them instead. (default=true)
	 * @param array $options
	 *  - `ignoreTags` (array): Tags that can be ignored because they close themselves. (default=per HTML spec)
	 * @return string
	 *
	 */
	public function fixUnclosedTags($str, $remove = true, $options = []) {
		
		$defaults = ['ignoreTags' => ['area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'menuitem', 'meta', 'param', 'source', 'track', 'wbr']];

		if(isset($options['ignoreTags'])) {
			// merge user specified ignoreTags with our defaults so that both are used
			$options['ignoreTags'] = array_merge($defaults['ignoreTags'], $options['ignoreTags']);
		}
		
		$options = array_merge($defaults, $options);
		$tags = [];
		$unclosed = [];

		$n1 = substr_count($str, '>');
		$n2 = substr_count($str, '</');

		if($n1) $n1 = $n1 / 2;
		
		// if the quantity of ">" is equal to double the quantity of "</" then early exit
		if($n1 === $n2) return $str;
	
		// now check for string possibly ending with a partial tag, and remove if present
		$n1 = strrpos($str, '<');
		$n2 = strrpos($str, '>');
		if($n1 > $n2) {
			// string might end with a partial tag, i.e. "<span"
			$test = substr($str, $n1 + 1, 1); // i.e. "s" from "<span", or "<" is last char in the string
			if(ctype_alpha($test) || $test === false || $test === '') {
				// going to assume this is a tag, so trucate 
				$str = substr($str, 0, $n1 - 1);
			}
		}

		// find all open tags
		if(!preg_match_all('!<([a-z]+[a-z0-9]*)(>|\s*/>|\s[^>]+>)!i', $str, $matches)) return $str;

		foreach($matches[1] as $key => $tag) {
			if(str_contains($matches[2][$key], '/>')) continue; // ignore self closing tags
			if(in_array(strtolower($tag), $options['ignoreTags'])) continue; 
			$tags[$tag] = $tag;
		}

		// count appearances of found tags
		foreach($tags as $tag) {
			// count number of open tags of this type
			$openQty = substr_count($str, "<$tag>") + substr_count($str, "<$tag ");
			// count number of closing tags of this type
			$closeQty = substr_count($str, "</$tag>");
			// if quantities do not match, mark tag for deletion
			if($openQty !== $closeQty) {
				unset($tags[$tag]);
				$unclosed[] = $tag;
			}
		}
		

		if(count($unclosed)) {
			if($remove) {
				// strip all tags except those where open/close quantity matched
				$keepTags = count($tags) ? '<' . implode('><', $tags) . '>' : '';
				$str = strip_tags($str, $keepTags);
			} else {
				foreach($unclosed as $tag) {
					$str .= "</$tag>";
				}
			}
		}

		return $str;
	}

	/**
	 * Collapse string to plain text that all exists on a single long line without destroying words/punctuation.
	 *
	 * @param string $str String to collapse
	 * @param array $options
	 *  - `stripTags` (bool): Strip markup tags? (default=true)
	 *  - `keepTags` (array): Array of tag names to keep, if stripTags==true. (default=[])
	 *  - `collapseLinesWith` (string): String to collapse newlines with. (default=' ')
	 *  - `linksToUrls` (bool): Convert links to "(url)" rather than removing entirely? (default=false) Since 3.0.132
	 *  - `endBlocksWith` (string): Character or string to insert to identify paragraph/header separation (default='')
	 *  - `convertEntities` (bool): Convert entity-encoded characters to text? (default=true)
	 * @return string
	 *
	 */
	public function collapse($str, array $options = []) {

		$defaults = ['stripTags' => true, 'keepTags' => [], 'collapseLinesWith' => ' ', 'endBlocksWith' => '', 'convertEntities' => true, 'linksToUrls' => false];

		$options = array_merge($defaults, $options);

		if($options['stripTags']) {
			$str = $this->markupToText($str, ['underlineHeadlines' => false, 'uppercaseHeadlines' => false, 'convertEntities' => $options['convertEntities'], 'linksToUrls' => $options['linksToUrls'], 'keepTags' => $options['keepTags']]);
			if(!strlen($str)) return $str;
		}

		// character that we collapse lines with
		$r = $options['collapseLinesWith'];

		// convert any tabs to space
		if(str_contains($str, "\t")) {
			$str = str_replace("\t", " ", $str);
		}

		// convert CRs to LFs
		if(str_contains($str, "\r")) {
			$str = str_replace(["\r\n", "\r"], "\n", $str);
		}

		// collapse whitespace that appears before or after newlines
		while(str_contains($str, " \n")) $str = str_replace(" \n", "\n", $str);
		while(str_contains($str, "\n ")) $str = str_replace("\n ", "\n", $str);

		// convert redundant LFs to no more than double LFs
		while(str_contains($str, "\n\n\n")) {
			$str = str_replace("\n\n\n", "\n\n", $str);
		}

		// add character to indicate blocks, when asked for
		if(!empty($options['endBlocksWith'])) {
			$str = str_replace("\n\n", "$options[endBlocksWith]\n\n", $str);
		}

		// replace all types of newlines
		$str = str_replace(["\r\n", "\r", "\n\n", "\n"], $r, $str);

		// while there are consecutives of our collapse string, reduce them to one
		while(str_contains($str, "$r$r")) {
			$str = str_replace("$r$r", $r, $str);
		}

		if($r !== $defaults['collapseLinesWith']) {
			// replacement of whitespace with something other than another single whitespace
			// so collapse consecutive spaces to one space, since this would not be already done
			while(str_contains($str, "  ")) {
				$str = str_replace("  ", " ", $str);
			}
			// use space rather than replacement char when left side already ends with punctuation
			foreach($this->getPunctuationChars() as $c) {
				if(strpos($str, "$c$r")) $str = str_replace("$c$r", "$c ", $str);
			}
		}

		return trim($str);
	}

	/**
	 * Truncate string to given maximum length without breaking words
	 * 
	 * This method can truncate between words, sentences, punctuation or blocks (like paragraphs). 
	 * See the `type` option for details on how it should truncate. By default it truncates between
	 * words. Description of types:
	 * 
	 * - word: truncate to closest word.
	 * - punctuation: truncate to closest punctuation within sentence. 
	 * - sentence: truncate to closest sentence.
	 * - block: truncate to closest block of text (like a paragraph or headline). 
	 * 
	 * Note that if your specified `type` is something other than “word”, and it cannot be matched 
	 * within the maxLength, then it will attempt a different type. For instance, if you specify
	 * “sentence” as the type, and it cannot match a sentence, it will try to match to “punctuation”
	 * instead. If it cannot match that, then it will attempt “word”. 
	 * 
	 * HTML will be stripped from returned string. If you want to keep some tags use the `keepTags` or `keepFormatTags`
	 * options to specify what tags are allowed to remain. The `keepFormatTags` option that, when true, will make it
	 * retain all HTML inline text formatting tags. 
	 * 
	 * ~~~~~~~
	 * // Truncate string to closest word within 150 characters
	 * $s = $sanitizer->truncate($str, 150); 
	 * 
	 * // Truncate string to closest sentence within 300 characters
	 * $s = $sanitizer->truncate($str, 300, 'sentence'); 
	 * 
	 * // Truncate with options
	 * $s = $sanitizer->truncate($str, [
	 *   'type' => 'punctuation',
	 *   'maxLength' => 300,
	 *   'visible' => true,
	 *   'more' => '…'
	 * ]);
	 * ~~~~~~~
	 *
	 * @param string $str String to truncate
	 * @param int|array $maxLength Maximum length of returned string, or specify $options array here.
	 * @param array|string $options Options array, or specify `type` option (string).
	 *  - `type` (string): Preferred truncation type of word, punctuation, sentence, or block. (default='word')
	 *       This is a “preferred type”, not an absolute one, because it will adjust to match what it can within your maxLength.
	 *  - `maxLength` (int): Max characters for truncation, used only if $options array substituted for $maxLength argument.
	 *  - `maximize` (bool): Include as much as possible within specified type and max-length? (default=true)
	 *       If you specify false for the maximize option, it will truncate to first word, puncutation, sentence or block.
	 *  - `visible` (bool): When true, invisible text (markup, entities, etc.) does not count towards string length. (default=false)
	 *  - `trim` (string): Characters to trim from returned string. (default=',;/ ')
	 *  - `noTrim` (string): Never trim these from end of returned string. (default=')]>}”»')
	 *  - `more` (string): Append this to truncated strings that do not end with sentence punctuation. (default='…')
	 *  - `keepTags` (array): HTML tags that should be kept in returned string. (default=[])
	 *  - `keepFormatTags` (bool): Keep HTML text-formatting tags? Simpler alternative to keepTags option. (default=false)
	 *  - `collapseLinesWith` (string): String to collapse lines with where the first is not punctuated. (default=' … ')
	 *  - `convertEntities` (bool): Convert HTML entities to non-entity characters? (default=false)
	 *  - `noEndSentence` (string): Strings that sentence may not end with, space-separated values (default='Mr. Mrs. …')
	 * @return string
	 *
	 */
	function truncate($str, $maxLength, $options = []) {
		
		if(!strlen($str)) return '';

		$ent = __(true, 'entityEncode', false);

		$defaults = [
      'type' => 'word',
      // word, punctuation, sentence, or block
      'maximize' => true,
      // include as much as possible within the type and maxLength (false=include as little as possible)
      'visible' => false,
      // when true, invisible text (markup, entities, etc.) does not count towards string length. (default=false)
      'trim' => $this->_(',;/') . ' ',
      // Trim these characters from the end of the returned string
      'noTrim' => $this->_(')]>}”»'),
      // Never trim these characters from end of returned string
      'more' => '…',
      // Append to truncated strings that do not end with sentence punctuation
      'stripTags' => true,
      // strip HTML tags? (currently required, see keepTags to keep some)
      'keepTags' => [],
      // if strip HTML tags is true, optional array of tag names you want to keep
      'keepFormatTags' => false,
      // alternative to keepTags: keep just inline text format tags like strong, em, etc.
      'collapseWhitespace' => true,
      // collapsed whitespace (currently required)
      'collapseLinesWith' => ' ' . $this->_('…') . ' ',
      // String placed between joined lines (like from paragraphs)
      'convertEntities' => false,
      // convert entity encoded characters to non-entity equivalents? (default=false)
      'noEndSentence' => $this->_('Mr. Mrs. Ms. Dr. Hon. PhD. i.e. e.g.'),
  ];

		if($ent) __(true, 'entityEncode', $ent);
		
		if(is_string($options) && ctype_alpha($options)) {
			$defaults['type'] = $options;
			$options = [];
		}

		if(is_array($maxLength)) {
			$options = $maxLength;
			if(!isset($options['maxLength'])) $options['maxLength'] = 0;
			$maxLength = $options['maxLength'];
		} else if(is_string($maxLength) && ctype_alpha($maxLength)) {
			$options['type'] = $maxLength;
			$maxLength = $options['maxLength'] ?? $this->strlen($str);
		}

		if(!$maxLength) $maxLength = 255;
		$options = array_merge($defaults, $options);
		$type = $options['type'];
		$str = trim($str);
		$blockEndChar = '¶';
		$tests = [];
		$punctuationChars = $this->getPunctuationChars();
		$endSentenceChars = $this->getPunctuationChars(true);
		$endSentenceChars[] = ':';

		if($options['keepFormatTags']) {
			$options['keepTags'] = array_merge($options['keepTags'], ['abbr', 'acronym', 'b', 'big', 'cite', 'code', 'em', 'i', 'kbd', 'q', 'samp', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'var']);
		}

		if($type === 'block') {
			if($this->strpos($str, $blockEndChar) !== false) $str = str_replace($blockEndChar, ' ', $str);
			$options['endBlocksWith'] = $blockEndChar;
		}

		// collapse whitespace and strip tags
		$str = $this->collapse($str, $options);
		
		if(trim((string) $options['collapseLinesWith']) && $this->strpos($str, $options['collapseLinesWith'])) {
			// if lines are collapsed with something other than whitespace, avoid using that string
			// when the line already ends with sentence punctuation
			foreach($endSentenceChars as $c) {
				$str = str_replace("$c$options[collapseLinesWith]", "$c ", $str); 
			}
		}

		// if anything above reduced the length of the string enough, return it now
		if($this->strlen($str) <= $maxLength) return $str;

		// get string at maximum possible length
		if($options['visible']) {
			// adjust for only visible length
			$_str = $str;
			$str = $this->substr($str, 0, $maxLength);
			$len = $this->getVisibleLength($str);
			if($len < $maxLength) {
				$maxLength += ($maxLength - $len);
				$str = $this->substr($_str, 0, $maxLength);
			}
			unset($_str);
		} else {
			$str = $this->substr($str, 0, $maxLength);
		}

		// match to closest blocks, like paragraph(s)
		if($type === 'block') {
			$pos = $options['maximize'] ? $this->strrpos($str, $blockEndChar) : $this->strpos($str, $blockEndChar);
			if($pos === false) {
				$type = 'sentence';
			} else {
				$tests[] = $pos;
				$options['trim'] .= $blockEndChar;
			}
		}

		// find sentences closest to end	
		if($type === 'sentence') {
			$this->truncateSentenceTests($str, $tests, $endSentenceChars, $options);
			if(!count($tests)) $type = 'punctuation';
		}

		// find punctuation closes to end of string
		if($type === 'punctuation') {
			foreach($punctuationChars as $find) {
				$pos = $options['maximize'] ? $this->strrpos($str, $find) : $this->strpos($str, $find);
				if($pos) $tests[] = $pos;
			}
			if(!count($tests)) $type = 'word';
		}

		// find whitespace and last word closest to end of string
		if($type === 'word' || !count($tests)) {
			$pos = $options['maximize'] ? $this->strrpos($str, ' ') : $this->strpos($str, ' ');
			if($pos) $tests[] = $pos;
		}

		if(count($tests)) {
			// we found somewhere to truncate, so truncate at the longest one possible
			if($options['maximize']) {
				sort($tests);
			} else {
				rsort($tests);
			}

			// process our tests
			do {
				$pos = array_pop($tests);
				$result = trim($this->substr($str, 0, $pos + 1));
				$lastChar = $this->substr($result, -1);
				$result = $this->rtrim($result, $options['trim']);

				if($type === 'sentence' || $type === 'block') {
					// good to go with result as is
				} else if(in_array($lastChar, $endSentenceChars)) {
					// good, end with sentence ending punctuation
				} else if(in_array($lastChar, $punctuationChars)) {
					$trims = ' ';
					foreach($punctuationChars as $c) {
						if($this->strpos($options['noTrim'], $c) !== false) continue;
						if(in_array($c, $endSentenceChars)) continue;
						$trims .= $c;
					}
					$result = $this->rtrim($result, $trims) . $options['more'];
				} else {
					$result .= $options['more'];
				}

			} while(!strlen($result) && count($tests));

			// make sure we didn't break any HTML tags as a result of truncation
			if(strlen($result) && count($options['keepTags']) && str_contains($result, '<')) {
				$result = $this->fixUnclosedTags($result);
			}
		} else {
			// if we didn't find any place to truncate, just return exact truncated string
			$result = $this->trim($str, $options['trim']) . $options['more'];
		}
		
		if(strlen((string) $options['more'])) {
			// remove any duplicated more strings
			$more = $options['more'];
			while(str_contains($result, "$more$more")) {
				$result = str_replace("$more$more", "$more", $result); 
			}
		}
		
		return $result;
	}

	/**
	 * Helper to truncate() method, generate tests/positions for where sentences end
	 * 
	 * @param string $str
	 * @param array $tests Tests to append found positions to
	 * @param array $endSentenceChars
	 * @param array $options Options provided to truncate method
	 * 
	 */
	protected function truncateSentenceTests($str, array &$tests, array $endSentenceChars, array $options) {
		
		$chars = $endSentenceChars;
		$thisStr = $str;
		$nextStr = '';
		$nextOffset = 0;
		$offset = 0; // offset used for maximize==false mode only
		$n = 0;
		
		// regex matches specified words, plus digits or single letters followed by period
		$noEndRegex = '!\b(' . str_replace(' ', '|', preg_quote((string) $options['noEndSentence'])) . '|\d+\.|\w\.)$!';
		
		do {
			
			if($nextStr) {
				$offset = $nextOffset;
				$thisStr = $nextStr;
				$nextStr = '';
				$chars = ['.'];
			}
			
			foreach($chars as $find) {
				
				$pos = $options['maximize'] ? $this->strrpos($thisStr, "$find ") : $this->strpos($thisStr, "$find ", $offset);
				
				if(!$pos) continue;
				
				if($find === '.') {
					$testStr = $this->substr($thisStr, 0, $pos + 1);
					if(preg_match($noEndRegex, $testStr, $matches)) {
						// ends with a disallowed word, next time try to match with a shorter string
						if($options['maximize']) {
							$nextStr = $this->substr($testStr, 0, $this->strlen($testStr) - $this->strlen($matches[1]) - 1);
						} else {
							$nextOffset = $this->strlen($testStr);
						}
						continue;
					}
				}
				
				$tests[] = $pos;
			}
			
		} while(strlen($nextStr) && ++$n < 3);
	}

	/**
	 * Return visible length of string, which is length not counting markup or entities
	 * 
	 * @param string $str
	 * @return int
	 * 
	 */
	public function getVisibleLength($str): int {
		if(strpos($str, '>')) {
			$str = strip_tags($str);
		}
		if(str_contains($str, '&') && strpos($str, ';')) {
			$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
		}
		return $this->strlen($str);
	}

	/**
	 * Get array of punctuation characters
	 * 
	 * @param bool $sentence Get only sentence-ending punctuation
	 * @return array
	 * 
	 */
	public function getPunctuationChars($sentence = false) {
		$ent = __(true, 'entityEncode', false);
		if($sentence) {
			$s = $this->_('. ? !'); // Sentence ending punctuation characters (must be space-separated)
		} else {
			$s = $this->_(', : . ? ! “ ” „ " – -- ( ) [ ] { } « »'); // All punctuation characters (must be space-separated)
		}
		if($ent) __(true, 'entityEncode', $ent);
		return explode(' ', $s); 
	}

	/**
	 * Get alternate words for given word 
	 * 
	 * This method does not do anything unless an implementation is provided by a module (or something else)
	 * hooking the protected `WireTextTools::wordAlternates($word, $options)` method. Implementation should 
	 * populate $event->return with any or all of the following (as available): 
	 * 
	 * - Word plural(s)
	 * - Word singular(s)
	 * - Word Lemmas
	 * - Word Synonyms
	 * - Anything else applicable to current $user->language
	 * 
	 * See the protected WireTextTools::wordAlternates() method for hook instructions and an example. 
	 * 
	 * @param string $word
	 * @param array $options
	 *  - `operator` (string): Operator being used, if applicable (default='')
	 *  - `minLength` (int): Minimum word length to return in alternates (default=2)
	 *  - `lowercase` (bool): Convert words to lowercase, if not already (default=false)
	 * @return array
	 * @since 3.0.162
	 * @see WireTextTools::getWordStem()
	 * 
	 */
	public function getWordAlternates($word, array $options = []): array {
		
		if(!$this->hasHook('wordAlternates()')) return [];
		
		$defaults = ['operator' => '', 'minLength' => 2, 'lowercase' => false];
		
		$options = array_merge($defaults, $options);
		$word = $this->trim($word);
		$words = [];
		$wordLow = $this->strtolower($word);
		
		if($options['lowercase']) $word = $wordLow;
		if(empty($word)) return [];
		
		$alternates = $this->wordAlternates($word, $options);
		if(!count($alternates)) return [];
		
		// if original word appears in return value, remove it
		$key = array_search($word, $alternates);
		if($key !== false) unset($alternates[$key]);
		
		// populate $words, removing any invalid or duplicate values
		foreach($alternates as $w) {
			if(!is_string($w)) continue;
			$w = $this->trim($w);
			$wLow = $this->strtolower($w);
			if($wLow === $wordLow) continue; // dup of original word
			if($options['lowercase']) $w = $wLow; // use lowercase
			if($this->strlen($w) < $options['minLength']) continue; // too short
			if(isset($words[$wLow])) continue; // already have it
			$words[$wLow] = $w;
		}
	
		return array_values($words);
	}
	
	/**
	 * Hookable method to return alternate words for given word
	 *
	 * This hookable method is separate from the public getWordAlternates() method so that
	 * we can provide predictable and already-populated $options to whatever is hooking this, as
	 * as provide some additional QA with the return value from modules/hooks.
	 *
	 * It is fine if the return value contains duplicates, the original word, or too-short words,
	 * as the calling getWordAlternates() takes care of those before returning words to user.
	 * Basically, hooks can ignore the `$options` argument, unless they need to know the `operator`,
	 * which may or may not be provided by the caller.
	 *
	 * In hook implementation, avoid deleting what’s already present in $event->return just in
	 * case multiple hooks are adding words.
	 *
	 * ~~~~~
	 * // Contrived example of how to implement
	 * $wire->addHookAfter('WireTextTools::wordAlternates', function(HookEvent $event) {
	 *   $word = $event->arguments(0); // string: word requested alternates for
	 *   $words = $event->return; // array: existing return value
	 *
	 *   $cats = [ 'cat', 'cats', 'kitty', 'feline', 'felines' ];
	 *   $dogs = [ 'dog', 'dogs', 'doggy', 'canine', 'canines' ];
	 *
	 *   if(in_array($word, $cats)) {
	 *     $words = array_merge($words, $cats);
	 *   } else if(in_array($word, $dogs)) {
	 *     $words = array_merge($words, $dogs);
	 *   }
	 *
	 *   $event->return = $words;
	 * });
	 *
	 * // Test it out
	 * $words = $sanitizer->getTextTools()->getWordAlternates('cat');
	 * echo implode(', ', $words); // outputs: cats, kitty, kitten, feline, felines
	 * ~~~~~
	 *
	 * #pw-hooker
	 *
	 * @param string $word
	 * @param array $options
	 *  - `operator` (string): Operator being used, if applicable (default='')
	 * @return array
	 * @since 3.0.162
	 *
	 */
	protected function ___wordAlternates($word, array $options): array {
		if($word && $options) {} // ignore
		$alternates = [];
		return $alternates;
	}

	/**
	 * Find and return all {placeholder} tags found in given string
	 *
	 * @param string $str String that might contain field {tags}
	 * @param array $options
	 *  - `has` (bool): Specify true to only return true or false if it has tags (default=false).
	 * 	- `tagOpen` (string): The required opening tag character(s), default is '{'
	 *	- `tagClose` (string): The required closing tag character(s), default is '}'
	 * @return array|bool
	 * @since 3.0.126
	 *
	 */
	public function findPlaceholders($str, array $options = []) {
		
		$defaults = ['has' => false, 'tagOpen' => '{', 'tagClose' => '}'];
		
		$options = array_merge($defaults, $options);
		$tags = [];
		$pos1 = strpos($str, (string) $options['tagOpen']);
		
		if($pos1 === false) return $options['has'] ? false : $tags;
		
		if(strlen((string) $options['tagClose'])) {
			$pos2 = strpos($str, (string) $options['tagClose']);
			if($pos2 === false) return $options['has'] ? false : $tags;
		}

		$regex = '/' . preg_quote((string) $options['tagOpen']) . '([-_.|a-zA-Z0-9]+)' . preg_quote((string) $options['tagClose']) . '/';
		if($options['has']) return (bool) preg_match($regex, $str);
		if(!preg_match_all($regex, $str, $matches)) return $tags;
		
		foreach($matches[0] as $key => $tag) {
			$name = $matches[1][$key];
			$tags[$name] = $tag;
		}
			
		return $tags;
	}

	/**
	 * Does the string have any {placeholder} tags in it?
	 *
	 * @param string $str
	 * @param array $options
	 * 	- `tagOpen` (string): The required opening tag character(s), default is '{'
	 *	- `tagClose` (string): The required closing tag character(s), default is '}'
	 * @return bool
	 * @since 3.0.126
	 *
	 */
	public function hasPlaceholders($str, array $options = []) {
		$options['has'] = true;
		return $this->findPlaceholders($str, $options);
	}
	
	/**
	 * Given a string ($str) and values ($vars), populate placeholder “{tags}” in the string with the values
	 *
	 * - The `$vars` should be an associative array of `[ 'tag' => 'value' ]`.
	 * - The `$vars` may also be an object, in which case values will be pulled as properties of the object.
	 *
	 * By default, tags are specified in the format: {first_name} where first_name is the name of the
	 * variable to pull from $vars, `{` is the opening tag character, and `}` is the closing tag char.
	 *
	 * The tag parser can also handle subfields and OR tags, if `$vars` is an object that supports that.
	 * For instance `{products.title}` is a subfield, and `{first_name|title|name}` is an OR tag.
	 *
	 * ~~~~~
	 * $vars = [ 'foo' => 'FOO!', 'bar' => 'BAR!' ];
	 * $str = 'This is a test: {foo}, and this is another test: {bar}';
	 * echo $sanitizer->getTextTools()->populatePlaceholders($str, $vars);
	 * // outputs: This is a test: FOO!, and this is another test: BAR!
	 * ~~~~~
	 *
	 * @param string $str The string to operate on (where the {tags} might be found)
	 * @param WireData|object|array $vars Object or associative array to pull replacement values from.
	 * @param array $options Array of optional changes to default behavior, including:
	 * 	- `tagOpen` (string): The required opening tag character(s), default is '{'
	 *  - `tagClose` (string): The optional closing tag character(s), default is '}'
	 *  - `recursive` (bool): If replacement value contains tags, populate those too? (default=false)
	 *  - `removeNullTags` (bool): If a tag resolves to a NULL, remove it? If false, tag will remain. (default=true)
	 *  - `entityEncode` (bool): Entity encode the values pulled from $vars? (default=false)
	 *  - `entityDecode` (bool): Entity decode the values pulled from $vars? (default=false)
	 *  - `allowMarkup` (bool): Allow markup to appear in populated variables? (default=true)
	 * @return string String with tags populated.
	 * @since 3.0.126 Use wirePopulateStringTags() function for older versions
	 *
	 */
	public function populatePlaceholders($str, $vars, array $options = []) {
		
		$defaults = [
      'tagOpen' => '{',
      // opening tag (required)
      'tagClose' => '}',
      // closing tag (optional)
      'recursive' => false,
      // if replacement value contains tags, populate those too?
      'removeNullTags' => true,
      // if a tag value resolves to a NULL, remove it? If false, tag will be left in tact.
      'entityEncode' => false,
      // entity encode values pulled from $vars?
      'entityDecode' => false,
      // entity decode values pulled from $vars?
      'allowMarkup' => true,
  ];

		$options = array_merge($defaults, $options);
		$optionsNoRecursive = $options['recursive'] ? array_merge($options, ['recursive' => false]) : $options;
		$replacements = [];
		$tags = $this->findPlaceholders($str, $options);

		// create a list of replacements by finding replacement values in $vars
		foreach($tags as $fieldName => $tag) {
			
			if(isset($replacements[$tag])) continue; // if already found, do not do it again
			$fieldValue = null;

			if(is_object($vars)) {
				if($vars instanceof Page) {
					$fieldValue = $options['allowMarkup'] ? $vars->getMarkup($fieldName) : $vars->getText($fieldName);
				} else if($vars instanceof WireData) {
					$fieldValue = $vars->get($fieldName);
				} else {
					$fieldValue = $vars->$fieldName;
				}
			} else if(is_array($vars)) {
				$fieldValue = $vars[$fieldName] ?? null;
			}
			
			// if value resolves to null and we are not removing null tags, then do not add to replacements
			if($fieldValue === null && !$options['removeNullTags']) continue;
			
			$fieldValue = (string) $fieldValue;

			if(!$options['allowMarkup'] && str_contains($fieldValue, '<')) $fieldValue = strip_tags($fieldValue);
			if($options['entityEncode']) $fieldValue = htmlentities($fieldValue, ENT_QUOTES, 'UTF-8', false);
			if($options['entityDecode']) $fieldValue = html_entity_decode($fieldValue, ENT_QUOTES, 'UTF-8');
			
			if($options['recursive'] && str_contains($fieldValue, (string) $options['tagOpen'])) {
				$fieldValue = $this->populatePlaceholders($fieldValue, $vars, $optionsNoRecursive);
			}
		
			$replacements[$tag] = $fieldValue;
		}

		// replace the tags 
		if(count($tags)) {
			$str = str_replace(array_keys($replacements), array_values($replacements), $str);
		}

		return $str; 
	}
	
	/**
	 * Populate placeholders in string with sanitizers applied to populated values
	 * 
	 * These placeholders accept one or more sanitizer names as part `{placeholder}` in the format `{placeholder:sanitizers}`,
	 * where `placeholder` is the name of a variable accessible from `$data` argument and `sanitizers` is the name of a 
	 * sanitizer method or a CSV string of sanitizer methods. Placeholders with any whitespace are ignored.
	 * 
	 * #pw-internal 
	 * 
	 * ~~~~~
	 * $tools = $sanitizer->getTextTools();
	 * $data = [ 'name' => 'John <Bob> Smith', 'age' => 46.5 ];
	 * 
	 * $str = "My name is {name:camelCase}, my age is {age:int}";
	 * echo $tools->placeholderSanitizers($str, $data); // outputs: My name is johnBobSmith, my age is 46
	 * 
	 * $str = "My name is {name:removeWhitespace,entities}, my age is {age:float}";
	 * echo $tools->placeholderSanitizers($str, $data); // outputs: My name is John&lt;Bob&gt;Smith, my age is 46.5
	 * 
	 * $str = "My name is {name:text,word}, my age is {age:digits}";
	 * echo $tools->placeholderSanitizers($str, $data); // outputs: My name is John, my age is 465
	 * ~~~~~
	 * 
	 * @param string $str
	 * @param array|WireData|WireInputData 
	 * @param array $options
	 * @return string
	 * @throws WireException
	 * @since 3.0.178
	 * @todo currently 'protected' for later use
	 *
	 */
	protected function placeholderSanitizers($str, $data, array $options = []) {
		

		$defaults = [
      'tagOpen' => '{',
      'tagClose' => '}',
      'sanitizersBefore' => ['string'],
      // sanitizers to apply before requested ones
      'sanitizersAfter' => [],
      // sanitizers to apply after requested ones
      'sanitizersDefault' => ['text'],
  ];
		

		$options = array_merge($defaults, $options);
		$sanitizer = $this->wire()->sanitizer;
		$dataIsArray = is_array($data);
		$replacements = [];
		$parts = [];
		
		if(!str_contains($str, (string) $options['tagOpen']) || !strpos($str, (string) $options['tagClose'])) return $str;
		
		if(!is_array($data) && !$data instanceof WireData && !$data instanceof WireInputData) {
			throw new WireException('$data argument must be associative array, WireData or WireInputData');
		}
	
		[$tagOpen, $tagClose] = [preg_quote((string) $options['tagOpen']), preg_quote((string) $options['tagClose'])]; 
		
		$regex = '/OPEN([-_.a-z0-9]+)(:[_,a-z0-9]+CLOSE|CLOSE)/i';
		$regex = str_replace(['OPEN', 'CLOSE'], [$tagOpen, $tagClose], $regex); 
		if(!preg_match_all($regex, $str, $matches)) return $str;

		foreach($matches[0] as $key => $placeholder) {
			$varName = $matches[1][$key];
			$sanitizers = trim($matches[2][$key], ':}');
			$sanitizers = strlen($sanitizers) ? explode(',', $sanitizers) : [];
			if(!count($sanitizers)) $sanitizers = $options['sanitizersDefault'];
			if($dataIsArray) {
				/** @var array $data */
				$value = $data[$varName] ?? null;
			} else {
				/** @var WireData|WireInputData $data */
				$value = $data->get($varName);
			}
			$n = 0;
			foreach([$options['sanitizersBefore'], $sanitizers, $options['sanitizersAfter']] as $methods) {
				foreach($methods as $method) {
					if(!$sanitizer->methodExists($method)) throw new WireException("Unknown sanitizer method: $method");
					$value = $sanitizer->sanitize($value, $method);
					$n++;
				}
			}
			if(!$n) $value = $placeholder;
			$replacements[] = [$placeholder, $value];
		}

		// piece it back together manually so values in $data cannot introduce more placeholders
		foreach($replacements as $item) {
			[$placeholder, $value] = $item;
			[$before, $after] = explode($placeholder, $str, 2);
			$parts[] = $before . $value;
			$str = $after;
		}

		return implode('', $parts) . $str;
	}

	/**
	 * Populate placeholders with optional sanitizers in a selector string
	 * 
	 * #pw-internal
	 * 
	 * @param string $selectorString
	 * @param array|WireData|WireInputData
	 * @param array $options
	 * @return string
	 * @throws WireException
	 * @since 3.0.178
	 * @todo currently 'protected' for later use
	 * 
	 */
	protected function placeholderSelector($selectorString, $data, array $options = []) {
		if(!isset($options['sanitizersBefore'])) $options['sanitizersBefore'] = [];
		if(!isset($options['sanitizersAfter'])) $options['sanitizersAfter'] = [];
		$options['sanitizersBefore'][] = 'text';
		$options['sanitizersAfter'][] = 'selectorValue';
		return $this->placeholderSanitizers($selectorString, $data, $options);
	}

	/**
	 * Given two arrays, return array of the changes with 'ins' and 'del' keys
	 * 
	 * Based upon Paul Butler’s Simple Diff Algorithm v0.1 © 2007 (zlib/libpng) https://paulbutler.org
	 * 
	 * @param array $oldArray
	 * @param array $newArray
	 * @return array
	 * @since 3.0.144
	 * 
	 */
	protected function diffArray(array $oldArray, array $newArray) {
		
		$matrix = [];
		$maxLen = 0;
		$oldMax = 0; 
		$newMax = 0;
		
		foreach($oldArray as $oldKey => $oldValue){
			
			$newKeys = array_keys($newArray, $oldValue);
			
			foreach($newKeys as $newKey) {
				$len = 1;
				if(isset($matrix[$oldKey - 1][$newKey - 1])) {
					$len = $matrix[$oldKey - 1][$newKey - 1] + 1;
				}
				$matrix[$oldKey][$newKey] = $len;

				if($len > $maxLen) {
					$maxLen = $len;
					$oldMax = $oldKey + 1 - $maxLen;
					$newMax = $newKey + 1 - $maxLen;
				}
			}
		}
		
		if($maxLen == 0) {
			$result = [['del' => $oldArray, 'ins' => $newArray]];
			
		} else {
			$result = array_merge(
				$this->diffArray(
					array_slice($oldArray, 0, $oldMax), 
					array_slice($newArray, 0, $newMax)
				),
				array_slice($newArray, $newMax, $maxLen),
				$this->diffArray(
					array_slice($oldArray, $oldMax + $maxLen), 
					array_slice($newArray, $newMax + $maxLen)
				)
			);
		}
		
		return $result;
	}

	/**
	 * Given two strings ($old and $new) return a diff string in HTML markup
	 * 
	 * @param string $old Old string value
	 * @param string $new New string value
	 * @param array $options Options to modify behavior:
	 *  - `ins` (string) Markup to use for diff insertions (default: `<ins>{out}</ins>`)
	 *  - `del` (string) Markup to use for diff deletions (default: `<del>{out}</del>`)
	 *  - `entityEncode` (bool): Entity encode values, other than added ins/del tags? (default=true)
	 *  - `split` (string): Regex used to split strings for parts to diff (default=`\s+`)
	 * @return string
	 * @since 3.0.144
	 * 
	 */
	public function diffMarkup($old, $new, array $options = []) {
		
		$defaults = ['ins' => "<ins>{out}</ins>", 'del' => "<del>{out}</del>", 'entityEncode' => true, 'split' => '\s+'];
		
		/** @var Sanitizer $sanitizer */
		$sanitizer = $this->wire('sanitizer');
		[$old, $new] = ["$old", "$new"]; // enforce as string
		$options = array_merge($defaults, $options);
		$oldArray = preg_split("!($options[split])!", $old, 0, PREG_SPLIT_DELIM_CAPTURE);
		$newArray = preg_split("!($options[split])!", $new, 0, PREG_SPLIT_DELIM_CAPTURE);
		$diffArray = $this->diffArray($oldArray, $newArray);
		[, $delClose] = explode('{out}', (string) $options['del'], 2);
		[$insOpen, ] = explode('{out}', (string) $options['ins'], 2); 
		$out = '';
		
		foreach($diffArray as $diff) {
			if(is_array($diff)) {
				foreach(['del', 'ins'] as $key) {
					if(empty($diff[$key])) continue;
					$diffStr = implode('', $diff[$key]);
					if($options['entityEncode']) $diffStr = $sanitizer->entities1($diffStr);
					$out .= str_replace('{out}', $diffStr, $options[$key]);
				}
			} else {
				$out .= ($options['entityEncode'] ? $sanitizer->entities1($diff) : $diff);
			}
		}

		if(strpos($out, "$delClose$insOpen")) {
			// put a space between '</del><ins>' so that it is '</del> <ins>'
			$out = str_replace("$delClose$insOpen", "$delClose $insOpen", $out);
		}
		
		return $out;
	}

	/**
	 * Find escaped characters in $str, replace them with a placeholder, and return the placeholders 
	 * 
	 * Usage
	 * ~~~~~
	 * // 1. Escape certain chars in a string that you want to survive some processing:
	 * $str = 'Hello \*world\* foo \"bar\" baz'; 
	 * 
	 * // 2. Use this method to find escape chars and replace them temporarily:
	 * $a = $sanitizer->getTextTools()->findReplaceEscapeChars($str, [ '*', '"' ]); 
	 * 
	 * // 3. Process string with anything that you want NOT to see chars that were escaped:
	 * $str = some_function_that_processes_the_string($str);
	 * 
	 * // 4. Do this to restore the escaped chars (restored without backslashes by default):
	 * $str = str_replace(array_keys($a), array_values($a), $str); 
	 * ~~~~~
	 * 
	 * @param string &$str String to find escape chars in, it will be modified directly (passed by reference)
	 * @param array $escapeChars Array of chars you want to escape i.e. [ '*', '[', ']', '(', ')', '`', '_', '\\', '"' ]
	 * @param array $options Options to modify behavior: 
	 *  - `escapePrefix` (string): Character used to escape another character (default is backslash).
	 *  - `restoreEscape` (bool): Should returned array also include the escape prefix, so escapes are restored? (default=false)
	 *  - `gluePrefix` (string): Prefix for placeholders we substitute for escaped characters (default='{ESC') 
	 *  - `glueSuffix` (string): Suffix for placeholders we substitute for escaped characters (default='}')
	 *  - `unescapeUnknown` (bool): If we come across escaped char not in your $escapeChars list, unescape it? (default=false)
	 *  - `removeUnknown` (bool): If we come across escaped char not in your $escapeChars list, remove the escape and char? (default=false)
	 * @return array Returns assoc array where keys are placeholders substituted in $str and values are escaped characters. 
	 * @since 3.0.162
	 * 
	 */
	public function findReplaceEscapeChars(&$str, array $escapeChars, array $options = []): array {

		$defaults = [
      'escapePrefix' => '\\',
      'restoreEscape' => false,
      // when restoring, also restore escape prefix?
      'gluePrefix' => '{ESC',
      'glueSuffix' => '}',
      'unescapeUnknown' => false,
      'removeUnknown' => false,
  ];

		$options = array_merge($defaults, $options);
		$escapePrefix = $options['escapePrefix'];
		if(!str_contains($str, (string) $escapePrefix)) return [];
		$escapes = [];
		$glueSuffix = $options['glueSuffix'];
		$parts = explode($escapePrefix, $str);
		$n = 0;

		do {
			$gluePrefix = $options['gluePrefix'] . $n;
		} while($this->strpos($str, $gluePrefix) !== false && ++$n);

		$str = array_shift($parts);

		foreach($parts as $part) {

			$len = $this->strlen($part);
			$char = $len > 0 ? $this->substr($part, 0, 1) : ''; // char being escaped
			$part = $len > 1 ? $this->substr($part, 1) : ''; // everything after it
			$charKey = array_search($char, $escapeChars); // find placeholder (glue)

			if($charKey !== false) {
				// replace escaped char with placeholder ($glue)
				$glue = $gluePrefix . $charKey . $glueSuffix;
				$escapes[$glue] = $options['restoreEscape'] ? $escapePrefix . $char : $char;
				$str .= $glue . $part;
			} else if($options['unescapeUnknown']) {
				// unescape unknown escape char
				$str .= $char . $part;
			} else if($options['removeUnknown']) {
				// remove unknown escape char
				$str .= $part;
			} else {
				// some other backslash that’s allowed, restore back as it was
				$str .= $escapePrefix . $char . $part;
			}
		}

		return $escapes;
	}
	
	/***********************************************************************************************************
	 * MULTIBYTE PHP STRING FUNCTIONS THAT FALLBACK WHEN MBSTRING NOT AVAILABLE
	 * 
	 * These duplicate the equivalent PHP string methods and use exactly the same arguments
	 * and exhibit exactly the same behavior. The only difference is that these methods using
	 * the multibyte string versions when they are available, and fallback to the regular PHP 
	 * string methods when not. Use these functions only when that behavior is okay. 
	 * 
	 */

	/**
	 * Get part of a string
	 * 
	 * #pw-group-PHP-function-alternates
	 * 
	 * @param string $str
	 * @param int $start
	 * @param int|null $length Max chars to use from str. If omitted or NULL, extract all characters to the end of the string.
	 * @return string
	 * @see https://www.php.net/manual/en/function.substr.php
	 * 
	 */
	public function substr($str, $start, $length = null): string {
		return $this->mb ? mb_substr($str, $start, $length) : substr($str, $start, $length);
	}

	/**
	 * Find position of first occurrence of string in a string
	 * 
	 * #pw-group-PHP-function-alternates
	 * 
	 * @param string $haystack
	 * @param string $needle
	 * @param int $offset
	 * @return bool|false|int
	 * @see https://www.php.net/manual/en/function.strpos.php
	 * 
	 */
	public function strpos($haystack, $needle, $offset = 0) {
		return $this->mb ? mb_strpos($haystack, $needle, $offset) : strpos($haystack, $needle, $offset);
	}

	/**
	 * Find the position of the first occurrence of a case-insensitive substring in a string
	 * 
	 * #pw-group-PHP-function-alternates
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @param int $offset
	 * @return bool|false|int
	 * @see https://www.php.net/manual/en/function.stripos.php
	 *
	 */
	public function stripos($haystack, $needle, $offset = 0) {
		return $this->mb ? mb_stripos($haystack, $needle, $offset) : stripos($haystack, $needle, $offset);
	}

	/**
	 * Find the position of the last occurrence of a substring in a string
	 * 
	 * #pw-group-PHP-function-alternates
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @param int $offset
	 * @return bool|false|int
	 * @see https://www.php.net/manual/en/function.strrpos.php
	 *
	 */
	public function strrpos($haystack, $needle, $offset = 0) {
		return $this->mb ? mb_strrpos($haystack, $needle, $offset) : strrpos($haystack, $needle, $offset);
	}

	/**
	 * Find the position of the last occurrence of a case-insensitive substring in a string
	 * 
	 * #pw-group-PHP-function-alternates
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @param int $offset
	 * @return bool|false|int
	 * @see https://www.php.net/manual/en/function.strripos.php
	 *
	 */
	public function strripos($haystack, $needle, $offset = 0) {
		return $this->mb ? mb_strripos($haystack, $needle, $offset) : strripos($haystack, $needle, $offset);
	}

	/**
	 * Get string length
	 * 
	 * #pw-group-PHP-function-alternates
	 * 
	 * @param string $str
	 * @return int
	 * @see https://www.php.net/manual/en/function.strlen.php
	 * 
	 */
	public function strlen($str): int {
		return $this->mb ? mb_strlen($str) : strlen($str);
	}

	/**
	 * Make a string lowercase
	 * 
	 * #pw-group-PHP-function-alternates
	 * 
	 * @param string $str
	 * @return string
	 * @see https://www.php.net/manual/en/function.strtolower.php
	 * 
	 */
	public function strtolower($str): string {
		return $this->mb ? mb_strtolower($str) : strtolower($str);
	}

	/**
	 * Make a string uppercase
	 * 
	 * #pw-group-PHP-function-alternates
	 *
	 * @param string $str
	 * @return string
	 * @see https://www.php.net/manual/en/function.strtoupper.php
	 *
	 */
	public function strtoupper($str): string {
		return $this->mb ? mb_strtoupper($str) : strtoupper($str);
	}

	/**
	 * Count the number of substring occurrences
	 * 
	 * #pw-group-PHP-function-alternates
	 * 
	 * @param string $haystack
	 * @param string $needle
	 * @return int
	 * @see https://www.php.net/manual/en/function.substr-count.php
	 * 
	 */
	public function substrCount($haystack, $needle): int {
		return $this->mb ? mb_substr_count($haystack, $needle) : substr_count($haystack, $needle); 
	}

	/**
	 * Find the first occurrence of a string
	 * 
	 * #pw-group-PHP-function-alternates
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @param bool $beforeNeedle Return part of haystack before first occurrence of the needle? (default=false)
	 * @return false|string
	 * @see https://www.php.net/manual/en/function.strstr.php
	 *
	 */
	public function strstr($haystack, $needle, $beforeNeedle = false) {
		return $this->mb ? mb_strstr($haystack, $needle, $beforeNeedle) : strstr($haystack, $needle, $beforeNeedle);
	}

	/**
	 * Find the first occurrence of a string (case insensitive)
	 * 
	 * #pw-group-PHP-function-alternates
	 * 
	 * @param string $haystack
	 * @param string $needle
	 * @param bool $beforeNeedle Return part of haystack before first occurrence of the needle? (default=false)
	 * @return false|string
	 * @see https://www.php.net/manual/en/function.stristr.php
	 * 
	 */
	public function stristr($haystack, $needle, $beforeNeedle = false) {
		return $this->mb ? mb_stristr($haystack, $needle, $beforeNeedle) : stristr($haystack, $needle, $beforeNeedle); 
	}


	/**
	 * Find the last occurrence of a character in a string
	 * 
	 * #pw-group-PHP-function-alternates
	 * 
	 * @param string $haystack
	 * @param string $needle Only first given character used
	 * @return false|string
	 * @see https://www.php.net/manual/en/function.strrchr.php
	 * 
	 */
	public function strrchr($haystack, $needle) {
		return $this->mb ? mb_strrchr($haystack, $needle) : strrchr($haystack, $needle); 
	}

	/**
	 * Strip whitespace (or other characters) from the beginning and end of a string
	 * 
	 * #pw-group-PHP-function-alternates
	 * 
	 * @param string $str
	 * @param string $chars Omit for default
	 * @return string
	 * 
	 */
	public function trim($str, $chars = '') {
		if(!$this->mb) return $chars === '' ? trim($str) : trim($str, $chars);
		return $this->wire()->sanitizer->trim($str, $chars);
	}

	/**
	 * Strip whitespace (or other characters) from the beginning of string only (aka left trim)
	 *
	 * #pw-group-PHP-function-alternates
	 *
	 * @param string $str
	 * @param string $chars Omit for default
	 * @return string
	 * @since 3.0.168
	 *
	 */
	public function ltrim($str, $chars = '') {
		if(!$this->mb) return $chars === '' ? ltrim($str) : ltrim($str, $chars);
		return $this->wire()->sanitizer->trim($str, $chars, 'ltrim');
	}
	
	/**
	 * Strip whitespace (or other characters) from the end of string only (aka right trim)
	 *
	 * #pw-group-PHP-function-alternates
	 *
	 * @param string $str
	 * @param string $chars Omit for default
	 * @return string
	 * @since 3.0.168
	 *
	 */
	public function rtrim($str, $chars = '') {
		if(!$this->mb) return $chars === '' ? rtrim($str) : rtrim($str, $chars);
		return $this->wire()->sanitizer->trim($str, $chars, 'rtrim');
	}
	

}
