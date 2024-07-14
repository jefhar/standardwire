<?php namespace ProcessWire;

use Override;
/**
 * A notice that's indicated to be an error
 *
 */
class NoticeError extends Notice { 
	#[Override]
 public function getName(): string {
		return 'errors';
	}
}
