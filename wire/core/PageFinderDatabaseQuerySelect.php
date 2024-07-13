<?php namespace ProcessWire;

/**
 * Typehinting class for DatabaseQuerySelect object passed to Fieldtype::getMatchQuery()
 *
 * @property Field $field Original field
 * @property string $group Original group of the field
 * @property Selector $selector Original Selector object
 * @property Selectors $selectors Original Selectors object
 * @property DatabaseQuerySelect $parentQuery Parent database query
 * @property PageFinder $pageFinder PageFinder instance that initiated the query
 */
abstract class PageFinderDatabaseQuerySelect extends DatabaseQuerySelect { }
