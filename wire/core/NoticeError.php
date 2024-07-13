<?php namespace ProcessWire;

/**
 * A notice that's indicated to be an error
 *
 */
class NoticeError extends Notice { 
	public function getName() {
		return 'errors';
	}
}
