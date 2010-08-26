<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include config file
include PATH_THIRD.'entries_number/config'.EXT;

$plugin_info = array(
	'pi_name'			=> ENTRIES_NUMBER_NAME,
	'pi_version'		=> ENTRIES_NUMBER_VERSION,
	'pi_author'			=> 'Laisvunas Sopauskas and Ian Beck',
	'pi_author_url'		=> 'http://devot-ee.com/add-ons/entries-number/',
	'pi_description'	=> 'Allows you to find number of entries posted into certain
	channels and/or into certain categories and/or having
	certain url_title. Use the number found in conditionals.',
	'pi_usage'			=> Entries_number::usage()
);

/**
 * Entries Number Plugin class
 *
 * @package			entries-number-ee2_addon
 * @version			2.0
 * @author			Laisvunas Sopauskas <laisvunas@classicsunlocked.net>
 * @link			http://www.classicsunlocked.net/
 * @author			Ian Beck <ian@onecrayon.com>
 * @link			http://onecrayon.com/
*/
class Entries_number
{
	
	/**
	* Plugin return data
	*
	* @var	string
	*/
	var $return_data="";
	
	/**
	* PHP4 Constructor
	*
	* @see	__construct()
	*/
	function Entries_number()
	{
		$this->__construct();
	}
	
