<?php
/**
* Google® IPN Handler (inner processing routines).
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

if (!class_exists ("c_ws_plugin__s2member_pro_google_notify_in"))
	{
		/**
		* Google® IPN Handler (inner processing routines).
		*
		* @package s2Member\Google
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_google_notify_in
			{
				/**
				* Handles Google® IPN URL processing.
				*
				* @package s2Member\Google
				* @since 1.5
				*
				* @attaches-to ``add_action("init");``
				*
				* @return null Or exits script execution after handling the Notification.
				*/
				public static function google_notify ()
					{
						global /* For Multisite support. */ $current_site, $current_blog;

						if (!empty ($_GET["s2member_pro_google_notify"]) && $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"])
							{
								@ignore_user_abort (true); // Continue processing even if/when connection is broken by the sender.

								if (is_array ($google = c_ws_plugin__s2member_pro_google_utilities::google_postvars ()) && ($_google = $google))
									{
										$google["s2member_log"][] = "IPN received on: " . date ("D M j, Y g:i:s a T");
										$google["s2member_log"][] = "s2Member POST vars verified with Google®.";

										if (preg_match ("/^new-order-notification$/i", $google["_type"])
										&& is_array ($s2vars_item1 = c_ws_plugin__s2member_pro_google_utilities::google_parse_s2vars ($google["order-summary_shopping-cart_items_item-1_merchant-private-item-data"]))
										 && !$s2vars_item1["s2_subscr_id"])
											{
												$google["s2member_log"][] = "Google® transaction identified as ( `SALE/BUY-NOW` ).";
												$google["s2member_log"][] = "IPN reformulated. Piping through s2Member's core/standard PayPal® processor as `txn_type` ( `web_accept` ).";
												$google["s2member_log"][] = "Please check PayPal® IPN logs for further processing details.";

												$processing = $processed = true;
												$ipn = array (); // Reset.

												$ipn["txn_type"] = "web_accept";

												$ipn["txn_id"] = ($s2vars_item1["s2_txn_id"]) ? $s2vars_item1["s2_txn_id"] : $google["order-summary_google-order-number"];

												$ipn["custom"] = $s2vars_item1["s2_custom"];

												$ipn["mc_gross"] = number_format ($google["order-summary_order-total"], 2, ".", "");
												$ipn["mc_currency"] = strtoupper ($google["order-summary_order-total_currency"]);
												$ipn["tax"] = number_format ($google["order-summary_order-adjustment_total-tax"], 2, ".", "");

												$ipn["payer_email"] = $google["buyer-billing-address_email"];
												$ipn["first_name"] = $google["buyer-billing-address_structured-name_first-name"];
												$ipn["last_name"] = $google["buyer-billing-address_structured-name_last-name"];

												$ipn["option_name1"] = ($s2vars_item1["s2_referencing"]) ? "Referencing Customer ID" : "Originating Domain";
												$ipn["option_selection1"] = ($s2vars_item1["s2_referencing"]) ? $s2vars_item1["s2_referencing"] : $_SERVER["HTTP_HOST"];

												$ipn["option_name2"] = "Customer IP Address"; // IP Address.
												$ipn["option_selection2"] = $s2vars_item1["s2_customer_ip"];

												$ipn["item_number"] = $s2vars_item1["s2_item_number"];
												$ipn["item_name"] = $google["order-summary_shopping-cart_items_item-1_item-name"];

												$ipn["s2member_paypal_proxy"] = "google";
												$ipn["s2member_paypal_proxy_use"] = "standard-emails";
												$ipn["s2member_paypal_proxy_verification"] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

												c_ws_plugin__s2member_utils_urls::remote (site_url ("/?s2member_paypal_notify=1"), $ipn, array ("timeout" => 20));
											}

										else if (preg_match ("/^new-order-notification$/i", $google["_type"])
										&& is_array ($s2vars_item1 = c_ws_plugin__s2member_pro_google_utilities::google_parse_s2vars ($google["order-summary_shopping-cart_items_item-1_merchant-private-item-data"]))
										 && $s2vars_item1["s2_subscr_id"] && !$s2vars_item1["s2_subscr_payment"])
											{
												$google["s2member_log"][] = "Google® transaction identified as ( `SALE/SUBSCRIPTION` ).";
												$google["s2member_log"][] = "IPN reformulated. Piping through s2Member's core/standard PayPal® processor as `txn_type` ( `subscr_signup` ).";
												$google["s2member_log"][] = "Please check PayPal® IPN logs for further processing details.";

												$processing = $processed = true;
												$ipn = array (); // Reset.

												$ipn["txn_type"] = "subscr_signup";
												$ipn["subscr_id"] = $s2vars_item1["s2_subscr_id"];

												$ipn["recurring"] = (!($times = $google["order-summary_shopping-cart_items_item-2_subscription_payments_subscription-payment-1_times"]) || $times > 1) ? "1" : "0";

												$ipn["txn_id"] = $google["order-summary_google-order-number"];

												$ipn["custom"] = $s2vars_item1["s2_custom"];

												$ipn["period1"] = $s2vars_item1["s2_period1"]; // Just use s2Member's period calculations to make this easier.
												$ipn["period3"] = $s2vars_item1["s2_period3"]; // Just use s2Member's period calculations to make this easier.

												$ipn["mc_amount1"] = number_format ($google["order-summary_shopping-cart_items_item-1_unit-price"], 2, ".", "");
												$ipn["mc_amount3"] = number_format ($google["order-summary_shopping-cart_items_item-2_subscription_recurrent-item_unit-price"], 2, ".", "");

												$ipn["mc_gross"] = (preg_match ("/^[1-9]/", $ipn["period1"])) ? $ipn["mc_amount1"] : $ipn["mc_amount3"];

												$ipn["mc_currency"] = strtoupper ($google["order-summary_order-total_currency"]);
												$ipn["tax"] = number_format ($google["order-summary_order-adjustment_total-tax"], 2, ".", "");

												$ipn["payer_email"] = $google["buyer-billing-address_email"];
												$ipn["first_name"] = $google["buyer-billing-address_structured-name_first-name"];
												$ipn["last_name"] = $google["buyer-billing-address_structured-name_last-name"];

												$ipn["option_name1"] = ($s2vars_item1["s2_referencing"]) ? "Referencing Customer ID" : "Originating Domain";
												$ipn["option_selection1"] = ($s2vars_item1["s2_referencing"]) ? $s2vars_item1["s2_referencing"] : $_SERVER["HTTP_HOST"];

												$ipn["option_name2"] = "Customer IP Address"; // IP Address.
												$ipn["option_selection2"] = $s2vars_item1["s2_customer_ip"];

												$ipn["item_number"] = $s2vars_item1["s2_item_number"];
												$ipn["item_name"] = $google["order-summary_shopping-cart_items_item-1_item-name"];

												$ipn["s2member_paypal_proxy"] = "google";
												$ipn["s2member_paypal_proxy_use"] = "standard-emails";
												$ipn["s2member_paypal_proxy_use"] .= ($ipn["mc_gross"] > 0) ? ",subscr-signup-as-subscr-payment" : "";
												$ipn["s2member_paypal_proxy_verification"] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

												c_ws_plugin__s2member_utils_urls::remote (site_url ("/?s2member_paypal_notify=1"), $ipn, array ("timeout" => 20));
											}

										else if (preg_match ("/^new-order-notification$/i", $google["_type"])
										&& is_array ($s2vars_item1 = c_ws_plugin__s2member_pro_google_utilities::google_parse_s2vars ($google["order-summary_shopping-cart_items_item-1_merchant-private-item-data"]))
										 && $s2vars_item1["s2_subscr_id"] && $s2vars_item1["s2_subscr_payment"])
											{
												$google["s2member_log"][] = "Google® transaction identified as ( `SUBSCRIPTION PAYMENT` ).";
												$google["s2member_log"][] = "IPN reformulated. Piping through s2Member's core/standard PayPal® processor as `txn_type` ( `subscr_payment` ).";
												$google["s2member_log"][] = "Please check PayPal® IPN logs for further processing details.";

												$processing = $processed = true;
												$ipn = array (); // Reset.

												$ipn["txn_type"] = "subscr_payment";
												$ipn["subscr_id"] = $s2vars_item1["s2_subscr_id"];

												$ipn["txn_id"] = $google["order-summary_google-order-number"];

												$ipn["custom"] = $s2vars_item1["s2_custom"];

												$ipn["mc_gross"] = number_format ($google["order-summary_order-total"], 2, ".", "");
												$ipn["mc_currency"] = strtoupper ($google["order-summary_order-total_currency"]);
												$ipn["tax"] = number_format ($google["order-summary_order-adjustment_total-tax"], 2, ".", "");

												$ipn["payer_email"] = $google["buyer-billing-address_email"];
												$ipn["first_name"] = $google["buyer-billing-address_structured-name_first-name"];
												$ipn["last_name"] = $google["buyer-billing-address_structured-name_last-name"];

												$ipn["option_name1"] = ($s2vars_item1["s2_referencing"]) ? "Referencing Customer ID" : "Originating Domain";
												$ipn["option_selection1"] = ($s2vars_item1["s2_referencing"]) ? $s2vars_item1["s2_referencing"] : $_SERVER["HTTP_HOST"];

												$ipn["option_name2"] = "Customer IP Address"; // IP Address.
												$ipn["option_selection2"] = $s2vars_item1["s2_customer_ip"];

												$ipn["item_number"] = $s2vars_item1["s2_item_number"];
												$ipn["item_name"] = $google["order-summary_shopping-cart_items_item-1_item-name"];

												$ipn["s2member_paypal_proxy"] = "google";
												$ipn["s2member_paypal_proxy_use"] = "standard-emails";
												$ipn["s2member_paypal_proxy_verification"] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

												c_ws_plugin__s2member_utils_urls::remote (site_url ("/?s2member_paypal_notify=1"), $ipn, array ("timeout" => 20));
											}

										else if (preg_match ("/^cancelled-subscription-notification$/i", $google["_type"])
										&& is_array ($s2vars_item1 = c_ws_plugin__s2member_pro_google_utilities::google_parse_s2vars ($google["order-summary_shopping-cart_items_item-1_merchant-private-item-data"]))
										 && $s2vars_item1["s2_subscr_id"])
											{
												$google["s2member_log"][] = "Google® transaction identified as ( `SUBSCRIPTION CANCELLATION` ).";
												$google["s2member_log"][] = "IPN reformulated. Piping through s2Member's core/standard PayPal® processor as `txn_type` ( `subscr_cancel` ).";
												$google["s2member_log"][] = "Please check PayPal® IPN logs for further processing details.";

												$processing = $processed = true;
												$ipn = array (); // Reset.

												$ipn["txn_type"] = "subscr_cancel";
												$ipn["subscr_id"] = $s2vars_item1["s2_subscr_id"];

												$ipn["custom"] = $s2vars_item1["s2_custom"];

												$ipn["period1"] = $s2vars_item1["s2_period1"];
												$ipn["period3"] = $s2vars_item1["s2_period3"];

												$ipn["payer_email"] = $google["order-summary_risk-information_billing-address_email"];
												$ipn["first_name"] = preg_replace ("/( )(.+)/", "", $google["order-summary_risk-information_billing-address_contact-name"]);
												$ipn["last_name"] = preg_replace ("/(.+?)( )/", "", $google["order-summary_risk-information_billing-address_contact-name"]);

												$ipn["option_name1"] = ($s2vars_item1["s2_referencing"]) ? "Referencing Customer ID" : "Originating Domain";
												$ipn["option_selection1"] = ($s2vars_item1["s2_referencing"]) ? $s2vars_item1["s2_referencing"] : $_SERVER["HTTP_HOST"];

												$ipn["option_name2"] = "Customer IP Address"; // IP Address.
												$ipn["option_selection2"] = $s2vars_item1["s2_customer_ip"];

												$ipn["item_number"] = $s2vars_item1["s2_item_number"];
												$ipn["item_name"] = $google["order-summary_shopping-cart_items_item-1_item-name"];

												$ipn["s2member_paypal_proxy"] = "google";
												$ipn["s2member_paypal_proxy_use"] = "standard-emails";
												$ipn["s2member_paypal_proxy_verification"] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

												c_ws_plugin__s2member_utils_urls::remote (site_url ("/?s2member_paypal_notify=1"), $ipn, array ("timeout" => 20));
											}

										else if (preg_match ("/^(refund|chargeback)-amount-notification$/i", $google["_type"]) // Do NOT process partial refunds/chargebacks.
										&& is_array ($s2vars_item1 = c_ws_plugin__s2member_pro_google_utilities::google_parse_s2vars ($google["order-summary_shopping-cart_items_item-1_merchant-private-item-data"]))
										 && ((preg_match ("/^refund/", $google["_type"]) && $google["latest-fee-refund-amount"] >= $google["order-summary_total-charge-amount"])
										 || (preg_match ("/^chargeback/", $google["_type"]) && $google["latest-chargeback-amount"] >= $google["order-summary_total-charge-amount"])))
											{
												$google["s2member_log"][] = "Google® transaction identified as ( `REFUND|CHARGEBACK` ).";
												$google["s2member_log"][] = "IPN reformulated. Piping through s2Member's core/standard PayPal® processor as `payment_status` ( `refunded|reversed` ).";
												$google["s2member_log"][] = "Please check PayPal® IPN logs for further processing details.";

												$processing = $processed = true;
												$ipn = array (); // Reset.

												$ipn["custom"] = $s2vars_item1["s2_custom"];

												if ($s2vars_item1["s2_subscr_id"] && !$s2vars_item1["s2_txn_id"])
													$ipn["parent_txn_id"] = $s2vars_item1["s2_subscr_id"];

												else if ($s2vars_item1["s2_txn_id"] && !$s2vars_item1["s2_subscr_id"])
													$ipn["parent_txn_id"] = $s2vars_item1["s2_txn_id"];

												else // Default to Google's Order #.
													$ipn["parent_txn_id"] = $google["order-summary_google-order-number"];

												if (preg_match ("/^refund/", $google["_type"])) // Use refunded amounts.
													{
														$ipn["payment_status"] = "refunded"; // Refunding.
														$ipn["mc_fee"] = "-" . number_format ($google["latest-fee-refund-amount"], 2, ".", "");
														$ipn["mc_gross"] = "-" . number_format ($google["latest-refund-amount"], 2, ".", "");
														$ipn["mc_currency"] = strtoupper ($google["latest-refund-amount_currency"]);
														$ipn["tax"] = "-" . number_format ("0.00", 2, ".", "");
													}
												else if (preg_match ("/^chargeback/", $google["_type"])) // Chargeback.
													{
														$ipn["payment_status"] = "reversed"; // Reversed/chargeback.
														$ipn["mc_fee"] = "-" . number_format ($google["latest-chargeback-fee-amount"], 2, ".", "");
														$ipn["mc_gross"] = "-" . number_format ($google["latest-chargeback-amount"], 2, ".", "");
														$ipn["mc_currency"] = strtoupper ($google["latest-chargeback-amount_currency"]);
														$ipn["tax"] = "-" . number_format ("0.00", 2, ".", "");
													}

												$ipn["payer_email"] = $google["order-summary_risk-information_billing-address_email"];
												$ipn["first_name"] = preg_replace ("/( )(.+)/", "", $google["order-summary_risk-information_billing-address_contact-name"]);
												$ipn["last_name"] = preg_replace ("/(.+?)( )/", "", $google["order-summary_risk-information_billing-address_contact-name"]);

												$ipn["option_name1"] = ($s2vars_item1["s2_referencing"]) ? "Referencing Customer ID" : "Originating Domain";
												$ipn["option_selection1"] = ($s2vars_item1["s2_referencing"]) ? $s2vars_item1["s2_referencing"] : $_SERVER["HTTP_HOST"];

												$ipn["option_name2"] = "Customer IP Address"; // IP Address.
												$ipn["option_selection2"] = $s2vars_item1["s2_customer_ip"];

												$ipn["item_number"] = $s2vars_item1["s2_item_number"];
												$ipn["item_name"] = $google["order-summary_shopping-cart_items_item-1_item-name"];

												$ipn["s2member_paypal_proxy"] = "google";
												$ipn["s2member_paypal_proxy_use"] = "standard-emails";
												$ipn["s2member_paypal_proxy_verification"] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

												c_ws_plugin__s2member_utils_urls::remote (site_url ("/?s2member_paypal_notify=1"), $ipn, array ("timeout" => 20));
											}

										else if (!$processed) // If nothing was processed, here we add a message to the logs indicating the IPN was ignored.
											$google["s2member_log"][] = "Ignoring this IPN request. The transaction does NOT require any action on the part of s2Member.";
									}
								else // Extensive log reporting here. This is an area where many site owners find trouble. Depending on server configuration; remote HTTPS connections may fail.
									{
										$google["s2member_log"][] = "Unable to verify POST vars. This is most likely related to an invalid Google® configuration. Please check: s2Member -› Google® Options.";
										$google["s2member_log"][] = "If you're absolutely SURE that your Google® configuration is valid, you may want to run some tests on your server, just to be sure \$_POST variables are populated, and that your server is able to connect to Google® over an HTTPS connection.";
										$google["s2member_log"][] = "s2Member uses the WP_Http class for remote connections; which will try to use cURL first, and then fall back on the FOPEN method when cURL is not available. On a Windows® server, you may have to disable your cURL extension. Instead, set allow_url_fopen = yes in your php.ini file. The cURL extension (usually) does NOT support SSL connections on a Windows® server.";
										$google["s2member_log"][] = var_export ($_REQUEST, true); // Recording _POST + _GET vars for analysis and debugging.
									}
								/*
								We need to log this final event before it occurs, so that is makes it into the log entry.
								*/
								$google["s2member_log"][] = "Sending Google® an XML Notification Acknowlegment w/ original serial number.";
								/*
								If debugging/logging is enabled; we need to append $google to the log file.
									Logging now supports Multisite Networking as well.
								*/
								$logt = c_ws_plugin__s2member_utilities::time_details ();
								$logv = c_ws_plugin__s2member_utilities::ver_details ();
								$logm = c_ws_plugin__s2member_utilities::mem_details ();
								$log4 = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "\nUser-Agent: " . $_SERVER["HTTP_USER_AGENT"];
								$log4 = (is_multisite () && !is_main_site ()) ? ($_log4 = $current_blog->domain . $current_blog->path) . "\n" . $log4 : $log4;
								$log2 = (is_multisite () && !is_main_site ()) ? "google-ipn-4-" . trim (preg_replace ("/[^a-z0-9]/i", "-", $_log4), "-") . ".log" : "google-ipn.log";

								if ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["gateway_debug_logs"])
									if (is_dir ($logs_dir = $GLOBALS["WS_PLUGIN__"]["s2member"]["c"]["logs_dir"]))
										if (is_writable ($logs_dir) && c_ws_plugin__s2member_utils_logs::archive_oversize_log_files ())
											file_put_contents ($logs_dir . "/" . $log2,
											                   "LOG ENTRY: ".$logt . "\n" . $logv . "\n" . $logm . "\n" . $log4 . "\n" .
											                                            c_ws_plugin__s2member_utils_logs::conceal_private_info(var_export ($google, true)) . "\n\n",
											                   FILE_APPEND);

								$confirmation = '<?xml version="1.0" encoding="UTF-8"?>';
								$confirmation .= '<notification-acknowledgment xmlns="http://checkout.google.com/schema/2"';
								$confirmation .= ' serial-number="' . esc_attr (trim (stripslashes ($_REQUEST["serial-number"]))) . '" />';

								status_header (200); // Send a 200 OK status header.
								header ("Content-Type: application/xml"); // Google® expects application/xml here.
								while (@ob_end_clean ()); // Clean any existing output buffers.

								exit ($confirmation); // Exit w/ serial number confirmation.
							}
					}
			}
	}
?>