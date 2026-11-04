<?php
// @codingStandardsIgnoreFile
/**
* s2Member Pro utilities.
*
* Copyright: © 2009-2011
* {@link http://websharks-inc.com/ WebSharks, Inc.}
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
* 	See: {@link http://s2member.com/prices/}
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
* @package s2Member\Utilities
* @since 1.5
*/
if(!defined('WPINC')) // MUST have WordPress.
	exit("Do not access this file directly.");

if (!class_exists ("c_ws_plugin__s2member_pro_utilities"))
	{
		/**
		* s2Member Pro utilities.
		*
		* @package s2Member\Utilities
		* @since 1.5
		*/
		class c_ws_plugin__s2member_pro_utilities
			{

				/**
				* Converts Currency Codes to Currency Symbols (deprecated).
				*
				* @package s2Member\Utilities
				* @since 1.5
				*
				* @param string $currency Expects a 3 character Currency Code.
				* @return string A Currency Symbol. Defaults to the `$` sign.
				*
				* @deprecated Starting with v110531, please use:
				* 	``c_ws_plugin__s2member_utils_cur::symbol()``
				*/
				public static function currency_symbol ($currency = FALSE)
					{
						return c_ws_plugin__s2member_utils_cur::symbol ($currency);
					}
				/**
				* Expands a state/province abbrev into it's full version.
				*
				* This works for the United States and Canada only.
				*
				* @package s2Member\Utilities
				* @since 1.5
				*
				* @param string $state A state/province abbreviation.
				* @param string $country A country code. One of `US|CA`.
				* @return string The full state/province name.
				*/
				public static function full_state ($state = FALSE, $country = FALSE)
					{
						static $lines; // Optimizes this routine for repeated usage.

						if (strlen ($state = strtoupper ($state)) === 2 && preg_match ("/^US|CA$/", ($country = strtoupper ($country))))
							{
								if (!isset ($lines[$country])) // If $lines are NOT already established.
									{
										if ($country === "US") // Handle lookups for the United States.
											{
												$txt = file_get_contents (dirname (dirname (__FILE__)) . "/usps-states.txt");
											}
										else if ($country === "CA") // Lookups for Canada.
											{
												$txt = file_get_contents (dirname (dirname (__FILE__)) . "/ca-provinces.txt");
											}

										$lines[$country] = preg_split ("/[\r\n\t]+/", trim (strtoupper ($txt)));
									}

								foreach ($lines[$country] as $line) // Find full version.

									if ($line = trim ($line)) // Do NOT process empty lines.
										{
											list ($full, $abbr) = preg_split ("/;/", trim ($line));
											if ($abbr === $state && $full)
												return ucwords ($full);
										}
							}
						return $state; // Full state name.
					}
				/**
				 * Resolve calendar-based regular start and purchased-term end into legacy payment terms.
				 *
				 * @package s2Member\Utilities
				 * @since 261018
				 *
				 * @param array $attr Normalized shortcode payment attributes.
				 * @param string $gateway The provider shortcode family.
				 * @param int|null $now Optional timestamp for deterministic QA.
				 * @return array Resolved `attr` plus an `error` string; unchanged when not configured.
				 */
				public static function calendar_payment_terms($attr, $gateway, $now = NULL)
				{
					$result = array('attr' => $attr, 'error' => '');
					if(!is_array($attr))
						return array('attr' => array(), 'error' => 'Invalid calendar payment attributes.');
					foreach(array('rs', 'sa', 're') as $key)
						if(isset($attr[$key]) && !is_scalar($attr[$key]))
							return array('attr' => $attr, 'error' => 'Calendar payment date attributes must be strings.');
					$rs = isset($attr['rs']) ? trim((string)$attr['rs']) : '';
					$sa = isset($attr['sa']) ? trim((string)$attr['sa']) : '';
					$re = isset($attr['re']) ? trim((string)$attr['re']) : '';
					if($rs === '' && $sa === '' && $re === '') return $result;

					//261018.1856 Modification-only checkout (modify="1") is still a membership purchase, so it may use calendar terms.
					//261018.0627 Keep calendar shortcuts out of non-purchase cancellation, registration, billing-update, and Specific Post/Page forms.
					foreach(array('cancel', 'unsub', 'update', 'register', 'sp') as $flag)
						if(!empty($attr[$flag])) return array('attr' => $attr, 'error' => 'Calendar payment dates are only supported on membership purchase shortcodes.');

					if($rs === '' && $sa !== '')
						return array('attr' => $attr, 'error' => 'The sa attribute requires rs.');
					$recurring = !in_array(strtoupper((string)$attr['rr']), array('BN', '0'), TRUE);
					//261019.2037 TODO: Support recurring re only after gateway-verified cutoff, paid-through tracking, and cancellation-aware EOT are implemented; rrt alone cannot guarantee a calendar end.
					if($recurring && $re !== '')
						return array('attr' => $attr, 'error' => 'The re attribute currently supports Buy Now purchases only; recurring calendar end dates are not yet supported.');
					//261019.2037 rr=0 with a trial is still a delayed-payment subscription, not an exact Buy Now end date.
					if($re !== '' && (string)$attr['rr'] === '0' && !empty($attr['tp']))
						return array('attr' => $attr, 'error' => 'The re attribute does not support nonrecurring subscriptions with a trial; use rr="BN" without a trial.');
					//261025.0102 ClickBank uses rr=0 for Standard products; rr=BN would omit the purchased-term duration from its checkout metadata.
					if($gateway === 'clickbank' && (string)$attr['rr'] === 'BN')
						return array('attr' => $attr, 'error' => 'ClickBank Buy Now products require rr="0"; rr="BN" is not supported.');
					if($gateway === 'clickbank' && ($recurring || $rs !== '' || $sa !== ''))
						return array('attr' => $attr, 'error' => 'ClickBank cannot dynamically alter initial charges or product subscription terms; only Buy Now re is supported.');
					if($rs !== '' && !$recurring)
						return array('attr' => $attr, 'error' => 'The rs attribute requires a recurring subscription.');
					if($recurring && (!ctype_digit((string)$attr['rp']) || (int)$attr['rp'] < 1 || !in_array($attr['rt'], array('D', 'W', 'M', 'Y'), TRUE)))
						return array('attr' => $attr, 'error' => 'Calendar subscription dates require a valid rp/rt regular period.');

					try {
						//261025.0102 wp_timezone() requires WP 5.3; retain calendar-shortcode support on the advertised WP 4.2+ minimum.
						if(function_exists('wp_timezone')) $timezone = wp_timezone();
						else if(($timezone_string = (string)get_option('timezone_string'))) $timezone = new DateTimeZone($timezone_string);
						else {
							$offset = (float)get_option('gmt_offset', 0);
							$offset_abs = abs($offset);
							$timezone = new DateTimeZone(sprintf('%s%02d:%02d', $offset < 0 ? '-' : '+', floor($offset_abs), round(($offset_abs - floor($offset_abs)) * 60)));
						}
						$today = (new DateTimeImmutable('@'.(int)($now === NULL ? time() : $now)))->setTimezone($timezone)->setTime(0, 0, 0);
						$regularStart = NULL;
						if($rs !== '') {
							//261018.0627 Date-only rs sets the first regular charge; a same-day rs starts regular payments immediately.
							$regularStart = self::calendar_date($rs, $today, FALSE);
							$maxStart = self::calendar_add_period($today, (int)$attr['rp'], $attr['rt']);
							if($regularStart > $maxStart) throw new InvalidArgumentException('rs must be within one regular billing period.');
							$days = (int)$today->diff($regularStart)->days;
							if($days > 365) throw new InvalidArgumentException('The initial billing period exceeds the supported 365-day limit.');
							if($days) {
								$amount = $sa === '' ? self::calendar_initial_amount($attr, $today, $regularStart, $days) : $sa;
								if(!is_numeric($amount) || !is_finite((float)$amount) || (float)$amount < 0)
									throw new InvalidArgumentException('sa must be a nonnegative numeric amount.');
								//261018.1755 Auto proration must not fail checkout or become free when rounded below an existing gateway charge minimum; explicit sa remains authoritative.
								if($sa === '' && (float)$attr['ra'] > 0)
									$amount = max((float)$amount, ($gateway === 'stripe' ? .50 : .01));
								if($gateway === 'stripe' && (float)$amount > 0 && (float)$amount < .50)
									throw new InvalidArgumentException('Stripe requires an initial payment of at least 0.50.');
								$attr['ta'] = number_format((float)$amount, 2, '.', '');
								$attr['tp'] = (string)$days;
								$attr['tt'] = 'D';
							} else {
								$attr['ta'] = '0'; $attr['tp'] = '0'; $attr['tt'] = 'D';
							}
						}
						if($re !== '') {
							$lastDay = self::calendar_date($re, $today, TRUE);
							if($lastDay < $today) throw new InvalidArgumentException('The requested re date is already past this month.');
							$exclusiveEnd = $lastDay->modify('+1 day');
							$daysToEnd = (int)$today->diff($exclusiveEnd)->days;
							if($daysToEnd < 1) throw new InvalidArgumentException('re must cover at least the current day.');
							//261019.2037 Only Buy Now re reaches this block; preserve the legacy rp/rt duration and EOT/grace behavior.
							$attr['rp'] = (string)$daysToEnd; $attr['rt'] = 'D';
						}
					} catch(Exception $error) {
						return array('attr' => $result['attr'], 'error' => $error->getMessage());
					}
					return array('attr' => $attr, 'error' => '');
				}

				/**
				 * Parse supported partial/complete calendar date syntax in WordPress timezone.
				 * @package s2Member\Utilities
				 * @since 261018
				 * @param string $value `dd`, `mm-dd`, or `yyyy-mm-dd`.
				 * @param DateTimeImmutable $today Local checkout day.
				 * @param bool $expiration Whether `31` represents current month's last purchased-term day.
				 * @return DateTimeImmutable Resolved calendar day.
				 */
				public static function calendar_date($value, $today, $expiration)
				{
					if(!is_string($value)) throw new InvalidArgumentException('Invalid calendar date.');
					$tz = $today->getTimezone();
					if(preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
						$date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, $tz);
						if(!$date || $date->format('Y-m-d') !== $value || $date < $today)
							throw new InvalidArgumentException('Invalid or expired exact calendar date.');
						return $date;
					}
					if(preg_match('/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/D', $value, $matches)) {
						$year = (int)$today->format('Y');
						for($i = 0; $i < 2; $i++) {
							$day = (int)$matches[2];
							//261018.0627 For annual February 29, use February 28 in non-leap years.
							if($matches[1] === '02' && $day === 29 && !checkdate(2, 29, $year)) $day = 28;
							if(!checkdate((int)$matches[1], $day, $year)) throw new InvalidArgumentException('Invalid recurring calendar date.');
							$date = $today->setDate($year, (int)$matches[1], $day);
							if($date >= $today) return $date;
							$year++;
						}
					}
					if(preg_match('/^(0[1-9]|[12]\d|3[01])$/D', $value, $matches)) {
						$day = (int)$matches[1];
						if(!$expiration && $day > 28)
							throw new InvalidArgumentException('Monthly rs supports days 01 through 28; the month-end billing anchor is not portable.');
						if($expiration) {
							//261018.0627 Day 31 is month-end in the selected month, never an overflow into the next month.
							if($day === 31) $day = (int)$today->format('t');
							else if(!checkdate((int)$today->format('m'), $day, (int)$today->format('Y')))
								throw new InvalidArgumentException('This end-of-term day does not exist in the current month.');
							return $today->setDate((int)$today->format('Y'), (int)$today->format('m'), $day);
						}
						$date = $today->setDate((int)$today->format('Y'), (int)$today->format('m'), $day);
						if($date < $today) {
							//261018.0635 Advance the date first; referencing the old month inside the chained expression discarded the rollover.
							$date = $date->modify('first day of next month');
							$date = $date->setDate((int)$date->format('Y'), (int)$date->format('m'), $day);
						}
						return $date;
					}
					throw new InvalidArgumentException('Calendar dates must use dd, mm-dd, or yyyy-mm-dd.');
				}

				/**
				 * Advance a billing date using existing regular period units, preserving legacy checkout terms.
				 * @package s2Member\Utilities
				 * @since 261018
				 * @param DateTimeImmutable $date Local billing day.
				 * @param int $count Period count.
				 * @param string $unit D, W, M, or Y.
				 * @return DateTimeImmutable Next nominal billing day.
				 */
				public static function calendar_add_period($date, $count, $unit)
				{
					$map = array('D' => 'D', 'W' => 'W', 'M' => 'M', 'Y' => 'Y');
					if($count < 1 || !isset($map[$unit])) throw new InvalidArgumentException('Invalid recurring billing interval.');
					return $date->add(new DateInterval('P'.$count.$map[$unit]));
				}

				/**
				 * Prorate the initial charge by actual calendar days in the full billing period ending at rs.
				 * @package s2Member\Utilities
				 * @since 261018
				 * @param array $attr Regular price/period.
				 * @param DateTimeImmutable $today Checkout day.
				 * @param DateTimeImmutable $start First regular billing day.
				 * @param int $days Number of initial calendar days.
				 * @return float Initial amount before coupon/tax handling.
				 */
				public static function calendar_initial_amount($attr, $today, $start, $days)
				{
					$rp = (int)$attr['rp']; $unit = $attr['rt'];
					if($rp < 1 || !in_array($unit, array('D', 'W', 'M', 'Y'), TRUE))
						throw new InvalidArgumentException('Invalid period for automatic starting amount.');
					if($unit === 'M' || $unit === 'Y') {
						//261019.2037 Subtract from day 1, then clamp to the prior month/year's last day; PHP's default subtraction can overflow at month ends and Feb 29.
						$anchor = $start->modify('first day of this month')->sub(new DateInterval('P'.$rp.$unit));
						$periodStart = $anchor->setDate((int)$anchor->format('Y'), (int)$anchor->format('m'), min((int)$start->format('j'), (int)$anchor->format('t')));
					} else $periodStart = $start->sub(new DateInterval('P'.$rp.$unit));
					$fullDays = (int)$periodStart->diff($start)->days;
					if($fullDays < 1) throw new InvalidArgumentException('Invalid period for automatic starting amount.');
					//261019.2037 The numerator is the checkout-to-rs calendar-day count; cap it at the full period so automatic proration never exceeds ra.
					return round((float)$attr['ra'] * min(max(0, $days), $fullDays) / $fullDays, 2);
				}

				/**
				 * Apply the calendar resolver to a shortcode's by-reference attribute hook.
				 * @package s2Member\Utilities
				 * @since 261018
				 * @param array $vars Hook-provided variables with `__refs`.
				 * @param string $gateway Gateway identifier.
				 * @return void
				 */
				public static function calendar_apply_hook($vars, $gateway)
				{
					if(empty($vars['__refs']['attr']) || !is_array($vars['__refs']['attr'])) return;
					$attr = &$vars['__refs']['attr'];
					$has_dates = FALSE;
					foreach(array('rs', 'sa', 're') as $key)
						if(isset($attr[$key]) && (!is_scalar($attr[$key]) || trim((string)$attr[$key]) !== '')) $has_dates = TRUE;
					//261018.0640 Never introduce extra shortcode keys into ordinary signed checkout terms.
					if(!$has_dates) { unset($attr['rs'], $attr['sa'], $attr['re']); return; }
					$resolved = self::calendar_payment_terms($attr, $gateway);
					$attr = $resolved['attr'];
					unset($attr['rs'], $attr['sa'], $attr['re']);
					if($resolved['error'] !== '') $attr['calendar_payment_error'] = $resolved['error'];
				}

				/**
				 * Resolve PayPal Pro-Form calendar dates before payment processing.
				 * @package s2Member\Utilities
				 * @since 261018
				 * @param array $vars Hook variable references.
				 * @return void
				 */
				public static function calendar_paypal_form($vars) { self::calendar_apply_hook($vars, 'paypal_form'); }
				/**
				 * Resolve Stripe Pro-Form calendar dates before payment processing.
				 * @package s2Member\Utilities
				 * @since 261018
				 * @param array $vars Hook variable references.
				 * @return void
				 */
				public static function calendar_stripe_form($vars) { self::calendar_apply_hook($vars, 'stripe'); }
				/**
				 * Resolve Authorize.Net Pro-Form calendar dates before payment processing.
				 * @package s2Member\Utilities
				 * @since 261018
				 * @param array $vars Hook variable references.
				 * @return void
				 */
				public static function calendar_authnet_form($vars) { self::calendar_apply_hook($vars, 'authnet'); }

			}
	}
