<?php
//j// BOF

/*n// NOTE
----------------------------------------------------------------------------
secured WebGine
net-based application engine
----------------------------------------------------------------------------
(C) direct Netware Group - All rights reserved
http://www.direct-netware.de/redirect.php?swg

This Source Code Form is subject to the terms of the Mozilla Public License,
v. 2.0. If a copy of the MPL was not distributed with this file, You can
obtain one at http://mozilla.org/MPL/2.0/.
----------------------------------------------------------------------------
http://www.direct-netware.de/redirect.php?licenses;mpl2
----------------------------------------------------------------------------
#echo(sWGaccountCreditsVersion)#
sWG/#echo(__FILEPATH__)#
----------------------------------------------------------------------------
NOTE_END //n*/
/**
* The sWG supports credits for payments or to provide "enhanced services" for
* active users.
*
* @internal   We are using phpDocumentor to automate the documentation process
*             for creating the Developer's Manual. All sections including
*             these special comments will be removed from the release source
*             code.
*             Use the following line to ensure 76 character sizes:
* ----------------------------------------------------------------------------
* @author     direct Netware Group
* @copyright  (C) direct Netware Group - All rights reserved
* @package    sWG
* @subpackage account_credits
* @since      v0.1.00
* @license    http://www.direct-netware.de/redirect.php?licenses;mpl2
*             Mozilla Public License, v. 2.0
*/

/* -------------------------------------------------------------------------
All comments will be removed in the "production" packages (they will be in
all development packets)
------------------------------------------------------------------------- */

//j// Basic configuration

/* -------------------------------------------------------------------------
Direct calls will be honored with an "exit ()"
------------------------------------------------------------------------- */

if (!defined ("direct_product_iversion")) { exit (); }

//j// Functions and classes

