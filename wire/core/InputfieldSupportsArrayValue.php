<?php namespace ProcessWire;

/**
 * Inputfield that doesn’t have an array value by default but can return array value or accept it
 * 
 * @since 3.0.176
 *
 */
interface InputfieldSupportsArrayValue {
	/**
	 * @return array
	 * 
	 */
	public function getArrayValue();

	/**
	 * @param array $value
	 * 
	 */
	public function setArrayValue(array $value);
}
