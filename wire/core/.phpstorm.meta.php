<?php
/**
 * ProcessWire PhpStorm Meta
 *
 * This file is not a CODE, it makes no sense and won't run or validate
 * Its AST serves PhpStorm IDE as DATA source to make advanced type inference decisions.
 *
 * @see https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 */

namespace PHPSTORM_META {

	registerArgumentsSet(
		'WIRE_ARGUMENTS',
		'',
		'cache',
		'config',
		'database',
		'datetime',
		'db',
		'fieldgroups',
		'fields',
		'fieldtypes',
		'files',
		'hooks',
		'input',
		'languages',
		'log',
		'mail',
		'modules',
		'notices',
		'page',
		'pages',
		'permissions',
		'procache',
		'roles',
		'sanitizer',
		'session',
		'templates',
		'user',
		'users',
		'wire',
	);

	expectedArguments(\Processwire\wire(), 0, argumentsSet('WIRE_ARGUMENTS'));

	override(\Processwire\wire(0), map([
		'' => \ProcessWire\ProcessWire::class,
		'cache' => \ProcessWire\WireCache::class,
		'config' => \ProcessWire\Config::class,
		'database' => \ProcessWire\WireDatabasePDO::class,
		'datetime' => \ProcessWire\WireDateTime::class,
		'db' => \ProcessWire\DatabaseMysqli::class,
		'fieldgroups' => \ProcessWire\Fieldgroups::class,
		'fields' => \ProcessWire\Fields::class,
		'fieldtypes' => \ProcessWire\Fieldtypes::class,
		'files' => \ProcessWire\WireFileTools::class,
		'hooks' => \ProcessWire\WireHooks::class,
		'input' => \ProcessWire\WireInput::class,
		'languages' => \ProcessWire\WireLanguages::class,
		'log' => \ProcessWire\WireLog::class,
		'mail' => \ProcessWire\WireMailTools::class,
		'modules' => \ProcessWire\Modules::class,
		'notices' => \ProcessWire\Notices::class,
		'page' => \ProcessWire\Page::class,
		'pages' => \ProcessWire\Pages::class,
		'permissions' => \ProcessWire\Permissions::class,
		'procache' => \Procache::class,
		'roles' => \ProcessWire\Roles::class,
		'sanitizer' => \ProcessWire\Sanitizer::class,
		'session' => \ProcessWire\Session::class,
		'templates' => \ProcessWire\Templates::class,
		'user' => \App\Bracketfire\Models\User::class,
		'users' => \ProcessWire\Users::class,
		'wire' => \ProcessWire\ProcessWire::class,
	]));
}
