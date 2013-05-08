<?php
/**
* ClickBank® utilities.
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
* @package s2Member\ClickBank
* @since 1.5
*/
if(realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_clickbank_utilities"))
	{
		/**
		* ClickBank® utilities.
		*
		* @package s2Member\ClickBank
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_clickbank_utilities
			{
				/**
				* Formulates request Authorization headers.
				*
				* @package s2Member\ClickBank
				* @since 1.5
				*
				* @return array Request Authorization headers for ClickBank® API communication.
				*/
				public static function clickbank_api_headers()
					{
						$req["headers"]["Accept"] = "application/json";
						$req["headers"]["Authorization"] = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_clickbank_developer_key"].":".$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_clickbank_clerk_key"];

						return $req; // Return array with headers.
					}
				/**
				* Get ``$_POST`` or ``$_REQUEST`` vars from ClickBank®.
				*
				* @package s2Member\ClickBank
				* @since 1.5
				*
				* @return array|bool An array of verified ``$_POST`` or ``$_REQUEST`` variables, else false.
				*
				* @todo Continue optimizing this routine with ``empty()`` and ``isset()``.
				* @todo Candidate for the use of ``ifsetor()``?
				*/
				public static function clickbank_postvars()
					{
						if(!empty($_REQUEST["s2member_pro_clickbank_return"]) && !empty($_REQUEST["cbpop"]))
							{
								$postvars = c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($_REQUEST));

								foreach($postvars as $var => $value)
									if(preg_match("/^s2member_/", $var))
										unset($postvars[$var]);

								$cbpop = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_clickbank_secret_key"];
								$cbpop .= "|".$postvars["cbreceipt"]."|".$postvars["time"]."|".$postvars["item"];

								$mb = function_exists("mb_convert_encoding") ? @mb_convert_encoding($cbpop, "UTF-8", $GLOBALS["WS_PLUGIN__"]["s2member"]["c"]["mb_detection_order"]) : $cbpop;
								$cbpop = ($mb) ? $mb : $cbpop; // Double check this, just in case conversion fails.
								$cbpop = strtoupper(substr(sha1($cbpop), 0, 8));

								if($postvars["cbpop"] === $cbpop)
									return $postvars;

								else // Nope.
									return false;
							}
						else if(!empty($_REQUEST["s2member_pro_clickbank_notify"]) && !empty($_REQUEST["cverify"]))
							{
								$postvars = c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($_REQUEST));

								foreach($postvars as $var => $value)
									if(preg_match("/^s2member_/", $var))
										unset($postvars[$var]);

								$cverify = ""; // Initialize verification.

								($keys = array_keys($postvars)).sort($keys);
								foreach($keys as $key) // Go through keys.
									if($key && !preg_match("/^(cverify)$/", $key))
										$cverify .= $postvars[$key]."|";

								$cverify .= $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_clickbank_secret_key"];

								$mb = function_exists("mb_convert_encoding") ? @mb_convert_encoding($cverify, "UTF-8", $GLOBALS["WS_PLUGIN__"]["s2member"]["c"]["mb_detection_order"]) : $cverify;
								$cverify = ($mb) ? $mb : $cverify; // Double check this, just in case conversion fails.
								$cverify = strtoupper(substr(sha1($cverify), 0, 8));

								if($postvars["cverify"] === $cverify)
									return $postvars;

								else // Nope.
									return false;
							}
						else // Nope.
							return false;
					}
				/**
				* Parses s2Vars passed through by ClickBank®.
				*
				* @package s2Member\ClickBank
				* @since 111205
				*
				* @param str $cvendthru Expects the URL-encoded query string of s2Vars, including `_s2member_sig`.
				* @param str $type Optional. The type of ClickBank® transaction. This deals with backward compatibility.
				* 	For SALE transactions, do NOT accept the older format. For others, remain backward compatible.
				* @return array Array of s2Vars. Possibly an empty array.
				*/
				public static function clickbank_parse_s2vars($cvendthru = FALSE, $type = FALSE)
					{
						wp_parse_str((string)$cvendthru, $s2vars);
						$s2vars = c_ws_plugin__s2member_utils_strings::trim_deep($s2vars);

						foreach($s2vars as $var => $value /* Pulls out `s2_|_s2member_sig` vars. */)
							if(!in_array($var, array("cbskin", "cbfid", "cbur", "cbf"), TRUE)) // These may be included in a signature too.
								if(!preg_match("/^(?:s2_|_s2member_sig)/", $var)) // These will always be included in a signature.
									unset($s2vars[$var]);

						$is_sale = preg_match("/^(?:TEST_)?SALE$/i", (string)$type);
						if(!$is_sale || c_ws_plugin__s2member_utils_urls::s2member_sig_ok(http_build_query($s2vars, null, "&")))
							return /* Looks good. Return ``$s2vars``. */ $s2vars;

						return /* Default empty array. */ array();
					}
				/**
				* Credit card reminder on Return Page templates.
				*
				* @package s2Member\ClickBank
				* @since 110720
				*
				* @attaches-to ``add_filter("ws_plugin__s2member_return_template_support");``
				*
				* @param str $support The current value for the `%%support%%` Replacement Code, passed through by the Filter.
				* @param array $vars An array of defined variables, passed through by the Filter.
				* @return str The ``$support`` value, after possible modification.
				*/
				public static function clickbank_cc_reminder($support = FALSE, $vars = FALSE)
					{
						if(!empty($vars["template"]) && $vars["template"] === "clickbank")
							return $support. // Now add the reminder below this. ClickBank® requires site owners to display this.
							'<div class="cc-reminder">'._x('<strong>Reminder:</strong> Purchases at this site will appear on your credit card or bank statement as: <code>ClickBank®</code> or <code>CLKBANK*COM</code>.', "s2member-front", "s2member").'</div>';

						return $support;
					}
			}
	}
?>