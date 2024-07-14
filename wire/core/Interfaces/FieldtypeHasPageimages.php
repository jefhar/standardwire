<?php namespace ProcessWire\Interfaces;

use ProcessWire\Field;
use ProcessWire\Page;
use ProcessWire\Pageimage;
use ProcessWire\Pageimages;

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
