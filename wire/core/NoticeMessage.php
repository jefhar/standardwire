<?php namespace ProcessWire;

/**
 * A notice that's indicated to be informational
 *
 */
class NoticeMessage extends Notice { 
	#[\Override]
 public function getName() {
		return 'messages';
	}
}
