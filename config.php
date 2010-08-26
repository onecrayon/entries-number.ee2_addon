<?php

/**
 * Entries Number config file
 *
 * @package			entries-number-ee2_addon
 * @version			2.0
 * @author			Laisvunas Sopauskas <laisvunas@classicsunlocked.net>
 * @link			http://www.classicsunlocked.net/
 * @author			Ian Beck <ian@onecrayon.com>
 * @link			http://onecrayon.com/
 
 * ====================================================
 * This program is freeware; 
 * you may use this code for any purpose, commercial or
 * private, without any further permission from the author.
*/

if ( ! defined('ENTRIES_NUMBER_NAME'))
{
	define('ENTRIES_NUMBER_NAME', 'Entries Number');
	define('ENTRIES_NUMBER_VERSION', '2.0');
}
 
$config['name']    = ENTRIES_NUMBER_NAME;
$config['version'] = ENTRIES_NUMBER_VERSION;

// Not applicable, but maybe down the road
//$config['nsm_addon_updater']['versions_xml'] = 'FEED-URL-HERE';
