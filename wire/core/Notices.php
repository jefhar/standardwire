<?php namespace ProcessWire;

use Override;
/**
 * ProcessWire Notices
 * 
 * #pw-summary A class to contain multiple Notice instances, whether messages, warnings or errors
 * #pw-body =
 * This class manages notices that have been sent by `Wire::message()`, `Wire::warning()` and `Wire::error()` calls. 
 * The message(), warning() and error() methods are available on every `Wire` derived object. This class is primarily
 * for internal use in the admin. However, it may also be useful in some front-end contexts. 
 * ~~~~~
 * // Adding a NoticeMessage using object syntax
 * $notices->add(new NoticeMessage("Hello World"));
 * 
 * // Adding a NoticeMessage using regular syntax
 * $notices->message("Hello World"); 
 * 
 * // Adding a NoticeWarning, and allow markup in it
 * $notices->message("Hello <strong>World</strong>", Notice::allowMarkup); 
 * 
 * // Adding a NoticeError that only appears if debug mode is on
 * $notices->error("Hello World", Notice::debug); 
 * ~~~~~
 * Iterating and outputting Notices:
 * ~~~~~
 * foreach($notices as $notice) {
 *   // skip over debug notices, if debug mode isn't active
 *   if($notice->flags & Notice::debug && !$config->debug) continue;
 *   // entity encode notices unless the allowMarkup flag is set
 *   if($notice->flags & Notice::allowMarkup) {
 *     $text = $notice->text; 
 *   } else {
 *     $text = $sanitizer->entities($notice->text);
 *   }
 *   // output either an error, warning or message notice
 *   if($notice instanceof NoticeError) {
 *     echo "<p class='error'>$text</p>";
 *   } else if($notice instanceof NoticeWarning) {
 *     echo "<p class='warning'>$text</p>";
 *   } else {
 *     echo "<p class='message'>$text</p>";
 *   }
 * }
 * ~~~~~
 *
 * #pw-body
 * 
 *
 */
class Notices extends WireArray {
	
	public const logAllNotices = false;  // for debugging/dev purposes

	/**
	 * Initialize Notices API var
	 * 
	 * #pw-internal
	 * 
	 */
	public function init() {
		// @todo 
		// $this->loadStoredNotices();
	}

	/**
	 * #pw-internal
	 * 
	 * @param mixed $item
	 * @return bool
	 * 
	 */
	#[Override]
 public function isValidItem($item) {
		return $item instanceof Notice; 
	}

	/**
	 * #pw-internal
	 *
	 * @return Notice
	 *
	 */
	#[Override]
 public function makeBlankItem() {
		return $this->wire(new NoticeMessage('')); 
	}

	/**
	 * Allow given Notice to be added?
	 * 
	 * @param Notice $item
	 * @return bool
	 * 
	 */
	protected function allowNotice(Notice $item) {
		
		$user = $this->wire()->user;
		
		if($item->flags & Notice::debug) {
			if(!$this->wire()->config->debug) return false;
		}

		if($item->flags & Notice::superuser) {
			if(!$user || !$user->isSuperuser()) return false;
		}
		
		if($item->flags & Notice::login) {
			if(!$user || !$user->isLoggedin()) return false;
		}
		
		if($item->flags & Notice::admin) {
			$page = $this->wire()->page;
			if(!$page || !$page->template || $page->template->name != 'admin') return false;
		}
	
		if($item->flags & Notice::allowDuplicate) {
			// allow it
		} else if($this->isDuplicate($item)) {
			$item->qty = $item->qty+1;
			return false;
		}
		
		if(self::logAllNotices || ($item->flags & Notice::log) || ($item->flags & Notice::logOnly)) {
			$this->addLog($item);
			$item->flags = $item->flags & ~Notice::log; // remove log flag, to prevent it from being logged again
			if($item->flags & Notice::logOnly) return false;
		}

		return true;
	}

	/**
	 * Format Notice text
	 * 
	 * @param Notice $item
	 * 
	 */
	protected function formatNotice(Notice $item) {
		$text = $item->text;
		$label = '';
		
		if(is_array($text)) {
			// if text is associative array with 1 item, we consider the 
			// key to be the notice label and value to be the notice text
			if(count($text) === 1) {
				$value = reset($text);
				$key = key($text);
				if(is_string($key)) {
					$label = $key;
					$text = $value;
					$item->text = $text;
					if($this->wire()->config->debug) {
						$item->class = $label;
						$label = '';
					}
				}
			}	
		}
		
		if(is_object($text) || is_array($text)) {
			$text = Debug::toStr($text, ['html' => true]);
			$item->flags = $item->flags | Notice::allowMarkup;
			$item->text = $text;
		}
		
		if($item->hasFlag('allowMarkdown')) {
			$item->text = $this->wire()->sanitizer->entitiesMarkdown($text, ['allowBrackets' => true]); 
			$item->addFlag('allowMarkup');
			$item->removeFlag('allowMarkdown'); 
		}
		
		if($label) {
			if($item->hasFlag('allowMarkup')) {
				$label = $this->wire()->sanitizer->entities($label);
				$item->text = "<strong>$label:</strong> $item->text";
			} else {
				$item->text = "$label: \n$item->text";
			}
		}
	}

