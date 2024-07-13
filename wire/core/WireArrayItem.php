<?php namespace ProcessWire;

/**
 * Interface indicates item stores in a WireArray or type descending from it 
 * 
 * @since 3.0.205
 * 
 */
interface WireArrayItem {
	/**
	 * @return WireArray
	 * 
	 */
	public function getWireArray(); 
}
