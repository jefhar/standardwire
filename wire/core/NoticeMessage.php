<?php namespace ProcessWire;

use Override;
/**
 * A notice that's indicated to be informational
 *
 */
class NoticeMessage extends Notice { 
	#[Override]
 public function getName(): string {
		return 'messages';
	}
}
