<?php namespace ProcessWire;

/**
 * Class that provides information about selectors for Fieldtypes
 *
 * This is primarily a helper for the base Fieldtype class getSelectorInfo method
 *
 * This originated with the InputfieldSelector module and the need for Fieldtypes to
 * provide information about what properties can be selected, what operators, are used,
 * and so on. In the future this class will likely come in handy in providing selector
 * validation and improved help and error messaging when building/testing selectors. 
 * 
 * This file is licensed under the MIT license
 * https://processwire.com/about/license/mit/
 *
 * ProcessWire 3.x, Copyright 2023 by Ryan Cramer
 * https://processwire.com
 * 
 */
class FieldSelectorInfo extends Wire {

	/**
	 * Template of info, as returned by getSelectorInfo()
	 *
	 */
	protected $infoTemplate = [];

	/**
	 * Operators indexed by input type
	 *
	 */
	protected $operators = [];

	/**
	 * Labels for all the above operators
	 *
	 */
	protected $operatorLabels = [];

	/**
	 * CSV keywords from schema mapped to input types to auto-determine input type from schema
	 *
	 */
	protected $schemaToInput = [];

	/**
	 * Construct
	 * 
	 */
	public function __construct() {
		parent::__construct();

		$ftNops = [];
		$ftOps = Selectors::getOperators(['compareType' => Selector::compareTypeFind, 'getValueType' => 'operator', 'getIndexType' => 'none']);
		
		foreach($ftOps as $op) {
			$ftNops[] = "!$op";
		}
		
		$this->operators = ['number' => ['=', '!=', '>', '<', '>=', '<=', '=""', '!=""'], 'text' => ['=', '!=', '%=', '%^=', '%$=', '=""', '!=""'], 'fulltext' => array_merge($ftOps, ['=', '!=', '=""', '!=""'], $ftNops), 'select' => ['=', '!=']];

		$this->infoTemplate = [
      // name of the field
      'name' => '',
      // label for this field
      'label' => '',
      // operators accepted
      'operators' => $this->operators['number'],
      // type of input required: text, number, date, datetime, select, page, none
      'input' => 'number',
      // optional text hint about what to provide for input (for user)
      'hint' => '',
      // when input=select, page or checkbox, this contains the selectable options (value => label)
      'options' => [],
      // if field has subfields, this contains array of all above, indexed by subfield name (blank if not applicable)
      'subfields' => [],
  ];

		$this->schemaToInput = ['TEXT,TINYTEXT,MEDIUMTEXT,LONGTEXT,VARCHAR,CHAR' => 'text', 'DATETIME,TIMESTAMP' => 'datetime', 'DATE' => 'date', 'INT,DECIMAL,FLOAT,DOUBLE' => 'number', 'ENUM,SET' => 'select'];
	}

	/**
	 * Return array with information about what properties and operators can be used with this field
	 * 
	 * @param Field $field
	 * @return array
	 *
	 */
	public function getSelectorInfo(Field $field) {

		$info = $this->infoTemplate; 
		$info['name'] = $field->name; 
		$info['label'] = $field->label ?: $field->name; 
		$schema = $field->type->getDatabaseSchema($field);

		foreach($schema as $name => $schemaType) {

			// skip over native properties that aren't used in selectors
			if(in_array($name, ['pages_id', 'keys', 'xtra', 'sort'])) continue; 
			if(!is_string($schemaType)) continue;

			if($name == 'data') {
				// base property
				$target =& $info;
			} else {
				// subfield property
				$info['subfields'][$name] = $this->infoTemplate; 
				$target =& $info['subfields'][$name]; 
				$target['name'] = $name; 
				$target['label'] = $name; 
			}

			// determine the 'input' type based on the DB schema definition
			foreach($this->schemaToInput as $types => $input) {
				foreach(explode(',', $types) as $type) {
					if(stripos($schemaType, $type) !== 0) continue; 
					$target['input'] = $input; 
					break;
				}
			}

			// determine the operators based on the $input
			$input = $target['input'];
			if(isset($this->operators[$input])) $target['operators'] = $this->operators[$input]; 

			// determine selectable options if schema uses ENUM or SET fields
			if($input == 'select' && stripos($schemaType, 'ENUM(') !== false || stripos($schemaType, 'SET(') !== false) {
				if(preg_match('/^(ENUM|SET)\s*\(([^)]+)\)/i', $schemaType, $matches)) {
					$options = [];
					foreach(explode(',', $matches[2]) as $option) {
						$option = trim($option, '\'" '); 
						$options[$option] = $option; 
					}
					$target['options'] = $options; 
				}
			}

			// use fulltext operators if schema uses a fulltext index	
			if(isset($schema['keys'][$name])) {
				if(stripos($schema['keys'][$name], 'FULLTEXT') !== false) {
					$target['operators'] = $this->operators['fulltext'];
				}
			}
		}

		// if there are subfields, add the 'data' property back in there
		if(count($info['subfields'])) {
			$copy = $info; 
			$copy['subfields'] = [];
			$info['subfields']['data'] = $copy; 
		}

		return $info;
	}

	/**
	 * Return the default selector info template array
	 *
	 * @return array
	 *
	 */
	public function getSelectorInfoTemplate() {
		return $this->infoTemplate; 
	}

	/**
	 * Get array of operators
	 *
	 * @param string $inputType Specify: number, text, fulltext or select, or omit to return all possible operators at once
	 * @return array of operators or blank array if invalid type specified
	 *
	 */
	public function getOperators($inputType = '') {
		if(empty($inputType)) {
			$operators = []; 
			foreach($this->operators as $o) $operators = array_merge($operators, $o); 
			return $operators; 
		}
		return $this->operators[$inputType] ?? [];
	}

	/**
	 * Get array of operators mapped to text labels
	 *
	 * @return array 
	 *
	 */
	public function getOperatorLabels() {
		if(!empty($this->operatorLabels)) return $this->operatorLabels;
		$this->operatorLabels = Selectors::getOperators(['getIndexType' => 'operator', 'getValueType' => 'label']);
		$this->operatorLabels['=""'] = $this->_('Is Empty');
		$this->operatorLabels['!=""'] = $this->_('Is Not Empty');
		foreach($this->operators as $operator) {
			if(isset($this->operatorLabels[$operator])) continue; 
			if(!str_starts_with($operator, '!')) continue;
			$op = ltrim($operator, '!'); 
			$this->operatorLabels[$operator] = sprintf($this->_('Not: %s'), $this->operatorLabels[$op]); 
		}
		return $this->operatorLabels;
	}
}
