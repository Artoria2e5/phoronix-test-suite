<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2011, Michael Larabel
	Copyright (C) 2010 - 2011, Phoronix Media

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

	SETUP STEPS:

	1.) Run "phoronix-test-suite module-setup result_notifier"
	2.) This will prompt you through inputting the commands / absolute file paths to executables to run for each step. Leave empty for irrelevant ones.
	3.) To have this module always load automatically by the phoronix-test-suite command, add result_notifier to the LoadModules tag in ~/.phoronix-test-suite/user-config.xml
		i.e. my config portion looked like: <LoadModules>toggle_screensaver, update_checker, result_notifier</LoadModules>
	4.) Should be all set for testing... My initial tests (just using some scripts that wrote some temporary files of the different exported env variables all worked fine.
*/

class result_notifier extends pts_module_interface
{
	const module_name = "Result Notifier";
	const module_version = "1.0.0";
	const module_description = "A notification module.";
	const module_author = "Michael Larabel";

	public static function module_info()
	{
		return null;
	}
	public static function module_setup()
	{
		return array(
			new pts_module_option("pre_test_process", "Pre-test process hook", null),
			new pts_module_option("pre_test_run_process", "Pre-test run execution hook", null),
			new pts_module_option("interim_test_run_process", "Interim-test run execution hook", null),
			new pts_module_option("post_test_run_process", "Post-test run execution hook", null),
			new pts_module_option("post_test_process", "Post-test process script", null)
			);
	}

	public static function __startup()
	{
		// NOTE: This will just print to the terminal when PTS has loaded this module, so you know in fact it's being loaded/should be working
		echo "\nJust started the result_notifier module.\n";
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		$executable = pts_module::read_option("pre_test_process");
		self::process_user_config_external_hook_process($executable, "Running the pre-test process external hook", $test_run_manager);
	}
	public static function __pre_test_run(&$test_run_request)
	{
		$executable = pts_module::read_option("pre_test_run_process");
		self::process_user_config_external_hook_process($executable, "Running the pre-test external hook", $test_run_manager);
	}
	public static function __interim_test_run(&$test_run_request)
	{
		$executable = pts_module::read_option("interim_test_run_process");
		self::process_user_config_external_hook_process($executable, "Running the interim-test external hook", $test_run_request);
	}
	public static function __post_test_run(&$test_run_request)
	{
		$executable = pts_module::read_option("post_test_run_process");
		self::process_user_config_external_hook_process($executable, "Running the post-test external hook", $test_run_manager);
	}
	public static function __post_run_process(&$test_run_manager)
	{
		$executable = pts_module::read_option("post_test_process");
		self::process_user_config_external_hook_process($executable, "Running the post-test process external hook", $test_run_manager);
	}

	// This is called after the XML save, but not sure Intel needs this since __post_run_process is there too...
/*
	public static function __post_test_run_process(&$test_run_manager)
	{
		$executable = pts_module::read_option("post_test_process");
		self::process_user_config_external_hook_process($executable, "Doing external post test process", $test_run_manager);
	}
*/
	protected static function process_user_config_external_hook_process($cmd_value, $description_string = null, &$passed_obj = null)
	{
		if(!empty($cmd_value) && (is_executable($cmd_value) || ($cmd_value = pts_client::executable_in_path($cmd_value))))
		{
			$descriptor_spec = array(
				0 => array("pipe", 'r'),
				1 => array("pipe", 'w'),
				2 => array("pipe", 'w')
				);

			$env_vars = array();

			if($passed_obj instanceof pts_test_result)
			{
				$env_vars["PTS_EXTERNAL_TEST_IDENTIFIER"] = $passed_obj->test_profile->get_identifier() . '-' . $passed_obj->test_result_buffer->get_count();
				$env_vars["PTS_EXTERNAL_TEST_ARGS"] = $passed_obj->get_arguments();
				$env_vars["PTS_EXTERNAL_TEST_DESCRIPTION"] = $passed_obj->get_arguments_description();
				$env_vars["PTS_EXTERNAL_TEST_RESULT_SET"] = $passed_obj->test_result_buffer->get_values_as_string();
				$env_vars["PTS_EXTERNAL_TEST_RESULT"] = $passed_obj->get_result();
				$env_vars["PTS_EXTERNAL_TEST_HASH"] = $passed_obj->get_comparison_hash();
				$env_vars["PTS_EXTERNAL_TEST_STD_DEV_PERCENT"] = pts_math::percent_standard_deviation($passed_obj->test_result_buffer->get_values());

				if(is_file($passed_obj->test_profile->get_install_dir() . 'cache-share-' . PTS_INIT_TIME . '.pt2so'))
				{
					// There's a cache share present
					$env_vars["PTS_EXTERNAL_TEST_CACHE_SHARE"] = 1;
				}
			}
			else if($passed_obj instanceof pts_test_run_manager)
			{
				$env_vars["PTS_EXTERNAL_TESTS_IN_QUEUE"] = implode(':', $passed_obj->get_tests_to_run_identifiers());
				$env_vars["PTS_EXTERNAL_TEST_FILE_NAME"] = $passed_obj->get_file_name();
				$env_vars["PTS_EXTERNAL_TEST_IDENTIFIER"] = $passed_obj->get_results_identifier();
			}

			$description_string != null && pts_client::$display->generic_heading($description_string);
			$proc = proc_open($cmd_value, $descriptor_spec, $pipes, null, $env_vars);
			$std_output = stream_get_contents($pipes[1]);
			$return_value = proc_close($proc);

			// If you want PTS to exit or something when your script returns !0, you could add an "exit;" or whatever you want below
			// The contents of $std_output is anything that may have been written by your script, if you want it to be interpreted by anything in this module
			if($return_value != 0)
			{
				return false;
			}
		}

		return true;
	}
}

?>
