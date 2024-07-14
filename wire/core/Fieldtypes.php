<?php namespace ProcessWire;

use Override;
use ReturnTypeWillChange;
/**
 * ProcessWire Fieldtypes
 *
 * #pw-summary Maintains a collection of Fieldtype modules.
 * #pw-var $fieldtypes
 * 
 * ProcessWire 3.x, Copyright 2020 by Ryan Cramer
 * https://processwire.com
 *
 * @property FieldtypeCheckbox $FieldtypeCheckbox
 * @property FieldtypeCheckbox $checkbox
 * 
 * @property FieldtypeComments|null $FieldtypeComments
 * @property FieldtypeComments|null $comments
 * 
 * @property FieldtypeDatetime $FieldtypeDatetime
 * @property FieldtypeDatetime $datetime
 * 
 * @property FieldtypeEmail $FieldtypeEmail
 * @property FieldtypeEmail $email
 * 
 * @property FieldtypeFile $file
 * @property FieldtypeFile $FieldtypeFile
 * 
 * @property FieldtypeFloat $FieldtypeFloat
 * @property FieldtypeFloat $float
 * 
 * @property FieldtypeImage $FieldtypeImage
 * @property FieldtypeImage $image
 * 
 * @property FieldtypeInteger $FieldtypeInteger
 * @property FieldtypeInteger $integer
 * 
 * @property FieldtypeModule $FieldtypeModule
 * @property FieldtypeModule $module
 * 
 * @property FieldtypeOptions|null $FieldtypeOptions
 * @property FieldtypeOptions|null $options
 * 
 * @property FieldtypePage $FieldtypePage
 * @property FieldtypePage $page
 * 
 * @property FieldtypePageTable $FieldtypePageTable
 * @property FieldtypePageTable $pageTable
 * 
 * @property FieldtypePageTitle $FieldtypePageTitle
 * @property FieldtypePageTitle $pageTitle
 * 
 * @property FieldtypePassword $FieldtypePassword
 * @property FieldtypePassword $password
 * 
 * @property FieldtypeRepeater|null $FieldtypeRepeater
 * @property FieldtypeRepeater|null $repeater
 * 
 * @property FieldtypeSelector|null $FieldtypeSelector
 * @property FieldtypeSelector|null $selector
 * 
 * @property FieldtypeText $FieldtypeText
 * @property FieldtypeText $text
 * 
 * @property FieldtypeTextarea $FieldtypeTextarea
 * @property FieldtypeTextarea $textarea
 * 
 * @property FieldtypeToggle|null $FieldtypeToggle
 * @property FieldtypeToggle|null $toggle
 * 
 * @property FieldtypeURL $FieldtypeURL
 * @property FieldtypeURL $URL
 *
 *
 */
class Fieldtypes extends WireArray {

	/**
	 * @var bool
	 * 
	 */
	protected $preloaded = false;

	/**
	 * Is this the $fieldtypes API var?
	 * 
	 * @var bool
	 * 
	 */
	protected $isAPI = false;

	/**
	 * Construct the $fieldtypes API var (load all Fieldtype modules into it)
	 *
 	 */
	public function init() {
		$this->isAPI = true;
		foreach($this->wire()->modules->findByPrefix('Fieldtype', 3) as $name => $module) {
			$this->add($module); 
		}
	}

	/**
	 * Convert all ModulePlaceholders to Fieldtype modules
	 * 
	 */
	protected function preload() {
		if($this->preloaded) return;
		$debug = $this->isAPI && $this->wire()->config->debug; 
		if($debug) Debug::timer('Fieldtypes.preload'); 
		$modules = $this->wire()->modules;
		foreach($this->data as $moduleName => $module) {
			if($module instanceof ModulePlaceholder) {
				$fieldtype = $modules->getModule($moduleName); 
				if($fieldtype) $this->data[$moduleName] = $fieldtype; 
			}
		}
		if($debug) Debug::saveTimer('Fieldtypes.preload'); 
		$this->preloaded = true; 
	}

