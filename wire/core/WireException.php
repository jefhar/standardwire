<?php namespace ProcessWire;

use Exception;
/**
 * Generic ProcessWire exception
 *
 */
class WireException extends Exception {
	/**
	 * Replace previously set message
	 * 
	 * @param string $message
	 * @since 3.0.150
	 * 
	 */
	protected function setMessage($message) {
		$this->message = $message;
	}

	/**
	 * Replace previously set code
	 * 
	 * @param int $code
	 * @since 3.0.150
	 * 
	 */
	protected function setCode($code) {
		$this->code = $code;
	}
}
