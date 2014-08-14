<?php
/**
 * Stripe utilities.
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
		 * Get a Stripe customer object instance.
		 *
		 * @param integer $user_id If it's for an existing user; pass the user's ID (optional).
		 * @param string  $email Customer's email address (optional).
		 * @param string  $fname Customer's first name (optional).
		 * @param string  $lname Customer's last name (optional).
		 * @param array   $metadata Any metadata (optional).
		 *
		 * @return Stripe_Customer|string Customer object; else error message.
		 */
		public static function get_customer($user_id = 0, $email = '', $fname = '', $lname = '', $metadata = array())
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			require_once dirname(__FILE__).'/stripe-sdk/lib/Stripe.php';
			Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);

			try // Obtain existing customer object; else create a new one.
			{
				try // Attempt to find an existing customer; if that's possible.
				{
					if($user_id && ($customer_id = get_user_option('s2member_subscr_cid', $user_id)))
						$customer = Stripe_Customer::retrieve($customer_id);
				}
				catch(exception $exception)
				{
					// Fail silently; create a new customer below in this case.
				}
				if(empty($customer) || !is_object($customer))
					$customer = Stripe_Customer::create(array(
						                                    'email'       => $email,
						                                    'description' => trim($fname.' '.$lname),
						                                    'metadata'    => $metadata
					                                    ));
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $customer);

				return $customer; // Stripe customer object.
			}
			catch(exception $exception)
			{
				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $exception);

				return self::error_message($exception);
			}
		}

		/**
		 * Set a Stripe customer card/token.
		 *
		 * @param string $customer_id Customer ID in Stripe.
		 * @param string $card_token Stripe token.
		 *
		 * @return Stripe_Customer|string Customer object; else error message.
		 */
		public static function set_customer_card_token($customer_id, $card_token)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			require_once dirname(__FILE__).'/stripe-sdk/lib/Stripe.php';
			Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);

			try // Attempt to update the customer's card/token.
			{
				$customer       = Stripe_Customer::retrieve($customer_id);
				$customer->card = $card_token; // Update.
				$customer       = $customer->save();

				self::log_entry(__FUNCTION__, $input_time, $input_vars, time(), $customer);

				return $customer; // Stripe customer object.
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
		 * @param string               $customer_id Customer ID in Stripe.
		 * @param integer|float|string $amount The amount to charge.
		 * @param string               $currency Three character currency code.
		 * @param string               $description Description of the charge.
		 * @param array                $metadata Any additional metadata.
		 *
		 * @return Stripe_Charge|string Charge object; else error message.
		 */
		public static function create_customer_charge($customer_id, $amount, $currency, $description, $metadata = array())
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			require_once dirname(__FILE__).'/stripe-sdk/lib/Stripe.php';
			Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);

			try // Attempt to charge the customer.
			{
				$charge = array(
					'customer'              => $customer_id,
					'description'           => $description, 'metadata' => $metadata,
					'amount'                => self::dollar_amount_to_cents($amount, $currency), 'currency' => $currency,
					'statement_description' => $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description']
				);
				if(!trim($charge['statement_description'])) unset($charge['statement_description']);

				$charge = Stripe_Charge::create($charge);
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
		 * Get a Stripe plan object instance.
		 *
		 * @param array $shortcode_attrs An array of shortcode attributes.
		 * @param array $metadata Any additional metadata.
		 *
		 * @return Stripe_Plan|string Plan object; else error message.
		 */
		public static function get_plan($shortcode_attrs, $metadata = array())
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			require_once dirname(__FILE__).'/stripe-sdk/lib/Stripe.php';
			Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);

			$amount                      = $shortcode_attrs['ra'];
			$currency                    = $shortcode_attrs['cc'];
			$name                        = $shortcode_attrs['desc'];
			$metadata['recurring']       = $shortcode_attrs['rr'] && $shortcode_attrs['rr'] !== 'BN';
			$metadata['recurring_times'] = $shortcode_attrs['rr'] && $shortcode_attrs['rrt'] ? (integer)$shortcode_attrs['rrt'] : -1;
			$trial_period_days           = self::per_term_2_days($shortcode_attrs['tp'], $shortcode_attrs['tt']);
			$interval_days               = self::per_term_2_days($shortcode_attrs['rp'], $shortcode_attrs['rt']);

			$plan_id = 's2_'.md5($amount.$currency.$name.$trial_period_days.$interval_days.serialize($metadata).$GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description']);

			try // Attempt to get an existing plan; else create a new one.
			{
				try // Try to find an existing plan.
				{
					$plan = Stripe_Plan::retrieve($plan_id);
				}
				catch(exception $exception) // Else create one.
				{
					$plan = array(
						'id'                    => $plan_id,
						'name'                  => $name, 'metadata' => $metadata,
						'amount'                => self::dollar_amount_to_cents($amount, $currency), 'currency' => $currency,
						'statement_description' => $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_statement_description'],
						'interval'              => 'day', 'interval_count' => $interval_days,
						'trial_period_days'     => $trial_period_days ? $trial_period_days : $interval_days,
					);
					if(!trim($plan['statement_description'])) unset($plan['statement_description']);

					$plan = Stripe_Plan::create($plan);
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
		 * @param array  $metadata Any additional metadata.
		 *
		 * @return Stripe_Subscription|string Subscription object; else error message.
		 */
		public static function create_customer_subscription($customer_id, $plan_id, $metadata = array())
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			require_once dirname(__FILE__).'/stripe-sdk/lib/Stripe.php';
			Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);

			try // Attempt to create a new subscription for this customer.
			{
				$customer     = Stripe_Customer::retrieve($customer_id);
				$subscription = $customer->subscriptions->create(array('plan'     => $plan_id,
				                                                       'metadata' => $metadata));

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
		 * Get a Stripe customer subscription object instance.
		 *
		 * @param string $customer_id Customer ID in Stripe.
		 * @param string $subscription_id Subscription ID in Stripe.
		 *
		 * @return Stripe_Subscription|string Subscription object; else error message.
		 */
		public static function get_customer_subscription($customer_id, $subscription_id)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			require_once dirname(__FILE__).'/stripe-sdk/lib/Stripe.php';
			Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);

			try // Obtain existing customer object; else create a new one.
			{
				$customer     = Stripe_Customer::retrieve($customer_id);
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
		 * @param boolean $at_period_end Defaults to a `TRUE` value.
		 *    If `TRUE`, cancellation is delayed until the end of the current period.
		 *    If `FALSE`, cancellation is NOT delayed; i.e. it occurs immediately.
		 *
		 * @return Stripe_Subscription|string Subscription object; else error message.
		 */
		public static function cancel_customer_subscription($customer_id, $subscription_id, $at_period_end = TRUE)
		{
			$input_time = time(); // Initialize.
			$input_vars = get_defined_vars(); // Arguments.

			require_once dirname(__FILE__).'/stripe-sdk/lib/Stripe.php';
			Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);

			try // Attempt to cancel the subscription for this customer.
			{
				$customer     = Stripe_Customer::retrieve($customer_id);
				$subscription = $customer->subscriptions->retrieve($subscription_id)->cancel(array('at_period_end' => $at_period_end));

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

			require_once dirname(__FILE__).'/stripe-sdk/lib/Stripe.php';
			Stripe::setApiKey($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_stripe_api_secret_key']);

			try // Acquire the event from the Stripe servers.
			{
				if(!is_object($event) || empty($event->id))
					throw new exception('Missing event ID.');

				$event = Stripe_Event::retrieve($event->id);

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
					return (integer)($amount * 100);
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
		 * @param exception $exception
		 *
		 * @return string Error message.
		 */
		public static function error_message($exception)
		{
			if($exception instanceof Stripe_CardError)
			{
				$body  = $exception->getJsonBody();
				$error = $body['error'];

				return sprintf(_x('Error code: <code>%1$s</code>. %2$s.', 's2member-front', 's2member'),
				               esc_html(trim($error['code'], '.')), esc_html(trim($error['message'], '.')));
			}
			if($exception instanceof Stripe_InvalidRequestError)
				return _x('Invalid parameters to Stripe; please contact the site owner.', 's2member-front', 's2member');

			if($exception instanceof Stripe_AuthenticationError)
				return _x('Invalid Stripe API keys; please contact the site owner.', 's2member-front', 's2member');

			if($exception instanceof Stripe_ApiConnectionError)
				return _x('Network communication failure with Stripe; please try again.', 's2member-front', 's2member');

			if($exception instanceof Stripe_Error)
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

			else if($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_tax_rates'])
				return TRUE;

			return FALSE;
		}

		/**
		 * Handles the return of Tax for Pro Forms, via AJAX; through a JSON object.
		 *
		 * @package s2Member\Stripe
		 * @since 140617
		 */
		public static function stripe_ajax_tax()
		{
			if(!empty($_POST['ws_plugin__s2member_pro_stripe_ajax_tax']) && ($nonce = $_POST['ws_plugin__s2member_pro_stripe_ajax_tax']) && (wp_verify_nonce($nonce, 'ws-plugin--s2member-pro-stripe-ajax-tax') || c_ws_plugin__s2member_utils_encryption::decrypt($nonce) === 'ws-plugin--s2member-pro-stripe-ajax-tax'))
				/* A wp_verify_nonce() won't always work here, because s2member-pro-min.js must be cacheable. The output from wp_create_nonce() would go stale.
						So instead, s2member-pro-min.js should use ``c_ws_plugin__s2member_utils_encryption::encrypt()`` as an alternate form of nonce. */
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
		 *
		 * @return array Array of calculations.
		 */
		public static function cost($trial_sub_total = '', $sub_total = '', $state = '', $country = '', $zip = '', $currency = '', $desc = '')
		{
			$state   = strtoupper(c_ws_plugin__s2member_pro_utilities::full_state($state, ($country = strtoupper($country))));
			$rates   = apply_filters('ws_plugin__s2member_pro_tax_rates_before_cost_calculation', strtoupper($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_tax_rates']), get_defined_vars());
			$default = $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_default_tax'];
			$ps      = _x('%', 's2member-front percentage-symbol', 's2member');

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
		 * @param array  $attr An array of Pro Form Attributes.
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
			if(($coupon_code = trim(strtolower($coupon_code))) || ($coupon_code = trim(strtolower($attr['coupon']))))
				if($attr['accept_coupons'] && $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_coupon_codes'])
				{
					$cs = c_ws_plugin__s2member_utils_cur::symbol($attr['cc']);
					$tx = (c_ws_plugin__s2member_pro_stripe_utilities::tax_may_apply()) ? _x(' + tax', 's2member-front', 's2member') : '';
					$ps = _x('%', 's2member-front percentage-symbol', 's2member');

					$full_coupon_code = ''; // Initialize.
					if(strlen($affiliate_suffix_chars = $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_affiliate_coupon_code_suffix_chars']))
						if(preg_match('/^(.+?)'.preg_quote($affiliate_suffix_chars, '/').'([0-9]+)$/i', $coupon_code, $m))
							($full_coupon_code = $m[0]).($coupon_code = $m[1]).($affiliate_id = $m[2]);
					unset($affiliate_suffix_chars, $m); // Just a little housekeeping here.

					foreach(c_ws_plugin__s2member_utils_strings::trim_deep(preg_split('/['."\r\n\t".']+/', $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_coupon_codes'])) as $_line)
					{
						if(($_line = trim($_line, ' '."\r\n\t\0\x0B".'|')) && is_array($_coupon = preg_split('/\|/', $_line)))
						{
							$coupon['code'] = (!empty($_coupon[0])) ? trim(strtolower($_coupon[0])) : '';

							$coupon['percentage'] = (!empty($_coupon[1]) && preg_match('/%/', $_coupon[1])) ? (float)$_coupon[1] : 0;
							$coupon['flat-rate']  = (!empty($_coupon[1]) && !preg_match('/%/', $_coupon[1])) ? (float)$_coupon[1] : 0;

							$coupon['expired'] = (!empty($_coupon[2]) && strtotime($_coupon[2]) < time()) ? $_coupon[2] : FALSE;

							$coupon['directive'] = (!empty($_coupon[3]) && ($_coupon[3] = strtolower($_coupon[3]))) ? preg_replace('/_/', '-', $_coupon[3]) : 'all';
							$coupon['directive'] = (preg_match('/^(ta-only|ra-only|all)$/', $coupon['directive'])) ? $coupon['directive'] : 'all';

							$coupon['singulars'] = (!empty($_coupon[4]) && ($_coupon[4] = strtolower($_coupon[4])) && $_coupon[4] !== 'all') ? $_coupon[4] : 'all';
							$coupon['singulars'] = ($coupon['singulars'] !== 'all') ? preg_split('/['."\r\n\t".'\s;,]+/', trim(preg_replace('/[^0-9,]/', '', $coupon['singulars']), ',')) : array('all');

							unset($_line, $_coupon); // Just a little housekeeping here. Unset these temporary variables.

							if($coupon_code === $coupon['code'] && !$coupon['expired'] /* And it's NOT yet expired, or lasts forever? */)
							{
								if($coupon['singulars'] === array('all') || in_array($attr['singular'], $coupon['singulars']))
								{
									$coupon_accepted = TRUE; // Yes, this Coupon Code has been accepted.

									if($coupon['flat-rate']) // If it's a flat-rate Coupon.
									{
										if(($coupon['directive'] === 'ra-only' || $coupon['directive'] === 'all') && $attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$ta = number_format($attr['ta'], 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$ra = number_format($attr['ra'] - $coupon['flat-rate'], 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s)', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.$ra.$tx);
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.$ra.$tx);
										}
										else if($coupon['directive'] === 'ta-only' && $attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$ta = number_format($attr['ta'] - $coupon['flat-rate'], 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$ra = number_format($attr['ra'], 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s, then %s)', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
										}
										else if($coupon['directive'] === 'ra-only' && $attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$ta = number_format($attr['ta'], 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$ra = number_format($attr['ra'] - $coupon['flat-rate'], 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s, then %s)', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
										}
										else if($coupon['directive'] === 'all' && $attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$ta = number_format($attr['ta'] - $coupon['flat-rate'], 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$ra = number_format($attr['ra'] - $coupon['flat-rate'], 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s, then %s)', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
										}
										else if($coupon['directive'] === 'ra-only' && !$attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$ta = number_format($attr['ta'], 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$ra = number_format($attr['ra'] - $coupon['flat-rate'], 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s)', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']).$tx);
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']).$tx);
										}
										else if($coupon['directive'] === 'all' && !$attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$ta = number_format($attr['ta'] - $coupon['flat-rate'], 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$ra = number_format($attr['ra'] - $coupon['flat-rate'], 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s)', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']).$tx);
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', 's2member-front', 's2member'), $cs.number_format($coupon['flat-rate'], 2, '.', ''), $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']).$tx);
										}
										else // Otherwise, we need a default response to display.
											$response = _x('<div>Sorry, your Coupon is not applicable.</div>', 's2member-front', 's2member');
									}
									else if($coupon['percentage']) // Else if it's a percentage.
									{
										if(($coupon['directive'] === 'ra-only' || $coupon['directive'] === 'all') && $attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$p  = ($attr['ta'] / 100) * $coupon['percentage'];
											$ta = number_format($attr['ta'], 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$p  = ($attr['ra'] / 100) * $coupon['percentage'];
											$ra = number_format($attr['ra'] - $p, 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s)', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.$ra.$tx);
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.$ra.$tx);
										}
										else if($coupon['directive'] === 'ta-only' && $attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$p  = ($attr['ta'] / 100) * $coupon['percentage'];
											$ta = number_format($attr['ta'] - $p, 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$p  = ($attr['ra'] / 100) * $coupon['percentage'];
											$ra = number_format($attr['ra'], 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s, then %s)', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
										}
										else if($coupon['directive'] === 'ra-only' && $attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$p  = ($attr['ta'] / 100) * $coupon['percentage'];
											$ta = number_format($attr['ta'], 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$p  = ($attr['ra'] / 100) * $coupon['percentage'];
											$ra = number_format($attr['ra'] - $p, 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s, then %s)', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
										}
										else if($coupon['directive'] === 'all' && $attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$p  = ($attr['ta'] / 100) * $coupon['percentage'];
											$ta = number_format($attr['ta'] - $p, 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$p  = ($attr['ra'] / 100) * $coupon['percentage'];
											$ra = number_format($attr['ra'] - $p, 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s, then %s)', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s, then %s</strong>)</div>', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ta, $attr['tp'].' '.$attr['tt']).$tx, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']));
										}
										else if($coupon['directive'] === 'ra-only' && !$attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$p  = ($attr['ta'] / 100) * $coupon['percentage'];
											$ta = number_format($attr['ta'], 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$p  = ($attr['ra'] / 100) * $coupon['percentage'];
											$ra = number_format($attr['ra'] - $p, 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s)', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']).$tx);
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']).$tx);
										}
										else if($coupon['directive'] === 'all' && !$attr['tp'] && !$attr['sp'])
										{
											$coupon_applies = TRUE; // Applying.

											$p  = ($attr['ta'] / 100) * $coupon['percentage'];
											$ta = number_format($attr['ta'] - $p, 2, '.', '');
											$ta = ($ta >= 0.50) ? $ta : '0.00';

											$p  = ($attr['ra'] / 100) * $coupon['percentage'];
											$ra = number_format($attr['ra'] - $p, 2, '.', '');
											$ra = ($ra >= 0.50) ? $ra : '0.00';

											$desc     = sprintf(_x('COUPON %s off. (Now: %s)', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']).$tx);
											$response = sprintf(_x('<div>Coupon: <strong>%s off</strong>. (Now: <strong>%s</strong>)</div>', 's2member-front', 's2member'), number_format($coupon['percentage'], 0).$ps, $cs.c_ws_plugin__s2member_utils_time::amount_period_term($ra, $attr['rp'].' '.$attr['rt'], $attr['rr']).$tx);
										}
										else // Otherwise, we need a default response to display.
											$response = _x('<div>Sorry, your Coupon is not applicable.</div>', 's2member-front', 's2member');
									}

									else // Else there was no discount applied at all.
										$response = sprintf(_x('<div>Coupon: <strong>%s0.00 off</strong>.</div>', 's2member-front', 's2member'), $cs);
								}

								else // Otherwise, we need a response that indicates not applicable for this purchase.
									$response = _x('<div>Sorry, your Coupon cannot be applied to this particular purchase.</div>', 's2member-front', 's2member');
							}

							else if($coupon_code === $coupon['code'] && $coupon['expired'])
								$response = sprintf(_x('<div>Sorry, your Coupon <strong>expired</strong>: <em>%s</em>.</div>', 's2member-front', 's2member'), $coupon['expired']);
						}
					}
					if(isset($coupon_applies, $full_coupon_code, $desc) && $coupon_applies /* Need to modify the description dynamically? */)
						// translators: `%1$s` is new price/description, after coupon applied. `%2$s` is original description.
						$attr['desc'] = sprintf(_x('%1$s %2$s ~ ORIGINALLY: %3$s', 's2member-front', 's2member'), strtoupper($full_coupon_code), $desc, $attr['desc']);

					$attr['ta'] = (isset($coupon_applies, $ta) && $coupon_applies) ? $ta : $attr['ta']; // Do we have a new Trial Amount?
					$attr['ra'] = (isset($coupon_applies, $ra) && $coupon_applies) ? $ra : $attr['ra']; // A new Regular Amount?

					if(is_array($process) && (in_array('affiliates-silent-post', $process) || in_array('affiliates-1px-response', $process)) /* Processing affiliates? */)
						if(isset($coupon_applies) && $coupon_applies && !empty($affiliate_id) /* Now, is this an Affiliate Coupon Code? Contains an affiliate ID? */)
							if(empty($_COOKIE['idev']) /* Special consideration here. iDevAffiliate must NOT have already tracked this customer. */)
								if(($_urls = $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_affiliate_coupon_code_tracking_urls']))

									foreach(preg_split('/['."\r\n\t".']+/', $_urls) as $_url /* Notify each of the URLs. */)

										if(($_url = preg_replace('/%%full_coupon_code%%/i', c_ws_plugin__s2member_utils_strings::esc_refs(urlencode($full_coupon_code)), $_url)))
											if(($_url = preg_replace('/%%coupon_code%%/i', c_ws_plugin__s2member_utils_strings::esc_refs(urlencode($coupon_code)), $_url)))
												if(($_url = preg_replace('/%%(?:coupon_affiliate_id|affiliate_id)%%/i', c_ws_plugin__s2member_utils_strings::esc_refs(urlencode($affiliate_id)), $_url)))
													if(($_url = preg_replace('/%%user_ip%%/i', c_ws_plugin__s2member_utils_strings::esc_refs(urlencode($_SERVER['REMOTE_ADDR'])), $_url)))
													{
														if(($_url = trim(preg_replace('/%%(.+?)%%/i', '', $_url))) /* Cleanup any remaining Replacement Codes. */)

															if(!($_r = 0) && ($_url = preg_replace('/^silent-php\|/i', '', $_url, 1, $_r)) && $_r && in_array('affiliates-silent-post', $process))
																c_ws_plugin__s2member_utils_urls::remote($_url, FALSE, array('blocking' => FALSE));

															else if(!($_r = 0) && ($_url = preg_replace('/^img-1px\|/i', '', $_url, 1, $_r)) && $_r && in_array('affiliates-1px-response', $process))
																if(!empty($response) && $return === 'response' /* Now, we MUST also have a ``$response``, and MUST be returning ``$response``. */)
																	$response .= '\n'.'<img src="'.esc_attr($_url).'" style="width:0; height:0; border:0;" alt="" />';
													}
					unset($_urls, $_url, $_r); // Just a little housekeeping here. Unset these variables.

					if(empty($response)) // Is ``$response`` NOT set by now? If it's not, we need a default ``$response``.
						$response = _x('<div>Sorry, your Coupon is N/A, invalid or expired.</div>', 's2member-front', 's2member');
				}
				else // Otherwise, we need a default response to display.
					$response = _x('<div>Sorry, your Coupon is N/A, invalid or expired.</div>', 's2member-front', 's2member');

			$attr['_coupon_applies']      = (isset($coupon_applies) && $coupon_applies) ? '1' : '0';
			$attr['_coupon_code']         = (isset($coupon_applies) && $coupon_applies) ? $coupon_code : '';
			$attr['_full_coupon_code']    = (isset($coupon_applies) && $coupon_applies && !empty($full_coupon_code)) ? $full_coupon_code : ((isset($coupon_applies) && $coupon_applies) ? $coupon_code : '');
			$attr['_coupon_affiliate_id'] = (isset($coupon_applies) && $coupon_applies && !empty($affiliate_id) && empty($_COOKIE['idev'])) ? $affiliate_id : '';

			return ($return === 'response') ? (!empty($response) ? (string)$response : '') : $attr;
		}
	}
}