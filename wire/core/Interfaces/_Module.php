<?php namespace ProcessWire\Interfaces;

/**
 * Standard module interface with all methods. 
 * 
 * This interface is not intended to be used for anything other than for code hinting purposes. 
 *
 */
interface _Module {
	
	public function install();
	public function uninstall();
	public function upgrade($fromVersion, $toVersion);
	
	/** @return array */
	public static function getModuleInfo();
	
	public function init();
	
	public function ready();
	
	public function setConfigData(array $data);
	
	/** @return bool */
	public function isSingular();
	
	/** @return bool */
	public function isAutoload();

	/**
	 * @param InputfieldWrapper|array|null $data
	 * @return InputfieldWrapper
	 * 
	 */
	public function getModuleConfigInputfields($data = null);

	/**
	 * @return array
	 * 
	 */
	public function getModuleConfigArray();
}
