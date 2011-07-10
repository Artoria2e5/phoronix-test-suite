<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
	pts-core.php: To boot-strap the Phoronix Test Suite start-up

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

define('PTS_VERSION', '3.2.1');
define('PTS_CORE_VERSION', 3300);
define('PTS_CODENAME', 'LILLESAND');
define('PTS_IS_CLIENT', (defined('PTS_MODE') && PTS_MODE == 'CLIENT'));


if(PTS_IS_CLIENT)
{
	error_reporting(E_ALL | E_NOTICE | E_STRICT);
}

function pts_codename($full_string = false)
{
	$codename = ucwords(strtolower(PTS_CODENAME));

	return ($full_string ? 'PhoronixTestSuite/' : null) . $codename;
}
function pts_title($show_both = false)
{
	return 'Phoronix Test Suite' . (PTS_VERSION != null ? ' v' . PTS_VERSION : null) . (PTS_CODENAME != null && (PTS_VERSION == null || $show_both ) ? ' (' . ucwords(strtolower(PTS_CODENAME)) . ')' : null);
}
function pts_define_directories()
{
	// User's home directory for storing results, module files, test installations, etc.
	define('PTS_CORE_PATH', PTS_PATH . 'pts-core/');

	if(PTS_IS_CLIENT)
	{
		define('PTS_USER_PATH', pts_client::user_home_directory() . '.phoronix-test-suite/');
		define('PTS_CORE_STORAGE', PTS_USER_PATH . 'core.pt2so');
	}

	// Misc Locations
	define('PTS_MODULE_PATH', PTS_CORE_PATH . 'modules/');
	define('PTS_MODULE_LOCAL_PATH', PTS_USER_PATH . 'modules/');
	define('PTS_MODULE_DATA_PATH', PTS_USER_PATH . 'modules-data/');
	define('PTS_DOWNLOAD_CACHE_PATH', PTS_USER_PATH . 'download-cache/');
	define('PTS_CORE_STATIC_PATH', PTS_CORE_PATH . 'static/');
	define('PTS_COMMAND_PATH', PTS_CORE_PATH . 'commands/');
	define('PTS_EXDEP_PATH', PTS_CORE_PATH . 'external-test-dependencies/');
	define('PTS_RESULTS_VIEWER_PATH', PTS_CORE_PATH . 'results-viewer/');
	define('PTS_OPENBENCHMARKING_PATH', PTS_CORE_PATH . 'openbenchmarking.org/');
	define('PTS_OPENBENCHMARKING_SCRATCH_PATH', PTS_USER_PATH . 'openbenchmarking.org/');

	// Test & Suite Locations
	define('PTS_TEST_PROFILE_PATH', PTS_USER_PATH . 'test-profiles/');
	define('PTS_TEST_SUITE_PATH', PTS_USER_PATH . 'test-suites/');
}
function pts_needed_extensions()
{
	return array(
		// Required? - The Check If In Place - Name - Description

		// Required extesnions denoted by 1 at [0]
		array(1, extension_loaded('dom'), 'DOM', 'The PHP Document Object Model (DOM) is required.'),
		array(1, extension_loaded('zip') || extension_loaded('zlib'), 'ZIP', 'PHP Zip support is required.'),
		array(1, function_exists('json_decode'), 'JSON', 'PHP JSON support is required.'),

		// Optional but recommended extensions
		array(0, extension_loaded('openssl'), 'OpenSSL', 'PHP OpenSSL support is recommended to support HTTPS traffic.'),
		array(0, extension_loaded('gd'), 'GD', 'The PHP GD library is recommended for improved graph rendering.'),
		array(0, extension_loaded('zlib'), 'Zlib', 'The PHP Zlib extension can be used for greater file compression.'),
		array(0, function_exists('pcntl_fork'), 'PCNTL', 'PHP PCNTL is highly recommended as it is required by some tests.'),
		array(0, function_exists('posix_getpwuid'), 'POSIX', 'PHP POSIX support is highly recommended.'),
		array(0, function_exists('curl_init'), 'CURL', 'PHP CURL is recommended for a better download experience.')

		// PHP FPDF could be added here too...
		);
}

if(PTS_IS_CLIENT || defined('PTS_AUTO_LOAD_OBJECTS'))
{
	function pts_build_dir_php_list($dir, &$files)
	{
		if($dh = opendir($dir))
		{
			while(($file = readdir($dh)) !== false)
			{
				if($file != '.' && $file != '..')
				{
					if(is_dir($dir . '/' . $file))
					{
						pts_build_dir_php_list($dir . '/' . $file, $files);
					}
					else if(substr($file, -4) == '.php')
					{
						$files[substr($file, 0, -4)] = $dir . '/' . $file;
					}
				}
			}
		}

		closedir($dh);
	}
	function __autoload($to_load)
	{
		static $sub_objects = null;

		if($sub_objects == null)
		{
			pts_build_dir_php_list(PTS_PATH . 'pts-core/objects', $sub_objects);
		}

		if(isset($sub_objects[$to_load]))
		{
			include($sub_objects[$to_load]);
			unset($sub_objects[$to_load]);
		}
	}
}

?>
