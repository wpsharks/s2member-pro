<?php
/**
* Shortcode `[s2Member-Pro-Google-Button /]` (inner processing routines).
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
* @package s2Member\Google
* @since 1.5
*/
if (realpath (__FILE__) === realpath ($_SERVER["SCRIPT_FILENAME"]))
	exit ("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_google_button_in"))
	{
		/**
		* Shortcode `[s2Member-Pro-Google-Button /]` (inner processing routines).
		*
		* @package s2Member\Google
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_google_button_in
			{
				/**
				* Shortcode `[s2Member-Pro-Google-Button /]`.
				*
				* @package s2Member\Google
				* @since 1.5
				*
				* @attaches-to ``add_shortcode("s2Member-Pro-Google-Button");``
				*
				* @param array $attr An array of Attributes.
				* @param str $content Content inside the Shortcode.
				* @param str $shortcode The actual Shortcode name itself.
				* @return str The resulting Google Button Code, HTML markup.
				*/
				public static function sc_google_button ($attr = FALSE, $content = FALSE, $shortcode = FALSE)
					{
						include_once dirname(dirname(dirname(dirname(__FILE__)))).'/_xtnls/JWT.php';

						c_ws_plugin__s2member_no_cache::no_cache_constants /* No caching on pages that contain this Payment Button. */ (true);

						$attr = /* Force array. Trim quote entities. */ c_ws_plugin__s2member_utils_strings::trim_qts_deep ((array)$attr);

						$attr = shortcode_atts (array ("ids" => "0", "exp" => "72", "level" => "1", "ccaps" => "", "desc" => "", "cc" => "USD", "custom" => $_SERVER["HTTP_HOST"], "ta" => "0", "tp" => "0", "tt" => "D", "ra" => "0.01", "rp" => "1", "rt" => "M", "rr" => "1", "rrt" => "", "modify" => "0", "cancel" => "0", "sp" => "0", "image" => "default", "output" => "anchor", "success" => ""), $attr);

						$attr["tt"] = /* Term lengths absolutely must be provided in upper-case format. Only after running shortcode_atts(). */ strtoupper ($attr["tt"]);
						$attr["rt"] = /* Term lengths absolutely must be provided in upper-case format. Only after running shortcode_atts(). */ strtoupper ($attr["rt"]);
						$attr["rr"] = /* Must be provided in upper-case format. Numerical, or BN value. Only after running shortcode_atts(). */ strtoupper ($attr["rr"]);
						$attr["ccaps"] = /* Custom Capabilities must be typed in lower-case format. Only after running shortcode_atts(). */ strtolower ($attr["ccaps"]);
						$attr["ccaps"] = /* Custom Capabilities should not have spaces. */ str_replace(" ", "", $attr["ccaps"]);
						$attr["rr"] = /* Lifetime Subscriptions require Buy Now. Only after running shortcode_atts(). */ ($attr["rt"] === "L") ? "BN" : $attr["rr"];
						$attr["rr"] = /* Independent Ccaps do NOT recur. Only after running shortcode_atts(). */ ($attr["level"] === "*") ? "BN" : $attr["rr"];
						$attr["rr"] = /* No Trial / non-recurring. Only after running shortcode_atts(). */ (!$attr["tp"] && !$attr["rr"]) ? "BN" : $attr["rr"];

						if /* Modifications/Cancellations. */ ($attr["modify"] || $attr["cancel"])
							{
								$default_image = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images/google-edit-button.png";

								$code = trim (c_ws_plugin__s2member_utilities::evl (file_get_contents (dirname (dirname (dirname (dirname (__FILE__)))) . "/templates/buttons/google-cancellation-button.php")));
								$code = preg_replace ("/%%images%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images")), $code);
								$code = preg_replace ("/%%wpurl%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr (site_url ())), $code);

								$code = $_code = ($attr["image"] && $attr["image"] !== "default") ? preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($attr["image"])) . '"', $code) : preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($default_image)) . '"', $code);

								$code = ($attr["output"] === "anchor") ? /* Buttons already in anchor format. */ $code : $code;
								if ($attr["output"] === "url" && preg_match ('/ href\="(.*?)"/', $code, $m) && ($href = $m[1]))
									$code = ($url = c_ws_plugin__s2member_utils_urls::n_amps ($href));

								unset /* Just a little housekeeping */ ($href, $url, $m);
							}
						else if /* Specific Post/Page Buttons. */ ($attr["sp"])
							{
								$default_image = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images/google-wallet-co.png";

								$failure_return_url = site_url ("/?s2member_pro_google_return=0");
								$success_return_url = site_url ("/?s2member_pro_google_return=1");
								if($attr["success"]) // A custom return URL on success? If so, let's attach that now.
									$success_return_url = add_query_arg ("s2member_pro_google_return_success", rawurlencode ($attr["success"]), $success_return_url);

								$code = trim (c_ws_plugin__s2member_utilities::evl (file_get_contents (dirname (dirname (dirname (dirname (__FILE__)))) . "/templates/buttons/google-sp-checkout-button.php")));
								$code = preg_replace ("/%%images%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images")), $code);
								$code = preg_replace ("/%%wpurl%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr (site_url ())), $code);

								$attr["uniqid"] = uniqid (); // Unique ID.
								$attr["referencing"] = c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id ();

								$attr["sp_ids_exp"] = "sp:" . $attr["ids"] . ":" . $attr["exp"]; // Combined "sp:ids:expiration hours".

								$jwt["iss"] = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"];
								$jwt["aud"] = "Google"; $jwt["typ"] = "google/payments/inapp/item/v1";
								$jwt["exp"] = time() + 3600; $jwt["iat"] = time();
								$jwt["request"] = array("name" => substr($_SERVER["HTTP_HOST"], 0, 50), "description" => substr($attr["desc"], 0, 100),
																"price" => number_format($attr["ra"], 2, ".", ""), "currencyCode" => $attr["cc"],
																"sellerData" => json_encode(
																	array("s2_txn_id" => $attr["uniqid"],
																			"s2_custom" => $attr["custom"],
																			"s2_item_number" => $attr["sp_ids_exp"],
																			"s2_customer_ip" => $_SERVER["REMOTE_ADDR"],
																			"s2_referencing" => $attr["referencing"])));

								$code = preg_replace ("/%%jwt%%/",JWT::encode($jwt, $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_key"]), $code);
								$code = preg_replace (array("/%%success%%/", "/%%failure%%/"), array($success_return_url, $failure_return_url), $code);

								$code = $_code = ($attr["image"] && $attr["image"] !== "default") ? preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($attr["image"])) . '"', $code) : preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($default_image)) . '"', $code);
							}
						else if /* Independent Custom Capabilities. */ ($attr["level"] === "*")
							{
								$default_image = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images/google-wallet-co.png";

								$failure_return_url = site_url ("/?s2member_pro_google_return=0");
								$success_return_url = site_url ("/?s2member_pro_google_return=1");
								if($attr["success"]) // A custom return URL on success? If so, let's attach that now.
									$success_return_url = add_query_arg ("s2member_pro_google_return_success", rawurlencode ($attr["success"]), $success_return_url);

								$code = trim (c_ws_plugin__s2member_utilities::evl (file_get_contents (dirname (dirname (dirname (dirname (__FILE__)))) . "/templates/buttons/google-ccaps-checkout-button.php")));
								$code = preg_replace ("/%%images%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images")), $code);
								$code = preg_replace ("/%%wpurl%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr (site_url ())), $code);

								$attr["level_ccaps_eotper"] = ($attr["rt"] !== "L") ? $attr["level"] . ":" . $attr["ccaps"] . ":" . $attr["rp"] . " " . $attr["rt"] : $attr["level"] . ":" . $attr["ccaps"];
								$attr["level_ccaps_eotper"] = rtrim ($attr["level_ccaps_eotper"], ":"); // Right-trim separators from this string so we don't have trailing colons.

								$jwt["iss"] = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"];
								$jwt["aud"] = "Google"; $jwt["typ"] = "google/payments/inapp/item/v1";
								$jwt["exp"] = time() + 3600; $jwt["iat"] = time();
								$jwt["request"] = array("name" => substr($_SERVER["HTTP_HOST"], 0, 50), "description" => substr($attr["desc"], 0, 100),
																"price" => number_format($attr["ra"], 2, ".", ""), "currencyCode" => $attr["cc"],
																"sellerData" => json_encode(
																	array("s2_txn_id" => $attr["uniqid"],
																			"s2_custom" => $attr["custom"],
																			"s2_item_number" => $attr["level_ccaps_eotper"],
																			"s2_customer_ip" => $_SERVER["REMOTE_ADDR"],
																			"s2_referencing" => $attr["referencing"])));

								$code = preg_replace ("/%%jwt%%/",JWT::encode($jwt, $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_key"]), $code);
								$code = preg_replace (array("/%%success%%/", "/%%failure%%/"), array($success_return_url, $failure_return_url), $code);

								$code = $_code = ($attr["image"] && $attr["image"] !== "default") ? preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($attr["image"])) . '"', $code) : preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($default_image)) . '"', $code);
							}
						else if ($attr["rr"] === "BN" || (!$attr["tp"] && !$attr["rr"])) // Buy Now.
							{
								$default_image = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images/google-wallet-co.png";

								$failure_return_url = site_url ("/?s2member_pro_google_return=0");
								$success_return_url = site_url ("/?s2member_pro_google_return=1");
								if($attr["success"]) // A custom return URL on success? If so, let's attach that now.
									$success_return_url = add_query_arg ("s2member_pro_google_return_success", rawurlencode ($attr["success"]), $success_return_url);

								$code = trim (c_ws_plugin__s2member_utilities::evl (file_get_contents (dirname (dirname (dirname (dirname (__FILE__)))) . "/templates/buttons/google-ccaps-checkout-button.php")));
								$code = preg_replace ("/%%images%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images")), $code);
								$code = preg_replace ("/%%wpurl%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr (site_url ())), $code);

								$attr["desc"] = (!$attr["desc"]) ? $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["level" . $attr["level"] . "_label"] : $attr["desc"];

								$attr["level_ccaps_eotper"] = ($attr["rt"] !== "L") ? $attr["level"] . ":" . $attr["ccaps"] . ":" . $attr["rp"] . " " . $attr["rt"] : $attr["level"] . ":" . $attr["ccaps"];
								$attr["level_ccaps_eotper"] = rtrim ($attr["level_ccaps_eotper"], ":"); // Right-trim separators from this string so we don't have trailing colons.

								$jwt["iss"] = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"];
								$jwt["aud"] = "Google"; $jwt["typ"] = "google/payments/inapp/item/v1";
								$jwt["exp"] = time() + 3600; $jwt["iat"] = time();
								$jwt["request"] = array("name" => substr($_SERVER["HTTP_HOST"], 0, 50), "description" => substr($attr["desc"], 0, 100),
																"price" => number_format($attr["ra"], 2, ".", ""), "currencyCode" => $attr["cc"],
																"sellerData" => json_encode(
																	array("s2_txn_id" => $attr["uniqid"],
																			"s2_custom" => $attr["custom"],
																			"s2_item_number" => $attr["level_ccaps_eotper"],
																			"s2_customer_ip" => $_SERVER["REMOTE_ADDR"],
																			"s2_referencing" => $attr["referencing"])));

								$code = preg_replace ("/%%jwt%%/",JWT::encode($jwt, $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_key"]), $code);
								$code = preg_replace (array("/%%success%%/", "/%%failure%%/"), array($success_return_url, $failure_return_url), $code);

								$code = $_code = ($attr["image"] && $attr["image"] !== "default") ? preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($attr["image"])) . '"', $code) : preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($default_image)) . '"', $code);
							}
						else // Otherwise, we'll process this Button normally, using Membership routines.
							{
								$default_image = $GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images/google-wallet-co.png";

								$failure_return_url = site_url ("/?s2member_pro_google_return=0");
								$success_return_url = site_url ("/?s2member_pro_google_return=1");
								if($attr["success"]) // A custom return URL on success? If so, let's attach that now.
									$success_return_url = add_query_arg ("s2member_pro_google_return_success", rawurlencode ($attr["success"]), $success_return_url);

								$code = trim (c_ws_plugin__s2member_utilities::evl (file_get_contents (dirname (dirname (dirname (dirname (__FILE__)))) . "/templates/buttons/google-checkout-button.php")));
								$code = preg_replace ("/%%images%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($GLOBALS["WS_PLUGIN__"]["s2member_pro"]["c"]["dir_url"] . "/images")), $code);
								$code = preg_replace ("/%%wpurl%%/", c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr (site_url ())), $code);

								$attr["desc"] = (!$attr["desc"]) ? $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["level" . $attr["level"] . "_label"] : $attr["desc"];

								$attr["level_ccaps_eotper"] = $attr["level"] . ":" . $attr["ccaps"]; // Actual Subscriptions will always end on their own.
								$attr["level_ccaps_eotper"] = rtrim ($attr["level_ccaps_eotper"], ":"); // Clean any trailing separators from this string.

								$attr["periodicity"] = c_ws_plugin__s2member_pro_google_utilities::google_periodicity ($attr["rp"] . " " . $attr["rt"]);

								if ($attr["tp"]) // An actual Subscription that includes a Trial Period; which MIGHT also be recurring.
									{
										$attr["start_time"] = c_ws_plugin__s2member_pro_google_utilities::google_start_time ($attr["tp"] . " " . $attr["tt"]);

										$jwt["iss"] = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"];
										$jwt["aud"] = "Google"; $jwt["typ"] = "google/payments/inapp/subscription/v1";
										$jwt["exp"] = time() + 3600; $jwt["iat"] = time();
										$jwt["request"] = array("name" => substr($_SERVER["HTTP_HOST"], 0, 50),
								                        "description" => substr($attr["desc"], 0, 100),
																"initialPayment" =>
																	array("price" => number_format($attr["ta"], 2, ".", ""), "currencyCode" => $attr["cc"],
																			"paymentType" => (($attr["ta"] > 0) ? "prorated" : "free_trial")),
																"recurrence" =>
																	array("price" => number_format($attr["ra"], 2, ".", ""), "currencyCode" => $attr["cc"],
																			"startTime" => $attr["start_time"], "frequency" => $attr["periodicity"],
																			"numRecurrences" => ((!$attr["rr"]) ? 1 : (($attr["rrt"]) ? $attr["rrt"] :  NULL))),
																"sellerData" => json_encode(
																	array("s2_subscr_id" => $attr["uniqid"],
																			"s2_custom" => $attr["custom"],
																			"s2_item_number" => $attr["level_ccaps_eotper"],
																			"s2_period1" => $attr["tp"] . " " . $attr["tt"],
																			"s2_period3" => $attr["rp"] . " " . $attr["rt"],
																			"s2_recurring" => $attr["rr"],
																			"s2_customer_ip" => $_SERVER["REMOTE_ADDR"],
																			"s2_referencing" => $attr["referencing"])));
									}
								else if (!$attr["tp"] && $attr["rr"]) /* This is a Subscription w/o a Trial Period; and it IS associated with multiple recurring charges.
											This should ALWAYS be associated with recurring charges, because of the "BN" check above that includes (!$attr["tp"] && !$attr["rr"]).
											In other words, we should never have a Subscription w/o a Trial Period, AND no recurring charges. That would make no sense. */
									{
										$attr["start_time"] = c_ws_plugin__s2member_pro_google_utilities::google_start_time ($attr["rp"] . " " . $attr["rt"]);

										$jwt["iss"] = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_id"];
										$jwt["aud"] = "Google"; $jwt["typ"] = "google/payments/inapp/subscription/v1";
										$jwt["exp"] = time() + 3600; $jwt["iat"] = time();
										$jwt["request"] = array("name" => substr($_SERVER["HTTP_HOST"], 0, 50),
								                        "description" => substr($attr["desc"], 0, 100),
																"initialPayment" =>
																	array("price" => number_format($attr["ra"], 2, ".", ""), "currencyCode" => $attr["cc"],
																			"paymentType" => "prorated" /* No choice in the matter; always prorated by Google. */),
																"recurrence" =>
																	array("price" => number_format($attr["ra"], 2, ".", ""), "currencyCode" => $attr["cc"],
																			"startTime" => $attr["start_time"], "frequency" => $attr["periodicity"],
																			"numRecurrences" => ((!$attr["rr"]) ? 1 : (($attr["rrt"]) ? $attr["rrt"] :  NULL))),
																"sellerData" => json_encode(
																	array("s2_subscr_id" => $attr["uniqid"],
																			"s2_custom" => $attr["custom"],
																			"s2_item_number" => $attr["level_ccaps_eotper"],
																			"s2_period1" => "0 D", // There is no trial period in this case.
																			"s2_period3" => $attr["rp"] . " " . $attr["rt"],
																			"s2_recurring" => $attr["rr"],
																			"s2_customer_ip" => $_SERVER["REMOTE_ADDR"],
																			"s2_referencing" => $attr["referencing"])));
									}
								$code = preg_replace ("/%%jwt%%/",JWT::encode($jwt, $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_google_merchant_key"]), $code);
								$code = preg_replace (array("/%%success%%/", "/%%failure%%/"), array($success_return_url, $failure_return_url), $code);

								$code = $_code = ($attr["image"] && $attr["image"] !== "default") ? preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($attr["image"])) . '"', $code) : preg_replace ('/ src\="(.*?)"/', ' src="' . c_ws_plugin__s2member_utils_strings::esc_ds (esc_attr ($default_image)) . '"', $code);
							}
						return /* Button. */ $code;
					}
			}
	}
?>