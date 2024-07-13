<?php namespace ProcessWire;

/**
 * Thrown when a requested page does not exist, or can be thrown manually to show the 404 page
 *
 */
class Wire404Exception extends WireException {
	
	/**
	 * 404 is because core determined requested resource by URL does not physically exist
	 * 
	 * #pw-internal
	 *
	 */ 
	const codeNonexist = 404;
	
	/**
	 * 404 is a result of a resource that might exist but there is no access
	 * 
	 * Similar to a WirePermissionException except always still a 404 externally
	 * 
	 * #pw-internal
	 *
	 */
	const codePermission = 4041;
	
	/**
	 * 404 is a result of a secondary non-file asset that does not exist, even if page does
	 * 
	 * For example: /foo/bar/?id=123 where /foo/bar/ exists but 123 points to non-existent asset.
	 * 
	 * #pw-internal
	 *
	 */
	const codeSecondary = 4042;

	/**
	 * 404 is a result of content not available in requested language
	 * 
	 * #pw-internal
	 *
	 */
	const codeLanguage = 4043;
	
	/**
	 * 404 is a result of a physical file that does not exist on the file system
	 * 
	 * #pw-internal
	 *
	 */
	const codeFile = 4044;

	/**
	 * 404 is a result of a front-end wire404() function call
	 * 
	 * #pw-internal
	 * 
	 */
	const codeFunction = 4045;
	
	/**
	 * Anonymous 404 with no code provided 
	 *
	 * #pw-internal
	 *
	 */
	const codeAnonymous = 0;

}
