<?php namespace ProcessWire;

/**
 * Interface WireMatchable
 * 
 * Interface for objects that provide their own matches() method for matching selector strings
 * 
 */
interface WireMatchable {
	
	/**
	 * Does this object match the given Selectors object or string?
	 * 
	 * @param Selectors|string $s
	 * @return bool
	 * 
	 */
	public function matches($s); 
}
