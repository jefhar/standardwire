<?php namespace ProcessWire\Interfaces;

use ProcessWire\Field;
use ProcessWire\Page;

/**
 * Indicates Fieldtype manages files
 *
 */
interface FieldtypeHasFiles {
	/**
	 * Whether or not given Page/Field has any files connected with it
	 * 
	 * @param Page $page
	 * @param Field $field
	 * @return bool
	 * 
	 */
	public function hasFiles(Page $page, Field $field);
	
	/**
	 * Get array of full path/file for all files managed by given page and field
	 * 
	 * @param Page $page
	 * @param Field $field
	 * @return array
	 * 
	 */
	public function getFiles(Page $page, Field $field);

	/**
	 * Get path where files are (or would be) stored
	 * 
	 * @param Page $page
	 * @param Field $field
	 * @return string
	 * 
	 */
	public function getFilesPath(Page $page, Field $field); 
}
