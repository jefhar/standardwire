<?php namespace ProcessWire;

/**
 * ProcessWire ConfigModule interface
 * 
 * See notes about this interface and its differences in the ConfigurableModule documentation.
 * 
 * @since 3.0.179
 *
 */
interface ConfigModule {
	public function __get($key);
	public function __set($key, $value);
}
