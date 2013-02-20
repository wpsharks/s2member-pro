<?php
/**
* Handles various exportations (innner processing routines).
*
* Copyright: © 2009-2011
* {@link http://www.websharks-inc.com/ WebSharks, Inc.}
* (coded in the USA)
*
* This WordPress® plugin (s2Member Pro) is comprised of two parts:
*
* o (1) Its PHP code is licensed under the GPL license, as is WordPress®.
* 	You should have received a copy of the GNU General Public License,
* 	along with this software. In the main directory, see: /licensing/
* 	If not, see: {@link http://www.gnu.org/licenses/}.
*
* o (2) All other parts of (s2Member Pro); including, but not limited to:
* 	the CSS code, some JavaScript code, images, and design;
* 	are licensed according to the license purchased.
* 	See: {@link http://www.s2member.com/prices/}
*
* Unless you have our prior written consent, you must NOT directly or indirectly license,
* sub-license, sell, resell, or provide for free; part (2) of the s2Member Pro Module;
* or make an offer to do any of these things. All of these things are strictly
* prohibited with part (2) of the s2Member Pro Module.
*
* Your purchase of s2Member Pro includes free lifetime upgrades via s2Member.com
* (i.e. new features, bug fixes, updates, improvements); along with full access
* to our video tutorial library: {@link http://www.s2member.com/videos/}
*
* @package s2Member\Exports
* @since 1.5
*/
if(realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_exports_in"))
	{
		/**
		* Handles various exportations (innner processing routines).
		*
		* @package s2Member\Exports
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_exports_in
			{
				/**
				* Handles the exportation of Users/Members.
				*
				* @package s2Member\Exports
				* @since 110815
				*
				* @return null Or exits script execution after issuing file download prompt with CSV file.
				*/
				public static function export_users()
					{
						if(!empty($_POST["ws_plugin__s2member_pro_export_users"]) && ($nonce = $_POST["ws_plugin__s2member_pro_export_users"]) && wp_verify_nonce($nonce, "ws-plugin--s2member-pro-export-users") && current_user_can("create_users"))
							{
								global /* Global database object reference. */ $wpdb;
								global /* Multisite Networking. */ $current_site, $current_blog;

								@set_time_limit(0);
								@ini_set("memory_limit", apply_filters("admin_memory_limit", WP_MAX_MEMORY_LIMIT));

								@ini_set("zlib.output_compression", 0);
								if(function_exists("apache_setenv"))
									@apache_setenv("no-gzip", "1");

								while (@ob_end_clean ());

								$format = !empty($_POST["ws_plugin__s2member_pro_export_users_format"]) ? $_POST["ws_plugin__s2member_pro_export_users_format"] : "";
								$utf8_bom = isset($_POST["ws_plugin__s2member_pro_export_users_utf8_bom"]) ? (int)$_POST["ws_plugin__s2member_pro_export_users_utf8_bom"] : 0;
								$start = !empty($_POST["ws_plugin__s2member_pro_export_users_start"]) ? (int)$_POST["ws_plugin__s2member_pro_export_users_start"] : 1;

								$start = /* Must be 1 or higher. */ ($start >= 1) ? $start : 1;
								$sql_s = /* 1 should be 0. */ ($start === 1) ? 0 : $start;

								$export = /* Initialize the export file variable. */ "";

								$s2map = array( // Map s2Member fields.
								"custom" => $wpdb->prefix."s2member_custom",
								"subscr_id" => $wpdb->prefix."s2member_subscr_id",
								"subscr_gateway" => $wpdb->prefix."s2member_subscr_gateway",
								"auto_eot_time" => $wpdb->prefix."s2member_auto_eot_time",
								"last_payment_time" => $wpdb->prefix."s2member_last_payment_time",
								"paid_registration_times" => $wpdb->prefix."s2member_paid_registration_times",
								"custom_fields" => $wpdb->prefix."s2member_custom_fields");

								if(is_array($_users = $wpdb->get_results("SELECT `".$wpdb->users."`.`ID` FROM `".$wpdb->users."`, `".$wpdb->usermeta."` WHERE `".$wpdb->users."`.`ID` = `".$wpdb->usermeta."`.`user_id` AND `".$wpdb->usermeta."`.`meta_key` = '".esc_sql($wpdb->prefix."capabilities")."' ORDER BY `".$wpdb->users."`.`ID` ASC LIMIT ".$sql_s.", ".apply_filters("ws_plugin__s2member_pro_export_users_limit", 1000))))
									{
										if(is_multisite() && c_ws_plugin__s2member_utils_conds::is_multisite_farm() && !is_main_site())
											$export .= '"ID","Username","First Name","Last Name","Display Name","Email","Website","Role","Custom Capabilities","Registration Date","First Payment Date","Last Payment Date","Auto-EOT Date","Custom Value","Paid Subscr. ID","Paid Subscr. Gateway"';
										else // Otherwise, we use the standardized format for exportation.
											$export .= '"ID","Username","Password","First Name","Last Name","Display Name","Email","Website","Role","Custom Capabilities","Registration Date","First Payment Date","Last Payment Date","Auto-EOT Date","Custom Value","Paid Subscr. ID","Paid Subscr. Gateway"';

										$custom_field_vars = /* Initialize this array. */ array();
										if($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["custom_reg_fields"])
											foreach(json_decode($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["custom_reg_fields"], TRUE) as $field)
											{
												$custom_field_var = preg_replace("/[^a-z0-9]/i", "_", strtolower($field["id"]));
												$custom_field_vars[] = $custom_field_var;
											}
										sort /* Always sort this array. */($custom_field_vars, SORT_STRING);

										foreach($custom_field_vars as $custom_field_var)
												$export .= ',"'.c_ws_plugin__s2member_utils_strings::esc_dq($custom_field_var, 1, '"').'"';
										$export /* This completes the headers. */ .= "\n";

										foreach /* Go through each User/Member in this result set. */($_users as $_user)
											{
												if(is_object($user = new WP_User($_user->ID)) && $user->ID)
													{
														$custom_capabilities = /* Reset each time. */ "";

														foreach($user->allcaps as $cap => $cap_enabled)
															if(preg_match("/^access_s2member_ccap_/", $cap))
																if($cap = preg_replace("/^access_s2member_ccap_/", "", $cap))
																	$custom_capabilities .= ",".$cap;

														$custom_capabilities = trim($custom_capabilities, ",");

														$custom = (isset($user->$s2map["custom"])) ? $user->$s2map["custom"] : "";
														$subscr_id = (isset($user->$s2map["subscr_id"])) ? $user->$s2map["subscr_id"] : "";
														$subscr_gateway = (isset($user->$s2map["subscr_gateway"])) ? $user->$s2map["subscr_gateway"] : "";

														$auto_eot_time = (isset($user->$s2map["auto_eot_time"])) ? $user->$s2map["auto_eot_time"] : "";
														$last_payment_time = (isset($user->$s2map["last_payment_time"])) ? $user->$s2map["last_payment_time"] : "";
														$paid_registration_times = (isset($user->$s2map["paid_registration_times"])) ? $user->$s2map["paid_registration_times"] : array();
														$custom_fields = (isset($user->$s2map["custom_fields"]) && is_array($user->$s2map["custom_fields"])) ? $user->$s2map["custom_fields"] : array();

														$paid_registration_date = ($paid_registration_times["level"]) ? date("m/d/Y", $paid_registration_times["level"]) : "";
														$paid_registration_times = (is_array($paid_registration_times) && !empty($paid_registration_times)) ? serialize($paid_registration_times) : "";
														$registration_date = ($user->user_registered) ? date("m/d/Y", strtotime($user->user_registered)) : "";
														$last_payment_date = ($last_payment_time) ? date("m/d/Y", $last_payment_time) : "";
														$auto_eot_date = ($auto_eot_time) ? date("m/d/Y", $auto_eot_time) : "";

														if(is_multisite() && c_ws_plugin__s2member_utils_conds::is_multisite_farm() && !is_main_site())
															{
																if($format === "readable") // Human readable format; easier for some.
																	{
																		$line = '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->ID, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_login, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->first_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->last_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->display_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_email, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_url, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq(reset($user->roles), 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($custom_capabilities, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($registration_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($paid_registration_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($last_payment_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($auto_eot_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($custom, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($subscr_id, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($subscr_gateway, 1, '"').'",';

																		foreach($custom_field_vars as $custom_field_var)
																			if(isset($custom_fields[$custom_field_var]))
																				$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq(implode("|", (array)$custom_fields[$custom_field_var]), 1, '"').'",';
																			else $line .= '"",';
																	}
																else // Otherwise, we can just use the default re-importation format.
																	{
																		$line = '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->ID, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_login, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->first_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->last_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->display_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_email, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_url, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq(reset($user->roles), 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($custom_capabilities, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($registration_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($paid_registration_times, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($last_payment_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($auto_eot_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($custom, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($subscr_id, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($subscr_gateway, 1, '"').'",';

																		foreach($custom_field_vars as $custom_field_var)
																			if(isset($custom_fields[$custom_field_var]))
																				$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq(maybe_serialize($custom_fields[$custom_field_var]), 1, '"').'",';
																			else $line .= '"",';
																	}
															}
														else // Otherwise, we use the standardized formats for exportation.
															{
																if($format === "readable") // Human readable format; easier for some.
																	{
																		$line = '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->ID, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_login, 1, '"').'",';
																		$line .= '"",'; // The Password field is left blank on export.
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->first_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->last_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->display_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_email, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_url, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq(reset($user->roles), 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($custom_capabilities, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($registration_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($paid_registration_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($last_payment_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($auto_eot_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($custom, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($subscr_id, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($subscr_gateway, 1, '"').'",';

																		foreach($custom_field_vars as $custom_field_var)
																			if(isset($custom_fields[$custom_field_var]))
																				$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq(implode("|", (array)$custom_fields[$custom_field_var]), 1, '"').'",';
																			else $line .= '"",';
																	}
																else // Otherwise, we can just use the default re-importation format.
																	{
																		$line = '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->ID, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_login, 1, '"').'",';
																		$line .= '"",'; // The Password field is left blank on export.
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->first_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->last_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->display_name, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_email, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($user->user_url, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq(reset($user->roles), 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($custom_capabilities, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($registration_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($paid_registration_times, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($last_payment_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($auto_eot_date, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($custom, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($subscr_id, 1, '"').'",';
																		$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq($subscr_gateway, 1, '"').'",';

																		foreach($custom_field_vars as $custom_field_var)
																			if(isset($custom_fields[$custom_field_var]))
																				$line .= '"'.c_ws_plugin__s2member_utils_strings::esc_dq(maybe_serialize($custom_fields[$custom_field_var]), 1, '"').'",';
																			else $line .= '"",';
																	}
															}

														$export .= trim($line, " \r\n\t\0\x0B,")."\n";
													}
											}
									}

								status_header /* 200 OK status header. */(200);

								if($utf8_bom) // Add UTF-8 BOM (Byte Order Marker)?
									$export = "\xEF\xBB\xBF".$export;

								header("Content-Encoding: none");
								header("Accept-Ranges: none");
								header("Content-Type: text/csv; charset=UTF-8");
								header("Content-Length: ".strlen($export));
								header("Expires: ".gmdate("D, d M Y H:i:s", strtotime("-1 week"))." GMT");
								header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
								header("Cache-Control: no-cache, must-revalidate, max-age=0");
								header("Cache-Control: post-check=0, pre-check=0", false);
								header("Pragma: no-cache");

								header('Content-Disposition: attachment; filename="export-'.$start.'-'.($start + 999).'.csv"');

								exit($export); // Exportation file.
							}
					}
				/**
				* Handles the exportation of options.
				*
				* @package s2Member\Exports
				* @since 110815
				*
				* @return null Or exits script execution after issuing file download prompt with TXT file.
				*/
				public static function export_ops()
					{
						if(!empty($_GET["ws_plugin__s2member_pro_export_ops"]) && ($nonce = $_GET["ws_plugin__s2member_pro_export_ops"]) && wp_verify_nonce($nonce, "ws-plugin--s2member-pro-export-ops") && current_user_can("create_users"))
							{
								$export = serialize(c_ws_plugin__s2member_pro_utils_ops::op_replace($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]));

								@set_time_limit(0);
								@ini_set("memory_limit", apply_filters("admin_memory_limit", WP_MAX_MEMORY_LIMIT));

								@ini_set("zlib.output_compression", 0);
								if(function_exists("apache_setenv"))
									@apache_setenv("no-gzip", "1");

								while (@ob_end_clean ());

								status_header(200); // 200 OK status header.

								header("Content-Encoding: none");
								header("Accept-Ranges: none");
								header("Content-Type: text/plain; charset=UTF-8");
								header("Content-Length: ".strlen($export));
								header("Expires: ".gmdate("D, d M Y H:i:s", strtotime("-1 week"))." GMT");
								header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
								header("Cache-Control: no-cache, must-revalidate, max-age=0");
								header("Cache-Control: post-check=0, pre-check=0", false);
								header("Pragma: no-cache");

								header('Content-Disposition: attachment; filename="export.s2e"');

								exit($export); // Exportation file.
							}
					}
			}
	}
?>