	/**
	 * Add a Notice object
	 * 
	 * ~~~~
	 * $notices->add(new NoticeError("An error occurred!"));
	 * ~~~~
	 * 
	 * @param Notice $item
	 * @return Notices|WireArray
	 * 
	 */
	#[Override]
 public function add($item) {
		
		if(!($item instanceof Notice)) {
			$item = new NoticeError("You attempted to add a non-Notice object to \$notices: $item", Notice::debug); 
		}
		
		if(!$this->allowNotice($item)) return $this;

		$item->qty = $item->qty+1;
		$this->formatNotice($item);

		if($item->flags & Notice::anonymous) {
			$item->set('class', '');
		}
		
		if($item->flags & Notice::persist) {
			$this->storeNotice($item);
		}
		
		if($item->flags & Notice::prepend) {
			return parent::prepend($item);	
		} else {
			return parent::add($item);
		}
	}

	/**
	 * Store a persist Notice in Session
	 * 
	 * @param Notice $item
	 * @return bool
	 *
	 */
	protected function storeNotice(Notice $item) {
		$session = $this->wire()->session;
		if(!$session) return false;
		$items = $session->getFor($this, 'items');
		if(!is_array($items)) $items = [];
		$str = $this->noticeToStr($item);
		$idStr = $item->getIdStr();
		if(isset($items[$idStr])) return false;
		$items[$idStr] = $str;
		$session->setFor($this, 'items', $items);
		return true;
	}

	/**
	 * Load persist Notices stored in Session
	 * 
	 * @return int Number of Notices loaded
	 * 
	 */
	protected function loadStoredNotices() {
		
		$session = $this->wire()->session;
		$items = $session->getFor($this, 'items');
		$qty = 0;
		
		if(empty($items) || !is_array($items)) return $qty;
		
		foreach($items as $idStr => $str) {
			if(!is_string($str)) continue;
			$item = $this->strToNotice($str);
			if(!$item) continue;
			$persist = $item->hasFlag(Notice::persist) ? Notice::persist : 0;
			// temporarily remove persist flag so Notice does not get re-stored when added
			if($persist) $item->removeFlag($persist);
			$this->add($item);
			if($persist) $item->addFlag($persist);
			$item->set('_idStr', $idStr);
			$qty++;
		}
	
		return $qty;
	}

	/**
	 * Remove a Notice
	 * 
	 * Like the remove() method but also removes persist notices. 
	 * 
	 * @param string|Notice $item Accepts a Notice object or Notice ID string.
	 * @return self
	 * @since 3.0.149
	 * 
	 */
	public function removeNotice($item) {
		if($item instanceof Notice) {
			$idStr = $item->get('_idStr|idStr'); 
		} else if(is_string($item)) {
			$idStr = $item;
			$item = $this->getByIdStr($idStr); 
		} else {
			return $this;
		}
		if($item) parent::remove($item);
		$session = $this->wire()->session;
		$items = $session->getFor($this, 'items');
		if(is_array($items) && isset($items[$idStr])) {
			unset($items[$idStr]);
			$session->setFor($this, 'items', $items);
		}
		return $this;
	}

	/**
	 * Is the given Notice a duplicate of one already here?
	 * 
	 * @param Notice $item
	 * @return bool|Notice Returns Notice that it duplicate sor false if not a duplicate
	 * 
	 */
	protected function isDuplicate(Notice $item) {
		$duplicate = false;
		foreach($this as $notice) {
			/** @var Notice $notice */
			if($notice === $item) {
				$duplicate = $notice;
				break;
			}
			if($notice->className() === $item->className() && $notice->flags === $item->flags
				&& $notice->icon === $item->icon && $notice->text === $item->text) {
				$duplicate = $notice;
				break;
			}
		}
		return $duplicate;
	}

