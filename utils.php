<?php
/**
 * @package     VikCrediMax
 * @subpackage  core
 * @author     	Sanu Khan <sanulgebello@gmail.com>
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

// Define plugin base path
define('VIKCREDIMAX_DIR', dirname(__FILE__));
// Define plugin base URI
define('VIKCREDIMAX_URI', plugin_dir_url(__FILE__));

/**
 * Imports the file of the gateway and returns the classname
 * of the file that will be instantiated by the caller.
 *
 * @param 	string 	$plugin  The name of the caller.
 *
 * @return 	mixed 	The classname of the payment if exists, otherwise false.
 */
function vikcredimax_load_payment($plugin)
{
	if (!JLoader::import("{$plugin}.credimax", VIKCREDIMAX_DIR))
	{
		// there is not a version available for the given plugin
		return false;
	}

	return ucwords($plugin) . 'CrediMaxPayment';
}

/**
 * Returns the path in which the payment is located.
 *
 * @param 	string 	$plugin  The name of the caller.
 *
 * @return 	mixed 	The path if exists, otherwise false.
 */
function vikcredimax_get_payment_path($plugin)
{
	$path = VIKCREDIMAX_DIR . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR . 'credimax.php';

	if (!is_file($path))
	{
		// there is not a version available for the given plugin
		return false;
	}

	return $path;
}