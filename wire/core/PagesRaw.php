<?php namespace ProcessWire;

/**
 * ProcessWire Pages Raw Tools
 *
 * ProcessWire 3.x, Copyright 2022 by Ryan Cramer
 * https://processwire.com
 *
 */

class PagesRaw extends Wire {

	/**
	 * @var Pages
	 *
	 */
	protected $pages;

	/**
	 * Construct
	 *
	 * @param Pages $pages
	 *
	 */
	public function __construct(Pages $pages) {
		parent::__construct();
		$this->pages = $pages;
	}

	/**
	 * Find pages and return raw data from them in a PHP array
	 * 
	 * @param string|array|Selectors $selector
	 * @param string|array|Field $field Name of field/property to get, or array of them, CSV string, or omit to get all (default='')
	 *  - Optionally use associative array to rename fields in returned value, i.e. `['title' => 'label']` returns 'title' as 'label' in return value.
	 *  - Specify `parent.field_name` or `parent.parent.field_name`, etc. to return values from parent(s). 3.0.193+
	 *  - Specify `references` or `references.field_name`, etc. to also return values from pages referencing found pages. 3.0.193+
	 *  - Specify `meta` or `meta.name` to also return values from page meta data. 3.0.193+
	 * @param array $options See options for Pages::find
	 *  - `objects` (bool): Use objects rather than associative arrays? (default=false)
	 *  - `entities` (bool|array): Entity encode string values? True, or specify array of field names. (default=false)
	 *  - `nulls` (bool): Populate nulls for field values that are not present, rather than omitting them? (default=false) 3.0.198+
	 *  - `indexed` (bool): Index by page ID? (default=true)
	 *  - `flat` (bool|string): Flatten return value as `["field.subfield" => "value"]` rather than `["field" => ["subfield" => "value"]]`?
	 *     Optionally specify field delimiter, otherwise a period `.` will be used as the delimiter. (default=false) 3.0.193+
	 *  - Note the `objects` and `flat` options are not meant to be used together. 
	 * 
	 * @return array
	 * @since 3.0.172
	 *
	 */
	public function find($selector, $field = '', $options = array()) {
		if(!is_array($options)) $options = array('indexed' => (bool) $options);
		$finder = new PagesRawFinder($this->pages);
		$this->wire($finder);
		return $finder->find($selector, $field, $options);
	}

	/**
	 * Get page (no exclusions) and return raw data from it in a PHP array
	 *
	 * @param string|array|Selectors $selector
	 * @param string|Field|int|array $field Field/property name to get or array of them (or omit to get all)
	 * @param array|bool $options See options for Pages::find
	 *  - `objects` (bool): Use objects rather than associative arrays? (default=false)
	 *  - `entities` (bool|array): Entity encode string values? True, or specify array of field names. (default=false)
	 *  - `indexed` (bool): Index by page ID? (default=false)
	 *  - `flat` (bool|string): Flatten return value as `["field.subfield" => "value"]` rather than `["field" => ["subfield" => "value"]]`?
	 *     Optionally specify field delimiter, otherwise a period `.` will be used as the delimiter. (default=false) 3.0.193+
	 * @return array
	 * @since 3.0.172
	 *
	 */
	public function get($selector, $field = '', $options = array()) {
		if(!is_array($options)) $options = array('indexed' => (bool) $options);
		$options['findOne'] = true;
		if(!isset($options['findAll'])) $options['findAll'] = true;
		$values = $this->find($selector, $field, $options);
		return reset($values);
	}

