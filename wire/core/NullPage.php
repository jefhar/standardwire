<?php namespace ProcessWire;

/**
 * ProcessWire NullPage
 * 
 * #pw-summary NullPage is a type of Page object returned by many API methods to indicate a non-match. 
 * #pw-body = 
 * The simplest way to detect a NullPage is typically by checking the value of the `$page->id` property.
 * If it happens to be 0 then for most practical purposes, you have a NullPage. A NullPage object
 * has all of the same methods and properties as a regular `Page` but there's not much point in 
 * calling upon them since they will always be empty. 
 * ~~~~~
 * $item = $pages->get("featured=1"); 
 * 
 * if(!$item->id) {
 *   // this is a NullPage
 * }
 * 
 * if($item instanceof NullPage) {
 *   // this is a NullPage
 * }
 * ~~~~~
 * #pw-body
 *
 * Placeholder class for non-existant and non-saveable Page.
 * Many API functions return a NullPage to indicate no match. 
 *
 * ProcessWire 3.x, Copyright 2023 by Ryan Cramer
 * https://processwire.com
 * 
 * @property int $id The id property will always be 0 for a NullPage. 
 *
 */

class NullPage extends Page implements WireNull {
	/**
	 * #pw-internal
	 * 
	 * @return string
	 * 
	 */
	#[\Override]
 public function path() { return ''; }

	/**
	 * #pw-internal
	 * 
	 * @param array $options
	 * @return string
	 * 
	 */
	#[\Override]
 public function url($options = []) { return ''; }

	/**
	 * #pw-internal
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return $this
	 * 
	 */
	#[\Override]
 public function set($key, $value) { return parent::setForced($key, $value); }

	/**
	 * #pw-internal
	 * 
	 * @param string $selector
	 * @return null
	 * 
	 */
	#[\Override]
 public function parent($selector = '') { return null; }

	/**
	 * #pw-internal
	 * 
	 * @param string $selector
	 * @return PageArray
	 * @throws WireException
	 * 
	 */
	#[\Override]
 public function parents($selector = '') { 
		return $this->wire()->pages->newPageArray(); 
	}

	/**
	 * #pw-internal
	 * 
	 * @return string
	 * 
	 */
	#[\Override]
 public function __toString(): string { return ""; }

	/**
	 * #pw-internal
	 * 
	 * @return bool
	 * 
	 */
	#[\Override]
 public function isHidden() { return true; }

	/**
	 * #pw-internal
	 *
	 * @return bool
	 *
	 */
	#[\Override]
 public function isNew() { return false; }

	/**
	 * #pw-internal
	 * 
	 * @return null
	 * 
	 */
	#[\Override]
 public function filesManager() { return null; }

	/**
	 * #pw-internal
	 * 
	 * @return NullPage
	 * @throws WireException
	 * 
	 */
	#[\Override]
 public function ___rootParent() { 
		return $this->wire()->pages->newNullPage(); 
	}

	/**
	 * #pw-internal
	 * 
	 * @param string $selector
	 * @param bool $includeCurrent
	 * @return PageArray
	 * @throws WireException
	 * 
	 */
	#[\Override]
 public function siblings($selector = '', $includeCurrent = true) { 
		return $this->wire()->pages->newPageArray(); 
	}

	/**
	 * #pw-internal
	 * 
	 * @param string $selector
	 * @param array $options
	 * @return PageArray
	 * @throws WireException
	 * 
	 */
	#[\Override]
 public function children($selector = '', $options = []) { 
		return $this->wire()->pages->newPageArray(); 
	}

	/**
	 * #pw-internal
	 * 
	 * @param string $type
	 * @return NullPage
	 * @throws WireException
	 * 
	 */
	#[\Override]
 public function getAccessParent($type = 'view') { 
		return $this->wire()->pages->newNullPage(); 
	}

	/**
	 * #pw-internal
	 * 
	 * @param string $type
	 * @return PageArray
	 * @throws WireException
	 * 
	 */
	#[\Override]
 public function getAccessRoles($type = 'view') { 
		return $this->wire()->pages->newPageArray(); 
	}

	/**
	 * #pw-internal
	 * 
	 * @param int|Role|string $role
	 * @param string $type
	 * @return bool
	 * 
	 */
	#[\Override]
 public function hasAccessRole($role, $type = 'view') { return false; }

	/**
	 * #pw-internal
	 * 
	 * @param string $what
	 * @return bool
	 * 
	 */
	#[\Override]
 public function isChanged($what = '') { return false; }
}