	/**
	 * Per WireArray interface, items added to Fieldtypes must be Fieldtype instances
	 * 
	 * @param Wire|Fieldtype $item
	 * @return bool
	 *
	 */
	#[Override]
	public function isValidItem(mixed $item): bool {
		if($item instanceof Fieldtype) return true;
		if($item instanceof ModulePlaceholder && str_starts_with($item->className(), 'Fieldtype')) return true;
		return false;
	}

	/**
	 * Per the WireArray interface, keys must be strings (fieldtype class names)
	 * 
	 * @param string|int $key
	 * @return bool
	 *
	 */
	#[Override]
	public function isValidKey($key): bool {
		return is_string($key); 
	}

	/**
	 * Per the WireArray interface, Fields are indxed by their name
	 * 
	 * @param Fieldtype $item
	 * @return string
	 *
	 */
	#[Override]
 public function getItemKey($item) {
		return $item->className();
	}

	/**
	 * Does this WireArray use numeric keys only? 
	 *
	 * @return bool
	 *
	 */
	#[Override]
 protected function usesNumericKeys(): bool {
		return false;
	}

	/**
	 * Per the WireArray interface, return a blank copy
	 *
	 * Since Fieldtype is abstract, there is nothing but NULL to return here
	 * 
	 * @return null
	 *
	 */
	#[Override]
 public function makeBlankItem() {
		return null; 
	}

	/**
	 * Given a Fieldtype name (or class name) return the instantiated Fieldtype module. 
	 *
	 * If the requested Fieldtype is not already installed, it will be installed here automatically. 
	 *
	 * @param string $key Fieldtype name or class name, or dynamic property of Fieldtypes
	 * @return Fieldtype|null 
	 *
	 */
	#[Override]
 public function get($key) {

		if(stripos($key, 'Fieldtype') !== 0) $key = 'Fieldtype' . ucfirst($key); 

		$fieldtype = parent::get($key);
		
		if(!$fieldtype) {
			$fieldtype = $this->wire()->modules->getModule($key);
			if($fieldtype) $this->set($key, $fieldtype);
		}

		if($fieldtype instanceof ModulePlaceholder) {
			$fieldtype = $this->wire()->modules->getModule($fieldtype->className()); 			
			if($fieldtype) $this->set($key, $fieldtype); 
		}

		return $fieldtype; 
	}

	/**
	 * Below we account for all get() related functions in WireArray to preload the fieldtypes
	 * 
	 * This ensures there are no ModulePlaceholders present when results from any of these methods.
	 * 
	 */

	#[Override]
 public function getArray() { $this->preload(); return parent::getArray(); }
	#[Override]
 public function getAll() { $this->preload(); return parent::getAll(); }
	#[Override]
 public function getValues() { $this->preload(); return parent::getValues(); }
	#[Override]
 public function getRandom($num = 1, $alwaysArray = false) { $this->preload(); return parent::getRandom($num, $alwaysArray);  }
	#[Override]
 public function slice($start, $limit = 0) { $this->preload(); return parent::slice($start, $limit);  }
	#[Override]
 public function shift() { $this->preload(); return parent::shift(); }
	#[Override]
 public function pop() { $this->preload(); return parent::pop(); }
	#[Override]
 public function eq($num) { $this->preload(); return parent::eq($num); }
	#[Override]
 public function first() { $this->preload(); return parent::first(); }
	#[Override]
 public function last() { $this->preload(); return parent::last(); }
	#[Override]
 public function sort($properties, $flags = null) { $this->preload(); return parent::sort($properties, $flags); }
	#[Override]
 protected function filterData($selectors, $not = false) { $this->preload(); return parent::filterData($selectors, $not); }
	#[Override]
 public function makeCopy() { $this->preload(); return parent::makeCopy(); }
	#[Override]
 public function makeNew() { $this->preload(); return parent::makeNew(); }
	#[ReturnTypeWillChange]
 #[Override] 
	public function getIterator() { $this->preload(); return parent::getIterator(); }
	#[Override]
 public function getNext($item, $strict = true) { $this->preload(); return parent::getNext($item, $strict); }
	#[Override]
 public function getPrev($item, $strict = true) { $this->preload(); return parent::getPrev($item, $strict); }
}