	/**
	 * Get native pages table column value for given page ID
	 *
	 * This can only be used for native 'pages' table columns,
	 * i.e. id, name, templates_id, status, parent_id, etc.
	 *
	 * @param int|array $pageId Page ID or array of page IDs
	 * @param string|array $col Column name you want to get
	 * @return int|string|array|null Returns column value or array of column values if $pageId was an array.
	 *   When array is returned, it is indexed by page ID.
	 * @param array $options
	 *  - `cache` (bool): Allow use of memory cache to retrieve column value when available? (default=true)
	 *     Used only if $pageId is an integer (not used when array of page IDs).
	 * @throws WireException
	 * @since 3.0.190
	 *
	 *
	 */
	public function col($pageId, $col, array $options = array()) {

		$defaults = array(
			'cache' => true
		);

		$options = array_merge($defaults, $options);

		// delegate to cols() method when arguments require it
		if(is_array($col)) {
			return $this->cols($pageId, $col, $options);
		} else if(is_array($pageId)) {
			$value = array();
			foreach($this->cols($pageId, $col) as $id => $a) {
				$value[$id] = $a[$col];
			}
			return $value;
		}

		if(!ctype_alnum($col)) {
			$sanitizer = $this->wire()->sanitizer;
			if($sanitizer->fieldName($col) !== $col) {
				throw new WireException("Invalid column name: $col");
			}
		}

		$pageId = (int) $pageId;

		// use cached value when available
		if($options['cache']) {
			$page = $this->pages->cacher()->getCache($pageId);
			if($page) return $page->getUnformatted($col);
		}

		$database = $this->wire()->database;
		$col = $database->escapeCol($col);

		$query = $database->prepare("SELECT `$col` FROM pages WHERE id=:id");
		$query->bindValue(':id', $pageId, (int) \PDO::PARAM_INT);
		$query->execute();
		$value = $query->rowCount() ? $query->fetchColumn() : null;
		$query->closeCursor();

		return $value;
	}

	/**
	 * Get native pages table columns (plural) for given page ID
	 *
	 * This can only be used for native 'pages' table columns,
	 * i.e. id, name, templates_id, status, parent_id, etc.
	 *
	 * @param int|array $pageId Page ID or array of page IDs
	 * @param array|string $cols Names of columns to get or omit to get all columns
	 * @param array $options
	 *  - `cache` (bool): Allow use of memory cache to retrieve column value when available? (default=true)
	 *     Used only if $pageId is an integer (not used when array of page IDs).
	 * @return array Returns associative array on success or empty array if not found
	 *   If $pageId argument was an array then it returns a page ID indexed array of
	 *   associative arrays, one for each page.
	 * @throws WireException
	 * @since 3.0.190
	 *
	 */
	public function cols($pageId, $cols = array(), array $options = array()) {

		$defaults = array(
			'cache' => true,
		);

		$options = array_merge($defaults, $options);
		$sanitizer = $this->wire()->sanitizer;
		$database = $this->wire()->database;
		$query = null;
		$removeIdInReturn = false;

		if(!is_array($cols)) $cols = empty($cols) ? array() : array($cols);

		foreach($cols as $key => $col) {
			if(!ctype_alnum($col) && $sanitizer->fieldName($col) !== $col) {
				unset($cols[$key]);
			} else {
				$cols[$key] = $database->escapeCol($col);
			}
		}

		if(count($cols)) {
			$colStr = '`' . implode('`,`', $cols) . '`';
			if(is_array($pageId) && !in_array('id', $cols)) {
				$colStr .= ', id';
				$removeIdInReturn = true;
			}
		} else {
			$colStr = '*';
		}

		if(is_array($pageId)) {
			// multi page
			$ids = array();
			foreach($pageId as $id) {
				$id = (int) $id;
				if($id > 0) $ids[$id] = $id;
			}
			$ids = implode(',', $ids);
			$query = $database->prepare("SELECT $colStr FROM pages WHERE id IN($ids)");
			$query->execute();
			$value = array();
			while($row = $query->fetch(\PDO::FETCH_ASSOC)) {
				$id = (int) $row['id'];
				if($removeIdInReturn) unset($row['id']);
				foreach($row as $k => $v) {
					if(ctype_digit("$v")) $row[$k] = (int) $v;
				}
				$value[$id] = $row;
			}

		} else {
			// single page
			$pageId = (int) $pageId;
			$page = ($options['cache'] ? $this->pages->cacher()->getCache($pageId) : null);
			if($page) {
				$value = array();
				foreach($cols as $col) {
					$value[$col] = $page->get($col);
				}
			} else {
				$query = $database->prepare("SELECT $colStr FROM pages WHERE id=:id");
				$query->bindValue(':id', $pageId, (int) \PDO::PARAM_INT);
				$query->execute();
				$value = $query->rowCount() ? $query->fetch(\PDO::FETCH_ASSOC) : array();
			}
		}

		if($query) $query->closeCursor();

		return $value;
	}
}
