<?php namespace ProcessWire;

/**
 * For classes that may have their data exported to an array 
 * 
 * Classes implementing this interface are also assumed to be able to accept the same 
 * 
 * 
 */ 
interface Exportable {
	
	/**
	 * Return export data (may be the same as getTableData from Saveable interface)
	 *
	 * @return array
	 *
	 */
	public function getExportData();

	/**
	 * Given an export data array, import it back to the class and return what happened
	 * 
	 * @param array $data
	 * @return array Returns array(
	 * 	[property_name] => array(
	 * 		'old' => 'old value',	// old value, always a string
	 * 		'new' => 'new value',	// new value, always a string
	 * 		'error' => 'error message or blank if no error'
	 * 	)
	 * 
	 */
	public function setImportData(array $data);

}
