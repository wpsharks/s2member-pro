<?php
/**
* Google® Checkout (inner processing routines).
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
	exit("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_google_co_in"))
	{
		/**
		* Google® Checkout (inner processing routines).
		*
		* @package s2Member\Google
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_google_co_in
			{
				/**
				* Handles Google® XML Checkout redirections.
				*
				* @package s2Member\Google
				* @since 1.5
				*
				* @attaches-to ``add_action("init");``
				*
				* @return null Or exits script execution after redirection to Google® Checkout.
				*/
				public static function google_co ()
					{
						global /* For Multisite support. */ $current_site, $current_blog;

						if (!empty ($_GET["s2member_pro_google_co"]) && c_ws_plugin__s2member_utils_urls::s2member_sig_ok ($_SERVER["REQUEST_URI"]) && !empty ($_GET["co"]) && is_array ($attr = c_ws_plugin__s2member_utils_strings::trim_deep (stripslashes_deep ($_GET["co"]))) && $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"])
							{
								$attr = shortcode_atts (array ("ids" => "0", "exp" => "72", "level" => "1", "ccaps" => "", "desc" => "", "cc" => "USD", "custom" => $_SERVER["HTTP_HOST"], "ta" => "0", "tp" => "0", "tt" => "D", "ra" => "0.01", "rp" => "1", "rt" => "M", "rr" => "1", "rrt" => "", "modify" => "0", "cancel" => "0", "sp" => "0", "image" => "default", "output" => "anchor"), $attr);

								$attr["tt"] = strtoupper ($attr["tt"]); // Term lengths absolutely must be provided in upper-case format. Only after running shortcode_atts().
								$attr["rt"] = strtoupper ($attr["rt"]); // Term lengths absolutely must be provided in upper-case format. Only after running shortcode_atts().
								$attr["rr"] = strtoupper ($attr["rr"]); // Must be provided in upper-case format. Numerical, or BN value. Only after running shortcode_atts().
								$attr["ccaps"] = strtolower ($attr["ccaps"]); // Custom Capabilities must be typed in lower-case format. Only after running shortcode_atts().
								$attr["rr"] = ($attr["rt"] === "L") ? "BN" : $attr["rr"]; // Lifetime Subscriptions require Buy Now. Only after running shortcode_atts().
								$attr["rr"] = ($attr["level"] === "*") ? "BN" : $attr["rr"]; // Independent Ccaps do NOT recur. Only after running shortcode_atts().
								$attr["rr"] = (!$attr["tp"] && !$attr["rr"]) ? "BN" : $attr["rr"]; // No Trial / non-recurring. Only after running shortcode_atts().

								if ($attr["modify"] || $attr["cancel"]) // This is a special routine for Google® Modifications/Cancellations (one in the same).
									{
										$endpoint = ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_sandbox"]) ? "sandbox.google.com/checkout" : "checkout.google.com";

										wp_redirect("https://" . $endpoint . "/");
									}

								else if ($attr["sp"]) // Specific Posts/Pages.
									{
										$attr["uniqid"] = uniqid (); // Unique ID.
										$attr["referencing"] = c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id ();

										$attr["sp_ids_exp"] = "sp:" . $attr["ids"] . ":" . $attr["exp"]; // Combined "sp:ids:expiration hours".
										$attr["sp_access_link"] = c_ws_plugin__s2member_sp_access::sp_access_link_gen ($attr["ids"], $attr["exp"]);

										$xml = '<?xml version="1.0" encoding="UTF-8"?>';
										$xml .= '<checkout-shopping-cart xmlns="http://checkout.google.com/schema/2">';
										$xml .= '<shopping-cart>';
										$xml .= '<items>';

										$xml .= '<item>';

										$xml .= '<quantity>1</quantity>';
										$xml .= '<item-name>' . esc_html ($attr["desc"]) . '</item-name>';
										$xml .= '<item-description>(TID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (_x ("Grants you immediate access.", "s2member-front", "s2member")) . '</item-description>';
										$xml .= '<unit-price currency="' . esc_attr ($attr["cc"]) . '">' . esc_html ($attr["ra"]) . '</unit-price>';

										$xml .= '<merchant-private-item-data>';
										$xml .= '<s2_txn_id>s2-' . esc_html ($attr["uniqid"]) . '</s2_txn_id>';
										$xml .= '<s2_custom>' . esc_html ($attr["custom"]) . '</s2_custom>';
										$xml .= '<s2_item_number>' . esc_html ($attr["sp_ids_exp"]) . '</s2_item_number>';
										$xml .= '<s2_customer_ip>' . esc_html ($_SERVER["REMOTE_ADDR"]) . '</s2_customer_ip>';
										$xml .= ($attr["referencing"]) ? '<s2_referencing>' . esc_html ($attr["referencing"]) . '</s2_referencing>' : '';
										$xml .= '</merchant-private-item-data>';

										$xml .= '<digital-content>';
										$xml .= '<display-disposition>PESSIMISTIC</display-disposition>';
										$xml .= '<description>' . esc_html ($attr["desc"]) . '</description>';
										$xml .= '<url>' . esc_html ($attr["sp_access_link"]) . '</url>';
										$xml .= '</digital-content>';

										$xml .= '</item>';

										$xml .= '</items>';
										$xml .= '</shopping-cart>';

										$xml .= '<checkout-flow-support>';
										$xml .= '<merchant-checkout-flow-support>';
										$xml .= '<edit-cart-url>' . esc_html (get_page_link ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["membership_options_page"])) . '</edit-cart-url>';
										$xml .= '<continue-shopping-url>' . esc_html ($attr["sp_access_link"]) . '</continue-shopping-url>';
										$xml .= '</merchant-checkout-flow-support>';
										$xml .= '</checkout-flow-support>';

										$xml .= '</checkout-shopping-cart>';

										$endpoint = ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_sandbox"]) ? "sandbox.google.com/checkout" : "checkout.google.com";

										if (($xml = c_ws_plugin__s2member_utils_urls::remote ("https://" . $endpoint . "/api/checkout/v2/merchantCheckout/Merchant/" . $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"], $xml, array_merge (c_ws_plugin__s2member_pro_google_utilities::google_api_headers (), array ("timeout" => 20)))) && preg_match ("/\<redirect-url\>(.+?)\<\/redirect-url\>/i", preg_replace ("/[\r\n\t]+/", "", $xml), $m) && ($google = $m[1]))
											wp_redirect(wp_specialchars_decode ($google, ENT_QUOTES)); // Redirect to Google® Checkout.
										else // Display error message.
											echo strip_tags ($xml);
									}

								else if ($attr["level"] === "*") // Independent Custom Capabilities.
									{
										$attr["uniqid"] = uniqid (); // Unique ID.
										$attr["referencing"] = c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id ();

										$attr["level_ccaps_eotper"] = ($attr["rt"] !== "L") ? $attr["level"] . ":" . $attr["ccaps"] . ":" . $attr["rp"] . " " . $attr["rt"] : $attr["level"] . ":" . $attr["ccaps"];
										$attr["level_ccaps_eotper"] = rtrim ($attr["level_ccaps_eotper"], ":"); // Right-trim separators from this string so we don't have trailing colons.

										$xml = '<?xml version="1.0" encoding="UTF-8"?>';
										$xml .= '<checkout-shopping-cart xmlns="http://checkout.google.com/schema/2">';
										$xml .= '<shopping-cart>';
										$xml .= '<items>';

										$xml .= '<item>';

										$xml .= '<quantity>1</quantity>';
										$xml .= '<item-name>' . esc_html ($attr["desc"]) . '</item-name>';
										$xml .= '<item-description>(TID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (_x ("Grants you immediate access.", "s2member-front", "s2member")) . '</item-description>';
										$xml .= '<unit-price currency="' . esc_attr ($attr["cc"]) . '">' . esc_html ($attr["ra"]) . '</unit-price>';

										$xml .= '<merchant-private-item-data>';
										$xml .= '<s2_txn_id>s2-' . esc_html ($attr["uniqid"]) . '</s2_txn_id>';
										$xml .= '<s2_custom>' . esc_html ($attr["custom"]) . '</s2_custom>';
										$xml .= '<s2_customer_ip>' . esc_html ($_SERVER["REMOTE_ADDR"]) . '</s2_customer_ip>';
										$xml .= '<s2_item_number>' . esc_html ($attr["level_ccaps_eotper"]) . '</s2_item_number>';
										$xml .= '<s2_referencing>' . esc_html ($attr["referencing"]) . '</s2_referencing>';
										$xml .= '</merchant-private-item-data>';

										$xml .= '<digital-content>';
										$xml .= '<display-disposition>PESSIMISTIC</display-disposition>';
										$xml .= '<description>' . esc_html (sprintf (_x ('You now have access to:<br />%s<br />(<a href="%s">please log back in now</a>)', "s2member-front", "s2member"), $attr["desc"], esc_attr (wp_login_url ()))) . '</description>';
										$xml .= '<url>' . esc_html (wp_login_url ()) . '</url>';
										$xml .= '</digital-content>';

										$xml .= '</item>';

										$xml .= '</items>';
										$xml .= '</shopping-cart>';

										$xml .= '<checkout-flow-support>';
										$xml .= '<merchant-checkout-flow-support>';
										$xml .= '<edit-cart-url>' . esc_html (get_page_link ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["membership_options_page"])) . '</edit-cart-url>';
										$xml .= '<continue-shopping-url>' . esc_html (wp_login_url ()) . '</continue-shopping-url>';
										$xml .= '</merchant-checkout-flow-support>';
										$xml .= '</checkout-flow-support>';

										$xml .= '</checkout-shopping-cart>';

										$endpoint = ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_sandbox"]) ? "sandbox.google.com/checkout" : "checkout.google.com";

										if (($xml = c_ws_plugin__s2member_utils_urls::remote ("https://" . $endpoint . "/api/checkout/v2/merchantCheckout/Merchant/" . $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"], $xml, array_merge (c_ws_plugin__s2member_pro_google_utilities::google_api_headers (), array ("timeout" => 20)))) && preg_match ("/\<redirect-url\>(.+?)\<\/redirect-url\>/i", preg_replace ("/[\r\n\t]+/", "", $xml), $m) && ($google = $m[1]))
											wp_redirect(wp_specialchars_decode ($google, ENT_QUOTES)); // Redirect to Google® Checkout.
										else // Display error message.
											echo strip_tags ($xml);
									}

								else if ($attr["rr"] === "BN" || (!$attr["tp"] && !$attr["rr"])) // Buy Now.
									{
										$attr["uniqid"] = uniqid (); // Unique ID.
										$attr["referencing"] = c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id ();

										$attr["desc"] = (!$attr["desc"]) ? $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["level" . $attr["level"] . "_label"] : $attr["desc"];

										$attr["level_ccaps_eotper"] = ($attr["rt"] !== "L") ? $attr["level"] . ":" . $attr["ccaps"] . ":" . $attr["rp"] . " " . $attr["rt"] : $attr["level"] . ":" . $attr["ccaps"];
										$attr["level_ccaps_eotper"] = rtrim ($attr["level_ccaps_eotper"], ":"); // Right-trim separators from this string so we don't have trailing colons.

										$attr["register_access_link"] = c_ws_plugin__s2member_register_access::register_link_gen ("google", "s2-" . $attr["uniqid"], $attr["custom"], $attr["level_ccaps_eotper"]);

										$xml = '<?xml version="1.0" encoding="UTF-8"?>';
										$xml .= '<checkout-shopping-cart xmlns="http://checkout.google.com/schema/2">';
										$xml .= '<shopping-cart>';
										$xml .= '<items>';

										$xml .= '<item>';

										$xml .= '<quantity>1</quantity>';
										$xml .= '<item-name>' . esc_html ($attr["desc"]) . '</item-name>';
										$xml .= '<item-description>(TID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (_x ("Grants you immediate access.", "s2member-front", "s2member")) . '</item-description>';
										$xml .= '<unit-price currency="' . esc_attr ($attr["cc"]) . '">' . esc_html ($attr["ra"]) . '</unit-price>';

										$xml .= '<merchant-private-item-data>';
										$xml .= '<s2_txn_id>s2-' . esc_html ($attr["uniqid"]) . '</s2_txn_id>';
										$xml .= '<s2_custom>' . esc_html ($attr["custom"]) . '</s2_custom>';
										$xml .= '<s2_customer_ip>' . esc_html ($_SERVER["REMOTE_ADDR"]) . '</s2_customer_ip>';
										$xml .= '<s2_item_number>' . esc_html ($attr["level_ccaps_eotper"]) . '</s2_item_number>';
										$xml .= ($attr["referencing"]) ? '<s2_referencing>' . esc_html ($attr["referencing"]) . '</s2_referencing>' : '';
										$xml .= '</merchant-private-item-data>';

										$xml .= '<digital-content>';
										$xml .= '<display-disposition>PESSIMISTIC</display-disposition>';

										if ($attr["referencing"]) // If we're updating an existing account that is already in the system.
											{
												$xml .= '<description>' . esc_html (sprintf (_x ('You\'ve been updated to:<br />%s<br />(<a href="%s">please log back in now</a>)', "s2member-front", "s2member"), $attr["desc"], esc_attr (wp_login_url ()))) . '</description>';
												$xml .= '<url>' . esc_html (wp_login_url ()) . '</url>';
											}
										else // Otherwise, this checkout experience will establish a brand new Membership.
											{
												$xml .= '<description>' . esc_html (sprintf (_x ('%s<br />(the next step is to Register a Username)', "s2member-front", "s2member"), $attr["desc"])) . '</description>';
												$xml .= '<url>' . esc_html ($attr["register_access_link"]) . '</url>';
											}

										$xml .= '</digital-content>';

										$xml .= '</item>';

										$xml .= '</items>';
										$xml .= '</shopping-cart>';

										$xml .= '<checkout-flow-support>';
										$xml .= '<merchant-checkout-flow-support>';
										$xml .= '<edit-cart-url>' . esc_html (get_page_link ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["membership_options_page"])) . '</edit-cart-url>';
										$xml .= '<continue-shopping-url>' . esc_html ((($attr["referencing"]) ? wp_login_url () : $attr["register_access_link"])) . '</continue-shopping-url>';
										$xml .= '</merchant-checkout-flow-support>';
										$xml .= '</checkout-flow-support>';

										$xml .= '</checkout-shopping-cart>';

										$endpoint = ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_sandbox"]) ? "sandbox.google.com/checkout" : "checkout.google.com";

										if (($xml = c_ws_plugin__s2member_utils_urls::remote ("https://" . $endpoint . "/api/checkout/v2/merchantCheckout/Merchant/" . $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"], $xml, array_merge (c_ws_plugin__s2member_pro_google_utilities::google_api_headers (), array ("timeout" => 20)))) && preg_match ("/\<redirect-url\>(.+?)\<\/redirect-url\>/i", preg_replace ("/[\r\n\t]+/", "", $xml), $m) && ($google = $m[1]))
											wp_redirect(wp_specialchars_decode ($google, ENT_QUOTES)); // Redirect to Google® Checkout.
										else // Display error message.
											echo strip_tags ($xml);
									}

								else // Else, use Membership routines.
									{
										$attr["uniqid"] = uniqid (); // Unique ID.
										$attr["referencing"] = c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id ();

										$attr["desc"] = (!$attr["desc"]) ? $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["level" . $attr["level"] . "_label"] : $attr["desc"];

										$attr["level_ccaps_eotper"] = $attr["level"] . ":" . $attr["ccaps"]; // Actual Subscriptions will always end on their own.
										$attr["level_ccaps_eotper"] = rtrim ($attr["level_ccaps_eotper"], ":"); // Clean any trailing separators from this string.

										$attr["periodicity"] = c_ws_plugin__s2member_pro_google_utilities::google_periodicity ($attr["rp"] . " " . $attr["rt"]); // Google® periodicity.

										$attr["register_access_link"] = c_ws_plugin__s2member_register_access::register_link_gen ("google", "s2-" . $attr["uniqid"], $attr["custom"], $attr["level_ccaps_eotper"]);

										if ($attr["tp"]) // An actual Subscription that includes a Trial Period; which MIGHT also be recurring.
											{
												$attr["start_time"] = c_ws_plugin__s2member_pro_google_utilities::google_start_time ($attr["tp"] . " " . $attr["tt"]);

												$xml = '<?xml version="1.0" encoding="UTF-8"?>';
												$xml .= '<checkout-shopping-cart xmlns="http://checkout.google.com/schema/2">';
												$xml .= '<shopping-cart>';
												$xml .= '<items>';

												$xml .= '<item>';

												$xml .= '<quantity>1</quantity>';
												$xml .= '<item-name>' . esc_html ($attr["desc"]) . '</item-name>';
												$xml .= ($attr["ta"] < 0.01) ? // This needs to change; depending on whether or not this is a 100% Free Trial offer.
												'<item-description>(SID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (_x ("100% free trial. NO charge today.", "s2member-front", "s2member")) . '</item-description>' :
												'<item-description>(SID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (_x ("First payment for immediate access.", "s2member-front", "s2member")) . '</item-description>';
												$xml .= '<unit-price currency="' . esc_attr ($attr["cc"]) . '">' . esc_html ($attr["ta"]) . '</unit-price>';

												$xml .= '<merchant-private-item-data>';
												$xml .= '<s2_subscr_id>s2-' . esc_html ($attr["uniqid"]) . '</s2_subscr_id>';
												$xml .= '<s2_custom>' . esc_html ($attr["custom"]) . '</s2_custom>';
												$xml .= '<s2_customer_ip>' . esc_html ($_SERVER["REMOTE_ADDR"]) . '</s2_customer_ip>';
												$xml .= '<s2_item_number>' . esc_html ($attr["level_ccaps_eotper"]) . '</s2_item_number>';
												$xml .= '<s2_period1>' . esc_html ($attr["tp"] . " " . $attr["tt"]) . '</s2_period1>';
												$xml .= '<s2_period3>' . esc_html ($attr["rp"] . " " . $attr["rt"]) . '</s2_period3>';
												$xml .= ($attr["rr"]) ? '<s2_recurring>' . esc_html ($attr["rr"]) . '</s2_recurring>' : '';
												$xml .= ($attr["referencing"]) ? '<s2_referencing>' . esc_html ($attr["referencing"]) . '</s2_referencing>' : '';
												$xml .= '</merchant-private-item-data>';

												$xml .= '<digital-content>';
												$xml .= '<display-disposition>PESSIMISTIC</display-disposition>';
												$xml .= '<description>' . esc_html (_x ("You'll receive an email confirmation within 15 minutes.", "s2member-front", "s2member")) . '</description>';
												$xml .= '</digital-content>';

												$xml .= '</item>';

												$xml .= '<item>';

												$xml .= '<quantity>1</quantity>';
												$xml .= '<item-name>' . esc_html ($attr["desc"]) . '</item-name>';
												$xml .= '<item-description>(SID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (sprintf (_x ("Cancel at any time to avoid %s.", "s2member-front", "s2member"), ((!$attr["rr"]) ? _x ("this charge", "s2member-front", "s2member") : _x ("charges", "s2member-front", "s2member")))) . '</item-description>';
												$xml .= '<unit-price currency="' . esc_attr ($attr["cc"]) . '">0.00</unit-price>';

												$xml .= '<merchant-private-item-data>';
												$xml .= '<s2_subscr_id>s2-' . esc_html ($attr["uniqid"]) . '</s2_subscr_id>';
												$xml .= '<s2_custom>' . esc_html ($attr["custom"]) . '</s2_custom>';
												$xml .= '<s2_customer_ip>' . esc_html ($_SERVER["REMOTE_ADDR"]) . '</s2_customer_ip>';
												$xml .= '<s2_item_number>' . esc_html ($attr["level_ccaps_eotper"]) . '</s2_item_number>';
												$xml .= '<s2_period1>' . esc_html ($attr["tp"] . " " . $attr["tt"]) . '</s2_period1>';
												$xml .= '<s2_period3>' . esc_html ($attr["rp"] . " " . $attr["rt"]) . '</s2_period3>';
												$xml .= ($attr["rr"]) ? '<s2_recurring>' . esc_html ($attr["rr"]) . '</s2_recurring>' : '';
												$xml .= ($attr["referencing"]) ? '<s2_referencing>' . esc_html ($attr["referencing"]) . '</s2_referencing>' : '';
												$xml .= '</merchant-private-item-data>';

												$xml .= '<digital-content>';
												$xml .= '<display-disposition>PESSIMISTIC</display-disposition>';

												if ($attr["referencing"]) // If we're updating an existing account; already in the system.
													{
														$xml .= '<description>' . esc_html (sprintf (_x ('You\'ve been updated to:<br />%s<br />(<a href="%s">please log back in now</a>)', "s2member-front", "s2member"), $attr["desc"], esc_attr (wp_login_url ()))) . '</description>';
														$xml .= '<url>' . esc_html (wp_login_url ()) . '</url>';
													}
												else // Otherwise, this checkout experience will establish a brand new Membership.
													{
														$xml .= '<description>' . esc_html (sprintf (_x ('%s<br />(the next step is to Register a Username)', "s2member-front", "s2member"), $attr["desc"])) . '</description>';
														$xml .= '<url>' . esc_html ($attr["register_access_link"]) . '</url>';
													}

												$xml .= '</digital-content>';

												$xml .= '<subscription type="google" period="' . esc_attr ($attr["periodicity"]) . '" start-date="' . esc_attr (date ("Y-m-d", $attr["start_time"]) . "T00:00:00Z") . '">';

												$xml .= '<payments>';
												$xml .= '<subscription-payment' . ((!$attr["rr"]) ? ' times="1"' : (($attr["rrt"]) ? ' times="'.esc_attr($attr["rrt"]).'"' : "")) . '>';
												$xml .= '<maximum-charge currency="' . esc_attr ($attr["cc"]) . '">' . esc_html ($attr["ra"]) . '</maximum-charge>';
												$xml .= '</subscription-payment>';
												$xml .= '</payments>';

												$xml .= '<recurrent-item>';

												$xml .= '<quantity>1</quantity>';
												$xml .= '<item-name>' . esc_html ($attr["desc"]) . '</item-name>';
												$xml .= '<item-description>(SID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (_x ("Covers ongoing access.", "s2member-front", "s2member")) . '</item-description>';
												$xml .= '<unit-price currency="' . esc_attr ($attr["cc"]) . '">' . esc_html ($attr["ra"]) . '</unit-price>';

												$xml .= '<merchant-private-item-data>';
												$xml .= '<s2_subscr_payment>1</s2_subscr_payment>';
												$xml .= '<s2_subscr_id>s2-' . esc_html ($attr["uniqid"]) . '</s2_subscr_id>';
												$xml .= '<s2_custom>' . esc_html ($attr["custom"]) . '</s2_custom>';
												$xml .= '<s2_customer_ip>' . esc_html ($_SERVER["REMOTE_ADDR"]) . '</s2_customer_ip>';
												$xml .= '<s2_item_number>' . esc_html ($attr["level_ccaps_eotper"]) . '</s2_item_number>';
												$xml .= '<s2_period1>' . esc_html ($attr["tp"] . " " . $attr["tt"]) . '</s2_period1>';
												$xml .= '<s2_period3>' . esc_html ($attr["rp"] . " " . $attr["rt"]) . '</s2_period3>';
												$xml .= ($attr["rr"]) ? '<s2_recurring>' . esc_html ($attr["rr"]) . '</s2_recurring>' : '';
												$xml .= ($attr["referencing"]) ? '<s2_referencing>' . esc_html ($attr["referencing"]) . '</s2_referencing>' : '';
												$xml .= '</merchant-private-item-data>';

												$xml .= '<digital-content>';
												$xml .= '<display-disposition>PESSIMISTIC</display-disposition>';
												$xml .= '<description>' . esc_html ($attr["desc"]) . '</description>';
												$xml .= '<url>' . esc_html (home_url ("/")) . '</url>';
												$xml .= '</digital-content>';

												$xml .= '</recurrent-item>';

												$xml .= '</subscription>';

												$xml .= '</item>';

												$xml .= '</items>';
												$xml .= '</shopping-cart>';

												$xml .= '<checkout-flow-support>';
												$xml .= '<merchant-checkout-flow-support>';
												$xml .= '<edit-cart-url>' . esc_html (get_page_link ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["membership_options_page"])) . '</edit-cart-url>';
												$xml .= '<continue-shopping-url>' . esc_html ((($attr["referencing"]) ? wp_login_url () : $attr["register_access_link"])) . '</continue-shopping-url>';
												$xml .= '</merchant-checkout-flow-support>';
												$xml .= '</checkout-flow-support>';

												$xml .= '</checkout-shopping-cart>';
											}
										else if (!$attr["tp"] && $attr["rr"]) /* This is a Subscription w/o a Trial Period; and it IS associated with multiple recurring charges.
											This should ALWAYS be associated with recurring charges, because of the "BN" check above that includes (!$attr["tp"] && !$attr["rr"]).
											In other words, we should never have a Subscription w/o a Trial Period, AND no recurring charges. That would make no sense. */
											{
												$attr["start_time"] = c_ws_plugin__s2member_pro_google_utilities::google_start_time ($attr["rp"] . " " . $attr["rt"]);

												$xml = '<?xml version="1.0" encoding="UTF-8"?>';
												$xml .= '<checkout-shopping-cart xmlns="http://checkout.google.com/schema/2">';
												$xml .= '<shopping-cart>';
												$xml .= '<items>';

												$xml .= '<item>';

												$xml .= '<quantity>1</quantity>';
												$xml .= '<item-name>' . esc_html ($attr["desc"]) . '</item-name>';
												$xml .= '<item-description>(SID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (_x ("First payment for immediate access.", "s2member-front", "s2member")) . '</item-description>';
												$xml .= '<unit-price currency="' . esc_attr ($attr["cc"]) . '">' . esc_html ($attr["ra"]) . '</unit-price>';

												$xml .= '<merchant-private-item-data>';
												$xml .= '<s2_subscr_id>s2-' . esc_html ($attr["uniqid"]) . '</s2_subscr_id>';
												$xml .= '<s2_custom>' . esc_html ($attr["custom"]) . '</s2_custom>';
												$xml .= '<s2_customer_ip>' . esc_html ($_SERVER["REMOTE_ADDR"]) . '</s2_customer_ip>';
												$xml .= '<s2_item_number>' . esc_html ($attr["level_ccaps_eotper"]) . '</s2_item_number>';
												$xml .= '<s2_period1>0 D</s2_period1>'; // There is no Trial Period.
												$xml .= '<s2_period3>' . esc_html ($attr["rp"] . " " . $attr["rt"]) . '</s2_period3>';
												$xml .= '<s2_recurring>' . esc_html ($attr["rr"]) . '</s2_recurring>';
												$xml .= ($attr["referencing"]) ? '<s2_referencing>' . esc_html ($attr["referencing"]) . '</s2_referencing>' : '';
												$xml .= '</merchant-private-item-data>';

												$xml .= '<digital-content>';
												$xml .= '<display-disposition>PESSIMISTIC</display-disposition>';
												$xml .= '<description>' . esc_html (_x ("You'll receive an email confirmation within 15 minutes.", "s2member-front", "s2member")) . '</description>';
												$xml .= '</digital-content>';

												$xml .= '</item>';

												$xml .= '<item>';

												$xml .= '<quantity>1</quantity>';
												$xml .= '<item-name>' . esc_html ($attr["desc"]) . '</item-name>';
												$xml .= '<item-description>(SID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (_x ("Cancel at any time to avoid charges.", "s2member-front", "s2member")) . '</item-description>';
												$xml .= '<unit-price currency="' . esc_attr ($attr["cc"]) . '">0.00</unit-price>';

												$xml .= '<merchant-private-item-data>';
												$xml .= '<s2_subscr_id>s2-' . esc_html ($attr["uniqid"]) . '</s2_subscr_id>';
												$xml .= '<s2_custom>' . esc_html ($attr["custom"]) . '</s2_custom>';
												$xml .= '<s2_customer_ip>' . esc_html ($_SERVER["REMOTE_ADDR"]) . '</s2_customer_ip>';
												$xml .= '<s2_item_number>' . esc_html ($attr["level_ccaps_eotper"]) . '</s2_item_number>';
												$xml .= '<s2_period1>0 D</s2_period1>'; // There is no Trial Period.
												$xml .= '<s2_period3>' . esc_html ($attr["rp"] . " " . $attr["rt"]) . '</s2_period3>';
												$xml .= '<s2_recurring>' . esc_html ($attr["rr"]) . '</s2_recurring>';
												$xml .= ($attr["referencing"]) ? '<s2_referencing>' . esc_html ($attr["referencing"]) . '</s2_referencing>' : '';
												$xml .= '</merchant-private-item-data>';

												$xml .= '<digital-content>';
												$xml .= '<display-disposition>PESSIMISTIC</display-disposition>';

												if ($attr["referencing"]) // If we're updating an existing account that is already in the system.
													{
														$xml .= '<description>' . esc_html (sprintf (_x ('You\'ve been updated to:<br />%s<br />(<a href="%s">please log back in now</a>)', "s2member-front", "s2member"), $attr["desc"], esc_attr (wp_login_url ()))) . '</description>';
														$xml .= '<url>' . esc_html (wp_login_url ()) . '</url>';
													}
												else // Otherwise, this checkout experience will establish a brand new Membership.
													{
														$xml .= '<description>' . esc_html (sprintf (_x ('%s<br />(the next step is to Register a Username)', "s2member-front", "s2member"), $attr["desc"])) . '</description>';
														$xml .= '<url>' . esc_html ($attr["register_access_link"]) . '</url>';
													}

												$xml .= '</digital-content>';

												$xml .= '<subscription type="google" period="' . esc_attr ($attr["periodicity"]) . '" start-date="' . esc_attr (date ("Y-m-d", $attr["start_time"]) . "T00:00:00Z") . '">';

												$xml .= '<payments>';
												$xml .= '<subscription-payment'.(($attr["rrt"]) ? ' times="'.esc_attr($attr["rrt"]).'"' : "").'>';
												$xml .= '<maximum-charge currency="' . esc_attr ($attr["cc"]) . '">' . esc_html ($attr["ra"]) . '</maximum-charge>';
												$xml .= '</subscription-payment>';
												$xml .= '</payments>';

												$xml .= '<recurrent-item>';

												$xml .= '<quantity>1</quantity>';
												$xml .= '<item-name>' . esc_html ($attr["desc"]) . '</item-name>';
												$xml .= '<item-description>(SID:s2-' . esc_html ($attr["uniqid"]) . ') ' . esc_html (_x ("Covers ongoing access.", "s2member-front", "s2member")) . '</item-description>';
												$xml .= '<unit-price currency="' . esc_attr ($attr["cc"]) . '">' . esc_html ($attr["ra"]) . '</unit-price>';

												$xml .= '<merchant-private-item-data>';
												$xml .= '<s2_subscr_payment>1</s2_subscr_payment>';
												$xml .= '<s2_subscr_id>s2-' . esc_html ($attr["uniqid"]) . '</s2_subscr_id>';
												$xml .= '<s2_custom>' . esc_html ($attr["custom"]) . '</s2_custom>';
												$xml .= '<s2_customer_ip>' . esc_html ($_SERVER["REMOTE_ADDR"]) . '</s2_customer_ip>';
												$xml .= '<s2_item_number>' . esc_html ($attr["level_ccaps_eotper"]) . '</s2_item_number>';
												$xml .= '<s2_period1>0 D</s2_period1>'; // There is no Trial Period.
												$xml .= '<s2_period3>' . esc_html ($attr["rp"] . " " . $attr["rt"]) . '</s2_period3>';
												$xml .= '<s2_recurring>' . esc_html ($attr["rr"]) . '</s2_recurring>';
												$xml .= ($attr["referencing"]) ? '<s2_referencing>' . esc_html ($attr["referencing"]) . '</s2_referencing>' : '';
												$xml .= '</merchant-private-item-data>';

												$xml .= '<digital-content>';
												$xml .= '<display-disposition>PESSIMISTIC</display-disposition>';
												$xml .= '<description>' . esc_html ($attr["desc"]) . '</description>';
												$xml .= '<url>' . esc_html (home_url ("/")) . '</url>';
												$xml .= '</digital-content>';

												$xml .= '</recurrent-item>';

												$xml .= '</subscription>';

												$xml .= '</item>';

												$xml .= '</items>';
												$xml .= '</shopping-cart>';

												$xml .= '<checkout-flow-support>';
												$xml .= '<merchant-checkout-flow-support>';
												$xml .= '<edit-cart-url>' . esc_html (get_page_link ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["membership_options_page"])) . '</edit-cart-url>';
												$xml .= '<continue-shopping-url>' . esc_html ((($attr["referencing"]) ? wp_login_url () : $attr["register_access_link"])) . '</continue-shopping-url>';
												$xml .= '</merchant-checkout-flow-support>';
												$xml .= '</checkout-flow-support>';

												$xml .= '</checkout-shopping-cart>';
											}

										$endpoint = ($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_sandbox"]) ? "sandbox.google.com/checkout" : "checkout.google.com";

										if (($xml = c_ws_plugin__s2member_utils_urls::remote ("https://" . $endpoint . "/api/checkout/v2/merchantCheckout/Merchant/" . $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"], $xml, array_merge (c_ws_plugin__s2member_pro_google_utilities::google_api_headers (), array ("timeout" => 20)))) && preg_match ("/\<redirect-url\>(.+?)\<\/redirect-url\>/i", preg_replace ("/[\r\n\t]+/", "", $xml), $m) && ($google = $m[1]))
											wp_redirect(wp_specialchars_decode ($google, ENT_QUOTES)); // Redirect to Google® Checkout.
										else // Display error message.
											echo strip_tags ($xml);
									}

								exit /* Clean exit. */ ();
							}
					}
			}
	}
?>