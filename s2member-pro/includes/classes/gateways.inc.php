<?php
/**
* s2Member Pro Gateways.
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
* @package s2Member\Gateways
* @since 1.5
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_gateways"))
	{
		/**
		* s2Member Pro Gateways.
		*
		* @package s2Member\Gateways
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_gateways
			{
				/**
				* Array of available Payment Gateways supported by s2Member Pro.
				*
				* @package s2Member\Gateways
				* @since 1.5
				*
				* @return array Array of available Payment Gateways.
				*/
				public static function available_gateways () // Payment Gateways available.
					{
						$gateways = array ("alipay" => "<strong>AliPay®</strong> <em>(w/ Buttons)</em><br />&uarr; supports Buy Now transactions only.", "authnet" => "<strong>Authorize.Net®</strong> <em>(w/ Pro Forms)</em><br />&uarr; supports Buy Now &amp; Recurring Products.", "ccbill" => "<strong>ccBill®</strong> <em>(w/ Buttons)</em><br />&uarr; supports Buy Now &amp; Recurring Products.", "clickbank" => "<strong>ClickBank®</strong> <em>(w/ Buttons)</em><br />&uarr; supports Buy Now &amp; Recurring Products.", "google" => "<strong>Google® Checkout</strong> <em>(w/ Buttons)</em><br />&uarr; supports Buy Now &amp; Recurring Products.", "paypal" => "<strong>PayPal® Website Payments Pro</strong> <em>(w/ Pro Forms)</em><br />&uarr; supports Buy Now &amp; Recurring Products.");

						return apply_filters ("ws_plugin__s2member_pro_available_gateways", $gateways, get_defined_vars ());
					}
				/**
				* Adds to the list of Payment Gateways in User Profile management panels.
				*
				* @package s2Member\Gateways
				* @since 1.5
				*
				* @attaches-to ``add_filter("ws_plugin__s2member_profile_s2member_subscr_gateways");``
				*
				* @param array $gateways Expects an array of Payment Gateways, passed through by the Filter.
				* @return array Array of Payment Gateways to appear in Profile editing panels.
				*/
				public static function profile_subscr_gateways ($gateways = FALSE)
					{
						$available_gateways = array_keys (c_ws_plugin__s2member_pro_gateways::available_gateways ());

						foreach (($others = array ("alipay" => "AliPay® (code: alipay)", "authnet" => "Authorize.Net® (code: authnet)", "ccbill" => "ccBill® (code: ccbill)", "clickbank" => "ClickBank® (code: clickbank)", "google" => "Google® Checkout (code: google)")) as $other => $gateway)
							if (!in_array ($other, $available_gateways))
								unset($others[$other]);

						return apply_filters ("ws_plugin__s2member_pro_profile_subscr_gateways", array_unique (array_merge ((array)$gateways, $others)), get_defined_vars ());
					}
				/**
				* Loads Hooks/Functions/Codes for other Payment Gateways.
				*
				* @package s2Member\Gateways
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_after_loaded");``
				*
				* @return null
				*/
				public static function load_gateways () // Load Hooks/Functions/Codes for other Gateways.
					{
						foreach (array_keys (c_ws_plugin__s2member_pro_gateways::available_gateways ()) as $gateway)
							if (in_array ($gateway, $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_gateways_enabled"]))
								{
									include_once dirname (dirname (__FILE__)) . "/separates/gateways/" . $gateway . "/" . $gateway . "-hooks.inc.php";
									include_once dirname (dirname (__FILE__)) . "/separates/gateways/" . $gateway . "/" . $gateway . "-funcs.inc.php";
									include_once dirname (dirname (__FILE__)) . "/separates/gateways/" . $gateway . "/" . $gateway . "-codes.inc.php";
								}
						return /* Return for uniformity. */;
					}
			}
	}
?>