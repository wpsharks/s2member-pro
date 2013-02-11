<?php
/**
* Google® utilities.
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
* @package s2Member\Google
* @since 1.5
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_google_utilities"))
	{
		/**
		* Google® utilities.
		*
		* @package s2Member\Google
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_google_utilities
			{
				/**
				* Builds an HMAC-SHA1 signature for XML data transfer verification.
				*
				* @package s2Member\Google
				* @since 1.5
				*
				* @param str $xml An XML data string to sign.
				* @return str An HMAC-SHA1 signature string.
				*/
				public static function google_sign ($xml = FALSE)
					{
						$key = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_key"];

						return c_ws_plugin__s2member_utils_strings::hmac_sha1_sign ((string)$xml, $key);
					}
				/**
				* Formulates request Authorization headers.
				*
				* @package s2Member\Google
				* @since 1.5
				*
				* @return array Request Authorization headers for Google® API communication.
				*/
				public static function google_api_headers ()
					{
						$req["headers"]["Accept"] = "application/xml; charset=UTF-8";
						$req["headers"]["Content-Type"] = "application/xml; charset=UTF-8";
						$req["headers"]["Authorization"] = "Basic " . base64_encode ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"] . ":" . $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_key"]);

						return $req; // Return array with headers.
					}
				/**
				* Converts a "Period Term" into a Google® periodicity for XML subscription attribute.
				*
				* @package s2Member\Google
				* @since 1.5
				*
				* @param str $period_term A "Period Term" combination.
				* @return str The Google® Checkout equivalent for ``$period_term``.
				* 	One of `DAILY`, `WEEKLY`, `SEMI_MONTHLY`, `MONTHLY`, `EVERY_TWO_MONTHS`, `QUARTERLY`, or `YEARLY`.
				* 	Defaults to `MONTHLY` if ``$period_term`` is not configured properly.
				*/
				public static function google_periodicity ($period_term = FALSE)
					{
						list ($num, $span) = preg_split ("/ /", strtoupper ($period_term), 2);
						$num = (int)$num; // Force this to an integer.

						if ($num === 1 && $span === "D")
							return "DAILY";

						else if ($num === 1 && $span === "W")
							return "WEEKLY";

						else if ($num === 2 && $span === "W")
							return "SEMI_MONTHLY";

						else if ($num === 1 && $span === "M")
							return "MONTHLY";

						else if ($num === 2 && $span === "M")
							return "EVERY_TWO_MONTHS";

						else if ($num === 3 && $span === "M")
							return "QUARTERLY";

						else if ($num === 1 && $span === "Y")
							return "YEARLY";

						return "MONTHLY";
					}
				/**
				* Parses s2Vars from Google® IPN Notifications.
				*
				* @package s2Member\Google
				* @since 1.5
				*
				* @param str $xml XML data section returned by Google® for s2Vars.
				* @return array|bool An array of s2Vars, else false on failure.
				*/
				public static function google_parse_s2vars ($xml = FALSE)
					{
						if (preg_match_all ("/<([^\>]+)>([^\<]+)<\/([^\>]+)>/", $xml, $m) && is_array ($m[1]))
							{
								foreach ($m[1] as $key => $var)
									$s2vars[$var] = wp_specialchars_decode ($m[2][$key]);

								return $s2vars;
							}
						else
							return false;
					}
				/**
				* Get ``$_POST`` or ``$_REQUEST`` vars from Google®.
				*
				* @package s2Member\Google
				* @since 1.5
				*
				* @return array|bool An array of verified ``$_POST`` or ``$_REQUEST`` variables, else false.
				*
				* @todo Continue optimizing this routine with ``empty()`` and ``isset()``.
				* @todo Candidate for the use of ``ifsetor()``?
				*/
				public static function google_postvars ()
					{
						if (!empty ($_REQUEST["s2member_pro_google_notify"]) && !empty ($_REQUEST["serial-number"]))
							{
								$postback["_type"] = "notification-history-request";
								$postback["serial-number"] = trim (stripslashes ((string)$_REQUEST["serial-number"]));

								$endpoint = ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_sandbox"]) ? "sandbox.google.com/checkout" : "checkout.google.com";

								if (($response = c_ws_plugin__s2member_utils_urls::remote ("https://" . $endpoint . "/api/checkout/v2/reportsForm/Merchant/" . $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"], $postback, array_merge (c_ws_plugin__s2member_pro_google_utilities::google_api_headers (), array ("timeout" => 20)))) && wp_parse_str ($response, $postvars) !== "nill" && !empty ($postvars["_type"]))
									return c_ws_plugin__s2member_utils_strings::trim_deep ($postvars);
								else // Nope. Return false.
									return false;
							}
						else // Nope.
							return false;
					}
				/**
				* Calculates start date for a Recurring Payment Profile.
				*
				* @package s2Member\Google
				* @since 1.5
				*
				* @param str $period1 Optional. A "Period Term" combination. Defaults to `0 D`.
				* @param str $period3 Optional. A "Period Term" combination. Defaults to `0 D`.
				* @return int The start time, a Unix timestamp.
				*/
				public static function google_start_time ($period1 = FALSE, $period3 = FALSE)
					{
						if (!($p1_time = 0) && ($period1 = trim (strtoupper ($period1))))
							{
								list ($num, $span) = preg_split ("/ /", $period1, 2);

								$days = 0; // Days start at 0.

								if (is_numeric ($num) && !is_numeric ($span))
									{
										$days = ($span === "D") ? 1 : $days;
										$days = ($span === "W") ? 7 : $days;
										$days = ($span === "M") ? 30 : $days;
										$days = ($span === "Y") ? 365 : $days;
									}

								$p1_days = (int)$num * (int)$days;
								$p1_time = $p1_days * 86400;
							}

						if (!($p3_time = 0) && ($period3 = trim (strtoupper ($period3))))
							{
								list ($num, $span) = preg_split ("/ /", $period3, 2);

								$days = 0; // Days start at 0.

								if (is_numeric ($num) && !is_numeric ($span))
									{
										$days = ($span === "D") ? 1 : $days;
										$days = ($span === "W") ? 7 : $days;
										$days = ($span === "M") ? 30 : $days;
										$days = ($span === "Y") ? 365 : $days;
									}

								$p3_days = (int)$num * (int)$days;
								$p3_time = $p3_days * 86400;
							}

						$start_time = strtotime ("now") + $p1_time + $p3_time;

						$start_time = ($start_time <= 0) ? strtotime ("now") : $start_time;

						$start_time = $start_time + 43200; // + 12 hours.
						// This prevents date clashes with Google's API server.

						return $start_time;
					}
			}
	}
?>