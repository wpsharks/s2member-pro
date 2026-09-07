<?php
// @codingStandardsIgnoreFile
/**
* PayPal CSS/JS for theme integration.
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
* @package s2Member\CSS_JS
* @since 1.5
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit ("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_paypal_css_js"))
	{
		/**
		* PayPal CSS for theme integration.
		*
		* @package s2Member\CSS_JS
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_paypal_css_js
			{
				/**
				* Adds the CSS for this Payment Gateway.
				*
				* @package s2Member\CSS_JS
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_css");``
				*
				* @param array $vars Expects an array of defined vars to be passed in by the Action Hook.
				* @return null
				*/
				public static function paypal_css ($vars = FALSE)
					{
						$u = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"];
						$i = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/src/images";

						if (!apply_filters("ws_plugin__s2member_pro_css_affects_gateways", true) // Does it affect this?
						|| has_action ("ws_plugin__s2member_during_css", "c_ws_plugin__s2member_pro_css_js::css")) // Only if CSS loads.
							// This check allows a site owner to disable all CSS by removing the main CSS Hook in one shot.
							{
								echo "\n"; // Add a line break before inclusion.

								//260902.1520 Preserve absolute image URLs when this static stylesheet is emitted through the legacy Framework CSS endpoint.
								ob_start();
								include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/separates/gateways/paypal/paypal.css";
								echo str_replace("../../../../images/", $i . "/", ob_get_clean());
							}

						return /* Return for uniformity. */;
					}
				/**
				 * Returns site-wide PayPal JavaScript globals for generated/legacy frontend assets.
				 *
				 * @package s2Member\CSS_JS
				 * @since 260903.0453
				 *
				 * @return string JavaScript declarations.
				 */
				public static function paypal_js_globals()
					{
						$g = "var S2MEMBER_PRO_PAYPAL_GATEWAY = true,";
						$ppco_enabled = c_ws_plugin__s2member_paypal_utilities::paypal_checkout_is_enabled();

						if($ppco_enabled)
							{
								$ppco_sandbox = c_ws_plugin__s2member_paypal_utilities::paypal_checkout_is_sandbox();
								$ppco_client_id = (string)$GLOBALS["WS_PLUGIN__"]["s2member"]["o"][(($ppco_sandbox) ? "paypal_checkout_sandbox_client_id" : "paypal_checkout_client_id")];
								$ppco_config = array(
									'enabled'   => TRUE,
									'sandbox'   => $ppco_sandbox,
									'client_id' => $ppco_client_id,
									'messages'  => array(
										'prepare_failed'          => _x('Unable to prepare PayPal Checkout. Please try again.', 's2member-front', 's2member'),
										'payment_failed'          => _x('PayPal Checkout could not be completed. Please try again.', 's2member-front', 's2member'),
										'payment_recovering'      => _x('PayPal is taking longer than expected. Confirming your payment...', 's2member-front', 's2member'),
										'payment_unresolved'      => _x('PayPal could not confirm the payment yet. Please try again. If this continues, contact support for assistance.', 's2member-front', 's2member'),
										'subscription_failed'     => _x('PayPal subscription could not be completed. Please try again.', 's2member-front', 's2member'),
										'subscription_recovering' => _x('PayPal is taking longer than expected. Confirming your subscription...', 's2member-front', 's2member'),
										'subscription_unresolved' => _x('PayPal could not confirm the subscription yet. Please try again. If this continues, contact support for assistance.', 's2member-front', 's2member'),
										'sdk_failed'              => _x('PayPal Checkout could not be loaded. Please refresh the page and try again.', 's2member-front', 's2member'),
										'cancelled'               => _x('PayPal Checkout was cancelled.', 's2member-front', 's2member'),
									),
								);

								//260818.2010 Expose only public Checkout browser configuration; REST client secrets remain server-side.
								$g .= "S2MEMBER_PRO_PAYPAL_CHECKOUT = ".wp_json_encode($ppco_config).",";
							}

						return trim($g, " ,").";";
					}

				/**
				* Adds the JavaScript for this Payment Gateway.
				*
				* @package s2Member\CSS_JS
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_js_w_globals");``
				*
				* @param array $vars Expects an array of defined vars to be passed in by the Action Hook.
				* @return null
				*/
				public static function paypal_js_w_globals ($vars = FALSE)
					{
						$ppco_enabled = c_ws_plugin__s2member_paypal_utilities::paypal_checkout_is_enabled();
						$g = self::paypal_js_globals();

						$u = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"];
						$i = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/src/images";

						echo "\n" . $g . "\n"; // Add a line break before inclusion.

						include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/separates/gateways/paypal/paypal.min.js";

						//260818.2010 Load modern Checkout UI after legacy form handlers so its PayPal/card switching runs last.
						if($ppco_enabled)
							include_once dirname (dirname (dirname (dirname (__FILE__)))) . "/separates/gateways/paypal/paypal-checkout.js";

						return /* Return for uniformity. */;
					}
			}
	}
