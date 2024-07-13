<?php namespace ProcessWire;

/**
 * Indicates Fieldtype manages Pageimage objects
 *
 */
interface FieldtypeHasPageimages {

	/**
	 * Get Pageimages
	 *
	 * @param Page $page
	 * @param Field $field
	 * @return Pageimages|Pageimage[]
	 *
	 */
	public function getPageimages(Page $page, Field $field);
}
