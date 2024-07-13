<?php namespace ProcessWire;

/**
 * ProcessWire Installer
 *
 * Because this installer runs before PW is installed, it is largely self contained.
 * It's a quick-n-simple single purpose script that's designed to run once, and it should be deleted after installation.
 * This file self-executes using code found at the bottom of the file, under the Installer class.
 *
 * Note that it creates this file once installation is completed: /site/assets/installed.php
 * If that file exists, the installer will not run. So if you need to re-run this installer for any
 * reason, then you'll want to delete that file. This was implemented just in case someone doesn't delete the installer.
 *
 * ProcessWire 3.x, Copyright 2022 by Ryan Cramer
 * https://processwire.com
 *
 * @todo 3.0.190: provide option for command-line options to install
 * @todo have installer set session name
 *
 */

define("PROCESSWIRE_INSTALL", "3.x");

/****************************************************************************************************/

if(!Installer::TEST_MODE && is_file("./site/assets/installed.php")) die("This installer has already run. Please delete it.");
error_reporting(E_ALL | E_STRICT);
$installer = new Installer();
$installer->execute();
