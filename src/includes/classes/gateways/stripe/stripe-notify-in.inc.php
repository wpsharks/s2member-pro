<?php
// @codingStandardsIgnoreFile
/**
 * Stripe Silent Post *(aka: IPN)* (inner processing routines).
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
 * @package s2Member\Stripe
 * @since 140617
 */
if(!defined('WPINC')) // MUST have WordPress.
	exit ('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_pro_stripe_notify_in'))
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
		 * Handles Stripe Webhook/IPN event processing.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 *
		 * @attaches-to ``add_action('init');``
		 */
		public static function stripe_notify()
		{
			global $current_site, $current_blog;

			if(!empty($_GET['s2member_pro_stripe_notify']) && $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key'])
			{
				$stripe = array(); // Initialize array of Webhook/IPN event data and s2Member log details.
				@ignore_user_abort(TRUE); // Continue processing even if/when connection is broken.

				if(!class_exists('Stripe'))
					require_once dirname(__FILE__).'/stripe-sdk/lib/Stripe.php';
				Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);

				if(is_object($event = c_ws_plugin__s2member_pro_stripe_utilities::get_event()) && ($stripe['event'] = $event))
				{
					switch($event->type)
					{
						case 'invoice.payment_succeeded': // Subscription payments.

							if(!empty($event->data->object)
							   && ($stripe_invoice = $event->data->object) instanceof Stripe_Invoice
							   && !empty($stripe_invoice->customer) && !empty($stripe_invoice->subscription)
							   && ($stripe_invoice_total = number_format(c_ws_plugin__s2member_pro_stripe_utilities::cents_to_dollar_amount($stripe_invoice->total, $stripe_invoice->currency), 2, '.', '')) > 0
							   && is_object($stripe_subscription = c_ws_plugin__s2member_pro_stripe_utilities::get_customer_subscription($stripe_invoice->customer, $stripe_invoice->subscription))
							   && ($ipn_signup_vars = c_ws_plugin__s2member_utils_users::get_user_ipn_signup_vars(0, $stripe_subscription->id))
							)
							{
								$processing = TRUE;

								$ipn['txn_type']   = 'subscr_payment';
								$ipn['txn_id']     = $stripe_invoice->id;
								$ipn['txn_cid']    = $ipn_signup_vars['subscr_cid'];
								$ipn['subscr_cid'] = $ipn_signup_vars['subscr_cid'];
								$ipn['subscr_id']  = $ipn_signup_vars['subscr_id'];
								$ipn['custom']     = $ipn_signup_vars['custom'];

								$ipn['mc_gross']    = $stripe_invoice_total;
								$ipn['mc_currency'] = strtoupper($stripe_invoice->currency);
								$ipn['tax']         = number_format(0, 2, '.', '');

								$ipn['period1'] = $ipn_signup_vars['period1'];
								$ipn['period3'] = $ipn_signup_vars['period3'];

								$ipn['payer_email'] = $ipn_signup_vars['payer_email'];
								$ipn['first_name']  = $ipn_signup_vars['first_name'];
								$ipn['last_name']   = $ipn_signup_vars['last_name'];

								$ipn['option_name1']      = $ipn_signup_vars['option_name1'];
								$ipn['option_selection1'] = $ipn_signup_vars['option_selection1'];

								$ipn['option_name2']      = $ipn_signup_vars['option_name2'];
								$ipn['option_selection2'] = $ipn_signup_vars['option_selection2'];

								$ipn['item_name']   = $ipn_signup_vars['item_name'];
								$ipn['item_number'] = $ipn_signup_vars['item_number'];

								$ipn['s2member_paypal_proxy']              = 'stripe';
								$ipn['s2member_paypal_proxy_use']          = 'pro-emails';
								$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

								c_ws_plugin__s2member_utils_urls::remote(home_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');

								if(($maybe_end_subscription = self::_maybe_end_subscription_after_payment($stripe_invoice->customer, $stripe_subscription)))
									$stripe['s2member_log'][] = $maybe_end_subscription;

								$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor as `txn_type` (`'.$ipn['txn_type'].'`).';
								$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
							}
							break; // Break switch handler.

						case 'invoice.payment_failed': // Subscription payment failures.

							if(!empty($event->data->object)
							   && ($stripe_invoice = $event->data->object) instanceof Stripe_Invoice
							   && !empty($stripe_invoice->customer) && !empty($stripe_invoice->subscription)
							   && ($stripe_invoice_total = number_format(c_ws_plugin__s2member_pro_stripe_utilities::cents_to_dollar_amount($stripe_invoice->total, $stripe_invoice->currency), 2, '.', '')) > 0
							   && is_object($stripe_subscription = c_ws_plugin__s2member_pro_stripe_utilities::get_customer_subscription($stripe_invoice->customer, $stripe_invoice->subscription))
							   && ($ipn_signup_vars = c_ws_plugin__s2member_utils_users::get_user_ipn_signup_vars(0, $stripe_subscription->id))
							)
							{
								$processing = TRUE;

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');

								if(($maybe_end_subscription = self::_maybe_end_subscription_after_payment($stripe_invoice->customer, $stripe_subscription)))
									$stripe['s2member_log'][] = $maybe_end_subscription;

								$stripe['s2member_log'][] = 'Ignoring `'.$event->type.'`. s2Member does NOT respond to individual payment failures; only to subscription cancellations.';
								$stripe['s2member_log'][] = 'You may control the behavior(s) associated w/ subscription payment failures from your Stripe Dashboard please.';
							}
							break; // Break switch handler.

						case 'customer.deleted': // Customer deletions.

							if(!empty($event->data->object)
							   && ($stripe_customer = $event->data->object) instanceof Stripe_Customer
							   && ($ipn_signup_vars = c_ws_plugin__s2member_utils_users::get_user_ipn_signup_vars(0, $stripe_customer->id))
							)
							{
								$processing = TRUE;

								$ipn['txn_type']   = 'subscr_eot';
								$ipn['subscr_cid'] = $ipn_signup_vars['subscr_cid'];
								$ipn['subscr_id']  = $ipn_signup_vars['subscr_id'];
								$ipn['custom']     = $ipn_signup_vars['custom'];

								$ipn['period1'] = $ipn_signup_vars['period1'];
								$ipn['period3'] = $ipn_signup_vars['period3'];

								$ipn['payer_email'] = $ipn_signup_vars['payer_email'];
								$ipn['first_name']  = $ipn_signup_vars['first_name'];
								$ipn['last_name']   = $ipn_signup_vars['last_name'];

								$ipn['option_name1']      = $ipn_signup_vars['option_name1'];
								$ipn['option_selection1'] = $ipn_signup_vars['option_selection1'];

								$ipn['option_name2']      = $ipn_signup_vars['option_name2'];
								$ipn['option_selection2'] = $ipn_signup_vars['option_selection2'];

								$ipn['item_name']   = $ipn_signup_vars['item_name'];
								$ipn['item_number'] = $ipn_signup_vars['item_number'];

								$ipn['s2member_paypal_proxy']              = 'stripe';
								$ipn['s2member_paypal_proxy_use']          = 'pro-emails';
								$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

								c_ws_plugin__s2member_utils_urls::remote(home_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor as `txn_type` (`'.$ipn['txn_type'].'`).';
								$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
							}
							break; // Break switch handler.

						case 'customer.subscription.deleted': // Customer subscription deletion.

							if(!empty($event->data->object)
							   && ($stripe_subscription = $event->data->object) instanceof Stripe_Subscription
							   && ($ipn_signup_vars = c_ws_plugin__s2member_utils_users::get_user_ipn_signup_vars(0, $stripe_subscription->id))
							)
							{
								$processing = TRUE;

								$ipn['txn_type']   = 'subscr_eot';
								$ipn['subscr_cid'] = $ipn_signup_vars['subscr_cid'];
								$ipn['subscr_id']  = $ipn_signup_vars['subscr_id'];
								$ipn['custom']     = $ipn_signup_vars['custom'];

								$ipn['period1'] = $ipn_signup_vars['period1'];
								$ipn['period3'] = $ipn_signup_vars['period3'];

								$ipn['payer_email'] = $ipn_signup_vars['payer_email'];
								$ipn['first_name']  = $ipn_signup_vars['first_name'];
								$ipn['last_name']   = $ipn_signup_vars['last_name'];

								$ipn['option_name1']      = $ipn_signup_vars['option_name1'];
								$ipn['option_selection1'] = $ipn_signup_vars['option_selection1'];

								$ipn['option_name2']      = $ipn_signup_vars['option_name2'];
								$ipn['option_selection2'] = $ipn_signup_vars['option_selection2'];

								$ipn['item_name']   = $ipn_signup_vars['item_name'];
								$ipn['item_number'] = $ipn_signup_vars['item_number'];

								$ipn['s2member_paypal_proxy']              = 'stripe';
								$ipn['s2member_paypal_proxy_use']          = 'pro-emails';
								$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

								c_ws_plugin__s2member_utils_urls::remote(home_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor as `txn_type` (`'.$ipn['txn_type'].'`).';
								$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
							}
							break; // Break switch handler.

						case 'charge.refunded': // Customer refund (partial or full).

							if(!empty($event->data->object)
							   && ($stripe_charge = $event->data->object) instanceof Stripe_Charge
							   && !empty($stripe_charge->amount_refunded) && !empty($stripe_charge->customer)
							   && ($ipn_signup_vars = c_ws_plugin__s2member_utils_users::get_user_ipn_signup_vars(0, $stripe_charge->customer))
							)
							{
								$processing = TRUE;

								$ipn['payment_status'] = 'refunded';
								$ipn['subscr_cid'] = $ipn_signup_vars['subscr_cid'];
								$ipn['subscr_id']  = $ipn_signup_vars['subscr_id'];
								$ipn['parent_txn_id'] = $ipn_signup_vars['subscr_id'];

								$ipn['custom'] = $ipn_signup_vars['custom'];

								$ipn['mc_fee']      = '-'.number_format('0.00', 2, '.', '');
								$ipn['mc_gross']    = '-'.number_format(c_ws_plugin__s2member_pro_stripe_utilities::cents_to_dollar_amount(abs($stripe_charge->amount_refunded), $stripe_charge->currency), 2, '.', '');
								$ipn['mc_currency'] = strtoupper($stripe_charge->currency);
								$ipn['tax']         = '-'.number_format('0.00', 2, '.', '');

								$ipn['period1'] = $ipn_signup_vars['period1'];
								$ipn['period3'] = $ipn_signup_vars['period3'];

								$ipn['payer_email'] = $ipn_signup_vars['payer_email'];
								$ipn['first_name']  = $ipn_signup_vars['first_name'];
								$ipn['last_name']   = $ipn_signup_vars['last_name'];

								$ipn['option_name1']      = $ipn_signup_vars['option_name1'];
								$ipn['option_selection1'] = $ipn_signup_vars['option_selection1'];

								$ipn['option_name2']      = $ipn_signup_vars['option_name2'];
								$ipn['option_selection2'] = $ipn_signup_vars['option_selection2'];

								$ipn['item_name']   = $ipn_signup_vars['item_name'];
								$ipn['item_number'] = $ipn_signup_vars['item_number'];

								$ipn['s2member_paypal_proxy']              = 'stripe';
								$ipn['s2member_paypal_proxy_use']          = 'pro-emails';
								$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

								c_ws_plugin__s2member_utils_urls::remote(home_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor.';
								$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
							}
							break; // Break switch handler.

						case 'charge.dispute.created': // Customer dispute (chargeback).

							if(!empty($event->data->object->charge)
							   && ($stripe_charge = c_ws_plugin__s2member_pro_stripe_utilities::get_charge($event->data->object->charge)) instanceof Stripe_Charge
							   && !empty($stripe_charge->customer) // The charge that is being disputed must be associated with a customer that we know of.
							   && ($ipn_signup_vars = c_ws_plugin__s2member_utils_users::get_user_ipn_signup_vars(0, $stripe_charge->customer))
							)
							{
								$processing = TRUE;

								$ipn['payment_status'] = 'reversed';
								$ipn['subscr_cid'] = $ipn_signup_vars['subscr_cid'];
								$ipn['subscr_id']  = $ipn_signup_vars['subscr_id'];
								$ipn['parent_txn_id'] = $ipn_signup_vars['subscr_id'];

								$ipn['custom'] = $ipn_signup_vars['custom'];

								$ipn['mc_fee']      = '-'.number_format('0.00', 2, '.', '');
								$ipn['mc_gross']    = '-'.number_format(c_ws_plugin__s2member_pro_stripe_utilities::cents_to_dollar_amount(abs($stripe_charge->amount), $stripe_charge->currency), 2, '.', '');
								$ipn['mc_currency'] = strtoupper($stripe_charge->currency);
								$ipn['tax']         = '-'.number_format('0.00', 2, '.', '');

								$ipn['period1'] = $ipn_signup_vars['period1'];
								$ipn['period3'] = $ipn_signup_vars['period3'];

								$ipn['payer_email'] = $ipn_signup_vars['payer_email'];
								$ipn['first_name']  = $ipn_signup_vars['first_name'];
								$ipn['last_name']   = $ipn_signup_vars['last_name'];

								$ipn['option_name1']      = $ipn_signup_vars['option_name1'];
								$ipn['option_selection1'] = $ipn_signup_vars['option_selection1'];

								$ipn['option_name2']      = $ipn_signup_vars['option_name2'];
								$ipn['option_selection2'] = $ipn_signup_vars['option_selection2'];

								$ipn['item_name']   = $ipn_signup_vars['item_name'];
								$ipn['item_number'] = $ipn_signup_vars['item_number'];

								$ipn['s2member_paypal_proxy']              = 'stripe';
								$ipn['s2member_paypal_proxy_use']          = 'pro-emails';
								$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

								c_ws_plugin__s2member_utils_urls::remote(home_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor.';
								$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
							}
							break; // Break switch handler.
					}
					if(empty($processing)) $stripe['s2member_log'][] = 'Ignoring this Webhook/IPN. The event does NOT require any action on the part of s2Member.';
				}
				else // Extensive log reporting here. This is an area where many site owners find trouble. Depending on server configuration; remote HTTPS connections may fail.
				{
					$stripe['s2member_log'][] = 'Unable to verify Webhook/IPN event ID. This is most likely related to an invalid Stripe configuration. Please check: s2Member → Stripe Options.';
					$stripe['s2member_log'][] = 'If you\'re absolutely SURE that your Stripe configuration is valid, you may want to run some tests on your server, just to be sure \$_POST variables (and php://input) are populated; and that your server is able to connect to Stripe over an HTTPS connection.';
					$stripe['s2member_log'][] = 's2Member uses the Stripe SDK for remote connections; which relies upon the cURL extension for PHP. Please make sure that your installation of PHP has the cURL extension; and that it\'s configured together with OpenSSL for HTTPS communication.';
					$stripe['s2member_log'][] = var_export($_REQUEST, TRUE)."\n".var_export(json_decode(@file_get_contents('php://input')), TRUE);
				}
				c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', $stripe);

				status_header(200);
				header('Content-Type: text/plain; charset=UTF-8');
				while(@ob_end_clean()); exit();
			}
		}

		/**
		 * Handles Stripe Webhook/IPN event processing.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 *
		 * @param string              $customer_id Customer's ID in Stripe.
		 * @param Stripe_Subscription $stripe_subscription Customer's subscription object instance.
		 *
		 * @return string Additional log entry if ending subscription; else an empty string.
		 */
		public static function _maybe_end_subscription_after_payment($customer_id, $stripe_subscription)
		{
			if(!$customer_id || !($stripe_subscription instanceof Stripe_Subscription))
				return ''; // Not possible.

			if(isset($stripe_subscription->plan->metadata->recurring)
			   && !filter_var($stripe_subscription->plan->metadata->recurring, FILTER_VALIDATE_BOOLEAN)
			   && strtolower($stripe_subscription->status) !== 'trialing' // Past the initial/trial period?
			)
			{
				c_ws_plugin__s2member_pro_stripe_utilities::cancel_customer_subscription($customer_id, $stripe_subscription->id);

				return 'Subscription `'.$stripe_subscription->id.'` has `plan->metadata->recurring=false`.'.
				       ' Auto-cancelling subscription after current period ends.';
			}
			else if(isset($stripe_subscription->plan->metadata->recurring)
			        && filter_var($stripe_subscription->plan->metadata->recurring, FILTER_VALIDATE_BOOLEAN)
			        && isset($stripe_subscription->plan->metadata->recurring_times) && (integer)$stripe_subscription->plan->metadata->recurring_times === 1
			        && strtolower($stripe_subscription->status) !== 'trialing' // Past the initial/trial period?
			)
			{
				c_ws_plugin__s2member_pro_stripe_utilities::cancel_customer_subscription($customer_id, $stripe_subscription->id);

				return 'Subscription `'.$stripe_subscription->id.'` has `plan->metadata->recurring=true` `plan->metadata->recurring_times=1`.'.
				       ' Auto-cancelling subscription after current period ends. This was the last billing cycle.';
			}
			else if(isset($stripe_subscription->plan->metadata->recurring)
			        && filter_var($stripe_subscription->plan->metadata->recurring, FILTER_VALIDATE_BOOLEAN)
			        && isset($stripe_subscription->plan->metadata->recurring_times) && $stripe_subscription->plan->metadata->recurring_times > 0
			        && strtolower($stripe_subscription->plan->interval) === 'day' // s2Member configures all plans with a day-based interval.
			        && strtolower($stripe_subscription->status) !== 'trialing' // Past the initial/trial period?

			        && ($rr_start_time = $stripe_subscription->trial_end ? $stripe_subscription->trial_end : $stripe_subscription->start)
			        && ($rr_end_time = $rr_start_time + (($stripe_subscription->plan->interval_count * $stripe_subscription->plan->metadata->recurring_times) * 86400))
			        && (time() + 43200 >= $rr_end_time) // Give this 12 hours of leeway.
			)
			{
				c_ws_plugin__s2member_pro_stripe_utilities::cancel_customer_subscription($customer_id, $stripe_subscription->id);

				return 'Subscription `'.$stripe_subscription->id.'` has `plan->metadata->recurring=true` `plan->metadata->recurring_times='.$stripe_subscription->plan->metadata->recurring_times.'`.'.
				       ' Auto-cancelling subscription after current period ends. This was the last billing cycle.';
			}
			return ''; // Default behavior; i.e., do nothing.
		}
	}
}
