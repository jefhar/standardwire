<?php namespace ProcessWire;

/**
 * A notice that's indicated to be a warning
 *
 */
class NoticeWarning extends Notice {
	public function getName() {
		return 'warnings';
	}
}
