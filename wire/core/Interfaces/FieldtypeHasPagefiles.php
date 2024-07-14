<?php namespace ProcessWire\Interfaces;

use ProcessWire\Field;
use ProcessWire\Page;
use ProcessWire\Pagefile;
use ProcessWire\Pagefiles;

/**
 * Indicates Fieldtype manages Pagefile/Pageimage objects
 * 
 */
interface FieldtypeHasPagefiles {
	
	/**
	 * Get Pagefiles
	 * 
	 * @param Page $page
	 * @param Field $field
	 * @return Pagefiles|Pagefile[]
	 * 
	 */
	public function getPagefiles(Page $page, Field $field); 
}
