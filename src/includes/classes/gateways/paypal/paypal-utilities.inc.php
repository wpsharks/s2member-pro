<?php
// @codingStandardsIgnoreFile
/**
 * PayPal utilities.
 *
 * Copyright: © 2009-2011
 * {@link http://websharks-inc.com/ WebSharks, Inc.}
 * (coded in the USA)
 *
 * This WordPress plugin (s2Member Pro) is comprised of two parts:
 *
 * o (1) Its PHP code is licensed under the GPL license, as is WordPress.
 *   You should have received a copy of the GNU General Public License,
 *   along with this software. In the main directory, see: /licensing/
 *   If not, see: {@link http://www.gnu.org/licenses/}.
 *
 * o (2) All other parts of (s2Member Pro); including, but not limited to:
 *   the CSS code, some JavaScript code, images, and design;
 *   are licensed according to the license purchased.
 *   See: {@link http://s2member.com/prices/}
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
	exit('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_pro_paypal_utilities'))
{
	/**
	 * PayPal utilities.
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
		 * @param string $period1 Optional. A 'Period Term' combination. Defaults to `0 D`.
		 * @param string $period3 Optional. A 'Period Term' combination. Defaults to `0 D`.
		 *
		 * @return int The start time, a Unix timestamp.
		 */
		public static function paypal_start_time($period1 = '', $period3 = '')
		{
			if(!($p1_time = 0) && ($period1 = trim(strtoupper($period1))))
			{
				list($num, $span) = preg_split('/ /', $period1, 2);

				$days = 0; // Days start at 0.

				if(is_numeric($num) && !is_numeric($span))
				{
					$days = ($span === 'D') ? 1 : $days;
					$days = ($span === 'W') ? 7 : $days;
					$days = ($span === 'M') ? 30 : $days;
					$days = ($span === 'Y') ? 365 : $days;
				}
				$p1_days = (int)$num * (int)$days;
				$p1_time = $p1_days * 86400;
			}
			if(!($p3_time = 0) && ($period3 = trim(strtoupper($period3))))
			{
				list($num, $span) = preg_split('/ /', $period3, 2);

				$days = 0; // Days start at 0.

				if(is_numeric($num) && !is_numeric($span))
				{
					$days = ($span === 'D') ? 1 : $days;
					$days = ($span === 'W') ? 7 : $days;
					$days = ($span === 'M') ? 30 : $days;
					$days = ($span === 'Y') ? 365 : $days;
				}
				$p3_days = (int)$num * (int)$days;
				$p3_time = $p3_days * 86400;
			}
			$start_time = strtotime('now') + $p1_time + $p3_time;
			$start_time = ($start_time <= 0) ? strtotime('now') : $start_time;
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
			if((float)$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_default_tax'] > 0)
				return TRUE;

			if($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_tax_rates'])
				return TRUE;

			return FALSE;
		}

		/**
		 * Gets a Payflow recurring profile.
		 *
		 * @package s2Member\PayPal
		 * @since 110531
		 *
		 * @param string $subscr_id A paid subscription ID (aka: Recurring Profile ID).
		 *
		 * @return array|false Array of profile details, else false.
		 */
		public static function payflow_get_profile($subscr_id = '')
		{
			$payflow['TRXTYPE']       = 'R';
			$payflow['ACTION']        = 'I';
			$payflow['TENDER']        = 'C';
			$payflow['ORIGPROFILEID'] = $subscr_id;

			if(($profile = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($profile['__error']))
				return $profile;

			$payflow['TENDER'] = 'P';
			if(($profile = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($profile['__error']))
				return $profile;

			return FALSE;
		}

		/**
		 * Cancels a Payflow recurring profile.
		 *
		 * @package s2Member\PayPal
		 * @since 110531
		 *
		 * @param string $subscr_id A paid subscription ID (aka: Recurring Profile ID).
		 * @param string $baid A Billing Agreement ID (aka: BAID).
		 *
		 * @return boolean True if the profile was cancelled, else false.
		 */
		public static function payflow_cancel_profile($subscr_id = '', $baid = '')
		{
			$payflow['TRXTYPE']       = 'R';
			$payflow['ACTION']        = 'C';
			$payflow['TENDER']        = 'C';
			$payflow['ORIGPROFILEID'] = $subscr_id;

			if(($cancellation = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($cancellation['__error']))
				if(!$baid || c_ws_plugin__s2member_pro_paypal_utilities::payflow_cancel_billing_agreement($baid))
					return TRUE;

			$payflow['TENDER'] = 'P';
			if(($cancellation = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($cancellation['__error']))
				if(!$baid || c_ws_plugin__s2member_pro_paypal_utilities::payflow_cancel_billing_agreement($baid))
					return TRUE;

			return FALSE;
		}

		/**
		 * Cancels a Payflow Billing Agreement.
		 *
		 * @package s2Member\PayPal
		 * @since 130510
		 *
		 * @param string $baid A Billing Agreement ID (aka: BAID).
		 *
		 * @return boolean True if the agreement was cancelled, else false.
		 */
		public static function payflow_cancel_billing_agreement($baid = '')
		{
			$payflow['ACTION']    = 'U';
			$payflow['TENDER']    = 'P';
			$payflow['BAID']      = $baid;
			$payflow['BA_STATUS'] = 'cancel';

			if(($cancellation = c_ws_plugin__s2member_paypal_utilities::paypal_payflow_api_response($payflow)) && empty($cancellation['__error']))
				return TRUE;

			return FALSE;
		}

		/**
		 * Handles currency conversions for Maestro/Solo cards.
		 *
		 * PayPal requires Maestro/Solo to be charged in GBP. So if a site owner is using
		 * another currency *(i.e., something NOT in GBP)*, we have to convert all of the charge amounts dynamically.
		 *
		 * Coupon Codes should always be applied before this conversion takes place.
		 * That way a site owner's configuration remains adequate.
		 *
		 * Tax rates should be applied after this conversion takes place.
		 *
		 * @package s2Member\PayPal
		 * @since 110531
		 *
		 * @param array  $attr An array of PayPal Pro-Form Attributes.
		 * @param string $card_type The Card Type *(i.e., Billing Method)* selected.
		 *
		 * @return array The same array of Pro-Form Attributes, with possible currency conversions.
		 */
		public static function paypal_maestro_solo_2gbp($attr = array(), $card_type = '')
		{
			if(is_array($attr) && is_string($card_type) && in_array($card_type, array('Maestro', 'Solo')))
				if(!empty($attr['cc']) && strcasecmp($attr['cc'], 'GBP') !== 0 && is_numeric($attr['ta']) && is_numeric($attr['ra']))
					if(($attr['ta'] <= 0 && is_numeric($c_ta = '0')) || is_numeric($c_ta = c_ws_plugin__s2member_utils_cur::convert($attr['ta'], $attr['cc'], 'GBP')))
						if(($attr['ra'] <= 0 && is_numeric($c_ra = '0')) || is_numeric($c_ra = c_ws_plugin__s2member_utils_cur::convert($attr['ra'], $attr['cc'], 'GBP')))
							$attr = array_merge($attr, array('cc' => 'GBP', 'ta' => $c_ta, 'ra' => $c_ra));

			return $attr; // Return array of Attributes.
		}

		/**
		 * Handles the return of Tax for Pro-Forms, via AJAX; through a JSON object.
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
			if(!empty($_POST['ws_plugin__s2member_pro_paypal_ajax_tax']) && ($nonce = $_POST['ws_plugin__s2member_pro_paypal_ajax_tax']) && (wp_verify_nonce($nonce, 'ws-plugin--s2member-pro-paypal-ajax-tax') || c_ws_plugin__s2member_utils_encryption::decrypt($nonce) === 'ws-plugin--s2member-pro-paypal-ajax-tax'))
				/* A wp_verify_nonce() won't always work here, because s2member-pro.min.js must be cacheable. The output from wp_create_nonce() would go stale.
						So instead, s2member-pro.min.js should use c_ws_plugin__s2member_utils_encryption::encrypt() as an alternate form of nonce. */
			{
				status_header(200); // Send a 200 OK status header.
				header('Content-Type: text/plain; charset=UTF-8'); // Content-Type text/plain with UTF-8.
				while(@ob_end_clean()) ; // Clean any existing output buffers.

				if(!empty($_POST['ws_plugin__s2member_pro_paypal_ajax_tax_vars']) && is_array($_p_tax_vars = c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($_POST['ws_plugin__s2member_pro_paypal_ajax_tax_vars']))))
				{
					//260808 Safely unserialize the tax attributes.
					if(is_array($attr = (!empty($_p_tax_vars['attr'])) ? c_ws_plugin__s2member_utils_arrays::maybe_unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($_p_tax_vars['attr'])) : FALSE))
					{
						$attr = (!empty($attr['coupon'])) ? c_ws_plugin__s2member_pro_paypal_utilities::paypal_apply_coupon($attr, $attr['coupon']) : $attr;

						$trial           = ($attr['rr'] !== 'BN' && $attr['tp']) ? TRUE : FALSE; // Is there a trial?
						$sub_total_today = ($trial) ? $attr['ta'] : $attr['ra']; // What is the sub-total today?

						$state    = strip_tags($_p_tax_vars['state']);
						$country  = strip_tags($_p_tax_vars['country']);
						$zip      = strip_tags($_p_tax_vars['zip']);
						$currency = $attr['cc']; // Currency.
						$desc     = $attr['desc']; // Description.

						/* Trial is `null` in this function call. We only need to return what it costs today.
						However, we do tag on a 'trial' element in the array so the ajax routine will know about this. */
						$a = c_ws_plugin__s2member_pro_paypal_utilities::paypal_cost(NULL, $sub_total_today, $state, $country, $zip, $currency, $desc);

						echo json_encode(array('trial'      => $trial,
						                       'sub_total'  => $a['sub_total'],

						                       'tax'        => $a['tax'],
						                       'tax_per'    => $a['tax_per'],

						                       'total'      => $a['total'],

						                       'cur'        => $a['cur'],
						                       'cur_symbol' => $a['cur_symbol'],

						                       'desc'       => $a['desc']));
					}
				}
				exit(); // Clean exit.
			}
		}

		/**
		 * Handles all cost calculations for PayPal.
		 *
		 * Returns an associative array with a possible Percentage Rate, along with the calculated Tax Amount.
		 * Tax calculations are based on State/Province, Country, and/or Zip Code.
		 * Updated to support multiple data fields in it's return value.
		 *
		 * @package s2Member\PayPal
		 * @since 1.5
		 *
		 * @param int|string $trial_sub_total Optional. A numeric Amount/cost of a possible Initial/Trial being offered.
		 * @param int|string $sub_total Optional. A numeric Amount/cost of the purchase and/or Regular Period.
		 * @param string     $state Optional. The State/Province where the Customer is billed.
		 * @param string     $country Optional. The Country where the Customer is billed.
		 * @param int|string $zip Optional. The Postal/Zip Code where the Customer is billed.
		 * @param string     $currency Optional. Expects a 3 character Currency Code.
		 * @param string     $desc Optional. Description of the sale.
		 *
		 * @return array Array of calculations.
		 */
		public static function paypal_cost($trial_sub_total = 0, $sub_total = 0, $state = '', $country = '', $zip = '', $currency = '', $desc = '')
		{
			$state   = strtoupper(c_ws_plugin__s2member_pro_utilities::full_state($state, ($country = strtoupper($country))));
			$rates   = apply_filters('ws_plugin__s2member_pro_tax_rates_before_cost_calculation', strtoupper($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_tax_rates']), get_defined_vars());
			$default = $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_default_tax'];
			$ps      = _x('%', 's2member-front percentage-symbol', 's2member');

			$trial_tax = $tax = $trial_tax_per = $tax_per = $trial_total = $total = NULL; // Initialize.
			foreach(array('trial_sub_total' => $trial_sub_total, 'sub_total' => $sub_total) as $this_key => $this_sub_total)
			{
				$_default = $this_tax = $this_tax_per = $this_total = $configured_rates = $configured_rate = $location = $rate = $m = NULL;

				if(is_numeric($this_sub_total) && $this_sub_total > 0) // Must have a valid Sub-Total.
				{
					if(preg_match('/%$/', $default)) // Percentage-based.
					{
						if(($_default = (float)$default) > 0)
						{
							$this_tax     = round(($this_sub_total / 100) * $_default, 2);
							$this_tax_per = $_default.$ps;
						}
						else // Else the Tax is 0.00.
						{
							$this_tax     = 0.00;
							$this_tax_per = $_default.$ps;
						}
					}
					else if(($_default = (float)$default) > 0)
					{
						$this_tax     = round($_default, 2);
						$this_tax_per = ''; // Flat.
					}
					else // Else the Tax is 0.00.
					{
						$this_tax     = 0.00; // No Tax.
						$this_tax_per = ''; // Flat rate.
					}
					if(strlen($country) === 2) // Must have a valid country.
					{
						foreach(preg_split('/['."\r\n\t".']+/', $rates) as $rate)
						{
							if($rate = trim($rate)) // Do NOT process empty lines.
							{
								list($location, $rate) = preg_split('/\=/', $rate, 2);
								$location = trim($location);
								$rate     = trim($rate);

								if($location === $country)
									$configured_rates[1] = $rate;

								else if($state && $location === $state.'/'.$country)
									$configured_rates[2] = $rate;

								else if($state && preg_match('/^([A-Z]{2})\/('.preg_quote($country, '/').')$/', $location, $m) && strtoupper(c_ws_plugin__s2member_pro_utilities::full_state($m[1], $m[2])).'/'.$m[2] === $state.'/'.$country)
									$configured_rates[2] = $rate;

								else if($zip && preg_match('/^([0-9]+)-([0-9]+)\/('.preg_quote($country, '/').')$/', $location, $m) && $zip >= $m[1] && $zip <= $m[2] && $country === $m[3])
									$configured_rates[3] = $rate;

								else if($zip && $location === $zip.'/'.$country)
									$configured_rates[4] = $rate;
							}
						}
						if(is_array($configured_rates) && !empty($configured_rates))
						{
							krsort($configured_rates);
							$configured_rate = array_shift($configured_rates);

							if(preg_match('/%$/', $configured_rate)) // Percentage.
							{
								if(($configured_rate = (float)$configured_rate) > 0)
								{
									$this_tax     = round(($this_sub_total / 100) * $configured_rate, 2);
									$this_tax_per = $configured_rate.$ps;
								}
								else // Else the Tax is 0.00.
								{
									$this_tax     = 0.00; // No Tax.
									$this_tax_per = $configured_rate.$ps;
								}
							}
							else if(($configured_rate = (float)$configured_rate) > 0)
							{
								$this_tax     = round($configured_rate, 2);
								$this_tax_per = ''; // Flat rate.
							}
							else // Else the Tax is 0.00.
							{
								$this_tax     = 0.00; // No Tax.
								$this_tax_per = ''; // Flat rate.
							}
						}
					}
					$this_total = $this_sub_total + $this_tax;
				}
				else // Else the Tax is 0.00.
				{
					$this_tax       = 0.00; // No Tax.
					$this_tax_per   = ''; // Flat rate.
					$this_sub_total = 0.00; // 0.00.
					$this_total     = 0.00; // 0.00.
				}
				if($this_key === 'trial_sub_total')
				{
					$trial_tax       = $this_tax;
					$trial_tax_per   = $this_tax_per;
					$trial_sub_total = $this_sub_total;
					$trial_total     = $this_total;
				}
				else if($this_key === 'sub_total')
				{
					$tax       = $this_tax;
					$tax_per   = $this_tax_per;
					$sub_total = $this_sub_total;
					$total     = $this_total;
				}
			}
			return array('trial_sub_total' => number_format($trial_sub_total, 2, '.', ''),
			             'sub_total'       => number_format($sub_total, 2, '.', ''),

			             'trial_tax'       => number_format($trial_tax, 2, '.', ''),
			             'tax'             => number_format($tax, 2, '.', ''),

			             'trial_tax_per'   => $trial_tax_per,
			             'tax_per'         => $tax_per,

			             'trial_total'     => number_format($trial_total, 2, '.', ''),
			             'total'           => number_format($total, 2, '.', ''),

			             'cur'             => $currency,
			             'cur_symbol'      => c_ws_plugin__s2member_utils_cur::symbol($currency),

			             'desc'            => $desc);
		}

		/**
		 * Prepares an authoritative membership Pro-Form purchase for Framework PayPal Checkout.
		 *
		 * @since 260818
		 *
		 * @param array $raw_post_vars Raw s2member_pro_paypal_checkout form values.
		 *
		 * @return array|WP_Error Prepared Framework token data, else WP_Error.
		 */
		public static function paypal_checkout_prepare($raw_post_vars = array())
		{
			if(!c_ws_plugin__s2member_paypal_utilities::paypal_checkout_is_enabled())
				return new WP_Error('pro_checkout_not_enabled', _x('PayPal Checkout is not enabled.', 's2member-front', 's2member'));
			if(!is_array($raw_post_vars) || !$raw_post_vars)
				return new WP_Error('pro_checkout_missing_form', _x('Missing checkout form data. Please try again.', 's2member-front', 's2member'));

			$post_vars = c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($raw_post_vars));
			if(empty($post_vars['nonce']) || !wp_verify_nonce((string)$post_vars['nonce'], 's2member-pro-paypal-checkout'))
				return new WP_Error('pro_checkout_bad_nonce', _x('Unable to verify this checkout request. Please refresh the page and try again.', 's2member-front', 's2member'));

			$post_vars['attr'] = (!empty($post_vars['attr'])) ? (array)c_ws_plugin__s2member_utils_arrays::maybe_unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($post_vars['attr'])) : array();
			$post_vars['attr'] = apply_filters('ws_plugin__s2member_pro_paypal_checkout_post_attr', $post_vars['attr'], get_defined_vars());
			$post_vars['name'] = trim((string)@$post_vars['first_name'].' '.(string)@$post_vars['last_name']);
			$post_vars['email'] = apply_filters('user_registration_email', sanitize_email((string)@$post_vars['email']), get_defined_vars());
			$post_vars['username'] = (is_multisite()) ? strtolower((string)@$post_vars['username']) : (string)@$post_vars['username'];
			$post_vars['username'] = sanitize_user(($post_vars['_o_username'] = $post_vars['username']), is_multisite());

			if(empty($post_vars['card_expiration']) && isset($post_vars['card_expiration_month'], $post_vars['card_expiration_year']))
				$post_vars['card_expiration'] = $post_vars['card_expiration_month'].'/'.$post_vars['card_expiration_year'];

			$post_vars = c_ws_plugin__s2member_utils_captchas::recaptcha_post_vars($post_vars);

			if(($error = c_ws_plugin__s2member_pro_paypal_responses::paypal_form_attr_validation_errors($post_vars['attr'], TRUE)))
				return new WP_Error('pro_checkout_attr_invalid', !empty($error['response']) ? (string)$error['response'] : _x('Invalid checkout form configuration.', 's2member-front', 's2member'));
			if(($error = c_ws_plugin__s2member_pro_paypal_responses::paypal_form_submission_validation_errors('checkout', $post_vars, TRUE)))
				return new WP_Error('pro_checkout_validation_failed', !empty($error['response']) ? (string)$error['response'] : _x('Unable to validate this checkout request.', 's2member-front', 's2member'));
			if(empty($post_vars['card_type']) || (string)$post_vars['card_type'] !== 'PayPal')
				return new WP_Error('pro_checkout_not_paypal', _x('PayPal was not selected as the billing method.', 's2member-front', 's2member'));

			//260818.1920 Coupon, tax, and terms are finalized here; browser-displayed amounts are never authoritative.
			$cp_attr = self::paypal_apply_coupon($post_vars['attr'], !empty($post_vars['coupon']) ? $post_vars['coupon'] : '', 'attr', array('affiliates-silent-post'));
			$cp_2gbp_attr = self::paypal_maestro_solo_2gbp($cp_attr, $post_vars['card_type']);
			$cost_calculations = self::paypal_cost($cp_2gbp_attr['ta'], $cp_2gbp_attr['ra'], !empty($post_vars['state']) ? $post_vars['state'] : '', !empty($post_vars['country']) ? $post_vars['country'] : '', !empty($post_vars['zip']) ? $post_vars['zip'] : '', $cp_2gbp_attr['cc'], $cp_2gbp_attr['desc']);

			if($cost_calculations['total'] <= 0 && $post_vars['attr']['tp'] && $cost_calculations['trial_total'] > 0)
			{
				$post_vars['attr']['tp'] = '0';
				$cost_calculations['sub_total'] = $cost_calculations['trial_sub_total'];
				$cost_calculations['tax'] = $cost_calculations['trial_tax'];
				$cost_calculations['tax_per'] = $cost_calculations['trial_tax_per'];
				$cost_calculations['total'] = $cost_calculations['trial_total'];
				$cost_calculations['trial_sub_total'] = '0.00';
				$cost_calculations['trial_tax'] = '0.00';
				$cost_calculations['trial_tax_per'] = '';
				$cost_calculations['trial_total'] = '0.00';
			}

			$use_recurring_profile = ($post_vars['attr']['rr'] === 'BN' || (!$post_vars['attr']['tp'] && !$post_vars['attr']['rr'])) ? FALSE : TRUE;
			$is_independent_ccaps_sale = ($post_vars['attr']['level'] === '*');

			//260818.1920 A coupon can make a rendered paid form free; let the ordinary Pro-Form path handle that case without PayPal.
			if($cost_calculations['trial_total'] <= 0 && $cost_calculations['total'] <= 0)
				return new WP_Error('pro_checkout_payment_not_required', _x('Payment is no longer required for this checkout.', 's2member-front', 's2member'));

			$invoice = 's2mpf-'.md5(uniqid('s2mpf_', true).wp_rand());
			$period1 = c_ws_plugin__s2member_paypal_utilities::paypal_pro_period1($post_vars['attr']['tp'].' '.$post_vars['attr']['tt']);
			$period3 = c_ws_plugin__s2member_paypal_utilities::paypal_pro_period3($post_vars['attr']['rp'].' '.$post_vars['attr']['rt']);
			$user = (is_user_logged_in() && is_object($user = wp_get_current_user()) && ($user_id = (int)$user->ID)) ? $user : FALSE;
			$referencing = $user ? c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id() : FALSE;
			$payer_email = $user ? (string)$user->user_email : (string)$post_vars['email'];
			$host = (string)wp_parse_url(home_url('/'), PHP_URL_HOST);
			$host = $host ? $host : (!empty($_SERVER['HTTP_HOST']) ? preg_replace('/[^a-z0-9.\-]/i', '', (string)$_SERVER['HTTP_HOST']) : '');
			$port = (int)wp_parse_url(home_url('/'), PHP_URL_PORT);
			$url_host = $host.($port ? ':'.$port : '');

			$account = array(
				'mode'          => $user ? 'existing' : 'new',
				'user_id'       => $user ? (int)$user->ID : 0,
				'username'      => $user ? (string)$user->user_login : (string)$post_vars['username'],
				'email'         => $payer_email,
				'password1'     => $user ? '' : (string)@$post_vars['password1'],
				'first_name'    => (string)$post_vars['first_name'],
				'last_name'     => (string)$post_vars['last_name'],
				'custom_fields' => !empty($post_vars['custom_fields']) && is_array($post_vars['custom_fields']) ? $post_vars['custom_fields'] : array(),
			);

			$old_subscription = array();
			if($user)
			{
				$old_subscription = array(
					'gateway'         => (string)get_user_option('s2member_subscr_gateway', $user->ID),
					'id'              => (string)get_user_option('s2member_subscr_id', $user->ID),
					'baid'            => (string)get_user_option('s2member_subscr_baid', $user->ID),
					'cid'             => (string)get_user_option('s2member_subscr_cid', $user->ID),
					'ipn_signup_vars' => c_ws_plugin__s2member_utils_users::get_user_ipn_signup_vars(),
				);
			}

			$notify_paypal = array(
				'custom'            => (string)$post_vars['attr']['custom'],
				'mc_currency'       => (string)$cost_calculations['cur'],
				'tax'               => (string)$cost_calculations['tax'],
				'payer_email'       => $payer_email,
				'first_name'        => (string)$post_vars['first_name'],
				'last_name'         => (string)$post_vars['last_name'],
				'option_name1'      => $user ? 'Referencing Customer ID' : 'Originating Domain',
				'option_selection1' => $user ? (string)$referencing : $host,
				'option_name2'      => 'Customer IP Address',
				'option_selection2' => c_ws_plugin__s2member_utils_ip::current(),
				'item_name'         => (string)$cost_calculations['desc'],
				'item_number'       => (string)$post_vars['attr']['level_ccaps_eotper'],
			);

			if($use_recurring_profile)
			{
				$notify_paypal['period1'] = $period1;
				$notify_paypal['period3'] = $period3;
				$notify_paypal['mc_amount1'] = (string)$cost_calculations['trial_total'];
				$notify_paypal['mc_amount3'] = (string)$cost_calculations['total'];
				$notify_paypal['mc_gross'] = preg_match('/^[1-9]/', $period1) ? (string)$cost_calculations['trial_total'] : (string)$cost_calculations['total'];
				$notify_paypal['recurring'] = !empty($post_vars['attr']['rr']) ? '1' : '';
				$proxy_use = 'pro-emails'.(((float)$notify_paypal['mc_gross'] > 0) ? ',subscr-signup-as-subscr-payment' : '');
			}
			else
			{
				$notify_paypal['mc_gross'] = (string)$cost_calculations['total'];
				$proxy_use = 'pro-emails';
			}

			$coupon = array(
				'coupon_code'      => !empty($cp_attr['_coupon_code']) ? (string)$cp_attr['_coupon_code'] : '',
				'full_coupon_code' => !empty($cp_attr['_full_coupon_code']) ? (string)$cp_attr['_full_coupon_code'] : '',
				'affiliate_id'     => !empty($cp_attr['_coupon_affiliate_id']) ? (string)$cp_attr['_coupon_affiliate_id'] : '',
			);
			$notify_extra = array(
				's2member_paypal_proxy_coupon'     => $coupon,
				's2member_paypal_proxy_return_url' => !empty($post_vars['attr']['success']) ? (string)$post_vars['attr']['success'] : '',
			);

			$state = array(
				'recurring'        => $use_recurring_profile,
				'independent_ccaps'=> $is_independent_ccaps_sale,
				'account'          => $account,
				'old_subscription' => $old_subscription,
				'notify_paypal'    => $notify_paypal,
				'notify_extra'     => $notify_extra,
				'proxy_use'        => $proxy_use,
				'success'          => !empty($post_vars['attr']['success']) ? (string)$post_vars['attr']['success'] : '',
			);
			if(!self::paypal_checkout_prepared_state_set($invoice, $state))
			{
				c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
					'ppco'    => 'pro-form',
					'event'   => 'pro_purchase_prepare_save_failed',
					'invoice' => $invoice,
				));

				return new WP_Error('pro_checkout_state_save_failed', _x('Unable to prepare this checkout. Please try again.', 's2member-front', 's2member'));
			}

			$request_uri = !empty($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '/';
			$return_url = $cancel_url = ((is_ssl()) ? 'https://' : 'http://').$url_host.$request_uri;
			$return_url = $cancel_url = remove_query_arg(array('token', 'PayerID', 's2member_paypal_xco', 's2member_paypal_proxy'), ($return_url = $cancel_url));
			$return_url = add_query_arg('s2member_paypal_xco', 's2member_pro_paypal_checkout_rest_return', $return_url);

			$token = array(
				'exp'         => time() + 10800,
				'invoice'     => $invoice,
				'ip'          => c_ws_plugin__s2member_utils_ip::current(),
				'item_name'   => (string)$cost_calculations['desc'],
				'item_number' => (string)$post_vars['attr']['level_ccaps_eotper'],
				'custom'      => (string)$post_vars['attr']['custom'],
				'amount'      => (string)$cost_calculations['total'],
				'cc'          => strtoupper((string)$cost_calculations['cur']),
				'ns'          => (string)$post_vars['attr']['ns'],
				'rr'          => $use_recurring_profile ? (string)$post_vars['attr']['rr'] : 'BN',
				'rp'          => $use_recurring_profile ? (string)$post_vars['attr']['rp'] : '',
				'rt'          => $use_recurring_profile ? (string)$post_vars['attr']['rt'] : '',
				'rrt'         => $use_recurring_profile ? (string)$post_vars['attr']['rrt'] : '',
				'rra'         => $use_recurring_profile ? (string)$post_vars['attr']['rra'] : '',
				'ta'          => $use_recurring_profile ? (string)$cost_calculations['trial_total'] : '',
				'tp'          => $use_recurring_profile ? (string)$post_vars['attr']['tp'] : '',
				'tt'          => $use_recurring_profile ? (string)$post_vars['attr']['tt'] : '',
				'on0'         => $notify_paypal['option_name1'],
				'os0'         => $notify_paypal['option_selection1'],
				'on1'         => $notify_paypal['option_name2'],
				'os1'         => $notify_paypal['option_selection2'],
				'return'      => $return_url,
				'cancel'      => $cancel_url,
				's2member_paypal_proxy_use'        => 'pro-emails',
				's2member_paypal_proxy_coupon'     => $coupon,
				's2member_paypal_proxy_return_url' => !empty($post_vars['attr']['success']) ? (string)$post_vars['attr']['success'] : '',
				'checksum'    => md5($invoice.c_ws_plugin__s2member_utils_ip::current().$post_vars['attr']['level_ccaps_eotper']),
			);
			if(!$use_recurring_profile)
			{
				$token['sub_total'] = (string)$cost_calculations['sub_total'];
				$token['tax'] = (string)$cost_calculations['tax'];
				$token['payer_email'] = $payer_email;
				$token['first_name'] = (string)$post_vars['first_name'];
				$token['last_name'] = (string)$post_vars['last_name'];
			}

			$encrypted_token = urlencode(c_ws_plugin__s2member_utils_encryption::encrypt(serialize($token)));

			c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
				'ppco'      => 'pro-form',
				'event'     => 'pro_purchase_prepared',
				'invoice'   => $invoice,
				'mode'      => $account['mode'],
				'recurring' => $use_recurring_profile,
				'amount'    => (string)$cost_calculations['total'],
				'cc'        => strtoupper((string)$cost_calculations['cur']),
			));

			return array(
				'ok'       => TRUE,
				'flow'     => $use_recurring_profile ? 'subscription' : 'order',
				'invoice'  => $invoice,
				'token'    => $encrypted_token,
				'endpoint' => home_url('/?s2member_paypal_checkout=1'),
				'amount'   => (string)$cost_calculations['total'],
				'cc'       => strtoupper((string)$cost_calculations['cur']),
			);
		}

		/**
		 * Handles the signed browser return after Framework PayPal Checkout fulfillment.
		 *
		 * @since 260818
		 *
		 * @return void
		 */
		public static function paypal_checkout_browser_return()
		{
			$GLOBALS['ws_plugin__s2member_pro_paypal_checkout_response'] = array();
			$global_response = &$GLOBALS['ws_plugin__s2member_pro_paypal_checkout_response'];
			$paypal = c_ws_plugin__s2member_paypal_utilities::paypal_postvars();
			$invoice = (is_array($paypal) && !empty($paypal['invoice'])) ? (string)$paypal['invoice'] : '';

			if(!is_array($paypal) || empty($paypal['proxy_verified']) || $paypal['proxy_verified'] !== 'paypal'
			|| !self::paypal_checkout_prepared_invoice($invoice) || empty($paypal['txn_id'])
			|| empty($paypal['payment_status']) || strcasecmp((string)$paypal['payment_status'], 'Completed') !== 0
			|| !($completion = self::paypal_checkout_completion_state_get($invoice))
			|| empty($completion['payment_id']) || (string)$completion['payment_id'] !== (string)$paypal['txn_id'])
			{
				c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
					'ppco'    => 'pro-form',
					'event'   => 'pro_return_verification_failed',
					'invoice' => $invoice,
					'txn_id'  => is_array($paypal) && !empty($paypal['txn_id']) ? (string)$paypal['txn_id'] : '',
				));

				$global_response = array('response' => _x('<strong>Oops.</strong> Unable to verify the completed PayPal Checkout transaction. Please contact Support for assistance.', 's2member-front', 's2member'), 'error' => TRUE);
				return;
			}

			//260818.1920 Tracking is browser-only; account/payment fulfillment already succeeded before this signed return.
			setcookie('s2member_tracking', ($s2member_tracking = c_ws_plugin__s2member_utils_encryption::encrypt((string)$completion['payment_id'])), time() + 31556926, COOKIEPATH, COOKIE_DOMAIN);
			setcookie('s2member_tracking', $s2member_tracking, time() + 31556926, SITECOOKIEPATH, COOKIE_DOMAIN);
			$_COOKIE['s2member_tracking'] = $s2member_tracking;

			if(!empty($completion['account_mode']) && $completion['account_mode'] === 'existing')
				$response = _x('<strong>Thank you.</strong> Your account has been updated.', 's2member-front', 's2member');
			else if(!empty($completion['password_generated']))
				$response = _x('<strong>Thank you.</strong> Your account has been approved.<br />&mdash; You\'ll receive an email momentarily.', 's2member-front', 's2member');
			else
				$response = sprintf(_x('<strong>Thank you.</strong> Your account has been approved.<br />&mdash; Please <a href="%s" rel="nofollow">log in</a>.', 's2member-front', 's2member'), esc_attr(wp_login_url()));

			$global_response = array('response' => $response);
			$success = !empty($completion['success']) ? (string)$completion['success'] : '';
			$notify_return_url = !empty($completion['notify_body']) ? trim((string)$completion['notify_body']) : '';

			c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
				'ppco'       => 'pro-form',
				'event'      => 'pro_return_verified',
				'invoice'    => $invoice,
				'payment_id' => (string)$completion['payment_id'],
				'recurring'  => !empty($completion['recurring']),
			));

			if($success && substr($notify_return_url, 0, 2) === substr($success, 0, 2)
			&& ($custom_success_url = str_ireplace(array('%%s_response%%', '%%response%%'), array(urlencode(c_ws_plugin__s2member_utils_encryption::encrypt($response)), urlencode($response)), $notify_return_url))
			&& ($custom_success_url = trim(preg_replace('/%%(.+?)%%/i', '', $custom_success_url))))
				wp_redirect(c_ws_plugin__s2member_utils_urls::add_s2member_sig($custom_success_url, 's2p-v')).exit();
		}

		/**
		 * Returns the transient key for a prepared PayPal Checkout membership purchase.
		 *
		 * @since 260818
		 *
		 * @param string $invoice Prepared purchase invoice.
		 *
		 * @return string Transient key.
		 */
		public static function paypal_checkout_prepared_state_key($invoice = '')
		{
			return 's2m_'.md5('s2member_pro_paypal_checkout_prepared_'.(string)$invoice);
		}

		/**
		 * Tests whether an invoice belongs to a prepared membership Pro-Form PayPal Checkout purchase.
		 *
		 * @since 260818
		 *
		 * @param string $invoice Invoice value.
		 *
		 * @return bool True for a reserved membership Pro-Form invoice.
		 */
		public static function paypal_checkout_prepared_invoice($invoice = '')
		{
			return (strpos((string)$invoice, 's2mpf-') === 0);
		}

		/**
		 * Stores encrypted prepared PayPal Checkout membership state.
		 *
		 * @since 260818
		 *
		 * @param string $invoice Prepared purchase invoice.
		 * @param array  $state Prepared purchase state.
		 *
		 * @return bool True when the state can be read back.
		 */
		public static function paypal_checkout_prepared_state_set($invoice = '', $state = array())
		{
			if(!self::paypal_checkout_prepared_invoice($invoice) || !is_array($state) || !$state)
				return FALSE;

			$state['invoice'] = (string)$invoice;
			set_transient(self::paypal_checkout_prepared_state_key($invoice), c_ws_plugin__s2member_utils_encryption::encrypt(serialize($state)), WEEK_IN_SECONDS);

			return (self::paypal_checkout_prepared_state_get($invoice) !== FALSE);
		}

		/**
		 * Gets encrypted prepared PayPal Checkout membership state.
		 *
		 * @since 260818
		 *
		 * @param string $invoice Prepared purchase invoice.
		 *
		 * @return array|false Prepared state, else false.
		 */
		public static function paypal_checkout_prepared_state_get($invoice = '')
		{
			if(!self::paypal_checkout_prepared_invoice($invoice) || !($encrypted = get_transient(self::paypal_checkout_prepared_state_key($invoice))))
				return FALSE;

			$state = c_ws_plugin__s2member_utils_arrays::maybe_unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($encrypted));
			if(!is_array($state) || empty($state['invoice']) || (string)$state['invoice'] !== (string)$invoice)
				return FALSE;

			return $state;
		}

		/**
		 * Deletes prepared PayPal Checkout membership state.
		 *
		 * @since 260818
		 *
		 * @param string $invoice Prepared purchase invoice.
		 *
		 * @return bool True if deleted, else false.
		 */
		public static function paypal_checkout_prepared_state_delete($invoice = '')
		{
			return delete_transient(self::paypal_checkout_prepared_state_key($invoice));
		}

		/**
		 * Returns the transient key for scrubbed PayPal Checkout browser-completion state.
		 *
		 * @since 260818
		 *
		 * @param string $invoice Prepared purchase invoice.
		 *
		 * @return string Transient key.
		 */
		public static function paypal_checkout_completion_state_key($invoice = '')
		{
			return 's2m_'.md5('s2member_pro_paypal_checkout_completion_'.(string)$invoice);
		}

		/**
		 * Stores scrubbed PayPal Checkout browser-completion state.
		 *
		 * @since 260818
		 *
		 * @param string $invoice Prepared purchase invoice.
		 * @param array  $state Browser-completion state.
		 *
		 * @return bool True when the state can be read back.
		 */
		public static function paypal_checkout_completion_state_set($invoice = '', $state = array())
		{
			if(!self::paypal_checkout_prepared_invoice($invoice) || !is_array($state) || !$state)
				return FALSE;

			$state['invoice'] = (string)$invoice;
			set_transient(self::paypal_checkout_completion_state_key($invoice), c_ws_plugin__s2member_utils_encryption::encrypt(serialize($state)), DAY_IN_SECONDS);

			return (self::paypal_checkout_completion_state_get($invoice) !== FALSE);
		}

		/**
		 * Gets scrubbed PayPal Checkout browser-completion state.
		 *
		 * @since 260818
		 *
		 * @param string $invoice Prepared purchase invoice.
		 *
		 * @return array|false Completion state, else false.
		 */
		public static function paypal_checkout_completion_state_get($invoice = '')
		{
			if(!self::paypal_checkout_prepared_invoice($invoice) || !($encrypted = get_transient(self::paypal_checkout_completion_state_key($invoice))))
				return FALSE;

			$state = c_ws_plugin__s2member_utils_arrays::maybe_unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($encrypted));
			if(!is_array($state) || empty($state['invoice']) || (string)$state['invoice'] !== (string)$invoice)
				return FALSE;

			return $state;
		}

		/**
		 * Deletes scrubbed PayPal Checkout browser-completion state.
		 *
		 * @since 260818
		 *
		 * @param string $invoice Prepared purchase invoice.
		 *
		 * @return bool True if deleted, else false.
		 */
		public static function paypal_checkout_completion_state_delete($invoice = '')
		{
			return delete_transient(self::paypal_checkout_completion_state_key($invoice));
		}

		/**
		 * Creates or restores the Pro-Form account required before PayPal Checkout Notify fulfillment.
		 *
		 * @since 260818
		 *
		 * @param array  $state Prepared purchase state, updated by reference.
		 * @param array  $paypal Verified PayPal fulfillment variables.
		 * @param string $invoice Prepared purchase invoice.
		 *
		 * @return int|WP_Error User ID on success, else WP_Error.
		 */
		protected static function paypal_checkout_prepare_account(&$state = array(), $paypal = array(), $invoice = '')
		{
			$account = !empty($state['account']) && is_array($state['account']) ? $state['account'] : array();
			$mode = !empty($account['mode']) ? (string)$account['mode'] : '';

			if($mode === 'existing')
			{
				$user_id = !empty($account['user_id']) ? (int)$account['user_id'] : 0;
				if(!$user_id || !get_userdata($user_id))
					return new WP_Error('pro_checkout_user_missing');

				update_user_meta($user_id, 'first_name', isset($account['first_name']) ? (string)$account['first_name'] : '');
				update_user_meta($user_id, 'last_name', isset($account['last_name']) ? (string)$account['last_name'] : '');

				return $user_id;
			}

			if($mode !== 'new' || empty($account['username']) || empty($account['email']))
				return new WP_Error('pro_checkout_account_invalid');

			if(!empty($account['prepared_user_id']) && ($user_id = (int)$account['prepared_user_id']) && get_userdata($user_id))
				return $user_id;

			//260818.1752 Recover an account created by an earlier attempt if state persistence failed; never adopt an unrelated user.
			if(($existing_user = get_user_by('login', (string)$account['username'])) && (string)get_user_meta($existing_user->ID, 's2member_paypal_checkout_prepared_invoice', TRUE) === (string)$invoice)
			{
				$state['account']['prepared_user_id'] = (int)$existing_user->ID;
				$state['account']['password1'] = $state['account']['user_pass'] = ''; //260818.1752 Credentials are no longer needed once the account exists.
				if(!self::paypal_checkout_prepared_state_set($invoice, $state))
					return new WP_Error('pro_checkout_state_save_failed');

				return (int)$existing_user->ID;
			}
			else if($existing_user && (!is_multisite() || (int)c_ws_plugin__s2member_utils_users::ms_user_login_email_exists_but_not_on_blog((string)$account['username'], (string)$account['email']) !== (int)$existing_user->ID))
				return new WP_Error('pro_checkout_user_conflict');

			//260818.1752 Persist a generated password before account creation so a retry cannot generate different credentials.
			if(empty($account['user_pass']))
			{
				//260818.1830 maybe_custom_pass() accepts its password by reference, so PHP requires a variable here.
				$password1 = isset($account['password1']) ? (string)$account['password1'] : '';
				$state['account']['user_pass'] = c_ws_plugin__s2member_registrations::maybe_custom_pass($password1);
				$state['account']['password_generated'] = (empty($account['password1']) || (string)$account['password1'] !== (string)$state['account']['user_pass']);
				if(!self::paypal_checkout_prepared_state_set($invoice, $state))
					return new WP_Error('pro_checkout_state_save_failed');

				$account = $state['account'];
			}

			$old_post = $_POST;
			$old_cookie = $_COOKIE;
			$had_registration_vars = isset($GLOBALS['ws_plugin__s2member_registration_vars']);
			$old_registration_vars = $had_registration_vars ? $GLOBALS['ws_plugin__s2member_registration_vars'] : NULL;

			try
			{
				$_POST['ws_plugin__s2member_custom_reg_field_user_pass1'] = isset($account['password1']) ? (string)$account['password1'] : '';
				$_POST['ws_plugin__s2member_custom_reg_field_first_name'] = isset($account['first_name']) ? (string)$account['first_name'] : '';
				$_POST['ws_plugin__s2member_custom_reg_field_last_name'] = isset($account['last_name']) ? (string)$account['last_name'] : '';
				$_POST['ws_plugin__s2member_custom_reg_field_opt_in'] = !empty($account['custom_fields']['opt_in']) ? '1' : '';

				if($GLOBALS['WS_PLUGIN__']['s2member']['o']['custom_reg_fields'])
					foreach(json_decode($GLOBALS['WS_PLUGIN__']['s2member']['o']['custom_reg_fields'], TRUE) as $field)
					{
						$field_var = preg_replace('/[^a-z0-9]/i', '_', strtolower($field['id']));
						if(isset($account['custom_fields'][$field_var]))
							$_POST['ws_plugin__s2member_custom_reg_field_'.$field_var] = $account['custom_fields'][$field_var];
					}

				$subscr_id = !empty($state['recurring']) ? (!empty($paypal['subscr_id']) ? (string)$paypal['subscr_id'] : '') : (!empty($paypal['txn_id']) ? (string)$paypal['txn_id'] : '');
				if(!$subscr_id)
					return new WP_Error('pro_checkout_payment_id_missing');

				if(!empty($state['recurring']) && !empty($paypal['subscr_baid']))
					$GLOBALS['ws_plugin__s2member_registration_vars']['ws_plugin__s2member_custom_reg_field_s2member_subscr_baid'] = (string)$paypal['subscr_baid'];

				$_COOKIE['s2member_subscr_gateway'] = c_ws_plugin__s2member_utils_encryption::encrypt('paypal');
				$_COOKIE['s2member_subscr_id'] = c_ws_plugin__s2member_utils_encryption::encrypt($subscr_id);
				$_COOKIE['s2member_custom'] = c_ws_plugin__s2member_utils_encryption::encrypt((string)$state['notify_paypal']['custom']);
				$_COOKIE['s2member_item_number'] = c_ws_plugin__s2member_utils_encryption::encrypt((string)$state['notify_paypal']['item_number']);

				$user_login = (string)$account['username'];
				$user_pass = (string)$account['user_pass'];
				$user_email = (string)$account['email'];
				$new_user_id = (is_multisite() && ($new_user_id = c_ws_plugin__s2member_registrations::ms_create_existing_user($user_login, $user_email, $user_pass))) ? $new_user_id : wp_create_user($user_login, $user_pass, $user_email);
			}
			finally
			{
				$_POST = $old_post;
				$_COOKIE = $old_cookie;

				if($had_registration_vars)
					$GLOBALS['ws_plugin__s2member_registration_vars'] = $old_registration_vars;
				else
					unset($GLOBALS['ws_plugin__s2member_registration_vars']);
			}

			if(empty($new_user_id) || is_wp_error($new_user_id))
				return new WP_Error('pro_checkout_user_create_failed');

			$new_user_id = (int)$new_user_id;
			$notification_user_pass = (string)$state['account']['user_pass'];
			$password_generated = !empty($state['account']['password_generated']);

			update_user_meta($new_user_id, 's2member_paypal_checkout_prepared_invoice', (string)$invoice);
			$state['account']['prepared_user_id'] = $new_user_id;
			$state['account']['password1'] = $state['account']['user_pass'] = ''; //260818.1752 Do not retain credentials after account creation.
			if(!self::paypal_checkout_prepared_state_set($invoice, $state))
				return new WP_Error('pro_checkout_state_save_failed');

			if($password_generated)
			{
				update_user_option($new_user_id, 'default_password_nag', TRUE, TRUE);

				if(version_compare(get_bloginfo('version'), '4.3.1', '>='))
					wp_new_user_notification($new_user_id, NULL, 'both', $notification_user_pass);
				else if(version_compare(get_bloginfo('version'), '4.3', '>='))
					wp_new_user_notification($new_user_id, 'both', $notification_user_pass);
				else
					wp_new_user_notification($new_user_id, $notification_user_pass);
			}
			else
			{
				if(version_compare(get_bloginfo('version'), '4.3.1', '>='))
					wp_new_user_notification($new_user_id, NULL, 'admin', $notification_user_pass);
				else if(version_compare(get_bloginfo('version'), '4.3', '>='))
					wp_new_user_notification($new_user_id, 'admin', $notification_user_pass);
				else
					wp_new_user_notification($new_user_id, $notification_user_pass);
			}

			return $new_user_id;
		}

		/**
		 * Prepares account-specific PayPal Checkout Notify context for membership Pro-Forms.
		 *
		 * @since 260818
		 *
		 * @param array  $context Framework PayPal Checkout Notify context.
		 * @param string $done_option Framework fulfillment done-marker option.
		 *
		 * @return array|WP_Error Prepared Notify context, or WP_Error to keep fulfillment retryable.
		 */
		public static function paypal_checkout_notify_context($context = array(), $done_option = '')
		{
			if(!is_array($context) || empty($context['paypal']) || !is_array($context['paypal']))
				return $context;

			$invoice = !empty($context['paypal']['invoice']) ? (string)$context['paypal']['invoice'] : '';
			if(!self::paypal_checkout_prepared_invoice($invoice))
				return $context;

			if(!($state = self::paypal_checkout_prepared_state_get($invoice)) || empty($state['notify_paypal']) || !is_array($state['notify_paypal']) || empty($state['account']) || !is_array($state['account']))
			{
				c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
					'ppco'    => 'pro-form',
					'event'   => 'pro_prepared_state_missing',
					'invoice' => $invoice,
				));

				return new WP_Error('pro_checkout_prepared_state_missing');
			}

			$actual_payment_id = !empty($state['recurring']) ? (!empty($context['paypal']['subscr_id']) ? (string)$context['paypal']['subscr_id'] : '') : (!empty($context['paypal']['txn_id']) ? (string)$context['paypal']['txn_id'] : '');
			if(!$actual_payment_id)
				return new WP_Error('pro_checkout_payment_id_missing');

			if(is_wp_error($user_id = self::paypal_checkout_prepare_account($state, $context['paypal'], $invoice)))
			{
				c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
					'ppco'    => 'pro-form',
					'event'   => 'pro_account_prepare_failed',
					'invoice' => $invoice,
					'error'   => (string)$user_id->get_error_code(),
				));

				return $user_id;
			}

			$paypal = array_merge($context['paypal'], $state['notify_paypal']);
			$paypal['payment_status'] = 'Completed';
			$paypal['invoice'] = $invoice;

			if(!empty($state['recurring']))
			{
				$paypal['txn_type'] = 'subscr_signup';
				$paypal['txn_id'] = $actual_payment_id;
				$paypal['subscr_id'] = $actual_payment_id;
				$paypal['subscr_baid'] = !empty($context['paypal']['subscr_baid']) ? (string)$context['paypal']['subscr_baid'] : $actual_payment_id;
				$paypal['subscr_cid'] = !empty($context['paypal']['subscr_cid']) ? (string)$context['paypal']['subscr_cid'] : $actual_payment_id;
			}
			else
			{
				$paypal['txn_type'] = 'web_accept';
				$paypal['txn_id'] = $actual_payment_id;
				unset($paypal['subscr_id'], $paypal['subscr_baid'], $paypal['subscr_cid']); //260818.1752 Match the legacy one-time Pro-Form Notify shape.
			}

			$context['paypal'] = $paypal;
			$context['proxy_use'] = !empty($state['proxy_use']) ? (string)$state['proxy_use'] : 'pro-emails';
			$context['extra'] = array_merge(!empty($context['extra']) && is_array($context['extra']) ? $context['extra'] : array(), !empty($state['notify_extra']) && is_array($state['notify_extra']) ? $state['notify_extra'] : array());

			c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
				'ppco'      => 'pro-form',
				'event'     => 'pro_account_prepared',
				'invoice'   => $invoice,
				'user_id'   => (int)$user_id,
				'mode'      => !empty($state['account']['mode']) ? (string)$state['account']['mode'] : '',
				'recurring' => !empty($state['recurring']),
			));

			return $context;
		}

		/**
		 * Completes legacy Pro-Form post-Notify work after PayPal Checkout fulfillment succeeds.
		 *
		 * @since 260818
		 *
		 * @param array  $context Framework PayPal Checkout Notify context.
		 * @param string $done_option Framework fulfillment done-marker option.
		 * @param array  $result Internal Notify HTTP result.
		 *
		 * @return void
		 */
		public static function paypal_checkout_notify_processed($context = array(), $done_option = '', $result = array())
		{
			if(!is_array($context) || empty($context['paypal']['invoice']) || !self::paypal_checkout_prepared_invoice($invoice = (string)$context['paypal']['invoice']))
				return;

			if(!($state = self::paypal_checkout_prepared_state_get($invoice)) || empty($state['account']) || !is_array($state['account']))
				return;

			$paypal = !empty($context['paypal']) && is_array($context['paypal']) ? $context['paypal'] : array();
			$new__subscr_id = !empty($state['recurring']) ? (!empty($paypal['subscr_id']) ? (string)$paypal['subscr_id'] : '') : (!empty($paypal['txn_id']) ? (string)$paypal['txn_id'] : '');

			if(!empty($state['account']['mode']) && $state['account']['mode'] === 'existing')
			{
				$old = !empty($state['old_subscription']) && is_array($state['old_subscription']) ? $state['old_subscription'] : array();
				$old__subscr_gateway = !empty($old['gateway']) ? (string)$old['gateway'] : '';
				$old__subscr_id = !empty($old['id']) ? (string)$old['id'] : '';
				$old__subscr_baid = !empty($old['baid']) ? (string)$old['baid'] : '';
				$old__subscr_cid = !empty($old['cid']) ? (string)$old['cid'] : '';
				$old__ipn_signup_vars = !empty($old['ipn_signup_vars']) && is_array($old['ipn_signup_vars']) ? $old['ipn_signup_vars'] : array();
				$should_cancel_old = (!empty($state['recurring']) || empty($state['independent_ccaps']));

				if($should_cancel_old && $old__subscr_id && apply_filters('s2member_pro_cancels_old_rp_before_new_rp', ($old__subscr_id !== $new__subscr_id), get_defined_vars()))
					c_ws_plugin__s2member_utilities::cancel_gateway_subscription($old__subscr_gateway, $old__subscr_id, $old__subscr_baid, $old__subscr_cid, $old__ipn_signup_vars);

				if(!empty($state['account']['user_id']))
				{
					$previous_user_id = get_current_user_id();
					try
					{
						wp_set_current_user((int)$state['account']['user_id']);
						c_ws_plugin__s2member_list_servers::process_list_servers_against_current_user(!empty($state['account']['custom_fields']['opt_in']), TRUE, TRUE);
					}
					finally
					{
						wp_set_current_user($previous_user_id);
					}
				}
			}

			if(!empty($state['account']['prepared_user_id']))
				delete_user_meta((int)$state['account']['prepared_user_id'], 's2member_paypal_checkout_prepared_invoice', (string)$invoice);

			//260818.1752 Preserve only scrubbed completion data for a late browser return; completed purchase state must not re-enter Notify.
			$completion_state = array(
				'account_mode'       => !empty($state['account']['mode']) ? (string)$state['account']['mode'] : '',
				'password_generated' => !empty($state['account']['password_generated']),
				'payment_id'         => $new__subscr_id,
				'recurring'          => !empty($state['recurring']),
				'success'            => !empty($state['success']) ? (string)$state['success'] : '', //260818.1920 Preserve only the configured browser destination, never account details.
				'notify_body'        => !empty($result['body']) ? (string)$result['body'] : '',
			);
			if(!self::paypal_checkout_completion_state_set($invoice, $completion_state))
				c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
					'ppco'    => 'pro-form',
					'event'   => 'pro_completion_state_save_failed',
					'invoice' => $invoice,
				));

			self::paypal_checkout_prepared_state_delete($invoice);

			c_ws_plugin__s2member_utils_logs::log_entry('paypal-checkout', array(
				'ppco'       => 'pro-form',
				'event'      => 'pro_fulfillment_completed',
				'invoice'    => $invoice,
				'payment_id' => $new__subscr_id,
			));
		}

		/**
		 * Checks to see if a Coupon Code was supplied, and if so; what does it provide?
		 *
		 * @package s2Member\PayPal
		 * @since 1.5
		 *
		 * @param array  $attr An array of Pro-Form Attributes.
		 * @param string $coupon_code Optional. A possible Coupon Code supplied by the Customer.
		 * @param string $return Optional. Return type. One of `response|attr`. Defaults to `attr`.
		 * @param array  $process Optional. An array of additional processing routines to run here.
		 *   One or more of these values: `affiliates-1px-response|affiliates-silent-post|notifications`.
		 *
		 * @return array|string Original array, with prices and description modified when/if a Coupon Code is accepted.
		 *   Or, if ``$return === 'response'``, return a string response, indicating status.
		 */
		public static function paypal_apply_coupon($attr = array(), $coupon_code = '', $return = '', $process = array())
		{
			$coupons = new c_ws_plugin__s2member_pro_coupons();
			return $coupons->apply($attr, $coupon_code, $return, $process);
		}
	}
}
