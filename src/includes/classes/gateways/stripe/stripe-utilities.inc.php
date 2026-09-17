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
		 * Prepares durable Gateway Checkout state for a Stripe Pro-Form submission.
		 *
		 * @since 260830.0052
		 *
		 * @param array   $post_vars      Stripe Pro-Form post vars, updated with the authoritative Gateway Checkout identity.
		 * @param string  $operation      Gateway Checkout operation; `payment` or `subscription`.
		 * @param array   $purchase_terms Final server-side purchase terms.
		 * @param integer $user_id        WordPress user ID, if known.
		 *
		 * @return array|bool Gateway Checkout state, else FALSE.
		 */
		public static function prepare_gateway_checkout(&$post_vars, $operation = '', $purchase_terms = array(), $user_id = 0)
		{
			$post_vars = (array)$post_vars;
			$gateway_checkout_id = !empty($post_vars['gateway_checkout_id']) ? (string)$post_vars['gateway_checkout_id'] : '';
			$gateway_checkout_token = !empty($post_vars['gateway_checkout_token']) ? (string)$post_vars['gateway_checkout_token'] : '';
			//260907.2110 TO-DO: Move generic post-Notify fulfillment checkpoints into shared Gateway Checkout and apply them to Stripe so recovered successful checkouts cannot repeat outer side effects such as notifications, list processing, cancellations, or equivalent fulfillment work.
			$fingerprint = c_ws_plugin__s2member_gateway_checkouts::purchase_fingerprint((array)$purchase_terms);
			$existing_state = ($gateway_checkout_id && $gateway_checkout_token && c_ws_plugin__s2member_gateway_checkouts::browser_token_verify($gateway_checkout_id, $gateway_checkout_token))
				? c_ws_plugin__s2member_gateway_checkouts::get($gateway_checkout_id) : FALSE;
			$state = c_ws_plugin__s2member_gateway_checkouts::create_or_resume('stripe', $operation, $gateway_checkout_id, $gateway_checkout_token, $fingerprint, $user_id);

			if(!$state && $existing_state && (string)$existing_state['gateway'] === 'stripe' && (string)$existing_state['operation'] === (string)$operation
			   && empty($existing_state['gateway_ids']) && empty($existing_state['gateway_status']) && (string)$existing_state['fulfillment_status'] === 'pending')
			{
				//260830.0052 Purchase terms may change before gateway work begins (e.g., checkout option/coupon changes); only a pristine checkout can be replaced automatically.
				$state = c_ws_plugin__s2member_gateway_checkouts::create('stripe', $operation, $fingerprint, $user_id);
			}
			if(!$state)
				return FALSE;

			//260830.0059 If server validation replaced a stale/mismatched browser identity, prevent history.state from restoring the superseded checkout on the retry form.
			if($gateway_checkout_id && !hash_equals((string)$gateway_checkout_id, (string)$state['id']))
				$post_vars['gateway_checkout_reset'] = '1';

			$post_vars['gateway_checkout_id']    = (string)$state['id'];
			$post_vars['gateway_checkout_token'] = c_ws_plugin__s2member_gateway_checkouts::browser_token($state['id']);
			$post_vars['request_id']              = (string)$state['id']; //260830.0052 Compatibility alias for the v260829 same-render idempotency field.
			//260830.0052 Once durable state exists, only Stripe object IDs recovered from that signed checkout are authoritative; never trust independently posted intent/subscription IDs.
			$post_vars['pi_id']   = !empty($state['gateway_ids']['payment_intent_id']) ? (string)$state['gateway_ids']['payment_intent_id'] : '';
			$post_vars['seti_id'] = !empty($state['gateway_ids']['setup_intent_id']) ? (string)$state['gateway_ids']['setup_intent_id'] : '';
			$post_vars['sub_id']  = !empty($state['gateway_ids']['subscription_id']) ? (string)$state['gateway_ids']['subscription_id'] : '';

			return $state;
		}

		/**
		 * Updates Stripe object IDs/status/context in Gateway Checkout state.
		 *
		 * @since 260830.0052
		 *
		 * @param string $gateway_checkout_id Gateway Checkout ID.
		 * @param array  $gateway_ids         Stripe object IDs to merge into existing state.
		 * @param string $gateway_status      Optional Stripe status.
		 * @param array  $context             Stripe-specific context to merge into existing state.
		 * @param string $fulfillment_status  Optional local fulfillment status.
		 *
		 * @return array|bool Updated state, else FALSE.
		 */
		public static function update_gateway_checkout($gateway_checkout_id = '', $gateway_ids = array(), $gateway_status = '', $context = array(), $fulfillment_status = '')
		{
			$state = c_ws_plugin__s2member_gateway_checkouts::get($gateway_checkout_id);
			if(!$state || (string)$state['gateway'] !== 'stripe')
				return FALSE;

			$updates = array(
				'gateway_ids' => array_merge((array)$state['gateway_ids'], (array)$gateway_ids),
				'context'     => array_merge((array)$state['context'], (array)$context),
			);
			if($gateway_status !== '')
				$updates['gateway_status'] = (string)$gateway_status;
			if($fulfillment_status !== '')
				$updates['fulfillment_status'] = (string)$fulfillment_status;

			return c_ws_plugin__s2member_gateway_checkouts::update($gateway_checkout_id, $updates);
		}

		/**
		 * Gets the Stripe Gateway Checkout state represented by Pro-Form post vars.
		 *
		 * @since 260830.0052
		 *
		 * @param array $post_vars Stripe Pro-Form post vars.
		 *
		 * @return array|bool Gateway Checkout state, else FALSE.
		 */
		public static function gateway_checkout_state($post_vars = array())
		{
			$gateway_checkout_id = !empty($post_vars['gateway_checkout_id']) ? (string)$post_vars['gateway_checkout_id'] : '';
			$gateway_checkout_token = !empty($post_vars['gateway_checkout_token']) ? (string)$post_vars['gateway_checkout_token'] : '';
			$state = ($gateway_checkout_id && $gateway_checkout_token && c_ws_plugin__s2member_gateway_checkouts::browser_token_verify($gateway_checkout_id, $gateway_checkout_token))
				? c_ws_plugin__s2member_gateway_checkouts::get($gateway_checkout_id) : FALSE;

			if(!$state || (string)$state['gateway'] !== 'stripe')
				return FALSE;
			//260830.0135 A browser token cannot resume or mutate a checkout already bound to another logged-in WordPress user.
			if(!empty($state['user_id']) && (int)$state['user_id'] !== get_current_user_id())
				return FALSE;

			return $state;
		}

		/**
		 * Advances a terminal Stripe object's generation while preserving the logical Gateway Checkout.
		 *
		 * @since 260830.0052
		 *
		 * @param string $gateway_checkout_id Gateway Checkout ID.
		 * @param string $object_type         `payment` or `subscription`.
		 * @param array  $clear_gateway_ids   Gateway ID keys to clear for the replacement object.
		 *
		 * @return integer|bool New generation number, else FALSE.
		 */
		public static function advance_gateway_checkout_generation($gateway_checkout_id = '', $object_type = '', $clear_gateway_ids = array())
		{
			$state = c_ws_plugin__s2member_gateway_checkouts::get($gateway_checkout_id);
			if(!$state || !$object_type)
				return FALSE;

			$key = 'stripe_'.sanitize_key((string)$object_type).'_generation';
			$generation = max(1, (int)@$state['context'][$key]) + 1;
			$gateway_ids = array();
			foreach((array)$clear_gateway_ids as $gateway_id_key)
				$gateway_ids[(string)$gateway_id_key] = '';

			return self::update_gateway_checkout($gateway_checkout_id, $gateway_ids, '', array($key => $generation)) ? $generation : FALSE;
		}

		/**
		 * Finds a Stripe PaymentIntent previously created for a Gateway Checkout generation.
		 *
		 * @since 260830.0052
		 *
		 * @param string  $customer_id         Stripe Customer ID.
		 * @param string  $gateway_checkout_id Gateway Checkout ID.
		 * @param integer $generation          Gateway Checkout payment generation.
		 *
		 * @return object|bool PaymentIntent, else FALSE.
		 */
		public static function find_gateway_checkout_payment_intent($customer_id = '', $gateway_checkout_id = '', $generation = 1)
		{
			if(!$customer_id || !$gateway_checkout_id)
				return FALSE;

			self::init_stripe_sdk();
			$state = c_ws_plugin__s2member_gateway_checkouts::get($gateway_checkout_id);
			if(!$state || (string)$state['gateway'] !== 'stripe')
				return FALSE;

			if(!empty($state['gateway_ids']['payment_intent_id']))
			{
				try
				{
					$intent = \Stripe\PaymentIntent::retrieve((string)$state['gateway_ids']['payment_intent_id']);
					$metadata_generation = !empty($intent->metadata->s2member_gateway_checkout_generation) ? (int)$intent->metadata->s2member_gateway_checkout_generation : 1;
					if((string)$intent->customer === (string)$customer_id && !empty($intent->metadata->s2member_gateway_checkout_id)
					   && hash_equals((string)$gateway_checkout_id, (string)$intent->metadata->s2member_gateway_checkout_id) && $metadata_generation === (int)$generation)
						return $intent;
				}
				catch(exception $exception)
				{
					//260830.0135 A stale locally stored Stripe ID must not suppress metadata-based recovery of the object created for this Gateway Checkout.
				}
			}

			$params = array('customer' => $customer_id, 'limit' => 100);
			if(!empty($state['created_at']))
				$params['created'] = array('gte' => max(0, (int)$state['created_at'] - 60));
			$intents = \Stripe\PaymentIntent::all($params); //260830.0308 Let lookup failures abort checkout instead of risking a duplicate object.
			if(!empty($intents->data) && is_array($intents->data))
				foreach($intents->data as $intent)
				{
					$metadata_generation = !empty($intent->metadata->s2member_gateway_checkout_generation) ? (int)$intent->metadata->s2member_gateway_checkout_generation : 1;
					if(!empty($intent->metadata->s2member_gateway_checkout_id) && hash_equals((string)$gateway_checkout_id, (string)$intent->metadata->s2member_gateway_checkout_id) && $metadata_generation === (int)$generation)
						return $intent;
				}
			return FALSE;
		}

		/**
		 * Finds a Stripe Subscription previously created for a Gateway Checkout generation.
		 *
		 * @since 260830.0052
		 *
		 * @param string  $customer_id         Stripe Customer ID.
		 * @param string  $gateway_checkout_id Gateway Checkout ID.
		 * @param integer $generation          Gateway Checkout subscription generation.
		 *
		 * @return object|bool Subscription, else FALSE.
		 */
		public static function find_gateway_checkout_subscription($customer_id = '', $gateway_checkout_id = '', $generation = 1)
		{
			if(!$customer_id || !$gateway_checkout_id)
				return FALSE;

			self::init_stripe_sdk();
			$state = c_ws_plugin__s2member_gateway_checkouts::get($gateway_checkout_id);
			if(!$state || (string)$state['gateway'] !== 'stripe')
				return FALSE;

			$subscription = FALSE;
			if(!empty($state['gateway_ids']['subscription_id']))
			{
				try
				{
					$subscription = \Stripe\Subscription::retrieve((string)$state['gateway_ids']['subscription_id']);
					$metadata_generation = !empty($subscription->metadata->s2member_gateway_checkout_generation) ? (int)$subscription->metadata->s2member_gateway_checkout_generation : 1;
					if((string)$subscription->customer !== (string)$customer_id || empty($subscription->metadata->s2member_gateway_checkout_id)
					   || !hash_equals((string)$gateway_checkout_id, (string)$subscription->metadata->s2member_gateway_checkout_id) || $metadata_generation !== (int)$generation)
						$subscription = FALSE;
				}
				catch(exception $exception)
				{
					$subscription = FALSE; // A stale local ID must still fall through to metadata recovery.
				}
			}

			if(!$subscription)
			{
				$params = array('customer' => $customer_id, 'status' => 'all', 'limit' => 100);
				if(!empty($state['created_at']))
					$params['created'] = array('gte' => max(0, (int)$state['created_at'] - 60));
				$subscriptions = \Stripe\Subscription::all($params); //260830.0308 Let lookup failures abort checkout instead of risking a duplicate object.
				if(!empty($subscriptions->data) && is_array($subscriptions->data))
					foreach($subscriptions->data as $_subscription)
					{
						$metadata_generation = !empty($_subscription->metadata->s2member_gateway_checkout_generation) ? (int)$_subscription->metadata->s2member_gateway_checkout_generation : 1;
						if(!empty($_subscription->metadata->s2member_gateway_checkout_id) && hash_equals((string)$gateway_checkout_id, (string)$_subscription->metadata->s2member_gateway_checkout_id) && $metadata_generation === (int)$generation)
						{
							$subscription = \Stripe\Subscription::retrieve((string)$_subscription->id);
							break;
						}
					}
			}
			if(!$subscription)
				return FALSE;

			//260830.0308 Subscription creation requests expand these objects; recovered subscriptions need equivalent expansion for the existing checkout handlers.
			if(!empty($subscription->latest_invoice) && !is_object($subscription->latest_invoice))
				$subscription->latest_invoice = \Stripe\Invoice::retrieve((string)$subscription->latest_invoice);
			if(!empty($subscription->latest_invoice->payment_intent) && !is_object($subscription->latest_invoice->payment_intent))
				$subscription->latest_invoice->payment_intent = \Stripe\PaymentIntent::retrieve((string)$subscription->latest_invoice->payment_intent);
			if(!empty($subscription->pending_setup_intent) && !is_object($subscription->pending_setup_intent))
				$subscription->pending_setup_intent = \Stripe\SetupIntent::retrieve((string)$subscription->pending_setup_intent);

			return $subscription;
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
			$gateway_checkout_state = self::gateway_checkout_state($post_vars);
			$gateway_checkout_id = $gateway_checkout_state ? (string)$gateway_checkout_state['id'] : '';
			$customer = '';

			try // Obtain existing customer object; else create a new one.
			{
				if($gateway_checkout_state)
				{
					//260830.0308 Recover the exact Customer already tied to this checkout before falling back to s2Member's normal stored-ID/email lookup.
					if(!empty($gateway_checkout_state['gateway_ids']['customer_id']))
					{
						try
						{
							$customer = \Stripe\Customer::retrieve((string)$gateway_checkout_state['gateway_ids']['customer_id']);
						}
						catch(exception $exception)
						{
							$customer = ''; // A stale local ID must still fall through to metadata recovery.
						}
					}
					if((empty($customer) || !is_object($customer)) && $email && is_object($customers = \Stripe\Customer::all(array('email' => (string)$email, 'limit' => 100))) && !empty($customers->data) && is_array($customers->data))
						foreach($customers->data as $_customer)
							if(empty($_customer->deleted) && !empty($_customer->metadata->s2member_gateway_checkout_id)
							   && hash_equals($gateway_checkout_id, (string)$_customer->metadata->s2member_gateway_checkout_id))
							{
								$customer = $_customer;
								break;
							}
				}

				//260408 First try the stored Stripe customer id, but still fall back to email lookup if that id is stale or no longer retrievable.
				if((empty($customer) || !is_object($customer)) && $user_id && ($customer_id = get_user_option('s2member_subscr_cid', $user_id)))
				{
					try
					{
						$customer = \Stripe\Customer::retrieve($customer_id);
					}
					catch(exception $exception)
					{
						$customer = '';
					}
				}

				//260830.1632 !!! TO-DO: Reuse the earlier checkout email lookup here when available, preserving precedence: exact checkout metadata match -> stored customer ID -> ordinary email fallback. This can avoid a second Stripe Customer::all() call without weakening lost-response recovery.
				//260408 A stale stored customer id should not prevent reusing an existing customer found by email.
				if((empty($customer) || !is_object($customer)) && !empty($email))
				{
					try
					{
						if(is_object($customers = \Stripe\Customer::all(array('email' => $email, 'limit' => 1))))
							$customer = (isset($customers->data[0])) ? $customers->data[0] : '';
					}
					catch(exception $exception)
					{
						$customer = '';
					}
				}

				if(!empty($customer) && is_object($customer) && $metadata)
				{
					foreach($metadata as $_key => $_value)
						$customer->metadata->{$_key} = $_value;
					unset($_key, $_value); // Housekeeping.

					$customer->save(); // Update.
				}

				// If we don't have a Customer, let's create one.
				if(empty($customer) || !is_object($customer)) {
					$args = array(
						'email'    => $email,
						'name'     => trim($fname.' '.$lname),
						'metadata' => $metadata,
					);
					if($gateway_checkout_id)
						$args['metadata']['s2member_gateway_checkout_id'] = $gateway_checkout_id;
					// if we don't have a state, we didn't collect billing address.
					if (!empty($post_vars['state'])) {
						$args['address'] = array(
							'line1'       => $post_vars['street'],
							'city'        => $post_vars['city'],
							'state'       => $post_vars['state'],
							'country'     => $post_vars['country'],
							'postal_code' => $post_vars['zip'],
						);
					}
					//260830.0135 Customer creation is part of the same durable checkout; retry it idempotently if Stripe created the Customer but its response was lost.
					$customer = \Stripe\Customer::create($args, $gateway_checkout_id ? array('idempotency_key' => 's2member-cus-'.$gateway_checkout_id) : array());
				}
				if($gateway_checkout_state && is_object($customer) && !empty($customer->id))
					self::update_gateway_checkout((string)$gateway_checkout_state['id'], array('customer_id' => (string)$customer->id));

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
					: (bool)$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_reject_prepaid'];

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

			if($customer_id && !self::cancel_incomplete_customer_subscriptions($customer_id))
			{
				$error = 'Unable to cancel incomplete Stripe subscription(s) before creating a new charge.';

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $error);

				return $error;
			}

			try // Attempt to charge the customer.
			{
				$charge = array(
					'customer'                    => $customer_id,
					'description'                 => $description, 
					'metadata'                    => $metadata,
					'amount'                      => self::dollar_amount_to_cents($amount, $currency),
					'currency'                    => $currency,
					'statement_descriptor_suffix' => $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description'],
				);
				if(!trim($charge['statement_descriptor_suffix']))
					unset($charge['statement_descriptor_suffix']);

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
			$metadata['recurring_times'] = $shortcode_attrs['rr'] && $shortcode_attrs['rrt'] ? (int)$shortcode_attrs['rrt'] : -1;
			$trial_period_days           = self::per_term_2_days($shortcode_attrs['tp'], $shortcode_attrs['tt']);
			$interval_term = "";
			switch (strtoupper($shortcode_attrs['rt']))
			{
				case 'D':
					$interval_term = "day";
					break;
				case 'W':
					$interval_term = "week";
					break;
				case 'M':
					$interval_term = "month";
					break;
				case 'Y':
					$interval_term = "year";
					break;
			}
			$interval_period = is_numeric($shortcode_attrs['rp'])? (integer)$shortcode_attrs['rp'] : 0;

			// The access is more correct for the product's name, and will avoid duplicate products,
			// but the shortcode's description is probably better in this case...
			// $product_name = trim('level'$shortcode_attrs['level'].':'.$shortcode_attrs['ccaps']);
			$product      = self::get_product($name);

			$plan_id      = 's2_plan_'.md5($amount.$currency.$name.$trial_period_days.$interval_period.$interval_term.serialize($metadata).$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description']);

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
						'interval'          => $interval_term,
						'interval_count'    => $interval_period,
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
			$gateway_checkout_state = self::gateway_checkout_state($post_vars);
			$gateway_checkout_id = $gateway_checkout_state ? (string)$gateway_checkout_state['id'] : '';
			$generation = $gateway_checkout_state ? max(1, (int)@$gateway_checkout_state['context']['stripe_subscription_generation']) : 1;
			if($gateway_checkout_id)
			{
				$metadata['s2member_gateway_checkout_id'] = $gateway_checkout_id;
				$metadata['s2member_gateway_checkout_generation'] = (string)$generation;
			}
			//260830.0052 Gateway Checkout IDs survive reloads and are the preferred Stripe idempotency identity; retain request_id for pre-upgrade rendered forms.
			$stripe_request_id = $gateway_checkout_id ? $gateway_checkout_id : (!empty($post_vars['request_id']) && preg_match('/^[A-Za-z0-9-]{20,64}$/', (string)$post_vars['request_id']) ? (string)$post_vars['request_id'] : '');

			//260830.0052 Recover this checkout's subscription before the legacy incomplete-subscription cleanup, or that cleanup could cancel the object a lost-response retry needs.
			$existing_subscription = FALSE;
			if($gateway_checkout_id)
			{
				try
				{
					$existing_subscription = self::find_gateway_checkout_subscription($customer_id, $gateway_checkout_id, $generation);
				}
				catch(exception $exception)
				{
					//260830.0308 A failed reconciliation lookup must stop checkout instead of falling through to another subscription creation.
					self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);
					return self::error_message($exception);
				}
			}
			if(is_object($existing_subscription))
			{
				if((string)$existing_subscription->status === 'incomplete_expired')
				{
					$generation = self::advance_gateway_checkout_generation($gateway_checkout_id, 'subscription', array('subscription_id', 'payment_intent_id', 'setup_intent_id', 'invoice_item_id'));
					if(!$generation)
						return _x('Unable to prepare this subscription retry. Please try again.', 's2member-front', 's2member');
					$metadata['s2member_gateway_checkout_generation'] = (string)$generation;
				}
				else
				{
					$gateway_ids = array('customer_id' => (string)$customer_id, 'subscription_id' => (string)$existing_subscription->id);
					if(!empty($existing_subscription->latest_invoice->payment_intent->id))
						$gateway_ids['payment_intent_id'] = (string)$existing_subscription->latest_invoice->payment_intent->id;
					if(!empty($existing_subscription->pending_setup_intent->id))
						$gateway_ids['setup_intent_id'] = (string)$existing_subscription->pending_setup_intent->id;
					self::update_gateway_checkout($gateway_checkout_id, $gateway_ids, !empty($existing_subscription->status) ? (string)$existing_subscription->status : '');
					self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $existing_subscription);
					return $existing_subscription;
				}
			}

			if(!self::cancel_incomplete_customer_subscriptions($customer_id, $gateway_checkout_id))
			{
				$error = 'Unable to cancel incomplete Stripe subscription(s) before creating a new subscription.';
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $error);
				return $error;
			}

			// Do we have a paid trial.
			if(!empty($post_vars['attr']['tp']) && !empty($cost_calculations['trial_total']) && $cost_calculations['trial_total'] > 0)
			{
				// Create an invoice item for it, so it gets added to the trial's invoice.
				$item = array(
					'customer'    => $customer_id,
					'amount'      => self::dollar_amount_to_cents($cost_calculations['trial_total'], $cost_calculations['cur']),
					'currency'    => $cost_calculations['cur'],
					'description' => 'Initial period',
					'metadata'    => $gateway_checkout_id ? array(
						's2member_gateway_checkout_id'         => $gateway_checkout_id,
						's2member_gateway_checkout_generation' => (string)$generation,
					) : array(),
				);
				$invoice_item = FALSE;
				if($gateway_checkout_state)
				{
					if(!empty($gateway_checkout_state['gateway_ids']['invoice_item_id']))
					{
						try
						{
							$_invoice_item = \Stripe\InvoiceItem::retrieve((string)$gateway_checkout_state['gateway_ids']['invoice_item_id']);
							$_generation = !empty($_invoice_item->metadata->s2member_gateway_checkout_generation) ? (int)$_invoice_item->metadata->s2member_gateway_checkout_generation : 1;
							if((string)$_invoice_item->customer === (string)$customer_id && empty($_invoice_item->invoice) && !empty($_invoice_item->metadata->s2member_gateway_checkout_id)
							   && hash_equals($gateway_checkout_id, (string)$_invoice_item->metadata->s2member_gateway_checkout_id) && $_generation === (int)$generation)
								$invoice_item = $_invoice_item;
						}
						catch(exception $exception)
						{
							$invoice_item = FALSE;
						}
					}
					if(!$invoice_item)
					{
						try
						{
							$invoice_items = \Stripe\InvoiceItem::all(array('customer' => $customer_id, 'pending' => TRUE, 'limit' => 100));
						}
						catch(exception $exception)
						{
							//260830.0308 A failed reconciliation lookup must stop checkout instead of risking a duplicate paid-trial item.
							self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);
							return self::error_message($exception);
						}
						if(!empty($invoice_items->data) && is_array($invoice_items->data))
							foreach($invoice_items->data as $_invoice_item)
							{
								$_generation = !empty($_invoice_item->metadata->s2member_gateway_checkout_generation) ? (int)$_invoice_item->metadata->s2member_gateway_checkout_generation : 1;
								if(!empty($_invoice_item->metadata->s2member_gateway_checkout_id) && hash_equals($gateway_checkout_id, (string)$_invoice_item->metadata->s2member_gateway_checkout_id) && $_generation === (int)$generation)
								{
									$invoice_item = $_invoice_item;
									break;
								}
							}
					}
				}
				if($invoice_item)
					self::update_gateway_checkout($gateway_checkout_id, array('invoice_item_id' => (string)$invoice_item->id));
				else
				{
					$idempotency_key = $gateway_checkout_id ? 's2member-trial-'.$gateway_checkout_id.'-'.$generation : ($stripe_request_id ? 's2member-trial-'.$stripe_request_id : md5(serialize($item)));
					$invoice_item = \Stripe\InvoiceItem::create($item, array('idempotency_key' => $idempotency_key));
					if($gateway_checkout_id && is_object($invoice_item) && !empty($invoice_item->id))
						self::update_gateway_checkout($gateway_checkout_id, array('invoice_item_id' => (string)$invoice_item->id));
				}
			}

			try // Attempt to create a new subscription for this customer.
			{
				$customer = \Stripe\Customer::retrieve($customer_id);

				if(!empty($post_vars['pm_id']))
				{
					if(!is_object($payment_method = self::attached_card_payment_method($customer_id, $post_vars['pm_id'])))
					{
						self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $payment_method);

						return $payment_method;
					}
					if(empty($payment_method->customer))
					{
						try
						{
							$payment_method->attach(array('customer' => $customer_id));
							$payment_method = \Stripe\PaymentMethod::retrieve($payment_method->id);
						}
						catch(exception $exception)
						{
							self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

							return self::error_message($exception);
						}
					}
					$default_payment_method = (string)$payment_method->id;
				}
				else if(!empty($customer->invoice_settings->default_payment_method))
					$default_payment_method = (string)$customer->invoice_settings->default_payment_method;
				else $default_payment_method = '';

				$subscription = array(
					'customer'               => $customer_id,
					'items'                  => array(
						array(
							'plan' => $plan_id,
						),
					),
					'default_payment_method' => $default_payment_method,
					'trial_from_plan'        => true,
					'metadata'               => $metadata,
					'expand'                 => array(
						'latest_invoice.payment_intent',
						'pending_setup_intent',
					),
				);
				if(!$subscription['default_payment_method'])
					unset($subscription['default_payment_method']);

				$idempotency_key = $gateway_checkout_id ? 's2member-sub-'.$gateway_checkout_id.'-'.$generation : ($stripe_request_id ? 's2member-sub-'.$stripe_request_id : md5(serialize($subscription)));
				$subscription = \Stripe\Subscription::create($subscription, array('idempotency_key' => $idempotency_key));
				if($gateway_checkout_id && is_object($subscription) && !empty($subscription->id))
				{
					$gateway_ids = array('customer_id' => (string)$customer_id, 'subscription_id' => (string)$subscription->id);
					if(!empty($subscription->latest_invoice->payment_intent->id))
						$gateway_ids['payment_intent_id'] = (string)$subscription->latest_invoice->payment_intent->id;
					if(!empty($subscription->pending_setup_intent->id))
						$gateway_ids['setup_intent_id'] = (string)$subscription->pending_setup_intent->id;
					self::update_gateway_checkout($gateway_checkout_id, $gateway_ids, !empty($subscription->status) ? (string)$subscription->status : '');
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
			
			try // Obtain existing subscription object.
			{
				//260828.2016 Modern Stripe SDKs no longer expose subscriptions through Customer; retrieve directly and still verify the expected customer owns it.
				$subscription = \Stripe\Subscription::retrieve($subscription_id);
				$subscription_customer = $subscription->customer;
				$subscription_customer_id = is_object($subscription_customer) && !empty($subscription_customer->id) ? (string)$subscription_customer->id : (string)$subscription_customer;
				if($subscription_customer_id !== (string)$customer_id)
					throw new \Exception('Stripe subscription does not belong to the expected customer.');

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
		 * Gets the User ID associated with a pending Stripe subscription.
		 *
		 * @since 260619
		 *
		 * @param string $subscr_id Stripe Subscription ID.
		 *
		 * @return integer|bool A WordPress User ID on success; else FALSE.
		 */
		public static function get_user_id_with_pending_subscr_id($subscr_id)
		{
			global $wpdb;
			/** @var wpdb $wpdb */

			if(!$subscr_id || !is_string($subscr_id))
				return FALSE;

			if(($user_id = $wpdb->get_var($wpdb->prepare("SELECT `user_id` FROM `".$wpdb->usermeta."` WHERE `meta_key` = %s AND `meta_value` = %s LIMIT 1", $wpdb->prefix.'s2member_stripe_pending_subscr_id', (string)$subscr_id))))
				return (int)$user_id;

			return FALSE;
		}

		/**
		 * Stores pending Stripe subscription details.
		 *
		 * Used when Stripe creates a subscription but the first payment is not final yet.
		 * A later browser return or webhook can process these details to grant paid access.
		 *
		 * @since 260619
		 *
		 * @param string  $subscr_id Stripe Subscription ID.
		 * @param array   $details Pending subscription details.
		 * @param integer $expiration Optional expiration time in seconds.
		 *
		 * @return boolean TRUE if successful; else FALSE.
		 */
		public static function store_pending_subscr_details($subscr_id, $details = array(), $expiration = WEEK_IN_SECONDS)
		{
			if(!$subscr_id || !is_string($subscr_id))
				return FALSE;

			$details = array_merge(array(
				'gateway'         => 'stripe',
				'subscription_id' => (string)$subscr_id,
				'user_id'         => 0,
				'ipn'             => array(),
				'created'         => time(),
				'expires'         => time() + abs((int)$expiration),
			), (array)$details);

			$details['subscription_id'] = (string)$subscr_id;
			$user_id = !empty($details['user_id']) ? (int)$details['user_id'] : 0;

			if(!$user_id || !get_userdata($user_id) || empty($details['ipn']) || !is_array($details['ipn']))
			{
				c_ws_plugin__s2member_utils_logs::log_entry('stripe-checkout', array('s2member_log' => array('Pending Stripe subscription details could not be stored because the details were invalid.'), 'subscr_id' => $subscr_id, 'user_id' => $user_id));

				return FALSE;
			}

			update_user_option($user_id, 's2member_stripe_pending_subscr_id', (string)$subscr_id);
			update_user_option($user_id, 's2member_stripe_pending_subscr_details', $details);

			$stored_details = get_user_option('s2member_stripe_pending_subscr_details', $user_id);
			$stored         = (string)get_user_option('s2member_stripe_pending_subscr_id', $user_id) === (string)$subscr_id && is_array($stored_details) && !empty($stored_details['subscription_id']) && (string)$stored_details['subscription_id'] === (string)$subscr_id;

			c_ws_plugin__s2member_utils_logs::log_entry('stripe-checkout', array('s2member_log' => array($stored ? 'Pending Stripe subscription details stored.' : 'Pending Stripe subscription details could not be verified after storage.'), 'subscr_id' => $subscr_id, 'user_id' => $user_id));

			return $stored;
		}

		/**
		 * Gets pending Stripe subscription details.
		 *
		 * @since 260619
		 *
		 * @param string $subscr_id Stripe Subscription ID.
		 *
		 * @return array|bool Pending subscription details; else FALSE.
		 */
		public static function get_pending_subscr_details($subscr_id)
		{
			if(!($user_id = self::get_user_id_with_pending_subscr_id($subscr_id)))
				return FALSE;

			$details = get_user_option('s2member_stripe_pending_subscr_details', $user_id);

			if(!is_array($details) || empty($details['subscription_id']) || (string)$details['subscription_id'] !== (string)$subscr_id)
			{
				c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', array('s2member_log' => array('Pending Stripe subscription lookup exists, but details are missing, invalid, or mismatched.'), 'subscr_id' => $subscr_id, 'user_id' => $user_id, 'stored_subscr_id' => is_array($details) && !empty($details['subscription_id']) ? $details['subscription_id'] : '', 'details_type' => gettype($details)));

				return FALSE;
			}

			if(empty($details['user_id']))
				$details['user_id'] = $user_id;

			if(!empty($details['expires']) && time() > (int)$details['expires'])
			{
				c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', array('s2member_log' => array('Pending Stripe subscription details expired and were deleted.'), 'subscr_id' => $subscr_id, 'user_id' => $user_id));
				self::delete_pending_subscr_details($subscr_id);

				return FALSE;
			}

			return $details;
		}

		/**
		 * Checks whether a pending Stripe SetupIntent has succeeded.
		 *
		 * @since 260619
		 *
		 * @param string|object $pending_setup_intent Pending SetupIntent ID or object.
		 *
		 * @return boolean TRUE if there is no pending SetupIntent, or it has succeeded.
		 */
		public static function pending_setup_intent_succeeded($pending_setup_intent)
		{
			if(empty($pending_setup_intent))
				return TRUE;

			$input_vars   = array('pending_setup_intent' => is_object($pending_setup_intent) && !empty($pending_setup_intent->id) ? $pending_setup_intent->id : $pending_setup_intent);
			$setup_intent = NULL;

			if(is_object($pending_setup_intent) && !empty($pending_setup_intent->status))
				$setup_intent = $pending_setup_intent;
			else if(is_string($pending_setup_intent) && strpos($pending_setup_intent, 'seti_') === 0)
			{
				self::init_stripe_sdk();

				try
				{
					$setup_intent = \Stripe\SetupIntent::retrieve($pending_setup_intent);
				}
				catch(exception $exception)
				{
					c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', array('s2member_log' => array('Unable to verify pending SetupIntent status.'), 'input_vars' => $input_vars, 'exception' => $exception));

					return FALSE;
				}
			}

			if(!is_object($setup_intent) || empty($setup_intent->status))
			{
				c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', array('s2member_log' => array('Unable to verify pending SetupIntent status.'), 'input_vars' => $input_vars));

				return FALSE;
			}

			$status = (string)$setup_intent->status;
			c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', array('s2member_log' => array('Pending SetupIntent status: `'.$status.'`.'), 'input_vars' => $input_vars));

			if($status !== 'succeeded')
				return FALSE;

			//260619 Mirror browser-return handling by setting the customer default payment method after a successful SetupIntent.
			if(!empty($setup_intent->customer) && !empty($setup_intent->payment_method))
			{
				$set_customer_default_payment_method = self::set_customer_default_payment_method($setup_intent->customer, $setup_intent->payment_method);

				if(!is_object($set_customer_default_payment_method))
					c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', array('s2member_log' => array('Non-fatal: unable to set customer default payment method after successful pending SetupIntent.'), 'input_vars' => $input_vars, 'set_customer_default_payment_method' => $set_customer_default_payment_method));
			}

			return TRUE;
		}

		/**
		 * Checks whether a pending Stripe subscription is ready to be processed.
		 *
		 * @since 260619
		 *
		 * @param object $stripe_subscription Stripe Subscription object.
		 *
		 * @return boolean TRUE if ready to process.
		 */
		public static function pending_subscr_is_ready_to_process($stripe_subscription)
		{
			if(!is_object($stripe_subscription) || empty($stripe_subscription->id) || empty($stripe_subscription->status))
				return FALSE;

			if(!in_array((string)$stripe_subscription->status, array('active', 'trialing'), TRUE))
				return FALSE;

			if(!empty($stripe_subscription->pending_setup_intent) && !self::pending_setup_intent_succeeded($stripe_subscription->pending_setup_intent))
			{
				c_ws_plugin__s2member_utils_logs::log_entry('stripe-ipn', array('s2member_log' => array('Pending Stripe subscription is not ready because its SetupIntent has not succeeded yet.'), 'subscr_id' => $stripe_subscription->id, 'status' => $stripe_subscription->status));

				return FALSE;
			}

			return TRUE;
		}

		/**
		 * Deletes pending Stripe subscription details.
		 *
		 * @since 260619
		 *
		 * @param string $subscr_id Stripe Subscription ID.
		 *
		 * @return boolean TRUE if deleted; else FALSE.
		 */
		public static function delete_pending_subscr_details($subscr_id)
		{
			if(!($user_id = self::get_user_id_with_pending_subscr_id($subscr_id)))
				return FALSE;

			delete_user_option($user_id, 's2member_stripe_pending_subscr_details');
			delete_user_option($user_id, 's2member_stripe_pending_subscr_id');

			return TRUE;
		}

		/**
		 * Checks whether a pending Stripe subscription has already been processed.
		 *
		 * @since 260619
		 *
		 * @param string  $subscr_id Stripe Subscription ID.
		 * @param integer $user_id Optional WordPress User ID.
		 *
		 * @return boolean TRUE if already processed.
		 */
		public static function pending_subscr_is_processed($subscr_id, $user_id = 0)
		{
			if(!$subscr_id || !is_string($subscr_id))
				return FALSE;

			if(!$user_id && !($user_id = c_ws_plugin__s2member_utils_users::get_user_id_with($subscr_id)))
				return FALSE;

			if((string)get_user_option('s2member_subscr_id', $user_id) === (string)$subscr_id && is_array($ipn_signup_vars = get_user_option('s2member_ipn_signup_vars', $user_id)) && !empty($ipn_signup_vars['subscr_id']) && (string)$ipn_signup_vars['subscr_id'] === (string)$subscr_id)
				return TRUE;

			return FALSE;
		}

		/**
		 * Processes a pending Stripe subscription by replaying the saved s2Member IPN proxy.
		 *
		 * @since 260619
		 *
		 * @param string $subscr_id Stripe Subscription ID.
		 *
		 * @return string|bool Log message if handled; else FALSE.
		 */
		public static function process_pending_subscr($subscr_id)
		{
			if(!($details = self::get_pending_subscr_details($subscr_id)))
				return FALSE;

			$lock_key = 's2m_stripe_pending_subscr_lock_'.md5((string)$subscr_id);

			if(get_transient($lock_key))
				return FALSE;

			set_transient($lock_key, time(), 10 * MINUTE_IN_SECONDS);

			$user_id = !empty($details['user_id']) ? (int)$details['user_id'] : 0;
			$ipn     = !empty($details['ipn']) && is_array($details['ipn']) ? $details['ipn'] : array();

			if(!$user_id || empty($ipn['subscr_id']) || (string)$ipn['subscr_id'] !== (string)$subscr_id)
			{
				delete_transient($lock_key);

				return FALSE;
			}

			if(self::pending_subscr_is_processed($subscr_id, $user_id))
			{
				if(!empty($details['gateway_checkout_id']))
					self::update_gateway_checkout((string)$details['gateway_checkout_id'], array(), '', array('browser_response' => _x('<strong>Thank you.</strong> Your payment has been confirmed and your account has been updated.', 's2member-front', 's2member')), 'fulfilled');

				self::delete_pending_subscr_details($subscr_id);
				delete_transient($lock_key);

				return 'Pending Stripe subscription already processed for subscription ID: `'.$subscr_id.'`.';
			}

			$ipn['s2member_paypal_proxy']              = 'stripe';
			$ipn['s2member_paypal_proxy_verification'] = c_ws_plugin__s2member_paypal_utilities::paypal_proxy_key_gen();

			c_ws_plugin__s2member_utils_urls::remote(home_url('/?s2member_paypal_notify=1'), $ipn, array('timeout' => 20));

			if(!self::pending_subscr_is_processed($subscr_id, $user_id))
			{
				delete_transient($lock_key);

				return FALSE;
			}

			if(!empty($details['old_subscr_id']) && apply_filters('s2member_pro_cancels_old_rp_before_new_rp', ((string)$details['old_subscr_id'] !== (string)$ipn['subscr_id']), array('pending_subscr_details' => $details, 'ipn' => $ipn)))
				c_ws_plugin__s2member_utilities::cancel_gateway_subscription(!empty($details['old_subscr_gateway']) ? $details['old_subscr_gateway'] : '', $details['old_subscr_id'], !empty($details['old_subscr_baid']) ? $details['old_subscr_baid'] : '', !empty($details['old_subscr_cid']) ? $details['old_subscr_cid'] : '', !empty($details['old_ipn_signup_vars']) && is_array($details['old_ipn_signup_vars']) ? $details['old_ipn_signup_vars'] : array());

			if(array_key_exists('list_server_opt_in', $details))
			{
				$previous_user_id = get_current_user_id();

				wp_set_current_user($user_id);
				c_ws_plugin__s2member_list_servers::process_list_servers_against_current_user((bool)$details['list_server_opt_in'], TRUE, TRUE);

				wp_set_current_user($previous_user_id);
			}

			//260830.0408 The asynchronous gateway confirmation completes the same Gateway Checkout, so later browser retries should recover success instead of the earlier pending message.
			if(!empty($details['gateway_checkout_id']))
				self::update_gateway_checkout((string)$details['gateway_checkout_id'], array(), '', array('browser_response' => _x('<strong>Thank you.</strong> Your payment has been confirmed and your account has been updated.', 's2member-front', 's2member')), 'fulfilled');

			self::delete_pending_subscr_details($subscr_id);
			delete_transient($lock_key);

			return 'Pending Stripe subscription processed for subscription ID: `'.$subscr_id.'`.';
		}

		/**
		 * Gets the transient key for a Stripe replacement-cancellation guard.
		 *
		 * @since 260319
		 *
		 * @param string $subscription_id Subscription ID in Stripe.
		 *
		 * @return string Transient key.
		 */
		public static function replacement_cancellation_guard_key($subscription_id)
		{
			return 's2m_stripe_rpl_sub_'.md5((string)$subscription_id);
		}

		/**
		 * Sets a short-lived guard for a Stripe replacement-cancellation.
		 *
		 * @since 260319
		 *
		 * @param string  $subscription_id Subscription ID in Stripe.
		 * @param integer $expiration Optional. Guard expiration time in seconds.
		 *
		 * @return boolean TRUE if successful; else FALSE.
		 */
		public static function set_replacement_cancellation_guard($subscription_id, $expiration = 21600)
		{
			if(!$subscription_id || !is_string($subscription_id))
				return FALSE;

			return set_transient(self::replacement_cancellation_guard_key($subscription_id), time(), abs((int)$expiration));
		}

		/**
		 * Checks for a short-lived guard for a Stripe replacement-cancellation.
		 *
		 * @since 260319
		 *
		 * @param string $subscription_id Subscription ID in Stripe.
		 *
		 * @return boolean TRUE if guard exists; else FALSE.
		 */
		public static function has_replacement_cancellation_guard($subscription_id)
		{
			if(!$subscription_id || !is_string($subscription_id))
				return FALSE;

			return (bool)get_transient(self::replacement_cancellation_guard_key($subscription_id));
		}

		/**
		 * Clears a short-lived guard for a Stripe replacement-cancellation.
		 *
		 * @since 260319
		 *
		 * @param string $subscription_id Subscription ID in Stripe.
		 *
		 * @return boolean TRUE if successful; else FALSE.
		 */
		public static function clear_replacement_cancellation_guard($subscription_id)
		{
			if(!$subscription_id || !is_string($subscription_id))
				return FALSE;

			return delete_transient(self::replacement_cancellation_guard_key($subscription_id));
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
			//260903.1455 Malformed webhook payloads are rejected below; avoid reading an event property before validating the decoded object.
			$input_vars = array('event_id' => (is_object($event) && isset($event->id)) ? $event->id : '');

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
					return (int)$amount;

				default: // In cents.
					return (int)number_format($amount * 100, 0, '.', '');
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
					return (int)$amount;

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
				$p1_days = (int)$num * (int)$days;
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
				$p3_days = (int)$num * (int)$days;
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

				return (int)$period * (int)$days;
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
					//260808 Safely unserialize the tax attributes.
					if(is_array($attr = (!empty($_p_tax_vars['attr'])) ? c_ws_plugin__s2member_utils_arrays::maybe_unserialize(c_ws_plugin__s2member_utils_encryption::decrypt($_p_tax_vars['attr'])) : FALSE))
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
		 * Gets a Payment Method.
		 *
		 * @param string $customer_id Customer ID in Stripe.
		 * @param string $payment_method_id Payment Method ID.
		 *
		 * @return object PaymentMethod object, else error message.
		 */
		public static function attached_card_payment_method($customer_id, $payment_method_id)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			try
			{
				$payment_method = \Stripe\PaymentMethod::retrieve($payment_method_id);

				if(!empty($payment_method->customer) && (string)$payment_method->customer !== (string)$customer_id)
					throw new \Exception('This payment method is attached to a different Stripe customer and cannot be reused for this customer.');

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
		 * Cancels incomplete Stripe subscriptions for a customer.
		 *
		 * @since 260321
		 *
		 * @param string $customer_id         Stripe customer ID.
		 * @param string $gateway_checkout_id Optional Gateway Checkout ID whose incomplete subscription must be preserved.
		 *
		 * @return bool True on success; else false.
		 */
		public static function cancel_incomplete_customer_subscriptions($customer_id, $gateway_checkout_id = '')
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			try
			{
				$subscriptions = \Stripe\Subscription::all(array(
					'customer' => $customer_id,
					'status'   => 'incomplete',
					'limit'    => 100,
				));

				if(!empty($subscriptions->data) && is_array($subscriptions->data))
					foreach($subscriptions->data as $_subscription)
						if(!empty($_subscription->id))
						{
							//260830.0052 Do not cancel the incomplete subscription owned by the Gateway Checkout currently being recovered.
							if($gateway_checkout_id && !empty($_subscription->metadata->s2member_gateway_checkout_id) && (string)$_subscription->metadata->s2member_gateway_checkout_id === (string)$gateway_checkout_id)
								continue;

							$_subscription->cancel();
						}

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), true);

				return true;
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return false;
			}
		}

		/**
		 * Cancels the incomplete Stripe subscription associated with a PaymentIntent.
		 *
		 * @since 260321
		 *
		 * @param string $payment_intent_id       Stripe PaymentIntent ID.
		 * @param string $canceled_subscription_id Optional output variable receiving the subscription ID only when this call actually cancels it.
		 *
		 * @return bool True on success; else false.
		 */
		public static function cancel_incomplete_subscription_by_payment_intent($payment_intent_id, &$canceled_subscription_id = '')
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.
			$canceled_subscription_id = '';

			if(strpos((string)$payment_intent_id, 'pi_') !== 0)
				return false;

			self::init_stripe_sdk();

			try
			{
				$payment_intent = \Stripe\PaymentIntent::retrieve($payment_intent_id);

				if(empty($payment_intent->invoice))
				{
					self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), '260321 Skipped cancellation because the PaymentIntent has no invoice.');

					return true;
				}

				$invoice = \Stripe\Invoice::retrieve($payment_intent->invoice);

				if(empty($invoice->subscription) || strpos((string)$invoice->subscription, 'sub_') !== 0)
				{
					self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), '260321 Skipped cancellation because the invoice has no subscription.');

					return true;
				}

				try
				{
					$subscription = \Stripe\Subscription::retrieve($invoice->subscription);
				}
				catch(exception $exception)
				{
					if((string)$exception->getMessage() && stripos($exception->getMessage(), 'No such subscription') !== false)
					{
						self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), '260321 Subscription already gone; treating cleanup as successful.');

						return true;
					}
					throw $exception;
				}

				if((string)$subscription->status !== 'incomplete')
				{
					self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), '260321 Skipped cancellation because the subscription is no longer incomplete.');

					return true;
				}

				if(!empty($subscription->latest_invoice))
				{
					$latest_invoice = \Stripe\Invoice::retrieve($subscription->latest_invoice);

					if($latest_invoice->status === 'draft')
						$latest_invoice = $latest_invoice->finalizeInvoice(array('auto_advance' => false));

					if($latest_invoice->status === 'open')
						$latest_invoice = $latest_invoice->voidInvoice();
				}

				try
				{
					$subscription = $subscription->delete();
				}
				catch(exception $exception)
				{
					if((string)$exception->getMessage() && stripos($exception->getMessage(), 'No such subscription') !== false)
					{
						//260830.0620 We already verified this exact subscription was incomplete; voiding its invoice can remove it before delete(), which is still a successful cleanup and must advance the Gateway Checkout generation.
						$canceled_subscription_id = (string)$invoice->subscription;
						self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), '260321 Subscription already gone during cancellation; treating cleanup as successful.');

						return true;
					}
					throw $exception;
				}

				$canceled_subscription_id = !empty($subscription->id) ? (string)$subscription->id : (string)$invoice->subscription;
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $subscription);

				return true;
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return false;
			}
		}

		public static function set_customer_default_payment_method($customer_id, $payment_method_id)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			try
			{
				$customer_update = array(
					'invoice_settings' => array(
						'default_payment_method' => $payment_method_id,
					),
				);
				$customer = \Stripe\Customer::update($customer_id, $customer_update);

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $customer);

				return $customer;
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		/**
		 * Remove card from subscription, so customer's default is used
		 * @since 230504
		 * @param string $subscr_id Subscription ID in Stripe.
		 * @return bool true after removed
		 */
		public static function subscr_remove_default_card($subscr_id) {
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.
			self::init_stripe_sdk();

			// Just let it catch the exeption if not a sub id? 
			// if (strpos($subscr_id, 'sub_') !== 0)
			// 	return false;

			try {
				// https://stripe.com/docs/api/subscriptions/update#update_subscription
				$subscription = \Stripe\Subscription::update(
					$subscr_id,
					['default_payment_method' => '']
				);

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $subscription);
				return true;
			}
			catch (exception $exception) {
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);
				return false;
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
			$gateway_checkout_state = self::gateway_checkout_state($post_vars);
			$gateway_checkout_id = $gateway_checkout_state ? (string)$gateway_checkout_state['id'] : '';
			$generation = $gateway_checkout_state ? max(1, (int)@$gateway_checkout_state['context']['stripe_payment_generation']) : 1;
			if($gateway_checkout_id)
			{
				$metadata['s2member_gateway_checkout_id'] = $gateway_checkout_id;
				$metadata['s2member_gateway_checkout_generation'] = (string)$generation;
			}
			//260830.0052 Gateway Checkout IDs survive reloads and are the preferred Stripe idempotency identity; retain request_id for pre-upgrade rendered forms.
			$stripe_request_id = $gateway_checkout_id ? $gateway_checkout_id : (!empty($post_vars['request_id']) && preg_match('/^[A-Za-z0-9-]{20,64}$/', (string)$post_vars['request_id']) ? (string)$post_vars['request_id'] : '');

			if(empty($pm_id))
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), 'Missing payment method.');
				return _x('The payment failed, please try again with a different card.', 's2member-front', 's2member');
			}

			try // Attempt to recover or create the Payment Intent.
			{
				if($gateway_checkout_id && is_object($existing_intent = self::find_gateway_checkout_payment_intent($cus_id, $gateway_checkout_id, $generation)))
				{
					$expected_amount = self::dollar_amount_to_cents($amount, $currency);
					if((int)$existing_intent->amount !== (int)$expected_amount || strtolower((string)$existing_intent->currency) !== strtolower((string)$currency))
					{
						$error = 'Unable to reconcile the existing Stripe PaymentIntent because its amount or currency does not match this Gateway Checkout.';
						self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $error);
						return $error;
					}

					if((string)$existing_intent->status === 'canceled')
					{
						//260830.0052 A canceled PaymentIntent is terminal; keep the logical checkout but advance its Stripe object generation before creating a replacement.
						$generation = self::advance_gateway_checkout_generation($gateway_checkout_id, 'payment', array('payment_intent_id'));
						if(!$generation)
							return _x('Unable to prepare this payment retry. Please try again.', 's2member-front', 's2member');
						$metadata['s2member_gateway_checkout_generation'] = (string)$generation;
					}
					else
					{
						//260830.0052 Retryable PaymentIntents remain the same checkout object; a new card updates that object instead of creating another charge candidate.
						if(in_array((string)$existing_intent->status, array('requires_payment_method', 'requires_confirmation'), TRUE) && (string)$existing_intent->payment_method !== (string)$pm_id)
							$existing_intent = \Stripe\PaymentIntent::update($existing_intent->id, array('payment_method' => $pm_id));

						self::update_gateway_checkout($gateway_checkout_id, array('customer_id' => (string)$cus_id, 'payment_intent_id' => (string)$existing_intent->id), (string)$existing_intent->status);
						self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $existing_intent);
						return $existing_intent;
					}
				}

				$intent = array(
					'amount'                      => self::dollar_amount_to_cents($amount, $currency),
					'currency'                    => $currency,
					'customer'                    => $cus_id,
					'payment_method'              => $pm_id,
					'confirmation_method'         => 'manual',
					'confirm'                     => true,
					'description'                 => $description, 
					'metadata'                    => $metadata,
					'statement_descriptor_suffix' => $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description'],
				);
				if(!trim($intent['statement_descriptor_suffix']))
					unset($intent['statement_descriptor_suffix']);

				$idempotency_key = $gateway_checkout_id ? 's2member-pi-'.$gateway_checkout_id.'-'.$generation : ($stripe_request_id ? 's2member-pi-'.$stripe_request_id : md5(serialize($intent)));
				$intent = \Stripe\PaymentIntent::create($intent, array('idempotency_key' => $idempotency_key));
				if($gateway_checkout_id && is_object($intent) && !empty($intent->id))
					self::update_gateway_checkout($gateway_checkout_id, array('customer_id' => (string)$cus_id, 'payment_intent_id' => (string)$intent->id), !empty($intent->status) ? (string)$intent->status : '');

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
			if ($intent->status == 'succeeded') {
				if(!empty($intent->customer) && !empty($intent->payment_method)) {
					$set_customer_default_payment_method = self::set_customer_default_payment_method($intent->customer, $intent->payment_method);
					if(!is_object($set_customer_default_payment_method))
						self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), '260317 Non-fatal: unable to set customer default payment method after successful SetupIntent. '.print_r($set_customer_default_payment_method, TRUE));
				}

				return $intent;
			}

			//260617 Block checkout fall-through on unhandled SetupIntent statuses.
			return array('response' => sprintf(_x('The payment method could not be confirmed. Stripe returned status: %s.', 's2member-front', 's2member'), esc_html((string)$intent->status)), 'error' => TRUE);
		}

		/**
		 * Check the PaymentIntent's status.
		 * 
		 * @param string|int $pi_id PaymentIntent id.
		 * @param bool       $set_customer_default_payment_method Optional. Set the customer's default payment method after success.
		 * 
		 * @return object|array PaymentIntent object if succeeded, or response array with error.
		 */
		public static function handle_payment_intent_status($pi_id, $set_customer_default_payment_method = FALSE)
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
			if ($intent->status == 'succeeded') {
				if($set_customer_default_payment_method && !empty($intent->customer) && !empty($intent->payment_method)) {
					$set_customer_default_payment_method_response = self::set_customer_default_payment_method($intent->customer, $intent->payment_method);
					if(!is_object($set_customer_default_payment_method_response))
						self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), '260317 Non-fatal: unable to set customer default payment method after successful PaymentIntent. '.print_r($set_customer_default_payment_method_response, TRUE));
				}

				return $intent;
			}

			//260617 Block checkout fall-through on unhandled PaymentIntent statuses.
			return array('response' => sprintf(_x('The payment could not be completed. Stripe returned status: %s.', 's2member-front', 's2member'), esc_html((string)$intent->status)), 'error' => TRUE);
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
		public static function update_payment_intent($pi_id, $args)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			// Get the intent
			$intent = \Stripe\PaymentIntent::update($pi_id, $args);

			self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $intent);

			return $intent;
		}

		public static function udpate_payment_intent($pi_id, $args)
		{
			return self::update_payment_intent($pi_id, $args);
		}

		/**
		 * Update an existing Setup Intent.
		 * 
		 * @param string|int $seti_id 
		 * @param array      $args Array of arguments to update. https://stripe.com/docs/api/setup_intents/update
		 * 
		 * @return object The SetupIntent object
		 */
		public static function update_setup_intent($seti_id, $args)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			self::init_stripe_sdk();

			// Get the intent
			$intent = \Stripe\SetupIntent::update($seti_id, $args);

			self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $intent);

			return $intent;
		}

		public static function udpate_setup_intent($seti_id, $args)
		{
			return self::update_setup_intent($seti_id, $args);
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
