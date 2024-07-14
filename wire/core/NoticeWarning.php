<?php namespace ProcessWire;

/**
 * A notice that's indicated to be a warning
 *
 */
class NoticeWarning extends Notice {
	#[\Override]
 public function getName() {
		return 'warnings';
	}
}
