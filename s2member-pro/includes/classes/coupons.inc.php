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
		protected $coupons = array();

		public function __construct()
		{
			foreach(c_ws_plugin__s2member_utils_strings::trim_deep(preg_split('/['."\r\n\t".']+/', $GLOBALS['WS_PLUGIN__']['s2member']['o']['pro_coupon_codes'])) as $_line)
			{
				if(($_line = trim($_line, " \r\n\t\0\x0B|")) && is_array($_coupon_parts = preg_split('/\|/', $_line)))
				{
					$_coupon['code'] = !empty($_coupon_parts[0]) ? trim(strtolower($_coupon_parts[0])) : '';

					$_coupon['percentage'] = !empty($_coupon_parts[1]) && preg_match('/%/', $_coupon_parts[1]) ? (float)$_coupon_parts[1] : 0;
					$_coupon['flat_rate']  = !empty($_coupon_parts[1]) && !preg_match('/%/', $_coupon_parts[1]) ? (float)$_coupon_parts[1] : 0;
					$_coupon['expired']    = !empty($_coupon_parts[2]) && strtotime($_coupon_parts[2]) < time() ? $_coupon_parts[2] : FALSE;

					$_coupon['directive'] = !empty($_coupon_parts[3]) ? preg_replace('/_/', '-', strtolower($_coupon_parts[3])) : 'all';
					$_coupon['directive'] = preg_match('/^(ta-only|ra-only|all)$/', $_coupon['directive']) ? $_coupon['directive'] : 'all';

					$_coupon['singulars'] = !empty($_coupon_parts[4]) ? strtolower($_coupon_parts[4]) : 'all';
					$_coupon['singulars'] = $_coupon['singulars'] !== 'all' ? preg_split('/['."\r\n\t".'\s;,]+/', trim(preg_replace('/[^0-9,]/', '', $_coupon['singulars']), ',')) : array('all');

					$_coupon['users'] = !empty($_coupon_parts[5]) ? strtolower($_coupon_parts[5]) : 'all';
					$_coupon['users'] = $_coupon['users'] !== 'all' ? preg_split('/['."\r\n\t".'\s;,]+/', trim(preg_replace('/[^0-9,]/', '', $_coupon['users']), ',')) : array('all');

					$_coupon['max_uses'] = !empty($_coupon_parts[6]) ? (integer)$_coupon_parts[6] : 0;

					$this->coupons[] = (object)$_coupon; // Add this coupon to the array now.

					unset($_line, $_coupon_parts, $_coupon); // Just a little housekeeping.
				}
			}
		}

		public function apply()
		{
			$cs = c_ws_plugin__s2member_utils_cur::symbol($attr['cc']);
			$tx = (c_ws_plugin__s2member_pro_paypal_utilities::paypal_tax_may_apply()) ? _x(' + tax', 's2member-front', 's2member') : '';
			$ps = _x('%', 's2member-front percentage-symbol', 's2member');
		}
	}
}