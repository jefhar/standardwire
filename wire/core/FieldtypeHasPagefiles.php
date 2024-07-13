<?php namespace ProcessWire;

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
