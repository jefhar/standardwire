<?php namespace ProcessWire\Interfaces;

use ProcessWire\Language;

/**
 * Inputfield that supports selectable options
 * 
 * @since 3.0.176
 *
 */
interface InputfieldHasSelectableOptions {
	/**
	 * Add a selectable option
	 * 
	 * @param string|int $value
	 * @param string|null $label
	 * @param array|null $attributes
	 * @return self|$this
	 * 
	 */
	public function addOption($value, $label = null, array $attributes = null);
	
	/**
	 * Add selectable option with label, optionally for specific language
	 *
	 * @param string|int $value
	 * @param string $label
	 * @param Language|null $language
	 * @return self|$this
	 *
	 */
	public function addOptionLabel($value, $label, $language = null);
}
