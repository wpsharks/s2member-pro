<?php
// @codingStandardsIgnoreFile
/**
* PayPal Specific Post/Page Forms (inner processing routines).
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
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_paypal_sp_checkout_in"))
	{
		/**
		* PayPal Specific Post/Page Forms (inner processing routines).
		*
		* @package s2Member\PayPal
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_paypal_sp_checkout_in
			{
				/**
				 * Sends a JSON response for modern PayPal Checkout preparation.
				 *
				 * @since 260818
				 *
				 * @param array $result Response data.
				 *
				 * @return void Exits after sending JSON.
				 */
				protected static function sp_checkout_json_exit($result = array())
					{
						if(!headers_sent())
							{
								nocache_headers();
								header('Content-Type: application/json; charset='.get_option('blog_charset'));
							}

						echo wp_json_encode(is_array($result) ? $result : array());
						exit();
					}

				/**
				* Handles processing of Pro-Forms for Specific Post/Page checkout.
				*
				* @package s2Member\PayPal
				* @since 1.5
				*
				* @attaches-to ``add_action("init");``
				*
				* @return null Or exits script execution after a custom URL redirection; or upon Express Checkout redirection.
				*/
				public static function sp_checkout()
					{
						//260817.2318 Initialize REST state for PHP 8.x, then resolve any saved REST return.
						$ppco_post_vars = $ppco_return_vars = $ppco_raw_return_vars = array();
						$ppco_rest_state = $ppco_rest_invoice = "";
						$ppco_prepare = (!empty($_POST["s2member_pro_paypal_sp_checkout"]["paypal_checkout_op"]) && $_POST["s2member_pro_paypal_sp_checkout"]["paypal_checkout_op"] === "prepare");
						$ppco_free_fallback = (!empty($_POST["s2member_pro_paypal_sp_checkout"]["paypal_checkout_op"]) && $_POST["s2member_pro_paypal_sp_checkout"]["paypal_checkout_op"] === "free");
						$ppco_rest_route = (!empty($_GET["s2member_paypal_xco"]) && $_GET["s2member_paypal_xco"] === "s2member_pro_paypal_sp_checkout_rest_return");
						$ppco_rest_return = ($ppco_rest_route
						&& !empty($_GET["s2member_paypal_rest_state"]) && ($ppco_rest_state = preg_replace("/[^a-f0-9]/i", "", (string)$_GET["s2member_paypal_rest_state"]))
						&& ($ppco_post_vars = get_transient("s2m_".md5("s2member_transient_paypal_checkout_".$ppco_rest_state))) && is_array($ppco_post_vars));

						if((!empty($_POST["s2member_pro_paypal_sp_checkout"]["nonce"]) && ($nonce = $_POST["s2member_pro_paypal_sp_checkout"]["nonce"]) && wp_verify_nonce($nonce, "s2member-pro-paypal-sp-checkout"))
						|| (!empty($_GET["s2member_paypal_xco"]) && $_GET["s2member_paypal_xco"] === "s2member_pro_paypal_sp_checkout_return" // PayPal Express Checkout with $_GET["token"] & $_GET["PayerID"].
						&& !empty($_GET["token"]) && ($_GET["token"] = esc_html($_GET["token"])) && (empty($_GET["PayerID"]) || ($_GET["PayerID"] = esc_html($_GET["PayerID"]))) // PayerID is not required.
						&& ($xco_post_vars = get_transient("s2m_".md5("s2member_transient_express_checkout_".$_GET["token"]))))
						|| $ppco_rest_route)
							{
								$GLOBALS["ws_plugin__s2member_pro_paypal_sp_checkout_response"] = array(); // This holds the global response details.
								$global_response = &$GLOBALS["ws_plugin__s2member_pro_paypal_sp_checkout_response"]; // This is a shorter reference.

								//260818.2056 Preparation is a modern Checkout operation; never fall through to legacy Express when REST is unavailable.
								if($ppco_prepare && !c_ws_plugin__s2member_paypal_utilities::paypal_checkout_is_enabled())
									self::sp_checkout_json_exit(array('error' => 'pro_checkout_not_enabled', 'message' => _x('PayPal Checkout is not enabled.', 's2member-front', 's2member')));

								if(!empty($xco_post_vars)) // A customer is returning from Express Checkout @ PayPal?
									$_POST = $xco_post_vars; // POST vars from submission prior to Express Checkout.
								else if($ppco_rest_route)
									{
										$ppco_raw_return_vars = is_array($_POST) ? stripslashes_deep($_POST) : array();

										//260817.2318 Verify saved REST state and the signed browser return before restoring the Pro-Form submission.
										if(!$ppco_rest_return || !is_array($ppco_return_vars = c_ws_plugin__s2member_paypal_utilities::paypal_postvars()) || !$ppco_return_vars
										|| empty($ppco_return_vars["proxy_verified"]) || $ppco_return_vars["proxy_verified"] !== "paypal"
										|| empty($ppco_raw_return_vars["s2member_paypal_proxy_use"]) || $ppco_raw_return_vars["s2member_paypal_proxy_use"] !== "pro-emails"
										|| empty($ppco_post_vars["post"]) || !is_array($ppco_post_vars["post"]))
											{
												c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
													'ppco'        => 'pro-sp',
													'event'       => 'sp_rest_return_verification_failed',
													'invoice'     => !empty($ppco_post_vars["invoice"]) ? (string)$ppco_post_vars["invoice"] : '',
													'state_found' => $ppco_rest_return ? '1' : '0',
													'proxy_use'   => !empty($ppco_raw_return_vars["s2member_paypal_proxy_use"]) ? (string)$ppco_raw_return_vars["s2member_paypal_proxy_use"] : '',
												));

												$global_response = array("response" => _x("<strong>Oops.</strong> Unable to verify the completed PayPal Checkout transaction. Please contact Support for assistance.", "s2member-front", "s2member"), "error" => true);
												return;
											}

										$ppco_rest_invoice = !empty($ppco_post_vars["invoice"]) ? (string)$ppco_post_vars["invoice"] : "";
										$_POST = $ppco_post_vars["post"]; // Restore the validated Pro-Form submission after the Framework REST handler captures the order.
									}

								$post_vars           = c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($_POST["s2member_pro_paypal_sp_checkout"]));
								//260808 Safely unserialize the form attributes.
								$post_vars["attr"]   = (!empty($post_vars["attr"])) ? (array)c_ws_plugin__s2member_utils_arrays::maybe_unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($post_vars["attr"])) : array();
								$post_vars["attr"]   = apply_filters("ws_plugin__s2member_pro_paypal_sp_checkout_post_attr", $post_vars["attr"], get_defined_vars());
								if(!empty($xco_post_vars) || !empty($ppco_post_vars)) $post_vars["attr"]["captcha"] = "0"; //260816 PayPal returns reuse the form submission that already passed CAPTCHA validation.
								if(!empty($ppco_rest_invoice)) $post_vars["attr"]["invoice"] = $ppco_rest_invoice; //260816 Keep the server-side invoice that was placed in the REST order.

								$post_vars["name"] = trim($post_vars["first_name"]." ".$post_vars["last_name"]);
								$post_vars["email"] = apply_filters("user_registration_email", sanitize_email($post_vars["email"]), get_defined_vars());

								if(empty($post_vars["card_expiration"]) && isset($post_vars["card_expiration_month"], $post_vars["card_expiration_year"]))
									$post_vars["card_expiration"] = $post_vars["card_expiration_month"]."/".$post_vars["card_expiration_year"];

								$post_vars = c_ws_plugin__s2member_utils_captchas::recaptcha_post_vars($post_vars); // Collect reCAPTCHA™ post vars.

								(!empty($_GET["token"])) ? delete_transient("s2m_".md5("s2member_transient_express_checkout_".$_GET["token"])) : null;

								//260818.2056 Modern SP wallet/return/free paths use REST or no gateway; keep all form checks but skip unrelated NVP credentials.
								$skip_legacy_paypal_validation = (c_ws_plugin__s2member_paypal_utilities::paypal_checkout_is_enabled()
								&& ($ppco_prepare || $ppco_rest_return || $ppco_free_fallback || (!empty($post_vars["card_type"]) && $post_vars["card_type"] === "PayPal")));

								$attr_error = c_ws_plugin__s2member_pro_paypal_responses::paypal_form_attr_validation_errors($post_vars["attr"], $skip_legacy_paypal_validation);
								if($ppco_prepare && $attr_error)
									self::sp_checkout_json_exit(array('error' => 'pro_checkout_attr_invalid', 'message' => !empty($attr_error['response']) ? (string)$attr_error['response'] : _x('Invalid checkout form configuration.', 's2member-front', 's2member')));

								if(!$attr_error) // Attr errors?
									{
										$error = c_ws_plugin__s2member_pro_paypal_responses::paypal_form_submission_validation_errors("sp-checkout", $post_vars, $skip_legacy_paypal_validation);
										if($ppco_prepare && $error)
											self::sp_checkout_json_exit(array('error' => 'pro_checkout_validation_failed', 'message' => !empty($error['response']) ? (string)$error['response'] : _x('Unable to validate this checkout request.', 's2member-front', 's2member')));

										if(!$error)
											{
												//260818.2056 The preparation endpoint is wallet-only; never let a crafted prepare request enter a legacy card path.
												if($ppco_prepare && $post_vars["card_type"] !== "PayPal")
													self::sp_checkout_json_exit(array('error' => 'pro_checkout_not_paypal', 'message' => _x('PayPal was not selected as the billing method.', 's2member-front', 's2member')));

												//260817 REST returns reuse the server-side coupon/pricing snapshot created before PayPal fulfillment, so coupons are not applied a second time.
												if($ppco_rest_return && !empty($ppco_post_vars["cp_attr"]) && is_array($ppco_post_vars["cp_attr"])
												&& !empty($ppco_post_vars["cost_calculations"]) && is_array($ppco_post_vars["cost_calculations"]))
													{
														$cp_attr = $ppco_post_vars["cp_attr"];
														$cost_calculations = $ppco_post_vars["cost_calculations"];
													}
												else
													{
														$cp_attr = c_ws_plugin__s2member_pro_paypal_utilities::paypal_apply_coupon($post_vars["attr"], $post_vars["coupon"], "attr", array("affiliates-silent-post"));
														$cp_2gbp_attr = c_ws_plugin__s2member_pro_paypal_utilities::paypal_maestro_solo_2gbp( /* Now we use the new array of ``$cp_attr``. */$cp_attr, $post_vars["card_type"]);
														$cost_calculations = c_ws_plugin__s2member_pro_paypal_utilities::paypal_cost(null, $cp_2gbp_attr["ra"], $post_vars["state"], $post_vars["country"], $post_vars["zip"], $cp_2gbp_attr["cc"], $cp_2gbp_attr["desc"]);
													}

												//260818.2056 Tell shared JS to use the ordinary free path without submitting an already-validated CAPTCHA twice.
												if($ppco_prepare && $cost_calculations["total"] <= 0)
													{
														$free_handoff = c_ws_plugin__s2member_pro_paypal_utilities::paypal_checkout_free_handoff_create('sp-checkout', $_POST['s2member_pro_paypal_sp_checkout']);
														if(!$free_handoff)
															self::sp_checkout_json_exit(array('error' => 'pro_checkout_free_handoff_failed', 'message' => _x('Unable to prepare this checkout. Please try again.', 's2member-front', 's2member')));

														self::sp_checkout_json_exit(array('error' => 'pro_checkout_payment_not_required', 'message' => _x('Payment is no longer required for this checkout.', 's2member-front', 's2member'), 'free_handoff' => $free_handoff));
													}

												if(empty($_GET["s2member_paypal_xco"]) && $post_vars["card_type"] === "PayPal" && $cost_calculations["total"] > 0)
													{
														$return_url = $cancel_url = (is_ssl()) ? "https://" : "http://";
														$return_url = $cancel_url = ($return_url = $cancel_url).$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
														$return_url = $cancel_url = /* Ditch. */ remove_query_arg(array("token", "PayerID"), ($return_url = $cancel_url));
														$return_url = add_query_arg("s2member_paypal_xco", urlencode("s2member_pro_paypal_sp_checkout_return"), $return_url);
														$cancel_url = add_query_arg("s2member_paypal_xco", urlencode("s2member_pro_paypal_sp_checkout_cancel"), $cancel_url);

														$user = (is_user_logged_in() && is_object($user = wp_get_current_user()) && ($user_id = $user->ID)) ? $user : false;

														$post_vars["attr"]["invoice"] = uniqid()."~".c_ws_plugin__s2member_utils_ip::current(); // Unique invoice w/ IP address too.

														//260816 Use the shared Framework PayPal Checkout REST redirect/capture flow when configured; legacy Express Checkout remains the fallback.
														if(c_ws_plugin__s2member_paypal_utilities::paypal_checkout_is_enabled())
															{
																$ppco_rest_state = md5(uniqid("s2m_ppco_", true).wp_rand());

																$return_url = add_query_arg("s2member_paypal_xco", urlencode("s2member_pro_paypal_sp_checkout_rest_return"), $return_url);
																$return_url = add_query_arg("s2member_paypal_rest_state", urlencode($ppco_rest_state), $return_url);

																$paypal_on0_input_value = ($referencing = c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id()) ? "Referencing Customer ID" : "Originating Domain";
																$paypal_os0_input_value = ($referencing) ? $referencing : $_SERVER["HTTP_HOST"];

																//260817.2119 Package the server-validated SP purchase, pricing, contact, and fulfillment context into the encrypted Framework Checkout token.
																$ppco_token = array(
																	"exp"         => time() + 10800,
																	"invoice"     => $post_vars["attr"]["invoice"],
																	"ip"          => c_ws_plugin__s2member_utils_ip::current(),
																	"item_name"   => $cost_calculations["desc"],
																	"item_number" => $post_vars["attr"]["sp_ids_exp"],
																	"custom"      => $post_vars["attr"]["custom"],
																	"sub_total"   => $cost_calculations["sub_total"],
																	"amount"      => $cost_calculations["total"],
																	"cc"          => strtoupper($cost_calculations["cur"]),
																	"ns"          => $post_vars["attr"]["ns"],
																	"rr"          => "BN",
																	"tax"         => $cost_calculations["tax"],
																	"payer_email" => $post_vars["email"],
																	"first_name"  => $post_vars["first_name"],
																	"last_name"   => $post_vars["last_name"],
																	"on0"         => $paypal_on0_input_value,
																	"os0"         => $paypal_os0_input_value,
																	"on1"         => "Customer IP Address",
																	"os1"         => c_ws_plugin__s2member_utils_ip::current(),
																	"return"      => $return_url,
																	"cancel"      => $cancel_url,
																	"s2member_paypal_proxy_use" => "pro-emails",
																	"s2member_paypal_proxy_coupon" => array("coupon_code" => $cp_attr["_coupon_code"], "full_coupon_code" => $cp_attr["_full_coupon_code"], "affiliate_id" => $cp_attr["_coupon_affiliate_id"]),
																	"s2member_paypal_proxy_return_url" => $post_vars["attr"]["success"],
																	"checksum"    => md5($post_vars["attr"]["invoice"].c_ws_plugin__s2member_utils_ip::current().$post_vars["attr"]["sp_ids_exp"]),
																);

																//260817 Keep the validated form, invoice, coupon data, and calculated price server-side for the REST return.
																$ppco_post_vars = array(
																	"post"              => $_POST,
																	"invoice"           => $post_vars["attr"]["invoice"],
																	"cp_attr"           => $cp_attr,
																	"cost_calculations" => $cost_calculations,
																);
																set_transient("s2m_".md5("s2member_transient_paypal_checkout_".$ppco_rest_state), $ppco_post_vars, 10800);

																$ppco_token = urlencode(c_ws_plugin__s2member_utils_encryption::encrypt(serialize($ppco_token)));
																$ppco_endpoint = home_url("/?s2member_paypal_checkout=1");

																//260818.2056 Shared JS consumes the same validated SP token; no second SP-specific PayPal payment engine is needed.
																if($ppco_prepare)
																	self::sp_checkout_json_exit(array(
																		'ok'       => TRUE,
																		'flow'     => 'order',
																		'invoice'  => (string)$post_vars["attr"]["invoice"],
																		'token'    => $ppco_token,
																		'endpoint' => $ppco_endpoint,
																		'amount'   => (string)$cost_calculations["total"],
																		'cc'       => strtoupper((string)$cost_calculations["cur"]),
																	));

																$ppco_redirect_url = $ppco_endpoint."&s2member_paypal_checkout_op=redirect&s2member_paypal_checkout_t=".$ppco_token;

																wp_redirect($ppco_redirect_url);
																exit(); // Clean exit.
															}

														if(!($paypal_set_xco = array())) // PayPal Express Checkout.
															{
																$paypal_set_xco["METHOD"] = "SetExpressCheckout";

																$paypal_set_xco["RETURNURL"] = $return_url;
																$paypal_set_xco["CANCELURL"] = $cancel_url;

																$paypal_set_xco["PAGESTYLE"] = $post_vars["attr"]["ps"];
																$paypal_set_xco["LOCALECODE"] = $post_vars["attr"]["lc"];
																$paypal_set_xco["NOSHIPPING"] = $post_vars["attr"]["ns"];
																$paypal_set_xco["SOLUTIONTYPE"] = "Sole";
																$paypal_set_xco["LANDINGPAGE"] = "Billing";
																$paypal_set_xco["ALLOWNOTE"] = "0";

																$paypal_set_xco["PAYMENTREQUEST_0_PAYMENTACTION"] = "Sale";

																$paypal_set_xco["MAXAMT"] = $cost_calculations["total"];

																$paypal_set_xco["PAYMENTREQUEST_0_DESC"] = $cost_calculations["desc"];
																$paypal_set_xco["PAYMENTREQUEST_0_CUSTOM"] = $post_vars["attr"]["custom"];
																$paypal_set_xco["PAYMENTREQUEST_0_INVNUM"] = $post_vars["attr"]["invoice"];

																$paypal_set_xco["PAYMENTREQUEST_0_CURRENCYCODE"] = $cost_calculations["cur"];
																$paypal_set_xco["PAYMENTREQUEST_0_ITEMAMT"] = $cost_calculations["sub_total"];
																$paypal_set_xco["PAYMENTREQUEST_0_TAXAMT"] = $cost_calculations["tax"];
																$paypal_set_xco["PAYMENTREQUEST_0_AMT"] = $cost_calculations["total"];

																$paypal_set_xco["L_PAYMENTREQUEST_0_QTY0"] = "1"; // Always (1).
																$paypal_set_xco["L_PAYMENTREQUEST_0_NAME0"] = $cost_calculations["desc"];
																$paypal_set_xco["L_PAYMENTREQUEST_0_NUMBER0"] = $post_vars["attr"]["sp_ids_exp"];
																$paypal_set_xco["L_PAYMENTREQUEST_0_AMT0"] = $cost_calculations["sub_total"];

																$paypal_set_xco["PAYMENTREQUEST_0_SHIPTONAME"] = $post_vars["name"];
																$paypal_set_xco["PAYMENTREQUEST_0_SHIPTOSTREET"] = $post_vars["street"];
																$paypal_set_xco["PAYMENTREQUEST_0_SHIPTOCITY"] = $post_vars["city"];
																$paypal_set_xco["PAYMENTREQUEST_0_SHIPTOSTATE"] = $post_vars["state"];
																$paypal_set_xco["PAYMENTREQUEST_0_SHIPTOCOUNTRYCODE"] = $post_vars["country"];
																$paypal_set_xco["PAYMENTREQUEST_0_SHIPTOZIP"] = $post_vars["zip"];

																$paypal_set_xco["EMAIL"] = $post_vars["email"];
															}

														if(($paypal_set_xco = c_ws_plugin__s2member_paypal_utilities::paypal_api_response($paypal_set_xco)) && empty($paypal_set_xco["__error"]))
															{
																set_transient("s2m_".md5("s2member_transient_express_checkout_".$paypal_set_xco["TOKEN"]), $_POST, 10800);

																$endpoint = ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["paypal_sandbox"]) ? "www.sandbox.paypal.com" : "www.paypal.com";

																wp_redirect(add_query_arg("token", urlencode($paypal_set_xco["TOKEN"]), "https://".$endpoint."/cgi-bin/webscr?cmd=_express-checkout"));

																exit(); // Clean exit.
															}
														else // Else, an error.
															{
																$global_response = array("response" => $paypal_set_xco["__error"], "error" => true);
															}
													}
												else // Else we're good. Now ready to process this "Buy Now" transaction.
													{
														if(empty($post_vars["attr"]["invoice"])) // Only if it's empty.
															$post_vars["attr"]["invoice"] = uniqid()."~".c_ws_plugin__s2member_utils_ip::current();

														if(!($paypal = array())) // Build a simple "Buy Now" request.
															{
																//260817.2318 Accept REST only when the signed transaction matches the saved SP purchase.
																if(!empty($ppco_return_vars))
																	{
																		if(empty($ppco_return_vars["txn_id"])
																		|| empty($ppco_return_vars["invoice"]) || (string)$ppco_return_vars["invoice"] !== (string)$post_vars["attr"]["invoice"]
																		|| empty($ppco_return_vars["item_number"]) || (string)$ppco_return_vars["item_number"] !== (string)$post_vars["attr"]["sp_ids_exp"]
																		|| empty($ppco_return_vars["payment_status"]) || strcasecmp((string)$ppco_return_vars["payment_status"], "Completed") !== 0
																		|| empty($ppco_return_vars["mc_currency"]) || strcasecmp((string)$ppco_return_vars["mc_currency"], (string)$cost_calculations["cur"]) !== 0
																		|| !isset($ppco_return_vars["mc_gross"]) || number_format((float)$ppco_return_vars["mc_gross"], 2, ".", "") !== number_format((float)$cost_calculations["total"], 2, ".", "")
																		|| (isset($ppco_return_vars["tax"]) && number_format((float)$ppco_return_vars["tax"], 2, ".", "") !== number_format((float)$cost_calculations["tax"], 2, ".", "")))
																			{
																				c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
																					'ppco'             => 'pro-sp',
																					'event'            => 'sp_rest_transaction_mismatch',
																					'txn_id'           => !empty($ppco_return_vars["txn_id"]) ? (string)$ppco_return_vars["txn_id"] : '',
																					'invoice'          => !empty($ppco_return_vars["invoice"]) ? (string)$ppco_return_vars["invoice"] : '',
																					'invoice_expected' => (string)$post_vars["attr"]["invoice"],
																					'item_number'      => !empty($ppco_return_vars["item_number"]) ? (string)$ppco_return_vars["item_number"] : '',
																					'item_expected'    => (string)$post_vars["attr"]["sp_ids_exp"],
																					'amount'           => isset($ppco_return_vars["mc_gross"]) ? (string)$ppco_return_vars["mc_gross"] : '',
																					'amount_expected'  => (string)$cost_calculations["total"],
																					'cc'               => !empty($ppco_return_vars["mc_currency"]) ? (string)$ppco_return_vars["mc_currency"] : '',
																					'cc_expected'      => (string)$cost_calculations["cur"],
																					'tax'              => isset($ppco_return_vars["tax"]) ? (string)$ppco_return_vars["tax"] : '',
																					'tax_expected'     => (string)$cost_calculations["tax"],
																				));

																				$paypal["__error"] = _x("<strong>Oops.</strong> Unable to verify the completed PayPal Checkout transaction. Please contact Support for assistance.", "s2member-front", "s2member");
																			}
																		else
																			{
																				$paypal["TRANSACTIONID"] = (string)$ppco_return_vars["txn_id"];

																				c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
																					'ppco'    => 'pro-sp',
																					'event'   => 'sp_rest_return_verified',
																					'txn_id'  => (string)$ppco_return_vars["txn_id"],
																					'invoice' => (string)$post_vars["attr"]["invoice"],
																				));

																				//260817.2318 Consume REST state after the verified transaction matches the saved SP purchase.
																				delete_transient("s2m_".md5("s2member_transient_paypal_checkout_".$ppco_rest_state));
																			}
																	}
																else if(!empty($_GET["s2member_paypal_xco"]) && $_GET["s2member_paypal_xco"] === "s2member_pro_paypal_sp_checkout_return" && !empty($_GET["token"]) && ($paypal_xco_details = array("METHOD" => "GetExpressCheckoutDetails", "TOKEN" => $_GET["token"])) && ($paypal_xco_details = c_ws_plugin__s2member_paypal_utilities::paypal_api_response($paypal_xco_details)) && empty($paypal_xco_details["__error"]))
																	{
																		$paypal["METHOD"] = "DoExpressCheckoutPayment";

																		$paypal["TOKEN"] = $paypal_xco_details["TOKEN"];
																		$paypal["PAYERID"] = $paypal_xco_details["PAYERID"];

																		$paypal["PAYMENTREQUEST_0_PAYMENTACTION"] = "Sale";

																		$paypal["PAYMENTREQUEST_0_DESC"] = $cost_calculations["desc"];
																		$paypal["PAYMENTREQUEST_0_CUSTOM"] = $post_vars["attr"]["custom"];
																		$paypal["PAYMENTREQUEST_0_INVNUM"] = $post_vars["attr"]["invoice"];

																		$paypal["PAYMENTREQUEST_0_CURRENCYCODE"] = $cost_calculations["cur"];
																		$paypal["PAYMENTREQUEST_0_ITEMAMT"] = $cost_calculations["sub_total"];
																		$paypal["PAYMENTREQUEST_0_TAXAMT"] = $cost_calculations["tax"];
																		$paypal["PAYMENTREQUEST_0_AMT"] = $cost_calculations["total"];

																		$paypal["L_PAYMENTREQUEST_0_QTY0"] = "1"; // Always (1).
																		$paypal["L_PAYMENTREQUEST_0_NAME0"] = $cost_calculations["desc"];
																		$paypal["L_PAYMENTREQUEST_0_NUMBER0"] = $post_vars["attr"]["sp_ids_exp"];
																		$paypal["L_PAYMENTREQUEST_0_AMT0"] = $cost_calculations["sub_total"];
																	}
																else // NOT using PayPal Express Checkout.
																	{
																		$paypal["METHOD"] = "DoDirectPayment";
																		$paypal["PAYMENTACTION"] = "Sale";

																		$paypal["EMAIL"] = $post_vars["email"];
																		$paypal["FIRSTNAME"] = $post_vars["first_name"];
																		$paypal["LASTNAME"] = $post_vars["last_name"];
																		$paypal["IPADDRESS"] = c_ws_plugin__s2member_utils_ip::current();

																		$paypal["DESC"] = $cost_calculations["desc"];
																		$paypal["CUSTOM"] = $post_vars["attr"]["custom"];
																		$paypal["INVNUM"] = $post_vars["attr"]["invoice"];

																		$paypal["CURRENCYCODE"] = $cost_calculations["cur"];
																		$paypal["ITEMAMT"] = $cost_calculations["sub_total"];
																		$paypal["TAXAMT"] = $cost_calculations["tax"];
																		$paypal["AMT"] = $cost_calculations["total"];

																		$paypal["L_QTY0"] = "1"; // Always (1).
																		$paypal["L_NAME0"] = $cost_calculations["desc"];
																		$paypal["L_NUMBER0"] = $post_vars["attr"]["sp_ids_exp"];
																		$paypal["L_AMT0"] = $cost_calculations["sub_total"];

																		$paypal["CREDITCARDTYPE"] = $post_vars["card_type"];
																		$paypal["ACCT"] = preg_replace("/[^0-9]/", "", $post_vars["card_number"]);
																		$paypal["EXPDATE"] = preg_replace("/[^0-9]/", "", $post_vars["card_expiration"]);
																		$paypal["CVV2"] = $post_vars["card_verification"];

																		if(in_array($post_vars["card_type"], array("Maestro", "Solo")))
																			if(preg_match("/^[0-9]{2}\/[0-9]{4}$/", $post_vars["card_start_date_issue_number"]))
																				$paypal["STARTDATE"] = preg_replace("/[^0-9]/", "", $post_vars["card_start_date_issue_number"]);
																			else // Otherwise, we assume they provided an Issue Number instead.
																			$paypal["ISSUENUMBER"] = $post_vars["card_start_date_issue_number"];

																		$paypal["STREET"] = $post_vars["street"];
																		$paypal["CITY"] = $post_vars["city"];
																		$paypal["STATE"] = $post_vars["state"];
																		$paypal["COUNTRYCODE"] = $post_vars["country"];
																		$paypal["ZIP"] = $post_vars["zip"];
																	}
															}
														//260817.2119 A REST return already represents the Framework-captured payment, so only free purchases and legacy payment paths should call the old PayPal API here.
														if($cost_calculations["total"] <= 0 || (!empty($ppco_return_vars) && empty($paypal["__error"])) || (($paypal = c_ws_plugin__s2member_paypal_utilities::paypal_api_response($paypal)) && empty($paypal["__error"])))
															{
																if($cost_calculations["total"] <= 0) $new__txn_id = strtoupper('free-'.uniqid()); // Auto-generated value in this case.

																else // We handle this normally. The transaction ID comes from PayPal as it always does.
																	{
																		$new__txn_id = (!empty($paypal["PAYMENTINFO_0_TRANSACTIONID"])) ? $paypal["PAYMENTINFO_0_TRANSACTIONID"] : false;
																		$new__txn_id = (!$new__txn_id && !empty($paypal["TRANSACTIONID"])) ? $paypal["TRANSACTIONID"] : $new__txn_id;
																	}
																if(!($ipn = array())) // Simulated PayPal IPN.
																	{
																		$ipn["txn_type"] = "web_accept";
																		$ipn["txn_id"] = $new__txn_id;
																		$ipn["custom"] = $post_vars["attr"]["custom"];
																		$ipn["invoice"] = $post_vars["attr"]["invoice"];

																		$ipn["mc_gross"] = $cost_calculations["total"];
																		$ipn["mc_currency"] = $cost_calculations["cur"];
																		$ipn["tax"] = $cost_calculations["tax"];

																		$ipn["payer_email"] = $post_vars["email"];
																		$ipn["first_name"] = $post_vars["first_name"];
																		$ipn["last_name"] = $post_vars["last_name"];

																		if(is_user_logged_in() && // Reference a User/Member?
																		($referencing = c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id()))
																			{
																				$ipn["option_name1"] = "Referencing Customer ID";
																				$ipn["option_selection1"] = $referencing;
																			}
																		else // Otherwise, default to the originating domain.
																			{
																				$ipn["option_name1"] = "Originating Domain";
																				$ipn["option_selection1"] = $_SERVER["HTTP_HOST"];
																			}

																		$ipn["option_name2"] = "Customer IP Address";
																		$ipn["option_selection2"] = c_ws_plugin__s2member_utils_ip::current();

																		$ipn["item_name"] = $cost_calculations["desc"];
																		$ipn["item_number"] = $post_vars["attr"]["sp_ids_exp"];

																		$ipn["s2member_paypal_proxy"] = "paypal";
																		$ipn["s2member_paypal_proxy_use"] = "pro-emails";
																		$ipn["s2member_paypal_proxy_coupon"] = array("coupon_code" => $cp_attr["_coupon_code"], "full_coupon_code" => $cp_attr["_full_coupon_code"], "affiliate_id" => $cp_attr["_coupon_affiliate_id"]);
																		$ipn["s2member_paypal_proxy_verification"] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();
																		$ipn["s2member_paypal_proxy_return_url"] = $post_vars["attr"]["success"];

																		//260817.2119 Framework already sent the REST simulated IPN; reuse the success URL from its verified signed browser package instead of fulfilling the sale a second time.
																		if(!empty($ppco_return_vars))
																			$ipn["s2member_paypal_proxy_return_url"] = isset($ppco_raw_return_vars["s2member_paypal_proxy_return_url"]) ? trim((string)$ppco_raw_return_vars["s2member_paypal_proxy_return_url"]) : "";
																		else
																			$ipn["s2member_paypal_proxy_return_url"] = trim(c_ws_plugin__s2member_utils_urls::remote(home_url("/?s2member_paypal_notify=1"), $ipn, array("timeout" => 20)));
																	}
																if(($sp_access_url = c_ws_plugin__s2member_sp_access::sp_access_link_gen($post_vars["attr"]["ids"], $post_vars["attr"]["exp"])))
																	{
																		setcookie("s2member_sp_tracking", ($s2member_sp_tracking = c_ws_plugin__s2member_utils_encryption::encrypt($new__txn_id)), time() + 31556926, COOKIEPATH, COOKIE_DOMAIN).setcookie("s2member_sp_tracking", $s2member_sp_tracking, time() + 31556926, SITECOOKIEPATH, COOKIE_DOMAIN).($_COOKIE["s2member_sp_tracking"] = $s2member_sp_tracking);

																		$global_response = array("response" => sprintf(_x('<strong>Thank you.</strong> Your purchase has been approved.<br />&mdash; Please <a href="%s" rel="nofollow">click here</a> to proceed.', "s2member-front", "s2member"), esc_attr($sp_access_url)));

																		if($post_vars["attr"]["success"]
																				&& (substr($ipn['s2member_paypal_proxy_return_url'], 0, 2) === substr($post_vars['attr']['success'], 0, 2) || stripos($ipn['s2member_paypal_proxy_return_url'], 'http') === 0)
																				&& ($custom_success_url = str_ireplace(array("%%s_response%%", /* Deprecated in v111106 ». */ "%%response%%"), array(urlencode(c_ws_plugin__s2member_utils_encryption::encrypt($global_response["response"])), urlencode($global_response["response"])), $ipn["s2member_paypal_proxy_return_url"]))
																				&& ($custom_success_url = trim(preg_replace("/%%(.+?)%%/i", "", $custom_success_url)))) {
																			wp_redirect($post_vars['attr']['success'] === '%%sp_access_url%%' ? $custom_success_url : c_ws_plugin__s2member_utils_urls::add_s2member_sig($custom_success_url, "s2p-v")).exit();
																		}
																	}
																else // Else, unable to generate Access Link.
																	{
																		$global_response = array("response" => _x('<strong>Oops.</strong> Unable to generate Access Link. Please contact Support for assistance.', "s2member-front", "s2member"), "error" => true);
																	}
															}
														else // Else, an error.
															{
																$global_response = array("response" => $paypal["__error"], "error" => true);
															}
													}
											}
										else // Else, an error.
											{
												$global_response = $error;
											}
									}
							}
					}
			}
	}
