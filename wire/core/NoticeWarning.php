<?php namespace ProcessWire;

use Override;
/**
 * A notice that's indicated to be a warning
 *
 */
class NoticeWarning extends Notice {
	#[Override]
 public function getName(): string {
		return 'warnings';
	}
}
