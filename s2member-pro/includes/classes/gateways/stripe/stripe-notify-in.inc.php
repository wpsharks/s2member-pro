<?php
/**
* Stripe Silent Post *(aka: IPN)* (inner processing routines).
*
* Copyright: © 2009-2011
* {@link http://www.websharks-inc.com/ WebSharks, Inc.}
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
* @package s2Member\Stripe
* @since 140617
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_stripe_notify_in"))
	{
		/**
		* Stripe Silent Post *(aka: IPN)* (inner processing routines).
		*
		* @package s2Member\Stripe
		* @since 140617
		*/
		class c_ws_plugin__s2member_pro_stripe_notify_in
			{
				/**
				* Handles Stripe IPN URL processing.
				*
				* @package s2Member\Stripe
				* @since 140617
				*
				* @attaches-to ``add_action("init");``
				*
				* @return null Or exits script execution after handling IPN processing.
				*/
				public static function stripe_notify ()
					{
						global /* For Multisite support. */ $current_site, $current_blog;

						if (!empty($_GET["s2member_pro_stripe_notify"]) && $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_stripe_api_login_id"])
							{
								@ignore_user_abort (true); // Continue processing even if/when connection is broken by the sender.

								if (is_array($stripe = c_ws_plugin__s2member_pro_stripe_utilities::stripe_postvars ()) && ($_stripe = $stripe))
									{
										$stripe["s2member_log"][] = "IPN received on: " . date ("D M j, Y g:i:s a T");
										$stripe["s2member_log"][] = "s2Member POST vars verified with Stripe.";

										if ($stripe["x_subscription_id"] && $stripe["x_subscription_paynum"] && $stripe["x_response_code"] === "1")
											{
												if (($_stripe = c_ws_plugin__s2member_pro_stripe_utilities::stripe_parse_arb_desc ($stripe)) && ($stripe = $_stripe))
													{
														$stripe["s2member_log"][] = "Stripe transaction identified as ( `ARB / PAYMENT #" . $stripe["x_subscription_paynum"] . "` ).";
														$stripe["s2member_log"][] = "IPN reformulated. Piping through s2Member's core/standard PayPal processor as `txn_type` ( `subscr_payment` ).";
														$stripe["s2member_log"][] = "Please check PayPal IPN logs for further processing details.";

														$processing = $processed = true;
														$ipn = array(); // Reset.

														$ipn["txn_type"] = "subscr_payment";
														$ipn["subscr_id"] = $stripe["x_subscription_id"];
														$ipn["txn_id"] = $stripe["x_trans_id"];

														$ipn["custom"] = $stripe["s2_custom"];

														$ipn["mc_gross"] = number_format ($stripe["x_amount"], 2, ".", "");
														$ipn["mc_currency"] = strtoupper ((!empty($stripe["s2_currency"]) ? $stripe["s2_currency"] : "USD"));
														$ipn["tax"] = number_format ($stripe["x_tax"], 2, ".", "");

														$ipn["payer_email"] = $stripe["x_email"];
														$ipn["first_name"] = $stripe["x_first_name"];
														$ipn["last_name"] = $stripe["x_last_name"];

														$ipn["option_name1"] = "Referencing Customer ID";
														$ipn["option_selection1"] = $stripe["x_subscription_id"];

														$ipn["option_name2"] = "Customer IP Address";
														$ipn["option_selection2"] = null;

														$ipn["item_number"] = $stripe["s2_invoice"];
														$ipn["item_name"] = $stripe["x_description"];

														$ipn["s2member_paypal_proxy"] = "stripe";
														$ipn["s2member_paypal_proxy_use"] = "pro-emails";
														$ipn["s2member_paypal_proxy_verification"] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

														c_ws_plugin__s2member_utils_urls::remote (site_url ("/?s2member_paypal_notify=1"), $ipn, array("timeout" => 20));
													}
												else // Otherwise, we don't have enough information to reforumalte this IPN response. An error must be generated.
													{
														$stripe["s2member_log"][] = "Stripe transaction identified as ( `ARB / PAYMENT #" . $stripe["x_subscription_paynum"] . "` ).";
														$stripe["s2member_log"][] = "Ignoring this IPN. The transaction does NOT contain a valid reference value/desc.";
													}
											}

										else if ($stripe["x_subscription_id"] && $stripe["x_subscription_paynum"] && preg_match ("/^(2|3)$/", $stripe["x_response_code"]))
											{
												if (($_stripe = c_ws_plugin__s2member_pro_stripe_utilities::stripe_parse_arb_desc ($stripe)) && ($stripe = $_stripe))
													{
														$stripe["s2member_log"][] = "Stripe transaction identified as ( `ARB / FAILED PAYMENT` ).";
														$stripe["s2member_log"][] = "s2Member does NOT respond to individual failed payment notifications.";
														$stripe["s2member_log"][] = "When multiple consecutive payments fail, s2Member is notified via ARB services.";
														$stripe["s2member_log"][] = "This does not require any action (at the moment) on the part of s2Member.";
													}
												else // Otherwise, we don't have enough information to reforumalte this IPN response. An error must be generated.
													{
														$stripe["s2member_log"][] = "Stripe transaction identified as ( `ARB / FAILED PAYMENT` ).";
														$stripe["s2member_log"][] = "Ignoring this IPN. The transaction does NOT contain a valid reference value/desc.";
													}
											}

										else if (!$processed) // If nothing was processed, here we add a message to the logs indicating the IPN was ignored.
											$stripe["s2member_log"][] = "Ignoring this IPN. The transaction does NOT require any action on the part of s2Member.";
									}
								else // Extensive log reporting here. This is an area where many site owners find trouble. Depending on server configuration; remote HTTPS connections may fail.
									{
										$stripe["s2member_log"][] = "Unable to verify POST vars. This is most likely related to an invalid Stripe configuration. Please check: s2Member -› Stripe Options.";
										$stripe["s2member_log"][] = "If you're absolutely SURE that your Stripe configuration is valid, you may want to run some tests on your server, just to be sure \$_POST variables are populated, and that your server is able to connect to Stripe over an HTTPS connection.";
										$stripe["s2member_log"][] = "s2Member uses the WP_Http class for remote connections; which will try to use cURL first, and then fall back on the FOPEN method when cURL is not available. On a Windows server, you may have to disable your cURL extension. Instead, set allow_url_fopen = yes in your php.ini file. The cURL extension (usually) does NOT support SSL connections on a Windows server.";
										$stripe["s2member_log"][] = var_export ($_REQUEST, true); // Recording _POST + _GET vars for analysis and debugging.
									}
								/*
								If debugging/logging is enabled; we need to append $stripe to the log file.
									Logging now supports Multisite Networking as well.
								*/
								$logt = c_ws_plugin__s2member_utilities::time_details ();
								$logv = c_ws_plugin__s2member_utilities::ver_details ();
								$logm = c_ws_plugin__s2member_utilities::mem_details ();
								$log4 = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "\nUser-Agent: " . $_SERVER["HTTP_USER_AGENT"];
								$log4 = (is_multisite () && !is_main_site ()) ? ($_log4 = $current_blog->domain . $current_blog->path) . "\n" . $log4 : $log4;
								$log2 = (is_multisite () && !is_main_site ()) ? "stripe-ipn-4-" . trim (preg_replace ("/[^a-z0-9]/i", "-", $_log4), "-") . ".log" : "stripe-ipn.log";

								if ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["gateway_debug_logs"])
									if (is_dir ($logs_dir = $GLOBALS["WS_PLUGIN__"]["s2member"]["c"]["logs_dir"]))
										if (is_writable ($logs_dir) && c_ws_plugin__s2member_utils_logs::archive_oversize_log_files ())
											file_put_contents ($logs_dir . "/" . $log2,
											                   "LOG ENTRY: ".$logt . "\n" . $logv . "\n" . $logm . "\n" . $log4 . "\n" .
											                                            c_ws_plugin__s2member_utils_logs::conceal_private_info(var_export ($stripe, true)) . "\n\n",
											                   FILE_APPEND);

								status_header (200); // Send a 200 OK status header.
								header ("Content-Type: text/plain; charset=UTF-8"); // Content-Type text/plain with UTF-8.
								while (@ob_end_clean ()); // Clean any existing output buffers.

								exit (); // Exit now.
							}
					}
			}
	}
?>