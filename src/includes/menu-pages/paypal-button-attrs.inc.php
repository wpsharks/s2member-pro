<?php
// @codingStandardsIgnoreFile
/**
 * Menu page for s2Member Pro (PayPal Button Attributes).
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
 * @package s2Member\Menu_Pages
 * @since 110604
 */
if(!defined('WPINC')) // MUST have WordPress.
	exit("Do not access this file directly.");

if(!class_exists("c_ws_plugin__s2member_pro_menu_page_paypal_button_attrs"))
{
	/**
	 * Menu page for s2Member Pro (PayPal Button Attributes).
	 *
	 * @package s2Member\Menu_Pages
	 * @since 110604
	 */
	class c_ws_plugin__s2member_pro_menu_page_paypal_button_attrs
	{
		public function __construct()
		{
			echo '</ul>'."\n";

			echo '<h3>Additional Shortcode Attributes (enabled by s2Member Pro)</h3>'."\n";

			echo '<ul class="ws-menu-page-li-margins">'."\n";
			//260913.2356 Clarify success="" for normal checkout versus confirmed on-site PayPal Checkout cancellation buttons.
			echo '<li><code>success=""</code> Success Return URL <em>(optional)</em>. During normal checkout, this lets you choose the landing page for a new Customer after successful checkout <em>(i.e., your own custom Thank-You Page)</em>; it is not used for billing modifications. With a PayPal Checkout cancellation button using <code>cancel="1" output="button"</code>, it is also used after s2Member confirms that the Subscription was cancelled. Cancellation links using <code>output="anchor"</code> or <code>output="url"</code> open PayPal\'s subscription-management interface instead, so s2Member cannot redirect after a cancellation completed there. If supplied, this must be a full URL starting with <code>http://</code> or <code>https://</code>. For cancellation buttons, the Success URL must normally be on the same site; additional hosts can be allowed with WordPress\'s <code>allowed_redirect_hosts</code> filter.</li>'."\n";
			echo '<li><code>accept="card"</code> Enables card funding in the PayPal-hosted Checkout experience <em>(optional)</em>. When PayPal allows it, buyers may be offered an option to pay by debit/credit card as a guest (without logging into a PayPal account). Availability is controlled by PayPal and can vary based on merchant account settings (e.g., guest checkout/account optional), buyer country/region, PayPal eligibility/risk rules, and browser privacy/tracking protections. s2Member cannot force this option to appear.</li>'."\n";
			//261018.1856 Explain Pro-only calendar shortcuts alongside the other additional PayPal Button attributes.
		echo '<li><code>rs="01"</code> Regular Start <em>(optional)</em>. The date of the first regular payment, using <code>dd</code>, <code>mm-dd</code>, or <code>yyyy-mm-dd</code>. Only for recurring membership purchases. s2Member calculates the initial period and overrides <code>ta</code>, <code>tp</code>, and <code>tt</code>. For monthly dates, the day must be 01 through 28; the first regular payment must occur within one regular billing period.</li>'."\n";
			echo '<li><code>sa=""</code> Starting Amount <em>(optional; requires <code>rs</code>)</em>. The one-time charge for the initial period between checkout and the first regular payment on <code>rs</code>. For example, <code>sa="10.00"</code> charges 10.00 for that initial period; <code>sa="0"</code> makes it free when supported by the checkout. If omitted or empty, s2Member calculates the initial amount automatically based on <code>ra</code> and the time until <code>rs</code>. The existing gateway minimum charge may apply.</li>'."\n";
			echo '<li><code>eot="12-31"</code> Calendar End of Term <em>(optional)</em>. Sets the membership access end date using <code>dd</code>, <code>mm-dd</code>, or <code>yyyy-mm-dd</code>; <code>eot="31"</code> means the last day of the current month. For Buy Now, it calculates the access duration; for supported finite subscriptions, it calculates <code>rrt</code>. Existing EOT/grace and Auto-Extend behavior still applies. Unsupported billing and date combinations display an error. If no regular payment falls before EOT, a free initial period is converted to a Buy Now charge of at least $0.01 for PayPal Buttons.</li>'."\n";
		}
	}
}

new c_ws_plugin__s2member_pro_menu_page_paypal_button_attrs ();
