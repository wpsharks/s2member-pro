<?php
/**
* PayPal® utilities.
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
* @package s2Member\PayPal
* @since 1.5
*/
if(realpath(__FILE__) === realpath($_SERVER["SCRIPT_FILENAME"]))
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_paypal_utilities"))
	{
		/**
		* PayPal® utilities.
		*
		* @package s2Member\PayPal
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_paypal_utilities
			{
				/**
				* Calculates start date for a Recurring Payment Profile.
				*
				* @package s2Member\PayPal
				* @since 1.5
				*
				* @param str $period1 Optional. A "Period Term" combination. Defaults to `0 D`.
				* @param str $period3 Optional. A "Period Term" combination. Defaults to `0 D`.
				* @return int The start time, a Unix timestamp.
				*/
				public static function paypal_start_time($period1 = FALSE, $period3 = FALSE)
					{
						if(!($p1_time = 0) && ($period1 = trim(strtoupper($period1))))
							{
								list($num, $span) = preg_split("/ /", $period1, 2);

								$days = 0; // Days start at 0.

								if(is_numeric($num) && !is_numeric($span))
									{
										$days = ($span === "D") ? 1 : $days;
										$days = ($span === "W") ? 7 : $days;
										$days = ($span === "M") ? 30 : $days;
										$days = ($span === "Y") ? 365 : $days;
									}

								$p1_days = (int)$num * (int)$days;
								$p1_time = $p1_days * 86400;
							}

						if(!($p3_time = 0) && ($period3 = trim(strtoupper($period3))))
							{
								list($num, $span) = preg_split("/ /", $period3, 2);

								$days = 0; // Days start at 0.

								if(is_numeric($num) && !is_numeric($span))
									{
										$days = ($span === "D") ? 1 : $days;
										$days = ($span === "W") ? 7 : $days;
										$days = ($span === "M") ? 30 : $days;
										$days = ($span === "Y") ? 365 : $days;
									}

								$p3_days = (int)$num * (int)$days;
								$p3_time = $p3_days * 86400;
							}

						$start_time = strtotime("now") + $p1_time + $p3_time;

						$start_time = ($start_time <= 0) ? strtotime("now") : $start_time;

						$start_time = $start_time + 43200; // + 12 hours.

						return $start_time;
					}
				/**
				* Determines whether or not Tax may apply.
				*
				* @package s2Member\PayPal
				* @since 1.5
				*
				* @return bool True if Tax may apply, else false.
				*/
				public static function paypal_tax_may_apply()
					{
						if((float)$GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_default_tax"] > 0)
							return true;

						else if($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_tax_rates"])
							return true;

						return false;
					}
				/**
				* Gets a Payflow® recurring profile.
				*
				* @package s2Member\PayPal
				* @since 110531
				*
				* @param string $subscr_id A paid subscription ID (aka: Recurring Profile ID).
				* @return array|false Array of profile details, else false.
				*/
				public static function payflow_get_profile($subscr_id = FALSE)
					{
						$payflow["TRXTYPE"] = "R";
						$payflow["ACTION"] = "I";
						$payflow["TENDER"] = "C";
						$payflow["ORIGPROFILEID"] = $subscr_id;

						if(($profile = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($profile["__error"]))
							return $profile;

						$payflow["TENDER"] = "P";
						if(($profile = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($profile["__error"]))
							return $profile;

						return false;
					}
				/**
				* Cancels a Payflow® recurring profile.
				*
				* @package s2Member\PayPal
				* @since 110531
				*
				* @param string $subscr_id A paid subscription ID (aka: Recurring Profile ID).
				* @param string $baid A Billing Agreement ID (aka: BAID).
				* @return boolean True if the profile was cancelled, else false.
				*/
				public static function payflow_cancel_profile($subscr_id = FALSE, $baid = FALSE)
					{
						$payflow["TRXTYPE"] = "R";
						$payflow["ACTION"] = "C";
						$payflow["TENDER"] = "C";
						$payflow["ORIGPROFILEID"] = $subscr_id;

						if(($cancellation = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($cancellation["__error"]))
							if(!$baid || c_ws_plugin__s2member_paypal_utilities::payflow_cancel_billing_agreement($baid))
								return true;

						$payflow["TENDER"] = "P";
						if(($cancellation = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($cancellation["__error"]))
							if(!$baid || c_ws_plugin__s2member_paypal_utilities::payflow_cancel_billing_agreement($baid))
								return true;

						return false;
					}
				/**
				* Cancels a Payflow® Billing Agreement.
				*
				* @package s2Member\PayPal
				* @since 130510
				*
				* @param string $baid A Billing Agreement ID (aka: BAID).
				* @return boolean True if the agreement was cancelled, else false.
				*/
				public static function payflow_cancel_billing_agreement($baid = FALSE)
					{
						$payflow["ACTION"] = "U";
						$payflow["TENDER"] = "P";
						$payflow["BAID"] = $baid;
						$payflow["BA_STATUS"] = "cancel";

						if(($cancellation = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($cancellation["__error"]))
							return true;

						return false;
					}
				/**
				* Handles currency conversions for Maestro/Solo cards.
				*
				* PayPal® requires Maestro/Solo to be charged in GBP. So if a site owner is using
				* another currency *(i.e. something NOT in GBP)*, we have to convert all of the charge amounts dynamically.
				*
				* Coupon Codes should always be applied before this conversion takes place.
				* That way a site owner's configuration remains adequate.
				*
				* Tax rates should be applied after this conversion takes place.
				*
				* @package s2Member\PayPal
				* @since 110531
				*
				* @param array $attr An array of PayPal® Pro Form Attributes.
				* @param str $card_type The Card Type *(i.e. Billing Method)* selected.
				* @return array The same array of Pro Form Attributes, with possible currency conversions.
				*/
				public static function paypal_maestro_solo_2gbp($attr = FALSE, $card_type = FALSE)
					{
						if(is_array($attr) && is_string($card_type) && in_array($card_type, array("Maestro", "Solo")))
							if(!empty($attr["cc"]) && strcasecmp($attr["cc"], "GBP") !== 0 && is_numeric($attr["ta"]) && is_numeric($attr["ra"]))
								if(($attr["ta"] <= 0 && is_numeric($c_ta = "0")) || is_numeric($c_ta = c_ws_plugin__s2member_utils_cur::convert($attr["ta"], $attr["cc"], "GBP")))
									if(($attr["ra"] <= 0 && is_numeric($c_ra = "0")) || is_numeric($c_ra = c_ws_plugin__s2member_utils_cur::convert($attr["ra"], $attr["cc"], "GBP")))
										$attr = array_merge($attr, array("cc" => "GBP", "ta" => $c_ta, "ra" => $c_ra));

						return $attr; // Return array of Attributes.
					}
				/**
				* Handles the return of Tax for Pro Forms, via AJAX; through a JSON object.
				*
				* @package s2Member\PayPal
				* @since 1.5
				*
				* @return null Or exits script execution after returning data for AJAX caller.
				*
				* @todo Check the use of ``strip_tags()`` in this routine?
				* @todo Continue optimizing this routine with ``empty()`` and ``isset()``.
				* @todo Candidate for the use of ``ifsetor()``?
				*/
				public static function paypal_ajax_tax()
					{
						if(!empty($_POST["ws_plugin__s2member_pro_paypal_ajax_tax"]) && ($nonce = $_POST["ws_plugin__s2member_pro_paypal_ajax_tax"]) && (wp_verify_nonce($nonce, "ws-plugin--s2member-pro-paypal-ajax-tax") || c_ws_plugin__s2member_utils_encryption::decrypt($nonce) === "ws-plugin--s2member-pro-paypal-ajax-tax"))
							/* A wp_verify_nonce() won't always work here, because s2member-pro-min.js must be cacheable. The output from wp_create_nonce() would go stale.
									So instead, s2member-pro-min.js should use c_ws_plugin__s2member_utils_encryption::encrypt() as an alternate form of nonce. */
							{
								status_header(200); // Send a 200 OK status header.
								header("Content-Type: text/plain; charset=UTF-8"); // Content-Type text/plain with UTF-8.
								while (@ob_end_clean ()); // Clean any existing output buffers.

								if(!empty($_POST["ws_plugin__s2member_pro_paypal_ajax_tax_vars"]) && is_array($_p_tax_vars = c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($_POST["ws_plugin__s2member_pro_paypal_ajax_tax_vars"]))))
									{
										if(is_array($attr = (!empty($_p_tax_vars["attr"])) ? unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($_p_tax_vars["attr"])) : false))
											{
												$attr = (!empty($attr["coupon"])) ? c_ws_plugin__s2member_pro_paypal_utilities::paypal_apply_coupon($attr, $attr["coupon"]) : $attr;

												$trial = ($attr["rr"] !== "BN" && $attr["tp"]) ? true : false; // Is there a trial?
												$sub_total_today = ($trial) ? $attr["ta"] : $attr["ra"]; // What is the sub-total today?

												$state = strip_tags($_p_tax_vars["state"]);
												$country = strip_tags($_p_tax_vars["country"]);
												$zip = strip_tags($_p_tax_vars["zip"]);
												$currency = $attr["cc"]; // Currency.
												$desc = $attr["desc"]; // Description.

												/* Trial is `null` in this function call. We only need to return what it costs today.
												However, we do tag on a "trial" element in the array so the ajax routine will know about this. */
												$a = c_ws_plugin__s2member_pro_paypal_utilities::paypal_cost(null, $sub_total_today, $state, $country, $zip, $currency, $desc);
												echo json_encode(array("trial" => $trial, "sub_total" => $a["sub_total"], "tax" => $a["tax"], "tax_per" => $a["tax_per"], "total" => $a["total"], "cur" => $a["cur"], "cur_symbol" => $a["cur_symbol"], "desc" => $a["desc"]));
											}
									}

								exit(); // Clean exit.
							}
					}
				/**
				* Handles all cost calculations for PayPal®.
				*
				* Returns an associative array with a possible Percentage Rate, along with the calculated Tax Amount.
				* Tax calculations are based on State/Province, Country, and/or Zip Code.
				* Updated to support multiple data fields in it's return value.
				*
				* @package s2Member\PayPal
				* @since 1.5
				*
				* @param int|str $trial_sub_total Optional. A numeric Amount/cost of a possible Initial/Trial being offered.
				* @param int|str $sub_total Optional. A numeric Amount/cost of the purchase and/or Regular Period.
				* @param str $state Optional. The State/Province where the Customer is billed.
				* @param str $country Optional. The Country where the Customer is billed.
				* @param int|str $zip Optional. The Postal/Zip Code where the Customer is billed.
				* @param str $currency Optional. Expects a 3 character Currency Code.
				* @param str $desc Optional. Description of the sale.
				* @return array Array of calculations.
				*
				* @todo Add support for `Zip + 4` syntax?
				*/
				public static function paypal_cost($trial_sub_total = FALSE, $sub_total = FALSE, $state = FALSE, $country = FALSE, $zip = FALSE, $currency = FALSE, $desc = FALSE)
					{
						$state = strtoupper(c_ws_plugin__s2member_pro_utilities::full_state($state, ($country = strtoupper($country))));
						$rates = apply_filters("ws_plugin__s2member_pro_tax_rates_before_cost_calculation", strtoupper($GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_tax_rates"]), get_defined_vars());
						$default = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_default_tax"];
						$ps = _x("%", "s2member-front percentage-symbol", "s2member");

						foreach(array("trial_sub_total" => $trial_sub_total, "sub_total" => $sub_total) as $this_key => $this_sub_total)
							{
								unset($_default, $this_tax, $this_tax_per, $this_total, $configured_rates, $configured_rate, $location, $rate, $m);

								if(is_numeric($this_sub_total) && $this_sub_total > 0) // Must have a valid Sub-Total.
									{
										if(preg_match("/%$/", $default)) // Percentage-based.
											{
												if(($_default = (float)$default) > 0)
													{
														$this_tax = round(($this_sub_total / 100) * $_default, 2);
														$this_tax_per = $_default.$ps;
													}
												else // Else the Tax is 0.00.
													{
														$this_tax = 0.00;
														$this_tax_per = $_default.$ps;
													}
											}
										else if(($_default = (float)$default) > 0)
											{
												$this_tax = round($_default, 2);
												$this_tax_per = ""; // Flat.
											}
										else // Else the Tax is 0.00.
											{
												$this_tax = 0.00; // No Tax.
												$this_tax_per = ""; // Flat rate.
											}

										if(strlen($country) === 2) // Must have a valid country.
											{
												foreach(preg_split("/[\r\n\t]+/", $rates) as $rate)
													{
														if($rate = trim($rate)) // Do NOT process empty lines.
															{
																list($location, $rate) = preg_split("/\=/", $rate, 2);
																$location = trim($location);
																$rate = trim($rate);

																if($location === $country)
																	$configured_rates[1] = $rate;

																else if($state && $location === $state."/".$country)
																	$configured_rates[2] = $rate;

																else if($state && preg_match("/^([A-Z]{2})\/(".preg_quote($country, "/").")$/", $location, $m) && strtoupper(c_ws_plugin__s2member_pro_utilities::full_state($m[1], $m[2]))."/".$m[2] === $state."/".$country)
																	$configured_rates[2] = $rate;

																else if($zip && preg_match("/^([0-9]+)-([0-9]+)\/(".preg_quote($country, "/").")$/", $location, $m) && $zip >= $m[1] && $zip <= $m[2] && $country === $m[3])
																	$configured_rates[3] = $rate;

																else if($zip && $location === $zip."/".$country)
																	$configured_rates[4] = $rate;
															}
													}

												if(is_array($configured_rates) && !empty($configured_rates))
													{
														krsort($configured_rates);
														$configured_rate = array_shift($configured_rates);

														if(preg_match("/%$/", $configured_rate)) // Percentage.
															{
																if(($configured_rate = (float)$configured_rate) > 0)
																	{
																		$this_tax = round(($this_sub_total / 100) * $configured_rate, 2);
																		$this_tax_per = $configured_rate.$ps;
																	}
																else // Else the Tax is 0.00.
																	{
																		$this_tax = 0.00; // No Tax.
																		$this_tax_per = $configured_rate.$ps;
																	}
															}
														else if(($configured_rate = (float)$configured_rate) > 0)
															{
																$this_tax = round($configured_rate, 2);
																$this_tax_per = ""; // Flat rate.
															}
														else // Else the Tax is 0.00.
															{
																$this_tax = 0.00; // No Tax.
																$this_tax_per = ""; // Flat rate.
															}
													}
											}

										$this_total = $this_sub_total + $this_tax;
									}
								else // Else the Tax is 0.00.
									{
										$this_tax = 0.00; // No Tax.
										$this_tax_per = ""; // Flat rate.
										$this_sub_total = 0.00; // 0.00.
										$this_total = 0.00; // 0.00.
									}

								if($this_key === "trial_sub_total")
									{
										$trial_tax = $this_tax;
										$trial_tax_per = $this_tax_per;
										$trial_sub_total = $this_sub_total;
										$trial_total = $this_total;
									}
								else if($this_key === "sub_total")
									{
										$tax = $this_tax;
										$tax_per = $this_tax_per;
										$sub_total = $this_sub_total;
										$total = $this_total;
									}
							}

						return array("trial_sub_total" => number_format($trial_sub_total, 2, ".", ""), "sub_total" => number_format($sub_total, 2, ".", ""), "trial_tax" => number_format($trial_tax, 2, ".", ""), "tax" => number_format($tax, 2, ".", ""), "trial_tax_per" => $trial_tax_per, "tax_per" => $tax_per, "trial_total" => number_format($trial_total, 2, ".", ""), "total" => number_format($total, 2, ".", ""), "cur" => $currency, "cur_symbol" => c_ws_plugin__s2member_utils_cur::symbol($currency), "desc" => $desc);
					}
				/**
				* Checks to see if a Coupon Code was supplied, and if so; what does it provide?
				*
				* @package s2Member\PayPal
				* @since 1.5
				*
				* @param array $attr An array of Pro Form Attributes.
				* @param str $coupon_code Optional. A possible Coupon Code supplied by the Customer.
				* @param str $return Optional. Return type. One of `response|attr`. Defaults to `attr`.
				* @param array $process Optional. An array of additional processing routines to run here.
				* 	One or more of these values: `affiliates-1px-response|affiliates-silent-post|notifications`.
				* @return array|str Original array, with prices and description modified when/if a Coupon Code is accepted.
				* 	Or, if ``$return === "response"``, return a string response, indicating status.
				*
				* @todo See if it's possible to simplify this routine.
				* @todo Add support for tracking Coupon Code usage.
				* @todo Add support for a fixed number of uses.
				*/
				public static function paypal_apply_coupon($attr = FALSE, $coupon_code = FALSE, $return = FALSE, $process = FALSE)
					{
						if(($coupon_code = trim(strtolower($coupon_code))) || ($coupon_code = trim(strtolower($attr["coupon"]))))
							if($attr["accept_coupons"] && $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_coupon_codes"])
								{
									$cs = c_ws_plugin__s2member_utils_cur::symbol($attr["cc"]);
									$tx = (c_ws_plugin__s2member_pro_paypal_utilities::paypal_tax_may_apply()) ? _x(" + tax", "s2member-front", "s2member") : "";
									$ps = _x("%", "s2member-front percentage-symbol", "s2member");

									if(strlen($affiliate_suffix_chars = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_affiliate_coupon_code_suffix_chars"]))
										if(preg_match("/^(.+?)".preg_quote($affiliate_suffix_chars, "/")."([0-9]+)$/i", $coupon_code, $m))
											($full_coupon_code = $m[0]).($coupon_code = $m[1]).($affiliate_id = $m[2]);
									unset /* Just a little housekeeping here. */($affiliate_suffix_chars, $m);

									foreach(c_ws_plugin__s2member_utils_strings::trim_deep(preg_split("/[\r\n\t]+/", $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_coupon_codes"])) as $_line)
										{
											if(($_line = trim($_line, " \r\n\t\0\x0B|")) && is_array($_coupon = preg_split("/\|/", $_line)))
												{
													$coupon["code"] = (!empty($_coupon[0])) ? trim(strtolower($_coupon[0])) : "";

													$coupon["percentage"] = (!empty($_coupon[1]) && preg_match("/%/", $_coupon[1])) ? (float)$_coupon[1] : 0;
													$coupon["flat-rate"] = (!empty($_coupon[1]) && !preg_match("/%/", $_coupon[1])) ? (float)$_coupon[1] : 0;

													$coupon["expired"] = (!empty($_coupon[2]) && /* Expired? */ strtotime($_coupon[2]) < time()) ? $_coupon[2] : false;

													$coupon["directive"] = (!empty($_coupon[3]) && ($_coupon[3] = strtolower($_coupon[3]))) ? preg_replace("/_/", "-", $_coupon[3]) : "all";
													$coupon["directive"] = (preg_match("/^(ta-only|ra-only|all)$/", $coupon["directive"])) ? $coupon["directive"] : "all";

													$coupon["singulars"] = (!empty($_coupon[4]) && ($_coupon[4] = strtolower($_coupon[4])) && $_coupon[4] !== "all") ? $_coupon[4] : "all";
													$coupon["singulars"] = ($coupon["singulars"] !== "all") ? preg_split("/[\r\n\t\s;,]+/", trim(preg_replace("/[^0-9,]/", "", $coupon["singulars"]), ",")) : array("all");

													unset /* Just a little housekeeping here. Unset these temporary variables. */($_line, $_coupon);

													if($coupon_code === $coupon["code"] && /* And it's NOT yet expired, or lasts forever? */ !$coupon["expired"])
														{
															if($coupon["singulars"] === array("all")|| in_array($attr["singular"], $coupon["singulars"]))
																{
																	$coupon_accepted = /* Yes, this Coupon Code has been accepted. */ true;

																	if($coupon["flat-rate"]) // If it's a flat-rate Coupon.
																		{
																			if(($coupon["directive"] === "ra-only" || $coupon["directive"] === "all") && $attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$ta = number_format($attr["ta"], 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$ra = number_format($attr["ra"] - $coupon["flat-rate"], 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s)", "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.$ra.$tx);
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.$ra.$tx);
																				}
																			else if($coupon["directive"] === "ta-only" && $attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$ta = number_format($attr["ta"] - $coupon["flat-rate"], 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$ra = number_format($attr["ra"], 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s, then %s)", "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																				}
																			else if($coupon["directive"] === "ra-only" && $attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$ta = number_format($attr["ta"], 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$ra = number_format($attr["ra"] - $coupon["flat-rate"], 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s, then %s)", "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																				}
																			else if($coupon["directive"] === "all" && $attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$ta = number_format($attr["ta"] - $coupon["flat-rate"], 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$ra = number_format($attr["ra"] - $coupon["flat-rate"], 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s, then %s)", "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																				}
																			else if($coupon["directive"] === "ra-only" && !$attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$ta = number_format($attr["ta"], 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$ra = number_format($attr["ra"] - $coupon["flat-rate"], 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s)", "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]).$tx);
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]).$tx);
																				}
																			else if($coupon["directive"] === "all" && !$attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$ta = number_format($attr["ta"] - $coupon["flat-rate"], 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$ra = number_format($attr["ra"] - $coupon["flat-rate"], 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s)", "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]).$tx);
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', "s2member-front", "s2member"), $cs.number_format($coupon["flat-rate"], 2, ".", ""), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]).$tx);
																				}

																			else // Otherwise, we need a default response to display.
																				$response = _x('<div>Sorry, your Coupon is not applicable.</div>', "s2member-front", "s2member");
																		}

																	else if($coupon["percentage"]) // Else if it's a percentage.
																		{
																			if(($coupon["directive"] === "ra-only" || $coupon["directive"] === "all") && $attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$p = ($attr["ta"] / 100) * $coupon["percentage"];
																					$ta = number_format($attr["ta"], 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$p = ($attr["ra"] / 100) * $coupon["percentage"];
																					$ra = number_format($attr["ra"] - $p, 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s)", "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.$ra.$tx);
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.$ra.$tx);
																				}
																			else if($coupon["directive"] === "ta-only" && $attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$p = ($attr["ta"] / 100) * $coupon["percentage"];
																					$ta = number_format($attr["ta"] - $p, 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$p = ($attr["ra"] / 100) * $coupon["percentage"];
																					$ra = number_format($attr["ra"], 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s, then %s)", "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																				}
																			else if($coupon["directive"] === "ra-only" && $attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$p = ($attr["ta"] / 100) * $coupon["percentage"];
																					$ta = number_format($attr["ta"], 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$p = ($attr["ra"] / 100) * $coupon["percentage"];
																					$ra = number_format($attr["ra"] - $p, 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s, then %s)", "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																				}
																			else if($coupon["directive"] === "all" && $attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$p = ($attr["ta"] / 100) * $coupon["percentage"];
																					$ta = number_format($attr["ta"] - $p, 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$p = ($attr["ra"] / 100) * $coupon["percentage"];
																					$ra = number_format($attr["ra"] - $p, 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s, then %s)", "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr["tp"]." ".$attr["tt"]).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]));
																				}
																			else if($coupon["directive"] === "ra-only" && !$attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$p = ($attr["ta"] / 100) * $coupon["percentage"];
																					$ta = number_format($attr["ta"], 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$p = ($attr["ra"] / 100) * $coupon["percentage"];
																					$ra = number_format($attr["ra"] - $p, 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s)", "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]).$tx);
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]).$tx);
																				}
																			else if($coupon["directive"] === "all" && !$attr["tp"] && !$attr["sp"])
																				{
																					$coupon_applies = /* Applying. */ true;

																					$p = ($attr["ta"] / 100) * $coupon["percentage"];
																					$ta = number_format($attr["ta"] - $p, 2, ".", "");
																					$ta = ($ta >= 0.00) ? $ta : "0.00";

																					$p = ($attr["ra"] / 100) * $coupon["percentage"];
																					$ra = number_format($attr["ra"] - $p, 2, ".", "");
																					$ra = ($ra >= 0.00) ? $ra : "0.00";

																					$desc = sprintf(_x("COUPON %s off. (Now: %s)", "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]).$tx);
																					$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', "s2member-front", "s2member"), number_format($coupon["percentage"], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr["rp"]." ".$attr["rt"], $attr["rr"]).$tx);
																				}

																			else // Otherwise, we need a default response to display.
																				$response = _x('<div>Sorry, your Coupon is not applicable.</div>', "s2member-front", "s2member");
																		}

																	else // Else there was no discount applied at all.
																		$response = sprintf(_x('<div>Coupon: <strong>%s0.00 off</strong>.</div>', "s2member-front", "s2member"), $cs);
																}

															else // Otherwise, we need a response that indicates not applicable for this purchase.
																$response = _x('<div>Sorry, your Coupon cannot be applied to this particular purchase.</div>', "s2member-front", "s2member");
														}

													else if($coupon_code === $coupon["code"] && $coupon["expired"])
														$response = sprintf(_x('<div>Sorry, your Coupon <strong>expired</strong>: <em>%s</em>.</div>', "s2member-front", "s2member"), $coupon["expired"]);
												}
										}

									if(isset($coupon_applies, $desc) && $coupon_applies /* Need to modify the description dynamically? */)
										// translators: `%1$s` is new price/description, after coupon applied. `%2$s` is original description.
										$attr["desc"] = sprintf(_x('%1$s ~ ORIGINALLY: %2$s', "s2member-front", "s2member"), $desc, $attr["desc"]);

									$attr["ta"] = (isset($coupon_applies, $ta) && $coupon_applies) ? /* Do we have a new Trial Amount? */ $ta : $attr["ta"];
									$attr["ra"] = (isset($coupon_applies, $ra) && $coupon_applies) ? /* A new Regular Amount? */ $ra : $attr["ra"];

									if(is_array($process) && /* Processing affiliates? */ (in_array("affiliates-silent-post", $process) || in_array("affiliates-1px-response", $process)))
										if(isset($coupon_applies) && $coupon_applies && /* Now, is this an Affiliate Coupon Code? Contains an affiliate ID? */ !empty($affiliate_id))
											if(empty($_COOKIE["idev"]) /* Special consideration here. iDevAffiliate® must NOT have already tracked this customer. */)
												if(($_urls = $GLOBALS["WS_PLUGIN__"]["s2member"]["o"]["pro_affiliate_coupon_code_tracking_urls"]))

													foreach(preg_split("/[\r\n\t]+/", $_urls) as $_url /* Notify each of the URLs. */)

														if(($_url = preg_replace("/%%full_coupon_code%%/i", c_ws_plugin__s2member_utils_strings::esc_ds(urlencode($full_coupon_code)), $_url)))
															if(($_url = preg_replace("/%%coupon_code%%/i", c_ws_plugin__s2member_utils_strings::esc_ds(urlencode($coupon_code)), $_url)))
																if(($_url = preg_replace("/%%(?:coupon_affiliate_id|affiliate_id)%%/i", c_ws_plugin__s2member_utils_strings::esc_ds(urlencode($affiliate_id)), $_url)))
																	if(($_url = preg_replace("/%%user_ip%%/i", c_ws_plugin__s2member_utils_strings::esc_ds(urlencode($_SERVER["REMOTE_ADDR"])), $_url)))
																		{
																			if(($_url = trim(preg_replace("/%%(.+?)%%/i", "", $_url))) /* Cleanup any remaining Replacement Codes. */)

																				if(!($_r = 0) && ($_url = preg_replace("/^silent-php\|/i", "", $_url, 1, $_r)) && $_r && in_array /* Processing? */("affiliates-silent-post", $process))
																					c_ws_plugin__s2member_utils_urls::remote /* Post silently via PHP. Relies on IP tracking. */($_url, false, array("blocking" => false));

																				else if(!($_r = 0) && ($_url = preg_replace("/^img-1px\|/i", "", $_url, 1, $_r)) && $_r && in_array("affiliates-1px-response", $process))
																					if( /* Now, we MUST also have a ``$response``, and MUST be returning ``$response``. */!empty($response) && $return === "response")
																						$response .= "\n".'<img src="'.esc_attr($_url).'" style="width:0; height:0; border:0;" alt="" />';
																		}
									unset /* Just a little housekeeping here. Unset these variables. */($_urls, $_url, $_r);

									if(empty($response)) // Is ``$response`` NOT set by now? If it's not, we need a default ``$response``.
										$response = _x('<div>Sorry, your Coupon is N/A, invalid or expired.</div>', "s2member-front", "s2member");
								}
							else // Otherwise, we need a default response to display.
								$response = _x('<div>Sorry, your Coupon is N/A, invalid or expired.</div>', "s2member-front", "s2member");

						$attr["_coupon_applies"] = (isset($coupon_applies) && $coupon_applies) ? /* Coupon applies? */ "1" : "0";
						$attr["_coupon_code"] = (isset($coupon_applies) && $coupon_applies) ? /* Coupon applies? */ $coupon_code : "";
						$attr["_full_coupon_code"] = (isset($coupon_applies) && $coupon_applies && !empty($full_coupon_code)) ? $full_coupon_code : ((isset($coupon_applies) && $coupon_applies) ? $coupon_code : "");
						$attr["_coupon_affiliate_id"] = (isset($coupon_applies) && $coupon_applies && !empty($affiliate_id) && empty($_COOKIE["idev"])) ? $affiliate_id : "";

						return ( /* Returning ``$response``? */$return === "response") ? $response : $attr;
					}
			}
	}
?>