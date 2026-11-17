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
		 * Verify a Stripe webhook's exact raw HTTP body before retrieving its event from the API.
		 * Only a configured endpoint secret enables this gate; malformed or partly configured
		 * lists fail closed. Multiple secrets permit an overlapping Stripe secret rotation.
		 *
		 * @since 261117.0221
		 * @param string $payload Exact raw HTTP request body, unmodified by WordPress.
		 * @param string $header Stripe-Signature request header.
		 * @param string|array $secrets Endpoint signing secret(s), not the Stripe API secret key.
		 * @return bool Whether the body has a current, valid Stripe signature.
		 */
		public static function stripe_webhook_origin_verified($payload, $header, $secrets)
		{
			if(!is_string($payload) || $payload === '' || !is_string($header) || $header === ''
			|| strlen($header) > 4096
			|| !preg_match('/(?:^|,)t=[0-9]{10,12}(?:,|$)/D', $header)
			|| !preg_match('/(?:^|,)v1=[a-f0-9]{64}(?:,|$)/D', $header))
				return FALSE;
			//261117.0221 Validate header fields before the bundled Stripe SDK indexes them; malformed fields must not emit PHP warnings.
			foreach(explode(',', $header) as $part)
				if(strpos($part, '=') === FALSE)
					return FALSE;
			if(!is_array($secrets))
				$secrets = array($secrets);
			if(!$secrets)
				return FALSE;
			foreach($secrets as $secret)
				if(!is_string($secret) || strpos($secret, 'whsec_') !== 0 || strlen($secret) <= 6)
					return FALSE;
			foreach($secrets as $secret)
			{
				try
				{
					if(\Stripe\WebhookSignature::verifyHeader($payload, $header, $secret, 300))
						return TRUE;
				}
				catch(\Exception $exception) {} //261117.0221 Invalid signature; try another configured secret without logging the body or secret.
			}
			return FALSE;
		}

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

				c_ws_plugin__s2member_pro_stripe_utilities::init_stripe_sdk();

				//261117.0221 Authenticate Stripe's original signed HTTP body before its untrusted event ID reaches the Stripe API.
				//261117.0221 Empty filter retains the historical API-retrieval path; a configured invalid secret/header never falls back.
				$webhook_secrets = apply_filters('ws_plugin__s2member_pro_stripe_webhook_signing_secrets', '');
				if($webhook_secrets !== '')
				{
					$raw_payload = @file_get_contents('php://input');
					$signature_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : NULL;
					if(!self::stripe_webhook_origin_verified($raw_payload, $signature_header, $webhook_secrets))
					{
						//261117.0221 Do not log rejected request bodies, headers or signing secrets; never retrieve or process an event after rejection.
						c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', array('s2member_log' => array('Stripe webhook signature verification failed. No event was processed.')));
						status_header(403);
						header('Content-Type: text/plain; charset=UTF-8');
						while(@ob_end_clean());
						exit();
					}
				}

				if(is_object($event = c_ws_plugin__s2member_pro_stripe_utilities::get_event($webhook_secrets !== '' ? $raw_payload : NULL)) && ($stripe['event'] = $event))
				{
					foreach(array_keys(get_defined_vars()) as $__v) $__refs[$__v] =& $$__v;
					do_action('ws_plugin__s2member_pro_before_stripe_notify_event_switch', get_defined_vars());
					unset($__refs, $__v);

					$event_id                         = !empty($event->id) ? (string)$event->id : '';
					$stripe_event_lock               = '';
					$stripe_event_lock_owner         = FALSE;
					$stripe_event_processed_key      = '';
					$stripe_event_processed          = FALSE;
					$skip_event_switch               = FALSE;
					$stripe_retry_required           = FALSE;

					if($event_id)
					{
						$stripe_event_lock          = 's2m_stripe_wh_lock_'.md5($event_id);
						$stripe_event_processed_key = 's2m_stripe_wh_done_'.md5($event_id);

						if(get_transient($stripe_event_processed_key))
						{
							$processing        = TRUE;
							$skip_event_switch = TRUE;

							$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
							$stripe['s2member_log'][] = 'Ignoring duplicate Stripe Webhook/IPN event. s2Member has already processed Stripe event ID: `'.$event_id.'`.';
						}
						else if(!is_string($stripe_event_lock_owner = self::_acquire_event_lock($stripe_event_lock)))
						{
							$processing        = TRUE;
							$skip_event_switch = TRUE;
							//261115.2020 A concurrent delivery needs a Stripe retry unless its predecessor finished durably.
							$stripe_retry_required = !get_transient($stripe_event_processed_key);

							$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
							$stripe['s2member_log'][] = 'A concurrent Stripe Webhook/IPN event is still in progress; retry is required unless its event ID is complete: `'.$event_id.'`.';
						}
						else if(get_transient($stripe_event_processed_key))
						{
							//261115.2020 Recheck after the lock: a previous owner may have finished since the initial read.
							$processing        = TRUE;
							$skip_event_switch = TRUE;
							$stripe['s2member_log'][] = 'Ignoring completed Stripe Webhook/IPN event after lock acquisition: `'.$event_id.'`.';
						}
					}
					else
					{
						$processing        = TRUE;
						$skip_event_switch = TRUE;
						$stripe_retry_required = TRUE;
						$stripe['s2member_log'][] = 'Verified Stripe event has no usable event ID; no fulfillment was attempted.';
					}

					try
					{
					if(!$skip_event_switch)
						switch($event->type)
					{
						case 'invoice.payment_succeeded': // Subscription payments.

							//260619 Process pending subscriptions before the on-session duplicate-prevention window.
							if(!empty($event->data->object)
							   && ($stripe_invoice = $event->data->object) instanceof \Stripe\Invoice
							   && !empty($stripe_invoice->customer) && !empty($stripe_invoice->subscription)
							   && ($pending_subscr_details = c_ws_plugin__s2member_pro_stripe_utilities::get_pending_subscr_details($stripe_invoice->subscription))
							   && is_object($stripe_subscription = c_ws_plugin__s2member_pro_stripe_utilities::get_customer_subscription($stripe_invoice->customer, $stripe_invoice->subscription))
							)
							{
								$processing = TRUE;

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Pending Stripe subscription found for subscription ID: `'.$stripe_invoice->subscription.'`.';

								if(c_ws_plugin__s2member_pro_stripe_utilities::pending_subscr_is_ready_to_process($stripe_subscription))
								{
									if(($pending_subscr_result = c_ws_plugin__s2member_pro_stripe_utilities::process_pending_subscr($stripe_invoice->subscription)))
									{
										$stripe_event_processed = TRUE;
										$stripe['s2member_log'][] = $pending_subscr_result;

										if(($maybe_end_subscription = self::_maybe_end_subscription_after_payment($stripe_invoice->customer, $stripe_subscription)))
											$stripe['s2member_log'][] = $maybe_end_subscription;
									}
									else $stripe['s2member_log'][] = 'Pending Stripe subscription could not be processed yet for subscription ID: `'.$stripe_invoice->subscription.'`.';
								}
								else
								{
									$stripe_event_processed = TRUE;
									$stripe['s2member_log'][] = 'Pending Stripe subscription not processed yet because it is not ready for activation; subscription status: `'.$stripe_subscription->status.'`.';
								}

								break;
							}

							if(!empty($event->data->object)
							   && ($stripe_invoice = $event->data->object) instanceof \Stripe\Invoice
							   && !empty($stripe_invoice->customer) && !empty($stripe_invoice->subscription)
							   && ($stripe_invoice_total = number_format(c_ws_plugin__s2member_pro_stripe_utilities::cents_to_dollar_amount($stripe_invoice->total, $stripe_invoice->currency), 2, '.', '')) > 0
							   && is_object($stripe_subscription = c_ws_plugin__s2member_pro_stripe_utilities::get_customer_subscription($stripe_invoice->customer, $stripe_invoice->subscription))
							   // Ignore webhook for on-session payment, only act on off-session payments.
							   && ((time() - $stripe_subscription->created) > (12 * 60 * 60))
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

								$stripe_event_processed = self::_proxy_event_to_paypal($ipn, $stripe);

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');

								if(($maybe_end_subscription = self::_maybe_end_subscription_after_payment($stripe_invoice->customer, $stripe_subscription)))
									$stripe['s2member_log'][] = $maybe_end_subscription;

								$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor as `txn_type` (`'.$ipn['txn_type'].'`).';
								$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
							}
							break; // Break switch handler.

						case 'invoice.payment_failed': // Subscription payment failures.

							//260619 Log failed first-payment pending subscriptions explicitly.
							if(!empty($event->data->object)
							   && ($stripe_invoice = $event->data->object) instanceof \Stripe\Invoice
							   && !empty($stripe_invoice->customer) && !empty($stripe_invoice->subscription)
							   && ($pending_subscr_details = c_ws_plugin__s2member_pro_stripe_utilities::get_pending_subscr_details($stripe_invoice->subscription))
							)
							{
								$processing = TRUE;

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Pending Stripe subscription found for subscription ID: `'.$stripe_invoice->subscription.'`; first payment failed or is incomplete. No paid access is being granted by this event.';

								if(is_object($stripe_subscription = c_ws_plugin__s2member_pro_stripe_utilities::get_customer_subscription($stripe_invoice->customer, $stripe_invoice->subscription)))
									$stripe['s2member_log'][] = 'Pending Stripe subscription status after failed first payment: `'.$stripe_subscription->status.'`.';
								else
									$stripe['s2member_log'][] = 'Unable to retrieve pending Stripe subscription after failed first payment.';

								$stripe_event_processed = TRUE;
								break;
							}

							if(!empty($event->data->object)
							   && ($stripe_invoice = $event->data->object) instanceof \Stripe\Invoice
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
								$stripe_event_processed = TRUE;
							}
							else if(empty($processing)
							   && !empty($event->data->object)
							   && ($stripe_invoice = $event->data->object) instanceof \Stripe\Invoice
							   && !empty($stripe_invoice->customer) && !empty($stripe_invoice->subscription)
							)
							{
								$processing = TRUE;

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'No pending Stripe subscription found for subscription ID: `'.$stripe_invoice->subscription.'`.';
								$stripe['s2member_log'][] = 'Ignoring `'.$event->type.'`; no pending subscription matched, or no active s2Member signup vars were found for this subscription.';
								$stripe_event_processed = TRUE;
							}
							break; // Break switch handler.

						case 'customer.deleted': // Customer deletions.

							if(!empty($event->data->object)
							   && ($stripe_customer = $event->data->object) instanceof \Stripe\Customer
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

								$stripe_event_processed = self::_proxy_event_to_paypal($ipn, $stripe);

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor as `txn_type` (`'.$ipn['txn_type'].'`).';
								$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
							}
							break; // Break switch handler.

						case 'setup_intent.setup_failed': // Failed off-session payment-method authentication for a pending subscription.

							//260925.1847 A failed SetupIntent can leave a trialing pending subscription alive indefinitely; cancel only the subscription durably bound to this exact SetupIntent and Gateway Checkout.
							if(!empty($event->data->object)
							   && ($stripe_setup_intent = $event->data->object) instanceof \Stripe\SetupIntent
							   && !empty($stripe_setup_intent->id) && !empty($stripe_setup_intent->customer)
							)
							{
								$processing = TRUE;

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');

								try
								{
									$stripe_subscriptions = \Stripe\Subscription::all(array('customer' => (string)$stripe_setup_intent->customer, 'status' => 'all', 'limit' => 100));
									$stripe_event_processed = TRUE; // A verified SetupIntent failure with no matching s2Member pending subscription requires no local action.

									if(!empty($stripe_subscriptions->data) && is_array($stripe_subscriptions->data))
										foreach($stripe_subscriptions->data as $stripe_subscription)
										{
											$pending_setup_intent_id = is_object($stripe_subscription->pending_setup_intent) && !empty($stripe_subscription->pending_setup_intent->id) ? (string)$stripe_subscription->pending_setup_intent->id : (string)$stripe_subscription->pending_setup_intent;
											$gateway_checkout_id = !empty($stripe_subscription->metadata->s2member_gateway_checkout_id) ? (string)$stripe_subscription->metadata->s2member_gateway_checkout_id : '';

											if(!$pending_setup_intent_id || !hash_equals((string)$stripe_setup_intent->id, $pending_setup_intent_id)
											   || !$gateway_checkout_id || !c_ws_plugin__s2member_pro_stripe_utilities::get_pending_subscr_details((string)$stripe_subscription->id)
											   || !($gateway_checkout_state = c_ws_plugin__s2member_gateway_checkouts::load_state($gateway_checkout_id))
											   || empty($gateway_checkout_state['gateway_ids']['subscription_id']) || !hash_equals((string)$stripe_subscription->id, (string)$gateway_checkout_state['gateway_ids']['subscription_id'])
											   || empty($gateway_checkout_state['gateway_ids']['setup_intent_id']) || !hash_equals((string)$stripe_setup_intent->id, (string)$gateway_checkout_state['gateway_ids']['setup_intent_id']))
												continue;

											//260926.0433 Browser cleanup can win this race first; use the shared SetupIntent cleanup helper so an already-canceled/gone subscription is treated as successful convergence instead of an error.
											$canceled_subscription_id = '';
											if(!c_ws_plugin__s2member_pro_stripe_utilities::cancel_pending_subscription_by_setup_intent((string)$stripe_setup_intent->customer, (string)$stripe_subscription->id, (string)$stripe_setup_intent->id, $canceled_subscription_id))
											{
												$stripe['s2member_log'][] = 'Unable to clean up pending Stripe subscription after failed SetupIntent authentication: `'.$stripe_subscription->id.'`.';
												break;
											}
											if(!$canceled_subscription_id)
											{
												$stripe['s2member_log'][] = 'Pending Stripe subscription no longer requires failed-SetupIntent cleanup: `'.$stripe_subscription->id.'`.';
												break;
											}

											//260926.0332 A browser failed-auth cleanup can race this webhook; advance only while the latest durable checkout still points at this exact failed subscription/SetupIntent generation.
											$latest_gateway_checkout_state = c_ws_plugin__s2member_gateway_checkouts::load_state_uncached($gateway_checkout_id);
											if($latest_gateway_checkout_state && !empty($latest_gateway_checkout_state['gateway_ids']['subscription_id']) && !empty($latest_gateway_checkout_state['gateway_ids']['setup_intent_id'])
											   && hash_equals((string)$stripe_subscription->id, (string)$latest_gateway_checkout_state['gateway_ids']['subscription_id'])
											   && hash_equals((string)$stripe_setup_intent->id, (string)$latest_gateway_checkout_state['gateway_ids']['setup_intent_id']))
											{
												if(!c_ws_plugin__s2member_pro_stripe_utilities::advance_gateway_checkout_generation($gateway_checkout_id, 'subscription', array('subscription_id', 'payment_intent_id', 'setup_intent_id', 'invoice_item_id')))
													$stripe['s2member_log'][] = 'Pending Stripe subscription was canceled after failed SetupIntent authentication, but its Gateway Checkout generation could not be advanced.';
											}
											else $stripe['s2member_log'][] = 'Gateway Checkout generation was already advanced by concurrent failed-auth cleanup.';

											$stripe['s2member_log'][] = 'Pending Stripe subscription cleanup completed after failed SetupIntent authentication: `'.$stripe_subscription->id.'`.';
											break;
										}
								}
								catch(exception $exception)
								{
									$stripe['s2member_log'][] = 'Unable to clean up a pending Stripe subscription after failed SetupIntent authentication: `'.$exception->getMessage().'`.';
								}
							}
							break; // Break switch handler.

						case 'customer.subscription.updated': // Customer subscription update.

							//260619 Process or discard pending subscriptions when Stripe changes their status.
							if(!empty($event->data->object)
							   && ($stripe_subscription = $event->data->object) instanceof \Stripe\Subscription
							   && ($pending_subscr_details = c_ws_plugin__s2member_pro_stripe_utilities::get_pending_subscr_details($stripe_subscription->id))
							)
							{
								$processing = TRUE;

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Pending Stripe subscription found for subscription ID: `'.$stripe_subscription->id.'`; subscription status: `'.$stripe_subscription->status.'`.';

								if(c_ws_plugin__s2member_pro_stripe_utilities::pending_subscr_is_ready_to_process($stripe_subscription))
								{
									if(($pending_subscr_result = c_ws_plugin__s2member_pro_stripe_utilities::process_pending_subscr($stripe_subscription->id)))
									{
										$stripe_event_processed = TRUE;
										$stripe['s2member_log'][] = $pending_subscr_result;

										if(($maybe_end_subscription = self::_maybe_end_subscription_after_payment($stripe_subscription->customer, $stripe_subscription)))
											$stripe['s2member_log'][] = $maybe_end_subscription;
									}
									else $stripe['s2member_log'][] = 'Pending Stripe subscription could not be processed yet for subscription ID: `'.$stripe_subscription->id.'`.';
								}
								else if((string)$stripe_subscription->status === 'incomplete_expired')
								{
									$stripe_event_processed = TRUE;
									c_ws_plugin__s2member_pro_stripe_utilities::delete_pending_subscr_details($stripe_subscription->id);
									$stripe['s2member_log'][] = 'Pending Stripe subscription discarded because it expired incomplete.';
								}
								else
								{
									$stripe_event_processed = TRUE;
									$stripe['s2member_log'][] = 'Pending Stripe subscription not processed yet because it is not ready for activation.';
								}
							}
							break; // Break switch handler.

						case 'customer.subscription.deleted': // Customer subscription deletion.

							//260619 Discard pending subscription details when Stripe deletes the subscription before activation.
							if(!empty($event->data->object)
							   && ($stripe_subscription = $event->data->object) instanceof \Stripe\Subscription
							   && c_ws_plugin__s2member_pro_stripe_utilities::get_pending_subscr_details($stripe_subscription->id)
							)
							{
								$processing = TRUE;
								$stripe_event_processed = TRUE;

								c_ws_plugin__s2member_pro_stripe_utilities::delete_pending_subscr_details($stripe_subscription->id);

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Pending Stripe subscription discarded because the subscription was deleted before activation.';

								break;
							}

							if(!empty($event->data->object)
							   && ($stripe_subscription = $event->data->object) instanceof \Stripe\Subscription
							   && ($ipn_signup_vars = c_ws_plugin__s2member_utils_users::get_user_ipn_signup_vars(0, $stripe_subscription->id))
							)
							{
								$processing = TRUE;

								if(c_ws_plugin__s2member_pro_stripe_utilities::has_replacement_cancellation_guard($stripe_subscription->id))
								{
									$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
									$stripe['s2member_log'][] = 'Ignoring `'.$event->type.'` for old Stripe subscription ID `'.$stripe_subscription->id.'` because it was cancelled as part of a subscription replacement/modification flow.';
									$stripe_event_processed = TRUE;
								}
								else
								{
									//261110.0051 TO-DO: Review early Stripe deletion during paid/promotional trials. `customer.subscription.deleted`
									//261110.0051 maps to `subscr_eot`; without an Auto-EOT (normal for active subscriptions), Core may demote immediately.
									//261110.0051 Signup vars (`initial`, `initial_term`, `mc_amount1`, `period1`) record intended terms, not proven payment.
									//261110.0051 Verify Stripe invoices/payments and independently granted access; `trial_end` alone is insufficient.
									//261110.0051 Evaluate before proxying: Core demotion removes saved signup vars. Preserve manual EOTs/replacement guards.
									//261110.0051 Compare other gateway cancellation/EOT semantics
									//261110.0051 and test free/paid trials, early/scheduled cancellation, and duplicate/out-of-order webhooks before changing this mapping.
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

									$stripe_event_processed = self::_proxy_event_to_paypal($ipn, $stripe);

									$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
									$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor as `txn_type` (`'.$ipn['txn_type'].'`).';
									$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
								}
							}
							break; // Break switch handler.

						case 'charge.refunded': // Customer refund (partial or full).

							if(!empty($event->data->object)
							   && ($stripe_charge = $event->data->object) instanceof \Stripe\Charge
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

								$stripe_event_processed = self::_proxy_event_to_paypal($ipn, $stripe);

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor.';
								$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
							}
							break; // Break switch handler.

						case 'charge.dispute.created': // Customer dispute (chargeback).

							if(!empty($event->data->object->charge)
							   && ($stripe_charge = c_ws_plugin__s2member_pro_stripe_utilities::get_charge($event->data->object->charge)) instanceof \Stripe\Charge
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

								$stripe_event_processed = self::_proxy_event_to_paypal($ipn, $stripe);

								$stripe['s2member_log'][] = 'Stripe Webhook/IPN event type identified as: `'.$event->type.'` on: '.date('D M j, Y g:i:s a T');
								$stripe['s2member_log'][] = 'Webhook/IPN event `'.$event->type.'` reformulated. Piping through s2Member\'s core gateway processor.';
								$stripe['s2member_log'][] = 'Please check core IPN logs for further processing details.';
							}
							break; // Break switch handler.
					}
					foreach(array_keys(get_defined_vars()) as $__v) $__refs[$__v] =& $$__v;
					do_action('ws_plugin__s2member_pro_after_stripe_notify_event_switch', get_defined_vars());
					unset($__refs, $__v);

					if(empty($processing))
					{
						$stripe['s2member_log'][] = 'Ignoring this Webhook/IPN. The event does NOT require any action on the part of s2Member.';
						$stripe_event_processed = TRUE;
					}
					if($stripe_event_processed && $stripe_event_processed_key && !$skip_event_switch)
					{
						//261115.2020 A failed completion marker must not be reported as a durable event acknowledgment.
						if(!set_transient($stripe_event_processed_key, time(), 31556952))
							$stripe_retry_required = TRUE;
					}
					if(!empty($processing) && !$stripe_event_processed && !$skip_event_switch)
						$stripe_retry_required = TRUE;
					}
					catch(\Exception $exception)
					{
						$stripe_retry_required = TRUE;
						$stripe['s2member_log'][] = 'Stripe processing interrupted: `'.get_class($exception).'`; fulfillment outcome may require reconciliation.';
					}
					catch(\Error $error)
					{
						$stripe_retry_required = TRUE;
						$stripe['s2member_log'][] = 'Stripe processing interrupted: `'.get_class($error).'`; fulfillment outcome may require reconciliation.';
					}
					finally
					{
						if(is_string($stripe_event_lock_owner))
							self::_release_event_lock($stripe_event_lock, $stripe_event_lock_owner);
					}
				}
				else // Extensive log reporting here. This is an area where many site owners find trouble. Depending on server configuration; remote HTTPS connections may fail.
				{
					$stripe['s2member_log'][] = 'Unable to verify Webhook/IPN event ID. This is most likely related to an invalid Stripe configuration. Please check: s2Member → Stripe Options.';
					$stripe['s2member_log'][] = 'If you\'re absolutely SURE that your Stripe configuration is valid, you may want to run some tests on your server, just to be sure \$_POST variables (and php://input) are populated; and that your server is able to connect to Stripe over an HTTPS connection.';
					$stripe['s2member_log'][] = 's2Member uses the Stripe SDK for remote connections; which relies upon the cURL extension for PHP. Please make sure that your installation of PHP has the cURL extension; and that it\'s configured together with OpenSSL for HTTPS communication.';
					$stripe['s2member_log'][] = var_export($_REQUEST, TRUE)."\n".var_export(json_decode(@file_get_contents('php://input')), TRUE);
				}
				c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', $stripe);

				//261115.2020 Do not acknowledge a verified event that must be retried after contention or failed processing.
				status_header(!empty($stripe_retry_required) ? 503 : 200);
				if(!empty($stripe_retry_required))
					header('Retry-After: 30');
				header('Content-Type: text/plain; charset=UTF-8');
				while(@ob_end_clean()); exit();
			}
		}

		/**
		 * Check old timestamp locks before allowing a migrated Stripe event worker to proceed.
		 *
		 * @since 261115.2020
		 * @return bool Whether an old active or unknown lock prevents safe processing.
		 */
		private static function _legacy_event_lock_active($stripe_event_lock, $lock_timeout)
		{
			global $wpdb;

			$raw = $wpdb->get_var($wpdb->prepare("SELECT `option_value` FROM `{$wpdb->options}` WHERE `option_name` = %s LIMIT 1", $stripe_event_lock));
			if($wpdb->last_error !== '')
				return TRUE;
			if($raw === NULL)
				return FALSE;
			if(!is_string($raw) || !preg_match('/^[1-9][0-9]{0,12}$/D', $raw))
				return TRUE;
			return (int)$raw > time() - $lock_timeout;
		}

		/**
		 * Claim one owner-aware Stripe webhook lease while respecting old timestamp locks.
		 *
		 * @since 261115.2020
		 * @param string $stripe_event_lock Historical event key retained as the lock identity.
		 * @param int $lock_timeout Lease lifetime in seconds.
		 * @return string|bool Owner token on success; FALSE on contention or database failure.
		 */
		public static function _acquire_event_lock($stripe_event_lock, $lock_timeout = 900)
		{
			if(!is_string($stripe_event_lock) || !preg_match('/^s2m_stripe_wh_lock_[a-f0-9]{32}$/D', $stripe_event_lock)
			   || !is_int($lock_timeout) || $lock_timeout < 1 || $lock_timeout > 86400)
				return FALSE;
			if(self::_legacy_event_lock_active($stripe_event_lock, $lock_timeout))
				return FALSE;

			$resource = 'stripe-webhook/'.$stripe_event_lock;
			$owner = c_ws_plugin__s2member_database::lock_acquire($resource, $lock_timeout);
			if(!is_string($owner))
				return FALSE;
			//261115.2020 The second check fences a still-running legacy worker during a rolling deployment.
			if(self::_legacy_event_lock_active($stripe_event_lock, $lock_timeout))
			{
				c_ws_plugin__s2member_database::lock_release($resource, $owner);
				return FALSE;
			}
			return $owner;
		}

		/**
		 * Release an event lease only when the caller owns the exact database lock.
		 *
		 * @since 261115.2020
		 * @param string $stripe_event_lock Event lock key.
		 * @param string $owner Owner token returned by _acquire_event_lock().
		 * @return bool Whether the exact lease was released.
		 */
		public static function _release_event_lock($stripe_event_lock, $owner = '')
		{
			if(!is_string($stripe_event_lock) || !preg_match('/^s2m_stripe_wh_lock_[a-f0-9]{32}$/D', $stripe_event_lock))
				return FALSE;
			return c_ws_plugin__s2member_database::lock_release('stripe-webhook/'.$stripe_event_lock, $owner) === TRUE;
		}

		/**
		 * Proxies a Stripe webhook event into s2Member's PayPal notify handler.
		 *
		 * @package s2Member\Stripe
		 * @since 260318
		 *
		 * @param array $ipn    PayPal-proxy payload.
		 * @param array $stripe Stripe log accumulator.
		 *
		 * @return bool TRUE on success; else FALSE.
		 */
		public static function _proxy_event_to_paypal($ipn, &$stripe)
		{
			if(!is_array($ipn) || empty($ipn['s2member_paypal_proxy']) || $ipn['s2member_paypal_proxy'] !== 'stripe')
				return FALSE;

			//261115.2110 Verified Stripe webhook forwarding avoids loopback HTTP; the opt-out retains signed HTTP support.
			$local_dispatch = apply_filters('ws_plugin__s2member_pro_stripe_notify_local_dispatch', TRUE, $ipn);
			//261117.0417 Internal PHP must not use a bearer key; only an explicitly selected HTTP fallback receives a signed credential.
			unset($ipn['s2member_paypal_proxy_verification']);
			$use_proxy_v2 = !$local_dispatch && apply_filters('ws_plugin__s2member_pro_stripe_notify_use_proxy_v2', FALSE, $ipn);
			if($use_proxy_v2)
			{
				//261111.0034 Sign the final POST body here rather than at individual call sites; later additions to the payload must never invalidate the HMAC.
				if(!is_array($ipn) || empty($ipn['s2member_paypal_proxy']) || $ipn['s2member_paypal_proxy'] !== 'stripe'
				|| !method_exists('c_ws_plugin__s2member_paypal_utilities', 'paypal_proxy_v2_sign')
				|| !is_string($signature = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_v2_sign($ipn)))
				{
					$stripe['s2member_log'][] = 'Unable to sign the Stripe gateway notification for internal processing. No proxy request was sent.';
					return FALSE;
				}
				$ipn['s2member_paypal_proxy_verification'] = $signature;
			}
			else if(!$local_dispatch)
			{
				//261117.0417 Retain the old credential only for opted-out, non-v2 HTTP transport; a missing key cannot authenticate a fallback.
				$key = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();
				if(!is_string($key) || $key === '')
					return FALSE;
				$ipn['s2member_paypal_proxy_verification'] = $key;
			}

			try
			{
				$r = $local_dispatch
					? c_ws_plugin__s2member_paypal_utilities::paypal_checkout_notify_inprocess($ipn)
					: c_ws_plugin__s2member_utils_urls::remote(home_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20), TRUE);
			}
			catch(\Exception $e)
			{
				$r = array('code' => 0, 'message' => get_class($e), 'body' => '');
			}
			catch(\Error $e)
			{
				$r = array('code' => 0, 'message' => get_class($e), 'body' => '');
			}

			if(!is_array($r))
				$r = array('code' => 0, 'message' => 'request_failed', 'body' => '');

			if(($code = (int)$r['code']) >= 200 && $code <= 299)
				return TRUE;

			$stripe['s2member_log'][] = 'Unable to proxy this Stripe Webhook/IPN event through s2Member\'s core gateway processor.';
			$stripe['s2member_log'][] = 'Proxy response code: `'.$code.'`; message: `'.((string)$r['message']).'`.';
			return FALSE;
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
			if(!$customer_id || !($stripe_subscription instanceof \Stripe\Subscription))
				return ''; // Not possible.

			// Non-recurring subscription
			// It only gets here if it had a trial.
			// Non-recur without trial is charged as buy-now instead of subscription.
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
				&& isset($stripe_subscription->plan->metadata->recurring_times)
				&& (int)$stripe_subscription->plan->metadata->recurring_times === 1
				&& strtolower($stripe_subscription->status) !== 'trialing' // Past the initial/trial period?
			)
			{
				c_ws_plugin__s2member_pro_stripe_utilities::cancel_customer_subscription($customer_id, $stripe_subscription->id);

				return 'Subscription `'.$stripe_subscription->id.'` has `plan->metadata->recurring=true` `plan->metadata->recurring_times=1`.'.
				       ' Auto-cancelling subscription after current period ends. This was the last billing cycle.';
			}
			// Installments
			else if(isset($stripe_subscription->plan->metadata->recurring)
				&& filter_var($stripe_subscription->plan->metadata->recurring, FILTER_VALIDATE_BOOLEAN)
				&& isset($stripe_subscription->plan->metadata->recurring_times)
				&& $stripe_subscription->plan->metadata->recurring_times > 0
				&& strtolower($stripe_subscription->plan->interval) === 'day' // s2Member configures all plans with a day-based interval.
				&& strtolower($stripe_subscription->status) !== 'trialing' // Past the initial/trial period?

				&& ($rr_start_time = $stripe_subscription->trial_end ? $stripe_subscription->trial_end : $stripe_subscription->start)
				// End time is when the last paid period ends, but this is what we need.
				// They are installments, and access will continue after the last payment, no EOT.
				// What matters is not the end of the last paid period, but its beginning.
				// Payments happen at the beginning of each period, not the end.
				// First payment happened at start time, so remove it from recurring_times when calculating remaining ones.
				&& ($rr_last_payment_time = $rr_start_time + (($stripe_subscription->plan->interval_count * ($stripe_subscription->plan->metadata->recurring_times - 1)) * 86400))
				&& (time() + 43200 >= $rr_last_payment_time) // Give this 12 hours of leeway.
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
