<?php namespace ProcessWire;

/**
 * Interface LanguagesValueInterface
 * 
 * Interface for multi-language fields
 * 
 */
interface LanguagesValueInterface {

	/**
	 * Sets the value for a given language
	 *
	 * @param int|Language $languageID
	 * @param mixed $value
	 *
	 */
	public function setLanguageValue($languageID, $value);

	/**
	 * Given a language, returns the value in that language
	 *
	 * @param Language|int
	 * @return string|mixed
	 *
	 */
	public function getLanguageValue($languageID);

	/**
	 * Given an Inputfield with multi language values, this grabs and populates the language values from it
	 *
	 * @param Inputfield $inputfield
	 *
	 */
	public function setFromInputfield(Inputfield $inputfield);

}
