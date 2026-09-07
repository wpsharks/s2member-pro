<?php
// @codingStandardsIgnoreFile
/**
* CSS/JS integrations with theme.
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
	exit("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_css_js"))
	{
		/**
		* CSS/JS integrations with theme.
		*
		* @package s2Member\CSS_JS
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_css_js
			{
				/**
				 * Determines whether the legacy WordPress-backed CSS endpoint is still required.
				 *
				 * Built-in Pro CSS callbacks can be served directly as static stylesheets. Any
				 * custom callback, `all` listener, before-CSS listener, or callback reprioritization
				 * keeps the legacy endpoint so existing extension behavior and CSS ordering survive.
				 *
				 * @package s2Member\CSS_JS
				 * @since 260902.0737
				 *
				 * @param bool $required Whether dynamic CSS currently appears to be required.
				 * @return bool True if the legacy dynamic endpoint is required.
				 */
				public static function dynamic_css_required($required = FALSE)
					{
						if(!$required)
							return FALSE;
						if(has_action('ws_plugin__s2member_before_css') || has_filter('ws_plugin__s2member_pro_css_affects_gateways') || has_filter('ws_plugin__s2member_pro_available_gateways') || isset($GLOBALS['wp_filter']['all']))
							return TRUE;

						$hook = 'ws_plugin__s2member_during_css';
						$built_ins = array(
							'c_ws_plugin__s2member_pro_css_js::css',
							'c_ws_plugin__s2member_pro_paypal_css_js::paypal_css',
							'c_ws_plugin__s2member_pro_stripe_css_js::stripe_css',
							'c_ws_plugin__s2member_pro_authnet_css_js::authnet_css',
							'c_ws_plugin__s2member_pro_clickbank_css_js::clickbank_css',
							'c_ws_plugin__s2member_pro_google_css_js::google_css',
							'c_ws_plugin__s2member_pro_alipay_css_js::alipay_css',
							'c_ws_plugin__s2member_pro_ccbill_css_js::ccbill_css',
						);
						$callbacks = isset($GLOBALS['wp_filter'][$hook]) ? $GLOBALS['wp_filter'][$hook] : array();
						if(is_object($callbacks) && isset($callbacks->callbacks))
							$callbacks = $callbacks->callbacks;

						//260902.1520 Inspect both legacy filter arrays and modern WP_Hook callbacks without mutating callback order while deciding whether static delivery is safe.
						foreach((array) $callbacks as $priority => $priority_callbacks)
							foreach((array) $priority_callbacks as $callback)
								if((int) $priority !== 10 || !is_array($callback) || !isset($callback['function'], $callback['accepted_args']) || !in_array($callback['function'], $built_ins, TRUE) || (int) $callback['accepted_args'] !== 1)
									return TRUE;

						return FALSE;
					}

				/**
				 * Appends built-in Pro CSS sources to the generated Pro or combined frontend stylesheet.
				 *
				 * @package s2Member\CSS_JS
				 * @since 260903.0525
				 *
				 * @attaches-to ``add_filter('ws_plugin__s2member_static_pro_css_sources');``
				 *
				 * @param array $sources Existing ordered CSS source definitions.
				 * @param array $vars    Framework asset-build context.
				 * @return array Ordered CSS source definitions.
				 */
				public static function static_css_sources($sources = array(), $vars = FALSE)
					{
						if(!is_array($sources))
							$sources = array();

						$hook = 'ws_plugin__s2member_during_css';
						$core_callback = 'c_ws_plugin__s2member_pro_css_js::css';
						$core_css = has_action($hook, $core_callback) !== FALSE;
						$affects_gateways = (bool)apply_filters('ws_plugin__s2member_pro_css_affects_gateways', TRUE);
						$callbacks = isset($GLOBALS['wp_filter'][$hook]) ? $GLOBALS['wp_filter'][$hook] : array();
						if(is_object($callbacks) && isset($callbacks->callbacks))
							$callbacks = $callbacks->callbacks;
						$callbacks = isset($callbacks[10]) ? (array)$callbacks[10] : array();

						$dir = $GLOBALS['WS_PLUGIN__']['s2member_pro']['c']['dir'];
						$images = $GLOBALS['WS_PLUGIN__']['s2member_pro']['c']['dir_url'].'/src/images/';
						$styles = array(
							$core_callback => array('file' => $dir.'/src/includes/s2member-pro.css', 'replacements' => array('../images/' => $images), 'preserve_header' => TRUE),
						);
						foreach(array('paypal', 'stripe', 'authnet', 'clickbank', 'google', 'alipay', 'ccbill') as $gateway)
						{
							$callback = 'c_ws_plugin__s2member_pro_'.$gateway.'_css_js::'.$gateway.'_css';
							$styles[$callback] = array('file' => $dir.'/src/includes/separates/gateways/'.$gateway.'/'.$gateway.'.css', 'replacements' => array('../../../../images/' => $images));
						}

						//260903.1918 Append Pro core/enabled-gateway CSS in exact legacy callback order to either the separate Pro file or the optional combined file.
						foreach($callbacks as $callback)
						{
							if(!is_array($callback) || !isset($callback['function'], $styles[$callback['function']]))
								continue;
							if($callback['function'] !== $core_callback && $affects_gateways && !$core_css)
								continue;
							$sources[] = $styles[$callback['function']];
						}
						return $sources;
					}


				/**
				 * Determines whether the legacy dynamic JavaScript endpoint is still required.
				 *
				 * Static JS supports Pro core plus PayPal, Stripe, Authorize.Net, and ClickBank. Deprecated
				 * gateway callbacks, custom callbacks/order changes, `all`, and full API-constants exposure
				 * deliberately keep the existing dynamic endpoint during the beta.
				 *
				 * @package s2Member\CSS_JS
				 * @since 260903.0437
				 *
				 * @param bool $required Whether dynamic JS currently appears to be required.
				 * @return bool True if legacy dynamic JS is required.
				 */
				public static function dynamic_js_required($required = FALSE)
				{
					if(!$required)
						return FALSE;
					if(apply_filters('ws_plugin__s2member_js_api_constants_enable', FALSE) || has_action('ws_plugin__s2member_before_js_w_globals') || has_filter('ws_plugin__s2member_pro_available_gateways') || isset($GLOBALS['wp_filter']['all']))
						return TRUE;

					$hook = 'ws_plugin__s2member_during_js_w_globals';
					$built_ins = array(
						'c_ws_plugin__s2member_pro_css_js::js_w_globals',
						'c_ws_plugin__s2member_pro_paypal_css_js::paypal_js_w_globals',
						'c_ws_plugin__s2member_pro_stripe_css_js::stripe_js_w_globals',
						'c_ws_plugin__s2member_pro_authnet_css_js::authnet_js_w_globals',
						'c_ws_plugin__s2member_pro_clickbank_css_js::clickbank_js_w_globals',
					);
					$callbacks = isset($GLOBALS['wp_filter'][$hook]) ? $GLOBALS['wp_filter'][$hook] : array();
					if(is_object($callbacks) && isset($callbacks->callbacks))
						$callbacks = $callbacks->callbacks;

					foreach((array)$callbacks as $priority => $priority_callbacks)
						foreach((array)$priority_callbacks as $callback)
							if((int)$priority !== 10 || !is_array($callback) || !isset($callback['function'], $callback['accepted_args']) || !in_array($callback['function'], $built_ins, TRUE) || (int)$callback['accepted_args'] !== 1)
								return TRUE;

					return FALSE;
				}

				/**
				 * Appends the shipped Pro static JavaScript data map for separate or combined static JS.
				 *
				 * @package s2Member\CSS_JS
				 * @since 260906.0738
				 *
				 * @attaches-to ``add_filter('ws_plugin__s2member_static_js_data_map_paths');``
				 *
				 * @param array  $paths Existing data-map paths.
				 * @param string $id    Logical generated JavaScript filename.
				 * @param array  $vars  Framework context.
				 * @return array Data-map paths.
				 */
				public static function static_js_data_map_paths($paths = array(), $id = '', $vars = FALSE)
				{
					$paths = is_array($paths) ? $paths : array();
					if(!method_exists('c_ws_plugin__s2member_utils_assets', 'static_js_text_delivery') || c_ws_plugin__s2member_utils_assets::static_js_text_delivery() !== 'page')
						return $paths;
					if($id === 's2member-pro.js' || ($id === 's2member.js' && !empty($GLOBALS['WS_PLUGIN__']['s2member']['o']['static_assets_combine'])))
						$paths['p'] = $GLOBALS['WS_PLUGIN__']['s2member_pro']['c']['dir'].'/src/includes/s2member-pro.js.php';
					return $paths;
				}

				/**
				 * Returns Pro gateway globals that must remain page-local for static JavaScript delivery.
				 *
				 * @package s2Member\CSS_JS
				 * @since 260906.0738
				 *
				 * @attaches-to ``add_filter('ws_plugin__s2member_static_js_inline_globals');``
				 *
				 * @param string $globals Existing inline globals.
				 * @param array  $assets  Active static JavaScript assets.
				 * @param array  $vars    Framework context.
				 * @return string Inline Pro gateway globals.
				 */
				public static function static_js_inline_globals($globals = '', $assets = array(), $vars = FALSE)
				{
					if(!method_exists('c_ws_plugin__s2member_utils_assets', 'static_js_text_delivery') || c_ws_plugin__s2member_utils_assets::static_js_text_delivery() !== 'page')
						return $globals;
					$hook = 'ws_plugin__s2member_during_js_w_globals';
					$callbacks = isset($GLOBALS['wp_filter'][$hook]) ? $GLOBALS['wp_filter'][$hook] : array();
					if(is_object($callbacks) && isset($callbacks->callbacks))
						$callbacks = $callbacks->callbacks;
					$callbacks = isset($callbacks[10]) ? (array)$callbacks[10] : array();
					$global_callbacks = array(
						'c_ws_plugin__s2member_pro_paypal_css_js::paypal_js_w_globals' => 'c_ws_plugin__s2member_pro_paypal_css_js::paypal_js_globals',
						'c_ws_plugin__s2member_pro_stripe_css_js::stripe_js_w_globals' => 'c_ws_plugin__s2member_pro_stripe_css_js::stripe_js_globals',
						'c_ws_plugin__s2member_pro_authnet_css_js::authnet_js_w_globals' => 'c_ws_plugin__s2member_pro_authnet_css_js::authnet_js_globals',
						'c_ws_plugin__s2member_pro_clickbank_css_js::clickbank_js_w_globals' => 'c_ws_plugin__s2member_pro_clickbank_css_js::clickbank_js_globals',
					);
					foreach($callbacks as $callback)
						if(is_array($callback) && isset($callback['function'], $global_callbacks[$callback['function']]))
							$globals .= (($globals !== '') ? "\n" : '').call_user_func($global_callbacks[$callback['function']]);
					return $globals;
				}


				/**
				 * Appends built-in Pro JavaScript sources to the generated Pro or combined frontend script.
				 *
				 * @package s2Member\CSS_JS
				 * @since 260903.0525
				 *
				 * @attaches-to ``add_filter('ws_plugin__s2member_static_pro_js_sources');``
				 *
				 * @param array $sources Existing ordered JavaScript source definitions.
				 * @param array $vars    Framework asset-build context.
				 * @return array Ordered JavaScript source definitions.
				 */
				public static function static_js_sources($sources = array(), $vars = FALSE)
					{
						if(!is_array($sources))
							$sources = array();

						$hook = 'ws_plugin__s2member_during_js_w_globals';
						$callbacks = isset($GLOBALS['wp_filter'][$hook]) ? $GLOBALS['wp_filter'][$hook] : array();
						if(is_object($callbacks) && isset($callbacks->callbacks))
							$callbacks = $callbacks->callbacks;
						$callbacks = isset($callbacks[10]) ? (array)$callbacks[10] : array();

						$dir = $GLOBALS['WS_PLUGIN__']['s2member_pro']['c']['dir'];
						$dir_url = $GLOBALS['WS_PLUGIN__']['s2member_pro']['c']['dir_url'];
						//260906.2049 Gateway JavaScript rendered into static files still needs these source-template URLs.
						$template_vars = array('vars' => array('u' => $dir_url, 'i' => $dir_url.'/src/images'));
						$page_text = method_exists('c_ws_plugin__s2member_utils_assets', 'static_js_text_delivery') && c_ws_plugin__s2member_utils_assets::static_js_text_delivery() === 'page';
						$data_source = array('data_map' => $dir.'/src/includes/s2member-pro.js.php', 'data_key' => 'p');
						$core_callback = 'c_ws_plugin__s2member_pro_css_js::js_w_globals';
						$sources_by_callback = array(
							$core_callback => array(
								array('file' => $dir.'/src/includes/s2member-pro.js', 'prefix' => self::js_globals(), 'preserve_header' => TRUE),
							),
							'c_ws_plugin__s2member_pro_stripe_css_js::stripe_js_w_globals' => array(
								($page_text) ? array_merge(array('file' => $dir.'/src/includes/separates/gateways/stripe/stripe.js'), $data_source) : array('file' => $dir.'/src/includes/separates/gateways/stripe/stripe.js', 'render' => TRUE, 'vars' => $template_vars, 'prefix' => c_ws_plugin__s2member_pro_stripe_css_js::stripe_js_globals()),
							),
							'c_ws_plugin__s2member_pro_authnet_css_js::authnet_js_w_globals' => array(
								($page_text) ? array_merge(array('file' => $dir.'/src/includes/separates/gateways/authnet/authnet.js'), $data_source) : array('file' => $dir.'/src/includes/separates/gateways/authnet/authnet.js', 'render' => TRUE, 'vars' => $template_vars, 'prefix' => c_ws_plugin__s2member_pro_authnet_css_js::authnet_js_globals()),
							),
							'c_ws_plugin__s2member_pro_clickbank_css_js::clickbank_js_w_globals' => array(
								array('file' => $dir.'/src/includes/separates/gateways/clickbank/clickbank.js', 'render' => TRUE, 'vars' => $template_vars, 'prefix' => c_ws_plugin__s2member_pro_clickbank_css_js::clickbank_js_globals()),
							),
						);
						$paypal_sources = array(
							($page_text) ? array_merge(array('file' => $dir.'/src/includes/separates/gateways/paypal/paypal.js'), $data_source) : array('file' => $dir.'/src/includes/separates/gateways/paypal/paypal.js', 'render' => TRUE, 'vars' => $template_vars, 'prefix' => c_ws_plugin__s2member_pro_paypal_css_js::paypal_js_globals()),
						);
						if(c_ws_plugin__s2member_paypal_utilities::paypal_checkout_is_enabled())
							$paypal_sources[] = $dir.'/src/includes/separates/gateways/paypal/paypal-checkout.js';
						$sources_by_callback['c_ws_plugin__s2member_pro_paypal_css_js::paypal_js_w_globals'] = $paypal_sources;

						//260903.0525 Preserve the existing supported gateway callback order; PayPal Checkout remains after the legacy PayPal handlers and deprecated gateway JS keeps dynamic delivery.
						foreach($callbacks as $callback)
							if(is_array($callback) && isset($callback['function'], $sources_by_callback[$callback['function']]))
								foreach($sources_by_callback[$callback['function']] as $source)
									$sources[] = $source;
						return $sources;
					}


				/**
				 * Returns site-wide Pro JavaScript globals for generated/legacy frontend assets.
				 *
				 * @package s2Member\CSS_JS
				 * @since 260903.0437
				 *
				 * @return string JavaScript declarations.
				 */
				public static function js_globals()
				{
					return "var S2MEMBER_PRO_VERSION = '".c_ws_plugin__s2member_utils_strings::esc_js_sq(S2MEMBER_PRO_VERSION)."';";
				}

				/**
				* Adds Pro Add-on CSS.
				*
				* @package s2Member\CSS_JS
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_css");``
				*
				* @param array $vars Expects array of defined variables, passed in by the Action Hook.
				* @return null
				*/
				public static function css ($vars = FALSE)
					{
						$u = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"];
						$i = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/src/images";

						echo "\n"; // Add a line break before inclusion.

						//260902.1520 The source stylesheet now uses browser-relative image URLs for direct static delivery; convert them back to absolute Pro URLs only on the legacy combined endpoint.
						ob_start();
						include_once dirname (dirname (__FILE__)) . "/s2member-pro.css";
						echo str_replace("../images/", $i . "/", ob_get_clean());

						return; // Return unformity.
					}
				/**
				* Adds Pro Add-on JavaScript.
				*
				* @package s2Member\CSS_JS
				* @since 1.5
				*
				* @attaches-to ``add_action("ws_plugin__s2member_during_js_w_globals");``
				*
				* @param array $vars Expects array of defined variables, passed in by the Action Hook.
				* @return null
				*/
				public static function js_w_globals ($vars = FALSE)
					{
						$u = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"];
						$i = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/src/images";

						echo "\n" . self::js_globals() . "\n"; // Add a line break before inclusion.
						include_once dirname (dirname (__FILE__)) . "/s2member-pro.min.js";

						return; // Return unformity.
					}
			}
	}
