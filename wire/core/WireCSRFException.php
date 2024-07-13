<?php namespace ProcessWire;

/**
 * Thrown when cross site request forgery detected by SessionCSRF::validate()
 *
 */
class WireCSRFException extends WireException {}
