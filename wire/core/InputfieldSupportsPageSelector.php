<?php namespace ProcessWire;

/**
 * Inputfield that supports a Page selector for selectable options
 *
 * @since 3.0.176
 *
 */
interface InputfieldSupportsPageSelector {
	/**
	 * Set page selector or test if feature is disabledd
	 * 
	 * @param string $selector Selector string or blank string when testing if feature is disabled
	 * @return bool Return boolean false if feature disabled, otherwise boolean true
	 * 
	 */
	public function setPageSelector($selector);
}