/**
* Checks if a payment would be successful or not. Result is either a string
* or a boolean.
*
* @param  boolean $f_return_information True to return a string instead of a
*         boolean as result.
* @param  integer $f_credits_change Credits difference of the transaction
* @return mixed String or boolean containing the payment status
* @since  v0.1.00
*/
function direct_credits_payment_check ($f_return_information = false,$f_credits_change = 0)
{
	global $direct_globals,$direct_settings;
	if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -direct_credits_payment_check (+f_return_information,$f_credits_change)- (#echo(__LINE__)#)"); }

	if ($direct_settings['account_credits'])
	{
		$f_credits_change = ceil ($f_credits_change);
		$f_result = false;

		if ($direct_settings['user']['id'])
		{
			$f_user_array = $direct_globals['kernel']->vUserGet ($direct_settings['user']['id']);

			if (($f_user_array['ddbusers_credits'])||($f_credits_change >= 0))
			{
				if ($f_credits_change >= 0) { $f_result = true; }
				else
				{
					if (($f_user_array['ddbusers_credits'] + $f_credits_change) >= 0) { $f_result = true; }
				}
			}
		}
		else
		{
			$f_user_array = array ("ddbusers_credits" => 0);
			if ($f_credits_change >= 0) { $f_result = true; }
		}

		if ($f_return_information)
		{
			if ($f_credits_change) { $f_return = ($f_result ? (direct_local_get ("credits_manager_check_payment_1_1"))."<span style='font-weight:bold'>{$f_user_array['ddbusers_credits']}</span>".(direct_local_get ("credits_manager_check_payment_1_2"))."<span style='font-weight:bold'>".($f_user_array['ddbusers_credits'] + $f_credits_change)."</span>".(direct_local_get ("credits_manager_check_payment_1_3")) : (direct_local_get ("credits_manager_check_payment_0_1"))."<span style='font-weight:bold'>{$f_user_array['ddbusers_credits']}</span>".(direct_local_get ("credits_manager_check_payment_0_2"))."<span style='font-weight:bold'>".($f_credits_change * -1)."</span>".(direct_local_get ("credits_manager_check_payment_0_3"))); }
			else { $f_return = ""; }
		}
		else { $f_return = $f_result; }
	}
	else { $f_return = ($f_return_information ? "" : true); }

	return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -direct_credits_payment_check ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
}

/**
* Execute a payment.
*
* @param  string $f_controller The controller responsible for this payment.
* @param  string $f_identifier Payment identifier
* @param  string $f_id Payment ID
* @param  string $f_userid User ID used for the payment
* @param  integer $f_credits_onetime Onetime payment amount
* @param  integer $f_credits_periodically Periodically payment amount
* @param  integer $f_counter This parameter is used to limit the periodically
*         payments to a limited time.
* @return boolean True on success
* @since  v0.1.00
*/
function direct_credits_payment_exec ($f_controller,$f_identifier,$f_id,$f_userid,$f_credits_onetime = 0,$f_credits_periodically = 0,$f_counter = 0)
{
	global $direct_cachedata,$direct_globals,$direct_settings;
	if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -direct_credits_payment_exec ($f_controller,$f_identifier,$f_id,$f_userid,$f_credits_onetime,$f_credits_periodically)- (#echo(__LINE__)#)"); }

	if ($direct_settings['account_credits'])
	{
		$f_credits_onetime = ceil ($f_credits_onetime);
		$f_credits_periodically = ceil ($f_credits_periodically);
		$f_return = false;
		$f_user_array = $direct_globals['kernel']->vUserGet ($f_userid ? $f_userid : $direct_settings['user']['id']);

		if ($f_user_array)
		{
			if ($f_credits_onetime)
			{
				$f_credits_old = $f_user_array['ddbusers_credits'];
				$f_user_array['ddbusers_credits'] += $f_credits_onetime;

				if ($f_user_array['ddbusers_credits'] < 0)
				{
$f_log_array = array (
"ddblog_source_user_id" => $direct_settings['user']['id'],
"ddblog_source_user_ip" => $direct_settings['user_ip'],
"ddblog_target_user_id" => $f_user_array['ddbusers_id'],
"ddblog_target_user_ip" => $f_user_array['ddbusers_lastvisit_ip'],
"ddblog_sid" => "4063a52147d1bc5c975d3caf2966274d",
// md5 ("account_credits")
"ddblog_identifier" => "account_credits_limit_zero_warning",
"ddblog_data" => array ("fee" => $f_credits_onetime,"old" => $f_credits_old,"new" => $f_user_array['ddbusers_credits'])
);

					direct_log_write ($f_log_array);
					$f_user_array['ddbusers_credits'] = 0;
				}

				if ($f_user_array['ddbusers_credits'] > $direct_settings['users_credits_max'])
				{
$f_log_array = array (
"ddblog_source_user_id" => $direct_settings['user']['id'],
"ddblog_source_user_ip" => $direct_settings['user_ip'],
"ddblog_target_user_id" => $f_user_array['ddbusers_id'],
"ddblog_target_user_ip" => $f_user_array['ddbusers_lastvisit_ip'],
"ddblog_sid" => "4063a52147d1bc5c975d3caf2966274d",
// md5 ("account_credits")
"ddblog_identifier" => "account_credits_limit_max_warning",
"ddblog_data" => array ("fee" => $f_credits_onetime,"old" => $f_credits_old,"new" => $f_user_array['ddbusers_credits'])
);

					direct_log_write ($f_log_array);
					$f_user_array['ddbusers_credits'] = $direct_settings['users_credits_max'];
				}

				if ($direct_globals['kernel']->vUserUpdate ($f_user_array['ddbusers_id'],$f_user_array))
				{
$f_log_array = array (
"ddblog_source_user_id" => $direct_settings['user']['id'],
"ddblog_source_user_ip" => $direct_settings['user_ip'],
"ddblog_target_user_id" => $f_user_array['ddbusers_id'],
"ddblog_target_user_ip" => $f_user_array['ddbusers_lastvisit_ip'],
"ddblog_sid" => "4063a52147d1bc5c975d3caf2966274d",
// md5 ("account_credits")
"ddblog_identifier" => "account_credits_payment",
"ddblog_data" => array (
	"type" => "onetime",
	"fee" => $f_credits_onetime,
	"old" => $f_credits_old,
	"new" => $f_user_array['ddbusers_credits'],
	"controller" => $f_controller,
	"identifier" => $f_identifier,
	"objid" => $f_id
	)
);

					direct_log_write ($f_log_array);
					$f_return = true;
				}
			}
			else { $f_return = true; }

			if ($direct_settings['swg_auto_maintenance'])
			{
				if (($f_credits_periodically)&&($f_return))
				{
					$direct_globals['db']->initInsert ($direct_settings['users_credits_table']);

					$f_insert_attributes = array ($direct_settings['users_credits_table'].".ddbcredits_id",$direct_settings['users_credits_table'].".ddbcredits_id_obj",$direct_settings['users_credits_table'].".ddbcredits_id_user",$direct_settings['users_credits_table'].".ddbcredits_controller",$direct_settings['users_credits_table'].".ddbcredits_identifier",$direct_settings['users_credits_table'].".ddbcredits_time",$direct_settings['users_credits_table'].".ddbcredits_amount",$direct_settings['users_credits_table'].".ddbcredits_counter");
					$direct_globals['db']->defineValuesKeys ($f_insert_attributes);

					$f_credits_task_id = uniqid ("");
					$f_credits_task_time = ($direct_cachedata['core_time'] + ($direct_settings['users_credits_periodically_days'] * 86400));

$f_insert_values = ("<sqlvalues>
".($direct_globals['db']->defineValuesEncode ($f_credits_task_id,"string"))."
".($direct_globals['db']->defineValuesEncode ($f_id,"string"))."
".($direct_globals['db']->defineValuesEncode ($f_user_array['ddbusers_id'],"string"))."
".($direct_globals['db']->defineValuesEncode ($f_controller,"string"))."
".($direct_globals['db']->defineValuesEncode ($f_identifier,"string"))."
".($direct_globals['db']->defineValuesEncode ($f_credits_task_time,"number"))."
".($direct_globals['db']->defineValuesEncode ($f_credits_periodically,"number"))."
".($direct_globals['db']->defineValuesEncode ($f_counter,"number"))."
</sqlvalues>");

					$direct_globals['db']->defineValues ($f_insert_values);
					$f_return = $direct_globals['db']->queryExec ("co");

					if (($f_return)&&(function_exists ("direct_dbsync_event"))) { direct_dbsync_event ($direct_settings['users_credits_table'],"insert",("<sqlconditions>".($direct_globals['db']->defineRowConditionsEncode ("ddbcredits_id",$f_credits_task_id,"string"))."</sqlconditions>")); }
				}
			}
			else { trigger_error ("sWG/#echo(__FILEPATH__)# -direct_credits_payment_exec ()- (#echo(__LINE__)#) reporting: sWG does not support periodically credits while running in non-auto-maintenance mode.",E_USER_WARNING); }

			if ((!$f_credits_onetime)&&(!$f_credits_periodically)&&($f_return))
			{
				$direct_globals['db']->initDelete ($direct_settings['users_credits_table']);

				$f_delete_criteria = "<sqlconditions>".($direct_globals['db']->defineRowConditionsEncode ($direct_settings['users_credits_table'].".ddbcredits_id_obj",$f_id,"string"));
				if ($f_controller) { $f_delete_criteria .= $direct_globals['db']->defineRowConditionsEncode ($direct_settings['users_credits_table'].".ddbcredits_controller",$f_controller,"string"); }
				if ($f_identifier) { $f_delete_criteria .= $direct_globals['db']->defineRowConditionsEncode ($direct_settings['users_credits_table'].".ddbcredits_identifier",$f_identifier,"string"); }
				$f_delete_criteria .= "</sqlconditions>";

				$direct_globals['db']->defineRowConditions ($f_delete_criteria);
				$f_return = $direct_globals['db']->queryExec ("co");

				if ($f_return)
				{
					if (function_exists ("direct_dbsync_event")) { direct_dbsync_event ($direct_settings['users_credits_table'],"delete",$f_delete_criteria); }
					if (!$direct_settings['swg_auto_maintenance']) { $direct_globals['db']->vOptimize ($direct_settings['users_credits_table']); }
				}
			}
		}
	}
	else { $f_return = true; }

	return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -direct_credits_payment_exec ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
}

/**
* Returns special payment values for the defined situation, user and group.
*
* @param  string $f_identifier Identifier for special credit settings
* @param  string $f_object The specific object (for example a category ID)
* @param  integer &$f_default_onetime Reference to the default onetime
*         payment amount
* @param  integer &$f_default_periodically Reference to the default
*         periodically payment amount
* @since  v0.1.00
*/
function direct_credits_payment_get_specials ($f_identifier,$f_object,&$f_default_onetime,&$f_default_periodically)
{
	global $direct_globals,$direct_settings;
	if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -direct_credits_payment_get_specials ($f_identifier,$f_object,$f_default_onetime,$f_default_periodically)- (#echo(__LINE__)#)"); }

	if (($direct_settings['user']['type'] == "ad")||(!$direct_settings['account_credits']))
	{
		$f_default_onetime = 0;
		$f_default_periodically = 0;
	}
	elseif (($direct_settings['user']['type'] != "gt")&&(direct_class_function_check ($direct_globals['kernel'],"v_group_user_get_groups")))
	{
		$f_id_identifier = md5 ($f_identifier);

		$direct_globals['db']->initSelect ($direct_settings['users_credits_specials_table']);
		$direct_globals['db']->defineAttributes ("*");

		$f_select_criteria = "<sqlconditions>".($direct_globals['db']->defineRowConditionsEncode ($direct_settings['users_credits_table'].".ddbcredits_specials_id_obj",$f_id_identifier,"string","==","or"))."</sqlconditions>";

		if ($f_object)
		{
			$f_id_obj = md5 ($f_identifier."_".$f_object);
			$f_select_criteria .= $direct_globals['db']->defineRowConditionsEncode ($direct_settings['users_credits_table'].".ddbcredits_specials_id_obj",$f_id_obj,"string","==","or");
		}

		$f_select_criteria .= "</sqlconditions>";
		$direct_globals['db']->defineRowConditions ($f_select_criteria);

		$f_results_array = $direct_globals['db']->queryExec ("ma");

		if ($f_results_array)
		{
			$f_credits_default = array ();
			$f_credits_special_object = array ();
			$f_credits_special_groups = array ();

			foreach ($f_results_array as $f_result_array)
			{
				if ($f_result_array['ddbcredits_specials_id_obj'] == $f_id_identifier) { $f_credits_default = array ($f_result_array['ddbcredits_specials_onetime'],$f_result_array['ddbcredits_specials_periodically']); }
				if ($f_result_array['ddbcredits_specials_id_obj'] == $f_id_obj)
				{
					if ($f_result_array['ddbcredits_specials_group']) { $f_credits_special_groups[$f_result_array['ddbcredits_specials_group']] = $f_result_array; }
					else { $f_credits_special_object = array ($f_result_array['ddbcredits_specials_onetime'],$f_result_array['ddbcredits_specials_periodically']); }
				}
			}

			if (empty ($f_credits_special_object))
			{
				if (!empty ($f_credits_default))
				{
					$f_default_onetime = $f_credits_default[0];
					$f_default_periodically = $f_credits_default[1];
				}
			}

			if ($f_object)
			{
				$f_credits_special_group = array ();

				if (!empty ($f_credits_special_groups))
				{
					$f_groups_array = $direct_globals['kernel']->vGroupUserGetGroups ();

					foreach ($f_credits_special_groups as $f_group => $f_result_array)
					{
						if (in_array ($f_group,$f_groups_array))
						{
							if ((empty ($f_credits_special_group))||($f_result_array['ddbcredits_specials_periodically'] < $f_credits_special_group[1])) { $f_credits_special_group = array ($f_result_array['ddbcredits_specials_onetime'],$f_result_array['ddbcredits_specials_periodically']); }
						}
					}
				}

				if (empty ($f_credits_special_group))
				{
					if (!empty ($f_credits_special_object))
					{
						$f_default_onetime = $f_credits_special_object[0];
						$f_default_periodically = $f_credits_special_object[1];
					}
				}
				else
				{
					$f_default_onetime = $f_credits_special_group[0];
					$f_default_periodically = $f_credits_special_group[1];
				}
			}
		}
	}
}

/**
* Returns an informational message what will happen on
* "payment_exec()" based on the given values for "$f_credits_onetime"
* and "$f_credits_periodically".
*
* @param  integer $f_credits_onetime Onetime payment amount
* @param  integer $f_credits_periodically Periodically payment amount
* @return string Valid (X)HTML information string for output inclusion
* @since  v0.1.00
*/
function direct_credits_payment_info ($f_credits_onetime = 0,$f_credits_periodically = 0)
{
	global $direct_settings;
	if (USE_debug_reporting) { direct_debug (5,"sWG/#echo(__FILEPATH__)# -direct_credits_payment_info ($f_credits_onetime,$f_credits_periodically)- (#echo(__LINE__)#)"); }

	if ($direct_settings['account_credits'])
	{
		$f_credits_onetime = ceil ($f_credits_onetime);
		$f_credits_periodically = ceil ($f_credits_periodically);
		$f_return = "";

		if ($f_credits_onetime)
		{
			if ($f_credits_onetime < 0)
			{
				$f_credits_onetime *= -1;
				$f_return .= (direct_local_get ("credits_manager_pay_onetime_preinfo_1"))."<span style='font-weight:bold'>$f_credits_onetime</span>".(direct_local_get ("credits_manager_pay_onetime_preinfo_2"));
			}
			else { $f_return .= (direct_local_get ("credits_manager_receive_onetime_preinfo_1"))."<span style='font-weight:bold'>$f_credits_onetime</span>".(direct_local_get ("credits_manager_receive_onetime_preinfo_2")); }
		}

		if (($direct_settings['swg_auto_maintenance'])&&($f_credits_periodically))
		{
			if ($f_return)
			{
				$f_return .= " ";
				$f_text_type = 2;
			}
			else { $f_text_type = 1; }

			if ($f_credits_periodically < 0)
			{
				$f_credits_periodically *= -1;
				$f_return .= (direct_local_get ("credits_manager_pay_periodically_preinfo_{$f_text_type}_1"))."<span style='font-weight:bold'>$f_credits_periodically</span>".(direct_local_get ("credits_manager_pay_periodically_preinfo_{$f_text_type}_2"));
			}
			else { $f_return .= (direct_local_get ("credits_manager_receive_periodically_preinfo_{$f_text_type}_1"))."<span style='font-weight:bold'>$f_credits_periodically</span>".(direct_local_get ("credits_manager_receive_periodically_preinfo_{$f_text_type}_2")); }
		}
	}
	else { $f_return = ""; }

	return /*#ifdef(DEBUG):direct_debug (7,"sWG/#echo(__FILEPATH__)# -direct_credits_payment_info ()- (#echo(__LINE__)#)",:#*/$f_return/*#ifdef(DEBUG):,true):#*/;
}

//j// Script specific commands

if (!isset ($direct_settings['account_credits'])) { $direct_settings['account_credits'] = false; }
if (!isset ($direct_settings['swg_auto_maintenance'])) { $direct_settings['swg_auto_maintenance'] = false; }
if (!isset ($direct_settings['users_credits_max'])) { $direct_settings['users_credits_max'] = 50000; }
if (!isset ($direct_settings['users_credits_periodically_days'])) { $direct_settings['users_credits_periodically_days'] = 30; }

$direct_globals['basic_functions']->requireFile ($direct_settings['path_system']."/functions/swg_log_storager.php");
direct_local_integration ("credits_manager");

//j// EOF
?>