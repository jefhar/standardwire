<?php namespace ProcessWire;

/**
 * Interface for tracking runtime events
 *
 */
interface WireProfilerInterface {
	
	/**
	 * Start profiling an event
	 * 
	 * Return the event array to be used for stop profiling
	 * 
	 * @param string $name Name of event in format "method" or "method.id" or "something"
	 * @param Wire|object|string|null Source of event (may be object instance)
	 * @param array $data
	 * @return mixed Event to be used for stop call
	 * 
	 */
	public function start($name, $source = null, $data = array()); 

	/**
	 * Stop profiling an event
	 * 
	 * @param array|object|string $event Event returned by start() 
	 * @return void
	 * 
	 */
	public function stop($event);

	/**
	 * End of request maintenance
	 * 
	 * @return void
	 * 
	 */
	public function maintenance();
}
