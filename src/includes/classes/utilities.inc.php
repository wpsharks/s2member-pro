<?php
// @codingStandardsIgnoreFile
/**
* s2Member Pro utilities.
*
* Copyright: © 2009-2011
* {@link http://websharks-inc.com/ WebSharks, Inc.}
* (coded in the USA)
*
* This WordPress plugin (s2Member Pro) is comprised of two parts:
*
* o (1) Its PHP code is licensed under the GPL license, as is WordPress.
* 	You should have received a copy of the GNU General Public License,
* 	along with this software. In the main directory, see: /licensing/
* 	If not, see: {@link http://www.gnu.org/licenses/}.
*
* o (2) All other parts of (s2Member Pro); including, but not limited to:
* 	the CSS code, some JavaScript code, images, and design;
* 	are licensed according to the license purchased.
* 	See: {@link http://s2member.com/prices/}
*
* Unless you have our prior written consent, you must NOT directly or indirectly license,
* sub-license, sell, resell, or provide for free; part (2) of the s2Member Pro Add-on;
* or make an offer to do any of these things. All of these things are strictly
* prohibited with part (2) of the s2Member Pro Add-on.
*
* Your purchase of s2Member Pro includes free lifetime upgrades via s2Member.com
* (i.e., new features, bug fixes, updates, improvements); along with full access
* to our video tutorial library: {@link http://s2member.com/videos/}
*
* @package s2Member\Utilities
* @since 1.5
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_utilities"))
	{
		/**
		* s2Member Pro utilities.
		*
		* @package s2Member\Utilities
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_utilities
			{
				/**
				* Converts Currency Codes to Currency Symbols (deprecated).
				*
				* @package s2Member\Utilities
				* @since 1.5
				*
				* @param string $currency Expects a 3 character Currency Code.
				* @return string A Currency Symbol. Defaults to the `$` sign.
				*
				* @deprecated Starting with v110531, please use:
				* 	``c_ws_plugin__s2member_utils_cur::symbol()``
				*/
				public static function currency_symbol ($currency = FALSE)
					{
						return c_ws_plugin__s2member_utils_cur::symbol ($currency);
					}
				/**
				* Expands a state/province abbrev into it's full version.
				*
				* This works for the United States and Canada only.
				*
				* @package s2Member\Utilities
				* @since 1.5
				*
				* @param string $state A state/province abbreviation.
				* @param string $country A country code. One of `US|CA`.
				* @return string The full state/province name.
				*/
				public static function full_state ($state = FALSE, $country = FALSE)
					{
						static $lines; // Optimizes this routine for repeated usage.

						if (strlen ($state = strtoupper ($state)) === 2 && preg_match ("/^US|CA$/", ($country = strtoupper ($country))))
							{
								if (!isset ($lines[$country])) // If $lines are NOT already established.
									{
										if ($country === "US") // Handle lookups for the United States.
											{
												$txt = file_get_contents (dirname (dirname (__FILE__)) . "/usps-states.txt");
											}
										else if ($country === "CA") // Lookups for Canada.
											{
												$txt = file_get_contents (dirname (dirname (__FILE__)) . "/ca-provinces.txt");
											}

										$lines[$country] = preg_split ("/[\r\n\t]+/", trim (strtoupper ($txt)));
									}

								foreach ($lines[$country] as $line) // Find full version.

									if ($line = trim ($line)) // Do NOT process empty lines.
										{
											list ($full, $abbr) = preg_split ("/;/", trim ($line));
											if ($abbr === $state && $full)
												return ucwords ($full);
										}
							}
						return $state; // Full state name.
					}
				/**
				 * Cancels a subscription using a gateway-aware dispatch routine.
				 *
				 * This is designed for replacement/modification flows where the old subscription must be
				 * canceled after a new checkout begins. Callers should pass the previously captured
				 * subscription details from before proxy/IPN processing updates the user's current profile.
				 *
				 * For PayPal-family subscriptions, this uses captured signup/proxy vars first when available
				 * as a product hint, and then falls back to trying other configured cancellation routines
				 * that are still plausible for the captured subscription context.
				 *
				 * Gateway/product handlers may no longer be configured or loaded by the current installation.
				 * In those cases this routine fails safely and returns `FALSE` instead of calling unavailable code.
				 *
				 * @package s2Member\Pro
				 * @since 260407
				 *
				 * @param string|bool $subscr_gateway Previously captured `s2member_subscr_gateway`.
				 * @param string|bool $subscr_id Previously captured `s2member_subscr_id`.
				 * @param string|bool $subscr_baid Previously captured `s2member_subscr_baid`.
				 * @param string|bool $subscr_cid Previously captured `s2member_subscr_cid`.
				 * @param array $ipn_signup_vars Previously captured signup/proxy vars for product-specific routing.
				 * @param bool $immediate Optional. If false, cancel at period end where supported.
				 *
				 * @return bool True if a cancellation request was accepted; else false.
				 */
				public static function cancel_gateway_subscription ($subscr_gateway = FALSE, $subscr_id = FALSE, $subscr_baid = FALSE, $subscr_cid = FALSE, $ipn_signup_vars = array(), $immediate = TRUE)
					{
						$subscr_gateway = strtolower((string)$subscr_gateway);
						$subscr_id = (string)$subscr_id;
						$subscr_baid = (string)$subscr_baid;
						$subscr_cid = (string)$subscr_cid;
						$ipn_signup_vars = (is_array($ipn_signup_vars)) ? $ipn_signup_vars : array();

						if(!$subscr_gateway || !$subscr_id)
							return FALSE;

						switch($subscr_gateway)
							{
								case "stripe":
									if(!$subscr_cid || !class_exists("c_ws_plugin__s2member_pro_stripe_utilities"))
										return FALSE;

									c_ws_plugin__s2member_pro_stripe_utilities::set_replacement_cancellation_guard($subscr_id);

									if(!is_object(c_ws_plugin__s2member_pro_stripe_utilities::cancel_customer_subscription($subscr_cid, $subscr_id, !$immediate)))
										{
											c_ws_plugin__s2member_pro_stripe_utilities::clear_replacement_cancellation_guard($subscr_id);
											return FALSE;
										}
									return TRUE;

								case "authnet":
									if(!class_exists("c_ws_plugin__s2member_pro_authnet_utilities"))
										return FALSE;

									return (($authnet = c_ws_plugin__s2member_pro_authnet_utilities::authnet_arb_response(array("x_method" => "cancel", "x_subscription_id" => $subscr_id))) && empty($authnet["__error"])) ? TRUE : FALSE;

								case "paypal":
									if(!empty($ipn_signup_vars["s2member_paypal_proxy_use"]) && $ipn_signup_vars["s2member_paypal_proxy_use"] === "paypal_checkout")
										{
											$paypal_checkout_creds = c_ws_plugin__s2member_paypal_utilities::paypal_checkout_creds();

											if(!empty($paypal_checkout_creds["client_id"]) && !empty($paypal_checkout_creds["secret"])
											   && ($paypal_checkout = c_ws_plugin__s2member_paypal_utilities::paypal_checkout_subscription_cancel($subscr_id, "Cancelled by replacement checkout."))
											   && !empty($paypal_checkout["code"]) && ($paypal_checkout["code"] === 204 || ($paypal_checkout["code"] >= 200 && $paypal_checkout["code"] <= 299)))
												return TRUE;
										}

									if(!empty($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_api_username"])
									   && !empty($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_api_password"])
									   && !empty($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_api_signature"])
									   && ($paypal = c_ws_plugin__s2member_paypal_utilities::paypal_api_response(array("METHOD" => "ManageRecurringPaymentsProfileStatus", "ACTION" => "Cancel", "PROFILEID" => $subscr_id)))
									   && empty($paypal["__error"]))
										return TRUE;

									if(!empty($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_payflow_api_username"])
									   && !empty($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_payflow_api_partner"])
									   && !empty($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_payflow_api_vendor"])
									   && !empty($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_payflow_api_password"])
									   && class_exists("c_ws_plugin__s2member_pro_paypal_utilities")
									   && c_ws_plugin__s2member_pro_paypal_utilities::payflow_cancel_profile($subscr_id, $subscr_baid))
										return TRUE;
							}
						return FALSE;
					}
			}
	}
