<?php
// @codingStandardsIgnoreFile
/**
* PayPal Cancellation Form processing.
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
* @package s2Member\PayPal
* @since 1.5
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit ("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_paypal_cancellation"))
	{
		/**
		* PayPal Cancellation Form processing.
		*
		* @package s2Member\PayPal
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_paypal_cancellation
			{
				/**
				* Handles processing of Pro-Form cancellations.
				*
				* @package s2Member\PayPal
				* @since 1.5
				*
				* @attaches-to ``add_action("init");``
				*
				* @return null|inner Return-value of inner routine.
				*/
				public static function paypal_cancellation ()
					{
						if(empty($_POST["s2member_pro_paypal_cancellation"]))
							return;

						if(empty($_POST["s2member_pro_paypal_cancellation"]["nonce"])
						|| !($nonce = $_POST["s2member_pro_paypal_cancellation"]["nonce"])
						|| !wp_verify_nonce($nonce, "s2member-pro-paypal-cancellation"))
							return;

						$GLOBALS["ws_plugin__s2member_pro_paypal_cancellation_response"] = array();
						$global_response = &$GLOBALS["ws_plugin__s2member_pro_paypal_cancellation_response"];

						$post_vars = c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($_POST["s2member_pro_paypal_cancellation"]));
						$post_vars["attr"] = (!empty($post_vars["attr"])) ? (array)c_ws_plugin__s2member_utils_arrays::maybe_unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($post_vars["attr"])) : array();
						$post_vars["attr"] = apply_filters("ws_plugin__s2member_pro_paypal_cancellation_post_attr", $post_vars["attr"], get_defined_vars());
						$post_vars = c_ws_plugin__s2member_utils_captchas::recaptcha_post_vars($post_vars);

						if(($error = c_ws_plugin__s2member_pro_paypal_responses::paypal_form_attr_validation_errors($post_vars["attr"])))
							{
								$global_response = $error;
								return;
							}
						if(($error = c_ws_plugin__s2member_pro_paypal_responses::paypal_form_submission_validation_errors("cancellation", $post_vars)))
							{
								$global_response = $error;
								return;
							}
						if(!is_user_logged_in() || !is_object($user = wp_get_current_user()) || !($user_id = (int)$user->ID))
							{
								$global_response = array("response" => _x('You\'re <strong>NOT</strong> logged in.', "s2member-front", "s2member"), "error" => true);
								return;
							}

						//260819.0417 Cancel through the owning configured PayPal API family; PayPal account management remains the final compatibility fallback.
						$result = c_ws_plugin__s2member_pro_paypal_utilities::paypal_subscription_cancel_for_user($user_id, 'Cancelled by subscriber.');
						if(!empty($result["ok"]))
							{
								$global_response = array("response" => _x('<strong>Billing termination confirmed.</strong> Your account has been cancelled.', "s2member-front", "s2member"));

								if(!empty($post_vars["attr"]["unsub"]))
									c_ws_plugin__s2member_list_servers::process_list_server_removals_against_current_user(TRUE);

								if(!empty($post_vars["attr"]["success"])
								&& ($custom_success_url = str_ireplace(array("%%s_response%%", "%%response%%"), array(urlencode(c_ws_plugin__s2member_utils_encryption::encrypt($global_response["response"])), urlencode($global_response["response"])), $post_vars["attr"]["success"]))
								&& ($custom_success_url = trim(preg_replace("/%%(.+?)%%/i", "", $custom_success_url))))
									wp_redirect(c_ws_plugin__s2member_utils_urls::add_s2member_sig($custom_success_url, "s2p-v")).exit();
							}
						else
							{
								$manage_url = !empty($result["manage_url"]) ? (string)$result["manage_url"] : c_ws_plugin__s2member_pro_paypal_utilities::paypal_subscription_manage_url($user_id);
								$global_response = array("response" => sprintf(_x('Please <a href="%s" rel="nofollow noopener" target="_blank">log in at PayPal</a> to cancel your Subscription.', "s2member-front", "s2member"), esc_attr($manage_url)), "error" => true);
							}
					}
			}
	}
