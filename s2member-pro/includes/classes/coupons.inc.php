<?php
/**
 * Coupon Codes.
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
 * @package s2Member\Coupons
 * @since 150122
 */
if(!defined('WPINC')) // MUST have WordPress.
	exit ('Do not access this file directly.');

if(!class_exists('c_ws_plugin__s2member_pro_coupons'))
{
	/**
	 * Coupon Codes.
	 *
	 * @package s2Member\Coupons
	 * @since 150122
	 */
	class c_ws_plugin__s2member_pro_coupons
	{
		public $list = '';

		public $coupons = array();

		public function __construct($args = array())
		{
			$default_args = array(); // Defaults.
			$args         = array_merge($default_args, (array)$args);
			$args         = array_intersect_key($args, $default_args);

			$this->coupons = $this->list_to_coupons($GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_coupon_codes']);
		}

		public function list_to_coupons($list, $update = TRUE)
		{
			$list    = trim((string)$list);
			$coupons = array(); // Initialize.

			foreach(array_map('trim', preg_split('/['."\r\n".']+/', $list, NULL, PREG_SPLIT_NO_EMPTY)) as $_line)
			{
				if(!($_line = trim($_line, " \r\n\t\0\x0B|")))
					continue; // Empty line; continue.

				if(strpos($_line, '#') === 0)
					continue; // Comment line.

				$_coupon_parts = $_coupon = array(); // Initialize.
				$_coupon_parts = array_map('trim', preg_split('/\|/', $_line));

				if(!($_coupon['code'] = !empty($_coupon_parts[0]) ? $this->n_code($_coupon_parts[0]) : ''))
					continue; // Not applicable; no coupon code after sanitizing.

				$_coupon['discount']            = !empty($_coupon_parts[1]) ? $_coupon_parts[1] : '';
				$_coupon['percentage_discount'] = $_coupon['discount'] && preg_match('/%/', $_coupon['discount']) ? (float)$_coupon['discount'] : (float)0;
				$_coupon['flat_discount']       = $_coupon['discount'] && !preg_match('/%/', $_coupon['discount']) ? (float)$_coupon['discount'] : (float)0;

				$_active_time           = $_expires_time = '';
				$_coupon['active_time'] = $_coupon['expires_time'] = 0;
				if(($_coupon['dates'] = !empty($_coupon_parts[2]) ? $_coupon_parts[2] : ''))
				{
					if(strpos($_coupon['dates'], '~') !== FALSE)
						list($_active_time, $_expires_time) = array_map('trim', explode('~', $_coupon['dates'], 2));
					else $_expires_time = $_coupon['dates']; // Back compat.

					if($_active_time && ($_active_time = strtotime($_active_time)))
						$_coupon['active_time'] = (integer)$_active_time;

					if($_expires_time && ($_expires_time = strtotime($_expires_time)))
						$_coupon['expires_time'] = (integer)$_expires_time;
				}
				unset($_active_time, $_expires_time); // Housekeeping.

				$_coupon['directive'] = !empty($_coupon_parts[3]) && strtolower($_coupon_parts[3]) !== 'all' ? preg_replace('/_/', '-', strtolower($_coupon_parts[3])) : '';
				$_coupon['directive'] = preg_match('/^(?:ta\-only|ra\-only)$/', $_coupon['directive']) ? $_coupon['directive'] : '';

				$_coupon['singulars'] = !empty($_coupon_parts[4]) && strtolower($_coupon_parts[4]) !== 'all' ? $_coupon_parts[4] : '';
				$_coupon['singulars'] = $_coupon['singulars'] ? array_map('intval', preg_split('/,+/', preg_replace('/[^0-9,]/', '', $_coupon['singulars']), NULL, PREG_SPLIT_NO_EMPTY)) : array();

				$_coupon['users'] = !empty($_coupon_parts[5]) && strtolower($_coupon_parts[5]) !== 'all' ? $_coupon_parts[5] : '';
				$_coupon['users'] = $_coupon['users'] ? array_map('intval', preg_split('/,+/', preg_replace('/[^0-9,]/', '', $_coupon['users']), NULL, PREG_SPLIT_NO_EMPTY)) : array();

				$_coupon['max_uses'] = !empty($_coupon_parts[6]) ? (integer)$_coupon_parts[6] : 0;

				$coupons[] = $_coupon; // Add this coupon to the array now.
			}
			unset($_line, $_coupon_parts, $_coupon); // Housekeeping.

			if($update) // Update class properties?
			{
				$this->coupons = $coupons;
				$this->list    = $this->coupons_to_list($coupons, FALSE);
			}
			return $coupons;
		}

		public function coupons_to_list($coupons, $update = TRUE)
		{
			$list    = ''; // Initialize.
			$coupons = (array)$coupons;

			foreach($coupons as $_coupon)
			{
				if(!$_coupon || !is_array($_coupon))
					continue; // Not applicable.

				if(empty($_coupon['code']) || !($_coupon['code'] = trim((string)$_coupon['code'])))
					continue; // Not applicable; empty coupon code.

				# The coupon code itself.

				$list .= str_replace('|', '', $_coupon['code']).'|';

				# Discount amount; or percentage/flat rate.

				if(isset($_coupon['discount']))
					$list .= str_replace('|', '', trim((string)$_coupon['discount'])).'|';

				else if(isset($_coupon['percentage_discount']))
					$list .= str_replace('|', '', trim((string)$_coupon['percentage_discount'])).'%|';

				else if(isset($_coupon['flat_discount']))
					$list .= str_replace('|', '', trim((string)$_coupon['flat_discount'])).'|';

				else $list .= '|'; // Unspecified in this case.

				# Dates; i.e. `dates` or individual times.

				if(isset($_coupon['dates']))
					$list .= str_replace('|', '', trim((string)$_coupon['dates'])).'|';

				else if(isset($_coupon['active_time']) || isset($_coupon['expires_time']))
					$list .= str_replace('|', '', (isset($_coupon['active_time']) && (integer)$_coupon['active_time'] ? date('Y/m/d', (integer)$_coupon['active_time']) : '').
					                              '~'.(isset($_coupon['expires_time']) && (integer)$_coupon['expires_time'] ? date('Y/m/d', (integer)$_coupon['expires_time']) : '')).'|';

				else $list .= '|'; // Unspecified in this case.

				# Coupon directive; i.e. how does it apply.

				if(isset($_coupon['directive']))
					$list .= str_replace('|', '', trim((string)$_coupon['directive'])).'|';

				else $list .= '|'; // Unspecified in this case.

				# Coupon singulars; i.e. particular post IDs where it's applicable.

				if(isset($_coupon['singulars']) && is_array($_coupon['singulars']))
					$list .= str_replace('|', '', implode(',', $_coupon['singulars'])).'|';

				else if(isset($_coupon['singulars']))
					$list .= str_replace('|', '', trim((string)$_coupon['singulars'])).'|';

				else $list .= '|'; // Unspecified in this case.

				# Coupon users; i.e. particular user IDs where it's applicable.

				if(isset($_coupon['users']) && is_array($_coupon['users']))
					$list .= str_replace('|', '', implode(',', $_coupon['users'])).'|';

				else if(isset($_coupon['users']))
					$list .= str_replace('|', '', trim((string)$_coupon['users'])).'|';

				else $list .= '|'; // Unspecified in this case.

				# Coupon users; i.e. particular user IDs where it's applicable.

				if(isset($_coupon['max_uses']))
					$list .= str_replace('|', '', trim((string)$_coupon['max_uses'])).'|';

				else $list .= '|'; // Unspecified in this case.

				# Line ending; always.

				$list .= "\n"; // One coupon per line.
			}
			unset($_coupon); // Housekeeping.

			$list = trim($list); // Trim it up now.

			if($update) // Update class properties?
			{
				$this->list    = $list; // Update.
				$this->coupons = $this->list_to_coupons($list, FALSE);
			}
			return $list;
		}

		/**
		 * Checks to see if a Coupon Code was supplied, and if so; what does it provide?
		 *
		 * @package s2Member\PayPal
		 * @since 1.5
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
		public function check($attr, $coupon_code, $return = 'attr', $process = array())
		{
			global $wpdb; // Global DB reference.
			/** @var $wpdb \wpdb Reference for IDEs. */

			global $current_user; // Global user reference.
			/** @var $current_user \WP_User for IDEs. */

			$current_time = time(); // UTC timestamp.
			$valid_coupon = NULL; // Defaults to a `NULL` value.

			if(($coupon_code = $this->n_code($coupon_code)))
				foreach($this->coupons as $_coupon)
				{
					if($coupon_code !== $_coupon['code'])
						continue; // Not a match here.

					if(!$_coupon['active_time'] || $current_time >= $_coupon['active_time'])
						if(!$_coupon['expires_time'] || $current_time <= $_coupon['expires_time'])
							if(!$_coupon['singulars'] || (!empty($attr['singular']) && in_array((integer)$attr['singular'], $_coupon['singulars'], TRUE)))
								if(!$_coupon['users'] || ($current_user->ID && in_array((integer)$current_user->ID, $_coupon['users'], TRUE)))
									if(!$_coupon['max_uses'] || $this->get_uses($_coupon['code']) < $_coupon['max_uses'])
										$valid_coupon = $_coupon; // It's discount time! :-)
					break; // It is either valid or it's not.
				}
			unset($_coupon); // Housekeeping.
		}

		public function get_uses($coupon_code)
		{
			if(!($coupon_code = trim((string)$coupon_code)))
				return 0; // Not possible.

			return (integer)get_option($this->uses_option_key($coupon_code));
		}

		public function update_uses($coupon_code)
		{
			if(!($coupon_code = trim((string)$coupon_code)))
				return; // Not possible.

			$uses_option_key = $this->uses_option_key($coupon_code);

			if(($current_uses = get_option($uses_option_key)) === FALSE)
				add_option($uses_option_key, 1, '', 'no');
			else update_option($uses_option_key, $current_uses + 1);
		}

		public function delete_uses($coupon_code)
		{
			if(!($coupon_code = trim((string)$coupon_code)))
				return; // Not possible.

			delete_option($this->uses_option_key($coupon_code));
		}

		public function uses_option_key($coupon_code)
		{
			if(!($coupon_code = trim((string)$coupon_code)))
				return ''; // Not possible.

			return 's2member_cpc_uses_'.$this->n_code_hash($coupon_code);
		}

		public function n_code($coupon_code)
		{
			if(!($coupon_code = trim((string)$coupon_code)))
				return ''; // Not possible.

			return strtoupper(preg_replace('/[_\-]+/', '', $coupon_code));
		}

		public function n_code_hash($coupon_code)
		{
			if(!($coupon_code = trim((string)$coupon_code)))
				return ''; // Not possible.

			return hash('crc32b', $this->n_code($coupon_code)); // 8 chars.
		}
	}
}