	/**
	* PHP 5 Constructor
	*
	* @return	string
	*/
	function __construct()
	{
		// get global instance
		$this->EE =& get_instance();
		
		// Fetch the tagdata
		$tagdata = $this->EE->TMPL->tagdata;
			
		// Fetch params
		$channel = $this->EE->TMPL->fetch_param('channel');
		$categoryid = $this->EE->TMPL->fetch_param('category');
		$urltitle = $this->EE->TMPL->fetch_param('url_title');
		$entryid = $this->EE->TMPL->fetch_param('entry_id');
		$site = $this->EE->TMPL->fetch_param('site');
		$status = $this->EE->TMPL->fetch_param('status');
		$invalid_input = $this->EE->TMPL->fetch_param('invalid_input');
		$show_expired = $this->EE->TMPL->fetch_param('show_expired');
		$author_id = $this->EE->TMPL->fetch_param('author_id');
		$required_field = $this->EE->TMPL->fetch_param('required_field');
		// Mark some params to test for invalid characters
		$testparams = array(
			'channel' => $channel,
			'categoryid' => $categoryid,
			'urltitle' => $urltitle,
			'entryid' => $entryid,
			'site' => $site
		);
		
		// Define variables
		$categoryidclause = '';
		$channelclause = '';
		$urltitleclause = '';
		$entryidclause = '';
		$siteclause = '';
		$statusclause = '';
		$entryidequalityclause = '';
		$groupbyclause = '';
		$havingclause = '';
		$authoridclause = '';
		$categorytablesclause = '';
		$distinctoperator = '';
		$found_invalid = FALSE;
		$fieldnamearray = array();
		$fieldidarray = array();
		$entriesnumber = 0;
		$current_time = time();
		
		// Simple validation of params values
		$invalidchars = array('~', '#', '*', '{', '}', '[', ']', '/', '\\', '<', '>', '\'', '\"');
		foreach ($testparams as $key => $param)
		{
			foreach ($invalidchars as $char)
			{
				if (strpos($param, $char) !== FALSE)
				{
					if ($params['invalid_input'] === 'alert')
					{
						exit('Error! Parameter "' . $key . '" of exp:entries_number tag contains at least one illegal character.');
					}
					$found_invalid = TRUE;
				}
			}
		}
		
		// construct our SQL clauses
		if ($found_invalid === FALSE)
		{
			// If "category" parameter is defined
			if ($categoryid !== FALSE)
			{
				// Clean whitespace from "category" parameter value
				$categoryid = str_replace(' ', '', $categoryid);
				// Check if "category" param contains "not"
				if (strpos($categoryid, 'not')===0)  // In case "category" param contains "not" form SQL clause using "AND" and "!=" operators
				{
					$categoryid = substr($categoryid, 3);
					$categoryidarray = explode('|', $categoryid);
					foreach($categoryidarray as $categoryidnumber)
					{
						$categoryidclause .= " AND exp_category_posts.cat_id!='".trim($categoryidnumber)."' ";
					}
					//exit('$categoryidclause: '.$categoryidclause);
				}
				else  // the case "category" param does not contain "not"
				{
					$categoryidarray = explode('|', $categoryid);
					$categoryidarray2 = explode('&', $categoryid);
					// the case in "category" param there is neither "|" symbol nor "&" symbol
					if (count($categoryidarray)==1 AND count($categoryidarray2)==1)
					{
						$categoryidclause = " AND exp_category_posts.cat_id='".$categoryidarray[0]."' ";
					}
					//the case in "category" param there is at least one "|" symbol
					elseif (count($categoryidarray)>1)
					{
						foreach($categoryidarray as $categoryidnumber)
						{
							$categoryidclause .= " OR exp_category_posts.cat_id='".$categoryidnumber."' ";
						}
						$categoryidclause = substr($categoryidclause, 4);
						$categoryidclause = " AND (".$categoryidclause.")";
						$distinctoperator = ' DISTINCT ';
					}
					//the case in "category" param there is at least one "&" symbol
					elseif (count($categoryidarray2)>1)
					{
						$categoryidclause = " AND exp_category_posts.cat_id IN (";
						foreach($categoryidarray2 as $categoryidnumber2)
						{
							$categoryidclause .= "'".$categoryidnumber2."',";
						}
						$categoryidclause = substr($categoryidclause, 0, strlen($categoryidclause)-1);
						$categoryidclause .=") ";
						$groupbyclause = " GROUP BY exp_channel_titles.url_title, exp_channel_titles.title, exp_channel_titles.entry_id, exp_channel_titles.status, exp_channel_titles.expiration_date, exp_channels.channel_name ";
						$havingclause = " HAVING count(*)>1 ";
					}
					//echo '$categoryidclause: '.$categoryidclause.'<br><br>';
				}
				// Form category related clauses
				$entryidequalityclause = " AND exp_category_posts.entry_id=exp_channel_titles.entry_id ";
				$categorytablesclause = ", exp_category_posts ";
			}
			
			// If "channel" parameter is defined
			if ($channel !== FALSE)
			{
				// Clean whitespace from "channel" parameter value
				$channel = str_replace(' ', '', $channel);
				// Check if "channel" param contains "not"
				if (strpos($channel, 'not')===0)
				{
					// In case "channel" param contains "not" form SQL clause using "AND" and "!=" operators
					$channel = substr($channel, 3);
					$channelarray = explode('|', $channel);
					foreach($channelarray as $channelname)
					{
						$channelclause .= " AND exp_channels.channel_name!='".trim($channelname)."' ";
					}
					//exit('$channelclause: '.$channelclause);
				}
				else
				{
					// In case "channel" param does not contain "not" form SQL clause using "OR" and "=" operators
					$channelarray = explode('|', $channel);
					if (count($channelarray)==1)
					{
						$channelclause = " AND exp_channels.channel_name='".$channelarray[0]."' ";
					}
					else
					{
						foreach($channelarray as $channelname)
						{
							$channelclause .= " OR exp_channels.channel_name='".$channelname."' ";
						}
						$channelclause = substr($channelclause, 4);
						$channelclause = " AND (".$channelclause.") ";
					}
					//exit('$channelclause: '.$channelclause);
				}
			}
			
			// If "required_field" parameter is defined
			if ($required_field !== FALSE)
			{
				// Clean whitespace from "required_field" parameter value
				$required_field = str_replace(' ', '', $required_field);
				$fieldnamearray = explode('|', $required_field);
			}
			
			// If "author_id" parameter is defined
			if ($author_id !== FALSE)
			{
				// Clean whitespace from "author_id" parameter value
				$author_id = str_replace(' ', '', $author_id);
				// Check if "author_id" param contains "not"
				if (strpos($author_id, 'not')===0)
				{
					// In case "author_id" param contains "not" form SQL clause using "AND" and "!=" operators
					$author_id = substr($author_id, 3);
					$authoridarray = explode('|', $author_id);
					foreach($authoridarray as $authoridnum)
					{
						$authoridclause .= " AND exp_channel_titles.author_id!='".$authoridnum."' ";
					}
				}
				else
				{
					// In case "author_id" param does not contain "not" form SQL clause using "OR" and "=" operators
					$authoridarray = explode('|', $author_id);
					if (count($authoridarray)==1)
					{
						$authoridclause = " AND exp_channel_titles.author_id='".$authoridarray[0]."' ";
					}
					else
					{
						foreach($authoridarray as $authoridnum)
						{
							$authoridclause .= " OR exp_channel_titles.author_id='".$authoridnum."' ";
							$authoridclause = substr($authoridclause, 4);
							$authoridclause = " AND (".$authoridclause.") ";
						}
					}
				}
				//echo 'authoridclause: '.$authoridclause.'<br><br>';
			}
			
			//If "status" parameter is defined
			if ($status !== FALSE)
			{
				// Check if "status" param contains "not"
				if (strpos($status, 'not')===0)
				{
					// In case "status" param contains "not" form SQL clause using "AND" and "!=" operators
					$status = substr($status, 3);
					$statusarray = explode('|', $status);
					foreach($statusarray as $statusname)
					{
						$statusname = trim($statusname);
						$statusclause .= " AND exp_channel_titles.status!='".$statusname."' ";
					}
					//echo '$statusclause: '.$statusclause;
				}
				else
				{
					// In case "status" param does not contain "not" form SQL clause using "OR" and "=" operators
					$statusarray = explode('|', $status);
					if (count($statusarray)==1)
					{
						$statusclause = " AND exp_channel_titles.status='".$statusarray[0]."' ";
					}
					else
					{
						foreach($statusarray as $statusname)
						{
							$statusname = trim($statusname);
							$statusclause .= " OR exp_channel_titles.status='".$statusname."' ";
						}
						$statusclause = substr($statusclause, 4);
						$statusclause = " AND (".$statusclause.") ";
					}
				}
				//echo '$statusclause: '.$statusclause.'<br><br>';
			}
			
			// If "site" parameter is defined
			if ($site !== FALSE)
			{
				// Clean whitespace from "site" parameter value
				$site = str_replace(' ', '', $site);
				// Check if "site" param contains "not"
				if (strpos($site, 'not')===0)
				{
					// In case "site" param contains "not" form SQL clause using "AND" and "!=" operators
					$site = substr($site, 3);
					$sitearray = explode('|', $site);
					foreach($sitearray as $siteid)
					{
						$siteclause .= " AND exp_channel_titles.site_id!='".trim($siteid)."' ";
					}
					//exit('$siteclause: '.$siteclause);
				}
				else
				{
					// In case "site" param does not contain "not" form SQL clause using "OR" and "=" operators
					$sitearray = explode('|', $site);
					if (count($sitearray)==1)
					{
						$siteclause = " AND exp_channel_titles.site_id='".$sitearray[0]."' ";
					}
					else
					{
						foreach($sitearray as $siteid)
						{
							$siteclause .= " OR exp_channel_titles.site_id='".$siteid."' ";
						}
						$siteclause = substr($siteclause, 4);
						$siteclause = " AND (".$siteclause.") ";
						//exit('$siteclause: '.$siteclause);
					}
				}
			}
			
			if ($urltitle !== FALSE)
			{
				$urltitleclause = " AND exp_channel_titles.url_title='".$urltitle."' ";
			}
			
			if ($entryid !== FALSE)
			{
				$entryidclause = " AND exp_channel_titles.entry_id='".$entryid."' ";
			}
			
				// Create SQL query string
			$todo = "SELECT ".$distinctoperator." exp_channel_titles.url_title, exp_channel_titles.title, exp_channel_titles.entry_id, exp_channel_titles.status, exp_channel_titles.expiration_date, exp_channel_titles.author_id, exp_channels.channel_name FROM exp_channel_titles, exp_channels ".$categorytablesclause." WHERE exp_channel_titles.channel_id=exp_channels.channel_id ";
			$todo .= $entryidequalityclause.$categoryidclause.$channelclause.$urltitleclause.$entryidclause.$statusclause.$authoridclause.$siteclause.$groupbyclause.$havingclause;
			//echo '$todo: '.$todo.'<br><br>';
			
			// Perform SQL query
			$query = $this->EE->db->query($todo);
			
			//Find number of entries
			
			// the case "show_expired" parameter is not set to "no" and "required_field" parameter is not defined
			if ($show_expired !== 'no' AND $required_field === FALSE)
			{
				$entriesnumber = $query->num_rows();
			}
			// the case "show_expired" parameter is set to "no" and "required_field" parameter is not defined
			elseif (strtolower($show_expired) === 'no' AND $required_field === FALSE)
			{
				foreach($query->result_array() as $row)
				{
					//echo 'current_time: '.$current_time.' expiration_date: '.$row['expiration_date'].'<br><br>';
					if ($current_time < $row['expiration_date'] OR $row['expiration_date'] == 0)
					{
						$entriesnumber++;
					}
				}
			}
			// the case "required_field" parameter is defined
			elseif ($required_field !== FALSE)
			{
				if (count($fieldnamearray) > 0)
				{
					// find field id numbers for all required fields
					foreach ($fieldnamearray as $fieldname)
					{
						$todo2 = "SELECT field_id FROM exp_channel_fields WHERE field_name = '".$fieldname."'";
						$todo2 .= $siteclause." LIMIT 1 ";
						// Perform SQL query
						$query2 = $this->EE->db->query($todo2);
						foreach($query2->result_array() as $row2)
						{
							array_push($fieldidarray, $row2['field_id']);
						}
						//echo 'fieldidarray length: '.count($fieldidarray).'<br><br>';
					}
					foreach($query->result_array() as $row)
					{
						$fieldexists = 'no';
						foreach ($fieldidarray as $fieldid)
						{
							$todo3 = "SELECT field_id_".$fieldid." FROM exp_channel_data WHERE entry_id = '".$row['entry_id']."' LIMIT 1";
							//echo 'todo3: '.$todo3.'<br><br>';
							$query3 = $this->EE->db->query($todo3);
							if ($query3->num_rows() == 1)
							{
								$query3result = $query3->result_array();
								if (trim($query3result['field_id_'.$fieldid]) != '')
								{
									$fieldexists = 'yes';
								}
							}
						}
						if ($fieldexists === 'yes')
						{
							// the case "show_expired" parameter is not set to "no"
							if ($show_expired !== 'no')
							{
								$entriesnumber++;
							}
							// the case "show_expired" parameter is set to "no"
							else
							{
								if ($current_time < $row['expiration_date'] OR $row['expiration_date'] == 0)
								{
									$entriesnumber++;
								}
							}
						}    
					}
				}
			}
			
			//Create conditionals array
			$conds['entries_number'] = $entriesnumber;
			
			//Make entries_number variable available for use in conditionals
			$tagdata = $this->EE->functions->prep_conditionals($tagdata, $conds);
			
			// Check if there is {entries_number} variable placed between {exp:entries_number} and {/exp:entries_number} tag pair
			if (strpos($tagdata, '{entries_number}') !== FALSE)
			{
				// If there is {entries_number} variable, then return entries number as variable's output
				$tagdata = str_replace('{entries_number}', $entriesnumber, $tagdata);
			}
			$this->return_data = $tagdata;
		}
	}
	// END FUNCTION
	