	/**
	 * Add Notice to log
	 * 
	 * @param Notice $item
	 * 
	 */
	protected function addLog(Notice $item) {
		$text = $item->text;
		if(str_contains($text, '&')) {
			$text = $this->wire()->sanitizer->unentities($text);
		}
		if($this->wire()->config->debug && $item->class) $text .= " ($item->class)"; 
		$this->wire()->log->save($item->getName(), $text); 
	}

	/**
	 * Are there NoticeError items present?
	 * 
	 * @return bool
	 * 
	 */
	public function hasErrors() {
		$numErrors = 0;
		foreach($this as $notice) {
			if($notice instanceof NoticeError) $numErrors++;
		}
		return $numErrors > 0;
	}

	/**
	 * Are there NoticeWarning items present?
	 * 
	 * @return bool
	 * 
	 */
	public function hasWarnings() {
		$numWarnings = 0;
		foreach($this as $notice) {
			if($notice instanceof NoticeWarning) $numWarnings++;
		}
		return $numWarnings > 0;
	}

	/**
	 * Recursively entity encoded values in arrays and convert objects to string
	 * 
	 * This enables us to safely print_r the string for debugging purposes 
	 * 
	 * #pw-internal
	 * 
	 * @param array $a
	 * @return array
	 * 
	 */
	public function sanitizeArray(array $a) {
		$sanitizer = $this->wire()->sanitizer; 
		$b = [];
		foreach($a as $key => $value) {
			if(is_array($value)) {
				$value = $this->sanitizeArray($value);
			} else {
				if(is_object($value)) {
					if($value instanceof Wire) {
						$value = (string) $value;
						$class = wireClassName($value);
						if($value !== $class) $value = "object:$class($value)";
					} else {
						$value = 'object:' . wireClassName($value);
					}
				}
				$value = $sanitizer->entities($value); 
			} 
			$key = $sanitizer->entities($key);
			$b[$key] = $value;
		}
		return $b; 
	}

	/**
	 * Move notices from one Wire instance to another
	 * 
	 * @param Wire $from
	 * @param Wire $to
	 * @param array $options Additional options:
	 *  - `types` (array): Types to move (default=['messages','warnings','errors'])
	 *  - `prefix` (string): Optional prefix to add to moved notices text (default='')
	 *  - `suffix` (string): Optional suffix to add to moved notices text (default='')
	 * @return int Number of notices moved
	 * 
	 */
	public function move(Wire $from, Wire $to, array $options = []) {
		$n = 0;
		$types = $options['types'] ?? ['errors', 'warnings', 'messages']; 
		foreach($types as $type) {
			$method = rtrim((string) $type, 's');
			foreach($from->$type('clear') as $notice) {
				$text = $notice->text; 
				if(isset($options['prefix'])) $text = "$options[prefix]$text";
				if(isset($options['suffix'])) $text = "$text$options[suffix]";
				$to->$method($text, $notice->flags);
				$n++;
			}
		}
		return $n;
	}

	/**
	 * Get a Notice by ID string
	 * 
	 * #pw-internal
	 * 
	 * @param string $idStr
	 * @return Notice|null
	 * @since 3.0.149
	 * 
	 */
	protected function getByIdStr($idStr) {
		$notice = null;	
		if(strlen($idStr) < 33) return null;
		$prefix = substr($idStr, 0, 1);
		foreach($this as $item) {
			/** @var Notice $item */
			if(!str_starts_with($item->className(), $prefix)) continue;
			if($item->getIdStr() !== $idStr) continue;
			$notice = $item;
			break;
		}
		return $notice;
	}

	/**
	 * Export Notice object to string
	 * 
	 * #pw-internal
	 * 
	 * @param Notice $item
	 * @return string
	 * @since 3.0.149
	 * 
	 */
	protected function noticeToStr(Notice $item) {
		$type = str_replace('Notice', '', $item->className());
		$a = ['type' => $type, 'flags' => $item->flags, 'timestamp' => $item->timestamp, 'class' => $item->class, 'icon' => $item->icon, 'text' => $item->text];
		return implode(';', $a);
	}

	/**
	 * Import Notice object from string
	 * 
	 * #pw-internal
	 * 
	 * @param string $str
	 * @return Notice|null
	 * @since 3.0.149
	 * 
	 */
	protected function strToNotice($str) {
		if(substr_count($str, ';') < 5) return null;
		[$type, $flags, $timestamp, $class, $icon, $text] = explode(';', $str, 6);
		$type = __NAMESPACE__ . "\\Notice$type";
		if(!wireClassExists($type)) return null;
		/** @var Notice $item */
		$item = new $type($text, (int) $flags);
		$item->setArray(['timestamp' => (int) $timestamp, 'class' => $class, 'icon' => $icon]);
		return $item;
	}
}
