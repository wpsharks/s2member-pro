<?php
/**
 * Stripe Checkout Form handler (inner processing routines).
 *
 * Copyright: © 2009-2011
 * {@link http://www.websharks-inc.com/ WebSharks, Inc.}
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
 *   See: {@link http://www.s2member.com/prices/}
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
if(realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME']))
	exit('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_pro_stripe_checkout_in'))
{
	/**
	 * Stripe Checkout Form handler (inner processing routines).
	 *
	 * @package s2Member\Stripe
	 * @since 140617
	 */
	class c_ws_plugin__s2member_pro_stripe_checkout_in
	{
		/**
		 * Handles processing of Pro Form checkouts.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 *
		 * @attaches-to ``add_action('init');``
		 */
		public static function stripe_checkout()
		{
			if(!empty($_POST['s2member_pro_stripe_checkout']['nonce']) && ($nonce = $_POST['s2member_pro_stripe_checkout']['nonce']) && wp_verify_nonce($nonce, 's2member-pro-stripe-checkout'))
			{
				$GLOBALS['ws_plugin__s2member_pro_stripe_checkout_response'] = array(); // This holds the global response details.
				$global_response                                             = & $GLOBALS['ws_plugin__s2member_pro_stripe_checkout_response'];

				$post_vars         = c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($_POST['s2member_pro_stripe_checkout']));
				$post_vars['attr'] = (!empty($post_vars['attr'])) ? (array)unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($post_vars['attr'])) : array();
				$post_vars['attr'] = apply_filters('ws_plugin__s2member_pro_stripe_checkout_post_attr', $post_vars['attr'], get_defined_vars());

				$post_vars['name']     = trim($post_vars['first_name'].' '.$post_vars['last_name']);
				$post_vars['email']    = apply_filters('user_registration_email', sanitize_email(@$post_vars['email']), get_defined_vars());
				$post_vars['username'] = (is_multisite()) ? strtolower(@$post_vars['username']) : @$post_vars['username']; // Force lowercase.
				$post_vars['username'] = preg_replace('/\s+/', '', sanitize_user(($post_vars['_o_username'] = $post_vars['username']), is_multisite()));

				$post_vars['recaptcha_challenge_field'] = (isset($_POST['recaptcha_challenge_field'])) ? trim(stripslashes($_POST['recaptcha_challenge_field'])) : '';
				$post_vars['recaptcha_response_field']  = (isset($_POST['recaptcha_response_field'])) ? trim(stripslashes($_POST['recaptcha_response_field'])) : '';

				if(!c_ws_plugin__s2member_pro_stripe_responses::stripe_form_attr_validation_errors($post_vars['attr'])) // Attr errors?
				{
					if(!($form_submission_validation_errors // Validate checkout input form fields.
						= c_ws_plugin__s2member_pro_stripe_responses::stripe_form_submission_validation_errors('checkout', $post_vars))
					) // If this fails the global response is set to the error(s) returned during form field validation.
					{
						$cp_attr           = c_ws_plugin__s2member_pro_stripe_utilities::apply_coupon($post_vars['attr'], $post_vars['coupon'], 'attr', array('affiliates-silent-post'));
						$cost_calculations = c_ws_plugin__s2member_pro_stripe_utilities::cost($cp_attr['ta'], $cp_attr['ra'], $post_vars['state'], $post_vars['country'], $post_vars['zip'], $cp_attr['cc'], $cp_attr['desc']);

						if($cost_calculations['total'] <= 0 && $post_vars['attr']['tp'] && $cost_calculations['trial_total'] > 0)
						{
							$post_vars['attr']['tp']              = '0'; // Ditch the trial period completely.
							$cost_calculations['sub_total']       = $cost_calculations['trial_sub_total']; // Use as regular sub-total (ditch trial sub-total).
							$cost_calculations['tax']             = $cost_calculations['trial_tax']; // Use as regular tax (ditch trial tax).
							$cost_calculations['tax_per']         = $cost_calculations['trial_tax_per']; // Use as regular tax (ditch trial tax).
							$cost_calculations['total']           = $cost_calculations['trial_total']; // Use as regular total (ditch trial).
							$cost_calculations['trial_sub_total'] = '0.00'; // Ditch the initial total (using as grand total).
							$cost_calculations['trial_tax']       = '0.00'; // Ditch this calculation now also.
							$cost_calculations['trial_tax_per']   = ''; // Ditch this calculation now also.
							$cost_calculations['trial_total']     = '0.00'; // Ditch this calculation now also.
						}
						$use_recurring_profile     = ($post_vars['attr']['rr'] === 'BN' || (!$post_vars['attr']['tp'] && !$post_vars['attr']['rr'])) ? FALSE : TRUE;
						$is_independent_ccaps_sale = ($post_vars['attr']['level'] === '*') ? TRUE : FALSE; // Selling Independent Custom Capabilities?

						if($use_recurring_profile && $cost_calculations['trial_total'] <= 0 && $cost_calculations['total'] <= 0)
						{
							if(!$post_vars['attr']['rr'] && $post_vars['attr']['rt'] !== 'L')
							{
								if(substr_count($post_vars['attr']['level_ccaps_eotper'], ':') === 1)
									$post_vars['attr']['level_ccaps_eotper'] .= ':'.$post_vars['attr']['rp'].' '.$post_vars['attr']['rt'];

								else if(substr_count($post_vars['attr']['level_ccaps_eotper'], ':') === 0)
									$post_vars['attr']['level_ccaps_eotper'] .= '::'.$post_vars['attr']['rp'].' '.$post_vars['attr']['rt'];
							}
							else if($post_vars['attr']['rr'] && $post_vars['attr']['rrt'] && $post_vars['attr']['rt'] !== 'L')
							{
								if(substr_count($post_vars['attr']['level_ccaps_eotper'], ':') === 1)
									$post_vars['attr']['level_ccaps_eotper'] .= ':'.($post_vars['attr']['rp'] * $post_vars['attr']['rrt']).' '.$post_vars['attr']['rt'];

								else if(substr_count($post_vars['attr']['level_ccaps_eotper'], ':') === 0)
									$post_vars['attr']['level_ccaps_eotper'] .= '::'.($post_vars['attr']['rp'] * $post_vars['attr']['rrt']).' '.$post_vars['attr']['rt'];
							}
						}
						if($use_recurring_profile && is_user_logged_in() && is_object($user = wp_get_current_user()) && ($user_id = $user->ID))
						{
							$plan_attr       = $cp_attr; // For the subscription plan.
							$plan_attr['ta'] = $cost_calculations['trial_total'];
							$plan_attr['ra'] = $cost_calculations['total'];

							update_user_meta($user_id, 'first_name', $post_vars['first_name']);
							update_user_meta($user_id, 'last_name', $post_vars['last_name']);

							$old__subscr_cid      = get_user_option('s2member_subscr_cid');
							$old__subscr_id       = get_user_option('s2member_subscr_id');
							$old__subscr_or_wp_id = c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id();

							if(!$global_response && (($post_vars['attr']['tp'] && $cost_calculations['trial_total'] > 0) || (!$post_vars['attr']['tp'] && $cost_calculations['total'] > 0)))
							{
								if(!is_object($stripe_customer = c_ws_plugin__s2member_pro_stripe_utilities::get_customer($user_id, $post_vars['email'], $post_vars['first_name'], $post_vars['last_name'])))
									$global_response = array('response' => $stripe_customer, 'error' => TRUE);

								else if(!is_object($stripe_customer = c_ws_plugin__s2member_pro_stripe_utilities::set_customer_card_token($stripe_customer->id, $post_vars['card_token'])))
									$global_response = array('response' => $stripe_customer, 'error' => TRUE);

								else if(!is_object($stripe_charge = c_ws_plugin__s2member_pro_stripe_utilities::create_customer_charge($stripe_customer->id, ($post_vars['attr']['tp'] && $cost_calculations['trial_total'] > 0) ? $cost_calculations['trial_total'] : $cost_calculations['total'], $cost_calculations['cur'], $cost_calculations['desc'])))
									$global_response = array('response' => $stripe_charge, 'error' => TRUE);

								else // We got what we needed here.
								{
									$new__txn_id  = $stripe_charge->id;
									$new__txn_cid = $stripe_customer->id;
								}
							}
							else if(!$global_response)
							{
								$new__txn_id  = strtoupper('free-'.uniqid());
								$new__txn_cid = strtoupper('free-'.uniqid());
							}
							if(!$global_response && $cost_calculations['total'] > 0) // NOTE: we need to flag non-recurring subscriptions; it is s2Member's job to stop them.
							{
								if(!is_object($stripe_plan = c_ws_plugin__s2member_pro_stripe_utilities::get_plan($plan_attr)))
									$global_response = array('response' => $stripe_plan, 'error' => TRUE);

								else if((!isset($stripe_customer) || !is_object($stripe_customer))
								        && !is_object($stripe_customer = c_ws_plugin__s2member_pro_stripe_utilities::get_customer($user_id, $post_vars['email'], $post_vars['first_name'], $post_vars['last_name']))
								) $global_response = array('response' => $stripe_customer, 'error' => TRUE);

								else if((!isset($stripe_customer) || !is_object($stripe_customer))
								        && !is_object($stripe_customer = c_ws_plugin__s2member_pro_stripe_utilities::set_customer_card_token($stripe_customer->id, $post_vars['card_token']))
								) $global_response = array('response' => $stripe_customer, 'error' => TRUE);

								else if(!is_object($stripe_subscription = c_ws_plugin__s2member_pro_stripe_utilities::create_customer_subscription($stripe_customer->id, $stripe_plan->id)))
									$global_response = array('response' => $stripe_subscription, 'error' => TRUE);

								else // We got what we needed here.
								{
									$new__subscr_id  = $stripe_subscription->id;
									$new__subscr_cid = $stripe_customer->id;
								}
							}
							else if(!$global_response)
							{
								$new__subscr_id  = strtoupper('free-'.uniqid());
								$new__subscr_cid = strtoupper('free-'.uniqid());
							}
							if(!$global_response)
							{
								$ipn['txn_type']  = 'subscr_signup';
								$ipn['subscr_id'] = $new__subscr_id;
								$ipn['custom']    = $post_vars['attr']['custom'];

								$ipn['txn_id'] = ($new__txn_id) ? $new__txn_id : $new__subscr_id;

								$ipn['period1'] = c_ws_plugin__s2member_paypal_utilities::paypal_pro_period1($post_vars['attr']['tp'].' '.$post_vars['attr']['tt']);
								$ipn['period3'] = c_ws_plugin__s2member_paypal_utilities::paypal_pro_period3($post_vars['attr']['rp'].' '.$post_vars['attr']['rt']);

								$ipn['mc_amount1'] = $cost_calculations['trial_total'];
								$ipn['mc_amount3'] = $cost_calculations['total'];

								$ipn['mc_gross'] = (preg_match('/^[1-9]/', $ipn['period1'])) ? $ipn['mc_amount1'] : $ipn['mc_amount3'];

								$ipn['mc_currency'] = $cost_calculations['cur'];
								$ipn['tax']         = $cost_calculations['tax'];

								$ipn['recurring'] = ($post_vars['attr']['rr']) ? '1' : '';

								$ipn['payer_email'] = $user->user_email;
								$ipn['first_name']  = $post_vars['first_name'];
								$ipn['last_name']   = $post_vars['last_name'];

								$ipn['option_name1']      = 'Referencing Customer ID';
								$ipn['option_selection1'] = $old__subscr_or_wp_id;

								$ipn['option_name2']      = 'Customer IP Address';
								$ipn['option_selection2'] = $_SERVER['REMOTE_ADDR'];

								$ipn['item_name']   = $cost_calculations['desc'];
								$ipn['item_number'] = $post_vars['attr']['level_ccaps_eotper'];

								$ipn['s2member_paypal_proxy']     = 'stripe';
								$ipn['s2member_paypal_proxy_use'] = 'pro-emails';
								$ipn['s2member_paypal_proxy_use'] .= ($ipn['mc_gross'] > 0) ? ',subscr-signup-as-subscr-payment' : '';
								$ipn['s2member_paypal_proxy_coupon']       = array('coupon_code' => $cp_attr['_coupon_code'], 'full_coupon_code' => $cp_attr['_full_coupon_code'], 'affiliate_id' => $cp_attr['_coupon_affiliate_id']);
								$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();
								$ipn['s2member_paypal_proxy_return_url']   = $post_vars['attr']['success'];

								$ipn['s2member_stripe_proxy_return_url'] = trim(c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20)));

								if($_stripe && !empty($_stripe['transaction_id']) && $stripe['response_reason_code'] === 'E00018')
								{
									update_user_option($user_id, 's2member_auto_eot_time', $start_time);
								}
								if($old__subscr_cid && $old__subscr_id && apply_filters('s2member_pro_cancels_old_rp_before_new_rp', TRUE, get_defined_vars()))
									c_ws_plugin__s2member_pro_stripe_utilities::cancel_customer_subscription($old__subscr_cid, $old__subscr_id);

								setcookie('s2member_tracking', ($s2member_tracking = c_ws_plugin__s2member_utils_encryption::encrypt($new__subscr_id)), time() + 31556926, COOKIEPATH, COOKIE_DOMAIN).
								setcookie('s2member_tracking', $s2member_tracking, time() + 31556926, SITECOOKIEPATH, COOKIE_DOMAIN).
								($_COOKIE['s2member_tracking'] = $s2member_tracking);

								$global_response = array('response' => sprintf(_x('<strong>Thank you.</strong> Your account has been updated.<br />&mdash; Please <a href="%s" rel="nofollow">log back in</a> now.', 's2member-front', 's2member'), esc_attr(wp_login_url())));

								if($post_vars['attr']['success'] && substr($ipn['s2member_stripe_proxy_return_url'], 0, 2) === substr($post_vars['attr']['success'], 0, 2) && ($custom_success_url = str_ireplace(array('%%s_response%%', '%%response%%'), array(urlencode(c_ws_plugin__s2member_utils_encryption::encrypt($global_response['response'])), urlencode($global_response['response'])), $ipn['s2member_stripe_proxy_return_url'])) && ($custom_success_url = trim(preg_replace('/%%(.+?)%%/i', '', $custom_success_url))))
									wp_redirect(c_ws_plugin__s2member_utils_urls::add_s2member_sig($custom_success_url, 's2p-v')).exit();
							}
						}
						else if($use_recurring_profile && !is_user_logged_in()) // Create a new account.
						{
							$plan_attr       = $cp_attr; // For the subscription plan.
							$plan_attr['ta'] = $cost_calculations['trial_total'];
							$plan_attr['ra'] = $cost_calculations['total'];

							$period1 = c_ws_plugin__s2member_paypal_utilities::paypal_pro_period1($post_vars['attr']['tp'].' '.$post_vars['attr']['tt']);
							$period3 = c_ws_plugin__s2member_paypal_utilities::paypal_pro_period3($post_vars['attr']['rp'].' '.$post_vars['attr']['rt']);

							$start_time = ($post_vars['attr']['tp']) ? // If there's an Initial/Trial Period; start when it's over.
								c_ws_plugin__s2member_pro_stripe_utilities::stripe_start_time($period1) : // After Trial is over.
								c_ws_plugin__s2member_pro_stripe_utilities::stripe_start_time($period3); // Or next billing cycle.

							$reference = $start_time.':'.$period1.':'.$period3.'~'.$_SERVER['HTTP_HOST'].'~'.$post_vars['attr']['level_ccaps_eotper'].'~'.$cost_calculations['cur'];

							if(!($_stripe = array()) && (!$post_vars['attr']['tp'] || ($post_vars['attr']['tp'] && $cost_calculations['trial_total'] > 0)))
							{
								$_stripe['x_type']   = 'AUTH_CAPTURE';
								$_stripe['x_method'] = 'CC';

								$_stripe['x_email']       = $post_vars['email'];
								$_stripe['x_first_name']  = $post_vars['first_name'];
								$_stripe['x_last_name']   = $post_vars['last_name'];
								$_stripe['x_customer_ip'] = $_SERVER['REMOTE_ADDR'];

								$_stripe['x_invoice_num'] = 's2-'.uniqid();
								$_stripe['x_description'] = $cost_calculations['desc'];

								$_stripe['s2_initial_payment'] = '1'; // Initial.

								$_stripe['s2_invoice'] = $post_vars['attr']['level_ccaps_eotper'];
								$_stripe['s2_custom']  = $post_vars['attr']['custom'];

								if($post_vars['attr']['tp'] && $cost_calculations['trial_total'] > 0)
								{
									$_stripe['x_tax']           = $cost_calculations['trial_tax'];
									$_stripe['x_amount']        = $cost_calculations['trial_total'];
									$_stripe['x_currency_code'] = $cost_calculations['cur'];
								}
								else // Otherwise, charge for the first Regular payment.
								{
									$_stripe['x_tax']           = $cost_calculations['tax'];
									$_stripe['x_amount']        = $cost_calculations['total'];
									$_stripe['x_currency_code'] = $cost_calculations['cur'];
								}
								$_stripe['x_card_num']  = preg_replace('/[^0-9]/', '', $post_vars['card_number']);
								$_stripe['x_exp_date']  = c_ws_plugin__s2member_pro_stripe_utilities::stripe_exp_date($post_vars['card_expiration']);
								$_stripe['x_card_code'] = $post_vars['card_verification'];

								#if (in_array($post_vars['card_type'], array('Maestro', 'Solo')))
								#	if (preg_match ('/^[0-9]{2}\/[0-9]{4}$/', $post_vars['card_start_date_issue_number']))
								#		$_stripe['x_card_start_date'] = preg_replace ('/[^0-9]/', '', $post_vars['card_start_date_issue_number']);
								#	else // Otherwise, we assume they provided an issue number instead.
								#		$_stripe['x_card_issue_number'] = $post_vars['card_start_date_issue_number'];

								$_stripe['x_address'] = $post_vars['street'];
								$_stripe['x_city']    = $post_vars['city'];
								$_stripe['x_state']   = $post_vars['state'];
								$_stripe['x_country'] = $post_vars['country'];
								$_stripe['x_zip']     = $post_vars['zip'];
							}
							if(!($stripe = array())) // Recurring Profile.
							{
								$stripe['x_method'] = 'create';

								$stripe['x_email']       = $post_vars['email'];
								$stripe['x_first_name']  = $post_vars['first_name'];
								$stripe['x_last_name']   = $post_vars['last_name'];
								$stripe['x_customer_ip'] = $_SERVER['REMOTE_ADDR'];

								$stripe['x_invoice_num'] = ($_stripe) ? $_stripe['x_invoice_num'] : 's2-'.uniqid();
								$stripe['x_description'] = $cost_calculations['desc'];
								$stripe['x_description'] .= ' (('.$reference.'))';

								$stripe['x_amount']        = $cost_calculations['total'];
								$stripe['x_currency_code'] = $cost_calculations['cur'];

								$stripe['x_start_date'] = date('Y-m-d', $start_time);

								$stripe['x_unit']              = 'days'; // Always calculated in days.
								$stripe['x_length']            = c_ws_plugin__s2member_pro_stripe_utilities::per_term_2_days($post_vars['attr']['rp'], $post_vars['attr']['rt']);
								$stripe['x_total_occurrences'] = ($post_vars['attr']['rr']) ? (($post_vars['attr']['rrt']) ? $post_vars['attr']['rrt'] : '9999') : '1';

								$stripe['x_card_num']  = preg_replace('/[^0-9]/', '', $post_vars['card_number']);
								$stripe['x_exp_date']  = c_ws_plugin__s2member_pro_stripe_utilities::stripe_exp_date($post_vars['card_expiration']);
								$stripe['x_card_code'] = $post_vars['card_verification'];

								#if (in_array($post_vars['card_type'], array('Maestro', 'Solo')))
								#	if (preg_match ('/^[0-9]{2}\/[0-9]{4}$/', $post_vars['card_start_date_issue_number']))
								#		$stripe['x_card_start_date'] = preg_replace ('/[^0-9]/', '', $post_vars['card_start_date_issue_number']);
								#	else // Otherwise, we assume they provided an issue number instead.
								#		$stripe['x_card_issue_number'] = $post_vars['card_start_date_issue_number'];

								$stripe['x_address'] = $post_vars['street'];
								$stripe['x_city']    = $post_vars['city'];
								$stripe['x_state']   = $post_vars['state'];
								$stripe['x_country'] = $post_vars['country'];
								$stripe['x_zip']     = $post_vars['zip'];
							}
							if(($cost_calculations['trial_total'] <= 0 && $cost_calculations['total'] <= 0) || !$_stripe || (($_stripe = c_ws_plugin__s2member_pro_stripe_utilities::stripe_aim_response($_stripe)) && empty($_stripe['__error'])))
							{
								if(($cost_calculations['trial_total'] <= 0 && $cost_calculations['total'] <= 0) || (($stripe = c_ws_plugin__s2member_pro_stripe_utilities::stripe_arb_response($stripe)) && (empty($stripe['__error']) || ($_stripe && !empty($_stripe['transaction_id']) && $stripe['response_reason_code'] === 'E00018'))))
								{
									// $stripe['response_reason_code'] === 'E00018' ... Card expires before start time.

									if($cost_calculations['trial_total'] <= 0 && $cost_calculations['total'] <= 0)
										$new__txn_id = $new__subscr_id = strtoupper('free-'.uniqid()); // Auto-generated value in this case.

									else // Handle this normally. The transaction/subscription IDs come from Stripe as they always do.
									{
										$new__txn_id    = ($_stripe && !empty($_stripe['transaction_id'])) ? $_stripe['transaction_id'] : FALSE;
										$new__subscr_id = ($_stripe && !empty($_stripe['transaction_id']) && $stripe['response_reason_code'] === 'E00018') ? $new__txn_id : $stripe['subscription_id'];
									}
									if(!($ipn = array())) // Simulated PayPal IPN.
									{
										$ipn['txn_type']  = 'subscr_signup';
										$ipn['subscr_id'] = $new__subscr_id;
										$ipn['custom']    = $post_vars['attr']['custom'];

										$ipn['txn_id'] = ($new__txn_id) ? $new__txn_id : $new__subscr_id;

										$ipn['period1'] = $period1;
										$ipn['period3'] = $period3;

										$ipn['mc_amount1'] = $cost_calculations['trial_total'];
										$ipn['mc_amount3'] = $cost_calculations['total'];

										$ipn['mc_gross'] = (preg_match('/^[1-9]/', $ipn['period1'])) ? $ipn['mc_amount1'] : $ipn['mc_amount3'];

										$ipn['mc_currency'] = $cost_calculations['cur'];
										$ipn['tax']         = $cost_calculations['tax'];

										$ipn['recurring'] = ($post_vars['attr']['rr']) ? '1' : '';

										$ipn['payer_email'] = $post_vars['email'];
										$ipn['first_name']  = $post_vars['first_name'];
										$ipn['last_name']   = $post_vars['last_name'];

										$ipn['option_name1']      = 'Originating Domain';
										$ipn['option_selection1'] = $_SERVER['HTTP_HOST'];

										$ipn['option_name2']      = 'Customer IP Address';
										$ipn['option_selection2'] = $_SERVER['REMOTE_ADDR'];

										$ipn['item_name']   = $cost_calculations['desc'];
										$ipn['item_number'] = $post_vars['attr']['level_ccaps_eotper'];

										$ipn['s2member_paypal_proxy']     = 'stripe';
										$ipn['s2member_paypal_proxy_use'] = 'pro-emails';
										$ipn['s2member_paypal_proxy_use'] .= ($ipn['mc_gross'] > 0) ? ',subscr-signup-as-subscr-payment' : '';
										$ipn['s2member_paypal_proxy_coupon']       = array('coupon_code' => $cp_attr['_coupon_code'], 'full_coupon_code' => $cp_attr['_full_coupon_code'], 'affiliate_id' => $cp_attr['_coupon_affiliate_id']);
										$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();
										$ipn['s2member_paypal_proxy_return_url']   = $post_vars['attr']['success'];
									}
									if(!($create_user = array())) // Build post fields for registration configuration, and then the creation array.
									{
										$_POST['ws_plugin__s2member_custom_reg_field_user_pass1'] = $post_vars['password1']; // Fake this for registration configuration.
										$_POST['ws_plugin__s2member_custom_reg_field_first_name'] = $post_vars['first_name']; // Fake this for registration configuration.
										$_POST['ws_plugin__s2member_custom_reg_field_last_name']  = $post_vars['last_name']; // Fake this for registration configuration.
										$_POST['ws_plugin__s2member_custom_reg_field_opt_in']     = @$post_vars['custom_fields']['opt_in']; // Fake this too.

										if($GLOBALS['WS_PLUGIN__']['s2member']['o']['custom_reg_fields'])
											foreach(json_decode($GLOBALS['WS_PLUGIN__']['s2member']['o']['custom_reg_fields'], TRUE) as $field)
											{
												$field_var      = preg_replace('/[^a-z0-9]/i', '_', strtolower($field['id']));
												$field_id_class = preg_replace('/_/', '-', $field_var);

												if(isset($post_vars['custom_fields'][$field_var]))
													$_POST['ws_plugin__s2member_custom_reg_field_'.$field_var] = $post_vars['custom_fields'][$field_var];
											}
										$_COOKIE['s2member_subscr_gateway'] = c_ws_plugin__s2member_utils_encryption::encrypt('stripe'); // Fake this for registration configuration.
										$_COOKIE['s2member_subscr_id']      = c_ws_plugin__s2member_utils_encryption::encrypt($new__subscr_id); // Fake this for registration configuration.
										$_COOKIE['s2member_custom']         = c_ws_plugin__s2member_utils_encryption::encrypt($post_vars['attr']['custom']); // Fake this for registration configuration.
										$_COOKIE['s2member_item_number']    = c_ws_plugin__s2member_utils_encryption::encrypt($post_vars['attr']['level_ccaps_eotper']); // Fake this too.

										$create_user['user_login'] = $post_vars['username']; // Copy this into a separate array for `wp_create_user()`.
										$create_user['user_pass']  = wp_generate_password(); // Which may fire `c_ws_plugin__s2member_registrations::generate_password()`.
										$create_user['user_email'] = $post_vars['email']; // Copy this into a separate array for `wp_create_user()`.
									}
									if($post_vars['password1'] && $post_vars['password1'] === $create_user['user_pass']) // A custom Password is being used?
									{
										if(((is_multisite() && ($new__user_id = c_ws_plugin__s2member_registrations::ms_create_existing_user($create_user['user_login'], $create_user['user_email'], $create_user['user_pass']))) || ($new__user_id = wp_create_user($create_user['user_login'], $create_user['user_pass'], $create_user['user_email']))) && !is_wp_error($new__user_id))
										{
											wp_new_user_notification($new__user_id, $create_user['user_pass']);

											$ipn['s2member_stripe_proxy_return_url'] = trim(c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20)));

											if($_stripe && !empty($_stripe['transaction_id']) && $stripe['response_reason_code'] === 'E00018')
												update_user_option($new__user_id, 's2member_auto_eot_time', $start_time);

											$global_response = array('response' => sprintf(_x('<strong>Thank you.</strong> Your account has been approved.<br />&mdash; Please <a href="%s" rel="nofollow">login</a>.', 's2member-front', 's2member'), esc_attr(wp_login_url())));

											if($post_vars['attr']['success'] && substr($ipn['s2member_stripe_proxy_return_url'], 0, 2) === substr($post_vars['attr']['success'], 0, 2) && ($custom_success_url = str_ireplace(array('%%s_response%%', '%%response%%'), array(urlencode(c_ws_plugin__s2member_utils_encryption::encrypt($global_response['response'])), urlencode($global_response['response'])), $ipn['s2member_stripe_proxy_return_url'])) && ($custom_success_url = trim(preg_replace('/%%(.+?)%%/i', '', $custom_success_url))))
												wp_redirect(c_ws_plugin__s2member_utils_urls::add_s2member_sig($custom_success_url, 's2p-v')).exit();
										}
										else // Else, an error reponse should be given.
										{
											c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

											$global_response = array('response' => _x('<strong>Oops.</strong> A slight problem. Please contact Support for assistance.', 's2member-front', 's2member'), 'error' => TRUE);
										}
									}
									else // Otherwise, they'll need to check their email for the auto-generated Password.
									{
										if(((is_multisite() && ($new__user_id = c_ws_plugin__s2member_registrations::ms_create_existing_user($create_user['user_login'], $create_user['user_email'], $create_user['user_pass']))) || ($new__user_id = wp_create_user($create_user['user_login'], $create_user['user_pass'], $create_user['user_email']))) && !is_wp_error($new__user_id))
										{
											update_user_option($new__user_id, 'default_password_nag', TRUE, TRUE); // Password nag.
											wp_new_user_notification($new__user_id, $create_user['user_pass']);

											$ipn['s2member_stripe_proxy_return_url'] = trim(c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20)));

											if($_stripe && !empty($_stripe['transaction_id']) && $stripe['response_reason_code'] === 'E00018')
												update_user_option($new__user_id, 's2member_auto_eot_time', $start_time);

											$global_response = array('response' => _x('<strong>Thank you.</strong> Your account has been approved.<br />&mdash; You\'ll receive an email momentarily.', 's2member-front', 's2member'));

											if($post_vars['attr']['success'] && substr($ipn['s2member_stripe_proxy_return_url'], 0, 2) === substr($post_vars['attr']['success'], 0, 2) && ($custom_success_url = str_ireplace(array('%%s_response%%', '%%response%%'), array(urlencode(c_ws_plugin__s2member_utils_encryption::encrypt($global_response['response'])), urlencode($global_response['response'])), $ipn['s2member_stripe_proxy_return_url'])) && ($custom_success_url = trim(preg_replace('/%%(.+?)%%/i', '', $custom_success_url))))
												wp_redirect(c_ws_plugin__s2member_utils_urls::add_s2member_sig($custom_success_url, 's2p-v')).exit();
										}
										else // Else, an error reponse should be given.
										{
											c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

											$global_response = array('response' => _x('<strong>Oops.</strong> A slight problem. Please contact Support for assistance.', 's2member-front', 's2member'), 'error' => TRUE);
										}
									}
								}
								else // Else, an error.
								{
									$global_response = array('response' => $stripe['__error'], 'error' => TRUE);
								}
							}
							else // Else, an error.
							{
								$global_response = array('response' => $_stripe['__error'], 'error' => TRUE);
							}
						}
						else if(!$use_recurring_profile && is_user_logged_in() && is_object($user = wp_get_current_user()) && ($user_id = $user->ID))
						{
							update_user_meta($user_id, 'first_name', $post_vars['first_name']).update_user_meta($user_id, 'last_name', $post_vars['last_name']);

							if(!($stripe = array())) // Direct payments.
							{
								$stripe['x_type']   = 'AUTH_CAPTURE';
								$stripe['x_method'] = 'CC';

								$stripe['x_email']       = $user->user_email;
								$stripe['x_first_name']  = $post_vars['first_name'];
								$stripe['x_last_name']   = $post_vars['last_name'];
								$stripe['x_customer_ip'] = $_SERVER['REMOTE_ADDR'];

								$stripe['x_invoice_num'] = 's2-'.uniqid();
								$stripe['x_description'] = $cost_calculations['desc'];

								$stripe['s2_invoice'] = $post_vars['attr']['level_ccaps_eotper'];
								$stripe['s2_custom']  = $post_vars['attr']['custom'];

								$stripe['x_tax']           = $cost_calculations['tax'];
								$stripe['x_amount']        = $cost_calculations['total'];
								$stripe['x_currency_code'] = $cost_calculations['cur'];

								$stripe['x_card_num']  = preg_replace('/[^0-9]/', '', $post_vars['card_number']);
								$stripe['x_exp_date']  = c_ws_plugin__s2member_pro_stripe_utilities::stripe_exp_date($post_vars['card_expiration']);
								$stripe['x_card_code'] = $post_vars['card_verification'];

								#if (in_array($post_vars['card_type'], array('Maestro', 'Solo')))
								#	if (preg_match ('/^[0-9]{2}\/[0-9]{4}$/', $post_vars['card_start_date_issue_number']))
								#		$stripe['x_card_start_date'] = preg_replace ('/[^0-9]/', '', $post_vars['card_start_date_issue_number']);
								#	else // Otherwise, we assume they provided an issue number instead.
								#		$stripe['x_card_issue_number'] = $post_vars['card_start_date_issue_number'];

								$stripe['x_address'] = $post_vars['street'];
								$stripe['x_city']    = $post_vars['city'];
								$stripe['x_state']   = $post_vars['state'];
								$stripe['x_country'] = $post_vars['country'];
								$stripe['x_zip']     = $post_vars['zip'];
							}
							if($cost_calculations['total'] <= 0 || (($stripe = c_ws_plugin__s2member_pro_stripe_utilities::stripe_aim_response($stripe)) && empty($stripe['__error'])))
							{
								$old__subscr_or_wp_id = c_ws_plugin__s2member_utils_users::get_user_subscr_or_wp_id();
								$old__subscr_id       = get_user_option('s2member_subscr_id');

								if($cost_calculations['total'] <= 0)
									$new__subscr_id = $new__txn_id = strtoupper('free-'.uniqid()); // Auto-generated value in this case.
								else $new__subscr_id = $new__txn_id = $stripe['transaction_id'];

								if(!($ipn = array())) // Simulated PayPal IPN.
								{
									$ipn['txn_type'] = 'web_accept';
									$ipn['txn_id']   = $new__subscr_id;
									$ipn['custom']   = $post_vars['attr']['custom'];

									$ipn['mc_gross']    = $cost_calculations['total'];
									$ipn['mc_currency'] = $cost_calculations['cur'];
									$ipn['tax']         = $cost_calculations['tax'];

									$ipn['payer_email'] = $user->user_email;
									$ipn['first_name']  = $post_vars['first_name'];
									$ipn['last_name']   = $post_vars['last_name'];

									$ipn['option_name1']      = 'Referencing Customer ID';
									$ipn['option_selection1'] = $old__subscr_or_wp_id;

									$ipn['option_name2']      = 'Customer IP Address';
									$ipn['option_selection2'] = $_SERVER['REMOTE_ADDR'];

									$ipn['item_name']   = $cost_calculations['desc'];
									$ipn['item_number'] = $post_vars['attr']['level_ccaps_eotper'];

									$ipn['s2member_paypal_proxy']              = 'stripe';
									$ipn['s2member_paypal_proxy_use']          = 'pro-emails';
									$ipn['s2member_paypal_proxy_coupon']       = array('coupon_code' => $cp_attr['_coupon_code'], 'full_coupon_code' => $cp_attr['_full_coupon_code'], 'affiliate_id' => $cp_attr['_coupon_affiliate_id']);
									$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();
									$ipn['s2member_paypal_proxy_return_url']   = $post_vars['attr']['success'];

									$ipn['s2member_stripe_proxy_return_url'] = trim(c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20)));
								}
								if(!$is_independent_ccaps_sale) // Independent?
									if(($stripe = array('x_method' => 'cancel')) && ($stripe['x_subscription_id'] = $old__subscr_id) && apply_filters('s2member_pro_cancels_old_rp_before_new_rp', TRUE, get_defined_vars()))
									{
										c_ws_plugin__s2member_pro_stripe_utilities::stripe_arb_response($stripe);
									}
								setcookie('s2member_tracking', ($s2member_tracking = c_ws_plugin__s2member_utils_encryption::encrypt($new__subscr_id)), time() + 31556926, COOKIEPATH, COOKIE_DOMAIN).setcookie('s2member_tracking', $s2member_tracking, time() + 31556926, SITECOOKIEPATH, COOKIE_DOMAIN).($_COOKIE['s2member_tracking'] = $s2member_tracking);

								$global_response = array('response' => sprintf(_x('<strong>Thank you.</strong> Your account has been updated.<br />&mdash; Please <a href="%s" rel="nofollow">log back in</a> now.', 's2member-front', 's2member'), esc_attr(wp_login_url())));

								if($post_vars['attr']['success'] && substr($ipn['s2member_stripe_proxy_return_url'], 0, 2) === substr($post_vars['attr']['success'], 0, 2) && ($custom_success_url = str_ireplace(array('%%s_response%%', '%%response%%'), array(urlencode(c_ws_plugin__s2member_utils_encryption::encrypt($global_response['response'])), urlencode($global_response['response'])), $ipn['s2member_stripe_proxy_return_url'])) && ($custom_success_url = trim(preg_replace('/%%(.+?)%%/i', '', $custom_success_url))))
									wp_redirect(c_ws_plugin__s2member_utils_urls::add_s2member_sig($custom_success_url, 's2p-v')).exit();
							}
							else // Else, an error.
							{
								$global_response = array('response' => $stripe['__error'], 'error' => TRUE);
							}
						}
						else if(!$use_recurring_profile && !is_user_logged_in()) // Create a new account.
						{
							if(!($stripe = array())) // Direct payments.
							{
								$stripe['x_type']   = 'AUTH_CAPTURE';
								$stripe['x_method'] = 'CC';

								$stripe['x_email']       = $post_vars['email'];
								$stripe['x_first_name']  = $post_vars['first_name'];
								$stripe['x_last_name']   = $post_vars['last_name'];
								$stripe['x_customer_ip'] = $_SERVER['REMOTE_ADDR'];

								$stripe['x_invoice_num'] = 's2-'.uniqid();
								$stripe['x_description'] = $cost_calculations['desc'];

								$stripe['s2_invoice'] = $post_vars['attr']['level_ccaps_eotper'];
								$stripe['s2_custom']  = $post_vars['attr']['custom'];

								$stripe['x_tax']           = $cost_calculations['tax'];
								$stripe['x_amount']        = $cost_calculations['total'];
								$stripe['x_currency_code'] = $cost_calculations['cur'];

								$stripe['x_card_num']  = preg_replace('/[^0-9]/', '', $post_vars['card_number']);
								$stripe['x_exp_date']  = c_ws_plugin__s2member_pro_stripe_utilities::stripe_exp_date($post_vars['card_expiration']);
								$stripe['x_card_code'] = $post_vars['card_verification'];

								#if (in_array($post_vars['card_type'], array('Maestro', 'Solo')))
								#	if (preg_match ('/^[0-9]{2}\/[0-9]{4}$/', $post_vars['card_start_date_issue_number']))
								#		$stripe['x_card_start_date'] = preg_replace ('/[^0-9]/', '', $post_vars['card_start_date_issue_number']);
								#	else // Otherwise, we assume they provided an issue number instead.
								#		$stripe['x_card_issue_number'] = $post_vars['card_start_date_issue_number'];

								$stripe['x_address'] = $post_vars['street'];
								$stripe['x_city']    = $post_vars['city'];
								$stripe['x_state']   = $post_vars['state'];
								$stripe['x_country'] = $post_vars['country'];
								$stripe['x_zip']     = $post_vars['zip'];
							}
							if($cost_calculations['total'] <= 0 || (($stripe = c_ws_plugin__s2member_pro_stripe_utilities::stripe_aim_response($stripe)) && empty($stripe['__error'])))
							{
								if($cost_calculations['total'] <= 0)
									$new__subscr_id = $new__txn_id = strtoupper('free-'.uniqid()); // Auto-generated value in this case.
								else $new__subscr_id = $new__txn_id = $stripe['transaction_id'];

								if(!($ipn = array())) // Simulated PayPal IPN.
								{
									$ipn['txn_type'] = 'web_accept';
									$ipn['txn_id']   = $new__subscr_id;
									$ipn['custom']   = $post_vars['attr']['custom'];

									$ipn['mc_gross']    = $cost_calculations['total'];
									$ipn['mc_currency'] = $cost_calculations['cur'];
									$ipn['tax']         = $cost_calculations['tax'];

									$ipn['payer_email'] = $post_vars['email'];
									$ipn['first_name']  = $post_vars['first_name'];
									$ipn['last_name']   = $post_vars['last_name'];

									$ipn['option_name1']      = 'Originating Domain';
									$ipn['option_selection1'] = $_SERVER['HTTP_HOST'];

									$ipn['option_name2']      = 'Customer IP Address';
									$ipn['option_selection2'] = $_SERVER['REMOTE_ADDR'];

									$ipn['item_name']   = $cost_calculations['desc'];
									$ipn['item_number'] = $post_vars['attr']['level_ccaps_eotper'];

									$ipn['s2member_paypal_proxy']              = 'stripe';
									$ipn['s2member_paypal_proxy_use']          = 'pro-emails';
									$ipn['s2member_paypal_proxy_coupon']       = array('coupon_code' => $cp_attr['_coupon_code'], 'full_coupon_code' => $cp_attr['_full_coupon_code'], 'affiliate_id' => $cp_attr['_coupon_affiliate_id']);
									$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();
									$ipn['s2member_paypal_proxy_return_url']   = $post_vars['attr']['success'];
								}
								if(!($create_user = array())) // Build post fields for registration configuration, and then the creation array.
								{
									$_POST['ws_plugin__s2member_custom_reg_field_user_pass1'] = $post_vars['password1']; // Fake this for registration configuration.
									$_POST['ws_plugin__s2member_custom_reg_field_first_name'] = $post_vars['first_name']; // Fake this for registration configuration.
									$_POST['ws_plugin__s2member_custom_reg_field_last_name']  = $post_vars['last_name']; // Fake this for registration configuration.
									$_POST['ws_plugin__s2member_custom_reg_field_opt_in']     = @$post_vars['custom_fields']['opt_in']; // Fake this too.

									if($GLOBALS['WS_PLUGIN__']['s2member']['o']['custom_reg_fields'])
										foreach(json_decode($GLOBALS['WS_PLUGIN__']['s2member']['o']['custom_reg_fields'], TRUE) as $field)
										{
											$field_var      = preg_replace('/[^a-z0-9]/i', '_', strtolower($field['id']));
											$field_id_class = preg_replace('/_/', '-', $field_var);

											if(isset($post_vars['custom_fields'][$field_var]))
												$_POST['ws_plugin__s2member_custom_reg_field_'.$field_var] = $post_vars['custom_fields'][$field_var];
										}
									$_COOKIE['s2member_subscr_gateway'] = c_ws_plugin__s2member_utils_encryption::encrypt('stripe'); // Fake this for registration configuration.
									$_COOKIE['s2member_subscr_id']      = c_ws_plugin__s2member_utils_encryption::encrypt($new__subscr_id); // Fake this for registration configuration.
									$_COOKIE['s2member_custom']         = c_ws_plugin__s2member_utils_encryption::encrypt($post_vars['attr']['custom']); // Fake this for registration configuration.
									$_COOKIE['s2member_item_number']    = c_ws_plugin__s2member_utils_encryption::encrypt($post_vars['attr']['level_ccaps_eotper']); // Fake this too.

									$create_user['user_login'] = $post_vars['username']; // Copy this into a separate array for `wp_create_user()`.
									$create_user['user_pass']  = wp_generate_password(); // Which may fire `c_ws_plugin__s2member_registrations::generate_password()`.
									$create_user['user_email'] = $post_vars['email']; // Copy this into a separate array for `wp_create_user()`.
								}
								if($post_vars['password1'] && $post_vars['password1'] === $create_user['user_pass']) // A custom Password is being used?
								{
									if(((is_multisite() && ($new__user_id = c_ws_plugin__s2member_registrations::ms_create_existing_user($create_user['user_login'], $create_user['user_email'], $create_user['user_pass']))) || ($new__user_id = wp_create_user($create_user['user_login'], $create_user['user_pass'], $create_user['user_email']))) && !is_wp_error($new__user_id))
									{
										wp_new_user_notification($new__user_id, $create_user['user_pass']);

										$ipn['s2member_stripe_proxy_return_url'] = trim(c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20)));

										$global_response = array('response' => sprintf(_x('<strong>Thank you.</strong> Your account has been approved.<br />&mdash; Please <a href="%s" rel="nofollow">login</a>.', 's2member-front', 's2member'), esc_attr(wp_login_url())));

										if($post_vars['attr']['success'] && substr($ipn['s2member_stripe_proxy_return_url'], 0, 2) === substr($post_vars['attr']['success'], 0, 2) && ($custom_success_url = str_ireplace(array('%%s_response%%', '%%response%%'), array(urlencode(c_ws_plugin__s2member_utils_encryption::encrypt($global_response['response'])), urlencode($global_response['response'])), $ipn['s2member_stripe_proxy_return_url'])) && ($custom_success_url = trim(preg_replace('/%%(.+?)%%/i', '', $custom_success_url))))
											wp_redirect(c_ws_plugin__s2member_utils_urls::add_s2member_sig($custom_success_url, 's2p-v')).exit();
									}
									else // Else, an error reponse should be given.
									{
										c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

										$global_response = array('response' => _x('<strong>Oops.</strong> A slight problem. Please contact Support for assistance.', 's2member-front', 's2member'), 'error' => TRUE);
									}
								}
								else // Otherwise, they'll need to check their email for the auto-generated Password.
								{
									if(((is_multisite() && ($new__user_id = c_ws_plugin__s2member_registrations::ms_create_existing_user($create_user['user_login'], $create_user['user_email'], $create_user['user_pass']))) || ($new__user_id = wp_create_user($create_user['user_login'], $create_user['user_pass'], $create_user['user_email']))) && !is_wp_error($new__user_id))
									{
										update_user_option($new__user_id, 'default_password_nag', TRUE, TRUE); // Password nag.
										wp_new_user_notification($new__user_id, $create_user['user_pass']);

										$ipn['s2member_stripe_proxy_return_url'] = trim(c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20)));

										$global_response = array('response' => _x('<strong>Thank you.</strong> Your account has been approved.<br />&mdash; You\'ll receive an email momentarily.', 's2member-front', 's2member'));

										if($post_vars['attr']['success'] && substr($ipn['s2member_stripe_proxy_return_url'], 0, 2) === substr($post_vars['attr']['success'], 0, 2) && ($custom_success_url = str_ireplace(array('%%s_response%%', '%%response%%'), array(urlencode(c_ws_plugin__s2member_utils_encryption::encrypt($global_response['response'])), urlencode($global_response['response'])), $ipn['s2member_stripe_proxy_return_url'])) && ($custom_success_url = trim(preg_replace('/%%(.+?)%%/i', '', $custom_success_url))))
											wp_redirect(c_ws_plugin__s2member_utils_urls::add_s2member_sig($custom_success_url, 's2p-v')).exit();
									}
									else // Else, an error reponse should be given.
									{
										c_ws_plugin__s2member_utils_urls::remote(site_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

										$global_response = array('response' => _x('<strong>Oops.</strong> A slight problem. Please contact Support for assistance.', 's2member-front', 's2member'), 'error' => TRUE);
									}
								}
							}
							else $global_response = array('response' => $stripe['__error'], 'error' => TRUE);
						}
						else $global_response = array('response' => _x('<strong>Unknown error.</strong> Please contact Support for assistance.', 's2member-front', 's2member'), 'error' => TRUE);
					}
					else // Input form field validation errors.
						$global_response = $form_submission_validation_errors;
				}
			}
		}
	}
}