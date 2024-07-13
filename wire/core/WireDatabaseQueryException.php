<?php namespace ProcessWire;

/**
 * Thrown by DatabaseQuery classes on query exception
 * 
 * May have \PDOException populated with call to its getPrevious(); method, 
 * in which can it also has same getCode() and getMessage() as \PDOException.
 * 
 * @since 3.0.156
 * 
 */
class WireDatabaseQueryException extends WireException {}