	// ----------------------------------------
	//  Plugin Usage
	// ----------------------------------------
	// This function describes how the plugin is used.
	//  Make sure and use output buffering
	
	function usage()
	{
		ob_start(); 
		?>
		
		PARAMETERS:
		
		1) category - Optional. Allows you to specify category id number 
		(the id number of each category is displayed in the Control Panel).
		You can stack categories using pipe character to get entries 
		with any of those categories, e.g. category="3|6|8". Or use "not" 
		(with a space after it) to exclude categories, e.g. category="not 4|5|7".
		Also you can use "&" symbol to get entries each of which was posted into all 
		specified categories, e.g. category="3&6&8". 
		
		2) channel - Optional. Allows you to specify channel name.
		You can use the pipe character to get entries from any of those 
		channels, e.g. channel="channel1|channel2|channel3".
		Or you can add the word "not" (with a space after it) to exclude channels,
		e.g. channel="not channel1|channel2|channel3".
		
		3) author_id - Optional. Allows you to specify author id number.
		You can use the pipe character to get entries posted by any of those 
		authors, e.g. author_id="5|11|18".
		Or you can add the word "not" (with a space after it) to exclude authors,
		e.g. author_id="not 1|9".
		
		4) site - Optional. Allows you to specify site id number.
		You can stack site id numbers using pipe character to get entries 
		from any of those sites, e.g. site="1|3". Or use "not" 
		(with a space after it) to exclude sites, e.g. site="not 1|2".
		
		5) status - Optional. Allows you to specify status of entries.
		You can stack statuses using pipe character to get entries 
		having any of those statuses, e.g. status="open|draft". Or use "not" 
		(with a space after it) to exclude statuses, 
		e.g. status="not submitted|processing|closed".
		
		6) url_title - Optional. Allows you to specify url_title of an entry.
		
		7) entry_id - Optional. Allows you to specify entry id number of an entry.
		
		8) show_expired - Optional. Allows you to specify if you wish expired entries
		to be counted. If the value is "yes", expired entries will be counted; if the
		value is "no", expired entries will not be counted. Default value is "yes".
		
		9) invalid_input - Optional. Accepts two values: "alert" and "silence".
		Default value is "silence". If the value is "alert", then in cases when some
		parameter’s value is invalid plugin exits and PHP alert is being shown;
		if the value is "silence", then in cases when some parameter’s value
		is invalid plugin finishes its work without any alert being shown. 
		Set this parameter to "alert" for development, and to "silence" - for deployment.
		
		10) required_field - Optional. Allows you to specify which custom field should not be
		emty. Pipe character is supported; "not" operator is not supported. E.g. if we have 
		required_field="custom_field1|custom_field2", then only those entries will be counted
		which have at least one of these fields not empty.
		
		VARIABLES:
		
		1) entries_number - outputs the number of entries which satisfy condition 
		entered in prameters.
		
		
		EXAMPLE OF USAGE:
		
		{exp:entries_number category="6" channel="not channel1|channel4" site="1"}
		{entries_number}
		{/exp:entries_number}
		
		The variable {entries_number} placed between {exp:entries_number} and {/exp:entries_number} tags
		will output the number of entries which satisfy condition entered in prameters.
		
		You can use {entries_number} variable in conditionals:
		
		{exp:entries_number category="6" channel="not channel1|channel4" site="1"}
		{if entries_number==0}
		Some code
		{if:elseif entries_number==1}
		Some other code
		{if:else}
		Yet another code
		{/if}
		{/exp:entries_number}
		
		It is not possible to use {entries_number} variable both inside and outside conditional.
		
		In contrast with "if no_results" conditional, which does not allow its parent tag {exp:channel:entries} to be
		wrapped in a plugin, contionals inside {exp:entries_number} does not interfere with outer plugins. That is,
		while the code as this 
		
		{exp:category_id category_group="3" category_url_title="segment_3" parse="inward"}
		{exp:channel:entries channel="my_channel" category="{category_id}"}
		{if no_results}
		No entry found! 
		{/if}
		{/exp:channel:entries}
		{/exp:category_id}
		
		will not work, the code as this
		
		{exp:category_id category_group="3" category_url_title="segment_3" parse="inward"}
		{exp:entries_number channel="my_channel" category="{category_id}"}
		{if entries_number==0}
		No entry found! 
		{/if}
		{/exp:entries_number}
		{/exp:category_id}
		
		will work properly.
		
		
		<?php
		$buffer = ob_get_contents();
			
		ob_end_clean(); 
		
		return $buffer;
	}
	// END USAGE
}
// END CLASS

/* End of file pi.entries_number.php */