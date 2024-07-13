<?php namespace ProcessWire;

/** 
 * For classes that are saved to a database or disk.
 *
 * Item must have a gettable/settable 'id' property for this interface as well
 * 
 * @property int $id
 * @property string $name
 *
 */
interface Saveable {

	/**
	 * Save the object's current state to database.
	 *
	 */
	public function save(); 

	/**
	 * Get an array of this item's saveable data, should match exact with the table it saves in
	 * 
	 * @return array
	 *
	 */
	public function getTableData();

}
