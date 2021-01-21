<?php
// @codingStandardsIgnoreFile
/**
 * Stripe utilities.
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
	exit('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_pro_stripe_utilities'))
{
	/**
	 * Stripe utilities.
	 *
	 * @package s2Member\Stripe
	 * @since 140617
	 */
	class c_ws_plugin__s2member_pro_stripe_utilities
	{
		/**
		 * Load Stripe SDK, set API Key, API Version, and App Info.
		 * 
		 */
		public static function init_stripe_sdk()
		{
			$stripe_api_version = '2019-10-08';
			if(!class_exists('Stripe\Stripe'))
				require_once dirname(__FILE__).'/stripe-sdk/init.php';
			\Stripe\Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);
			\Stripe\Stripe::setApiVersion($stripe_api_version);
			\Stripe\Stripe::setAppInfo('WordPress s2Member Pro', WS_PLUGIN__S2MEMBER_PRO_VERSION, "https://s2member.com");
		}

		/**
		 * Get a Stripe customer object instance.
		 *
		 * @param integer $user_id If it's for an existing user; pass the user's ID (optional).
		 * @param string  $email Customer's email address (optional).
		 * @param string  $fname Customer's first name (optional).
		 * @param string  $lname Customer's last name (optional).
		 * @param array   $metadata Any metadata (optional).
		 * @param array   $post_vars Pro-Form post vars (optional).
		 *
		 * @return Stripe_Customer|string Customer object; else error message.
		 */
		public static function get_customer($user_id = 0, $email = '', $fname = '', $lname = '', $metadata = array(), $post_vars = array())
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			$metadata = array_merge(self::_additional_customer_metadata($post_vars), (array)$metadata);
			$customer = '';

			try // Obtain existing customer object; else create a new one.
			{
				try // Attempt to find an existing customer; if that's possible.
				{
					if($user_id && ($customer_id = get_user_option('s2member_subscr_cid', $user_id)))
						$customer = \Stripe\Customer::retrieve($customer_id);
					// Maybe we don't have a cus_id, but the customer does exist... 
					// Let's try finding a customer by the email address.
					elseif (!empty($email) && is_object($customers = \Stripe\Customer::all(['email' => $email, 'limit' => 1])))
						$customer = (isset($customers->data[0])) ? $customers->data[0] : '';

					if(!empty($customer) && is_object($customer) && $metadata)
					{
						foreach($metadata as $_key => $_value)
							$customer->metadata->{$_key} = $_value;
						unset($_key, $_value); // Housekeeping.

						$customer->save(); // Update.
					}
				}
				catch(exception $exception)
				{
					// Fail silently.
				}
				
				// If we don't have a Customer, let's create one.
				if(empty($customer) || !is_object($customer)) { 
					$args = array(
						'email'    => $email,
						'name'     => trim($fname.' '.$lname),
						'metadata' => $metadata,
					);
					// if we don't have a state, we didn't collect billing address.
					if (!empty($post_vars['state'])) {
						$args['address'] = array(
							'line1'       => $post_vars['street'],
							'city'	      => $post_vars['city'],
							'state'       => $post_vars['state'],
							'country'     => $post_vars['country'],
							'postal_code' => $post_vars['zip'],
						);
					}
					$customer = \Stripe\Customer::create($args);
				}
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $customer);

				return $customer;
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		public static function _additional_customer_metadata($post_vars = array())
		{
			$post_vars = (array)$post_vars;
			$metadata  = array(); // Initialize.

			if(!empty($post_vars['first_name']) || !empty($post_vars['last_name']))
				$metadata['name'] = trim((string)@$post_vars['first_name'].' '.(string)@$post_vars['last_name']);

			if(c_ws_plugin__s2member_utils_ip::current())
				$metadata['ip'] = c_ws_plugin__s2member_utils_ip::current();

			return $metadata;
		}

		/**
		 * Set a Stripe customer source.
		 *
		 * @param string $customer_id Customer ID in Stripe.
		 * @param string $source_token Stripe source card/bank/bitcoin token.
		 * @param array  $post_vars Pro-Form post vars (optional).
		 * @param null|string $reject_prepaid Any non-empty value (or `false` or `0`)
		 * 	will override the global default setting for this instance.
		 *
		 * @return Stripe_Customer|string Customer object; else error message.
		 */
		public static function set_customer_source($customer_id, $source_token, $post_vars = array(), $reject_prepaid = null)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			$metadata       = self::_additional_customer_metadata($post_vars);
			$source_details = self::_additional_customer_source_details($post_vars);

			try // Attempt to update the customer's source token.
			{
				$customer         = \Stripe\Customer::retrieve($customer_id);
				$customer->source = $source_token; // Update.

				if($metadata) // Customer metadata?
				{
					foreach($metadata as $_key => $_value)
						$customer->metadata->{$_key} = $_value;
					unset($_key, $_value); // Housekeeping.
				}
				$customer->save(); // Update.

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $customer);

				if($source_details) // Additional details we should save?
					{
						try // Fail gracefully if a simple card update fails here.
						{
							$source = $customer->sources->data[0]; // Just one source.
							/** @var Stripe_Card|Stripe_BitcoinReceiver $source */

							if($source instanceof Card)
							{
								foreach($source_details as $_key => $_value)
									$source->{$_key} = $_value;
								unset($_key, $_value);

								$source->save(); // Update.
							}
							else if($source instanceof BitcoinReceiver)
							{
								foreach($source_details as $_key => $_value)
									$source->metadata->{$_key} = $_value;
								unset($_key, $_value);

								$source->save(); // Update.
							}
						}
						catch(exception $source_details_exception)
						{
							self::log_entry(__FUNCTION__, $input_time, $source_details, time(), $source_details_exception);
							// Fail silently in this case. It's just a simple update for tax reporting.
						}
					}
				$reject_prepaid = !empty($reject_prepaid) || $reject_prepaid === false || $reject_prepaid === '0'
					? filter_var($reject_prepaid, FILTER_VALIDATE_BOOLEAN) // Use the value passed in.
					: (boolean)$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_reject_prepaid'];

				if($reject_prepaid && !empty($customer->sources->data[0]->funding) && $customer->sources->data[0]->funding === 'prepaid')
				{ // Reject prepaid cards in this case.
					return self::error_message(_x('Error: <strong>prepaid</strong> cards not accepted at this time. Please use a different card and try again.', 's2member-front', 's2member'));
				}
				return $customer;
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		public static function _additional_customer_source_details($post_vars = array())
		{
			$post_vars = (array)$post_vars;
			$details   = array(); // Initialize.

			if(!empty($post_vars['first_name']) || !empty($post_vars['last_name']))
				$details['name'] = trim((string)@$post_vars['first_name'].' '.(string)@$post_vars['last_name']);

			if(!empty($post_vars['city']))
				$details['address_city'] = (string)$post_vars['city'];

			if(!empty($post_vars['state']))
				$details['address_state'] = (string)$post_vars['state'];

			if(!empty($post_vars['zip']))
				$details['address_zip'] = (string)$post_vars['zip'];

			if(!empty($post_vars['country']))
				$details['address_country'] = (string)$post_vars['country'];

			return $details;
		}

		/**
		 * Create a Stripe customer subscription.
		 *
		 * @param string               $customer_id Customer ID in Stripe.
		 * @param integer|float|string $amount The amount to charge.
		 * @param string               $currency Three character currency code.
		 * @param string               $description Description of the charge.
		 * @param array                $metadata Any additional metadata (optional).
		 * @param array                $post_vars Pro-Form post vars (optional).
		 * @param array                $cost_calculations Pro-Form cost calculations (optional).
		 *
		 * @return Stripe_Charge|string Charge object; else error message.
		 */
		public static function create_customer_charge($customer_id, $amount, $currency, $description, $metadata = array(), $post_vars = array(), $cost_calculations = array())
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			$metadata = array_merge(self::_additional_charge_metadata($post_vars, $cost_calculations), (array)$metadata);

			try // Attempt to charge the customer.
			{
				$charge = array(
					'customer'             => $customer_id,
					'description'          => $description, 
					'metadata'             => $metadata,
					'amount'               => self::dollar_amount_to_cents($amount, $currency),
					'currency'             => $currency,
					'statement_descriptor' => $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description'],
				);
				if(!trim($charge['statement_descriptor']))
					unset($charge['statement_descriptor']);

				$charge = \Stripe\Charge::create($charge);
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $charge);

				return $charge; // Stripe charge object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		public static function _additional_charge_metadata($post_vars = array(), $cost_calculations = array())
		{
			$post_vars         = (array)$post_vars;
			$cost_calculations = (array)$cost_calculations;
			$metadata          = array(); // Initialize.

			if(!empty($post_vars['coupon']))
				$coupon['code'] = $post_vars['coupon'];

			if(isset($cost_calculations['trial_tax'], $cost_calculations['trial_tax_per'])
			   && isset($post_vars['attr']['tp'], $cost_calculations['trial_total'])
			   && $post_vars['attr']['tp'] && $cost_calculations['trial_total'] > 0
			) // Charge is for a trial amount in this case.
			{
				$tax_info['tax']     = $cost_calculations['trial_tax'];
				$tax_info['tax_per'] = $cost_calculations['trial_tax_per'];
			}
			else if(isset($cost_calculations['tax'], $cost_calculations['tax_per']))
			{
				$tax_info['tax']     = $cost_calculations['tax'];
				$tax_info['tax_per'] = $cost_calculations['tax_per'];
			}
			if(!empty($coupon)) // JSON encode this data.
				$metadata['coupon'] = json_encode($coupon);

			if(!empty($tax_info)) // JSON encode this data.
				$metadata['tax_info'] = json_encode($tax_info);

			return $metadata;
		}

		/**
		 * Get a Stripe charge object instance.
		 *
		 * @param string $charge_id Charge ID in Stripe.
		 *
		 * @return Stripe_Charge|string Charge object; else error message.
		 */
		public static function get_charge($charge_id)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			try // Obtain charge object; if possible.
			{
				$charge = \Stripe\Charge::retrieve($charge_id);

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $charge);

				return $charge; // Stripe charge object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		/**
		 * Get a Stripe Billing Plan object instance.
		 *
		 * @param array $shortcode_attrs An array of shortcode attributes.
		 * @param array $metadata Any additional metadata.
		 *
		 * @return Plan|string Plan object; else error message.
		 */
		public static function get_plan($shortcode_attrs, $metadata = array())
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			$amount                      = $shortcode_attrs['ra'];
			$currency                    = $shortcode_attrs['cc'];
			$name                        = $shortcode_attrs['desc'];
			$metadata['recurring']       = $shortcode_attrs['rr'] && $shortcode_attrs['rr'] !== 'BN';
			// rrt installments are not managed by Stripe, it's a regular subscription ended by s2 after number of payments.
			// This gets tricky with Jason's shift of first regular to a separate charge when there's an unused trial period.
			$metadata['recurring_times'] = $shortcode_attrs['rr'] && $shortcode_attrs['rrt'] ? (integer)$shortcode_attrs['rrt'] : -1;
			$trial_period_days           = self::per_term_2_days($shortcode_attrs['tp'], $shortcode_attrs['tt']);
			$interval_days               = self::per_term_2_days($shortcode_attrs['rp'], $shortcode_attrs['rt']);

			// The access is more correct for the product's name, and will avoid duplicate products,
			// but the shortcode's description is probably better in this case...
			// $product_name = trim('level'$shortcode_attrs['level'].':'.$shortcode_attrs['ccaps']);
			$product      = self::get_product($name);

			$plan_id      = 's2_plan_'.md5($amount.$currency.$name.$trial_period_days.$interval_days.serialize($metadata).$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description']);

			try // Attempt to get an existing plan; else create a new one.
			{
				try // Try to find an existing plan.
				{
					$plan = \Stripe\Plan::retrieve($plan_id);
				}
				catch(exception $exception) // Else create one.
				{
					$plan = array(
						'id'                => $plan_id,
						'product'           => $product->id,
						'metadata'          => $metadata,
						'amount'            => self::dollar_amount_to_cents($amount, $currency),
						'currency'          => $currency,
						'interval'          => 'day',
						'interval_count'    => $interval_days,
						// This condition in the argument below moves the first regular period out of the subscription when there's an unused trial period.
						// Basically, if there's an unused trial, it'll use it, it will always set a trial, even when the site owner didn't mean it.
						// This trial will be "free" in the subscription (trialing...). The period is still charged, but separately.
						// 'trial_period_days' => $trial_period_days ? $trial_period_days : $interval_days,
					);
					// Stop adding the trial for subscriptions that didn't mean to have it.
					// To allow paid trials (initial period, different from the regular ones), create invoice item for it right before the subscription,
					// so it gets charged in the trial's invoice. https://stripe.com/docs/billing/subscriptions/trials
					if($trial_period_days)
						$plan['trial_period_days'] = $trial_period_days;

					$plan = \Stripe\Plan::create($plan);
				}
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $plan);

				return $plan; // Stripe plan object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		/**
		 * Create a Stripe customer subscription.
		 *
		 * @param string $customer_id Customer ID in Stripe.
		 * @param string $plan_id Subscription plan ID in Stripe.
		 * @param array  $metadata Any additional metadata (optional).
		 * @param array  $post_vars Pro-Form post vars (optional).
		 * @param array  $cost_calculations Pro-Form cost calculations (optional).
		 *
		 * @return Stripe_Subscription|string Subscription object; else error message.
		 */
		public static function create_customer_subscription($customer_id, $plan_id, $metadata = array(), $post_vars = array(), $cost_calculations = array())
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			$metadata = array_merge(self::_additional_subscription_metadata($post_vars, $cost_calculations), (array)$metadata);

			// Do we have a paid trial.
			if (!empty($post_vars['attr']['tp']) && !empty($cost_calculations['trial_total']) && $cost_calculations['trial_total'] > 0) {
				// Create an invoice item for it, so it gets added to the trial's invoice.
				$item = array(
					'customer'    => $customer_id,
					'amount'      => self::dollar_amount_to_cents($cost_calculations['trial_total'], $cost_calculations['cur']),
					'currency'    => $cost_calculations['cur'],
					'description' => 'Initial period'
				);
				$invoice_item = \Stripe\InvoiceItem::create($item, array(
					'idempotency_key' => md5(serialize($item)),
				));
			}

			try // Attempt to create a new subscription for this customer.
			{
				// Include the customer's payment method so the sub idemp key changes with new cards.
				$customer = \Stripe\Customer::retrieve($customer_id);

				$subscription = array(
					'customer'        => $customer_id,
					'items'           => array(
						array(
							'plan' => $plan_id,
						),
					),
					'default_payment_method' => $customer->invoice_settings->default_payment_method,
					'trial_from_plan' => true,
					'metadata'        => $metadata,
					'expand'          => array(
						'latest_invoice.payment_intent',
						'pending_setup_intent',
					),
				);
				$subscription = \Stripe\Subscription::create($subscription, array(
					'idempotency_key' => md5(serialize($subscription)),
				));

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $subscription);

				return $subscription; // Stripe subscription object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		public static function _additional_subscription_metadata($post_vars = array(), $cost_calculations = array())
		{
			$post_vars         = (array)$post_vars;
			$cost_calculations = (array)$cost_calculations;
			$metadata          = array(); // Initialize.

			if(!empty($post_vars['coupon']))
				$coupon['code'] = $post_vars['coupon'];

			if(isset($cost_calculations['trial_tax'], $cost_calculations['trial_tax_per'])
			   && isset($post_vars['attr']['tp'], $cost_calculations['trial_total'])
			   && $post_vars['attr']['tp'] && $cost_calculations['trial_total'] > 0
			) // Charge is for a trial amount in this case.
			{
				$tax_info['trial_tax']     = $cost_calculations['trial_tax'];
				$tax_info['trial_tax_per'] = $cost_calculations['trial_tax_per'];
			}
			if(isset($cost_calculations['tax'], $cost_calculations['tax_per']))
			{
				$tax_info['tax']     = $cost_calculations['tax'];
				$tax_info['tax_per'] = $cost_calculations['tax_per'];
			}
			if(!empty($coupon)) // JSON encode this data.
				$metadata['coupon'] = json_encode($coupon);

			if(!empty($tax_info)) // JSON encode this data.
				$metadata['tax_info'] = json_encode($tax_info);

			return $metadata;
		}

		/**
		 * Get a Stripe customer subscription object instance.
		 *
		 * @param string $customer_id Customer ID in Stripe.
		 * @param string $subscription_id Subscription ID in Stripe.
		 *
		 * @return Stripe_Subscription|string Subscription object; else error message.
		 */
		public static function get_customer_subscription($customer_id, $subscription_id)
		{
			// Subscription IDs start with 'sub_', don't continue if not a sub ID.
			if (strpos($subscription_id, 'sub_') !== 0)
				return false;

			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();
			
			try // Obtain existing customer object; else create a new one.
			{
				$customer     = \Stripe\Customer::retrieve($customer_id);
				$subscription = $customer->subscriptions->retrieve($subscription_id);

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $subscription);

				return $subscription; // Stripe subscription object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		/**
		 * Cancel a Stripe customer subscription.
		 *
		 * @param string  $customer_id Customer ID in Stripe.
		 * @param string  $subscription_id Subscription ID in Stripe.
		 *
		 * @param boolean $cancel_at_period_end Defaults to a `TRUE` value (optional).
		 *    If `TRUE`, cancellation is delayed until the end of the current period.
		 *    If `FALSE`, cancellation is NOT delayed; i.e., it occurs immediately.
		 *
		 * @return Stripe_Subscription|string Subscription object; else error message.
		 */
		public static function cancel_customer_subscription($customer_id, $subscription_id, $cancel_at_period_end = TRUE)
		{
			// Subscription IDs start with 'sub_', don't continue if not a sub ID.
			if (strpos($subscription_id, 'sub_') !== 0)
				return false;

			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			try // Attempt to cancel the subscription for this customer.
			{
				// Check for draft/open invoice and void.
				$subscription = \Stripe\Subscription::retrieve($subscription_id);
				if (!empty($subscription->latest_invoice)) {
					$latest_invoice = \Stripe\Invoice::retrieve($subscription->latest_invoice);
					// If draft, finalize to change status to "open".
					if ($latest_invoice->status == 'draft') {
						$latest_invoice = $latest_invoice->finalizeInvoice([
							'auto_advance' => false
						]);
					}
					if ($latest_invoice->status == 'open') {
						$latest_invoice = $latest_invoice->voidInvoice();
					}
				}

				// Delete subscription if cancel now, update if at period end.
				if ($cancel_at_period_end) {
					$subscription = \Stripe\Subscription::update(
						$subscription_id, 
						array('cancel_at_period_end' => true)
					);
				} else {
					$subscription = \Stripe\Subscription::retrieve($subscription_id);
					$subscription = $subscription->delete();
				}

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $subscription);

				return $subscription; // Stripe subscription object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		/**
		 * Receives a Stripe Webhook event object instance.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 *
		 * @return Stripe_Event|string Stripe event object; else error message.
		 */
		public static function get_event()
		{
			if(empty($_REQUEST['s2member_pro_stripe_notify']))
				return ''; // Not applicable.

			$input = @file_get_contents('php://input');
			$event = json_decode($input);

			$input_time = time(); // Initialize.
			$input_vars = array('event_id' => $event->id);

			self::init_stripe_sdk();

			try // Acquire the event from the Stripe servers.
			{
				if(!is_object($event) || empty($event->id))
					throw new exception('Missing event ID.');

				$event = \Stripe\Event::retrieve($event->id);

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $event);

				return $event; // Stripe event object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		/**
		 * Converts a dollar amount into a Stripe amount (usually in cents); based on currency code.
		 *
		 * @param integer|float|string $amount The amount.
		 * @param string               $currency Three character currency code.
		 *
		 * @return integer Amount represented as an integer (always).
		 *
		 * @see https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
		 */
		public static function dollar_amount_to_cents($amount, $currency)
		{
			switch(strtoupper($currency))
			{
				case 'BIF':
				case 'DJF':
				case 'JPY':
				case 'KRW':
				case 'PYG':
				case 'VUV':
				case 'XOF':
				case 'CLP':
				case 'GNF':
				case 'KMF':
				case 'MGA':
				case 'RWF':
				case 'XAF':
				case 'XPF':
					return (integer)$amount;

				default: // In cents.
					return (integer)number_format($amount * 100, 0, '.', '');
			}
		}

		/**
		 * Converts a Stripe amount (usually in cents) into a dollar amount; based on currency code.
		 *
		 * @param integer|float|string $amount The amount.
		 * @param string               $currency Three character currency code.
		 *
		 * @return integer|float Amount represented as an integer or float.
		 *
		 * @see https://support.stripe.com/questions/which-zero-decimal-currencies-does-stripe-support
		 */
		public static function cents_to_dollar_amount($amount, $currency)
		{
			switch(strtoupper($currency))
			{
				case 'BIF':
				case 'DJF':
				case 'JPY':
				case 'KRW':
				case 'PYG':
				case 'VUV':
				case 'XOF':
				case 'CLP':
				case 'GNF':
				case 'KMF':
				case 'MGA':
				case 'RWF':
				case 'XAF':
				case 'XPF':
					return (integer)$amount;

				default: // In dollars.
					return (float)number_format($amount / 100, 2, '.', '');
			}
		}

		/**
		 * Converts a Stripe exception into an error message.
		 *
		 * @param string|exception $exception
		 *
		 * @return string Error message.
		 */
		public static function error_message($exception)
		{
			if($exception && is_string($exception))
				return $exception;

			if($exception instanceof Stripe\Exception\CardException)
			{
				$body  = $exception->getJsonBody();
				$error = $body['error'];
				return sprintf(_x('Error code: <code>%1$s</code>. %2$s.', 's2member-front', 's2member'), esc_html(trim($error['code'], '.')), esc_html(trim($error['message'], '.')));
			}
			if($exception instanceof Stripe\Exception\InvalidRequestException)
				return _x('Invalid parameters to Stripe; please contact the site owner.', 's2member-front', 's2member');

			if($exception instanceof Stripe\Exception\AuthenticationException)
				return _x('Invalid Stripe API keys; please contact the site owner.', 's2member-front', 's2member');

			if($exception instanceof Stripe\Exception\ApiConnectionException)
				return _x('Network communication failure with Stripe; please try again.', 's2member-front', 's2member');

			if($exception instanceof Stripe\Exception\ApiErrorException)
				return _x('Stripe API error; please try again.', 's2member-front', 's2member');

			return _x('Stripe error; please try again.', 's2member-front', 's2member');
		}

		/**
		 * Logs Stripe API communication.
		 *
		 * @param string  $function Name of the caller.
		 *
		 * @param integer $input_time Input time.
		 * @param mixed   $input_vars Input data/vars.
		 *
		 * @param integer $output_time Output time.
		 * @param mixed   $output_vars Output data/vars.
		 */
		public static function log_entry($function, $input_time, $input_vars, $output_time, $output_vars)
		{
			global $current_site, $current_blog;

			if(!$GLOBALS['WS_PLUGIN__']['s2member']['o']['gateway_debug_logs'])
				return; // Nothing to do in this case.

			$logt = c_ws_plugin__s2member_utilities::time_details();
			$logv = c_ws_plugin__s2member_utilities::ver_details();
			$logm = c_ws_plugin__s2member_utilities::mem_details();
			$log4 = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\n".'User-Agent: '.$_SERVER['HTTP_USER_AGENT'];
			$log4 = (is_multisite() && !is_main_site()) ? ($_log4 = $current_blog->domain.$current_blog->path)."\n".$log4 : $log4;
			$log2 = (is_multisite() && !is_main_site()) ? 'stripe-api-4-'.trim(preg_replace('/[^a-z0-9]/i', '-', (!empty($_log4) ? $_log4 : '')), '-').'.log' : 'stripe-api.log';

			if(is_dir($logs_dir = $GLOBALS['WS_PLUGIN__']['s2member']['c']['logs_dir']))
				if(is_writable($logs_dir) && c_ws_plugin__s2member_utils_logs::archive_oversize_log_files())
					if(($log = '-------- Function/Caller: ( '.$function.' ) --------'."\n"))
						if(($log .= '-------- Input vars: ( '.date(DATE_RFC822, $input_time).' ) --------'."\n".print_r($input_vars, TRUE)."\n"))
							if(($log .= '-------- Output string/vars: ( '.date(DATE_RFC822, $output_time).' ) --------'."\n".print_r($output_vars, TRUE)))
								file_put_contents($logs_dir.'/'.$log2,
								                  'LOG ENTRY: '.$logt."\n".$logv."\n".$logm."\n".$log4."\n".
								                  c_ws_plugin__s2member_utils_logs::conceal_private_info($log)."\n\n",
								                  FILE_APPEND);
		}

		/**
		 * Calculates start date for a Recurring Payment Profile.
		 *
		 * @param string $period1 Optional. A "Period Term" combination. Defaults to `0 D`.
		 * @param string $period3 Optional. A "Period Term" combination. Defaults to `0 D`.
		 *
		 * @return integer The start time, a Unix timestamp.
		 */
		public static function start_time($period1 = '', $period3 = '')
		{
			if(!($p1_time = 0) && ($period1 = trim(strtoupper($period1))))
			{
				list($num, $span) = preg_split('/\s+/', $period1, 2);

				$days = 0; // Days start at 0.

				if(is_numeric($num) && !is_numeric($span))
				{
					$days = ($span === 'D') ? 1 : $days;
					$days = ($span === 'W') ? 7 : $days;
					$days = ($span === 'M') ? 30 : $days;
					$days = ($span === 'Y') ? 365 : $days;
				}
				$p1_days = (integer)$num * (integer)$days;
				$p1_time = $p1_days * 86400;
			}
			if(!($p3_time = 0) && ($period3 = trim(strtoupper($period3))))
			{
				list($num, $span) = preg_split('/\s+/', $period3, 2);

				$days = 0; // Days start at 0.

				if(is_numeric($num) && !is_numeric($span))
				{
					$days = ($span === 'D') ? 1 : $days;
					$days = ($span === 'W') ? 7 : $days;
					$days = ($span === 'M') ? 30 : $days;
					$days = ($span === 'Y') ? 365 : $days;
				}
				$p3_days = (integer)$num * (integer)$days;
				$p3_time = $p3_days * 86400;
			}
			$start_time = strtotime('now') + $p1_time + $p3_time;
			$start_time = ($start_time <= 0) ? strtotime('now') : $start_time;
			$start_time = $start_time + 43200; // + 12 hours.

			return $start_time;
		}

		/**
		 * Calculates period in days for Stripe ARB integration.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 *
		 * @param int|string $period Optional. A numeric Period that coincides with ``$term``.
		 * @param string     $term Optional. A Term that coincides with ``$period``.
		 *
		 * @return int A 'Period Term', in days. Defaults to `0`.
		 */
		public static function per_term_2_days($period = '', $term = '')
		{
			if(is_numeric($period) && !is_numeric($term) && ($term = strtoupper($term)))
			{
				$days = 0; // Days start at 0.

				$days = ($term === 'D') ? 1 : $days;
				$days = ($term === 'W') ? 7 : $days;
				$days = ($term === 'M') ? 30 : $days;
				$days = ($term === 'Y') ? 365 : $days;

				return (integer)$period * (integer)$days;
			}
			return 0;
		}

		/**
		 * Determines whether or not tax may apply.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 *
		 * @return bool TRUE if Tax may apply.
		 */
		public static function tax_may_apply()
		{
			if((float)$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_default_tax'] > 0)
				return TRUE;

			if($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_tax_rates'])
				return TRUE;

			return FALSE;
		}

		/**
		 * Handles the return of Tax for Pro-Forms, via AJAX; through a JSON object.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 */
		public static function stripe_ajax_tax()
		{
			if(!empty($_POST['ws_plugin__s2member_pro_stripe_ajax_tax']) && ($nonce = $_POST['ws_plugin__s2member_pro_stripe_ajax_tax']) && (wp_verify_nonce($nonce, 'ws-plugin--s2member-pro-stripe-ajax-tax') || c_ws_plugin__s2member_utils_encryption::decrypt($nonce) === 'ws-plugin--s2member-pro-stripe-ajax-tax'))
				/* A wp_verify_nonce() won't always work here, because s2member-pro.min.js must be cacheable. The output from wp_create_nonce() would go stale.
						So instead, s2member-pro.min.js should use ``c_ws_plugin__s2member_utils_encryption::encrypt()`` as an alternate form of nonce. */
			{
				status_header(200); // Send a 200 OK status header.
				header('Content-Type: text/plain; charset=UTF-8'); // Content-Type text/plain with UTF-8.
				while(@ob_end_clean()) ; // Clean any existing output buffers.

				if(!empty($_POST['ws_plugin__s2member_pro_stripe_ajax_tax_vars']) && is_array($_p_tax_vars = c_ws_plugin__s2member_utils_strings::trim_deep(stripslashes_deep($_POST['ws_plugin__s2member_pro_stripe_ajax_tax_vars']))))
				{
					if(is_array($attr = (!empty($_p_tax_vars['attr'])) ? unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($_p_tax_vars['attr'])) : FALSE))
					{
						$attr = (!empty($attr['coupon'])) ? c_ws_plugin__s2member_pro_stripe_utilities::apply_coupon($attr, $attr['coupon']) : $attr;

						$trial           = ($attr['rr'] !== 'BN' && $attr['tp']) ? TRUE : FALSE; // Is there a trial?
						$sub_total_today = ($trial) ? $attr['ta'] : $attr['ra']; // What is the sub-total today?

						$state    = strip_tags($_p_tax_vars['state']);
						$country  = strip_tags($_p_tax_vars['country']);
						$zip      = strip_tags($_p_tax_vars['zip']);
						$currency = $attr['cc'];
						$desc     = $attr['desc'];

						/* Trial is `null` in this function call. We only need to return what it costs today.
						However, we do tag on a 'trial' element in the array so the ajax routine will know about this. */
						$a = c_ws_plugin__s2member_pro_stripe_utilities::cost(NULL, $sub_total_today, $state, $country, $zip, $currency, $desc);

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
		 * Handles all cost calculations for Stripe.
		 *
		 * Returns an associative array with a possible Percentage Rate, along with the calculated Tax Amount.
		 * Tax calculations are based on State/Province, Country, and/or Zip Code.
		 * Updated to support multiple data fields in it's return value.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 *
		 * @param int|string $trial_sub_total Optional. A numeric Amount/cost of a possible Initial/Trial being offered.
		 * @param int|string $sub_total Optional. A numeric Amount/cost of the purchase and/or Regular Period.
		 * @param string     $state Optional. The State/Province where the Customer is billed.
		 * @param string     $country Optional. The Country where the Customer is billed.
		 * @param int|string $zip Optional. The Postal/Zip Code where the Customer is billed.
		 * @param string     $currency Optional. Expects a 3 character Currency Code.
		 * @param string     $desc Optional. Description of the sale.
		 * @param boolean    $is_bitcoin A Bitcoin transaction?
		 *
		 * @return array Array of calculations.
		 */
		public static function cost($trial_sub_total = '', $sub_total = '', $state = '', $country = '', $zip = '', $currency = '', $desc = '', $is_bitcoin = FALSE)
		{
			$state   = strtoupper(c_ws_plugin__s2member_pro_utilities::full_state($state, ($country = strtoupper($country))));
			$rates   = apply_filters('ws_plugin__s2member_pro_tax_rates_before_cost_calculation', strtoupper($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_tax_rates']), get_defined_vars());
			$default = $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_default_tax'];
			$ps      = _x('%', 's2member-front percentage-symbol', 's2member');

			if($is_bitcoin) // Ignore all of these if it's a Bitcoin transaction.
				$rates = $default = $state = $country = $zip = ''; // Not applicable at this time.

			$trial_tax = $tax = $trial_tax_per = $tax_per = $trial_total = $total = NULL; // Initialize.
			foreach(array('trial_sub_total' => $trial_sub_total, 'sub_total' => $sub_total) as $this_key => $this_sub_total)
			{
				$_default = $this_tax = $this_tax_per = $this_total = $configured_rates = $configured_rate = $location = $rate = $m = NULL;

				if(is_numeric($this_sub_total) && $this_sub_total > 0) // Must have a valid sub-total.
				{
					if($default && preg_match('/%$/', $default)) // Percentage-based.
					{
						if(($_default = (float)$default) > 0)
						{
							$this_tax     = round(($this_sub_total / 100) * $_default, 2);
							$this_tax_per = $_default.$ps;
						}
						else // Else the tax is 0.00.
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
					else // Else the tax is 0.00.
					{
						$this_tax     = 0.00; // No tax.
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
								else // Else the tax is 0.00.
								{
									$this_tax     = 0.00; // No tax.
									$this_tax_per = $configured_rate.$ps;
								}
							}
							else if(($configured_rate = (float)$configured_rate) > 0)
							{
								$this_tax     = round($configured_rate, 2);
								$this_tax_per = ''; // Flat rate.
							}
							else // Else the tax is 0.00.
							{
								$this_tax     = 0.00; // No tax.
								$this_tax_per = ''; // Flat rate.
							}
						}
					}
					$this_total = $this_sub_total + $this_tax;
				}
				else // Else the tax is 0.00.
				{
					$this_tax       = 0.00; // No tax.
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
			return array(
				'trial_sub_total' => number_format($trial_sub_total, 2, '.', ''),
				'sub_total'       => number_format($sub_total, 2, '.', ''),

				'trial_tax'       => number_format($trial_tax, 2, '.', ''),
				'tax'             => number_format($tax, 2, '.', ''),

				'trial_tax_per'   => $trial_tax_per,
				'tax_per'         => $tax_per,

				'trial_total'     => number_format($trial_total, 2, '.', ''),
				'total'           => number_format($total, 2, '.', ''),

				'cur'             => $currency,
				'cur_symbol'      => c_ws_plugin__s2member_utils_cur::symbol($currency),

				'desc'            => $desc
			);
		}

		/**
		 * Checks to see if a Coupon Code was supplied, and if so; what does it provide?
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 *
		 * @param array  $attr An array of Pro-Form Attributes (optional).
		 * @param string $coupon_code Optional. A possible Coupon Code supplied by the Customer.
		 * @param string $return Optional. Return type. One of `response|attr`. Defaults to `attr`.
		 * @param array  $process Optional. An array of additional processing routines to run here.
		 *   One or more of these values: `affiliates-1px-response|affiliates-silent-post|notifications`.
		 *
		 * @return array|string Original array, with prices and description modified when/if a Coupon Code is accepted.
		 *   Or, if ``$return === 'response'``, return a string response, indicating status.
		 */
		public static function apply_coupon($attr = array(), $coupon_code = '', $return = '', $process = array())
		{
			$coupons = new c_ws_plugin__s2member_pro_coupons();
			return $coupons->apply($attr, $coupon_code, $return, $process);
		}


		// Since 190914 ------------------------

		/**
		 * Attaches a Payment Method to a Customer if card not there yet.
		 * Gets the existing Payment Method for that card if already attached.
		 *
		 * @param string $customer_id Customer ID in Stripe.
		 * @param string $payment_method_id Payment Method ID.
		 *
		 * @return object Attached PaymentMethod for this card, else error message.
		 */
		public static function attached_card_payment_method($customer_id, $payment_method_id)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			try
			{
				// Let's get the new PaymentMethod.
				$payment_method = \Stripe\PaymentMethod::retrieve($payment_method_id);
				$attach = true; 

				//!!! It may be a problem to use the previous PM if the new one has Billing info.
				// Let's get all the cards this customer has.
				// $customer_pms = \Stripe\PaymentMethod::all([
				// 		'customer' => $customer_id,
				// 		'type'     => 'card',
				// 		'limit'    => 100,
				// ]);
				// Let's see if he doesn't already have this card.
				// if (!empty($customer_pms)) {
				// 	foreach ($customer_pms as $customer_pm) {
				// 		if ($payment_method->card->fingerprint == $customer_pm->card->fingerprint
				// 			&& $payment_method->card->exp_year == $customer_pm->card->exp_year
				// 			&& $payment_method->card->exp_month == $customer_pm->card->exp_month
				// 		) {
				// 			// It's already there, so we won't attach it again.
				// 			$attach = false;
				// 			// Let's use this card's existing Payment Method.
				// 			$payment_method = $customer_pm;
				// 			break;
				// 		}
				// 	}
				// }

				if ($attach)
					$payment_method = $payment_method->attach(['customer' => $customer_id]);

				// Let's set this payment method as the default for this customer's invoices.
				$customer_update = array(
					'invoice_settings' => array(
						'default_payment_method' => $payment_method->id,
					),
				);
				\Stripe\Customer::update($customer_id, $customer_update);

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $payment_method);

				return $payment_method;
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		/**
		 * Create a Payment Intent.
		 *
		 * @param string               $cus_id Customer ID in Stripe.
		 * @param string               $pm_id Payment Method ID in Stripe.
		 * @param integer|float|string $amount The amount to charge.
		 * @param string               $currency Three character currency code.
		 * @param string               $description Description of the charge.
		 * @param array                $metadata Any additional metadata (optional).
		 * @param array                $post_vars Pro-Form post vars (optional).
		 * @param array                $cost_calculations Pro-Form cost calculations (optional).
		 *
		 * @return object|string PaymentIntent object; else error message.
		 */
		public static function create_payment_intent($cus_id, $pm_id, $amount, $currency, $description, $metadata = array(), $post_vars = array(), $cost_calculations = array())
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			$metadata = array_merge(self::_additional_intent_metadata($post_vars, $cost_calculations), (array)$metadata);

			try // Attempt to create the Payment Intent.
			{
				$intent = array(
					'amount'               => self::dollar_amount_to_cents($amount, $currency),
					'currency'             => $currency,
					'customer'             => $cus_id,
					'payment_method'       => $pm_id,
					'confirmation_method'  => 'manual',
					'confirm'              => true,
					'description'          => $description, 
					'metadata'             => $metadata,
					'statement_descriptor' => $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description'],
				);
				if(!trim($intent['statement_descriptor']))
					unset($intent['statement_descriptor']);

				$intent = \Stripe\PaymentIntent::create($intent, array(
					'idempotency_key' => md5(serialize($intent))
				));
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $intent);

				return $intent; // Stripe charge object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		//!!! Based on self::create_customer_charge
		public static function _additional_intent_metadata($post_vars = array(), $cost_calculations = array())
		{
			$post_vars         = (array)$post_vars;
			$cost_calculations = (array)$cost_calculations;
			$metadata          = array(); // Initialize.

			if(!empty($post_vars['coupon']))
				$coupon['code'] = $post_vars['coupon'];

			if(isset($cost_calculations['trial_tax'], $cost_calculations['trial_tax_per'])
			   && isset($post_vars['attr']['tp'], $cost_calculations['trial_total'])
			   && $post_vars['attr']['tp'] && $cost_calculations['trial_total'] > 0
			) // Charge is for a trial amount in this case.
			{
				$tax_info['tax']     = $cost_calculations['trial_tax'];
				$tax_info['tax_per'] = $cost_calculations['trial_tax_per'];
			}
			else if(isset($cost_calculations['tax'], $cost_calculations['tax_per']))
			{
				$tax_info['tax']     = $cost_calculations['tax'];
				$tax_info['tax_per'] = $cost_calculations['tax_per'];
			}
			if(!empty($coupon)) // JSON encode this data.
				$metadata['coupon'] = json_encode($coupon);

			if(!empty($tax_info)) // JSON encode this data.
				$metadata['tax_info'] = json_encode($tax_info);

			return $metadata;
		}

		/**
		 * Create a Setup Intent.
		 *
		 * @param string               $cus_id Customer ID in Stripe.
		 * @param string               $pm_id PaymentMethod ID in Stripe.
		 *
		 * @return object|string SetupIntent object; else error message.
		 */
		public static function create_setup_intent($cus_id, $pm_id)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			try // Attempt to create the Setup Intent.
			{
				$intent = array(
					'customer'       => $cus_id,
					'payment_method' => $pm_id,
					'confirm'        => true,
				);

				$intent = \Stripe\SetupIntent::create($intent);
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $intent);

				return $intent; // Stripe charge object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		/**
		 * Check the SetupIntent's status.
		 * 
		 * @param string|int $pi_id SetupIntent id.
		 * @param object $intent Optional. StetupIntent object.
		 * 
		 * @return array|string Response if requires action, else empty string.
		 */
		public static function handle_setup_intent_status($seti_id, $intent='')
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			// If we don't have the intent's object, let's get it.
			if (!is_object($intent))
				$intent = \Stripe\SetupIntent::retrieve($seti_id);

			// Do we have it?
			if (!is_object($intent))
				return $global_response = array('response' => $intent, 'error' => TRUE);

			self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $intent->status);

			// requires_action
			// Pass the intent's client secret for stripe.handleCardStatus.
			if ($intent->status == 'requires_action') {
				$GLOBALS['ws_plugin__s2member_pro_stripe']['seti_secret'] = $intent->client_secret;
				return $global_response = array('response' => _x('Action required: 3D Secure authorization.', 's2member-front', 's2member'), 'error' => TRUE);
			}

			// requires_payment_method
			if ($intent->status == 'requires_payment_method')
				return $global_response = array('response' => _x('Please try again with a different card.', 's2member-front', 's2member'), 'error' => TRUE);

			// succeeded
			if ($intent->status == 'succeeded')
				return $intent;
		}

		/**
		 * Check the PaymentIntent's status.
		 * 
		 * @param string|int $pi_id PaymentIntent id.
		 * 
		 * @return object|array PaymentIntent object if succeeded, or response array with error.
		 */
		public static function handle_payment_intent_status($pi_id)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			// Get the intent
			$intent = self::get_payment_intent($pi_id);

			// Do we have it?
			if (!is_object($intent))
				return $global_response = array('response' => $intent, 'error' => TRUE);

			// requires_confirmation
			if ($intent->status == 'requires_confirmation')
				try {
					$intent->confirm();
				}
				catch (exception $exception) {
					self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);
					return $global_response = array('response' => self::error_message($exception), 'error' => TRUE);
				}
		
			self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $intent->status);

			// requires_action
			// Pass the intent's client secret for stripe.handleCard...
			if ($intent->status == 'requires_action') {
				$GLOBALS['ws_plugin__s2member_pro_stripe']['pi_secret'] = $intent->client_secret;
				return $global_response = array('response' => _x('Action required: 3D Secure authorization.', 's2member-front', 's2member'), 'error' => TRUE);
			}

			// requires_payment_method
			if ($intent->status == 'requires_payment_method')
				return $global_response = array('response' => _x('The payment failed, please try again with a different card.', 's2member-front', 's2member'), 'error' => TRUE);

			// succeeded
			if ($intent->status == 'succeeded')
				return $intent;
		}

		/**
		 * Get an existing PaymentIntent.
		 * 
		 * @param string|int $pi_id 
		 * 
		 * @return object The PaymentIntent object
		 */
		public static function get_payment_intent($pi_id)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			// Get the intent
			$intent = \Stripe\PaymentIntent::retrieve(
				array(
					'id'     => $pi_id,
					// Expand this to get the subscription id: $intent->charges->data['0']['invoice']->subscription
					'expand' => array('charges.data.invoice'),
				)
			);

			self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $intent);

			return $intent;
		}

		/**
		 * Update an existing Payment Intent.
		 * 
		 * @param string|int $pi_id 
		 * @param array      $args Array of arguments to update. https://stripe.com/docs/api/payment_intents/update
		 * 
		 * @return object The PaymentIntent object
		 */
		public static function udpate_payment_intent($pi_id, $args)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			// Get the intent
			$intent = \Stripe\PaymentIntent::update($pi_id, $args);

			self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $intent);

			return $intent;
		}

		/**
		 * Update an existing Setup Intent.
		 * 
		 * @param string|int $seti_id 
		 * @param array      $args Array of arguments to update. https://stripe.com/docs/api/setup_intents/update
		 * 
		 * @return object The SetupIntent object
		 */
		public static function udpate_setup_intent($seti_id, $args)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			// Get the intent
			$intent = \Stripe\SetupIntent::update($seti_id, $args);

			self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $intent);

			return $intent;
		}

		/**
		 * Get a Stripe Billing Product object instance.
		 *
		 * @param array $name The name for this product ('level:ccaps').
		 *
		 * @return Product|string Product object; else error message.
		 */
		public static function get_product($name)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			$product_id = 's2_prod_'.md5($name.$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description']);

			try // Attempt to get an existing product; else create a new one.
			{
				try // Try to find an existing product.
				{
					$product = \Stripe\Product::retrieve($product_id);
				}
				catch(exception $exception) // Else create one.
				{
					$product = array(
						'id'                   => $product_id,
						'name'                 => $name,
						'type'                 => 'service',
						'statement_descriptor' => $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description'],
					);
					if(!trim($product['statement_descriptor']))
						unset($product['statement_descriptor']);

					$product = \Stripe\Product::create($product);
				}
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $product);

				return $product; // Stripe product object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}



	}
}
